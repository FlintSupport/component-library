<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to main content', 'flint-base-theme' ); ?></a>
<?php
$layout = function_exists( 'flint_base_get_setting' ) ? flint_base_get_setting( 'header_layout', 'header-1' ) : 'header-1';
$allowed = array( 'none', 'header-1', 'header-2', 'header-3' );
if ( ! in_array( $layout, $allowed, true ) ) { $layout = 'header-1'; }
if ( 'none' !== $layout ) { get_template_part( 'template-parts/layout/' . $layout ); }
?>
