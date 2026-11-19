<header id="header3">
	<nav class="top" aria-label="Utility navigation">
		<div class="container">
			<div class="left"><a href="tel:<?php echo get_field('primary_contact_number', 'options'); ?>" title="Call Flint Group"><?php echo get_field('primary_contact_number', 'options'); ?></a></div>
			<?php wp_nav_menu( array( 'theme_location' => 'utility-menu' ) ); ?>
		</div>
	</nav>	
	<nav class="main" aria-label="Primary navigation">
		<div class="container">
			<div id="mobile-toggle"><button type="button" class="mobileMenu" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu"><span id="menuOpen" class="active"><img src="<?php echo get_template_directory_uri(); ?>/img/hamburger.svg" alt="" aria-hidden="true"></span><span id="menuClose"><img src="<?php echo get_template_directory_uri(); ?>/img/close.svg" alt="" aria-hidden="true"></span></button></div>
			<div id="mobile-menu">
				<?php get_search_form(); ?>
				<ul id="mobile-menu-links">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'main-menu-three',
						'menu_id' => '',
						'container' => '',
						'items_wrap'     => '%3$s'
					) );
					?>
				</ul>
				<?php if(get_field('customer_service_hours', 'options')) { echo '<div class="hours"><h4>Customer Service Hours</h4>'; echo get_field('customer_service_hours', 'options'); echo '</div>'; } ?>
				<?php if(get_field('routing_number', 'options')) { echo '<div class="routing"><h4>Routing Number</h4><p>'; echo get_field('routing_number', 'options'); echo '</p></div>'; } ?>
			</div>
			<div class="logo">
				<a href="<?php echo home_url(); ?>">
					<img src="<?php echo get_template_directory_uri(); ?>/img/logo.svg" alt="First National Bank of Bemidji Logo" width="203px" height="42px">
				</a>
			</div>
			<div id="main-menu">
				<?php wp_nav_menu( array( 'theme_location' => 'main-menu-three' ) ); ?>
				<div class="search">
					<div id="searchform"><?php get_search_form(); ?></div>
					<button type="button" id="searchButton" aria-expanded="false" aria-controls="searchform" aria-label="Toggle search"><img src="<?php echo get_template_directory_uri(); ?>/img/search.svg" alt="" aria-hidden="true"></button>
				</div>
			</div>
		</div>
	</nav>
</header>