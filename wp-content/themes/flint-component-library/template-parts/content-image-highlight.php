<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Image Highlight Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$image = get_field('image');
$headline = get_field('headline');
$content = get_field('content');
$link = get_field('link');
$intro = get_field('intro_content');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="image-highlight<?php if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($intro) { echo '<div class="intro">' . $intro . '</div>'; } ?>
        <a class="post<?php if($image) { echo ' hasthumb'; } ?>" href="<?php echo esc_url($link['url']); ?>">
            <?php if ($image) {
                echo '<div class="thumbnail"><img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '"></div>';
            } ?>
            <div class="content">
                <h3><?php echo $headline; ?></h3>
                <p><?php echo $content; ?></p>
                <div class="readMore"><?php echo esc_html($link['title']); ?></div>
            </div>
        </a>
    </div>
</section>