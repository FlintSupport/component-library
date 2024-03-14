<footer id="footer">
	<div class="container">
		<div class="top">
			<div class="column">
				<div class="logo">
					<a href="<?php echo home_url(); ?>">
						<img src="<?php echo esc_url(get_field('logo', 'options')['url']); ?>" alt="Flint Group Logo" width="182px" height="53px">
					</a>
				</div>
			</div>
			<div class="column">
				<h3>Title Here</h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer-menu-one' ) ); ?>
			</div>
			<div class="column">
				<h3>Title Here</h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer-menu-two' ) ); ?>
			</div>
			<div class="column">
				<div class="social">
					<a href="#" class="socialLink" target="_blank" title="Flint Group on Instagram"><svg id="iconmonstr-instagram-14" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path id="iconmonstr-instagram-14-2" data-name="iconmonstr-instagram-14" d="M24.715,10.5c-1.23-.057-1.6-.067-4.715-.067s-3.483.012-4.713.067c-3.165.145-4.638,1.643-4.783,4.783-.055,1.23-.068,1.6-.068,4.713s.013,3.483.068,4.715c.145,3.132,1.612,4.638,4.783,4.783,1.228.055,1.6.068,4.713.068s3.485-.012,4.715-.068c3.165-.143,4.637-1.647,4.783-4.783.055-1.23.067-1.6.067-4.715s-.012-3.483-.067-4.713c-.147-3.138-1.622-4.638-4.783-4.783ZM20,25.992A5.991,5.991,0,1,1,25.992,20,5.992,5.992,0,0,1,20,25.992Zm6.228-10.818a1.4,1.4,0,1,1,1.4-1.4A1.4,1.4,0,0,1,26.228,15.173ZM23.888,20A3.888,3.888,0,1,1,20,16.112,3.888,3.888,0,0,1,23.888,20ZM20,0A20,20,0,1,0,40,20,20,20,0,0,0,20,0ZM31.6,24.81C31.4,29.052,29.043,31.4,24.812,31.6c-1.245.057-1.643.07-4.812.07s-3.565-.013-4.81-.07C10.95,31.4,8.6,29.048,8.4,24.81c-.057-1.243-.07-1.642-.07-4.81s.013-3.565.07-4.81c.2-4.24,2.548-6.592,6.787-6.785,1.245-.058,1.642-.072,4.81-.072s3.567.013,4.812.072c4.242.195,6.595,2.553,6.785,6.785.057,1.245.07,1.642.07,4.81S31.653,23.567,31.6,24.81Z" fill="#000000"/></svg></a>
					<a href="#" class="socialLink" target="_blank" title="Flint Group on Facebook"><svg id="iconmonstr-facebook-4" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path id="iconmonstr-facebook-4-2" data-name="iconmonstr-facebook-4" d="M20,0A20,20,0,1,0,40,20,20,20,0,0,0,20,0Zm5,13.333H22.75c-.9,0-1.083.368-1.083,1.3v2.037H25L24.652,20H21.667V31.667h-5V20H13.333V16.667h3.333V12.82c0-2.948,1.552-4.487,5.048-4.487H25Z" fill="#000000"/></svg></a>
					<a href="#" class="socialLink" target="_blank" title="Flint Group on LinkedIn"><svg id="iconmonstr-linkedin-4" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path id="iconmonstr-linkedin-4-2" data-name="iconmonstr-linkedin-4" d="M20,0A20,20,0,1,0,40,20,20,20,0,0,0,20,0ZM16.667,26.667H13.333v-10h3.333ZM15,15.182a1.848,1.848,0,1,1,1.833-1.848A1.841,1.841,0,0,1,15,15.182ZM28.333,26.667H25V21.9c0-3.135-3.337-2.87-3.337,0v4.768H18.333v-10h3.333v1.822c1.453-2.693,6.667-2.893,6.667,2.58Z" fill="#000000"/></svg></a>
					<a href="#" class="socialLink" target="_blank" title="Flint Group on Twitter"><svg id="iconmonstr-twitter-4" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path id="iconmonstr-twitter-4-2" data-name="iconmonstr-twitter-4" d="M20,0A20,20,0,1,0,40,20,20,20,0,0,0,20,0ZM30.11,16.075A13.613,13.613,0,0,1,9.167,28.163a9.631,9.631,0,0,0,7.087-1.982,4.8,4.8,0,0,1-4.473-3.325,4.8,4.8,0,0,0,2.163-.082A4.794,4.794,0,0,1,10.1,18.02a4.776,4.776,0,0,0,2.168.6,4.794,4.794,0,0,1-1.482-6.392,13.588,13.588,0,0,0,9.867,5,4.791,4.791,0,0,1,8.158-4.367,9.549,9.549,0,0,0,3.04-1.162,4.807,4.807,0,0,1-2.1,2.648,9.55,9.55,0,0,0,2.748-.755A9.609,9.609,0,0,1,30.11,16.075Z" fill="#000000"/></svg></a>
					<a href="#" class="socialLink" target="_blank" title="Flint Group on YouTube"><svg id="iconmonstr-youtube-9" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path id="iconmonstr-youtube-9-2" data-name="iconmonstr-youtube-9" d="M20,0A20,20,0,1,0,40,20,20,20,0,0,0,20,0Zm7.4,28.153c-3.5.24-11.307.24-14.805,0C8.8,27.893,8.362,26.037,8.333,20c.028-6.048.475-7.893,4.263-8.153,3.5-.24,11.3-.24,14.805,0,3.795.26,4.235,2.117,4.265,8.153C31.637,26.048,31.192,27.893,27.4,28.153ZM16.667,16.1l8.195,3.9L16.667,23.9Z" fill="#000000"/></svg></a>
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
