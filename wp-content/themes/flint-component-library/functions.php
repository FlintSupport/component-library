<?php
//ADJUST ACTIONS AND THEME SUPPORT
remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); 
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' ); 
remove_action( 'wp_print_styles', 'print_emoji_styles' ); 
remove_action( 'admin_print_styles', 'print_emoji_styles' );
add_theme_support( 'responsive-embeds' );
add_theme_support( 'post-thumbnails' );

$theme = wp_get_theme();
define('themeversion', $theme->Version);

function flint_scripts_styles() {
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js', array(), '3.6.3' );
	wp_enqueue_script( 'slick', $src = get_template_directory_uri().'/js/slick.js', $deps = array('jquery'), '1.0', $in_footer = true );
    wp_enqueue_script( 'main', $src = get_template_directory_uri().'/js/site.js', $deps = array('jquery'), '1.0', $in_header = true );
    wp_enqueue_style( 'basestyle', $src = get_template_directory_uri().'/base-src/styles.css', $deps = array(), '1.0', $media = 'all' );
	wp_enqueue_style( 'style', $src = get_template_directory_uri().'/style.css', $deps = array(), themeversion, $media = 'all' );
}
add_action( 'wp_enqueue_scripts', 'flint_scripts_styles' );


//SET EXCERPT LENGTH
function flint_excerpts( $length ) {
    return 15;
}
add_filter( 'excerpt_length', 'flint_excerpts', 999 );
function flint_read_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'flint_read_more' );


//ALLOW SVG UPLOADS
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
	$filetype = wp_check_filetype( $filename, $mimes );
	return [
		'ext'             => $filetype['ext'],
		'type'            => $filetype['type'],
		'proper_filename' => $data['proper_filename']
	];
  
  }, 10, 4 );
  
  function cc_mime_types( $mimes ){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
  }
  add_filter( 'upload_mimes', 'cc_mime_types' );
  
  
  function fix_svg() {
	echo '<style type="text/css">
		  .attachment-266x266, .thumbnail img {
			   width: 100% !important;
			   height: auto !important;
		  }
		  </style>';
  }
add_action( 'admin_head', 'fix_svg' );


//CHANGE GRAVITY FORMS SUBMIT TO A BUTTON
add_filter("gform_submit_button", "form_submit_button", 10, 2);
function form_submit_button($button, $form){
	return "<button class='button' id='gform_submit_button_{$form["id"]}'>{$form['button']['text']}</button>";
}


//CUSTOM MENUS
function register_flint_menu() {
    register_nav_menu('utility-menu',__( 'Utility Menu' ));
    register_nav_menu('main-menu',__( 'Main Menu' ));
	register_nav_menu('main-menu-two',__( 'Main Menu 2' ));
	register_nav_menu('main-menu-three',__( 'Main Menu 3' ));
	register_nav_menu('policy-menu',__( 'Policy Menu' ));
    register_nav_menu('footer-menu-one',__( 'Footer Menu 1' ));
	register_nav_menu('footer-menu-two',__( 'Footer Menu 2' ));
	register_nav_menu('footer-menu-three',__( 'Footer Menu 3' ));
	register_nav_menu('footer-menu-four',__( 'Footer Menu 4' ));
}
add_action( 'init', 'register_flint_menu' );


//DEFAULT IMAGE TITLES
add_action( 'add_attachment', 'my_set_image_meta_upon_image_upload' );
function my_set_image_meta_upon_image_upload( $post_ID ) {
    if ( wp_attachment_is_image( $post_ID ) ) {
        $my_image_title = get_post( $post_ID )->post_title;
        $my_image_title = preg_replace( '%\s*[-_\s]+\s*%', ' ',  $my_image_title );
        $my_image_title = ucwords( strtolower( $my_image_title ) );
        $my_image_meta = array(
            'ID'        => $post_ID,           
            'post_title'    => $my_image_title,      
        );
        update_post_meta( $post_ID, '_wp_attachment_image_alt', $my_image_title );
        wp_update_post( $my_image_meta );
    } 
}


//CREATE ACF OPTIONS PAGE
if( function_exists('acf_add_options_page') ) {
	acf_add_options_page('Theme Settings');
}


//CREATE FLINT GUTENBERG BLOCK CATEGORY
function block_category( $categories ) {
	$categories[] = array(
		'slug'  => 'flint',
		'title' => 'flint Blocks'
	);
	return $categories;
}
if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
	add_filter( 'block_categories_all', 'block_category' );
} else {
	add_filter( 'block_categories', 'block_category' );
}


