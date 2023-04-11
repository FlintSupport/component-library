<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Newsletter Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$title = get_field('headline');
$content = get_field('content');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="newsletter <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <div class="left">
            <h3><?php echo $title; ?></h3>
            <p><?php echo $content; ?></p>
        </div>
        <div class="right">
        <?php gravity_form( 1, false, false, false, '', true ); ?>
        </div>
    </div>
</section>