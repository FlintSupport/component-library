<?php
if(is_admin()): ?>
    <div class="axonify-block">
        <div class="editor-note">
            <h3>Video Carousel Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$intro = get_field('introduction');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="videoCarousel <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; }?>">
    <div class="container">
        <?php if($intro) { echo $intro; }?>

            <?php if( have_rows('videos') ): ?>
                <div class="video-slider">
                    <?php while( have_rows('videos') ) : the_row();
                        $image = get_sub_field('video_thumbnail');
                        $title = get_sub_field('video_title');
                        $id = preg_replace('/[^a-zA-Z0-9]+/i', '-', trim($title));
                    ?>

                    <div class="slide">
                        <a href="javascript:void(0)" class="slide" id="slide-<?php echo $id; ?>">
                            <div class="thumbnail">
                                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                                <svg class="play" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                    viewBox="0 0 24 24" style="enable-background:new 0 0 24 24;" xml:space="preserve">
                                    <rect y="0" class="st0" width="24" height="24"/>
                                    <g>
                                        <path class="st1" d="M12,2C6.5,2,2,6.5,2,12s4.5,10,10,10s10-4.5,10-10S17.5,2,12,2z M10,16.5v-9l6,4.5L10,16.5z M10,16.5v-9l6,4.5
                                            L10,16.5z"/>
                                    </g>
                                    <polygon class="st2" points="10,16.5 10,7.5 16,12 "/>
                                </svg>
                            </div>
                            <?php echo $title; ?>
                        </a>
                    </div>

                    <?php endwhile; ?>
                </div>
                <div class="popups">
                    <?php while( have_rows('videos') ) : the_row();
                        $embed = get_sub_field('embed_code');
                        $title = get_sub_field('video_title');
                        $id = preg_replace('/[^a-zA-Z0-9]+/i', '-', trim($title));
                    ?>

                    <div id="popup-<?php echo $id; ?>" class="popup">
                    <svg class="loading" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: none; display: block; shape-rendering: auto;" width="200px" height="200px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                        <circle cx="50" cy="50" fill="none" stroke="#ffffff" stroke-width="10" r="35" stroke-dasharray="164.93361431346415 56.97787143782138">
                        <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                        </circle>
                        <div class="content">
                            <div class="close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16.834" height="16.842" viewBox="0 0 16.834 16.842">
                            <g id="Icon_ionic-ios-close-circle-outline" data-name="Icon ionic-ios-close-circle-outline" transform="translate(-9.984 -0.41)">
                                <path id="Path_247" data-name="Path 247" d="M28.7,26.325l-5.536-5.536L28.7,15.253a1.679,1.679,0,0,0-2.374-2.374l-5.536,5.536-5.536-5.536a1.679,1.679,0,1,0-2.374,2.374l5.536,5.536-5.536,5.536a1.623,1.623,0,0,0,0,2.374,1.668,1.668,0,0,0,2.374,0l5.536-5.536L26.323,28.7a1.687,1.687,0,0,0,2.374,0A1.668,1.668,0,0,0,28.7,26.325Z" transform="translate(-2.375 -11.944)" fill="#ffffff"/>
                            </g>
                            </svg>

                            </div>
                            <?php echo $embed; ?>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        <?php if($primaryBtn || $secondaryBtn) :?>
            <div class="buttons">
                <?php if($secondaryBtn) :?>
                    <a class="btn secondary <?php echo $secondaryBtnColor; if($secondaryDisabled) {echo ' disabled';} ?>" <?php if(!$secondaryDisabled) : ?>href="<?php echo esc_url($secondaryBtn['url']); ?>"<?php endif;?> target="<?php echo esc_attr($secondaryBtn['target']); ?>"><?php echo esc_html($secondaryBtn['title']); ?></a>
                <?php endif; ?>
                <?php if($primaryBtn) :?>
                    <a class="btn primary <?php echo $primaryBtnColor; if($primaryDisabled) {echo ' disabled';} ?>" <?php if(!$secondaryDisabled) : ?>href="<?php echo esc_url($primaryBtn['url']); ?>"<?php endif; ?> target="<?php echo esc_attr($primaryBtn['target']); ?>"><?php echo esc_html($primaryBtn['title']); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>