<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Editor {
	private static $instance;
	public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() {
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_blocks' ), 999, 2 );
		add_action( 'after_setup_theme', array( $this, 'gutenberg_palette' ), 30 );
		add_filter( 'tiny_mce_before_init', array( $this, 'tinymce_colors' ) );
		add_action( 'init', array( $this, 'tinymce_plugins' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wysiwyg_config' ) );
		add_action( 'enqueue_block_assets', array( $this, 'block_preview_assets' ), 5 );
		add_filter( 'acf/blocks/default_expanded_editor_button_text', array( $this, 'expanded_editor_button_text' ) );
	}


	/** Load behavior dependencies inside the editor canvas so previews behave like the front end. */
	public function block_preview_assets() {
		if ( ! is_admin() ) return;
		wp_enqueue_script( 'jquery' );
		if ( flint_base_feature_enabled( 'slick' ) ) {
			wp_enqueue_script( 'flint-slick', FLINT_BASE_URL . 'assets/vendor/slick.js', array( 'jquery' ), '1.8.1', true );
		}
	}

	/** Client-facing language for the fallback full-field editor used by complex ACF fields. */
	public function expanded_editor_button_text( $text ) {
		return __( 'Edit all block fields', 'flint-base' );
	}

	public function allowed_blocks( $allowed, $context ) {
		$s = flint_base_get_settings();
		if ( empty( $s['blocks_configured'] ) ) { return $allowed; }

		// Resolve the saved configuration against WordPress' live registry at
		// editor runtime. ACF blocks are registered after the theme loads, so the
		// settings screen's discovery list must not be treated as the registry.
		$post_type = '';
		if ( ! empty( $context->post ) && ! empty( $context->post->post_type ) ) {
			$post_type = sanitize_key( $context->post->post_type );
		}

		$rules = (array) ( $s['block_rules'] ?? array() );
		if ( $post_type && array_key_exists( $post_type, $rules ) ) {
			$enabled = (array) $rules[ $post_type ];
		} else {
			// 2.2.x migration/fallback: use the previous global selection until
			// this post type has explicitly been saved in the new Blocks UI.
			$enabled = (array) ( $s['enabled_blocks'] ?? array() );
			if ( ! $enabled && ! $post_type ) { return $allowed; }
		}
		$enabled = array_values( array_unique( array_filter( $enabled ) ) );
		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) { return $enabled; }

		$registered = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
		return array_values( array_intersect( $enabled, $registered ) );
	}

	public function gutenberg_palette() {
		$palette = array();
		foreach ( (array) flint_base_get_setting( 'editor_colors', array() ) as $color ) {
			if ( empty( $color['name'] ) || empty( $color['color'] ) ) continue;
			$palette[] = array( 'name' => $color['name'], 'slug' => $color['slug'], 'color' => $color['color'] );
		}
		if ( $palette ) { add_theme_support( 'editor-color-palette', $palette ); }
	}

	public function tinymce_colors( $settings ) {
		$map = array();
		foreach ( (array) flint_base_get_setting( 'editor_colors', array() ) as $color ) {
			$hex = ltrim( $color['color'] ?? '', '#' ); if ( ! $hex ) continue;
			$map[] = strtoupper( $hex ); $map[] = $color['name'] ?? $hex;
		}
		if ( $map ) { $settings['textcolor_map'] = wp_json_encode( $map ); $settings['textcolor_rows'] = 2; }
		return $settings;
	}

	public function tinymce_plugins() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) { return; }
		if ( 'true' !== get_user_option( 'rich_editing' ) ) { return; }
		if ( flint_base_feature_enabled( 'wysiwyg_elements' ) || flint_base_feature_enabled( 'wysiwyg_tables' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'buttons' ) );
		}
	}
	public function wysiwyg_config() {
		if ( ! flint_base_feature_enabled( 'wysiwyg_elements' ) ) return;
		wp_register_script( 'flint-wysiwyg-config', '', array(), FLINT_BASE_VERSION, true );
		wp_enqueue_script( 'flint-wysiwyg-config' );
		wp_add_inline_script( 'flint-wysiwyg-config', 'window.flintWysiwygElements = ' . wp_json_encode( array_values( (array) flint_base_get_setting( 'wysiwyg_elements', array() ) ) ) . ';', 'before' );
	}

	public function external_plugins( $plugins ) {
		if ( flint_base_feature_enabled( 'wysiwyg_elements' ) ) $plugins['shortcodes_button'] = FLINT_BASE_URL . 'assets/vendor/tinymce-elements.js';
		if ( flint_base_feature_enabled( 'wysiwyg_tables' ) ) $plugins['table'] = FLINT_BASE_URL . 'assets/vendor/tinymce-table.js';
		return $plugins;
	}
	public function buttons( $buttons ) {
		if ( flint_base_feature_enabled( 'wysiwyg_elements' ) ) $buttons[] = 'shortcodes_button';
		if ( flint_base_feature_enabled( 'wysiwyg_tables' ) ) $buttons[] = 'table';
		return array_values( array_unique( $buttons ) );
	}
}
