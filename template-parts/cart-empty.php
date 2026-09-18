<?php
/**
 * template-parts/cart-empty.php
 *
 * THE empty basket. One file, used everywhere an empty basket can appear:
 *
 *   - woocommerce/cart/cart-empty.php      the classic cart template
 *   - mv_our_empty_cart_block()            the Cart BLOCK's empty state,
 *                                          swapped in by inc/woocommerce.php
 *
 * WHY IT IS SHARED
 * There were three different empty baskets on this site: this design, the
 * theme's older "Your basket is empty / Browse the collection" wording, and
 * WooCommerce's own sad-face block — and which one a customer saw depended on
 * whether the page had just loaded or they had removed the last item without
 * reloading. Now every route renders this file.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mvce_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<div class="mv-cart-empty">

	<?php // Bottle and glass, drawn rather than an image so it takes the page's colour. ?>
	<div class="mv-cart-empty__icon" aria-hidden="true">
		<svg viewBox="0 0 96 86" width="96" height="86" fill="none" focusable="false">
			<path d="M34 6h12v14c0 3 4 6 4 12v46H30V32c0-6 4-9 4-12V6Z"
				stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
			<path d="M58 30h18c0 10-4 14-9 16v28" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
			<path d="M58 30c0 10 4 14 9 16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
			<path d="M20 78h36M58 78h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		</svg>
	</div>

	<h2 class="mv-cart-empty__title">
		<?php esc_html_e( 'Your basket is currently empty', 'maison-vintique-elementor' ); ?>
	</h2>

	<p class="mv-cart-empty__text">
		<?php esc_html_e( 'Discover our carefully selected wines from our estate partners.', 'maison-vintique-elementor' ); ?>
	</p>

	<a class="btn btn--primary mv-cart-empty__cta" href="<?php echo esc_url( $mvce_shop ); ?>">
		<?php esc_html_e( 'Explore our collection', 'maison-vintique-elementor' ); ?>
		<span aria-hidden="true">&rsaquo;</span>
	</a>

	<?php
	/*
	 * Kept from the old template: plugins (and the theme's own notices) hang
	 * things off this, so dropping it would silently lose them.
	 */
	do_action( 'woocommerce_cart_is_empty' );
	?>
</div>
