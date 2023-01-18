<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Hero Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$headline = get_field('main_headline');
$content = get_field('intro_content');
$buttonone = get_field('hero_button_one');
$buttontwo = get_field('hero_button_two');
$image = get_field('side_image');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="hero <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; }?>">
    <div class="container">
        <?php if($image) { echo '<div class="left">'; } ?>
            <h1><?php echo $headline;?></h1>
            <?php if($content) { echo $content; }?>
            <?php if($buttonone || $buttontwo) :?>
                <div class="buttons">
                    <?php if($buttonone) :?>
                        <a class="button primary" href="<?php echo esc_url($buttonone['url']); ?>" target="<?php echo esc_attr($buttonone['target']); ?>"><span><?php echo esc_html($buttonone['title']); ?></span></a>
                    <?php endif; ?>
                    <?php if($buttontwo) :?>
                        <a class="button secondary" href="<?php echo esc_url($buttontwo['url']); ?>" target="<?php echo esc_attr($buttontwo['target']); ?>"><span><?php echo esc_html($buttontwo['title']); ?></span></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php if($image) : ?>
            </div>
            <div class="right">
                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
            </div>
        <?php endif; ?>
    </div>
</section>