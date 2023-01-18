<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Split Content Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$typeLeft = get_field('content_type_left');
$alignLeft = get_field('content_alignment_left');
$contentLeft = get_field('content_left');
$primaryBtnLeft = get_field('primary_button_link_left');
$secondaryBtnLeft = get_field('secondary_button_link_left');
$imageLeft = get_field('image_left');
$videoLeft = get_field('video_left');
$typeRight = get_field('content_type_right');
$alignRight = get_field('content_alignment_right');
$contentRight = get_field('content_right');
$primaryBtnRight = get_field('primary_button_link_right');
$secondaryBtnRight = get_field('secondary_button_link_right');
$imageRight = get_field('image_right');
$videoRight = get_field('video_right');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="split <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } if($spacingLeft || $spacingRight) { echo ' spacing'; } ?>">
    <div class="container">
        <div class="left <?php echo $typeLeft . ' ' . $alignLeft; ?>">
            <?php
                if($typeLeft === 'image') {
                    echo '<img src="' . esc_url($imageLeft['url']) . '" alt="' . esc_html($imageLeft['alt']) . '" />';
                }
                else if($typeLeft === 'video') {
                    echo $videoLeft;
                }
                else if($typeLeft === 'card') {
                    echo $contentLeft;
                }
                else {
                    echo $contentLeft;
                    if($primaryBtnLeft || $secondaryBtnLeft) :?>
                        <div class="buttons">
                            <?php if($primaryBtnLeft) :?>
                                <a class="button primary" href="<?php echo esc_url($primaryBtnLeft['url']); ?>" target="<?php echo esc_attr($primaryBtnLeft['target']); ?>"><span><?php echo esc_html($primaryBtnLeft['title']); ?></span></a>
                            <?php endif; ?>
                            <?php if($secondaryBtnLeft) :?>
                                <a class="button secondary" href="<?php echo esc_url($secondaryBtnLeft['url']); ?>" target="<?php echo esc_attr($secondaryBtnLeft['target']); ?>"><span><?php echo esc_html($secondaryBtnLeft['title']); ?></span></a>
                            <?php endif; ?>
                        </div>
                    <?php endif;
                }
            ?>
        </div>
        <div class="right <?php echo $typeRight . ' ' . $alignRight;?>">
            <?php
                if($typeRight === 'image') {
                    echo '<img src="' . esc_url($imageRight['url']) . '" alt="' . esc_html($imageRight['alt']) . '" />';
                }
                else if($typeRight === 'video') {
                    echo $videoRight;
                }
                else if($typeRight === 'card') {
                    echo $contentRight;
                }
                else {
                    echo $contentRight;
                    if($primaryBtnRight || $secondaryBtnRight) :?>
                        <div class="buttons">
                            <?php if($primaryBtnRight) :?>
                                <a class="button primary" href="<?php echo esc_url($primaryBtnRight['url']); ?>" target="<?php echo esc_attr($primaryBtnRight['target']); ?>"><span><?php echo esc_html($primaryBtnRight['title']); ?></span></a>
                            <?php endif; ?>
                            <?php if($secondaryBtnRight) :?>
                                <a class="button secondary" href="<?php echo esc_url($secondaryBtnRight['url']); ?>" target="<?php echo esc_attr($secondaryBtnRight['target']); ?>"><span><?php echo esc_html($secondaryBtnRight['title']); ?></span></a>
                            <?php endif; ?>
                        </div>
                    <?php endif;
                }
            ?>
        </div>
    </div>
</section>