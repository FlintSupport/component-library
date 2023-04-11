<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Small Blurb Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$background = get_field('background_color');
$intro = get_field('top_title');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="blurbs <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($intro) { echo '<div class="intro">' . $intro . '</div>'; } ?>
        
        <div class="boxes">
            <?php if( have_rows('blurbs') ):
                while( have_rows('blurbs') ) : the_row();
                    $image = get_sub_field('image');
                    $title = get_sub_field('title');
                    $content = get_sub_field('content'); ?>

                    <div class="blurb">
                        <?php if($image) { echo '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '">'; } else { echo '<hr>'; } ?>
                        <h5><?php echo $title; ?></h5>
                        <?php if($content) { echo $content; } ?>
                    </div>
                
                <?php endwhile;
            endif; ?>
        </div>
    </div>
</section>