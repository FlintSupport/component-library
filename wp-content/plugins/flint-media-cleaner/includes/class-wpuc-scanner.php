<?php

if (! defined('ABSPATH')) {
    exit;
}

class WP_UC_Scanner {
    private static $instance = null;
    private $reference_urls = [];
    private $referenced_attachment_ids = [];
    private $referenced_relative_paths = [];

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_scan_state() {
        global $wpdb;

        $upload_info = wp_get_upload_dir();
        $baseurl     = isset($upload_info['baseurl']) ? (string) $upload_info['baseurl'] : '';
        $basedir     = isset($upload_info['basedir']) ? (string) $upload_info['basedir'] : '';

        $post_types = get_post_types(['public' => true], 'names');
        if (post_type_exists('tribe_events')) {
            $post_types[] = 'tribe_events';
        }
        $post_types = array_values(array_unique(array_filter($post_types)));

        $post_ids = get_posts([
            'post_type'              => $post_types,
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ]);

        $attachment_ids = get_posts([
            'post_type'              => 'attachment',
            'post_status'            => 'inherit',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ]);

        $file_paths = [];
        if ($basedir && is_dir($basedir)) {
            try {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir, FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file_info) {
                    if (! $file_info->isFile()) {
                        continue;
                    }
                    $path = wp_normalize_path($file_info->getPathname());
                    if ($this->should_skip_file($path, $basedir)) {
                        continue;
                    }
                    $file_paths[] = $path;
                }
            } catch (Throwable $e) {
                $file_paths = [];
            }
        }

        $termmeta_table = isset($wpdb->termmeta) ? (string) $wpdb->termmeta : '';
        $usermeta_table = isset($wpdb->usermeta) ? (string) $wpdb->usermeta : '';