//CREATE ACF BLOCKS
add_action('acf/init', 'flint_acf_init');
function flint_acf_init() {
	if( function_exists('acf_register_block') ) {
		acf_register_block(array(
			'name'				=> 'hero',
			'title'				=> __('Hero Block'),
			'description'		=> __('Page hero for all pages'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'cover-image',
			'keywords'			=> array( 'hero', 'intro', 'header'),
		));
		acf_register_block(array(
			'name'				=> 'flexiblecontent',
			'title'				=> __('Flexible Content Block'),
			'description'		=> __('A generic, flexible content block with column selection'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'table-row-before',
			'keywords'			=> array( 'content', 'intro', 'wysiwyg', 'video', 'image', 'flexible'),
		));
        acf_register_block(array(
			'name'				=> 'accordion',
			'title'				=> __('Accordion Block'),
			'description'		=> __('General or ordered list accordion block.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'menu',
			'keywords'			=> array( 'accordion', 'toggle', 'content', 'list'),
		));
        acf_register_block(array(
			'name'				=> 'cta',
			'title'				=> __('CTA Block'),
			'description'		=> __('Flexible call-to-action section.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'button',
			'keywords'			=> array( 'cta', 'call', 'action'),
		));
		acf_register_block(array(
			'name'				=> 'posts',
			'title'				=> __('Posts Feature Block'),
			'description'		=> __('Showcase posts by newest or custom order.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'welcome-write-blog',
			'keywords'			=> array( 'post', 'feature', 'highlight', 'news'),
		));
        acf_register_block(array(
			'name'				=> 'testimonials',
			'title'				=> __('Testimonial Block'),
			'description'		=> __('Showcases testimonials with a quote and headshot.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'admin-comments',
			'keywords'			=> array( 'testimonial', 'blurb', 'quote', 'review'),
		));
		acf_register_block(array(
			'name'				=> 'cards',
			'title'				=> __('Card Block'),
			'description'		=> __('Display up to 3 cards with optional links to other pages.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'columns',
			'keywords'			=> array( 'tab', 'content', 'media', 'toggle'),
		));
		acf_register_block(array(
			'name'				=> 'gallery',
			'title'				=> __('Image Gallery Block'),
			'description'		=> __('Image gallery in a tiled layout with optional captions.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'align-full-width',
			'keywords'			=> array( 'image', 'gallery', 'media', 'content'),
		));
		acf_register_block(array(
			'name'				=> 'newsletter',
			'title'				=> __('Newsletter Block'),
			'description'		=> __('Display a newsletter sign-up CTA.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'email-alt',
			'keywords'			=> array( 'newsletter', 'form', 'sign-up', 'email'),
		));
		acf_register_block(array(
			'name'				=> 'form',
			'title'				=> __('Form Block'),
			'description'		=> __('A basic form block for forms outside the hero.'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'forms',
			'keywords'			=> array( 'form', 'contact'),
		));
		acf_register_block(array(
			'name'				=> 'comparison',
			'title'				=> __('Comparison Card Block'),
			'description'		=> __('Compares up to three items in columns'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'editor-table',
			'keywords'			=> array( 'table', 'comparison', 'compare', 'card'),
		));
		acf_register_block(array(
			'name'				=> 'slider',
			'title'				=> __('Image Slider Block'),
			'description'		=> __('Simple image slider'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'slides',
			'keywords'			=> array( 'slider', 'carousel', 'slick'),
		));
		acf_register_block(array(
			'name'				=> 'tabs',
			'title'				=> __('Tab Block'),
			'description'		=> __('Provides multiple layouts for tabbed content'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'table-row-after',
			'keywords'			=> array( 'tab', 'content', 'copy'),
		));
		acf_register_block(array(
			'name'				=> 'videocarousel',
			'title'				=> __('Video Carousel Block'),
			'description'		=> __('Displays a carousel for YouTube videos'),
			'render_callback'	=> 'flint_acf_block_render_callback',
			'category'			=> 'flint',
			'icon'				=> 'format-video',
			'keywords'			=> array( 'video', 'youtube', 'carousel', 'slider'),
		));
	}
}
function flint_acf_block_render_callback( $block ) {
	$slug = str_replace('acf/', '', $block['name']);
	if( file_exists( get_theme_file_path("/template-parts/content-{$slug}.php") ) ) {
        include( get_theme_file_path("/template-parts/content-{$slug}.php") );
    }
}


//SET GUTENBERG BLOCKS PER POST TYPE
function wpse_allowed_block_types($allowed_block_types, $post) {
	if($post->post_type == 'page') {
		return array(
			'acf/hero',
			'acf/flexiblecontent',
			'acf/accordion',
			'acf/cta',
			'acf/posts',
			'acf/testimonials',
			'acf/cards',
			'acf/gallery',
			'acf/newsletter',
			'acf/form',
			'acf/comparison',
			'acf/slider',
			'acf/tabs',
			'acf/videocarousel',
		);
    }
	else {
        return array (
			'core/paragraph',
            'core/heading',
			'core/image',
			'core/table',
			'core/embed',
			'core/list',
		);
    }
}
add_filter('allowed_block_types', 'wpse_allowed_block_types', 10, 2);