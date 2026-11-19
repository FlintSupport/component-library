<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Card Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$layout = get_field('card_layout');
$intro = get_field('intro_copy');
$firstcard = get_field('first_card_cta');
$firstcontent = get_field('first_card_cta_content');
$firstbutton = get_field('first_card_cta_button');
$lastcard = get_field('last_card_cta');
$lastcontent = get_field('last_card_cta_content');
$lastbutton = get_field('last_card_cta_button');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="cards <?php echo $layout . ' ' . $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($intro) { echo '<div class="intro">' . $intro . '</div>'; } ?>
        <?php if($firstcard) :?>
            <div class="card first">
                <?php echo $firstcontent;
                if($firstbutton) : ?>
                    <div class="link"><a class="button primary" href="<?php echo esc_url($firstbutton['url']); ?>" target="<?php echo esc_attr($firstbutton['target']); ?>"><span><?php echo esc_html($firstbutton['title']); ?></span></a></div>
                <?php endif; ?>
            </div>
        <?php endif;
            if( have_rows('cards') ):
                while( have_rows('cards') ) : the_row();
                    $image = get_sub_field('image');
                    $title = get_sub_field('card_title');
                    $content = get_sub_field('card');
                    $link = get_sub_field('card_link');
                    $highlight = get_sub_field('add_highlight_tab'); 
                    $tabtext = get_sub_field('tab_text'); ?>

                <?php if($link) {
                    echo '<a class="card" ';
                    if ($layout === 'bg') { echo 'style="background-image: url(' . esc_url($image['url']) . ');" '; }
                    echo 'href="' . esc_url($link['url']) . '" target="' . esc_attr($link['target']) . '">';
                }
                    else {
                        echo '<div class="card"';
                        if ($layout === 'bg') { echo ' style="background-image: url(' . esc_url($image['url']) . ');"'; }
                        echo '>';
                    }
                ?>
                    <?php if($highlight) {
                        echo '<div class="featured">' . $tabtext . '</div>'; }
                    ?>
                    <div class="image<?php if(!$image) { echo ' noimage'; } ?>"><?php if($image && $layout != 'bg') { echo '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '">'; } ?></div>
                    <div class="content">
                        <h4><?php echo $title; ?></h4>
                        <?php if($content) { echo '<p>' . $content . '</p>'; } ?>
                        <?php if($link && $content) { echo '<span>' . esc_html($link['title']) . '</span>'; } ?>
                    </div>
                <?php if($link) { echo '</a>'; } else { echo '</div>'; } ?>
            <?php endwhile;
        endif;
        if($lastcard) :?>
            <div class="card last">
                <?php echo $lastcontent; 
                if($lastbutton) : ?>
                    <div class="link"><a class="button primary" href="<?php echo esc_url($lastbutton['url']); ?>" target="<?php echo esc_attr($lastbutton['target']); ?>"><span><?php echo esc_html($lastbutton['title']); ?></span></a></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>