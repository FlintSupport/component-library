<?php
/*
Plugin Name: (ƒ) FLINT - Custom Post: Team Members
Plugin URI: https://flint-group.com
Description: Declares a plugin that will create a custom post type called "Team Members" and add search functionality for the theme.
Version: 1.0
Author URI: https://flint-group.com
*/

function create_team() {
    $labels = array(
		'name' => _x('Team Members', 'post type general name'),
		'singular_name' => _x('Team Members', 'post type singular name'),
		'add_new' => _x('Add New', 'Team Member'),
		'add_new_item' => __('Add New Team Member'),
		'edit_item' => __('Edit Team Member'),
		'new_item' => __('Team Member'),
		'all_items' => __('All Team Members'),
		'view_item' => __('View Team Members'),
		'search_items' => __('Search Team Members'),
		'not_found' =>  __('No Team Members found'),
		'not_found_in_trash' => __('No Team Members found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Team Members'
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => false,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'capability_type' => 'post',
		'has_archive' => false, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title' ),
		'rewrite' => array('slug' => 'team', 'with_front' => false),
		'menu_icon' => 'dashicons-businessperson',
		'show_in_rest' => true,
		'taxonomies' => array('team-category', 'location'),
	); 
    register_post_type( 'team', $args );
}

add_action( 'init', 'create_team' );

function create_team_category_taxonomy() {
	$labels = array(
	  'name' => _x( 'Team Categories', 'taxonomy general name' ),
	  'singular_name' => _x( 'Team Category', 'taxonomy singular name' ),
	  'search_items' =>  __( 'Search Team Categories' ),
	  'all_items' => __( 'All Team Categories' ), 
	  'parent_item' => __( 'Parent Team Category' ),
	  'parent_item_colon' => __( 'Parent Team Category:' ),
	  'edit_item' => __( 'Edit Team Category' ), 
	  'update_item' => __( 'Update Team Category' ),
	  'add_new_item' => __( 'Add New' ),
	  'new_item_name' => __( 'New Team Category' ),
	  'menu_name' => __( 'Team Categories' ),
	);    
	register_taxonomy('team-category',array('team'), array(
	  'hierarchical' => true,
	  'has_archive' => false,
	  'publicly_queryable' => false,
	  'labels' => $labels,
	  'show_ui' => true,
	  'show_admin_column' => true,
	  'query_var' => true,
	  'rewrite' => array( 'slug' => 'team-category' ),
	  'show_in_rest' => true,
	));	 
}
add_action( 'init', 'create_team_category_taxonomy', 0 );

function create_location_taxonomy() {
	$labels = array(
	  'name' => _x( 'Locations', 'taxonomy general name' ),
	  'singular_name' => _x( 'Location', 'taxonomy singular name' ),
	  'search_items' =>  __( 'Search Locations' ),
	  'all_items' => __( 'All Locations' ), 
	  'parent_item' => __( 'Parent Location' ),
	  'parent_item_colon' => __( 'Parent Location:' ),
	  'edit_item' => __( 'Edit Location' ), 
	  'update_item' => __( 'Update Location' ),
	  'add_new_item' => __( 'Add New' ),
	  'new_item_name' => __( 'New Location' ),
	  'menu_name' => __( 'Locations' ),
	);    
	register_taxonomy('location',array('team'), array(
	  'hierarchical' => true,
	  'labels' => $labels,
	  'show_ui' => true,
	  'show_admin_column' => true,
	  'query_var' => true,
	  'rewrite' => array( 'slug' => 'location' ),
	  'show_in_rest' => true,
	));	 
}
add_action( 'init', 'create_location_taxonomy', 0 );



//TEAM MEMBER SEARCH
add_action( 'wp_footer', 'ajax_fetch' );
function ajax_fetch() {
?>
<script type="text/javascript">
function fetch(){
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'post',
        data: {
            action: 'data_fetch',
            location: jQuery('#locationTerm').val(),
            category: jQuery('#categoryTerm').val(),
            },
            success: function(data) {
                jQuery('#datafetch').html( data );
            }
        }
    );
}
</script>

<?php
}
add_action('wp_ajax_data_fetch' , 'data_fetch');
add_action('wp_ajax_nopriv_data_fetch','data_fetch');
function data_fetch(){
    remove_all_filters('posts_orderby');
	$location = esc_attr( $_POST['location'] );
	$category = esc_attr( $_POST['category'] );

	if($location && $category) {
		$the_query = new WP_Query( 
			array( 
				'posts_per_page' => -1, 
				'taxonomy' => 'team-category',
				'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'location',
						'field'    => 'slug',
						'terms'    => $location,
					),
					array(
						'taxonomy' => 'team-category',
						'field'    => 'slug',
						'terms'    => $category,
					),
				),
				'sort_column' => 'menu_order',
				'order' => 'DESC',
			) 
		);
	}
	else {
		$the_query = new WP_Query( 
			array( 
				'posts_per_page' => -1, 
				'taxonomy' => 'team-category',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'location',
						'field'    => 'slug',
						'terms'    => $location,
					),
					array(
						'taxonomy' => 'team-category',
						'field'    => 'slug',
						'terms'    => $category,
					),
				),
				'sort_column' => 'menu_order',
				'order' => 'DESC',
			) 
		);
	}

    if( $the_query->have_posts() ) :
        echo '<div class="teamMembers">';
        while( $the_query->have_posts() ): $the_query->the_post();
			$post_id = get_the_ID();
			$first = get_field('first_name', $post_id);
			$last = get_field('last_name', $post_id);
			$headshot = get_field('headshot', $post_id);
			$title = get_field('job_title', $post_id);
			$phone = get_field('phone_number', $post_id);?>
				
				<div class="member">
					<div class="overlay"></div>
					<img src="<?php echo esc_url($headshot['url']); ?>" alt="<?php echo $first . ' ' . $last . '\'s headshot'; ?>">
					<div class="content">
						<h3><?php echo $first . ' ' . $last; ?></h3>
						<span><?php echo $title; ?></span>
						<a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
					</div>
				</div>

			<?php
        endwhile;
        echo '</div>';
        wp_reset_postdata();
    else :

		if(!$location && !$category) {
			echo '<div class="teamMembers">';
        	remove_all_filters('posts_orderby');
			$args = array(  
				'post_type' => 'team',
				'post_status' => 'publish',
				'posts_per_page' => -1, 
				'sort_column' => 'menu_order',
				'order'   => 'DESC',
			);

			$teamloop = new WP_Query( $args ); 
			while ( $teamloop->have_posts() ) : $teamloop->the_post();
				$post_id = get_the_ID();
				$first = get_field('first_name', $post_id);
				$last = get_field('last_name', $post_id);
				$headshot = get_field('headshot', $post_id);
				$title = get_field('job_title', $post_id);
				$phone = get_field('phone_number', $post_id);
			?>

				<div class="member">
					<div class="overlay"></div>
					<img src="<?php echo esc_url($headshot['url']); ?>" alt="<?php echo $first . ' ' . $last . '\'s headshot'; ?>">
					<div class="content">
						<h3><?php echo $first . ' ' . $last; ?></h3>
						<span><?php echo $title; ?></span>
						<a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
					</div>
				</div>

			<? endwhile;
			wp_reset_postdata();
			echo '</div>';
		}
		else {
			echo '<p class="error">Sorry, no team members were found! Try refining your search.</p>';
		}

    endif;
    die();
}

?>