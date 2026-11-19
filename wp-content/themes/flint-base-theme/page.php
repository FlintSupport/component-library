<?php get_header();
$style = get_field('hero_style');
$primarystyle = get_field('hero_primary_cta_type');
$secondarystyle = get_field('hero_secondary_cta_type'); ?>

<main id="main-content" class="wrapper" tabindex="-1">
    <div class="container breadcrumbWrapper">
        <?php
            if ( function_exists('yoast_breadcrumb') ) {
            yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
            }
        ?>
    </div>
	<?php the_content(); ?>
</main>

<?php get_footer(); ?>