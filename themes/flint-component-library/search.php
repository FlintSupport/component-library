<?php get_header(); ?>

<div class="wrapper">
	<div class="container">
		<?php
			if(isset($_GET['search-type'])) {
				$searchtype = $_GET['search-type'];
				if($searchtype == 'post') {
					$args = array(
						'post_type'   => array('post'),
						'post_status' => 'publish',
						);
					$loop = new WP_Query( $args );
					if ( $loop -> have_posts() ) {
						_e("<h1>News Search Results for: ".get_query_var('s')."</h1>");
						echo('<div class="posts">');
							while ( $loop -> have_posts() ) {
								$loop -> the_post();
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
											<h3><?php the_title(); ?></h3>
											<?php the_excerpt(); ?>
											<div class="readMore">Read More</div>
										</div>
									</a>
								<?php
							}
						}else{
					?>
						<h1>Nothing Found</h1>
						<div class="alert alert-info">
						<p>Sorry, but nothing matched your search criteria. Please try again with some different keywords.</p>
						</div>
					<?php echo '</div>';
					}
				} elseif($searchtype == 'event') {
					$args = array(
						'post_type'   => array('event'),
						'post_status' => 'publish',
						);
					$loop = new WP_Query( $args );
					if ( $loop -> have_posts() ) {
						_e("<h1>News Search Results for: ".get_query_var('s')."</h1>");
						echo('<div class="posts">');
							while ( $loop -> have_posts() ) {
								$loop -> the_post();
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
											<h3><?php the_title(); ?></h3>
											<div class="date">
												<?php the_field('event_date', $post); ?><br>
												<?php the_field('event_start_time', $post); ?> - <?php the_field('event_end_time', $post); ?>
											</div>
											<?php the_excerpt(); ?>
											<div class="readMore">Read More</div>
										</div>
									</a>
								<?php
							}
						}else{
					?>
						<h1>Nothing Found</h1>
						<div class="alert alert-info">
						<p>Sorry, but nothing matched your search criteria. Please try again with some different keywords.</p>
						</div>
					<?php echo '</div>';
					}
				} elseif($searchtype == 'all') {
					if ( have_posts() ) {
						_e("<h1>Search Results for: ".get_query_var('s')."</h1>");
						echo('<div class="posts">');
							while ( have_posts() ) {
								the_post();
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
												<h3><?php the_title(); ?></h3>
												<?php the_excerpt(); ?>
												<div class="readMore">Read More</div>
											</div>
										</a>
									<?php
							}
						}else{
					?>
						<h1>Nothing Found</h1>
						<div class="alert alert-info">
						<p>Sorry, but nothing matched your search criteria. Please try again with some different keywords.</p>
						</div>
					<?php echo '</div>';
					}
				}
			}
		?>
	</div>
</div>
<?php get_footer(); ?>