<?php
if(is_admin()): ?>
    <div class="flint-block">
        <div class="editor-note">
            <h3>Testimonial Block</h3>
            <p>Click the <span class="dashicons dashicons-edit"></span> icon to edit content.</p>
        </div>
<?php endif;
//Global Fields
$id = get_field('block_anchor_id');
$background = get_field('background_color');
$notop = get_field('nopad_top');
$nobottom = get_field('nopad_bottom');

//Block-Specific Fields
$style = get_field('testimonial_style');
$content = get_field('testimonial_content');
?>

<section <?php if($id) { echo 'id="' . $id . '" '; } ?>class="testimonial <?php echo $background . ' ' . $style; if($notop) { echo ' notop'; } if($nobottom) { echo ' nobottom'; } ?>">
    <div class="container">
    <?php if($style === 'single') :?>
            <div class="image">
                <img src="<?php echo esc_url( $content['testimonial_image']['url'] ); ?>" alt="<?php echo esc_attr( $content['testimonial_image']['alt'] ); ?>">
            </div>
            <div class="content">
                <blockquote><?php echo $content['testimonial_quote']; ?></blockquote>
                <div class="author">
                    <p><?php echo $content['testimonial_author']; ?></p>
                    <a href="mailto:<?php echo $content['testimonial_email']; ?>"><?php echo $content['testimonial_email']; ?></p>
                    <a href="tel:<?php echo $content['testimonial_phone']; ?>"><?php echo $content['testimonial_phone']; ?></a>
                </div>
            </div>
        <?php endif;?>

        <?php if($style === 'multi') :?>
            <div class="testimonialSlider">
                <?php if( have_rows('testimonials') ):
                while( have_rows('testimonials') ) : the_row();
                    $image = get_sub_field('testimonial_image');
                    $quote = get_sub_field('testimonial_quote');
                    $author = get_sub_field('testimonial_author');
                    $email = get_sub_field('testimonial_email');
                    $phone = get_sub_field('testimonial_phone'); ?>

                    <div class="slide">
                        <div class="image">
                            <img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>">
                        </div>
                        <div class="content">
                            <blockquote><?php echo $quote; ?></blockquote>
                            <div class="author">
                                <p><?php echo $author; ?></p>
                                <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></p>
                                <a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
                            </div>
                        </div>
                    </div>

                <?php endwhile; endif; ?>
            </div>
        <?php endif;?>
    </div>
</section>