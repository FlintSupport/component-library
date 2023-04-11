<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Post Highlight Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$headline = get_field('headline');
$type = get_field('display_type');
$posts = get_field('posts');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="posts <?php echo $background; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } if($type === 'newest') { $posts = get_posts(array('post_type'   => 'post','posts_per_page' => -1,'orderby' => 'date',));if( !$posts ) { echo ' noposts'; } } ?>">
    <div class="container">
        <h2><?php echo $headline; ?></h2>
        <a class="viewall" href="<?php echo get_post_type_archive_link( 'post' ); ?>">View All Posts</a>

        <?php if($type === 'newest') : ?>
            <?php
                $posts = get_posts(array(
                    'post_type'   => 'post',
                    'posts_per_page' => -1,
                    'orderby' => 'date',
                ));

                if( $posts ) : ?>
                    <div class="columns">
                        <?php foreach( $posts as $post ) : ?>

                            <a class="post<?php if( has_post_thumbnail( $post ) ) { echo ' hasthumb'; } ?>" href="<?php the_permalink( $post ); ?>">
                                <?php if ( has_post_thumbnail( $post ) ) {
                                    $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
                                    if ( ! empty( $large_image_url[0] ) ) {
                                        echo '<div class="thumbnail">' . get_the_post_thumbnail( $post->ID, 'large' ) . '</div>';
                                    }
                                } ?>
                                <div class="content">
                                    <h4><?php echo get_the_title($post); ?></h4>
                                    <div class="date">
                                        <?php echo get_the_date(); ?>
                                    </div>
                                    <?php echo get_the_excerpt($post); ?>
                                </div>
                            </a>
                                
                        <?php endforeach;?>
                    </div>
                <?php else :
                    echo '<p>Sorry, there are no posts to show!</p>';
                endif;
            ?>
        <?php else : ?>
            <div class="columns">
                <?php if( $posts ): ?>
                    <?php foreach( $posts as $post ): ?>
                        <a class="post" href="<?php the_permalink( $post ); ?>">
                            <?php if ( has_post_thumbnail( $post ) ) {
                                $large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
                                if ( ! empty( $large_image_url[0] ) ) {
                                    echo '<div class="thumbnail">' . get_the_post_thumbnail( $post->ID, 'large' ) . '</div>';
                                }
                            } ?>
                            <div class="content">
                                <h4><?php echo get_the_title($post); ?></h4>
                                <?php echo get_the_excerpt($post); ?>
                                <div class="date">
                                    <?php echo get_the_date(); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>