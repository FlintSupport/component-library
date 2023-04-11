<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php is_front_page() ? bloginfo('description') : wp_title(''); ?></title>	 
	<link rel="shortcut icon" type="image/x-icon" href="<?php echo get_template_directory_uri(); ?>/img/favicon.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&family=Source+Sans+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
	<?php wp_head(); ?>
</head>
<?php
	if (has_block('acf/accordion' || 'acf/tabs')) {
		$i = 1;
	}
?>
<body <?php echo body_class($addClass); ?> data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>">
<?php if(get_field('alert_bar_content', 'options')) : ?>
	<div id="alert">
		<div class="container">
			<img src="<?php echo get_template_directory_uri(); ?>/img/alert.svg" alt="" >
			<div class="copy">
				<?php the_field('alert_bar_content', 'options'); ?>
			</div>
			<img id="noticeClose" src="<?php echo get_template_directory_uri(); ?>/img/close.svg" alt="Close Notice" >
		</div>
	</div>
<?php endif; ?>
<header id="header">
	<nav class="top">
		<div class="container">
			<div class="left"><a href="tel:<?php the_field('primary_contact_number', 'options'); ?>" title="Call Flint Group"><?php the_field('primary_contact_number', 'options'); ?></a></div>
			<?php wp_nav_menu( array( 'theme_location' => 'top-menu' ) ); ?>
		</div>
	</nav>
	<nav class="main">
		<div class="container">
			<div id="mobile-toggle"><a href="javascript:void(0);" class="mobileMenu"><span id="menuOpen" class="active"><img src="<?php echo get_template_directory_uri(); ?>/img/hamburger.svg" alt="Open Menu" ></span><span id="menuClose"><img src="<?php echo get_template_directory_uri(); ?>/img/close.svg" alt="Close Menu" ></span></a></div>
			<div id="mobile-menu">
				<?php get_search_form(); ?>
				<ul id="mobile-menu-links">
					<?php
					wp_nav_menu( array(
						'menu' => 'main-menu-two',
						'menu_id' => '',
						'container' => '',
						'items_wrap'     => '%3$s'
					) );
					?>
				</ul>
				<?php if(get_field('customer_service_hours', 'options')) { echo '<div class="hours"><h4>Customer Service Hours</h4>'; the_field('customer_service_hours', 'options'); echo '</div>'; } ?>
				<?php if(get_field('routing_number', 'options')) { echo '<div class="routing"><h4>Routing Number</h4><p>'; the_field('routing_number', 'options'); echo '</p></div>'; } ?>
			</div>
			<div class="logo">
				<a href="<?php echo home_url(); ?>">
					<img src="<?php echo get_template_directory_uri(); ?>/img/logo.svg" alt="First National Bank of Bemidji Logo" width="203px" height="42px">
				</a>
			</div>
			<div id="main-menu">
				<?php wp_nav_menu( array( 'theme_location' => 'main-menu-two' ) ); ?>
				<div class="search">
					<div id="searchform"><?php get_search_form(); ?></div>
					<a href="javascript:void(0);" id="searchButton"><img src="<?php echo get_template_directory_uri(); ?>/img/search.svg" alt="Search"></a>
				</div>
			</div>
		</div>
	</nav>
</header>