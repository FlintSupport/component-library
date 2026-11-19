<?php
/**
 * Flint Base Theme functions.
 *
 * Presentation belongs here: assets, templates, ACF block rendering, and layout definitions.
 * Site-wide behavior and setup toggles live in the Flint Base plugin.
 */

defined( 'ABSPATH' ) || exit;
function flint_theme_source_dir_name() {
	$dated = glob( get_template_directory() . '/src-??-??-??', GLOB_ONLYDIR );
	if ( $dated ) { rsort( $dated ); return basename( $dated[0] ); }
	return 'base-src';
}
function flint_theme_source_uri( $file = '' ) { return trailingslashit( get_template_directory_uri() . '/' . flint_theme_source_dir_name() ) . ltrim( $file, '/' ); }



function flint_theme_setup() {
	load_theme_textdomain( 'flint-base', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( flint_theme_source_dir_name() . '/styles.css', 'style.css' ) );
}
add_action( 'after_setup_theme', 'flint_theme_setup' );

function flint_theme_assets() {
	$theme = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_style( 'flint-base-components', flint_theme_source_uri( 'styles.css' ), array(), $version );
	wp_enqueue_style( 'flint-base-theme', get_stylesheet_uri(), array( 'flint-base-components' ), $version );
	wp_enqueue_script( 'flint-site', get_template_directory_uri() . '/js/site.js', array( 'jquery' ), $version, true );
}
add_action( 'wp_enqueue_scripts', 'flint_theme_assets', 20 );

/**
 * Layouts exposed to the Flint Base plugin's Starting Setup screen.
 */
function flint_theme_layout_registry( $registry ) {
	$registry['headers'] = array(
		'none' => array( 'label' => __( 'Disabled — No theme header', 'flint-base' ), 'menus' => array() ),
		'header-1' => array(
			'label' => __( 'Header 1 — Standard', 'flint-base' ),
			'menus' => array( 'utility-menu' => 'Utility Menu', 'main-menu' => 'Main Menu' ),
		),
		'header-2' => array(
			'label' => __( 'Header 2 — Banking / CTA', 'flint-base' ),
			'menus' => array( 'utility-menu' => 'Utility Menu', 'main-menu-two' => 'Main Menu 2' ),
		),
		'header-3' => array(
			'label' => __( 'Header 3 — Alternate', 'flint-base' ),
			'menus' => array( 'utility-menu' => 'Utility Menu', 'main-menu-three' => 'Main Menu 3' ),
		),
	);
	$registry['footers'] = array(
		'none' => array( 'label' => __( 'Disabled — No theme footer', 'flint-base' ), 'menus' => array() ),
		'footer-1' => array(
			'label' => __( 'Footer 1 — Standard', 'flint-base' ),
			'menus' => array( 'footer-menu-one' => 'Footer Menu 1', 'footer-menu-two' => 'Footer Menu 2', 'policy-menu' => 'Policy Menu' ),
		),
		'footer-2' => array(
			'label' => __( 'Footer 2 — Logo / Social', 'flint-base' ),
			'menus' => array( 'footer-menu-one' => 'Footer Menu 1', 'footer-menu-two' => 'Footer Menu 2', 'policy-menu' => 'Policy Menu' ),
		),
		'footer-3' => array(
			'label' => __( 'Footer 3 — CTA / Multi-column', 'flint-base' ),
			'menus' => array( 'footer-menu-one' => 'Footer Menu 1', 'footer-menu-two' => 'Footer Menu 2', 'policy-menu' => 'Policy Menu' ),
		),
		'footer-4' => array(
			'label' => __( 'Footer 4 — Compact / Banking', 'flint-base' ),
			'menus' => array( 'footer-menu-one' => 'Footer Menu 1', 'policy-menu' => 'Policy Menu' ),
		),
	);
	return $registry;
}
add_filter( 'flint_base_layout_registry', 'flint_theme_layout_registry' );

/**
 * ACF blocks stay in the theme because their markup/templates are presentation code.
 */
