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
                    echo '<div class="topTabs" role="tablist" aria-label="Content tabs">';
                    $j = 1;
                    while( have_rows('tabs_top') ) : the_row();
                        $tabTitle = get_sub_field('tab_title');
                        $count = $j++ . '-' . $i; ?>

                    <button type="button" role="tab" class="tabTitle tabTitle-<?php echo esc_attr($i); ?>" id="tabTitle-<?php echo esc_attr($count); ?>" aria-controls="tabCopy-<?php echo esc_attr($count); ?>" aria-selected="false" tabindex="-1"><span><?php echo esc_html($tabTitle); ?></span></button>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
            <?php
                if( have_rows('tabs_side') ):
                    echo '<div class="sideTabs" role="tablist" aria-label="Content tabs">';
                    $j = 1;
                    while( have_rows('tabs_side') ) : the_row();
                        $tabIcon = get_sub_field('tab_icon');
                        $tabTitle = get_sub_field('tab_title');
                        $tabContent = get_sub_field('tab_content');
                        $count = $j++ . '-' . $i; ?>

                    <button type="button" role="tab" class="tabTitle tabTitle-<?php echo esc_attr($i); ?>" id="tabTitle-<?php echo esc_attr($count); ?>" aria-controls="tabCopy-<?php echo esc_attr($count); ?>" aria-selected="false" tabindex="-1">
                        <div class="icon"><img src="<?php echo esc_url($tabIcon['url']); ?>" alt="<?php echo esc_attr($tabIcon['alt']); ?>" /></div>
                        <div class="tabTitleContent">
                            <span><?php echo $tabTitle; ?></span>
                            <?php echo $tabContent; ?>
                        </div>
                    </button>
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

                    <div role="tabpanel" class="tabCopy tabCopy-<?php echo $i; if(!$tabImage) {echo ' full';} ?>" id="tabCopy-<?php echo esc_attr($count); ?>" aria-labelledby="tabTitle-<?php echo esc_attr($count); ?>" tabindex="0"><div class="flex"><div class="content"><?php echo $tabCopy; ?></div><?php if($tabImage) :?><img src="<?php echo esc_url($tabImage['url']); ?>" class="<?php echo $align; ?>" alt="<?php echo esc_attr($tabImage['alt']); ?>" /><?php endif; ?></div></div>
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

                    <div role="tabpanel" class="tabCopy tabCopy-<?php echo $i; ?>" id="tabCopy-<?php echo esc_attr($count); ?>" aria-labelledby="tabTitle-<?php echo esc_attr($count); ?>" tabindex="0">
                        <img src="<?php echo esc_url($tabImage['url']); ?>" alt="<?php echo esc_attr($tabImage['alt']); ?>" />
                    </div>
                <?php endwhile;
                    echo '</div>';
            endif; ?>
        </div>
    </div>
</section>