<?php get_header();
?>

<div class="wrapper">
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
</div>

<?php get_footer(); ?>