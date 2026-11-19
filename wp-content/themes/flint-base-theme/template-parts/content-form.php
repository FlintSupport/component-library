<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Form Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$intro = get_field('intro_copy');
$form = get_field('form');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="form <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } if( have_rows('sidebar_items') ) { echo ' withside'; } ?>">
    <div class="container">
        <?php if($intro) { echo '<div class="intro">' . $intro . '</div>'; }?>

        <div class="gForm">
            <?php gravity_form( $form, false, false, false, '', true ); ?>
        </div>
        <?php if( have_rows('sidebar_items') ): ?>
            <div class="sidebar">
            <?php while( have_rows('sidebar_items') ) : the_row(); ?>

            <div class="block">
                <?php the_sub_field('block_content'); ?>
            </div>

            <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</section>