<?php
/**
 * WooCommerce trade behaviour — price on login.
 *
 * ONE RULE, and it is the whole of it:
 *
 *   Logged OUT -> no prices anywhere, nothing can be bought.
 *   Logged IN  -> prices visible, add to cart and buy work normally.
 *
 * There is no per-product setting and no special role: any logged-in account
 * sees everything. If you ever need to carve out an exception, filter
 * `mve_is_gated` rather than adding branches here.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is pricing hidden from the current visitor?
 *
 * @param int $product_id Unused by default; passed through to the filter so a
 *                        site can still make a per-product decision if needed.
 * @return bool
 */
function mve_is_gated( $product_id = 0 ) {
	return (bool) apply_filters( 'mve_is_gated', ! is_user_logged_in(), $product_id );
}

/**
 * Replace the price HTML with a login prompt while logged out.
 */
function mve_gated_price_html( $price, $product ) {
	if ( mve_is_gated( $product->get_id() ) ) {
		$login = wp_login_url( get_permalink( $product->get_id() ) );
		return '<span class="mv-trade-tag">' . esc_html__( 'Trade pricing on login', 'maison-vintique-elementor' ) . '</span> '
			. '<a class="mv-price-login" href="' . esc_url( $login ) . '">' . esc_html__( 'Login to view price', 'maison-vintique-elementor' ) . '</a>';
	}
	return $price;
}
add_filter( 'woocommerce_get_price_html', 'mve_gated_price_html', 10, 2 );

/**
 * Nothing is purchasable while logged out.
 */
function mve_gated_is_purchasable( $purchasable, $product ) {
	if ( mve_is_gated( $product->get_id() ) ) {
		return false;
	}
	return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'mve_gated_is_purchasable', 10, 2 );

/**
 * Swap the "Add to Cart" button text while logged out.
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
 * Minimum order quantity, from ACF `min_order_qty`.
 *
 * THE MINIMUM IS A FLOOR, NOT A MULTIPLE.
 *
 * This used to set `step` to the minimum as well, so a wine with a case of 6
 * counted 6, 12, 18 — press + once and the order doubled. That was two
 * separate problems:
 *
 *   1. The box opened on 1 (the template's default) while the minimum was 6.
 *      1 is not a valid step from 6, so the first press jumped to 6 and the
 *      second to 12 — which is exactly what "it goes to 12, not 7" describes.
 *   2. Anything typed between the steps — 7, 8, 9 — failed the browser's own
 *      validation, and Chrome refused to submit the form at all with
 *      "the two nearest valid values are 6 and 12".
 *
 * So: the box now OPENS on the minimum and counts up in ones from there.
 * 6, 7, 8. To go back to whole cases only:
 *
 *   add_filter( 'mve_quantity_step', function ( $step, $moq ) { return $moq; }, 10, 2 );
 *
 * @param array      $args    Quantity input arguments.
 * @param WC_Product $product Product.
 * @return array
 */
function mve_min_order_qty( $args, $product ) {
	$moq = function_exists( 'get_field' ) ? (int) get_field( 'min_order_qty', $product->get_id() ) : 0;
	if ( $moq <= 1 ) {
		return $args;
	}

	$args['min_value'] = $moq;
	$args['step']      = max( 1, (int) apply_filters( 'mve_quantity_step', 1, $moq, $product ) );

	/*
	 * Open on the minimum rather than below it — but only when the value is
	 * below it. The basket passes each line's real quantity through this same
	 * filter, and overwriting that would reset every basket line to the
	 * minimum the moment the page rendered.
	 */
	if ( isset( $args['input_value'] ) && (int) $args['input_value'] < $moq ) {
		$args['input_value'] = $moq;
	}

	return $args;
}
/*
 * PRIORITY 9999, NOT 10.
 *
 * "It still goes 6, 12, 18" after the step was already fixed means something
 * else is setting it back. Plenty of things can: a minimum/maximum-quantity
 * plugin, a wholesale extension, a snippet in the site's own functions.php.
 * WooCommerce applies this filter once and the LAST hook to run wins, so this
 * one now runs after everything else rather than before it.
 *
 * The same value is forced onto the two narrower filters below, because a
 * plugin may hook those instead — and the front end sets the attributes again
 * on the input itself, so even a stale cached page counts in ones.
 */
add_filter( 'woocommerce_quantity_input_args', 'mve_min_order_qty', 9999, 2 );

/**
 * The step, on its own filter, for anything that hooks that one instead.
 *
 * @param int|float  $step    Current step.
 * @param WC_Product $product Product.
 * @return int
 */
function mve_quantity_step_filter( $step, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $step;
	}
	$moq = function_exists( 'get_field' ) ? (int) get_field( 'min_order_qty', $product->get_id() ) : 0;
	if ( $moq <= 1 ) {
		return $step;
	}
	return max( 1, (int) apply_filters( 'mve_quantity_step', 1, $moq, $product ) );
}
add_filter( 'woocommerce_quantity_input_step', 'mve_quantity_step_filter', 9999, 2 );

/**
 * The minimum, likewise.
 *
 * @param int|float  $min     Current minimum.
 * @param WC_Product $product Product.
 * @return int|float
 */
function mve_quantity_min_filter( $min, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $min;
	}
	$moq = function_exists( 'get_field' ) ? (int) get_field( 'min_order_qty', $product->get_id() ) : 0;
	return $moq > 1 ? $moq : $min;
}
add_filter( 'woocommerce_quantity_input_min', 'mve_quantity_min_filter', 9999, 2 );

