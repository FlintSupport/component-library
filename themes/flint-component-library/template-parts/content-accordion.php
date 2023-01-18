<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Accordion Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
        <?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$open = get_field('have_first_item_open_by_default');

global $i;
$i++;
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="accordion <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <div class="accordionItems <?php echo $style; if($open) {echo ' open';} if($primaryBtn || $secondaryBtn || $headline || $intro) {echo ' padding';}?>" id="accordionItems-<?php echo $i;?>">
            <?php
                if( have_rows('accordion_items') ):
                    $j = 1;
                    while( have_rows('accordion_items') ) : the_row();
                        $tabTitle = get_sub_field('tab_title');
                        $tabCopy = get_sub_field('tab_content');
                        $count = $j++ . '-' . $i; ?>

                    <div class="accordionItem"><div class="accordionTitle accordionTitle-<?php echo $i; ?>" id="accordionTitle-<?php echo $count; ?>"><span><?php echo $tabTitle; ?></span></div>
                    <div class="accordionCopy accordionCopy-<?php echo $i; ?>" id="accordionCopy-<?php echo $count; ?>"><?php echo $tabCopy; ?></div></div>
                <?php endwhile;
            endif; ?>
        </div>
    </div>
</section>