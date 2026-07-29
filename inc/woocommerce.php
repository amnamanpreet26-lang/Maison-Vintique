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


/**
 * Keep the single product page free of stray "Add to Cart" / "Buy Now" buttons.
 *
 * woocommerce/single-product.php is a fully custom template: it renders its own
 * designed buy bar (quantity + "Add to Case" + "Enquire" for approved trade
 * accounts) and never calls woocommerce_template_single_add_to_cart(). So the
 * theme itself does not output a second button.
 *
 * What DOES put extra buttons on a product page is everything around the theme:
 * an Elementor "Single Product" / "Add To Cart" widget in Theme Builder, or a
 * "Buy Now"/"Quick Buy"/direct-checkout plugin hooking the standard actions.
 * This unhooks those so only the designed buy bar is ever shown.
 *
 * To put them back:
 *   add_filter( 'mve_hide_single_add_to_cart', '__return_false' );
 */
function mve_strip_single_add_to_cart() {
	// Product pages only — the shop loop's own buttons are untouched.
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	if ( ! apply_filters( 'mve_hide_single_add_to_cart', true ) ) {
		return;
	}

	// WooCommerce's own add-to-cart form on the single product page.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
	remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
	remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
	remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

	/**
	 * Hook point for any "Buy Now" plugin that renders around the add-to-cart
	 * button. Plugin callbacks are anonymous or namespaced differently in every
	 * plugin, so rather than guess names this strips every callback attached to
	 * the two actions a Buy Now button can realistically use on a product page.
	 */
	foreach ( array( 'woocommerce_after_add_to_cart_button', 'woocommerce_after_add_to_cart_form' ) as $mve_hook ) {
		remove_all_actions( $mve_hook );
	}
}
add_action( 'wp', 'mve_strip_single_add_to_cart' );

/**
 * Trade checkout extras from the approved design: PO reference, requested
 * delivery date and delivery instructions.
 *
 * Registered as real WooCommerce checkout fields (in the "order" fieldset, so
 * they render inside the Delivery section) rather than loose inputs in the
 * template — that way WooCommerce validates them, saves them and they survive
 * a failed payment attempt.
 */
function mve_trade_checkout_fields( $fields ) {
	$fields['order']['po_reference'] = array(
		'label'       => __( 'PO reference', 'maison-vintique-elementor' ),
		'placeholder' => __( 'e.g. PO-8841', 'maison-vintique-elementor' ),
		'required'    => false,
		'class'       => array( 'form-row-first' ),
		'priority'    => 5,
	);

	$fields['order']['delivery_date'] = array(
		'type'     => 'date',
		'label'    => __( 'Requested delivery date', 'maison-vintique-elementor' ),
		'required' => false,
		'class'    => array( 'form-row-last' ),
		'priority' => 10,
	);

	$fields['order']['delivery_instructions'] = array(
		'label'       => __( 'Delivery instructions (optional)', 'maison-vintique-elementor' ),
		'placeholder' => __( 'e.g. deliver before noon, cellar entrance', 'maison-vintique-elementor' ),
		'required'    => false,
		'class'       => array( 'form-row-wide' ),
		'priority'    => 15,
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'mve_trade_checkout_fields' );

/**
 * Persist the trade checkout extras onto the order.
 */
function mve_save_trade_checkout_fields( $order, $data ) {
	foreach ( array( 'po_reference', 'delivery_date', 'delivery_instructions' ) as $key ) {
		if ( ! empty( $data[ $key ] ) ) {
			$order->update_meta_data( '_mve_' . $key, sanitize_text_field( $data[ $key ] ) );
		}
	}
}
add_action( 'woocommerce_checkout_create_order', 'mve_save_trade_checkout_fields', 10, 2 );

/**
 * Show them on the order screen in wp-admin.
 */
function mve_show_trade_fields_in_admin( $order ) {
	$labels = array(
		'po_reference'          => __( 'PO reference', 'maison-vintique-elementor' ),
		'delivery_date'         => __( 'Requested delivery date', 'maison-vintique-elementor' ),
		'delivery_instructions' => __( 'Delivery instructions', 'maison-vintique-elementor' ),
	);

	foreach ( $labels as $key => $label ) {
		$value = $order->get_meta( '_mve_' . $key );
		if ( $value ) {
			printf( '<p><strong>%s:</strong> %s</p>', esc_html( $label ), esc_html( $value ) );
		}
	}
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'mve_show_trade_fields_in_admin' );

/**
 * The cart template renders its own "Order summary" aside, so WooCommerce's
 * default cart-totals block must not also print. Everything else hooked to
 * woocommerce_cart_collaterals (cross-sells, plugins) is left alone.
 *
 * @see woocommerce/cart/cart.php
 */
function mve_unhook_default_cart_totals() {
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );
}
add_action( 'wp_loaded', 'mve_unhook_default_cart_totals' );

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