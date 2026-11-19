<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Comparison Table Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$disclaimer = get_field('disclaimer_text');
$intro = get_field('intro_content');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="comparison <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($intro) { echo '<div class="intro">' . $intro . '</div>'; } ?>
        <div class="comparisonTable">
            <?php if( have_rows('tables') ):
                while( have_rows('tables') ) : the_row();
                $heading = get_sub_field('table_heading');
                $flag = get_sub_field('make_flagged');
                $link = get_sub_field('cta_link'); ?>

                <div class="tableItem<?php if($flag) { echo ' flagged'; } ?>">
                    <div class="heading"><h3><?php echo $heading; ?></h3></div>
                    <div class="content">
                        <div class="tableItems">
                            <?php if( have_rows('table_items') ):
                                while( have_rows('table_items') ) : the_row();
                                $maintext = get_sub_field('table_item');
                                $bigtext = get_sub_field('table_number'); ?>

                                <div class="item<?php if($bigtext) { echo ' small'; } ?>">
                                    <?php echo $maintext; ?>
                                    <?php if($bigtext) { echo '<span>' . $bigtext . '</span>'; } ?>
                                </div>

                            <?php endwhile; endif; ?>
                        </div>
                        <?php if($link) : ?><div class="cta">
                            <a class="button secondary" href="<?php echo esc_url($link['url']); ?>" target="<?php echo esc_attr($link['target']); ?>"><span><?php echo esc_html($link['title']); ?></span></a>
                        </div><?php endif; ?>
                    </div>
                </div>

                <?php endwhile;
            endif; ?>
        </div>
    </div>
    <?php if($disclaimer) { echo '<div class="disclaimer"><div class="container">' . $disclaimer . '</div><div>'; } ?>
</section>