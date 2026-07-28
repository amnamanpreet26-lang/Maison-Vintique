<?php
/**
 * WooCommerce trade behaviour — price on login + trade gating.
 *
 * Wines carry an ACF `visibility_tier` (public / trade-only / allocation / private).
 * Public wines behave as normal WooCommerce products. Trade-gated wines hide the
 * price and the add-to-cart button from guests and show a "Login to view price"
 * button instead. Approved trade users (role `mv_trade`) see price + purchase.
 *
 * In production, trade pricing itself comes from the Laravel portal via API/SSO;
 * this file provides the WooCommerce-side gating and hooks.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the current visitor an approved trade user?
 */
function mve_is_trade_user() {
	return is_user_logged_in() && current_user_can( 'mv_trade' );
}

/**
 * Get a product's visibility tier (falls back to public).
 *
 * @param int $product_id
 * @return string public|trade-only|allocation|private
 */
function mve_product_tier( $product_id ) {
	$tier = function_exists( 'get_field' ) ? get_field( 'visibility_tier', $product_id ) : '';
	return $tier ? $tier : 'public';
}

/**
 * Should this product be gated for the current visitor?
 */
function mve_is_gated( $product_id ) {
	$tier = mve_product_tier( $product_id );
	if ( 'public' === $tier ) {
		return false;
	}
	return ! mve_is_trade_user();
}

/**
 * Replace the price HTML with a login prompt for gated products.
 */
function mve_gated_price_html( $price, $product ) {
	if ( mve_is_gated( $product->get_id() ) ) {
		$login = wp_login_url( get_permalink( $product->get_id() ) );
		return '<span class="mv-trade-tag">Trade pricing on login</span> <a class="mv-price-login" href="' . esc_url( $login ) . '">Login to view price</a>';
	}
	return $price;
}
add_filter( 'woocommerce_get_price_html', 'mve_gated_price_html', 10, 2 );

/**
 * Prevent purchase of gated products by guests.
 */
function mve_gated_is_purchasable( $purchasable, $product ) {
	if ( mve_is_gated( $product->get_id() ) ) {
		return false;
	}
	return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'mve_gated_is_purchasable', 10, 2 );

/**
 * Swap the "Add to Cart" button text on gated products (shop/archive loop).
 */
function mve_gated_add_to_cart_text( $text, $product ) {
	if ( $product && mve_is_gated( $product->get_id() ) ) {
		return __( 'Login to view price', 'maison-vintique-elementor' );
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'mve_gated_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'mve_gated_add_to_cart_text', 10, 2 );

/**
 * Register the trade customer role on activation.
 */
function mve_register_trade_role() {
	add_role(
		'mv_trade',
		__( 'Trade Customer', 'maison-vintique-elementor' ),
		array(
			'read'     => true,
			'mv_trade' => true,
		)
	);
}
add_action( 'after_switch_theme', 'mve_register_trade_role' );

/**
 * Wine ordering is by the case — enforce minimum order quantity from ACF `min_order_qty`.
 */
function mve_min_order_qty( $args, $product ) {
	$moq = function_exists( 'get_field' ) ? (int) get_field( 'min_order_qty', $product->get_id() ) : 0;
	if ( $moq > 1 ) {
		$args['min_value'] = $moq;
		$args['step']      = $moq;
	}
	return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'mve_min_order_qty', 10, 2 );


add_filter( 'woocommerce_product_add_to_cart_text', 'mve_gated_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'mve_gated_add_to_cart_text', 10, 2 );

/**
 * Rename the catalogue "Add to Cart" button to "View Wine".
 *
 * Runs AFTER mve_gated_add_to_cart_text() (priority 20 vs 10) and only
 * replaces the untouched default string, so the "Login to view price"
 * text for trade-gated wines is left alone.
 */
function mve_rename_add_to_cart_text( $text ) {
	if ( 'Add to Cart' === $text ) {
		return __( 'View Wine', 'maison-vintique-elementor' );
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'mve_rename_add_to_cart_text', 20 );