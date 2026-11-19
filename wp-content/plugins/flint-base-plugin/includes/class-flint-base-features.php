<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Features {
	private static $instance;
	public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() {
		add_action( 'init', array( $this, 'remove_core_actions' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'performance_cleanup_assets' ), 100 );
		add_filter( 'wp_default_scripts', array( $this, 'maybe_remove_jquery_migrate' ) );
		add_action( 'init', array( $this, 'register_menus' ), 20 );
		add_filter( 'excerpt_length', array( $this, 'excerpt_length' ), 999 );
		add_filter( 'excerpt_more', fn() => '...' );
		add_action( 'add_attachment', array( $this, 'image_defaults' ) );
		add_filter( 'upload_mimes', array( $this, 'svg_mime' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'svg_filetype' ), 10, 5 );
		add_action( 'admin_head', array( $this, 'svg_admin_css' ) );
		add_filter( 'gform_submit_button', array( $this, 'gravity_forms_button' ), 10, 2 );
		add_filter( 'rest_endpoints', array( $this, 'protect_rest_user_endpoints' ) );
	}

	public function remove_core_actions() {
		if ( flint_base_feature_enabled( 'remove_emojis' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}

		if ( flint_base_feature_enabled( 'remove_oembed_discovery' ) ) {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		}
		if ( flint_base_feature_enabled( 'remove_head_meta' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			remove_action( 'wp_head', 'rsd_link' );
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
		if ( flint_base_feature_enabled( 'remove_rest_link' ) ) {
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		}
		if ( flint_base_feature_enabled( 'remove_shortlink' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		}
	}

	public function performance_cleanup_assets() {
		if ( flint_base_feature_enabled( 'remove_block_styles' ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
		if ( flint_base_feature_enabled( 'remove_global_styles' ) ) {
			wp_dequeue_style( 'global-styles' );
		}
		if ( flint_base_feature_enabled( 'remove_classic_theme_styles' ) || flint_base_feature_enabled( 'remove_global_styles' ) ) {
			wp_dequeue_style( 'classic-theme-styles' );
		}
		if ( flint_base_feature_enabled( 'remove_dashicons_frontend' ) && ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
		}
		if ( flint_base_feature_enabled( 'remove_wp_embed' ) ) {
			wp_dequeue_script( 'wp-embed' );
		}
	}

	public function maybe_remove_jquery_migrate( $scripts ) {
		if ( is_admin() || is_user_logged_in() || ! flint_base_feature_enabled( 'remove_jquery_migrate' ) || ! isset( $scripts->registered['jquery'] ) ) { return; }
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}

	public function frontend_assets() {
		if ( flint_base_feature_enabled( 'jquery_cdn' ) && ! is_admin() ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), '3.7.1', false );
		}
		wp_enqueue_script( 'jquery' );
		if ( flint_base_feature_enabled( 'slick' ) ) {
			wp_enqueue_script( 'flint-slick', FLINT_BASE_URL . 'assets/vendor/slick.js', array( 'jquery' ), '1.8.1', true );
		}
		wp_localize_script( 'jquery', 'flintBase', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'homeUrl' => home_url( '/' ),
			'themeUrl' => get_template_directory_uri(),
		) );
	}

	public function register_menus() {
		$registry = apply_filters( 'flint_base_layout_registry', array( 'headers' => array(), 'footers' => array() ) );
		$header = flint_base_get_setting( 'header_layout', 'header-1' );
		$footer = flint_base_get_setting( 'footer_layout', 'footer-1' );
		$menus = array();
		if ( ! empty( $registry['headers'][ $header ]['menus'] ) ) { $menus += $registry['headers'][ $header ]['menus']; }
		if ( ! empty( $registry['footers'][ $footer ]['menus'] ) ) { $menus += $registry['footers'][ $footer ]['menus']; }
		if ( ! $menus && empty( $registry['headers'] ) && empty( $registry['footers'] ) ) { $menus = array( 'main-menu' => 'Main Menu', 'policy-menu' => 'Policy Menu' ); }
		if ( $menus ) { register_nav_menus( $menus ); }
	}

	public function excerpt_length() { return absint( flint_base_get_setting( 'excerpt_length', 15 ) ); }

	public function image_defaults( $post_id ) {
		if ( ! flint_base_feature_enabled( 'image_alt_defaults' ) || ! wp_attachment_is_image( $post_id ) ) { return; }
		$post = get_post( $post_id );
		if ( ! $post ) { return; }
		$title = ucwords( strtolower( preg_replace( '/\s*[-_\s]+\s*/', ' ', $post->post_title ) ) );
		if ( ! get_post_meta( $post_id, '_wp_attachment_image_alt', true ) ) { update_post_meta( $post_id, '_wp_attachment_image_alt', $title ); }
		if ( $title !== $post->post_title ) { wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) ); }
	}

	public function svg_mime( $mimes ) {
		if ( flint_base_feature_enabled( 'svg_uploads' ) && current_user_can( 'upload_files' ) ) { $mimes['svg'] = 'image/svg+xml'; }
		return $mimes;
	}

	public function svg_filetype( $data, $file, $filename, $mimes, $real_mime = null ) {
		if ( ! flint_base_feature_enabled( 'svg_uploads' ) || 'svg' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) { return $data; }
		$data['ext'] = 'svg'; $data['type'] = 'image/svg+xml'; return $data;
	}

	public function svg_admin_css() {
		if ( flint_base_feature_enabled( 'svg_uploads' ) ) { echo '<style>.attachment-266x266 img[src$=".svg"],.thumbnail img[src$=".svg"]{width:100%!important;height:auto!important}</style>'; }
	}

	/**
	 * Prevent anonymous visitors from enumerating WordPress users through REST.
	 * Authenticated users retain whatever access WordPress capabilities normally grant.
	 */
	public function protect_rest_user_endpoints( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	public function gravity_forms_button( $button, $form ) {
		if ( ! flint_base_feature_enabled( 'gravity_forms_button' ) ) { return $button; }
		$id = absint( $form['id'] ?? 0 );
		$text = esc_html( $form['button']['text'] ?? __( 'Submit', 'flint-base' ) );
		return '<button type="submit" class="button gform_button" id="gform_submit_button_' . $id . '">' . $text . '</button>';
	}
}
