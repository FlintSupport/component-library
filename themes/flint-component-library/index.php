<?php get_header();?>

<div class="wrapper">
    <div class="container">
        <?php
			if ( function_exists('yoast_breadcrumb') ) {
			yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>

        <div class="header">
            <h1>News Articles</h1>

            <?php get_search_form(); ?>
        </div>

        <?php
            $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
            $args = array(
                'post_type' => 'post',
                'status'  => 'publish', 
                'posts_per_page' => '8',
                'paged' => $paged,
            );                                              
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) : ?>

                <div class="posts">
                    <?php while ( $the_query->have_posts() ) :
                        $the_query->the_post();
                        $postID = get_the_id();
                    ?>
                        <a class="post<?php if( has_post_thumbnail() ) { echo ' hasthumb'; } ?>" href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) {
                                $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
                                if ( ! empty( $large_image_url[0] ) ) {
                                    echo '<div class="thumbnail">' . get_the_post_thumbnail( $post->ID, 'large' ) . '</div>';
                                }
                            } ?>
                            <div class="content">
                                <h3><?php the_title(); ?> - <?php the_date(); ?></h3>
                                <?php the_excerpt(); ?>
                                <div class="readMore">Read More</div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
                
                <?php the_posts_pagination( array(
                    'mid_size' => 1,
                    'prev_text' => __( 'Previous', 'textdomain' ),
                    'next_text' => __( 'Next', 'textdomain' ),
                ) ); ?>
            </div>
        <?php endif; ?>
    <?php $page_id = get_option( 'page_for_posts' );
        $page_data = get_page( $page_id ); 
        echo apply_filters('the_content', $page_data->post_content);
    ?>
</div>

<?php get_footer(); ?>