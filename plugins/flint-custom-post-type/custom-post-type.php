<?php
/*
Plugin Name: (ƒ) FLINT - Custom Post: Testing Post Type
Plugin URI: https://flint-group.com
Description: Declares a plugin that will create a custom post type called "NAME HERE".
Version: 1.0
Author URI: https://flint-group.com
*/

function create_POSTTYPE() {
    $labels = array(
    'name' => _x('Post Type Name', 'post type general name'),
    'singular_name' => _x('NAME', 'post type singular name'),
    'add_new' => _x('Add New', 'Testimonial'),
    'add_new_item' => __('Add New Testimonial'),
    'edit_item' => __('Edit NAME'),
    'new_item' => __('NAME'),
    'all_items' => __('All NAMES'),
    'view_item' => __('View NAME'),
    'search_items' => __('Search NAMES'),
    'not_found' =>  __('No NAMES found'),
    'not_found_in_trash' => __('No NAMES found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => 'Post Type Name'

	);
	$args = array(
	  'labels' => $labels,
	  'public' => true,
	  'publicly_queryable' => true,
	  'show_ui' => true, 
	  'show_in_menu' => true, 
	  'query_var' => true,
	  'rewrite' => false,
	  'capability_type' => 'post',
	  'has_archive' => true, 
	  'hierarchical' => false,
	  'menu_position' => null,
	  'supports' => array( 'title', 'author', 'thumbnail', 'editor' ),
	  'rewrite' => array('slug' => 'testing-post-type', 'with_front' => false),
	  'taxonomies' => array('testing-category'), 
	  'menu_icon' => 'dashicons-format-status',
	  'show_in_rest' => true,
   	  'supports' => array('title')
	); 
    register_post_type( 'post-type', $args );
}

add_action( 'init', 'create_POSTTYPE' ); 

function create_testing_categories() {
	$labels = array(
	  'name' => _x( 'Testing Categories', 'taxonomy general name' ),
	  'singular_name' => _x( 'Testing Category', 'taxonomy singular name' ),
	  'search_items' =>  __( 'Search Testing Categories' ),
	  'all_items' => __( 'All Testing Categories' ), 
	  'parent_item' => __( 'Parent Testing Category' ),
	  'parent_item_colon' => __( 'Parent Testing Category:' ),
	  'edit_item' => __( 'Edit Testing Category' ), 
	  'update_item' => __( 'Update Testing Category' ),
	  'add_new_item' => __( 'Add New' ),
	  'new_item_name' => __( 'New Testing Category' ),
	  'menu_name' => __( 'Testing Categories' ),
	);    
	register_taxonomy('testing-category',array('post-type'), array(
	  'hierarchical' => true,
		'has_archive' => false,
		'publicly_queryable' => false,
	  'labels' => $labels,
	  'show_ui' => true,
	  'show_admin_column' => true,
	  'query_var' => true,
	  'rewrite' => array( 'slug' => 'testing-category' ),
	));	 
}
add_action( 'init', 'create_testing_categories', 0 );

?>