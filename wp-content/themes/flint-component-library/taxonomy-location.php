<?php get_header();
    $page = get_queried_object();
?>

<div class="wrapper">
    <div class="container">
        <?php
			if ( function_exists('yoast_breadcrumb') ) {
			yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>
    </div>

    <section class="hero image">
        <div class="container">
            <div class="left">
                <div class="content">
                    <h1>First National Bank Bemidji - <?php echo $page->name; ?></h1>
                    <?php echo '<a class="button primary" href="tel:' . get_field('primary_phone_number', $page) . '"><span>' . get_field('primary_phone_number', $page) . '</span></a>'; ?>
                </div>
            </div>
            <div class="right">
                <div class="image"><img src="<?php echo esc_url(get_field('hero_image', $page)['url']); ?>" alt="<?php echo esc_attr(get_field('hero_image', $page)['alt']); ?>"></div>
            </div>
        </div>
	</section>

    <section class="details">
        <div class="container">
            <div class="column">
                <h3>Location</h3>
                <?php echo get_field('address', $page); ?>
                <a href="<?php echo get_field('directions_link', $page); ?>" target="_blank">Get Directions</a>
            </div>
            <div class="column">
                <h3>Lobby Hours</h3>
                <?php echo get_field('hours', $page); ?>
            </div>
            <div class="column">
                <h3>Drive-Up Hours</h3>
                <?php echo get_field('drive_up', $page); ?>
            </div>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <div class="left">
                <h2><?php echo $page->name; ?> Services</h2>
                <?php echo get_field('services_intro', $page); ?>
                <?php echo '<a class="button primary desktop" href="tel:' . get_field('primary_phone_number', $page) . '"><span>' . get_field('primary_phone_number', $page) . '</span></a>'; ?>
            </div>
            <div class="right">
                <?php if(have_rows('main_services', $page)) : ?>
                    <ul class="checklist" style="columns: 2;">
                        <?php while(have_rows('main_services', $page)) : the_row(); ?>

                        <li><?php the_sub_field('service', $page); ?></li>

                        <?php endwhile; ?>
                    </ul>

                    <?php echo '<a class="button primary mobile" href="tel:' . get_field('primary_phone_number', $page) . '"><span>' . get_field('primary_phone_number', $page) . '</span></a>'; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="split">
        <div class="container">
            <div class="left copy graybg">
                <div class="content large">
                    <?php echo '<h2>' . get_field('headline', $page) . '</h2>';
                    if(get_field('cta_content')) { echo get_field('cta_content', $page); }?>
                    <a class="button primary" href="<?php echo esc_url(get_field('cta_button', $page)['url']); ?>" target="<?php echo esc_attr(get_field('cta_button', $page)['target']); ?>"><span><?php echo esc_html(get_field('cta_button', $page)['title']); ?></span></a>
                </div>
            </div>
            <div class="right image graybg"> 
                <div class="content">
                    <img src="<?php echo esc_url(get_field('cta_image', $page)['url']); ?>" alt="<?php echo esc_attr(get_field('cta_image', $page)['alt']); ?>">
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>