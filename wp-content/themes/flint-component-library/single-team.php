<?php get_header();
$first = get_field('first_name');
$last = get_field('last_name');
$headshot = get_field('headshot');
$title = get_field('title');
$bio = get_field('bio');
$link = get_field('linkedin_url'); ?>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <div class="image"><img src="<?php echo esc_url($headshot['url']); ?>" alt="<?php echo esc_attr($headshot['alt']); ?>" /></div>
            <div class="title"><h1><?php echo $first . ' ' . $last; ?></h1><span><?php echo $title; ?></span></div>
        </div>

        <?php if($bio) { echo $bio; } ?>

        <?php if($link) { echo '<div class="cta"><a href="' . $link . '" target="_blank" class="btn primary red"><span><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
  <path id="linkedin-square" d="M20,0H0V20H20ZM8.333,13.333H6.667v-5H8.333ZM7.5,7.583a.917.917,0,1,1,.917-.917A.925.925,0,0,1,7.5,7.583Zm6.667,5.75H12.5V10.917c0-1.583-1.667-1.417-1.667,0v2.417H9.167v-5h1.667V9.25c.75-1.333,3.333-1.417,3.333,1.25Z" fill="#fff"/>
</svg> Connect with ' . $first . ' on LinkedIn</span></a></div>'; } ?>
    </div>
</div>
<?php get_footer(); ?>