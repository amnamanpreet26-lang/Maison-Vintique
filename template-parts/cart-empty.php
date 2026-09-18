<?php
/**
 * template-parts/cart-empty.php
 *
 * THE empty basket. One file, both routes:
 *
 *   - woocommerce/cart/cart-empty.php   the classic cart template
 *   - mv_empty_basket_panel()           the Cart BLOCK, in inc/woocommerce.php,
 *                                       which prints this into the footer and
 *                                       moves it alongside the block
 *
 * WHY IT IS SHARED
 * There were three different empty baskets on this site - this design, an
 * older "Your basket is empty / Browse the collection", and WooCommerce's own
 * grey sad face - and which one a customer saw depended on whether the page
 * had just loaded or they had removed the last line without reloading.
 *
 * Artwork and wording are the client's Option 2 from the 12 September review.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mvce_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<div class="mv-empty-basket">

	<svg class="mv-empty-basket__art" viewBox="0 0 120 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		<path d="M46 8h12v14c0 3 5 7 5 14v44c0 3-2 5-5 5H46c-3 0-5-2-5-5V36c0-7 5-11 5-14V8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
		<path d="M46 22h12" stroke="currentColor" stroke-width="1.6"/>
		<path d="M72 34h18l-2 16a7 7 0 0 1-7 6 7 7 0 0 1-7-6l-2-16Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
		<path d="M81 56v22M73 78h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
		<path d="M24 85h72" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
	</svg>

	<h2 class="mv-empty-basket__title"><?php esc_html_e( 'Your basket is currently empty', 'maison-vintique' ); ?></h2>

	<p class="mv-empty-basket__copy">
		<?php esc_html_e( 'Discover our carefully selected wines', 'maison-vintique' ); ?><br>
		<?php esc_html_e( 'from our estate partners.', 'maison-vintique' ); ?>
	</p>

	<a class="mv-empty-basket__cta" href="<?php echo esc_url( $mvce_shop ); ?>">
		<?php esc_html_e( 'EXPLORE OUR COLLECTION', 'maison-vintique' ); ?> &rsaquo;
	</a>

	<?php
	/*
	 * Plugins and the theme's own notices hang things off this, so dropping it
	 * would silently lose them. Harmless when nothing is listening.
	 */
	do_action( 'woocommerce_cart_is_empty' );
	?>
</div>
