<?php get_header();
$style = get_field('hero_style');
$primarystyle = get_field('hero_primary_cta_type');
$secondarystyle = get_field('hero_secondary_cta_type'); ?>

<div class="wrapper">
    <div class="container breadcrumbWrapper">
        <?php
            if ( function_exists('yoast_breadcrumb') ) {
            yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
            }
        ?>
    </div>
	<?php include 'inc-hero.php'; ?>
	<?php the_content(); ?>
</div>

<?php get_footer(); ?>