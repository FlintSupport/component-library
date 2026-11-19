<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>CTA Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$content = get_field('content');
$image = get_field('image');
$primaryBtn = get_field('primary_button_link');
$secondaryBtn = get_field('secondary_button_link');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="cta <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <div class="left<?php if(!$image) {echo ' noimage';} ?>">
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
        <?php if($image) : ?>
            <div class="right">
                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
            </div>
        <?php endif; ?>
    </div>
</section>