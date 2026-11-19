<?php
$layout = function_exists( 'flint_base_get_setting' ) ? flint_base_get_setting( 'footer_layout', 'footer-1' ) : 'footer-1';
$allowed = array( 'none', 'footer-1', 'footer-2', 'footer-3', 'footer-4' );
if ( ! in_array( $layout, $allowed, true ) ) { $layout = 'footer-1'; }
if ( 'none' !== $layout ) { get_template_part( 'template-parts/layout/' . $layout ); }
wp_footer();
?>
</body>
</html>
