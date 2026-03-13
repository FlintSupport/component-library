<?php
/**
 * Plugin Name: (ƒ) Flint Media Cleaner
 * Description: Scans uploads and the media library for unreferenced items, supports common custom-field patterns including ACF and The Events Calendar, and provides batch cleanup tools.
 * Version: 0.2.6
 * Author: Flint Group
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WPUC_VERSION', '0.2.6');
define('WPUC_FILE', __FILE__);
define('WPUC_DIR', plugin_dir_path(__FILE__));
define('WPUC_URL', plugin_dir_url(__FILE__));

require_once WPUC_DIR . 'includes/class-wpuc-scanner.php';
require_once WPUC_DIR . 'includes/class-wpuc-admin.php';

final class WP_Unused_Cleaner {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        load_plugin_textdomain('wp-unused-cleaner', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            WP_UC_Admin::instance();
        }
    }
}

WP_Unused_Cleaner::instance();