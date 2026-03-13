<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_UC_Admin {
    private static $instance = null;
    private $option_key = 'wpuc_last_scan_results';
    private $state_key  = 'wpuc_scan_state';
    private $debug_key  = 'wpuc_last_scan_debug';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wpuc_start_scan', [$this, 'ajax_start_scan']);
        add_action('wp_ajax_wpuc_process_scan', [$this, 'ajax_process_scan']);
        add_action('wp_ajax_wpuc_delete_batch', [$this, 'ajax_delete_batch']);
        add_action('wp_ajax_wpuc_delete_all_filtered_batch', [$this, 'ajax_delete_all_filtered_batch']);
    }

    public function register_menu() {
        add_media_page(__('Flint Media Cleaner', 'wp-unused-cleaner'), __('Flint Media Cleaner', 'wp-unused-cleaner'), 'manage_options', 'wp-unused-cleaner', [$this, 'render_page']);
    }

    public function enqueue_assets($hook) {
        if ('media_page_wp-unused-cleaner' !== $hook) {
            return;
        }
        wp_enqueue_style('wpuc-admin', WPUC_URL . 'assets/admin.css', [], WPUC_VERSION);
        wp_enqueue_script('wpuc-admin', WPUC_URL . 'assets/admin.js', [], WPUC_VERSION, true);

        $current_filters = [
            'attachment_kind' => $this->sanitize_kind_filter(isset($_GET['attachment_kind']) ? wp_unslash($_GET['attachment_kind']) : 'all'),
            'file_kind'       => $this->sanitize_kind_filter(isset($_GET['file_kind']) ? wp_unslash($_GET['file_kind']) : 'all'),
            'attachment_search'=> sanitize_text_field(isset($_GET['attachment_search']) ? wp_unslash($_GET['attachment_search']) : ''),
            'file_search'     => sanitize_text_field(isset($_GET['file_search']) ? wp_unslash($_GET['file_search']) : ''),
            'attachment_sort' => $this->sanitize_attachment_sort(isset($_GET['attachment_sort']) ? wp_unslash($_GET['attachment_sort']) : 'title'),
            'attachment_dir'  => $this->sanitize_sort_dir(isset($_GET['attachment_dir']) ? wp_unslash($_GET['attachment_dir']) : 'asc'),
            'file_sort'       => $this->sanitize_file_sort(isset($_GET['file_sort']) ? wp_unslash($_GET['file_sort']) : 'relative'),
            'file_dir'        => $this->sanitize_sort_dir(isset($_GET['file_dir']) ? wp_unslash($_GET['file_dir']) : 'asc'),
        ];

        wp_localize_script('wpuc-admin', 'wpucAdmin', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('wpuc_ajax'),
            'filters'   => $current_filters,
            'strings'   => [
                'scanComplete'   => __('Scan complete.', 'wp-unused-cleaner'),
                'scanFailed'     => __('The scan could not continue.', 'wp-unused-cleaner'),
                'deleteComplete' => __('Deletion complete.', 'wp-unused-cleaner'),
                'deleteFailed'   => __('The deletion batch failed.', 'wp-unused-cleaner'),
                'working'        => __('Working…', 'wp-unused-cleaner'),
                'deletePhase'    => __('Deleting selected items', 'wp-unused-cleaner'),
                'deleteAllPhase' => __('Deleting all filtered results', 'wp-unused-cleaner'),
                'deleteDone'     => __('Deletion complete', 'wp-unused-cleaner'),
                'selectOne'      => __('Select at least one item first.', 'wp-unused-cleaner'),
            ],
            'batchSize' => 15,
        ]);
    }

    public function ajax_start_scan() {
        $this->authorize_ajax();
        $state = WP_UC_Scanner::instance()->create_scan_state();
        update_option($this->state_key, $state, false);
        update_option($this->debug_key, [
            'status' => 'started',
            'message' => __('Batch scan initialized.', 'wp-unused-cleaner'),
            'phase' => isset($state['phase']) ? (string) $state['phase'] : 'posts',
            'cursor' => isset($state['cursor']) ? (int) $state['cursor'] : 0,
            'updated_at' => current_time('mysql'),
            'warnings' => [],
        ], false);
        wp_send_json_success(['progress' => WP_UC_Scanner::instance()->get_progress_data($state)]);
    }

    public function ajax_process_scan() {
        $this->authorize_ajax();
        $state = get_option($this->state_key, []);
        if (empty($state) || ! is_array($state)) {
            wp_send_json_error(['message' => __('No active scan was found.', 'wp-unused-cleaner')], 400);
        }
        try {
            $batch = WP_UC_Scanner::instance()->process_scan_batch($state);
            update_option($this->state_key, $batch['state'], false);
            if (! empty($batch['done'])) {
                update_option($this->option_key, $batch['results'], false);
                delete_option($this->state_key);
            }
            update_option($this->debug_key, [
                'status' => ! empty($batch['done']) ? 'completed' : 'running',
                'message' => ! empty($batch['done']) ? __('Scan completed.', 'wp-unused-cleaner') : __('Scan batch processed.', 'wp-unused-cleaner'),
                'phase' => isset($batch['state']['phase']) ? (string) $batch['state']['phase'] : '',
                'cursor' => isset($batch['state']['cursor']) ? (int) $batch['state']['cursor'] : 0,
                'updated_at' => current_time('mysql'),
                'progress' => $batch['progress'],
                'warnings' => array_slice((array) ($batch['state']['warnings'] ?? []), -10),
                'results_warning_count' => count((array) (($batch['results']['warnings'] ?? ($batch['state']['warnings'] ?? [])))),
            ], false);
            wp_send_json_success([
                'done' => ! empty($batch['done']),
                'progress' => $batch['progress'],
                'results' => $batch['done'] ? $batch['results'] : null,
                'debug' => get_option($this->debug_key, []),
            ]);
        } catch (Throwable $e) {
            $debug = [
                'status' => 'error',
                'message' => sprintf(__('Scan failed during %1$s.', 'wp-unused-cleaner'), isset($state['phase']) ? (string) $state['phase'] : __('unknown step', 'wp-unused-cleaner')),
                'error' => $e->getMessage(),
                'phase' => isset($state['phase']) ? (string) $state['phase'] : '',
                'cursor' => isset($state['cursor']) ? (int) $state['cursor'] : 0,
                'updated_at' => current_time('mysql'),
                'warnings' => array_slice((array) ($state['warnings'] ?? []), -10),
            ];
            update_option($this->debug_key, $debug, false);
            wp_send_json_error(['message' => sprintf(__('Scan failed during %1$s: %2$s', 'wp-unused-cleaner'), isset($state['phase']) ? (string) $state['phase'] : __('unknown step', 'wp-unused-cleaner'), $e->getMessage()), 'debug' => $debug], 500);
        }
    }

    public function ajax_delete_batch() {
        $this->authorize_ajax();
        $attachment_ids = isset($_POST['attachment_ids']) ? array_map('intval', (array) wp_unslash($_POST['attachment_ids'])) : [];
        $file_paths     = isset($_POST['file_paths']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['file_paths'])) : [];
        $deleted = $this->perform_deletion($attachment_ids, $file_paths);
        $results = get_option($this->option_key, []);
        if (! empty($results)) {
            $results = $this->remove_deleted_from_results($results, $attachment_ids, $file_paths);
            update_option($this->option_key, $results, false);
        }
        wp_send_json_success(['deleted' => $deleted, 'summary' => $results['summary'] ?? []]);
    }

    public function ajax_delete_all_filtered_batch() {
        $this->authorize_ajax();
        $results = get_option($this->option_key, []);
        if (empty($results) || ! is_array($results)) {
            wp_send_json_error(['message' => __('No scan results were found.', 'wp-unused-cleaner')], 400);
        }

        $filters = [
            'attachment_kind' => $this->sanitize_kind_filter(isset($_POST['attachment_kind']) ? wp_unslash($_POST['attachment_kind']) : 'all'),
            'file_kind'       => $this->sanitize_kind_filter(isset($_POST['file_kind']) ? wp_unslash($_POST['file_kind']) : 'all'),
            'attachment_search'=> sanitize_text_field(isset($_POST['attachment_search']) ? wp_unslash($_POST['attachment_search']) : ''),
            'file_search'     => sanitize_text_field(isset($_POST['file_search']) ? wp_unslash($_POST['file_search']) : ''),
            'attachment_sort' => $this->sanitize_attachment_sort(isset($_POST['attachment_sort']) ? wp_unslash($_POST['attachment_sort']) : 'title'),
            'attachment_dir'  => $this->sanitize_sort_dir(isset($_POST['attachment_dir']) ? wp_unslash($_POST['attachment_dir']) : 'asc'),
            'file_sort'       => $this->sanitize_file_sort(isset($_POST['file_sort']) ? wp_unslash($_POST['file_sort']) : 'relative'),
            'file_dir'        => $this->sanitize_sort_dir(isset($_POST['file_dir']) ? wp_unslash($_POST['file_dir']) : 'asc'),
        ];

        $attachment_items = $this->filter_sort_items((array) ($results['attachments'] ?? []), [
            'kind' => $filters['attachment_kind'],
            'search' => $filters['attachment_search'],
            'search_in' => ['title', 'mime_type', 'file_path', 'url'],
            'sort' => $filters['attachment_sort'],
            'dir' => $filters['attachment_dir'],
        ], 'attachments');

        $file_items = $this->filter_sort_items((array) ($results['files'] ?? []), [
            'kind' => $filters['file_kind'],
            'search' => $filters['file_search'],
            'search_in' => ['relative', 'path', 'ext'],
            'sort' => $filters['file_sort'],
            'dir' => $filters['file_dir'],
        ], 'files');

        $batch_size = max(1, (int) (isset($_POST['batch_size']) ? $_POST['batch_size'] : 15));
        $attachment_ids = array_map(function ($item) { return (int) $item['id']; }, array_slice($attachment_items, 0, $batch_size));
        $file_paths = array_map(function ($item) { return (string) $item['path']; }, array_slice($file_items, 0, $batch_size));

        $deleted = $this->perform_deletion($attachment_ids, $file_paths);
        $results = $this->remove_deleted_from_results($results, $attachment_ids, $file_paths);
        update_option($this->option_key, $results, false);

        $remaining_attachments = max(0, count($attachment_items) - count($attachment_ids));
        $remaining_files = max(0, count($file_items) - count($file_paths));

        wp_send_json_success([
            'deleted' => $deleted,
            'summary' => $results['summary'],
            'remaining' => [
                'attachments' => $remaining_attachments,
                'files' => $remaining_files,
                'total' => $remaining_attachments + $remaining_files,
            ],
        ]);
    }

    private function authorize_ajax() {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-unused-cleaner')], 403);
        }
        check_ajax_referer('wpuc_ajax', 'nonce');
    }

    private function perform_deletion($attachment_ids, $file_paths) {
        $deleted = ['attachments' => 0, 'files' => 0];
        foreach ((array) $attachment_ids as $attachment_id) {
            $attachment_id = (int) $attachment_id;
            if ($attachment_id > 0 && current_user_can('delete_post', $attachment_id)) {
                if (wp_delete_attachment($attachment_id, true)) {
                    $deleted['attachments']++;
                }
            }
        }

        if (! empty($file_paths)) {
            $upload_info = wp_get_upload_dir();
            $basedir     = wp_normalize_path((string) ($upload_info['basedir'] ?? ''));
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
            global $wp_filesystem;
            foreach ((array) $file_paths as $path) {
                $path = wp_normalize_path((string) $path);
                if (! $path || ! $basedir || 0 !== strpos($path, $basedir) || ! file_exists($path)) {
                    continue;
                }
                if ($wp_filesystem && $wp_filesystem->delete($path, false, 'f')) {
                    $deleted['files']++;
                }
            }
        }

        return $deleted;
    }

    public function render_page() {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to view this page.', 'wp-unused-cleaner'));
        }

        $results = get_option($this->option_key, []);
        $summary = isset($results['summary']) && is_array($results['summary']) ? $results['summary'] : [];
        $debug = get_option($this->debug_key, []);
        $active_state = get_option($this->state_key, []);
        if (is_array($active_state) && ! empty($active_state)) {
            $debug['active_phase'] = isset($active_state['phase']) ? (string) $active_state['phase'] : '';
            $debug['active_cursor'] = isset($active_state['cursor']) ? (int) $active_state['cursor'] : 0;
            if (! isset($debug['warnings']) || ! is_array($debug['warnings'])) {
                $debug['warnings'] = [];
            }
            $debug['warnings'] = array_slice(array_values(array_unique(array_merge($debug['warnings'], (array) ($active_state['warnings'] ?? [])))), -10);
        }

        $attachment_filter = $this->sanitize_kind_filter(isset($_GET['attachment_kind']) ? wp_unslash($_GET['attachment_kind']) : 'all');
        $file_filter       = $this->sanitize_kind_filter(isset($_GET['file_kind']) ? wp_unslash($_GET['file_kind']) : 'all');
        $attachment_search = sanitize_text_field(isset($_GET['attachment_search']) ? wp_unslash($_GET['attachment_search']) : '');
        $file_search       = sanitize_text_field(isset($_GET['file_search']) ? wp_unslash($_GET['file_search']) : '');
        $attachment_page   = max(1, (int) (isset($_GET['attachments_paged']) ? $_GET['attachments_paged'] : 1));
        $file_page         = max(1, (int) (isset($_GET['files_paged']) ? $_GET['files_paged'] : 1));
        $attachment_sort   = $this->sanitize_attachment_sort(isset($_GET['attachment_sort']) ? wp_unslash($_GET['attachment_sort']) : 'title');
        $attachment_dir    = $this->sanitize_sort_dir(isset($_GET['attachment_dir']) ? wp_unslash($_GET['attachment_dir']) : 'asc');
        $file_sort         = $this->sanitize_file_sort(isset($_GET['file_sort']) ? wp_unslash($_GET['file_sort']) : 'relative');
        $file_dir          = $this->sanitize_sort_dir(isset($_GET['file_dir']) ? wp_unslash($_GET['file_dir']) : 'asc');
        $per_page          = 20;

        $attachment_view = $this->filter_and_paginate_items((array) ($results['attachments'] ?? []), [
            'kind' => $attachment_filter,
            'search' => $attachment_search,
            'page' => $attachment_page,
            'per_page' => $per_page,
            'search_in' => ['title', 'mime_type', 'file_path', 'url'],
            'sort' => $attachment_sort,
            'dir' => $attachment_dir,
        ], 'attachments');

        $file_view = $this->filter_and_paginate_items((array) ($results['files'] ?? []), [
            'kind' => $file_filter,
            'search' => $file_search,
            'page' => $file_page,
            'per_page' => $per_page,
            'search_in' => ['relative', 'path', 'ext'],
            'sort' => $file_sort,
            'dir' => $file_dir,
        ], 'files');
        ?>
        <div class="wrap wpuc-app">
            <div class="wpuc-hero">
                <div>
                    <h1><?php esc_html_e('Flint Media Cleaner', 'wp-unused-cleaner'); ?></h1>
                    <p><?php esc_html_e('Find unattached media-library items and stray upload files, review them safely, and clean them up in timed batches.', 'wp-unused-cleaner'); ?></p>
                </div>
                <div class="wpuc-badges">
                    <h2><?php esc_html_e('Currently Supports:', 'wp-unused-cleaner'); ?></h2>
                    <span><?php esc_html_e('ACF', 'wp-unused-cleaner'); ?></span>
                    <span><?php esc_html_e('The Events Calendar', 'wp-unused-cleaner'); ?></span>
                </div>
            </div>

            <div class="wpuc-grid wpuc-summary-grid">
                <div class="wpuc-card wpuc-card-accent">
                    <h2><?php esc_html_e('Run a fresh scan', 'wp-unused-cleaner'); ?></h2>
                    <p><?php esc_html_e('Scanning runs in batches to reduce timeout risk on larger sites. Scans can take a few minutes to complete. For a full log of operations and errors, check the debug log below.', 'wp-unused-cleaner'); ?></p>
                    <button type="button" class="button button-primary button-hero" id="wpuc-start-scan"><?php esc_html_e('Start batch scan', 'wp-unused-cleaner'); ?></button>
                    <?php if (! empty($results['scanned_at'])) : ?>
                        <p class="description wpuc-muted"><?php echo esc_html(sprintf(__('Last completed scan: %s', 'wp-unused-cleaner'), (string) $results['scanned_at'])); ?></p>
                    <?php endif; ?>
                    <div class="wpuc-progress" id="wpuc-progress" hidden>
                        <div class="wpuc-progress-bar"><span id="wpuc-progress-fill"></span></div>
                        <div class="wpuc-progress-meta">
                            <strong id="wpuc-progress-label"><?php esc_html_e('Preparing…', 'wp-unused-cleaner'); ?></strong>
                            <span id="wpuc-progress-count">0%</span>
                        </div>
                    </div>
                </div>
                <div class="wpuc-card"><span class="wpuc-stat-label"><?php esc_html_e('Media library items scanned', 'wp-unused-cleaner'); ?></span><strong class="wpuc-stat-value" data-summary="attachments_total"><?php echo esc_html((string) ($summary['attachments_total'] ?? 0)); ?></strong></div>
                <div class="wpuc-card"><span class="wpuc-stat-label"><?php esc_html_e('Potentially unused attachments', 'wp-unused-cleaner'); ?></span><strong class="wpuc-stat-value wpuc-danger" data-summary="attachments_unused"><?php echo esc_html((string) ($summary['attachments_unused'] ?? 0)); ?></strong></div>
                <div class="wpuc-card"><span class="wpuc-stat-label"><?php esc_html_e('Loose files scanned', 'wp-unused-cleaner'); ?></span><strong class="wpuc-stat-value" data-summary="files_total"><?php echo esc_html((string) ($summary['files_total'] ?? 0)); ?></strong></div>
                <div class="wpuc-card"><span class="wpuc-stat-label"><?php esc_html_e('Potentially unused loose files', 'wp-unused-cleaner'); ?></span><strong class="wpuc-stat-value wpuc-danger" data-summary="files_unused"><?php echo esc_html((string) ($summary['files_unused'] ?? 0)); ?></strong></div>
            </div>

            <div class="wpuc-inline-notice" id="wpuc-inline-notice" hidden></div>

            <?php $this->render_debug_panel($debug, $results); ?>
			
			<div class="wpuc-toolbar">
                <div>
                    <h2><?php esc_html_e('Review cleanup candidates', 'wp-unused-cleaner'); ?></h2>
                    <p><?php esc_html_e('Filter and sort the results, then delete selected items or all filtered results in safe batches.', 'wp-unused-cleaner'); ?></p>
                </div>
                <div class="wpuc-actions">
                    <button type="button" class="button button-secondary" id="wpuc-delete-selected"><?php esc_html_e('Delete selected items', 'wp-unused-cleaner'); ?></button>
                    <button type="button" class="button button-secondary" id="wpuc-delete-all-filtered"><?php esc_html_e('Delete all filtered results', 'wp-unused-cleaner'); ?></button>
                </div>
            </div>

            <div class="wpuc-results-form" id="wpuc-results-form">
                <div class="wpuc-section">
                    <div class="wpuc-section-header"><h3><?php esc_html_e('Unused media library items', 'wp-unused-cleaner'); ?></h3></div>
                    <?php $this->render_filter_bar('attachment', $attachment_filter, $attachment_search); ?>
                    <?php $this->render_attachments_table($attachment_view, $attachment_sort, $attachment_dir); ?>
                    <?php $this->render_pagination($attachment_view, 'attachments_paged', ['file_kind', 'file_search', 'files_paged', 'attachment_kind', 'attachment_search', 'attachment_sort', 'attachment_dir', 'file_sort', 'file_dir']); ?>
                </div>

                <div class="wpuc-section">
                    <div class="wpuc-section-header"><h3><?php esc_html_e('Unused files in uploads', 'wp-unused-cleaner'); ?></h3></div>
                    <?php $this->render_filter_bar('file', $file_filter, $file_search); ?>
                    <?php $this->render_files_table($file_view, $file_sort, $file_dir); ?>
                    <?php $this->render_pagination($file_view, 'files_paged', ['attachment_kind', 'attachment_search', 'attachments_paged', 'file_kind', 'file_search', 'attachment_sort', 'attachment_dir', 'file_sort', 'file_dir']); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_debug_panel($debug, $results) {
        $warnings = array_slice((array) ($debug['warnings'] ?? ($results['warnings'] ?? [])), -10);
        $status = isset($debug['status']) ? (string) $debug['status'] : __('idle', 'wp-unused-cleaner');
        ?>
        <div class="wpuc-section wpuc-debug-panel" id="debugPanel">
            <div class="wpuc-section-header">
                <h3 id="debugToggle"><?php esc_html_e('Scan debug', 'wp-unused-cleaner'); ?></h3>
                <span class="wpuc-pill <?php echo esc_attr('wpuc-pill-' . sanitize_html_class($status)); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
            </div>
            <div class="wpuc-debug-grid">
                <div><strong><?php esc_html_e('Message', 'wp-unused-cleaner'); ?></strong><div id="wpuc-debug-message"><?php echo esc_html((string) ($debug['message'] ?? __('No scan debug data yet.', 'wp-unused-cleaner'))); ?></div></div>
                <div><strong><?php esc_html_e('Phase', 'wp-unused-cleaner'); ?></strong><div id="wpuc-debug-phase"><?php echo esc_html((string) ($debug['active_phase'] ?? $debug['phase'] ?? '')); ?></div></div>
                <div><strong><?php esc_html_e('Cursor', 'wp-unused-cleaner'); ?></strong><div id="wpuc-debug-cursor"><?php echo esc_html((string) ($debug['active_cursor'] ?? $debug['cursor'] ?? 0)); ?></div></div>
                <div><strong><?php esc_html_e('Updated', 'wp-unused-cleaner'); ?></strong><div id="wpuc-debug-updated"><?php echo esc_html((string) ($debug['updated_at'] ?? '')); ?></div></div>
                <div class="wpuc-debug-error"><strong><?php esc_html_e('Last error', 'wp-unused-cleaner'); ?></strong><div id="wpuc-debug-error"><?php echo esc_html((string) ($debug['error'] ?? '')); ?></div></div>
            </div>
            <div class="wpuc-debug-warnings">
                <strong><?php esc_html_e('Recent warnings', 'wp-unused-cleaner'); ?></strong>
                <ul id="wpuc-debug-warnings">
                    <?php if (! empty($warnings)) : ?>
                        <?php foreach ($warnings as $warning) : ?>
                            <li><?php echo esc_html((string) $warning); ?></li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php esc_html_e('No warnings recorded yet.', 'wp-unused-cleaner'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    private function render_filter_bar($prefix, $kind, $search) {
        $kind_name   = 'attachment' === $prefix ? 'attachment_kind' : 'file_kind';
        $search_name = 'attachment' === $prefix ? 'attachment_search' : 'file_search';
        ?>
        <form method="get" class="wpuc-filter-bar">
            <input type="hidden" name="page" value="wp-unused-cleaner" />
            <input type="hidden" name="attachment_sort" value="<?php echo esc_attr($this->sanitize_attachment_sort(isset($_GET['attachment_sort']) ? wp_unslash($_GET['attachment_sort']) : 'title')); ?>" />
            <input type="hidden" name="attachment_dir" value="<?php echo esc_attr($this->sanitize_sort_dir(isset($_GET['attachment_dir']) ? wp_unslash($_GET['attachment_dir']) : 'asc')); ?>" />
            <input type="hidden" name="file_sort" value="<?php echo esc_attr($this->sanitize_file_sort(isset($_GET['file_sort']) ? wp_unslash($_GET['file_sort']) : 'relative')); ?>" />
            <input type="hidden" name="file_dir" value="<?php echo esc_attr($this->sanitize_sort_dir(isset($_GET['file_dir']) ? wp_unslash($_GET['file_dir']) : 'asc')); ?>" />
            <?php if ('attachment' === $prefix) : ?>
                <input type="hidden" name="file_kind" value="<?php echo esc_attr($this->sanitize_kind_filter(isset($_GET['file_kind']) ? wp_unslash($_GET['file_kind']) : 'all')); ?>" />
                <input type="hidden" name="file_search" value="<?php echo esc_attr(sanitize_text_field(isset($_GET['file_search']) ? wp_unslash($_GET['file_search']) : '')); ?>" />
            <?php else : ?>
                <input type="hidden" name="attachment_kind" value="<?php echo esc_attr($this->sanitize_kind_filter(isset($_GET['attachment_kind']) ? wp_unslash($_GET['attachment_kind']) : 'all')); ?>" />
                <input type="hidden" name="attachment_search" value="<?php echo esc_attr(sanitize_text_field(isset($_GET['attachment_search']) ? wp_unslash($_GET['attachment_search']) : '')); ?>" />
            <?php endif; ?>
            <label><span><?php esc_html_e('Filter', 'wp-unused-cleaner'); ?></span><select name="<?php echo esc_attr($kind_name); ?>"><?php foreach ($this->get_kind_options() as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($kind, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
            <label class="wpuc-search-field"><span><?php esc_html_e('Search', 'wp-unused-cleaner'); ?></span><input type="search" name="<?php echo esc_attr($search_name); ?>" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search title, path, type…', 'wp-unused-cleaner'); ?>" /></label>
            <button type="submit" class="button"><?php esc_html_e('Apply', 'wp-unused-cleaner'); ?></button>
        </form>
        <?php
    }

    private function render_attachments_table($view, $sort, $dir) {
        $items = (array) ($view['items'] ?? []);
        if (empty($items)) {
            echo '<div class="wpuc-empty">' . esc_html__('No unused media-library items match the current filter.', 'wp-unused-cleaner') . '</div>';
            return;
        }
        ?>
        <div class="wpuc-table-wrap"><table class="widefat fixed striped wpuc-table"><thead><tr><td class="check-column"><input type="checkbox" class="wpuc-master-toggle" data-target="attachment_ids[]" /></td><th><?php esc_html_e('Preview', 'wp-unused-cleaner'); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Title', 'wp-unused-cleaner'), 'attachment_sort', 'title', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Type', 'wp-unused-cleaner'), 'attachment_sort', 'mime_type', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Size', 'wp-unused-cleaner'), 'attachment_sort', 'size_bytes', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Date', 'wp-unused-cleaner'), 'attachment_sort', 'date_ts', $sort, $dir)); ?></th></tr></thead><tbody>
        <?php foreach ($items as $item) : ?>
            <tr>
                <th scope="row" class="check-column"><input type="checkbox" name="attachment_ids[]" value="<?php echo esc_attr((string) $item['id']); ?>" /></th>
                <td><div class="wpuc-thumb"><?php echo wp_kses_post(wp_get_attachment_image((int) $item['id'], [64, 64], true)); ?></div></td>
                <td><strong><?php echo esc_html((string) $item['title']); ?></strong><div><span class="wpuc-pill"><?php echo esc_html(ucfirst((string) ($item['kind'] ?? 'other'))); ?></span></div><div><a href="<?php echo esc_url((string) $item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open file', 'wp-unused-cleaner'); ?></a></div><code><?php echo esc_html((string) $item['file_path']); ?></code></td>
                <td><?php echo esc_html((string) $item['mime_type']); ?></td><td><?php echo esc_html((string) $item['size']); ?></td><td><?php echo esc_html((string) $item['date']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php
    }

    private function render_files_table($view, $sort, $dir) {
        $items = (array) ($view['items'] ?? []);
        if (empty($items)) {
            echo '<div class="wpuc-empty">' . esc_html__('No loose files match the current filter.', 'wp-unused-cleaner') . '</div>';
            return;
        }
        ?>
        <div class="wpuc-table-wrap"><table class="widefat fixed striped wpuc-table"><thead><tr><td class="check-column"><input type="checkbox" class="wpuc-master-toggle" data-target="file_paths[]" /></td><th><?php echo wp_kses_post($this->sortable_header_link(__('Relative path', 'wp-unused-cleaner'), 'file_sort', 'relative', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Size', 'wp-unused-cleaner'), 'file_sort', 'size_bytes', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Modified', 'wp-unused-cleaner'), 'file_sort', 'modified_ts', $sort, $dir)); ?></th><th><?php echo wp_kses_post($this->sortable_header_link(__('Link', 'wp-unused-cleaner'), 'file_sort', 'kind', $sort, $dir)); ?></th></tr></thead><tbody>
        <?php foreach ($items as $item) : ?>
            <tr>
                <th scope="row" class="check-column"><input type="checkbox" name="file_paths[]" value="<?php echo esc_attr((string) $item['path']); ?>" /></th>
                <td><strong><?php echo esc_html((string) $item['relative']); ?></strong><div><span class="wpuc-pill"><?php echo esc_html(! empty($item['is_thumbnail']) ? __('Image thumbnail', 'wp-unused-cleaner') : ucfirst((string) ($item['kind'] ?? 'other'))); ?></span></div><div><code><?php echo esc_html((string) $item['path']); ?></code></div></td>
                <td><?php echo esc_html((string) $item['size']); ?></td><td><?php echo esc_html((string) $item['modified']); ?></td><td><a href="<?php echo esc_url((string) $item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open file', 'wp-unused-cleaner'); ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php
    }

    private function sortable_header_link($label, $sort_key_name, $field, $current_sort, $current_dir) {
        $args = $_GET;
        $args['page'] = 'wp-unused-cleaner';
        $dir_key = ('attachment_sort' === $sort_key_name) ? 'attachment_dir' : 'file_dir';
        $args[$sort_key_name] = $field;
        $args[$dir_key] = ($current_sort === $field && 'asc' === $current_dir) ? 'desc' : 'asc';
        $url = add_query_arg(array_map('sanitize_text_field', wp_unslash($args)), admin_url('upload.php'));
        $indicator = '';
        if ($current_sort === $field) {
            $indicator = 'asc' === $current_dir ? ' ↑' : ' ↓';
        }
        return '<a class="wpuc-sort-link" href="' . esc_url($url) . '">' . esc_html($label . $indicator) . '</a>';
    }

    private function render_pagination($view, $page_key, $preserve_keys) {
        $total_pages = (int) ($view['total_pages'] ?? 1);
        $current     = (int) ($view['page'] ?? 1);
        if ($total_pages <= 1) {
            return;
        }

        $base_args = ['page' => 'wp-unused-cleaner'];
        foreach ($preserve_keys as $key) {
            if (isset($_GET[$key])) {
                $base_args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }
        $display_pages = $this->get_compact_pagination_pages($current, $total_pages);
        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages wpuc-tablenav-pages">
                <span class="displaying-num"><?php echo esc_html(sprintf(_n('%d item', '%d items', (int) ($view['total_items'] ?? 0), 'wp-unused-cleaner'), (int) ($view['total_items'] ?? 0))); ?></span>
                <span class="pagination-links">
                    <?php foreach ($display_pages as $page) : ?>
                        <?php if ('ellipsis' === $page) : ?>
                            <span class="wpuc-ellipsis">…</span>
                        <?php else : ?>
                            <?php $url = add_query_arg(array_merge($base_args, [$page_key => $page]), admin_url('upload.php')); ?>
                            <?php if ((int) $page === $current) : ?>
                                <span class="button button-small current-page"><?php echo esc_html((string) $page); ?></span>
                            <?php else : ?>
                                <a class="button button-small" href="<?php echo esc_url($url); ?>"><?php echo esc_html((string) $page); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </span>
                <?php if ($total_pages > 6) : ?>
                    <form method="get" class="wpuc-page-jump">
                        <input type="hidden" name="page" value="wp-unused-cleaner" />
                        <?php foreach ($base_args as $arg_key => $arg_value) : ?>
                            <?php if ('page' !== $arg_key) : ?>
                                <input type="hidden" name="<?php echo esc_attr($arg_key); ?>" value="<?php echo esc_attr((string) $arg_value); ?>" />
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <label>
                            <span><?php esc_html_e('Page', 'wp-unused-cleaner'); ?></span>
                            <select name="<?php echo esc_attr($page_key); ?>" onchange="this.form.submit()">
                                <?php for ($page = 1; $page <= $total_pages; $page++) : ?>
                                    <option value="<?php echo esc_attr((string) $page); ?>" <?php selected($current, $page); ?>><?php echo esc_html(sprintf(__('Page %d', 'wp-unused-cleaner'), $page)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function get_compact_pagination_pages($current, $total_pages) {
        if ($total_pages <= 6) {
            return range(1, $total_pages);
        }
        $pages = [1];
        if ($current > 3) {
            $pages[] = 'ellipsis';
        }
        $start = max(2, $current - 1);
        $end   = min($total_pages - 1, $current + 1);
        if ($current <= 3) {
            $start = 2;
            $end   = 4;
        }
        if ($current >= $total_pages - 2) {
            $start = $total_pages - 3;
            $end   = $total_pages - 1;
        }
        for ($page = $start; $page <= $end; $page++) {
            $pages[] = $page;
        }
        if ($current < $total_pages - 2) {
            $pages[] = 'ellipsis';
        }
        $pages[] = $total_pages;
        return $pages;
    }

    private function filter_sort_items($items, $args, $type) {
        $kind = isset($args['kind']) ? (string) $args['kind'] : 'all';
        $search = isset($args['search']) ? (string) $args['search'] : '';
        $search_in = isset($args['search_in']) ? (array) $args['search_in'] : [];
        $sort = isset($args['sort']) ? (string) $args['sort'] : ('attachments' === $type ? 'title' : 'relative');
        $dir  = isset($args['dir']) ? (string) $args['dir'] : 'asc';

        $items = array_values(array_filter((array) $items, function ($item) use ($kind, $search, $search_in) {
            if ('all' !== $kind && ($item['kind'] ?? 'other') !== $kind) {
                return false;
            }
            if ($search !== '') {
                $haystack = [];
                foreach ($search_in as $field) {
                    if (isset($item[$field])) {
                        $haystack[] = (string) $item[$field];
                    }
                }
                if (false === stripos(implode(' ', $haystack), $search)) {
                    return false;
                }
            }
            return true;
        }));

        usort($items, function ($a, $b) use ($sort, $dir) {
            $va = $a[$sort] ?? '';
            $vb = $b[$sort] ?? '';
            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = (int) $va <=> (int) $vb;
            } else {
                $cmp = strcasecmp((string) $va, (string) $vb);
            }
            return 'desc' === $dir ? -$cmp : $cmp;
        });

        return $items;
    }

    private function filter_and_paginate_items($items, $args, $type) {
        $page = max(1, (int) ($args['page'] ?? 1));
        $per_page = max(1, (int) ($args['per_page'] ?? 20));
        $items = $this->filter_sort_items($items, $args, $type);
        $total_items = count($items);
        $total_pages = max(1, (int) ceil($total_items / $per_page));
        $page = min($page, $total_pages);
        return [
            'items' => array_slice($items, ($page - 1) * $per_page, $per_page),
            'page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
        ];
    }

    private function remove_deleted_from_results($results, $attachment_ids, $file_paths) {
        $results['attachments'] = array_values(array_filter((array) ($results['attachments'] ?? []), function ($item) use ($attachment_ids) {
            return ! in_array((int) ($item['id'] ?? 0), array_map('intval', (array) $attachment_ids), true);
        }));
        $results['files'] = array_values(array_filter((array) ($results['files'] ?? []), function ($item) use ($file_paths) {
            return ! in_array((string) ($item['path'] ?? ''), array_map('strval', (array) $file_paths), true);
        }));
        $results['summary']['attachments_unused'] = count((array) ($results['attachments'] ?? []));
        $results['summary']['files_unused'] = count((array) ($results['files'] ?? []));
        return $results;
    }

    private function get_kind_options() {
        return [
            'all' => __('All types', 'wp-unused-cleaner'),
            'image' => __('Images', 'wp-unused-cleaner'),
            'video' => __('Video', 'wp-unused-cleaner'),
            'audio' => __('Audio', 'wp-unused-cleaner'),
            'document' => __('Documents', 'wp-unused-cleaner'),
            'other' => __('Other', 'wp-unused-cleaner'),
        ];
    }

    private function sanitize_kind_filter($value) {
        $value = sanitize_key((string) $value);
        $allowed = array_keys($this->get_kind_options());
        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function sanitize_sort_dir($value) {
        return 'desc' === strtolower((string) $value) ? 'desc' : 'asc';
    }

    private function sanitize_attachment_sort($value) {
        $allowed = ['title', 'mime_type', 'size_bytes', 'date_ts'];
        $value = sanitize_key((string) $value);
        return in_array($value, $allowed, true) ? $value : 'title';
    }

    private function sanitize_file_sort($value) {
        $allowed = ['relative', 'size_bytes', 'modified_ts', 'kind'];
        $value = sanitize_key((string) $value);
        return in_array($value, $allowed, true) ? $value : 'relative';
    }
}
