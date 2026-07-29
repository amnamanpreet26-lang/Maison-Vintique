<?php
/**
 * Empty basket.
 *
 * Theme override path: your-theme/woocommerce/cart/cart-empty.php
 *
 * WooCommerce loads this instead of cart.php when the basket has no lines,
 * so without it the empty state would fall back to the plugin default and
 * look nothing like the rest of the site.
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

	<div class="cart-empty-panel">
		<?php do_action( 'woocommerce_cart_is_empty' ); ?>

		<p class="cart-empty-msg"><?php esc_html_e( 'Your basket is empty.', 'maison-vintique-elementor' ); ?></p>
		<p class="note"><?php esc_html_e( 'Wines are sold by the case. Browse the collection to start an order request.', 'maison-vintique-elementor' ); ?></p>

		<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
			<a class="btn btn-p" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Browse the collection', 'maison-vintique-elementor' ); ?>
			</a>
		<?php endif; ?>
	</div>

</div>
</section>
