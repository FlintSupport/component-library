<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>WYSIWYG Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$size = get_field('content_size');
$content = get_field('content');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="wysiwyg <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; }?>">
    <div class="container">
        <?php if($content) { echo $content; }?>
    </div>
</section>