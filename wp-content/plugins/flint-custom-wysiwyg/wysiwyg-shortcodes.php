<?php
/*
Plugin Name: (ƒ) FLINT - Custom WYSIWYG Shortcodes
Plugin URI: https://flint-group.com
Description: Declares a plugin that will create custom WYSIWYG shortcodes and activates tables.
Version: 1.0
Author URI: https://flint-group.com
*/


//CUSTOM SHORTCODES
add_action( 'after_setup_theme', 'shortcodes_button_setup' );
if ( ! function_exists( 'shortcodes_button_setup' ) ) {
    function shortcodes_button_setup() {
        add_action( 'init', 'shortcodes_button' );
    }
}
if ( ! function_exists( 'shortcodes_button' ) ) {
    function shortcodes_button() {
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }
        if ( get_user_option( 'rich_editing' ) !== 'true' ) {
            return;
        }
        add_filter( 'mce_external_plugins', 'add_shortcodes_button' );
        add_filter( 'mce_buttons', 'register_shortcodes_button' );
    }
}
if ( ! function_exists( 'add_shortcodes_button' ) ) {
    function add_shortcodes_button( $plugin_array ) {
        $plugin_array['shortcodes_button'] = plugin_dir_url( __FILE__ ) . '/tinymce-plugin.js';
        return $plugin_array;
    }
}
if ( ! function_exists( 'register_shortcodes_button' ) ) {
    function register_shortcodes_button( $buttons ) {
        array_push( $buttons, 'shortcodes_button' );
        return $buttons;
    }
}


//TABLES
function add_the_table_button( $buttons ) {
    array_push( $buttons, 'separator', 'table' );
    return $buttons;
}
add_filter( 'mce_buttons', 'add_the_table_button' );

function add_the_table_plugin( $plugins ) {
      $plugins['table'] = plugin_dir_url( __FILE__ ) . '/table-plugin.js';
      return $plugins;
}
add_filter( 'mce_external_plugins', 'add_the_table_plugin' );

?>