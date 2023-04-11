<?php
if(is_admin()): ?>
    <div class="axonify-block">
        <div class="editor-note">
            <h3>Slider Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$style = get_field('slider_style');
$headline = get_field('main_headline');
$intro = get_field('intro_copy');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="slider <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($headline) { echo $headline; } if($intro) { echo $intro; }?>

        <div class="slider-<?php echo $style; if($headline || $intro) {echo ' padding';} ?>">
            <?php
                if( have_rows('slides') ):
                    while( have_rows('slides') ) : the_row();
                        $image = get_sub_field('slide_image');
                        $copy = get_sub_field('slide_copy');
                        $button = get_sub_field('slide_button_link');
                    ?>

                    <div class="slide">
                        <?php if(($style === 'multi') && $button) {echo '<a href="' . esc_url($button['url']) . '" target="' . esc_attr($button['target']) . '">';} ?>
                        <?php if($image) {
                            echo '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" />';
                        } 
                        if($title || $button) {echo '<div class="content">';}
                        if($copy) {echo '<div class="copy">' . $copy . '</div>';}
                        if(($style != 'multi') && $button) {echo '<a class="button primary" href="' . esc_url($button['url']) . '" target="' . esc_attr($button['target']) . '">' . esc_html($button['title']) . '</a>';}
                        if($title || $button) {echo '</div>';}
                        ?>
                        <?php if(($style === 'multi') && $button) {echo '</a>';} ?>
                    </div>

                    <?php endwhile;
            endif; ?>
        </div>
    </div>
</section>