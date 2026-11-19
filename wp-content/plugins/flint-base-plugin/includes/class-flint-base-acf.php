<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_ACF {
	private static $instance;
	public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() {
		add_filter( 'acf/settings/load_json', array( $this, 'load_json' ) );
		add_action( 'acf/init', array( $this, 'options_page' ) );
		add_action( 'admin_notices', array( $this, 'acf_notice' ) );
	}
	public function load_json( $paths ) { $paths[] = FLINT_BASE_DIR . 'acf-json'; return array_values( array_unique( $paths ) ); }
	public function options_page() {
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_sub_page( array( 'page_title' => 'Site Content Settings', 'menu_title' => 'Site Content', 'menu_slug' => 'acf-options-theme-settings', 'parent_slug' => 'flint-starting-setup', 'capability' => 'manage_options', 'redirect' => false ) );
		}
	}
	public function acf_notice() {
		if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'ACF' ) ) {
			echo '<div class="notice notice-warning"><p><strong>Flint Base:</strong> Advanced Custom Fields Pro is required for the bundled field groups and Flint ACF blocks.</p></div>';
		}
	}
}
