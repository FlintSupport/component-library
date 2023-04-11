<?php
if(is_admin()): ?>
    <div class="axonify-block">
        <div class="editor-note">
            <h3>Tabs Block</h3>
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
$layout = get_field('tab_layout');
$alignment = get_field('tab_alignment');

global $i;
$i++;
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="tabs <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($intro) { echo $intro; }?>

        <div class="tabItems open <?php echo $layout . ' ' . $alignment;?>" id="tabItems-<?php echo $i;?>">
            <?php
                if( have_rows('tabs_top') ):
                    echo '<div class="topTabs">';
                    $j = 1;
                    while( have_rows('tabs_top') ) : the_row();
                        $tabTitle = get_sub_field('tab_title');
                        $count = $j++ . '-' . $i; ?>

                    <div class="tabTitle tabTitle-<?php echo $i; ?>" id="tabTitle-<?php echo $count; ?>"><span><?php echo $tabTitle; ?></span></div>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
            <?php
                if( have_rows('tabs_side') ):
                    echo '<div class="sideTabs">';
                    $j = 1;
                    while( have_rows('tabs_side') ) : the_row();
                        $tabIcon = get_sub_field('tab_icon');
                        $tabTitle = get_sub_field('tab_title');
                        $tabContent = get_sub_field('tab_content');
                        $count = $j++ . '-' . $i; ?>

                    <div class="tabTitle tabTitle-<?php echo $i; ?>" id="tabTitle-<?php echo $count; ?>">
                        <div class="icon"><img src="<?php echo esc_url($tabIcon['url']); ?>" alt="<?php echo esc_attr($tabIcon['alt']); ?>" /></div>
                        <div class="tabTitleContent">
                            <span><?php echo $tabTitle; ?></span>
                            <?php echo $tabContent; ?>
                        </div>
                    </div>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
            <?php
                if( have_rows('tabs_top') ):
                    echo '<div class="tabContent top">';
                    $k = 1;
                    while( have_rows('tabs_top') ) : the_row();
                        $tabCopy = get_sub_field('tab_content');
                        $tabImage = get_sub_field('tab_side_image');
                        $align = get_sub_field('image_alignment');
                        $count = $k++ . '-' . $i; ?>

                    <div class="tabCopy tabCopy-<?php echo $i; if(!$tabImage) {echo ' full';} ?>" id="tabCopy-<?php echo $count; ?>"><div class="flex"><div class="content"><?php echo $tabCopy; ?></div><?php if($tabImage) :?><img src="<?php echo esc_url($tabImage['url']); ?>" class="<?php echo $align; ?>" alt="<?php echo esc_attr($tabImage['alt']); ?>" /><?php endif; ?></div></div>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
            <?php
                if( have_rows('tabs_side') ):
                    echo '<div class="tabContent side">';
                    $k = 1;
                    while( have_rows('tabs_side') ) : the_row();
                        $tabImage = get_sub_field('tab_side_image_side');
                        $count = $k++ . '-' . $i; ?>

                    <div class="tabCopy tabCopy-<?php echo $i; ?>" id="tabCopy-<?php echo $count; ?>">
                        <img src="<?php echo esc_url($tabImage['url']); ?>" alt="<?php echo esc_attr($tabImage['alt']); ?>" />
                    </div>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
        </div>
    </div>
</section>