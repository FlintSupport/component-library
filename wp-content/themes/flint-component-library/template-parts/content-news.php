<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>News Highlight Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$title = get_field('top_title');
$category = get_field('category_of_posts');
$show = get_field('posts_to_show');
$icon = get_field('cta_icon');
$cta = get_field('cta_title');
$link = get_field('cta_link');

if($show === 'two') { $number = '2'; } else { $number = '3'; }
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="newsHighlights<?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
        <?php if($title) { echo '<span>' . $title . '</span>'; } ?>

        <?php
            $posts = get_posts( array(
                'post_type' => 'post',
                'status'  => 'publish', 
                'posts_per_page' => $number,
                'category' => $category
            ));

            if( $posts ) :
                foreach( $posts as $post ) : ?>

                <a class="post" href="<?php the_permalink( $post ); ?>">
                    <h4><?php echo get_the_title($post); ?></h4>
                </a>        
            <?php endforeach;
        endif; ?>

        <?php if($show === 'two') : ?>

            <a class="cta" href="<?php echo esc_url($link['url']); ?>">
                <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>">
                <h4><?php echo $cta; ?></h4>
            </a>

        <?php endif; ?>
    </div>
</section>