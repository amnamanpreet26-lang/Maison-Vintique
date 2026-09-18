<?php
/**
 * Empty basket — the classic cart template.
 *
 * Theme override path: your-theme/woocommerce/cart/cart-empty.php
 *
 * WooCommerce loads this instead of cart.php when the basket has no lines.
 * The panel itself lives in template-parts/cart-empty.php because the Cart
 * BLOCK needs the identical markup — see inc/woocommerce.php, which swaps it
 * into the block's empty state so a customer cannot end up looking at
 * WooCommerce's own sad-face version.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="section cart-page">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php esc_html_e( 'Basket', 'maison-vintique-elementor' ); ?></span>
	</div>

	<div class="page-h">
		<p class="eyebrow"><?php esc_html_e( 'Trade Basket', 'maison-vintique-elementor' ); ?></p>
		<h1><?php esc_html_e( 'Your Basket', 'maison-vintique-elementor' ); ?></h1>
	</div>

	<?php get_template_part( 'template-parts/cart-empty' ); ?>

</div>
</section>