        return [
            'created_at' => time(),
            'baseurl'    => $baseurl,
            'basedir'    => $basedir,
            'phase'      => 'posts',
            'cursor'     => 0,
            'batches'    => [
                'posts'       => 40,
                'options'     => 50,
                'term_meta'   => 250,
                'user_meta'   => 250,
                'attachments' => 50,
                'files'       => 100,
            ],
            'sources'    => [
                'post_ids'         => array_map('intval', (array) $post_ids),
                'attachment_ids'   => array_map('intval', (array) $attachment_ids),
                'file_paths'       => array_values($file_paths),
                'options_total'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}"), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                'term_meta_total'  => $this->table_exists($termmeta_table) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$termmeta_table}") : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                'user_meta_total'  => $this->table_exists($usermeta_table) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$usermeta_table}") : 0, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                'has_term_meta'    => $this->table_exists($termmeta_table),
                'has_user_meta'    => $this->table_exists($usermeta_table),
            ],
            'references' => [
                'urls'           => [],
                'attachment_ids' => [],
                'relative_paths' => [],
            ],
            'results'    => [
                'attachments' => [],
                'files'       => [],
            ],
            'warnings'   => [],
            'summary'    => [
                'attachments_total'  => count((array) $attachment_ids),
                'attachments_unused' => 0,
                'files_total'        => count($file_paths),
                'files_unused'       => 0,
            ],
        ];
    }

    public function process_scan_batch($state) {
        global $wpdb;

        $this->hydrate_state_references($state);
        $phase = isset($state['phase']) ? (string) $state['phase'] : 'done';
        $done  = false;

        try {
            switch ($phase) {
                case 'posts':
                    $items = (array) ($state['sources']['post_ids'] ?? []);
                    $limit = (int) ($state['batches']['posts'] ?? 40);
                    $slice = array_slice($items, (int) $state['cursor'], $limit);
                    foreach ($slice as $post_id) {
                        $this->scan_post((int) $post_id, (string) $state['baseurl'], (string) $state['basedir']);
                    }
                    $state['cursor'] += count($slice);
                    if ($state['cursor'] >= count($items)) {
                        $state['phase'] = 'options';
                        $state['cursor'] = 0;
                    }
                    break;

                case 'options':
                    $limit = (int) ($state['batches']['options'] ?? 50);
                    $rows = $this->safe_get_option_rows($limit, (int) $state['cursor']);
                    foreach ((array) $rows as $row) {
                        $this->process_option_row($row, (string) $state['baseurl'], (string) $state['basedir'], $state);
                    }
                    $state['cursor'] += count((array) $rows);
                    if (count((array) $rows) < $limit) {
                        $state['phase'] = 'term_meta';
                        $state['cursor'] = 0;
                    }
                    break;

                case 'term_meta':
                    if (empty($state['sources']['has_term_meta'])) {
                        $state['phase'] = 'user_meta';
                        $state['cursor'] = 0;
                        break;
                    }
                    $limit = (int) ($state['batches']['term_meta'] ?? 250);
                    $rows = $this->safe_get_meta_rows((string) $wpdb->termmeta, 'meta_id', 'meta_value', $limit, (int) $state['cursor']);
                    foreach ((array) $rows as $row) {
                        if (isset($row['meta_value'])) {
                            $this->safe_extract_references($row['meta_value'], (string) $state['baseurl'], (string) $state['basedir']);
                        }
                    }
                    $state['cursor'] += count((array) $rows);
                    if (count((array) $rows) < $limit) {
                        $state['phase'] = 'user_meta';
                        $state['cursor'] = 0;
                    }
                    break;

                case 'user_meta':
                    if (empty($state['sources']['has_user_meta'])) {
                        $state['phase'] = 'attachments';
                        $state['cursor'] = 0;
                        break;
                    }
                    $limit = (int) ($state['batches']['user_meta'] ?? 250);
                    $rows = $this->safe_get_meta_rows((string) $wpdb->usermeta, 'umeta_id', 'meta_value', $limit, (int) $state['cursor']);
                    foreach ((array) $rows as $row) {
                        if (isset($row['meta_value'])) {
                            $this->safe_extract_references($row['meta_value'], (string) $state['baseurl'], (string) $state['basedir']);
                        }
                    }
                    $state['cursor'] += count((array) $rows);
                    if (count((array) $rows) < $limit) {
                        $state['phase'] = 'attachments';
                        $state['cursor'] = 0;
                    }
                    break;

                case 'attachments':
                    $items = (array) ($state['sources']['attachment_ids'] ?? []);
                    $limit = (int) ($state['batches']['attachments'] ?? 50);
                    $slice = array_slice($items, (int) $state['cursor'], $limit);
                    foreach ($slice as $attachment_id) {
                        try {
                            $item = $this->build_unused_attachment_item((int) $attachment_id, (string) $state['baseurl'], (string) $state['basedir']);
                            if ($item) {
                                $state['results']['attachments'][] = $item;
                            }
                        } catch (Throwable $inner_e) {
                            $this->append_warning($state, sprintf(__('Skipped attachment %1$d: %2$s', 'wp-unused-cleaner'), (int) $attachment_id, $inner_e->getMessage()));
                        }
                    }
                    $state['cursor'] += count($slice);
                    if ($state['cursor'] >= count($items)) {
                        $state['phase'] = 'files';
                        $state['cursor'] = 0;
                    }
                    break;

                case 'files':
                    $items = (array) ($state['sources']['file_paths'] ?? []);
                    $limit = (int) ($state['batches']['files'] ?? 100);
                    $slice = array_slice($items, (int) $state['cursor'], $limit);
                    foreach ($slice as $path) {
                        try {
                            $item = $this->build_unused_file_item((string) $path, (string) $state['baseurl'], (string) $state['basedir']);
                            if ($item) {
                                $state['results']['files'][] = $item;
                            }
                        } catch (Throwable $inner_e) {
                            $this->append_warning($state, sprintf(__('Skipped file %1$s: %2$s', 'wp-unused-cleaner'), (string) $path, $inner_e->getMessage()));
                        }
                    }
                    $state['cursor'] += count($slice);
                    if ($state['cursor'] >= count($items)) {
                        $state['phase'] = 'done';
                        $state['cursor'] = 0;
                        $done = true;
                    }
                    break;

                default:
                    $done = true;
                    break;
            }
        } catch (Throwable $e) {
            $this->append_warning($state, sprintf(__('Skipped %1$s phase: %2$s', 'wp-unused-cleaner'), $phase, $e->getMessage()));
            $state['phase'] = $this->get_next_phase($phase);
            $state['cursor'] = 0;
            if ('done' === $state['phase']) {
                $done = true;
            }
        }

        $this->persist_state_references($state);
        $state['summary']['attachments_unused'] = count((array) ($state['results']['attachments'] ?? []));
        $state['summary']['files_unused'] = count((array) ($state['results']['files'] ?? []));

        return [
            'state' => $state,
            'done' => $done,
            'progress' => $this->get_progress_data($state),
            'results' => $done ? $this->finalize_results($state) : [],
        ];
    }


    private function append_warning(&$state, $message) {
        if (! isset($state['warnings']) || ! is_array($state['warnings'])) {
            $state['warnings'] = [];
        }
        if (count($state['warnings']) < 25) {
            $state['warnings'][] = (string) $message;
        }
    }

    private function get_next_phase($phase) {
        $order = ['posts', 'options', 'term_meta', 'user_meta', 'attachments', 'files', 'done'];
        $index = array_search((string) $phase, $order, true);
        if (false === $index || ! isset($order[$index + 1])) {
            return 'done';
        }
        return $order[$index + 1];
    }

    private function table_exists($table_name) {
        global $wpdb;
        $table_name = (string) $table_name;
        if ($table_name === '') {
            return false;
        }
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name)));
        return (string) $found === $table_name;
    }

    private function safe_get_meta_rows($table, $id_column, $value_column, $limit, $offset) {
        global $wpdb;
        if (! $this->table_exists($table) || ! $this->table_has_column($table, $id_column) || ! $this->table_has_column($table, $value_column)) {
            return [];
        }
        $sql = "SELECT {$value_column} FROM {$table} ORDER BY {$id_column} ASC LIMIT %d OFFSET %d";
        $prepared = $wpdb->prepare($sql, (int) $limit, (int) $offset);
        $rows = $wpdb->get_results($prepared, ARRAY_A);
        if (! empty($wpdb->last_error) || ! is_array($rows)) {
            return [];
        }
        return $rows;
    }

    private function safe_get_option_rows($limit, $offset) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT option_id, option_name, option_value FROM {$wpdb->options} ORDER BY option_id ASC LIMIT %d OFFSET %d", (int) $limit, (int) $offset);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (! empty($wpdb->last_error) || ! is_array($rows)) {
            return [];
        }
        return $rows;
    }

    private function process_option_row($row, $baseurl, $basedir, &$state) {
        $option_name = isset($row['option_name']) ? (string) $row['option_name'] : '';
        $option_id   = isset($row['option_id']) ? (int) $row['option_id'] : 0;
        $raw_value   = isset($row['option_value']) ? $row['option_value'] : '';

        if ($this->should_skip_option_name($option_name)) {
            return;
        }

        if (is_string($raw_value)) {
            if ($this->is_probably_binary_string($raw_value)) {
                $this->append_warning($state, sprintf(__('Skipped option %1$s (#%2$d): binary data.', 'wp-unused-cleaner'), $option_name !== '' ? $option_name : __('(unnamed)', 'wp-unused-cleaner'), $option_id));
                return;
            }
            if (strlen($raw_value) > 262144) {
                $this->append_warning($state, sprintf(__('Skipped option %1$s (#%2$d): value too large to scan safely.', 'wp-unused-cleaner'), $option_name !== '' ? $option_name : __('(unnamed)', 'wp-unused-cleaner'), $option_id));
                return;
            }
        }

        try {
            $value = is_string($raw_value) ? maybe_unserialize($raw_value) : $raw_value;
            if (is_object($value)) {
                $this->append_warning($state, sprintf(__('Skipped option %1$s (#%2$d): unserialized object payload.', 'wp-unused-cleaner'), $option_name !== '' ? $option_name : __('(unnamed)', 'wp-unused-cleaner'), $option_id));
                return;
            }
            $this->extract_references_from_mixed($value, $baseurl, $basedir);
        } catch (Throwable $e) {
            $this->append_warning($state, sprintf(__('Skipped option %1$s (#%2$d): %3$s', 'wp-unused-cleaner'), $option_name !== '' ? $option_name : __('(unnamed)', 'wp-unused-cleaner'), $option_id, $e->getMessage()));
        }
    }

    private function should_skip_option_name($option_name) {
        $option_name = (string) $option_name;
        if ($option_name === '') {
            return false;
        }

        $prefixes = [
            '_transient_',
            '_site_transient_',
            'rss_',
            'rewrite_rules',
            'can_compress_scripts',
            'auto_core_update_failed',
            'widget_',
            'sidebars_widgets',
        ];

        foreach ($prefixes as $prefix) {
            if ($option_name === $prefix || strpos($option_name, $prefix) === 0) {
                return in_array($prefix, ['_transient_', '_site_transient_', 'rss_', 'rewrite_rules', 'can_compress_scripts', 'auto_core_update_failed'], true);
            }
        }

        if (strpos($option_name, 'aioseo_') === 0 || strpos($option_name, 'elementor_') === 0 || strpos($option_name, 'wpforms_') === 0) {
            return false;
        }

        return false;
    }

    private function is_probably_binary_string($value) {
        if (! is_string($value) || $value === '') {
            return false;
        }

        if (preg_match('/[ --]/', $value)) {
            return true;
        }

        return false;
    }

    private function table_has_column($table, $column) {
        global $wpdb;
        $table = (string) $table;
        $column = (string) $column;
        if ($table === '' || $column === '' || ! $this->table_exists($table)) {
            return false;
        }
        $sql = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $wpdb->esc_like($column));
        $row = $wpdb->get_row($sql, ARRAY_A);
        return is_array($row) && ! empty($row['Field']);
    }

    private function safe_extract_references($raw_value, $baseurl, $basedir) {
        try {
            $value = is_string($raw_value) ? maybe_unserialize($raw_value) : $raw_value;
            $this->extract_references_from_mixed($value, $baseurl, $basedir);
        } catch (Throwable $e) {
            // Skip malformed rows without failing the whole scan.
        }
    }

    public function finalize_results($state) {
        $attachments = (array) ($state['results']['attachments'] ?? []);
        $files = (array) ($state['results']['files'] ?? []);
        usort($attachments, function ($a, $b) { return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')); });
        usort($files, function ($a, $b) { return strcasecmp((string) ($a['relative'] ?? ''), (string) ($b['relative'] ?? '')); });
        return [
            'scanned_at' => current_time('mysql'),
            'summary' => [
                'attachments_total' => (int) ($state['summary']['attachments_total'] ?? 0),
                'attachments_unused' => count($attachments),
                'files_total' => (int) ($state['summary']['files_total'] ?? 0),
                'files_unused' => count($files),
            ],
            'attachments' => array_values($attachments),
            'files' => array_values($files),
            'warnings' => array_values(array_unique((array) ($state['warnings'] ?? []))),
        ];
    }

    public function get_progress_data($state) {
        $phase = (string) ($state['phase'] ?? 'done');
        $map = [
            'posts' => __('Scanning posts and events', 'wp-unused-cleaner'),
            'options' => __('Scanning options and settings', 'wp-unused-cleaner'),
            'term_meta' => __('Scanning term metadata', 'wp-unused-cleaner'),
            'user_meta' => __('Scanning user metadata', 'wp-unused-cleaner'),
            'attachments' => __('Checking media library items', 'wp-unused-cleaner'),
            'files' => __('Checking upload files', 'wp-unused-cleaner'),
            'done' => __('Complete', 'wp-unused-cleaner'),
        ];
        $totals = [
            'posts' => count((array) ($state['sources']['post_ids'] ?? [])),
            'options' => (int) ($state['sources']['options_total'] ?? 0),
            'term_meta' => (int) ($state['sources']['term_meta_total'] ?? 0),
            'user_meta' => (int) ($state['sources']['user_meta_total'] ?? 0),
            'attachments' => count((array) ($state['sources']['attachment_ids'] ?? [])),
            'files' => count((array) ($state['sources']['file_paths'] ?? [])),
        ];
        $weights = ['posts' => 25, 'options' => 10, 'term_meta' => 10, 'user_meta' => 10, 'attachments' => 25, 'files' => 20];
        $percent = 0;
        foreach ($weights as $name => $weight) {
            $total = max(1, (int) ($totals[$name] ?? 0));
            if ($phase === 'done') {
                $processed = $total;
            } elseif ($phase === $name) {
                $processed = min((int) ($state['cursor'] ?? 0), $total);
            } elseif ($this->phase_rank($phase) > $this->phase_rank($name)) {
                $processed = $total;
            } else {
                $processed = 0;
            }
            $percent += ($processed / $total) * $weight;
        }
        return [
            'phase' => $phase,
            'label' => $map[$phase] ?? $phase,
            'processed' => (int) ($state['cursor'] ?? 0),
            'total' => (int) ($totals[$phase] ?? 0),
            'percent' => min(100, max(0, (int) round($percent))),
        ];
    }

    private function phase_rank($phase) {
        $order = ['posts' => 1, 'options' => 2, 'term_meta' => 3, 'user_meta' => 4, 'attachments' => 5, 'files' => 6, 'done' => 7];
        return $order[$phase] ?? 0;
    }

    private function hydrate_state_references($state) {
        $this->reference_urls = array_fill_keys((array) ($state['references']['urls'] ?? []), true);
        $this->referenced_attachment_ids = array_fill_keys(array_map('intval', (array) ($state['references']['attachment_ids'] ?? [])), true);
        $this->referenced_relative_paths = array_fill_keys((array) ($state['references']['relative_paths'] ?? []), true);
    }

    private function persist_state_references(&$state) {
        $state['references']['urls'] = array_keys($this->reference_urls);
        $state['references']['attachment_ids'] = array_map('intval', array_keys($this->referenced_attachment_ids));
        $state['references']['relative_paths'] = array_keys($this->referenced_relative_paths);
    }

    private function scan_post($post_id, $baseurl, $basedir) {
        $post = get_post($post_id);
        if (! $post) {
            return;
        }
        $this->extract_references_from_text((string) $post->post_content, $baseurl, $basedir);
        $this->extract_references_from_text((string) $post->post_excerpt, $baseurl, $basedir);
        $this->extract_references_from_text((string) $post->post_title, $baseurl, $basedir);
        $thumb_id = (int) get_post_thumbnail_id($post_id);
        if ($thumb_id > 0) {
            $this->referenced_attachment_ids[$thumb_id] = true;
        }
        $meta = get_post_meta($post_id);
        foreach ($meta as $meta_key => $meta_values) {
            if (0 === strpos((string) $meta_key, '_edit_')) {
                continue;
            }
            foreach ((array) $meta_values as $meta_value) {
                $this->extract_references_from_mixed(maybe_unserialize($meta_value), $baseurl, $basedir);
            }
        }
    }

    private function extract_references_from_mixed($value, $baseurl, $basedir) {
        if (is_numeric($value)) {
            $attachment_id = (int) $value;
            if ($attachment_id > 0 && 'attachment' === get_post_type($attachment_id)) {
                $this->referenced_attachment_ids[$attachment_id] = true;
            }
            return;
        }
        if (is_string($value)) {
            $this->extract_references_from_text($value, $baseurl, $basedir);
            return;
        }
        if (is_array($value) || is_object($value)) {
            foreach ((array) $value as $key => $item) {
                if (is_string($key)) {
                    $this->extract_references_from_text($key, $baseurl, $basedir);
                }
                $this->extract_references_from_mixed($item, $baseurl, $basedir);
            }
        }
    }

    private function extract_references_from_text($text, $baseurl, $basedir) {
        if (! is_string($text) || '' === $text) {
            return;
        }
        if (preg_match_all('/wp-image-([0-9]+)/', $text, $matches)) {
            foreach ((array) $matches[1] as $attachment_id) {
                $attachment_id = (int) $attachment_id;
                if ($attachment_id > 0) {
                    $this->referenced_attachment_ids[$attachment_id] = true;
                }
            }
        }
        if ($baseurl && preg_match_all('/["\'](' . preg_quote($baseurl, '/') . '[^"\']+)["\']/', $text, $url_matches)) {
            foreach ((array) $url_matches[1] as $url) {
                $this->mark_url_reference($url, $baseurl, $basedir);
            }
        }
        if ($baseurl && preg_match_all("#" . preg_quote($baseurl, "#") . "/[^\s\"'\)<>]+#", $text, $loose_matches)) {
            foreach ((array) $loose_matches[0] as $url) {
                $this->mark_url_reference($url, $baseurl, $basedir);
            }
        }
        if (preg_match_all('/(?:^|[,\[{\s])([0-9]{1,8})(?:$|[\]},\s])/', $text, $id_matches)) {
            foreach ((array) $id_matches[1] as $candidate) {
                $attachment_id = (int) $candidate;
                if ($attachment_id > 0 && 'attachment' === get_post_type($attachment_id)) {
                    $this->referenced_attachment_ids[$attachment_id] = true;
                }
            }
        }
    }

    private function mark_url_reference($url, $baseurl, $basedir) {
        $url = trim((string) $url);
        if ('' === $url) {
            return;
        }
        $url = strtok($url, '?');
        $url = strtok($url, '#');
        $this->reference_urls[$url] = true;

        $relative = wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($baseurl)), '', wp_normalize_path($url)));
        if ($relative) {
            $this->referenced_relative_paths[ltrim($relative, '/')] = true;
        }

        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id > 0) {
            $this->referenced_attachment_ids[$attachment_id] = true;
        }

        $full_path = wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($baseurl)), wp_normalize_path(trailingslashit($basedir)), wp_normalize_path($url)));
        if ($full_path) {
            $relative_full = wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($basedir)), '', $full_path));
            if ($relative_full) {
                $this->referenced_relative_paths[ltrim($relative_full, '/')] = true;
            }
        }
    }

    private function build_unused_attachment_item($attachment_id, $baseurl, $basedir) {
        $file_path = get_attached_file($attachment_id);
        $url = wp_get_attachment_url($attachment_id);
        $mime_type = get_post_mime_type($attachment_id);
        if ($this->is_attachment_referenced($attachment_id, $url, $file_path, $baseurl, $basedir)) {
            return null;
        }
        return [
            'id' => $attachment_id,
            'title' => get_the_title($attachment_id) ?: sprintf(__('Attachment #%d', 'wp-unused-cleaner'), $attachment_id),
            'url' => $url,
            'file_path' => $file_path,
            'mime_type' => $mime_type ?: __('Unknown', 'wp-unused-cleaner'),
            'size' => $this->format_file_size($file_path),
            'date' => get_the_date(get_option('date_format'), $attachment_id),
            'kind' => $this->get_attachment_kind($mime_type),
        ];
    }

    private function build_unused_file_item($path, $baseurl, $basedir) {
        $path = wp_normalize_path($path);
        if (! $path || ! file_exists($path) || ! is_file($path)) {
            return null;
        }
        $relative = ltrim(wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($basedir)), '', $path)), '/');
        if ($relative === '' || isset($this->referenced_relative_paths[$relative])) {
            return null;
        }
        $url = trailingslashit($baseurl) . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        if (isset($this->reference_urls[$url])) {
            return null;
        }
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id > 0) {
            return null;
        }
        return [
            'path' => $path,
            'relative' => $relative,
            'url' => $url,
            'size' => size_format((int) @filesize($path), 2),
            'modified' => wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) @filemtime($path)),
            'kind' => $this->get_file_kind($relative),
            'ext' => strtolower((string) pathinfo($relative, PATHINFO_EXTENSION)),
            'is_thumbnail' => $this->is_wp_generated_thumbnail($relative),
        ];
    }

    private function is_attachment_referenced($attachment_id, $url, $file_path, $baseurl, $basedir) {
        if (isset($this->referenced_attachment_ids[$attachment_id])) {
            return true;
        }
        if ($url) {
            $normalized_url = strtok((string) $url, '?');
            $normalized_url = strtok($normalized_url, '#');
            if (isset($this->reference_urls[$normalized_url])) {
                return true;
            }
        }
        if ($file_path) {
            $relative = ltrim(wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($basedir)), '', wp_normalize_path((string) $file_path))), '/');
            if ($relative && isset($this->referenced_relative_paths[$relative])) {
                return true;
            }
        }
        $metadata = wp_get_attachment_metadata($attachment_id, true);
        if (is_array($metadata)) {
            if (! empty($metadata['file'])) {
                $meta_file = ltrim(wp_normalize_path((string) $metadata['file']), '/');
                if (isset($this->referenced_relative_paths[$meta_file])) {
                    return true;
                }
            }
            if (! empty($metadata['sizes']) && is_array($metadata['sizes']) && $file_path) {
                $dir = trailingslashit(dirname((string) $file_path));
                foreach ($metadata['sizes'] as $size_data) {
                    if (empty($size_data['file'])) {
                        continue;
                    }
                    $variant_path = wp_normalize_path($dir . $size_data['file']);
                    $relative = ltrim(wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($basedir)), '', $variant_path)), '/');
                    if ($relative && isset($this->referenced_relative_paths[$relative])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function should_skip_file($path, $basedir) {
        $relative = ltrim(wp_normalize_path(str_replace(wp_normalize_path(trailingslashit($basedir)), '', wp_normalize_path($path))), '/');
        if ('' === $relative) {
            return true;
        }
        return (bool) preg_match('#(^|/)cache/#i', $relative);
    }

    private function format_file_size($path) {
        if (! $path || ! file_exists($path)) {
            return __('Unknown', 'wp-unused-cleaner');
        }
        return size_format((int) filesize($path), 2);
    }

    private function get_attachment_kind($mime_type) {
        $mime_type = (string) $mime_type;
        if (0 === strpos($mime_type, 'image/')) {
            return 'image';
        }
        if (0 === strpos($mime_type, 'video/')) {
            return 'video';
        }
        if (0 === strpos($mime_type, 'audio/')) {
            return 'audio';
        }
        if (false !== strpos($mime_type, 'pdf') || false !== strpos($mime_type, 'document') || false !== strpos($mime_type, 'text')) {
            return 'document';
        }
        return 'other';
    }

    private function is_wp_generated_thumbnail($relative) {
        $basename = wp_basename((string) $relative);
        return (bool) preg_match('/-\d+x\d+(?:@\d+x)?\.(jpe?g|png|gif|webp|avif)$/i', $basename);
    }

    private function get_file_kind($relative) {
        if ($this->is_wp_generated_thumbnail($relative)) {
            return 'image';
        }
        $ext = strtolower((string) pathinfo((string) $relative, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'], true)) {
            return 'image';
        }
        if (in_array($ext, ['mp4', 'mov', 'avi', 'm4v', 'webm'], true)) {
            return 'video';
        }
        if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true)) {
            return 'audio';
        }
        if (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'csv', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            return 'document';
        }
        return 'other';
    }
}
