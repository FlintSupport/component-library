<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Content {
	private static $instance;
	public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() {
		add_action( 'init', array( $this, 'register_content' ), 5 );
		add_action( 'wp_ajax_flint_team_filter', array( $this, 'team_filter' ) );
		add_action( 'wp_ajax_nopriv_flint_team_filter', array( $this, 'team_filter' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'team_assets' ) );
	}


	public function team_assets() {
		if ( ! post_type_exists( 'team' ) ) { return; }
		wp_enqueue_script( 'flint-team-filter', FLINT_BASE_URL . 'assets/js/team.js', array( 'jquery' ), FLINT_BASE_VERSION, true );
		wp_localize_script( 'flint-team-filter', 'flintTeam', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'flint_team_filter' ) ) );
	}

	public function register_content() {
		$s = flint_base_get_settings();
		foreach ( (array) $s['custom_post_types'] as $row ) { $this->register_custom_post_type( $row ); }
		foreach ( (array) $s['custom_taxonomies'] as $row ) { $this->register_custom_taxonomy( $row ); }
	}

	private function labels( $plural, $singular ) {
		return array(
			'name' => $plural, 'singular_name' => $singular, 'menu_name' => $plural,
			'add_new' => 'Add New', 'add_new_item' => 'Add New ' . $singular, 'edit_item' => 'Edit ' . $singular,
			'new_item' => 'New ' . $singular, 'view_item' => 'View ' . $singular, 'search_items' => 'Search ' . $plural,
			'not_found' => 'No ' . strtolower( $plural ) . ' found', 'all_items' => 'All ' . $plural,
		);
	}

	private function register_team() {
		register_post_type( 'team', array(
			'labels' => $this->labels( 'Team Members', 'Team Member' ), 'public' => true, 'publicly_queryable' => false,
			'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => true, 'has_archive' => false, 'hierarchical' => false,
			'rewrite' => array( 'slug' => 'team', 'with_front' => false ), 'menu_icon' => 'dashicons-businessperson', 'supports' => array( 'title', 'page-attributes' ),
		) );
	}

	private function register_team_category() {
		register_taxonomy( 'team-category', array( 'team' ), array(
			'labels' => $this->labels( 'Team Categories', 'Team Category' ), 'hierarchical' => true, 'public' => false,
			'show_ui' => true, 'show_admin_column' => true, 'query_var' => true, 'rewrite' => array( 'slug' => 'team-category' ), 'show_in_rest' => true,
		) );
	}
	private function register_location() {
		register_taxonomy( 'location', array( 'team' ), array(
			'labels' => $this->labels( 'Locations', 'Location' ), 'hierarchical' => true, 'public' => true,
			'show_ui' => true, 'show_admin_column' => true, 'query_var' => true, 'rewrite' => array( 'slug' => 'location' ), 'show_in_rest' => true,
		) );
	}

	private function register_custom_post_type( $r ) {
		$key = sanitize_key( $r['key'] ?? '' ); if ( ! $key || post_type_exists( $key ) ) { return; }
		$supports = array( 'title' ); if ( ! empty( $r['editor'] ) ) $supports[] = 'editor'; if ( ! empty( $r['thumbnail'] ) ) $supports[] = 'thumbnail';
		register_post_type( $key, array(
			'labels' => $this->labels( $r['plural'], $r['singular'] ), 'public' => ! empty( $r['public'] ), 'show_ui' => true, 'show_in_rest' => true,
			'has_archive' => ! empty( $r['archive'] ), 'rewrite' => array( 'slug' => $r['slug'] ?: $key, 'with_front' => false ), 'menu_icon' => $r['icon'] ?: 'dashicons-admin-post', 'supports' => $supports,
		) );
	}
	private function register_custom_taxonomy( $r ) {
		$key = sanitize_key( $r['key'] ?? '' ); if ( ! $key || taxonomy_exists( $key ) ) { return; }
		$objects = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $r['object_types'] ?? '' ) ) ) ); if ( ! $objects ) return;
		register_taxonomy( $key, $objects, array(
			'labels' => $this->labels( $r['plural'], $r['singular'] ), 'hierarchical' => ! empty( $r['hierarchical'] ), 'public' => ! empty( $r['public'] ),
			'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => true, 'rewrite' => array( 'slug' => $r['slug'] ?: $key ),
		) );
	}

	public function team_filter() {
		check_ajax_referer( 'flint_team_filter', 'nonce' );
		$tax_query = array();
		foreach ( array( 'location' => 'location', 'category' => 'team-category' ) as $request => $taxonomy ) {
			$value = sanitize_title( wp_unslash( $_POST[ $request ] ?? '' ) );
			if ( $value && taxonomy_exists( $taxonomy ) ) { $tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $value ); }
		}
		if ( count( $tax_query ) > 1 ) { $tax_query['relation'] = 'AND'; }
		$q = new WP_Query( array( 'post_type' => 'team', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'tax_query' => $tax_query ?: array() ) );
		ob_start();
		if ( $q->have_posts() ) { echo '<div class="teamMembers">'; while ( $q->have_posts() ) { $q->the_post(); $this->team_card( get_the_ID() ); } echo '</div>'; }
		else { echo '<p class="error">Sorry, no team members were found. Try refining your search.</p>'; }
		wp_reset_postdata(); wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}
	private function team_card( $id ) {
		$first = function_exists('get_field') ? get_field('first_name', $id) : ''; $last = function_exists('get_field') ? get_field('last_name', $id) : '';
		$headshot = function_exists('get_field') ? get_field('headshot', $id) : null; $title = function_exists('get_field') ? get_field('job_title', $id) : ''; $phone = function_exists('get_field') ? get_field('phone_number', $id) : '';
		echo '<div class="member"><div class="overlay"></div>'; if ( ! empty( $headshot['url'] ) ) echo '<img src="' . esc_url($headshot['url']) . '" alt="' . esc_attr(trim($first.' '.$last) . ' headshot') . '">';
		echo '<div class="content"><h3>' . esc_html(trim($first.' '.$last)) . '</h3><span>' . esc_html($title) . '</span>'; if ($phone) echo '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/','',$phone)) . '">' . esc_html($phone) . '</a>'; echo '</div></div>';
	}
}
