<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Image Gallery Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$intro = get_field('intro_content');
$layout = get_field('layout');
$captions = get_field('captions');
$images = get_field('images');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="gallery <?php echo $background . ' ' . $size; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; }?>">
    <div class="container">
        <?php if($intro) { echo $intro; } ?>
        <?php if( $images ): ?>
            <div class="images <?php echo $layout; ?>">
                <?php foreach( $images as $image ): ?>
                    <div class="image">
                        <img src="<?php echo esc_url($image['sizes']['large']); ?>" width="<?php echo esc_html($image['sizes']['large-width']); ?>" height="<?php echo esc_html($image['sizes']['large-height']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
                        <?php if($captions) { echo '<figcaption>' . esc_html($image['caption']) . '</figcaption>'; } ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>