<?php get_header();
?>

<main id="main-content" class="wrapper" tabindex="-1">
    <div class="container breadcrumbWrapper">
        <?php
			if ( function_exists('yoast_breadcrumb') ) {
			yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>
    </div>
	<div class="header">
		<div class="container">
            <h1>Page not found</h1>
			<p>The page you're looking for could not be found.</p>
		</div>
    </div>
</main>

<?php get_footer(); ?>