/**
 * What the quantity box should actually be, for a product.
 *
 * The single template hands these to the front end so the script can put them
 * back on the input. That is the last line of defence: whatever any plugin
 * printed into the markup, and whatever a caching layer served, the browser
 * ends up counting the way this says.
 *
 * @param WC_Product $product Product.
 * @return array {min, step}
 */
function mve_quantity_rules( $product ) {
	$moq = ( $product instanceof WC_Product && function_exists( 'get_field' ) )
		? (int) get_field( 'min_order_qty', $product->get_id() )
		: 0;

	$min = $moq > 1 ? $moq : 1;

	return array(
		'min'  => $min,
		'step' => max( 1, (int) apply_filters( 'mve_quantity_step', 1, $min, $product ) ),
	);
}

/* =========================================================================
 * WHY IS THE QUANTITY BOX DOING THAT?
 * ---------------------------------------------------------------------
 * Add ?mv_qty=debug to any product page, signed in as an administrator, and
 * a small panel says what the minimum and step actually are, where they came
 * from, and what else on the site is hooked onto the same filters. It answers
 * "the theme is fixed but the site still counts in sixes" in one look instead
 * of by guesswork.
 * ====================================================================== */

/**
 * The panel.
 */
function mve_quantity_debug() {
	if ( ! is_product() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( ! isset( $_GET['mv_qty'] ) || 'debug' !== $_GET['mv_qty'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
		return;
	}

	global $product, $wp_filter;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$moq   = function_exists( 'get_field' ) ? get_field( 'min_order_qty', $product->get_id() ) : null;
	$rules = mve_quantity_rules( $product );

	// What WooCommerce would actually build, filters and all.
	$args = apply_filters(
		'woocommerce_quantity_input_args',
		array(
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
			'step'        => apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
			'input_value' => 1,
		),
		$product
	);

	// Everything hooked onto the filter, so a culprit names itself.
	$hooked = array();
	foreach ( array( 'woocommerce_quantity_input_args', 'woocommerce_quantity_input_step', 'woocommerce_quantity_input_min' ) as $tag ) {
		if ( empty( $wp_filter[ $tag ] ) ) {
			continue;
		}
		foreach ( $wp_filter[ $tag ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $cb ) {
				$name = $cb['function'];
				if ( is_array( $name ) ) {
					$name = ( is_object( $name[0] ) ? get_class( $name[0] ) : (string) $name[0] ) . '::' . $name[1];
				} elseif ( $name instanceof Closure ) {
					$name = 'closure';
				}
				$hooked[] = sprintf( '%s [%s] %s', $tag, $priority, (string) $name );
			}
		}
	}
	?>
	<div style="position:fixed;bottom:16px;left:16px;z-index:99998;max-width:520px;background:#17251f;color:#f6f1e7;font:12px/1.6 monospace;padding:16px 18px;border-radius:8px;box-shadow:0 18px 40px -20px rgba(0,0,0,.6)">
		<strong style="display:block;margin-bottom:8px;color:#a98854">QUANTITY — <?php echo esc_html( $product->get_name() ); ?></strong>
		<div>ACF min_order_qty ....... <?php echo esc_html( null === $moq || '' === $moq ? 'not set' : var_export( $moq, true ) ); ?></div>
		<div>theme says ............. min <?php echo esc_html( $rules['min'] ); ?>, step <?php echo esc_html( $rules['step'] ); ?></div>
		<div>WooCommerce builds ..... min <?php echo esc_html( $args['min_value'] ); ?>, step <?php echo esc_html( $args['step'] ); ?>, opens on <?php echo esc_html( $args['input_value'] ); ?></div>
		<div style="margin-top:8px;color:<?php echo ( (int) $args['step'] === (int) $rules['step'] ) ? '#8fd3a6' : '#ff9c9c'; ?>">
			<?php
			echo ( (int) $args['step'] === (int) $rules['step'] )
				? 'OK — nothing is overriding the step.'
				: 'SOMETHING ELSE IS SETTING THE STEP. See the list below.';
			?>
		</div>
		<details style="margin-top:8px"><summary style="cursor:pointer;color:#a98854">what is hooked (<?php echo count( $hooked ); ?>)</summary>
			<div style="margin-top:6px;max-height:180px;overflow:auto">
				<?php foreach ( $hooked as $line ) : ?>
					<div><?php echo esc_html( $line ); ?></div>
				<?php endforeach; ?>
			</div>
		</details>
	</div>
	<?php
}
add_action( 'wp_footer', 'mve_quantity_debug', 99 );


/**
 * Hide stray "Add to Cart" / "Buy Now" buttons on the product page — but ONLY
 * while logged out.
 *
 * woocommerce/single-product.php renders its own designed buy bar and never
 * calls woocommerce_template_single_add_to_cart(). Extra buttons come from
 * around the theme: an Elementor "Add To Cart" widget in Theme Builder, or a
 * "Buy Now"/direct-checkout plugin hooking the standard actions. Those would
 * otherwise offer a guest a purchase route the price is hidden on.
 *
 * Once the customer is logged in nothing is stripped, so add to cart and buy
 * now behave normally.
 *
 * To disable entirely:
 *   add_filter( 'mve_hide_single_add_to_cart', '__return_false' );
 */
function mve_strip_single_add_to_cart() {
	// Product pages only — the shop loop's own buttons are untouched.
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// Logged-in customers keep every purchase button.
	if ( is_user_logged_in() ) {
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