function flint_theme_block_registry() {
	return array(
		'hero'            => array( 'title' => 'Hero Block', 'description' => 'Page hero for all pages', 'icon' => 'cover-image', 'keywords' => array( 'hero', 'intro', 'header' ) ),
		'flexiblecontent' => array( 'title' => 'Flexible Content Block', 'description' => 'Flexible content with column selection', 'icon' => 'table-row-before', 'keywords' => array( 'content', 'wysiwyg', 'video', 'image' ) ),
		'accordion'       => array( 'title' => 'Accordion Block', 'description' => 'General or ordered list accordion', 'icon' => 'menu', 'keywords' => array( 'accordion', 'toggle', 'content' ) ),
		'cta'             => array( 'title' => 'CTA Block', 'description' => 'Flexible call-to-action section', 'icon' => 'button', 'keywords' => array( 'cta', 'call', 'action' ) ),
		'posts'           => array( 'title' => 'Posts Feature Block', 'description' => 'Showcase posts by newest or custom order', 'icon' => 'welcome-write-blog', 'keywords' => array( 'post', 'feature', 'news' ) ),
		'testimonials'    => array( 'title' => 'Testimonial Block', 'description' => 'Testimonials with quote and headshot', 'icon' => 'admin-comments', 'keywords' => array( 'testimonial', 'quote', 'review' ) ),
		'cards'           => array( 'title' => 'Card Block', 'description' => 'Display cards with optional links', 'icon' => 'columns', 'keywords' => array( 'card', 'content', 'media' ) ),
		'gallery'         => array( 'title' => 'Image Gallery Block', 'description' => 'Tiled image gallery with optional captions', 'icon' => 'format-gallery', 'keywords' => array( 'image', 'gallery', 'media' ) ),
		'newsletter'      => array( 'title' => 'Newsletter Block', 'description' => 'Newsletter sign-up CTA', 'icon' => 'email-alt', 'keywords' => array( 'newsletter', 'form', 'email' ) ),
		'form'            => array( 'title' => 'Form Block', 'description' => 'Basic form block', 'icon' => 'forms', 'keywords' => array( 'form', 'contact' ) ),
		'comparison'      => array( 'title' => 'Comparison Card Block', 'description' => 'Compare up to three items', 'icon' => 'editor-table', 'keywords' => array( 'table', 'comparison', 'card' ) ),
		'slider'          => array( 'title' => 'Image Slider Block', 'description' => 'Simple image slider', 'icon' => 'slides', 'keywords' => array( 'slider', 'carousel', 'slick' ) ),
		'tabs'            => array( 'title' => 'Tab Block', 'description' => 'Tabbed content layouts', 'icon' => 'table-row-after', 'keywords' => array( 'tab', 'content' ) ),
		'videocarousel'   => array( 'title' => 'Video Carousel Block', 'description' => 'Carousel for videos', 'icon' => 'format-video', 'keywords' => array( 'video', 'youtube', 'carousel' ) ),
	);
}

function flint_theme_register_acf_blocks() {
	static $registered = false;
	if ( $registered || ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}
	$registered = true;
	foreach ( flint_theme_block_registry() as $name => $block ) {
		// Do not double-register a block if another project component has already
		// provided the same Flint block name.
		$full_name = 'acf/' . $name;
		if ( class_exists( 'WP_Block_Type_Registry' ) && WP_Block_Type_Registry::get_instance()->is_registered( $full_name ) ) {
			continue;
		}
		acf_register_block_type( array(
			// ACF Blocks V3 keeps the real rendered preview visible while editing.
			// Automatic inline detection is intentionally disabled because ACF's detection pass can
			// replace structured field values (links/images/etc.) with marker strings. Flint keeps
			// the real preview visible and uses the full field editor for reliable complex editing.
			'api_version'       => 3,
			'acf_block_version' => 3,
			'name'            => $name,
			'title'           => __( $block['title'], 'flint-base' ),
			'description'     => __( $block['description'], 'flint-base' ),
			'render_callback' => 'flint_theme_render_acf_block',
			'category'        => 'flint',
			'icon'            => $block['icon'],
			'keywords'        => $block['keywords'],
			'mode'            => 'preview',
			'auto_inline_editing' => false,
			'hide_fields_in_sidebar' => false,
			'expanded_editor_button_text' => __( 'Edit all block fields', 'flint-base' ),
			'supports'        => array( 'align' => true, 'anchor' => true ),
		) );
	}
}
add_action( 'acf/init', 'flint_theme_register_acf_blocks', 20 );
// Fallback for unusual load orders; the static guard prevents duplicate registration.
add_action( 'init', 'flint_theme_register_acf_blocks', 20 );

function flint_theme_render_acf_block( $block ) {
	$slug = str_replace( 'acf/', '', $block['name'] );
	$template = get_theme_file_path( '/template-parts/content-' . sanitize_file_name( $slug ) . '.php' );
	if ( file_exists( $template ) ) {
		include $template;
	}
}

function flint_theme_block_category( $categories ) {
	$categories[] = array( 'slug' => 'flint', 'title' => __( 'Flint Blocks', 'flint-base' ) );
	return $categories;
}
add_filter( 'block_categories_all', 'flint_theme_block_category' );

/** Allow the plugin to label Flint ACF blocks before ACF has registered them. */
function flint_theme_known_blocks( $blocks ) {
	foreach ( flint_theme_block_registry() as $name => $block ) {
		$blocks[ 'acf/' . $name ] = $block['title'];
	}
	return $blocks;
}
add_filter( 'flint_base_known_custom_blocks', 'flint_theme_known_blocks' );


/**
 * Load the same presentation assets in the block-editor canvas as on the front end.
 * WordPress 7.1+ renders the canvas in an iframe, so enqueue_block_assets is the
 * reliable way to make ACF previews inherit the real component styling.
 */
function flint_theme_block_editor_preview_assets() {
	if ( ! is_admin() ) {
		return;
	}
	$theme   = wp_get_theme();
	$version = $theme->get( 'Version' );
	wp_enqueue_style( 'flint-base-components', flint_theme_source_uri( 'styles.css' ), array(), $version );
	wp_enqueue_style( 'flint-base-theme', get_stylesheet_uri(), array( 'flint-base-components' ), $version );
	wp_enqueue_script( 'flint-site-editor-preview', get_template_directory_uri() . '/js/site.js', array( 'jquery' ), $version, true );
}
add_action( 'enqueue_block_assets', 'flint_theme_block_editor_preview_assets', 20 );
