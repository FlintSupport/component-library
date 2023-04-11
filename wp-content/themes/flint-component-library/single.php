<?php get_header();
 ?>
<div class="wrapper">
    <div class="container">
		<?php
			if ( function_exists('yoast_breadcrumb') ) {
			yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>

        <div class="mainContent">
			<div class="returnLink"><a href="<?php echo get_post_type_archive_link( 'post' ); ?>">Back To All News</a></div>
			<div class="image">
				<?php echo get_the_post_thumbnail( $page->ID, 'full' ); ?>
			</div>
			<div class="flex">
				<div class="addthis_inline_share_toolbox"></div>
				<div class="content">
					<div class="data">
						<h1><?php the_title(); ?></h1>
						<p><?php the_date(); ?></p>
					</div>
					<?php the_content(); ?>
				</div>
			</div>
        </div>
    </div>

	<?php
		$content = get_field('cta_content');
		$image = get_field('cta_image');
		$primaryBtn = get_field('primary_button_link');
		$secondaryBtn = get_field('secondary_button_link');

		if(get_field('add_cta')) :
	?>
		<section class="cta">
			<div class="container">
				<div class="left">
					<?php if($content) { echo $content; }?>

					<?php if($primaryBtn || $secondaryBtn) :?>
						<div class="buttons">
							<?php if($primaryBtn) :?>
								<a class="button primary" href="<?php echo esc_url($primaryBtn['url']); ?>" target="<?php echo esc_attr($primaryBtn['target']); ?>"><span><?php echo esc_html($primaryBtn['title']); ?></span></a>
							<?php endif; ?>
							<?php if($secondaryBtn) :?>
								<a class="button secondary" href="<?php echo esc_url($secondaryBtn['url']); ?>" target="<?php echo esc_attr($secondaryBtn['target']); ?>"><span><?php echo esc_html($secondaryBtn['title']); ?></span></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="right">
					<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
				</div>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php get_footer(); ?>