<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Flexible Content Block</h3>
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
$twocol = get_field('column_layout_two');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="general <?php echo $background . ' ' . $size; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; }?>">
    <div class="container<?php if($twocol == '5050') { echo ' halves'; } if($twocol == '4060') { echo ' smallleft'; } if($twocol == '6040') { echo ' smallright'; } if(count(get_field('columns')) == 3) { echo ' thirds'; } if(count(get_field('columns')) == 4) { echo ' fourths'; } ?>">
    <?php if( have_rows('columns') ):
        while( have_rows('columns') ) : the_row();
            echo '<div class="column">';
            if( have_rows('module') ):
                while ( have_rows('module') ) : the_row();

                    if( get_row_layout() == 'text' ) {
                        $text = get_sub_field('text_content');
                        echo '<div class="module">' . $text . '</div>';
                    }

                    elseif( get_row_layout() == 'image' ) {
                        $image = get_sub_field('image');
                        echo '<div class="module"><img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" /></div>';
                    }

                    elseif( get_row_layout() == 'video' ) {
                        $type = get_sub_field('video_type');
                        $link = get_sub_field('youtube_link');
                        $embed = get_sub_field('embed_code');

                        if($link) { echo '<div class="module">' . $link . '</div>'; }
                        if($embed) { echo '<div class="module">' . $embed . '</div>'; }
                    }
                        

                    elseif( get_row_layout() == 'form' ) {
                        $form = get_sub_field('form');
                        echo '<div class="module">';
                        gravity_form($form, false, false);
                        echo '</div>';

                    }
                endwhile;
            endif;
            echo '</div>';
        endwhile;
    endif; ?>
    </div>
</section>