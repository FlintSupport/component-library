<?php
/**
 * Plugin Name: (ƒ) FLINT - Theme Builder
 * Plugin URI: https://flint-group.com
 * Description: Stable Flint site functionality and the Starting Setup interface for layouts, blocks, content types, editor colors, and common WordPress features.
 * Version: 2.0
 * Author: Flint Group
 * Author URI: https://flint-group.com
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: flint-base
 */

defined( 'ABSPATH' ) || exit;

define( 'FLINT_BASE_VERSION', '2.0' );
define( 'FLINT_BASE_FILE', __FILE__ );
define( 'FLINT_BASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLINT_BASE_URL', plugin_dir_url( __FILE__ ) );

require_once FLINT_BASE_DIR . 'includes/class-flint-base-settings.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-features.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-content.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-editor.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-acf.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-dashboard.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-launch.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-accessibility.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-security.php';
require_once FLINT_BASE_DIR . 'includes/class-flint-base-integrations.php';


/**
 * Keep the Flint Theme Builder out of the normal Plugins list. It remains an
 * active plugin and is managed through Theme Settings instead of plugins.php.
 */
function flint_base_hide_from_plugins_list( $plugins ) {
	$key = plugin_basename( FLINT_BASE_FILE );
	if ( isset( $plugins[ $key ] ) ) { unset( $plugins[ $key ] ); }
	return $plugins;
}
add_filter( 'all_plugins', 'flint_base_hide_from_plugins_list' );

function flint_base() {
	return Flint_Base_Settings::instance();
}

function flint_base_get_settings() {
	return flint_base()->get_settings();
}

function flint_base_get_setting( $key, $default = null ) {
	$settings = flint_base_get_settings();
	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

function flint_base_feature_enabled( $key ) {
	$built_in_features = array( 'svg_uploads', 'gravity_forms_button', 'jquery_cdn', 'remove_emojis', 'remove_dashicons_frontend' );
	if ( in_array( $key, $built_in_features, true ) ) { return true; }
	$features = (array) flint_base_get_setting( 'features', array() );
	return ! empty( $features[ $key ] );
}

function flint_base_boot() {
	Flint_Base_Settings::instance();
	Flint_Base_Features::instance();
	Flint_Base_Content::instance();
	Flint_Base_Editor::instance();
	Flint_Base_ACF::instance();
	Flint_Base_Dashboard::instance();
	Flint_Base_Launch::instance();
	Flint_Base_Accessibility::instance();
	Flint_Base_Security::instance();
	Flint_Base_Integrations::instance();
}
add_action( 'plugins_loaded', 'flint_base_boot' );

register_activation_hook( __FILE__, array( 'Flint_Base_Settings', 'activate' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
