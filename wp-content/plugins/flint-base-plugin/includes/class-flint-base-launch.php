<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Launch {
	private static $instance;
	const OPTION = 'flint_base_launch_checklist';

	public static function instance() {
		if ( ! self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_flint_launch_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_flint_launch_rename_source', array( $this, 'ajax_rename_source' ) );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 20, 2 );
	}

	public function robots_txt( $output, $public ) {
		// WordPress serves a virtual robots.txt when no physical file exists. Keep a sane
		// Flint baseline in the plugin so this no longer needs to be a launch task.
		$lines = array();
		$lines[] = 'User-agent: *';
		$lines[] = 'Disallow: /wp-admin/';
		$lines[] = 'Allow: /wp-admin/admin-ajax.php';
		if ( $public ) {
			$lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
		} else {
			$lines[] = 'Disallow: /';
		}
		return implode( "\n", $lines ) . "\n";
	}

	public function categories() {
		return array(
			'site_setup' => array(
				'label' => '1. Site & Theme Setup',
				'description' => 'Finish the core WordPress and theme configuration before testing launch services.',
			),
			'plugins_forms' => array(
				'label' => '2. Plugins & Forms',
				'description' => 'Clean up launch-only plugins and verify production form configuration and delivery.',
			),
			'quality_search' => array(
				'label' => '3. Accessibility & Search',
				'description' => 'Run final quality checks and confirm the site is ready for search engines.',
			),
			'launch_services' => array(
				'label' => '4. Analytics & Launch Infrastructure',
				'description' => 'Verify production tracking, DNS, HTTPS, and the launch-state backup.',
			),
			'handoff' => array(
				'label' => '5. Post-Launch & Handoff',
				'description' => 'Complete internal notifications, documentation, billing, credentials, and test-site cleanup after production is healthy.',
			),
		);
	}

	public function items() {
		$host = wp_parse_url( home_url('/'), PHP_URL_HOST );
		$items = array(
			// 1. Site & Theme Setup.
			'site_icon'=>array('category'=>'site_setup','label'=>'WordPress site icon added','auto'=>array($this,'auto_site_icon'),'link'=>admin_url('customize.php?autofocus[control]=site_icon'),'link_label'=>'Set site icon'),
			'sass_directory'=>array('category'=>'site_setup','label'=>'Rename base-src theme folder for launch','help'=>'Use the button to rename base-src to src-MM-DD-YY using today’s date.','action'=>'rename_source','link_label'=>'Rename source folder'),

			// 2. Plugins & Forms.
			'disable_backup_plugin'=>array('category'=>'plugins_forms','label'=>'Disable All-in-One WP Migration and Backup plugin if enabled','help'=>'Automatically verified when All-in-One WP Migration and its Unlimited Extension are inactive. The plugin files may remain installed.','auto'=>array($this,'auto_backup_plugin'),'link'=>admin_url('plugins.php'),'link_label'=>'Review plugins'),
			'cleanup_plugins'=>array('category'=>'plugins_forms','label'=>'Remove deactivated/unnecessary plugins','help'=>'Automatically verified when no inactive plugins remain outside All-in-One WP Migration and its Unlimited/Pro extension. Review active plugins manually for anything no longer needed.','auto'=>array($this,'auto_plugin_cleanup'),'link'=>admin_url('plugins.php?plugin_status=inactive'),'link_label'=>'Review plugins'),
			'recaptcha'=>array('category'=>'plugins_forms','label'=>'Add reCAPTCHA to forms if requested','help'=>'Configure Gravity Forms reCAPTCHA when requested for the project.','link'=>admin_url('admin.php?page=gf_settings&subview=recaptcha'),'link_label'=>'Gravity Forms reCAPTCHA settings'),
			'gdpr'=>array('category'=>'plugins_forms','label'=>'Forms: GDPR / Complianz requirement reviewed','help'=>'If subject to GDPR, install/configure Complianz; otherwise confirm the requirement with the project team.','auto'=>array($this,'auto_complianz'),'link'=>admin_url('plugin-install.php?s=Complianz&tab=search&type=term'),'link_label'=>'Review Complianz'),
			'form_delivery'=>array('category'=>'plugins_forms','label'=>'Forms: confirm web forms send to client email','help'=>'Submit each production form and verify delivery to the client recipient(s).','link'=>admin_url('admin.php?page=gf_edit_forms'),'link_label'=>'Open Forms'),

			// 3. Accessibility & Search.
			'accessibility'=>array('category'=>'quality_search','label'=>'Accessibility Report','help'=>'Run the Flint WCAG scan and review machine-detectable accessibility issues.','link'=>admin_url('admin.php?page=flint-starting-setup#accessibility'),'link_label'=>'Open Accessibility Report'),
			'sitemaps'=>array('category'=>'quality_search','label'=>'Verify Rank Math sitemaps are visible','auto'=>array($this,'auto_sitemap'),'link'=>home_url('/sitemap_index.xml'),'link_label'=>'Open Rank Math sitemap'),
			'search_visibility'=>array('category'=>'quality_search','label'=>'“Discourage search engines” is unchecked','auto'=>array($this,'auto_search_visibility'),'link'=>admin_url('options-reading.php'),'link_label'=>'Reading settings'),

			// 4. Analytics & Launch Infrastructure.
			'analytics'=>array('category'=>'launch_services','label'=>'Verify Google Analytics implemented','auto'=>array($this,'auto_analytics'),'link'=>admin_url('admin.php?page=flint-starting-setup#scripts'),'link_label'=>'Open Scripts settings'),
			'dns'=>array('category'=>'launch_services','label'=>'Ensure DNS is pointed to the correct location','link'=>'https://www.whois.com/whois/'.rawurlencode($host),'link_label'=>'WHOIS lookup'),
			'https'=>array('category'=>'launch_services','label'=>'Force HTTPS','auto'=>array($this,'auto_https'),'link'=>admin_url('options-general.php'),'link_label'=>'General settings'),
			'github_backup'=>array('category'=>'launch_services','label'=>'Backup site to GitHub','help'=>'Confirm the launch-state code/configuration has been committed and pushed.'),

			// 5. Post-Launch & Handoff.
			'analytics_notify'=>array('category'=>'handoff','label'=>'Notify Analytics of site launch','help'=>'Notify the Analytics team that the production site has launched.'),
			'launch_spreadsheet'=>array('category'=>'handoff','label'=>'Add site launch date to Digital46 Websites Launched','link'=>'https://flintcommunications.sharepoint.com/:l:/s/digital46/JAAbHj73RY22QrckUJHmTg3OAcsk8nUbtatyJCQjZ1Pm7F8?e=maotGH','link_label'=>'Open Digital46 Websites Launched'),
			'lastpass'=>array('category'=>'handoff','label'=>'Update LastPass entries','help'=>'Update production URLs and credentials in the team password manager.'),
			'hosting_billing'=>array('category'=>'handoff','label'=>'Hosting billing notification completed if applicable','help'=>'For LiquidWeb-hosted sites, notify the appropriate internal billing contacts that the site launched.'),
			'remove_test_site'=>array('category'=>'handoff','label'=>'Remove/disable test site (files & database)','help'=>'Complete only after production is confirmed healthy.'),
		);
		$header = (string) flint_base_get_setting( 'header_layout', 'header-1' );
		if ( in_array( $header, array( '', 'none', 'header-1' ), true ) ) {
			// Keep Max Mega Menu cleanup beside the other plugin cleanup tasks.
			$max_mega = array('category'=>'plugins_forms','label'=>'Remove Max Mega Menu plugin','help'=>'Header Style 1 (or no theme header) does not require Max Mega Menu. Automatically verified when Max Mega Menu is not installed.','auto'=>array($this,'auto_max_mega_menu'),'link'=>admin_url('plugins.php'),'link_label'=>'Review plugins');
			$ordered = array();
			foreach ( $items as $key => $item ) {
				$ordered[ $key ] = $item;
				if ( 'cleanup_plugins' === $key ) { $ordered['max_mega_menu'] = $max_mega; }
			}
			$items = $ordered;
		}
		return $items;
	}

	private function installed_plugin_contains( $needles ) {
		if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
		foreach ( array_keys( get_plugins() ) as $plugin ) {
			foreach ( (array) $needles as $needle ) { if ( false !== stripos( $plugin, $needle ) ) { return true; } }
		}
		return false;
	}
	private function active_plugin_contains( $needles ) {
		$plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) { $plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ); }
		foreach ( $plugins as $plugin ) {
			foreach ( (array) $needles as $needle ) { if ( false !== stripos( $plugin, $needle ) ) { return true; } }
		}
		return false;
	}
	public function auto_site_icon() { return (bool) get_option( 'site_icon' ); }
	public function auto_backup_plugin() { return ! $this->active_plugin_contains( array( 'all-in-one-wp-migration', 'all-in-one-wp-migration-unlimited-extension' ) ); }
	public function auto_plugin_cleanup() {
		if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
		$installed = array_keys( get_plugins() );
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) { $active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ); }
		foreach ( $installed as $plugin ) {
			if ( in_array( $plugin, $active, true ) ) { continue; }
			if ( false !== stripos( $plugin, 'all-in-one-wp-migration' ) ) { continue; }
			return false;
		}
		return true;
	}
	public function auto_max_mega_menu() { return ! $this->installed_plugin_contains( array( 'megamenu', 'max-mega-menu' ) ); }
	public function auto_redirect_plugin() { return $this->active_plugin_contains( array( '301-redirect', '301redirect', 'redirects' ) ); }
	public function auto_sitemap() { return (bool) get_option('blog_public') && ( defined('RANK_MATH_VERSION') || $this->active_plugin_contains(array('seo-by-rank-math')) ) && (bool) get_option('rank-math-options-sitemap',array()); }
	public function auto_complianz() { return $this->active_plugin_contains( array( 'complianz' ) ); }
	public function auto_yoast() { return defined( 'WPSEO_VERSION' ) || $this->active_plugin_contains( array( 'wordpress-seo' ) ); }
	public function auto_https() { return 0 === stripos( home_url( '/' ), 'https://' ) && 0 === stripos( site_url( '/' ), 'https://' ); }
	public function auto_login_url() { return $this->active_plugin_contains( array( 'wps-hide-login', 'hide-login' ) ); }
	public function auto_analytics() { foreach((array)flint_base_get_setting('scripts',array()) as $r){if(in_array($r['type']??'',array('ga4','gtm'),true)&&!empty($r['value']))return true;} return $this->active_plugin_contains(array('google-site-kit','monsterinsights','google-analytics','gtm4wp')); }
	public function auto_search_visibility() { return (bool) get_option( 'blog_public' ); }
	public function auto_yoast_og() {
		if ( ! $this->auto_yoast() ) { return false; }
		$opts = get_option( 'wpseo_social', array() );
		return ! is_array( $opts ) || ! array_key_exists( 'opengraph', $opts ) || ! empty( $opts['opengraph'] );
	}

	public function get_state() {
		$state = get_option( self::OPTION, array() );
		return is_array( $state ) ? $state : array();
	}

	public function item_state( $key, $item, $state ) {
		$auto = ! empty( $item['auto'] ) && is_callable( $item['auto'] ) ? (bool) call_user_func( $item['auto'] ) : false;
		$saved = isset( $state[ $key ] ) && is_array( $state[ $key ] ) ? $state[ $key ] : array();
		return array(
			'complete' => $auto || ! empty( $saved['complete'] ),
			'auto' => $auto,
			'timestamp' => $auto ? '' : (string) ( $saved['timestamp'] ?? '' ),
			'user_id' => $auto ? 0 : absint( $saved['user_id'] ?? 0 ),
		);
	}

	public function ajax_toggle() {
		check_ajax_referer( 'flint_launch_checklist', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 ); }
		$key = sanitize_key( $_POST['key'] ?? '' );
		$complete = ! empty( $_POST['complete'] );
		$items = $this->items();
		if ( ! isset( $items[ $key ] ) ) { wp_send_json_error( array( 'message' => 'Unknown checklist item.' ), 400 ); }
		$current = $this->item_state( $key, $items[ $key ], $this->get_state() );
		if ( $current['auto'] ) { wp_send_json_error( array( 'message' => 'This item is automatically managed.' ), 400 ); }
		$state = $this->get_state();
		if ( $complete ) {
			$state[ $key ] = array( 'complete' => 1, 'timestamp' => current_time( 'mysql' ), 'user_id' => get_current_user_id() );
		} else {
			unset( $state[ $key ] );
		}
		update_option( self::OPTION, $state, false );
		$user = wp_get_current_user();
		wp_send_json_success( array( 'complete' => $complete, 'timestamp' => $complete ? current_time( 'M j, Y g:i a' ) : '', 'user' => $complete ? $user->display_name : '' ) );
	}

	public function ajax_rename_source(){ check_ajax_referer('flint_launch_checklist','nonce'); if(!current_user_can('manage_options'))wp_send_json_error(array('message'=>'Not allowed.'),403); $theme=get_stylesheet_directory(); $from=$theme.'/base-src'; $name='src-'.wp_date('m-d-y'); $to=$theme.'/'.$name; if(!is_dir($from)){ if(is_dir($to))wp_send_json_success(array('message'=>$name.' already exists.')); wp_send_json_error(array('message'=>'base-src was not found in the active theme.')); } if(file_exists($to))wp_send_json_error(array('message'=>$name.' already exists.')); if(!@rename($from,$to))wp_send_json_error(array('message'=>'Unable to rename the folder. Check filesystem permissions.')); wp_send_json_success(array('message'=>'Renamed base-src to '.$name.'. Update theme asset references before launch if they are not dynamic.')); }

	public function render() {
		$items = $this->items(); $categories = $this->categories(); $saved = $this->get_state(); $done = 0;
		foreach ( $items as $key => $item ) { if ( $this->item_state( $key, $item, $saved )['complete'] ) { $done++; } }
		?>
		<div class="flint-launch-summary"><strong><span data-launch-done><?php echo esc_html( $done ); ?></span> of <?php echo esc_html( count( $items ) ); ?> launch checks complete</strong><progress max="<?php echo esc_attr( count( $items ) ); ?>" value="<?php echo esc_attr( $done ); ?>" data-launch-progress></progress></div>
		<p class="description">Work through the checklist by area. Checks save immediately. Automatically verified items are locked and update from the current WordPress/plugin configuration.</p>
		<div class="flint-launch-list" data-launch-nonce="<?php echo esc_attr( wp_create_nonce( 'flint_launch_checklist' ) ); ?>">
		<?php foreach ( $categories as $category_key => $category ) :
			$category_items = array_filter( $items, static function( $item ) use ( $category_key ) { return ( $item['category'] ?? '' ) === $category_key; } );
			if ( ! $category_items ) { continue; }
			$category_done = 0;
			foreach ( $category_items as $key => $item ) { if ( $this->item_state( $key, $item, $saved )['complete'] ) { $category_done++; } }
		?>
			<section class="flint-launch-category" aria-labelledby="flint-launch-<?php echo esc_attr( $category_key ); ?>">
				<header class="flint-launch-category-header">
					<div><h3 id="flint-launch-<?php echo esc_attr( $category_key ); ?>"><?php echo esc_html( $category['label'] ); ?></h3><p><?php echo esc_html( $category['description'] ); ?></p></div>
					<span class="flint-launch-category-count"><?php echo esc_html( $category_done ); ?> / <?php echo esc_html( count( $category_items ) ); ?> complete</span>
				</header>
				<div class="flint-launch-category-items">
				<?php foreach ( $category_items as $key => $item ) : $st = $this->item_state( $key, $item, $saved ); $u = $st['user_id'] ? get_userdata( $st['user_id'] ) : false; ?>
					<div class="flint-launch-item <?php echo $st['complete'] ? 'is-complete' : ''; ?>" data-launch-item="<?php echo esc_attr( $key ); ?>">
						<label><input type="checkbox" data-launch-check value="1" <?php checked( $st['complete'] ); ?> <?php disabled( $st['auto'] ); ?>> <span><strong><?php echo esc_html( $item['label'] ); ?></strong><?php if ( ! empty( $item['help'] ) ) : ?><small><?php echo esc_html( $item['help'] ); ?></small><?php endif; ?></span></label>
						<div class="flint-launch-meta"><?php if ( $st['auto'] ) : ?><span class="flint-auto">Automatically verified</span><?php elseif ( $st['complete'] ) : ?><span data-launch-meta>Completed <?php echo esc_html( wp_date( 'M j, Y g:i a', strtotime( $st['timestamp'] ) ) ); ?><?php echo $u ? ' by ' . esc_html( $u->display_name ) : ''; ?></span><?php else : ?><span data-launch-meta></span><?php endif; ?><?php if ( ! $st['complete'] && ! empty( $item['link'] ) ) : ?> <a data-launch-link href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item['link_label'] ?? 'Complete item' ); ?></a><?php elseif ( ! $st['complete'] && ! empty($item['action']) && 'rename_source'===$item['action'] ) : ?> <button type="button" class="button button-small" data-launch-rename-source><?php echo esc_html($item['link_label']); ?></button><?php endif; ?></div>
					</div>
				<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
		</div>
		<?php
	}

}
