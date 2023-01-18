<?php
/* Template Name: Team Template
 * SEARCH FUNCTIONS AND TEMPLATE ARE LOCATED WITHIN PLUGIN */
?>

<?php get_header();
$style = get_field('hero_style');
?>

<div class="wrapper">
    <div class="container breadcrumbWrapper">
        <?php
			if ( function_exists('yoast_breadcrumb') ) {
			yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>
    </div>
	<?php the_content(); ?>

    <section class="team">
        <div class="container">
            <?php
                $catterms = get_terms( 'team-category', array(
                    'hide_empty' => false,
                ) );
                $locterms = get_terms( 'location', array(
                    'hide_empty' => false,
                ) );
            ?>

                <div class="search">
                    <form method="get" action="javascript:fetch();" id="teamSearch">
                    <div class="categorySearch">
                            <strong>Select a Category</strong>
                            <select name="category" id="categoryTerm" onchange="this.form.submit()">
                                <option value="" selected>Select</option>
                                <?php if( $catterms ): foreach( $catterms as $term ):?>
                                    <option value="<?php echo esc_html( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="locationSearch">
                            <strong>Select a Location</strong>
                            <select name="location" id="locationTerm" onchange="this.form.submit()">
                                <option value="" selected>Select</option>
                                <?php if( $locterms ): foreach( $locterms as $term ):?>
                                    <option value="<?php echo esc_html( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="results" id="datafetch">
                    <div class="teamMembers">
                        <?php
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
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>