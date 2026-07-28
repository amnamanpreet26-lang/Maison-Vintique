<?php
/**
 * template-parts/content-product-wine.php
 *
 * One wine card inside .pgrid on the Shop / archive page.
 *
 * The card markup itself now lives in ONE place — template-parts/wine-card.php —
 * which the homepage "Curated Portfolio" grid also uses. This file stays so that
 * woocommerce/archive-product.php keeps working unchanged, and simply forwards
 * to that shared partial. Edit wine-card.php to change the card everywhere.
 *
 * Price gating (Visibility Tier) is NOT re-implemented here — it already
 * happens theme-wide via the `woocommerce_get_price_html`,
 * `woocommerce_is_purchasable` and `woocommerce_product_add_to_cart_text`
 * filters in inc/woocommerce.php.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_template_part( 'template-parts/wine-card' );
