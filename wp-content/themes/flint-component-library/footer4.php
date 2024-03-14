<footer id="footer">
	<div class="container">
		<div class="top">
			<div class="container">
				<div class="column">
					<address>(123) 456-4200 590<br>
					Main Street Small Town,<br>
					North Dakota 58078</address>
					<br><br>
					<img src="<?php echo get_template_directory_uri(); ?>/img/icon_fdic.png" alt="FDIC" /> <img src="<?php echo get_template_directory_uri(); ?>/img/icon_ehl.png" alt="EHL" />
				</div>

				<div class="column">
					<p>©<?php echo date('Y'); ?> Company Name</p> <?php wp_nav_menu( array( 'theme_location' => 'policy-menu' ) ); ?>
				</div>

				<div class="column">
					<?php if ( has_nav_menu( 'footer-menu-one' ) ) { wp_nav_menu( array( 'theme_location' => 'footer-menu-one' ) ); } ?>
				</div>
			</div>
		</div>
	</div>
	
	<div class="bottom">
		<div class="container">
			<p>©<?php echo date('Y'); ?> Company Name</p> <?php wp_nav_menu( array( 'theme_location' => 'policy-menu' ) ); ?>
			<div class="copy-disclaimer"><?php echo get_field('bottom_disclaimer', 'options'); ?></div>
		</div>
	</div>
<?php wp_footer(); ?>
</footer>
</body>
</html>
