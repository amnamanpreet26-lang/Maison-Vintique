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

/* =========================================================================
 * THE QUANTITY BOX, ENFORCED FROM OUTSIDE THE TEMPLATE
 * ---------------------------------------------------------------------
 * This has now been reported three times, and the reason the last two fixes
 * did not land is almost certainly that they lived in the theme's own
 * woocommerce/single-product.php. If the product page is rendered by anything
 * else — an Elementor Theme Builder single-product template, which this
 * project's own README tells you to build; a page builder widget; a plugin's
 * template — then that file never runs, its markup never appears, and the
 * script inside it never executes. The PHP filters still apply, but if a
 * plugin is also setting the step there is nothing on the page to correct it.
 *
 * So the enforcement moved OUT of the template and into the footer of every
 * page that could contain a quantity box. It finds the inputs itself, whatever
 * rendered them, and writes the right min and step onto them. There is no
 * longer any template it can be bypassed by.
 * ====================================================================== */

/**
 * The rules for every product that could have a quantity box on this page.
 *
 * @return array Product ID => array( min, step ).
 */
function mve_quantity_rules_on_page() {
	$rules = array();

	// The product page itself.
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$rules[ $product->get_id() ] = mve_quantity_rules( $product );

			// A variable product's children each have their own box.
			if ( $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					if ( $child instanceof WC_Product ) {
						$rules[ $child_id ] = mve_quantity_rules( $child );
					}
				}
			}
		}
	}

	// The basket, where every line has its own box.
	if ( function_exists( 'is_cart' ) && is_cart() && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;
			if ( $product instanceof WC_Product ) {
				$rules[ $product->get_id() ] = mve_quantity_rules( $product );
			}
		}
	}

	return apply_filters( 'mve_quantity_rules_on_page', $rules );
}

/**
 * Put the rules on the page, and make the browser obey them.
 */
function mve_quantity_enforcer() {
	$rules = mve_quantity_rules_on_page();
	if ( ! $rules ) {
		return;
	}
	?>
	<script id="mv-quantity-rules">
	(function () {
		var RULES = <?php echo wp_json_encode( $rules ); ?>;

		/* Which product does this quantity box belong to?
		   Every route WooCommerce and the page builders use is checked, because
		   the markup differs between the theme template, an Elementor widget,
		   the cart and the blocks checkout. */
		function productFor(input) {
			var form = input.closest('form');
			if (form) {
				var add = form.querySelector('[name="add-to-cart"], [name="variation_id"], button[name="add-to-cart"]');
				if (add && add.value) { return String(add.value); }
			}

			var wrap = input.closest('[data-product_id], [data-product-id]');
			if (wrap) { return String(wrap.getAttribute('data-product_id') || wrap.getAttribute('data-product-id')); }

			var row = input.closest('tr, li, .wc-block-cart-item');
			if (row) {
				var link = row.querySelector('a.remove[data-product_id]');
				if (link) { return String(link.getAttribute('data-product_id')); }
			}

			// One product on the page and one box: it can only be that one.
			var ids = Object.keys(RULES);
			return ids.length === 1 ? ids[0] : null;
		}

		function apply(input) {
			var id = productFor(input);
			var rule = id && RULES[id];
			if (!rule) { return; }

			if (rule.step > 0) { input.setAttribute('step', String(rule.step)); }
			input.setAttribute('min', String(rule.min));

			var val = parseFloat(input.value);
			if (isNaN(val) || val < rule.min) { input.value = String(rule.min); }
		}

		function sweep() {
			document.querySelectorAll('input.qty, input[name="quantity"], input[name^="cart["]').forEach(apply);
		}

		sweep();

		/* WooCommerce replaces the basket and the variation form over AJAX, and
		   Elementor rebuilds widgets in the editor preview — so a one-off pass
		   is not enough. Anything added later is corrected as it appears. */
		if (window.MutationObserver) {
			new MutationObserver(function (records) {
				for (var i = 0; i < records.length; i++) {
					for (var j = 0; j < records[i].addedNodes.length; j++) {
						var node = records[i].addedNodes[j];
						if (node.nodeType !== 1) { continue; }
						if (node.matches && node.matches('input')) { apply(node); }
						else if (node.querySelectorAll) { node.querySelectorAll('input.qty, input[name="quantity"]').forEach(apply); }
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		}

		document.addEventListener('click', function (e) {
			// After anything that might have redrawn a box.
			if (e.target.closest('.qty-plus, .qty-minus, .quantity, .single_add_to_cart_button')) {
				window.setTimeout(sweep, 60);
			}
		}, true);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mve_quantity_enforcer', 5 );

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
		<div>rendered by ............ <?php echo esc_html( $GLOBALS['mve_template_in_use'] ?? 'unknown' ); ?></div>
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
 * Remember which template WordPress actually loaded.
 *
 * The debug panel reports it, because "which file is drawing this page" is the
 * first question when a fix that is definitely in the theme has no effect —
 * and a page builder answers it differently from a theme template.
 *
 * @param string $template Template path.
 * @return string
 */
function mve_note_template( $template ) {
	$GLOBALS['mve_template_in_use'] = str_replace( ABSPATH, '', (string) $template );
	return $template;
}
add_filter( 'template_include', 'mve_note_template', 1 );


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

/**
 * Maison Vintique trade MOQ (client rule):
 *   6-bottle cases  - minimum 2 cases, in multiples of 2
 *   12-bottle cases - minimum 1 case,  in multiples of 1
 * Runs after mve_min_order_qty (9999) so it has the final word.
 */
function mv_case_moq_rule( $product_id ) {
	/* The ACF field is what staff edit and what the product page prints, so
	   it is the source of truth. The _mv_ meta is the fallback for anything
	   set through the WooCommerce Inventory select instead. Reading only the
	   _mv_ one let a wine show "6 x 75cl" while being sold in 12s. */
	$per = (int) get_post_meta( $product_id, 'bottles_per_case', true );
	if ( $per < 1 ) { $per = (int) get_post_meta( $product_id, '_mv_bottles_per_case', true ); }
	if ( $per < 1 ) { $per = 6; }
	return ( 12 === $per ) ? array( 1, 1, $per ) : array( 2, 2, $per );
}

function mv_case_moq_args( $args, $product ) {
	list( $min, $step ) = mv_case_moq_rule( $product->get_id() );
	$args['min_value'] = $min;
	$args['step']      = $step;
	if ( empty( $args['input_value'] ) || $args['input_value'] < $min ) {
		$args['input_value'] = $min;
	}
	return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'mv_case_moq_args', 10000, 2 );

function mv_case_moq_step( $step, $product ) {
	list( $min, $s ) = mv_case_moq_rule( $product->get_id() );
	return $s;
}
add_filter( 'woocommerce_quantity_input_step', 'mv_case_moq_step', 10000, 2 );

/**
 * Case-based quantities — the ONLY thing that writes min and step.
 *
 * Three separate scripts used to write these numbers and they disagreed: the
 * theme's legacy enforcer put back the old per-bottle rule (min 6, step 1) 60ms
 * after every click, while this one wanted cases (min 2, step 2). Pressing minus
 * decremented on the 1-ladder and then had the result snapped back up onto the
 * 2-ladder, landing on the number it started from — so the button looked dead.
 * The legacy enforcer is unhooked below and this is now the single writer.
 */
function mv_unhook_legacy_quantity_enforcer() {
	remove_action( 'wp_footer', 'mve_quantity_enforcer', 5 );
}
add_action( 'wp', 'mv_unhook_legacy_quantity_enforcer', 20 );

function mv_case_stepper_js() {
	if ( ! function_exists( 'is_product' ) ) {
		return;
	}

	$rules = array();

	if ( is_product() ) {
		global $post;
		if ( $post ) {
			$rules[ (int) $post->ID ] = mv_case_moq_rule( $post->ID );
		}
	}

	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $line ) {
			$pid = isset( $line['product_id'] ) ? (int) $line['product_id'] : 0;
			if ( $pid ) {
				$rules[ $pid ] = mv_case_moq_rule( $pid );
			}
		}
	}

	if ( ! $rules ) {
		return;
	}

	$map = array();
	foreach ( $rules as $pid => $rule ) {
		$map[ (string) $pid ] = array( 'min' => $rule[0], 'step' => $rule[1] );
	}
	?>
	<script id="mv-case-quantities">
	(function () {
		var RULES = <?php echo wp_json_encode( $map ); ?>;
		var IDS   = Object.keys( RULES );

		/* A quantity box and nothing else. The old version applied to any input
		   it was handed, which rewrote the enquiry form's hidden fields to the
		   minimum: action, product_id and the redirect all became "6" and the
		   enquiry silently went nowhere. */
		function isQty( el ) {
			if ( ! el || el.tagName !== 'INPUT' || el.type === 'hidden' ) { return false; }
			return el.classList.contains( 'qty' )
				|| el.name === 'quantity'
				|| el.name.indexOf( 'cart[' ) === 0;
		}

		function ruleFor( input ) {
			var form = input.closest( 'form' );
			if ( form ) {
				var add = form.querySelector( '[name="add-to-cart"]' );
				if ( add && add.value && RULES[ add.value ] ) { return RULES[ add.value ]; }
			}

			var row = input.closest( 'tr, li, .wc-block-cart-item' );
			if ( row ) {
				var link = row.querySelector( 'a.remove[data-product_id]' );
				if ( link && RULES[ link.getAttribute( 'data-product_id' ) ] ) {
					return RULES[ link.getAttribute( 'data-product_id' ) ];
				}
			}

			return IDS.length === 1 ? RULES[ IDS[0] ] : null;
		}

		function apply( input, first ) {
			if ( ! isQty( input ) ) { return; }

			var rule = ruleFor( input );
			if ( ! rule ) { return; }

			input.setAttribute( 'min', String( rule.min ) );
			input.setAttribute( 'step', String( rule.step ) );

			/* The stepper inline in single-product.php reads these off the
			   wrapper once at load and copies them onto the input, so if they
			   still say the old numbers it counts in the wrong increments. */
			/* Two nested wrappers carry these — div.qty outside div.quantity —
			   and the inline stepper seeds itself from the OUTER one, so
			   correcting only the nearest left the old numbers in the markup. */
			var node = input.parentElement;
			while ( node && node !== document.body ) {
				if ( node.matches && node.matches( '.qty, .quantity' ) ) {
					node.setAttribute( 'data-min', String( rule.min ) );
					node.setAttribute( 'data-step', String( rule.step ) );
				}
				node = node.parentElement;
			}

			var val  = parseFloat( input.value );
			var cart = input.closest( 'form.cart' );

			/* On a product page the box should open at the minimum. The inline
			   stepper runs before this one and copies the legacy minimum onto
			   it, which is why it opened at 6. In the basket the number is the
			   customer's own line quantity, so there it is only snapped. */
			if ( first && cart ) {
				input.value = String( rule.min );
				return;
			}

			if ( isNaN( val ) || val < rule.min ) {
				input.value = String( rule.min );
				return;
			}

			var snapped = rule.min + Math.round( ( val - rule.min ) / rule.step ) * rule.step;
			if ( snapped !== val ) { input.value = String( snapped ); }
		}

		function sweep( first ) {
			document.querySelectorAll( 'input.qty, input[name="quantity"], input[name^="cart["]' ).forEach( function ( el ) { apply( el, first ); } );
		}

		sweep( true );

		/* WooCommerce replaces the basket over AJAX, so a one-off pass is not
		   enough. Note this corrects boxes as they appear and does NOT re-run
		   after every click — re-running was what fought the minus button. */
		if ( window.MutationObserver ) {
			new MutationObserver( function ( records ) {
				for ( var i = 0; i < records.length; i++ ) {
					for ( var j = 0; j < records[i].addedNodes.length; j++ ) {
						var node = records[i].addedNodes[j];
						if ( node.nodeType !== 1 ) { continue; }
						if ( isQty( node ) ) { apply( node, false ); }
						else if ( node.querySelectorAll ) {
							node.querySelectorAll( 'input.qty, input[name="quantity"], input[name^="cart["]' ).forEach( function ( el ) { apply( el, false ); } );
						}
					}
				}
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mv_case_stepper_js', 999 );

/**
 * The carrier line is not the delivery charge.
 *
 * Delivery is billed as a separate EHD fee, so the shipping method is set
 * to zero on purpose — otherwise the customer pays twice. WooCommerce then
 * labels that line "Free shipping — FREE", which reads as free delivery.
 * Renaming the rate itself is the only reliable way to change it: the
 * instance title is not what the block cart and checkout display.
 */
/**
 * No shipping row in the cart totals at all.
 *
 * Delivery is charged as a fee, worked out from the approved postcode and the
 * EHD pallet bands. WooCommerce shipping is not used for it. This function
 * used to relabel the Free Shipping rate and leave it in place, which put a
 * second delivery line reading FREE directly above the real charge - read
 * plainly, the totals appeared to offer free delivery and then charge for it.
 */
function mv_rename_shipping_rate( $rates ) {
	return array();
}

// Belt and braces: with this off, shipping is never calculated or rendered.
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );

/**
 * Say cases and bottles on every basket line.
 *
 * The quantity box shows a bare number, which on a trade site is ambiguous -
 * "4" could be four bottles or four cases. Everything here is ordered by the
 * case, so the line spells out both.
 */
add_filter( 'woocommerce_get_item_data', 'mv_cart_item_case_note', 10, 2 );
function mv_cart_item_case_note( $data, $cart_item ) {
	if ( ! function_exists( 'mv_bottles_per_case' ) || empty( $cart_item['product_id'] ) ) {
		return $data;
	}

	$per = (int) mv_bottles_per_case( $cart_item['product_id'] );
	$qty = (int) ( $cart_item['quantity'] ?? 0 );
	if ( $per < 1 || $qty < 1 ) {
		return $data;
	}

	$data[] = array(
		'key'     => __( 'Quantity', 'maison-vintique' ),
		'name'    => __( 'Quantity', 'maison-vintique' ),
		'display' => sprintf(
			/* translators: 1: cases, 2: bottles per case, 3: total bottles */
			__( '%1$d case%2$s × %3$d bottles (%4$d bottles)', 'maison-vintique' ),
			$qty,
			1 === $qty ? '' : 's',
			$per,
			$qty * $per
		),
		'value'   => sprintf(
			__( '%1$d case%2$s × %3$d bottles (%4$d bottles)', 'maison-vintique' ),
			$qty,
			1 === $qty ? '' : 's',
			$per,
			$qty * $per
		),
	);

	return $data;
}
add_filter( 'woocommerce_package_rates', 'mv_rename_shipping_rate', 100 );

/**
 * One-off import of the finalised Privacy Notice and Terms of Trade.
 * Fetches the approved copy and writes it into the two pages, then
 * disables itself. Safe to delete once it has run.
 */
function mv_import_legal_pages() {
	if ( ! is_admin() || 'done-v2' === get_option( 'mv_legal_import' ) ) { return; }
	$src = array(
		1298 => array( 'https://floralwhite-mole-740454.hostingersite.com/legal/privacy.html', 'Privacy Notice' ),
		1480 => array( 'https://floralwhite-mole-740454.hostingersite.com/legal/terms.html', 'Terms and Conditions of Trade' ),
	);
	$log = array();
	foreach ( $src as $pid => $bits ) {
		$r = wp_remote_get( $bits[0], array( 'timeout' => 20 ) );
		if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) { $log[] = $pid . ':fetch-failed'; continue; }
		$bodyhtml = wp_remote_retrieve_body( $r );
		if ( strlen( $bodyhtml ) < 2000 ) { $log[] = $pid . ':too-short'; continue; }
		$ok = wp_update_post( array( 'ID' => $pid, 'post_title' => $bits[1], 'post_content' => $bodyhtml, 'post_status' => 'publish' ), true );
		$log[] = $pid . ':' . ( is_wp_error( $ok ) ? $ok->get_error_message() : 'ok ' . strlen( $bodyhtml ) );
	}
	update_option( 'mv_legal_import', 'done-v2' );
	update_option( 'mv_legal_import_log', implode( ' | ', $log ) );
}
add_action( 'admin_init', 'mv_import_legal_pages' );

/**
 * The policy template renders an ACF repeater and only falls back to the
 * page editor when that repeater is empty. The finalised notices were saved
 * into the page content correctly but never shown, because the old repeater
 * rows were still there. Emptying it lets the real content through.
 */
function mv_clear_policy_repeater() {
	if ( ! is_admin() || 'done' === get_option( 'mv_policy_repeater_cleared' ) ) { return; }
	foreach ( array( 1298, 1480 ) as $pid ) {
		update_post_meta( $pid, 'policy_sections', 0 );
	}
	update_option( 'mv_policy_repeater_cleared', 'done' );
}
add_action( 'admin_init', 'mv_clear_policy_repeater', 11 );

/** Put the real business mobile on the Contact page, replacing the placeholder. */
function mv_set_business_phone() {
	if ( ! is_admin() || 'done' === get_option( 'mv_phone_set' ) ) { return; }
	$hits = 0;
	foreach ( get_post_meta( 742 ) as $key => $vals ) {
		foreach ( (array) $vals as $v ) {
			if ( ! is_string( $v ) ) { continue; }
			$digits = preg_replace( '/[^0-9]/', '', $v );
			if ( '' !== $digits && false !== strpos( $digits, '000000000' ) ) {
				update_post_meta( 742, $key, '07706 544676' );
				$hits++;
			}
		}
	}
	update_option( 'mv_phone_set', 'done' );
	update_option( 'mv_phone_set_log', $hits . ' field(s) updated' );
}
add_action( 'admin_init', 'mv_set_business_phone', 12 );

/* ------------------------------------------------------------------
 * Trade portal rules from the client's review documents.
 * ---------------------------------------------------------------- */
function mv_trade_portal_rules() { return true; }

/* Orders are referenced SO-1546, not #1546, everywhere the customer sees them. */
function mv_order_number( $number, $order ) {
	return 'SO-' . $order->get_order_number_base_ref();
}
add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	$raw = is_object( $order ) ? $order->get_id() : $number;
	return 0 === strpos( (string) $number, 'SO-' ) ? $number : 'SO-' . $raw;
}, 10, 2 );

/* The delivery address is the one approved on the application. Customers
   must not be able to change it themselves — a different address is a
   request for Maison Vintique to approve, not a free edit. */
add_filter( 'woocommerce_my_account_get_addresses', function ( $addresses ) {
	unset( $addresses['shipping'] );
	return $addresses;
} );

add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'edit-address' ) ) { return; }
	wc_add_notice( 'Your delivery address is the one approved on your trade account. To change it, please contact Maison Vintique and we will update it once approved.', 'notice' );
	wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
	exit;
} );

/* Pro forma only — there are no saved cards to manage. */
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	unset( $items['payment-methods'] );
	unset( $items['edit-address'] );
	return $items;
}, 20 );


/* ------------------------------------------------------------------
 * Staff notifications.
 *
 * Enquiries and trade applications were not notifying anybody. There
 * was no wp_mail call in the enquiry code at all, so nothing was ever
 * sent — the address was not wrong, the email did not exist.
 * ---------------------------------------------------------------- */
if ( ! defined( 'MV_NOTIFY_EMAIL' ) ) { define( 'MV_NOTIFY_EMAIL', 'contact@MaisonVintique.com' ); }

/* Everything that reads the site address now reaches the trade inbox. */
add_filter( 'pre_option_admin_email', function () { return MV_NOTIFY_EMAIL; } );

function mv_notify_new_record( $post_id, $post, $update ) {
	if ( $update || wp_is_post_revision( $post_id ) ) { return; }
	$type = get_post_type( $post_id );
	if ( ! preg_match( '/enquir|applic/i', (string) $type ) ) { return; }

	$lines = array();
	foreach ( get_post_meta( $post_id ) as $k => $v ) {
		if ( '_' === substr( $k, 0, 1 ) ) { continue; }
		$val = is_array( $v ) ? reset( $v ) : $v;
		if ( ! is_string( $val ) || '' === trim( $val ) ) { continue; }
		$lines[] = ucwords( str_replace( '_', ' ', $k ) ) . ': ' . wp_strip_all_tags( $val );
	}

	$is_app  = false !== stripos( $type, 'applic' );
	$what    = $is_app ? 'trade account application' : 'trade enquiry';
	$title   = get_the_title( $post_id );
	$body    = "A new " . $what . " has been received on the website." . "\n\n"
		. ( $title ? 'Reference: ' . $title . "\n\n" : '' )
		. implode( "\n", array_slice( $lines, 0, 40 ) )
		. "\n\nReview it in the CRM:" . "\n" . "https://floralwhite-mole-740454.hostingersite.com/admin";

	wp_mail( MV_NOTIFY_EMAIL, 'Maison Vintique — new ' . $what, $body );
}
add_action( 'wp_insert_post', 'mv_notify_new_record', 10, 3 );


/* ------------------------------------------------------------------
 * Order review doc: a way back to the wines from the basket, and a
 * link to the portfolio from the account dashboard.
 * ---------------------------------------------------------------- */
function mv_continue_browsing() {
	if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) { return; }
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	?>
	<script id="mv-continue-browsing">
	(function () {
		var HREF = <?php echo wp_json_encode( $shop ); ?>;

		/* The basket is a block, so there is no PHP hook to render into.
		   Place the link beside the checkout button once it exists. */
		function place() {
			if ( document.getElementById( "mv-keep-shopping" ) ) { return true; }
			var btn = document.querySelector( ".wc-block-cart__submit-button, .wc-block-components-checkout-place-order-button, .checkout-button" );
			if ( ! btn ) { return false; }
			var a = document.createElement( "a" );
			a.id = "mv-keep-shopping";
			a.href = HREF;
			a.textContent = "\u2190 Continue browsing";
			a.style.cssText = "display:block;text-align:center;margin-top:12px;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#4B1E24;text-decoration:none";
			( btn.parentNode || btn ).appendChild( a );
			return true;
		}

		if ( ! place() && window.MutationObserver ) {
			var mo = new MutationObserver( function () { if ( place() ) { mo.disconnect(); } } );
			mo.observe( document.body, { childList: true, subtree: true } );
			window.setTimeout( function () { mo.disconnect(); }, 15000 );
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mv_continue_browsing', 998 );

/* Browse the portfolio, from the account dashboard. */
add_action( 'woocommerce_account_dashboard', function () {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	echo '<p style="margin-top:18px"><a class="button" href=' . esc_url( $shop ) . '>Browse the portfolio</a></p>';
}, 20 );


/* ------------------------------------------------------------------
 * Mail relay for the CRM.
 *
 * WordPress is where the working mailbox is, so the two emails that
 * start life in the CRM — account statements and emailed invoices —
 * are handed here to be sent, rather than keeping a second set of mail
 * credentials in the CRM.
 * ---------------------------------------------------------------- */
function mv_send_mail_endpoint() {
	register_rest_route( 'mv/v1', '/send-mail', array(
		'methods'  => 'POST',
		'permission_callback' => '__return_true',
		'callback' => 'mv_send_mail_handler',
	) );
}
add_action( 'rest_api_init', 'mv_send_mail_endpoint' );

function mv_send_mail_handler( $request ) {
	if ( ! defined( 'MV_CRM_BRIDGE_KEY' ) || ! hash_equals( MV_CRM_BRIDGE_KEY, (string) $request->get_param( 'key' ) ) ) {
		return new WP_Error( 'mv_forbidden', 'Bad key', array( 'status' => 403 ) );
	}

	$to      = sanitize_email( (string) $request->get_param( 'to' ) );
	$subject = wp_strip_all_tags( (string) $request->get_param( 'subject' ) );
	$body    = (string) $request->get_param( 'body' );

	if ( ! is_email( $to ) || '' === $subject ) {
		return new WP_Error( 'mv_bad_request', 'A valid recipient and subject are required', array( 'status' => 400 ) );
	}

	$attachments = array();
	$tmp         = null;
	$file        = (string) $request->get_param( 'file' );
	$filename    = sanitize_file_name( (string) $request->get_param( 'filename' ) );

	if ( '' !== $file && '' !== $filename ) {
		$bytes = base64_decode( $file, true );
		if ( false !== $bytes && strlen( $bytes ) < 8000000 ) {
			$dir = get_temp_dir() . 'mv-mail/';
			wp_mkdir_p( $dir );
			$tmp = $dir . wp_unique_filename( $dir, $filename );
			file_put_contents( $tmp, $bytes );
			$attachments[] = $tmp;
		}
	}

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$sent    = wp_mail( $to, $subject, $body, $headers, $attachments );

	if ( $tmp && file_exists( $tmp ) ) { @unlink( $tmp ); }

	return rest_ensure_response( array(
		'ok'     => (bool) $sent,
		'reason' => $sent ? null : 'WordPress could not send the message',
	) );
}


/* ------------------------------------------------------------------
 * How to pay. Both wordings are the client’s own, used verbatim.
 * ---------------------------------------------------------------- */
function mv_how_to_pay() {
	$bacs = "Bank details will be provided on your pro-forma invoice. Please quote your invoice number as the payment reference. Orders will be released for delivery once cleared funds have been received.";
	$wallet = "Electronic wallet payments, including Google Pay and Apple Pay, are accepted where processed through our approved payment provider and can be matched to the approved customer account and order.";
	$css = "border:1px solid #E7E1D6;border-radius:12px;padding:18px 20px;margin-top:22px;background:#FCFAF6";
	return '<section class="mv-how-to-pay" style="' . esc_attr( $css ) . '">'
		. '<h3 style="margin:0 0 12px;font-size:15px;letter-spacing:.04em">How to pay</h3>'
		. '<p style="margin:0 0 6px;font-weight:600;font-size:13px">Bank Transfer / BACS / Faster Payments</p>'
		. '<p style="margin:0 0 14px;font-size:13px;line-height:1.6">' . esc_html( $bacs ) . '</p>'
		. '<p style="margin:0 0 6px;font-weight:600;font-size:13px">Electronic wallet payments</p>'
		. '<p style="margin:0;font-size:13px;line-height:1.6">' . esc_html( $wallet ) . '</p>'
		. '</section>';
}

add_action( 'woocommerce_account_dashboard', function () { echo mv_how_to_pay(); }, 30 );

/* Also on the basket, so it is read before payment rather than after. */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) { return; }
	?>
	<script id="mv-how-to-pay-js">
	(function () {
		var HTML = <?php echo wp_json_encode( mv_how_to_pay() ); ?>;
		function place() {
			if ( document.querySelector( ".mv-how-to-pay" ) ) { return true; }
			var totals = document.querySelector( ".wp-block-woocommerce-cart-order-summary-block, .wc-block-components-totals-wrapper, .cart_totals" );
			if ( ! totals ) { return false; }
			var box = document.createElement( "div" );
			box.innerHTML = HTML;
			( totals.parentNode || totals ).appendChild( box.firstChild );
			return true;
		}
		if ( ! place() && window.MutationObserver ) {
			var mo = new MutationObserver( function () { if ( place() ) { mo.disconnect(); } } );
			mo.observe( document.body, { childList: true, subtree: true } );
			window.setTimeout( function () { mo.disconnect(); }, 15000 );
		}
	})();
	</script>
	<?php
}, 997 );


/* ------------------------------------------------------------------ *
 * Restored 7 Sep 2026. These five blocks were written on 4-5 Sep and
 * lost when the 4 Sep backup was restored; re-applied verbatim in
 * intent, re-checked against the current schema.
 * ------------------------------------------------------------------ */

/**
 * Hold back WooCommerce's own "your account has been created" email.
 *
 * Trade accounts are vetted by hand, so the account exists for days before
 * anybody may use it. Woo's stock email goes out at registration and invites
 * people to sign in to a portal that will turn them away (see
 * mve_block_unapproved_login). The theme already sends mve_trade_approved the
 * moment the status actually flips, so all this does is stop the duplicate
 * arriving days too early.
 */
add_filter( 'woocommerce_email_enabled_customer_new_account', 'mv_gate_new_account_email', 10, 2 );
function mv_gate_new_account_email( $enabled, $user = null ) {
	if ( ! function_exists( 'mve_trade_status' ) ) {
		return $enabled;
	}

	$user_id = 0;
	if ( $user instanceof WP_User ) {
		$user_id = (int) $user->ID;
	} elseif ( is_numeric( $user ) ) {
		$user_id = (int) $user;
	}

	// Not a trade applicant we can place: leave Woo's own decision alone.
	if ( ! $user_id ) {
		return $enabled;
	}

	return ( 'approved' === mve_trade_status( $user_id ) );
}

/**
 * Ask for a delivery address only when there is a different one to give.
 *
 * The application asks "same as your trading address?" first. Without this the
 * second box just sits there and most applicants retype the trading address
 * into it, usually slightly differently, and then the address we approve them
 * on disagrees with the one we deliver to.
 *
 * Deliberately selector-driven rather than markup-driven: the schema renders
 * these fields, so keying off the field names survives a change of wrapper.
 */
add_action( 'wp_footer', 'mv_delivery_same_toggle', 998 );
function mv_delivery_same_toggle() {
	if ( is_admin() ) {
		return;
	}
	?>
	<script>
	(function () {
		function wrapperOf( el, form ) {
			var n = el;
			while ( n && n.parentElement && n.parentElement !== form ) {
				n = n.parentElement;
				if ( n.tagName === 'FIELDSET' || n.tagName === 'FORM' ) { return el.parentElement; }
			}
			return n || el.parentElement;
		}

		function wire() {
			var addr = document.querySelector( '[name="delivery_address"], [name="mvta_delivery_address"]' );
			var same = document.querySelectorAll( '[name="delivery_same"], [name="mvta_delivery_same"]' );
			if ( ! addr || ! same.length || addr.getAttribute( 'data-mv-toggle' ) ) { return; }
			addr.setAttribute( 'data-mv-toggle', '1' );

			var form = addr.form || document;
			var box  = wrapperOf( addr, form );

			function sync() {
				var picked = '';
				for ( var i = 0; i < same.length; i++ ) {
					if ( same[ i ].checked ) { picked = same[ i ].value; }
				}
				// Nothing picked yet: keep it hidden rather than showing a box
				// the applicant has not been asked for.
				var show = ( picked === 'no' );
				box.style.display = show ? '' : 'none';
				if ( ! show ) { addr.value = ''; }
				addr.required = show;
			}

			for ( var i = 0; i < same.length; i++ ) {
				same[ i ].addEventListener( 'change', sync );
			}
			sync();
		}

		if ( document.readyState !== 'loading' ) { wire(); }
		else { document.addEventListener( 'DOMContentLoaded', wire ); }
		// The application form can arrive in the invite popup after load.
		if ( window.MutationObserver ) {
			new MutationObserver( wire ).observe( document.body, { childList: true, subtree: true } );
		}
	})();
	</script>
	<?php
}
/**
 * Create the Cookie Notice page once.
 *
 * The banner has to link somewhere before it is lawful to show it, and the
 * footer wants the same link, so the page is made here rather than by hand -
 * a restore that loses the page would otherwise leave both pointing at a 404.
 */
add_action( 'init', 'mv_make_cookie_page', 20 );
function mv_make_cookie_page() {
	if ( get_option( 'mv_cookie_page_done' ) ) {
		return;
	}

	$existing = get_page_by_path( 'cookie-notice' );
	if ( $existing ) {
		update_option( 'mv_cookie_page_done', (int) $existing->ID );
		return;
	}

	$body  = '<p>This notice explains the cookies Maison Vintique sets, and why.</p>';
	$body .= '<h2>Strictly necessary</h2>';
	$body .= '<p>These keep the site working and cannot be switched off. They remember what is in your basket, keep you signed in to your trade account, record that you have confirmed you are of legal drinking age, and carry the security token that protects forms from misuse. They are not used to advertise to you.</p>';
	$body .= '<h2>Preferences</h2>';
	$body .= '<p>One cookie records that you have seen and answered the cookie banner, so that we do not ask again on every page.</p>';
	$body .= '<h2>Analytics</h2>';
	$body .= '<p>We do not load analytics or advertising cookies. If that changes, this page will be updated first and the banner will ask before anything is set.</p>';
	$body .= '<h2>Managing cookies</h2>';
	$body .= '<p>You can clear or block cookies in your browser settings. Blocking the strictly necessary ones will stop the basket and trade account from working. You can also reopen the banner at any time using the Cookie settings link in the footer.</p>';
	$body .= '<p>Questions about this notice: <a href="mailto:contact@MaisonVintique.com">contact@MaisonVintique.com</a></p>';

	$id = wp_insert_post(
		array(
			'post_title'   => 'Cookie Notice',
			'post_name'    => 'cookie-notice',
			'post_content' => $body,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);

	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'mv_cookie_page_done', (int) $id );
	}
}

/**
 * Cookie banner, plus the footer link that reopens it.
 *
 * Nothing here gates a script, because the site sets no analytics or
 * advertising cookies - the banner tells people what is set and points at the
 * notice. Kept as plain markup in the footer so it does not depend on a
 * plugin surviving the next restore.
 */
add_action( 'wp_footer', 'mv_cookie_banner', 999 );
function mv_cookie_banner() {
	if ( is_admin() ) {
		return;
	}

	$notice = get_page_by_path( 'cookie-notice' );
	$url    = $notice ? get_permalink( $notice ) : home_url( '/cookie-notice/' );
	?>
	<style>
	.mv-ck{position:fixed;left:0;right:0;bottom:0;z-index:99990;display:none;
		background:#1c1c1c;color:#f2ede4;padding:18px 22px;
		font-size:14px;line-height:1.55;box-shadow:0 -2px 18px rgba(0,0,0,.25)}
	.mv-ck.is-on{display:block}
	.mv-ck__in{max-width:1100px;margin:0 auto;display:flex;gap:20px;
		align-items:center;flex-wrap:wrap;justify-content:space-between}
	.mv-ck__t{flex:1 1 420px;margin:0}
	.mv-ck__t a{color:#e8d9b8;text-decoration:underline}
	.mv-ck__b{border:1px solid #e8d9b8;background:#e8d9b8;color:#1c1c1c;
		padding:10px 22px;cursor:pointer;font:inherit;letter-spacing:.04em}
	.mv-ck__b--ghost{background:none;color:#e8d9b8}
	.mv-ck__acts{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
	.mv-ck__now{flex-basis:100%;margin:4px 0 0;opacity:.75;font-size:13px}
	@media(max-width:600px){.mv-ck{padding:16px}.mv-ck__in{gap:12px}}
	</style>
	<div class="mv-ck" id="mv-ck" role="region" aria-label="Cookie notice">
		<div class="mv-ck__in">
			<p class="mv-ck__t">
				We use cookies that are necessary for the basket, your trade account and
				age confirmation. We would also like to measure how the site is used, but
				only if you agree — nothing beyond the necessary ones is set unless you accept.
				<a href="<?php echo esc_url( $url ); ?>">Read our cookie notice</a>
			</p>
			<div class="mv-ck__acts">
				<button type="button" class="mv-ck__b" data-mv-ck="all">Accept</button>
				<button type="button" class="mv-ck__b mv-ck__b--ghost" data-mv-ck="essential">Necessary only</button>
			</div>
			<p class="mv-ck__now" id="mv-ck-now" hidden></p>
		</div>
	</div>
	<script>
	(function () {
		var KEY = 'mvCookieChoice';
		var el  = document.getElementById( 'mv-ck' );
		var now = document.getElementById( 'mv-ck-now' );
		if ( ! el ) { return; }

		function read() {
			try { return window.localStorage.getItem( KEY ); }
			catch ( e ) { return 'essential'; } // storage blocked: assume the safer answer
		}
		function write( v ) {
			try { window.localStorage.setItem( KEY, v ); } catch ( e ) {}
		}

		/*
		 * Anything non-essential must wait for this. Nothing listens yet because
		 * no analytics are installed; when one is added it hooks this event (and
		 * checks window.mvCookieConsent on load) rather than firing on its own.
		 */
		function publish( v ) {
			window.mvCookieConsent = v;
			try {
				document.dispatchEvent( new CustomEvent( 'mv-cookie-consent', { detail: v } ) );
			} catch ( e ) {}
		}

		function show() {
			var v = read();
			if ( v && now ) {
				now.textContent = 'Your current choice: '
					+ ( v === 'all' ? 'all cookies accepted.' : 'necessary cookies only.' )
					+ ' You can change it here.';
				now.hidden = false;
			}
			el.classList.add( 'is-on' );
		}

		el.addEventListener( 'click', function ( e ) {
			var v = e.target.getAttribute( 'data-mv-ck' );
			if ( v !== 'all' && v !== 'essential' ) { return; }
			write( v );
			publish( v );
			el.classList.remove( 'is-on' );
		});

		// Footer link, so a choice can be changed or withdrawn at any time.
		document.addEventListener( 'click', function ( e ) {
			var t = e.target.closest ? e.target.closest( '#mv-cookie-settings, .mv-cookie-settings' ) : null;
			if ( t ) { e.preventDefault(); show(); }
		});

		var saved = read();
		publish( saved || 'essential' );
		if ( ! saved ) { show(); }
	})();
	</script>
	<?php
}

/**
 * "Delivery address" in My Account.
 *
 * Read-only on purpose. The address is agreed at approval and the licence
 * conditions are written against it, so a customer changing it themselves at
 * checkout would put us outside our own AWRS terms. They can see what we hold
 * and ask for it to be changed; the change is made in the CRM.
 */
add_action( 'init', 'mv_delivery_addresses_endpoint' );
function mv_delivery_addresses_endpoint() {
	add_rewrite_endpoint( 'delivery-addresses', EP_ROOT | EP_PAGES );

	// One flush, the first time the endpoint exists, so the URL resolves
	// without anyone having to re-save Permalinks by hand.
	if ( ! get_option( 'mv_delivery_ep_flushed' ) ) {
		flush_rewrite_rules( false );
		update_option( 'mv_delivery_ep_flushed', 1 );
	}
}

add_filter( 'query_vars', 'mv_delivery_query_var' );
function mv_delivery_query_var( $vars ) {
	$vars[] = 'delivery-addresses';
	return $vars;
}

add_filter( 'woocommerce_account_menu_items', 'mv_delivery_menu_item', 20 );
function mv_delivery_menu_item( $items ) {
	$out = array();
	foreach ( $items as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'orders' === $key ) {
			$out['delivery-addresses'] = __( 'Delivery address', 'maison-vintique' );
		}
	}
	if ( ! isset( $out['delivery-addresses'] ) ) {
		$out['delivery-addresses'] = __( 'Delivery address', 'maison-vintique' );
	}
	return $out;
}

add_action( 'woocommerce_account_delivery-addresses_endpoint', 'mv_delivery_addresses_content' );
function mv_delivery_addresses_content() {
	if ( ! get_current_user_id() ) {
		return;
	}

	$addresses = function_exists( 'mv_customer_addresses' ) ? mv_customer_addresses() : array();

	echo '<h3>' . esc_html__( 'Where we deliver', 'maison-vintique' ) . '</h3>';

	if ( $addresses ) {
		echo '<table class="shop_table shop_table_responsive" style="margin-bottom:26px"><thead><tr>'
			. '<th>' . esc_html__( 'Address', 'maison-vintique' ) . '</th>'
			. '<th>' . esc_html__( 'Status', 'maison-vintique' ) . '</th>'
			. '</tr></thead><tbody>';

		foreach ( $addresses as $a ) {
			$status = (string) ( $a['status'] ?? '' );
			$isOk   = ( 'Approved' === $status );
			$colour = $isOk ? '#2E7D5B' : ( 'Pending' === $status ? '#8A6D1F' : '#8A8072' );

			echo '<tr><td>' . esc_html( (string) ( $a['line'] ?? '' ) ) . '</td>'
				. '<td><span style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:' . esc_attr( $colour ) . '">'
				. esc_html( $status ) . '</span>';

			if ( ! $isOk ) {
				echo '<div style="font-size:12px;color:#6b635e">'
					. esc_html__( 'Cannot be used until approved.', 'maison-vintique' ) . '</div>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	} else {
		echo '<p>' . esc_html__( 'We do not have a delivery address on file yet. It is taken from your trade application once your account is approved.', 'maison-vintique' ) . '</p>';
	}

	echo '<p style="font-size:13px;color:#6b635e;max-width:62ch">'
		. esc_html__( 'Your approved address is the one your delivery charge is worked out from, and it cannot be changed at checkout. To deliver somewhere else, ask below — we check every new address before it can be used.', 'maison-vintique' )
		. '</p>';

	$nonce = wp_create_nonce( 'mv_addr' );
	?>
	<h3 style="margin-top:30px"><?php esc_html_e( 'Request another delivery address', 'maison-vintique' ); ?></h3>

	<form id="mv-addr-form" class="woocommerce-EditAccountForm" style="max-width:640px">
		<p class="form-row form-row-wide">
			<label for="mv-a-label"><?php esc_html_e( 'Name for this address', 'maison-vintique' ); ?>
				<span style="color:#8A8072">(<?php esc_html_e( 'optional', 'maison-vintique' ); ?>)</span></label>
			<input class="input-text" type="text" id="mv-a-label" name="label" placeholder="<?php esc_attr_e( 'Second site, event venue…', 'maison-vintique' ); ?>">
		</p>
		<p class="form-row form-row-wide">
			<label for="mv-a-line1"><?php esc_html_e( 'Address line 1', 'maison-vintique' ); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" id="mv-a-line1" name="line1" required>
		</p>
		<p class="form-row form-row-wide">
			<label for="mv-a-line2"><?php esc_html_e( 'Address line 2', 'maison-vintique' ); ?></label>
			<input class="input-text" type="text" id="mv-a-line2" name="line2">
		</p>
		<p class="form-row form-row-first">
			<label for="mv-a-city"><?php esc_html_e( 'Town or city', 'maison-vintique' ); ?></label>
			<input class="input-text" type="text" id="mv-a-city" name="city">
		</p>
		<p class="form-row form-row-last">
			<label for="mv-a-pc"><?php esc_html_e( 'Postcode', 'maison-vintique' ); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" id="mv-a-pc" name="postcode" required>
		</p>
		<p class="form-row form-row-wide">
			<label for="mv-a-type"><?php esc_html_e( 'What sort of premises is it?', 'maison-vintique' ); ?></label>
			<select class="input-text" id="mv-a-type" name="location_type">
				<option value=""><?php esc_html_e( 'Please choose…', 'maison-vintique' ); ?></option>
				<option value="Trading premises"><?php esc_html_e( 'Licensed trading premises', 'maison-vintique' ); ?></option>
				<option value="Warehouse or storage"><?php esc_html_e( 'Warehouse or storage', 'maison-vintique' ); ?></option>
				<option value="Event or venue"><?php esc_html_e( 'Event or venue', 'maison-vintique' ); ?></option>
				<option value="Other"><?php esc_html_e( 'Other', 'maison-vintique' ); ?></option>
			</select>
		</p>
		<p class="form-row form-row-wide">
			<label for="mv-a-notes"><?php esc_html_e( 'Anything we should know', 'maison-vintique' ); ?></label>
			<textarea class="input-text" id="mv-a-notes" name="notes" rows="3"></textarea>
		</p>
		<p class="form-row">
			<button type="submit" class="woocommerce-Button button"><?php esc_html_e( 'Send for approval', 'maison-vintique' ); ?></button>
		</p>
		<div id="mv-addr-msg" style="margin-top:10px;font-size:14px" role="status"></div>
	</form>

	<script>
	(function () {
		var f = document.getElementById( 'mv-addr-form' );
		if ( ! f ) { return; }
		var msg = document.getElementById( 'mv-addr-msg' );

		f.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var btn = f.querySelector( 'button[type=submit]' );
			btn.disabled = true;
			msg.style.color = '#6b635e';
			msg.textContent = <?php echo wp_json_encode( __( 'Sending…', 'maison-vintique' ) ); ?>;

			var body = new FormData( f );
			body.append( 'action', 'mv_request_address' );
			body.append( 'nonce', <?php echo wp_json_encode( $nonce ); ?> );

			fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
				method: 'POST', credentials: 'same-origin', body: body
			} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) {
				var ok = d && d.success;
				msg.style.color = ok ? '#2E7D5B' : '#B3261E';
				msg.textContent = ( d && d.data && d.data.message ) || 'Something went wrong.';
				if ( ok ) { f.reset(); window.setTimeout( function () { location.reload(); }, 2500 ); }
				btn.disabled = false;
			} )
			.catch( function () {
				msg.style.color = '#B3261E';
				msg.textContent = 'Something went wrong. Please email us.';
				btn.disabled = false;
			} );
		} );
	})();
	</script>
	<?php
}

/**
 * "Cookie settings" in the footer's legal menu.
 *
 * The banner reopens from anything carrying this id, but nothing linked to it,
 * so a visitor who had already answered had no way back to change their mind —
 * which is precisely what the notice promises them. Added to the legal menu by
 * filter rather than by hand so it cannot be lost if the menu is re-saved.
 */
add_filter( 'wp_nav_menu_items', 'mv_cookie_settings_link', 10, 2 );
function mv_cookie_settings_link( $items, $args ) {
	if ( empty( $args->theme_location ) || 'footer-legal' !== $args->theme_location ) {
		return $items;
	}

	return $items
		. '<li class="menu-item menu-item-mv-cookie"><a href="#" id="mv-cookie-settings">'
		. esc_html__( 'Cookie settings', 'maison-vintique' )
		. '</a></li>';
}

/**
 * Style WooCommerce's legacy block grid to match the theme's wine card.
 *
 * The cart page is a Blocks cart, and its "New in store" panel renders through
 * wc-block-grid rather than template-parts/wine-card.php — markup the theme has
 * never styled. Left alone it inherits WooCommerce's own rules, which set no
 * card height and no image box, so every column finds its own height: the
 * titles, prices and buttons end up on different baselines and the bottles come
 * out at whatever aspect ratio they were uploaded at.
 *
 * The fix is the same shape .mvcard already uses — a fixed 4/3 image box with
 * object-fit: contain, and the button pushed to the bottom with margin-top:auto
 * so it lines up across the row no matter how long a wine's name is.
 *
 * Grid rather than flex, because WooCommerce sizes the flex children with
 * percentage widths that a gap would break.
 */
add_action( 'wp_enqueue_scripts', 'mv_block_grid_styles', 30 );
function mv_block_grid_styles() {
	$css = '
	.wc-block-grid__products{
		display:grid !important;
		grid-template-columns:repeat(4,minmax(0,1fr));
		gap:24px;
		list-style:none !important;
		margin:0 !important;
		padding:0 !important;
	}
	.wc-block-grid.has-3-columns .wc-block-grid__products{grid-template-columns:repeat(3,minmax(0,1fr))}
	.wc-block-grid.has-2-columns .wc-block-grid__products{grid-template-columns:repeat(2,minmax(0,1fr))}

	.wc-block-grid__product{
		display:flex !important;
		flex-direction:column;
		width:auto !important;
		max-width:none !important;
		margin:0 !important;
		padding:0 !important;
		text-align:left;
		background:var(--mv2-paper,#fff);
		border:1px solid var(--mv2-line,#DCD4C4);
		border-radius:2px;
		overflow:hidden;
	}

	/* The image box is what actually equalises the cards. */
	.wc-block-grid__product-image{
		display:block;
		margin:0;
		aspect-ratio:4/3;
		background:#fff;
		overflow:hidden;
	}
	.wc-block-grid__product-image img{
		width:100%;
		height:100%;
		object-fit:contain;
		padding:16px 16px 0;
		box-sizing:border-box;
		display:block;
	}

	.wc-block-grid__product-title{
		font-family:var(--mv-serif,Georgia,serif);
		font-size:17px;
		line-height:1.35;
		color:var(--mv-ink,#2B2420);
		margin:16px 16px 6px;
		text-decoration:none;
	}
	.wc-block-grid__product-title a{color:inherit;text-decoration:none}

	.wc-block-grid__product-price{
		font-family:var(--mv-sans,system-ui,sans-serif);
		font-size:14px;
		color:var(--mv-ink-soft,#4A4038);
		margin:0 16px 14px;
	}

	/* margin-top:auto is what puts every button on the same line. */
	.wc-block-grid__product-add-to-cart{
		margin:auto 16px 16px !important;
		padding:0;
	}
	.wc-block-grid__product-add-to-cart .button,
	.wc-block-grid__product-add-to-cart a{
		display:block;
		width:100%;
		text-align:center;
		box-sizing:border-box;
		font-family:var(--mv-sans,system-ui,sans-serif);
		font-size:12px;
		letter-spacing:.12em;
		text-transform:uppercase;
		padding:12px 14px;
		border:1px solid var(--mv-ink,#2B2420);
		background:transparent;
		color:var(--mv-ink,#2B2420);
		text-decoration:none;
		border-radius:0;
	}
	.wc-block-grid__product-add-to-cart .button:hover,
	.wc-block-grid__product-add-to-cart a:hover{
		background:var(--mv-ink,#2B2420);
		color:#fff;
	}

	@media(max-width:1024px){.wc-block-grid__products{grid-template-columns:repeat(2,minmax(0,1fr)) !important}}
	@media(max-width:600px){.wc-block-grid__products{grid-template-columns:1fr !important}}
	';

	// Attach to whichever of the theme sheets is actually registered.
	foreach ( array( 'maison-vintique', 'hello-elementor', 'woocommerce-general' ) as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
			wp_add_inline_style( $handle, $css );
			return;
		}
	}
}

/**
 * Make loop "add to cart" buttons add a valid quantity.
 *
 * Every wine here is a 6-bottle case with a minimum of two, but the loop button
 * posts a quantity of 1, so mv_case_rule validation rejected it every single
 * time — the button could not succeed on any product in the catalogue. The
 * product page already defaults its quantity box to the minimum; this makes the
 * button in a listing agree with it instead of guaranteeing an error.
 */
add_filter( 'woocommerce_loop_add_to_cart_args', 'mv_loop_add_to_cart_min_qty', 10, 2 );
function mv_loop_add_to_cart_min_qty( $args, $product ) {
	if ( ! function_exists( 'mv_case_rule' ) || ! $product ) {
		return $args;
	}

	$rule = mv_case_rule( $product->get_id() );
	$min  = isset( $rule['min'] ) ? (int) $rule['min'] : 1;

	if ( $min > 1 ) {
		$args['quantity'] = $min;
		// Woo prints the quantity into data-quantity for the AJAX handler.
		$args['attributes']['data-quantity'] = $min;
	}

	return $args;
}

/**
 * Same fix, for the block grid specifically.
 *
 * The legacy grid builds its own button markup instead of going through
 * woocommerce_loop_add_to_cart_args, so the filter above never reaches it and
 * the button kept posting quantity 1. This rewrites the quantity in the
 * rendered block, per product, from that product's own case rule.
 */
add_filter( 'render_block', 'mv_block_grid_min_qty', 10, 2 );
function mv_block_grid_min_qty( $html, $block ) {
	if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'woocommerce/' ) ) {
		return $html;
	}
	if ( false === strpos( $html, 'wc-block-grid__product-add-to-cart' ) || ! function_exists( 'mv_case_rule' ) ) {
		return $html;
	}

	return preg_replace_callback(
		'/<a\b[^>]*data-product_id="(\d+)"[^>]*>/i',
		function ( $m ) {
			$rule = mv_case_rule( (int) $m[1] );
			$min  = max( 1, (int) ( $rule['min'] ?? 1 ) );
			if ( $min <= 1 ) {
				return $m[0];
			}

			$tag   = $m[0];
			$count = 0;
			$tag   = preg_replace( '/data-quantity="\d+"/', 'data-quantity="' . $min . '"', $tag, 1, $count );
			if ( ! $count ) {
				$tag = preg_replace( '/^<a\b/', '<a data-quantity="' . $min . '"', $tag, 1 );
			}

			// The non-JS fallback is the href, so that has to agree.
			$tag = preg_replace_callback(
				'/href="([^"]*add-to-cart=\d+[^"]*)"/i',
				function ( $h ) use ( $min ) {
					$url = html_entity_decode( $h[1], ENT_QUOTES );
					$n   = 0;
					$url = preg_replace( '/([?&])quantity=\d+/', '${1}quantity=' . $min, $url, 1, $n );
					if ( ! $n ) {
						$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'quantity=' . $min;
					}
					return 'href="' . esc_url( $url ) . '"';
				},
				$tag
			);

			return $tag;
		},
		$html
	);
}

/**
 * One login prompt per card, not two.
 *
 * When prices are gated the theme already turns the price into "Trade pricing
 * on login" plus a Login to view price link, and the add-to-cart slot renders
 * a second identical button underneath it. In the theme's own card that slot is
 * suppressed; the block grid has no such handling, so every card in the cart's
 * "New in store" panel carried the same button twice.
 */
add_filter( 'render_block', 'mv_block_grid_single_login_cta', 11, 2 );
function mv_block_grid_single_login_cta( $html, $block ) {
	if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'woocommerce/' ) ) {
		return $html;
	}
	if ( false === strpos( $html, 'wc-block-grid__product-add-to-cart' ) ) {
		return $html;
	}
	if ( ! function_exists( 'mve_is_gated' ) || ! mve_is_gated() ) {
		return $html;   // signed in: the real Add to cart belongs there
	}

	// The slot holds a single anchor, so this cannot swallow nested markup.
	return preg_replace(
		'#<div[^>]*class="[^"]*wc-block-grid__product-add-to-cart[^"]*"[^>]*>.*?</div>#s',
		'',
		$html
	);
}

/**
 * Small polish on the block grid: the titles arrive as underlined links, and
 * the gated price needs to read as a button rather than raw link text.
 */
add_action( 'wp_enqueue_scripts', 'mv_block_grid_polish', 31 );
function mv_block_grid_polish() {
	$css = '
	/*
	 * The title is a div inside the wrapping card anchor, so the underline and
	 * the link colour are painted by that ancestor. Decoration from an ancestor
	 * cannot be switched off by a descendant, which is why setting it on the
	 * title alone did nothing - it has to go on the anchor itself.
	 */
	a.wc-block-grid__product-link,
	.wc-block-grid__product-link{
		text-decoration:none !important;
		color:var(--mv-ink,#2B2420) !important;
		display:block;
	}
	.wc-block-grid__product-title{
		text-decoration:none !important;
		color:var(--mv-ink,#2B2420);
	}
	.wc-block-grid__product-price .mv-trade-tag{
		display:block;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;
		color:var(--mv-taupe,#857A6B);margin-bottom:10px;
	}
	.wc-block-grid__product-price .mv-price-login{
		display:block;text-align:center;padding:12px 14px;
		border:1px solid var(--mv-burg,#6E1E2A);color:var(--mv-burg,#6E1E2A);
		text-decoration:none;font-size:12px;letter-spacing:.12em;text-transform:uppercase;
	}
	.wc-block-grid__product-price .mv-price-login:hover{background:var(--mv-burg,#6E1E2A);color:#fff}
	';

	foreach ( array( 'maison-vintique', 'hello-elementor', 'woocommerce-general' ) as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
			wp_add_inline_style( $handle, $css );
			return;
		}
	}
}

/**
 * Totals wording, per the client cart review.
 *
 * The Blocks cart calls the figure an "Estimated total", which on a trade
 * portal issuing pro forma invoices reads as though the price might still
 * move. It cannot: the delivery band and VAT are both settled by this point.
 */
add_filter( 'gettext_woocommerce', 'mv_totals_wording', 10, 3 );
add_filter( 'gettext', 'mv_totals_wording', 10, 3 );
function mv_totals_wording( $translated, $original, $domain ) {
	if ( 'Estimated total' === $original ) {
		return __( 'Order total', 'maison-vintique' );
	}
	return $translated;
}


/**
 * Rename "Estimated total" in the Blocks cart.
 *
 * The cart is rendered in JavaScript from the Store API, so the string lives
 * in the script bundle and no PHP gettext filter ever sees it. It has to be
 * overridden in wp.i18n before the block renders, so React draws the right
 * word itself - rewriting the DOM afterwards fights the re-render and blanks
 * the figures out.
 *
 * On a trade portal issuing pro forma invoices, nothing about the total is an
 * estimate: the delivery band and the VAT are both settled by this point.
 */
add_action( 'wp_enqueue_scripts', 'mv_blocks_total_wording', 5 );
function mv_blocks_total_wording() {
	if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}

	wp_add_inline_script(
		'wp-i18n',
		"( function () {
			if ( ! window.wp || ! wp.i18n || ! wp.i18n.setLocaleData ) { return; }
			wp.i18n.setLocaleData( { 'Estimated total': [ 'Order total' ] }, 'woocommerce' );
		} )();",
		'after'
	);
}

/**
 * Show "delivery quotation required" on the basket.
 *
 * The quotation line is deliberately GBP 0.00, and the Blocks cart drops
 * zero-value fees, so the basket ended up with no delivery line at all - which
 * reads as free delivery, the exact fault this work set out to remove.
 *
 * Rendered as a panel beside the totals rather than as a row inside them.
 * The totals are React, and an earlier attempt to insert a row into that tree
 * with a live MutationObserver fought the re-render and blanked out the
 * figures. This follows mv_how_to_pay instead: append once, outside the block,
 * then disconnect.
 */
add_action( 'wp_footer', 'mv_delivery_quote_panel', 996 );
function mv_delivery_quote_panel() {
	if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) {
		return;
	}
	if ( ! is_user_logged_in() || ! function_exists( 'mv_delivery_quote' ) || ! WC()->cart ) {
		return;
	}

	$q = mv_delivery_quote();
	if ( ! $q || empty( $q['no_zone'] ) ) {
		return;
	}

	$pc = strtoupper( (string) mv_delivery_postcode() );

	$html = '<div class="mv-delivery-quote" style="border:1px solid #C9B896;background:#FBF7EE;'
		. 'border-radius:4px;padding:16px 18px;margin:18px 0">'
		. '<div style="font-family:var(--mv-sans,system-ui,sans-serif);font-size:11px;letter-spacing:.14em;'
		. 'text-transform:uppercase;color:#8A7A55;margin-bottom:8px">'
		. esc_html__( 'Delivery', 'maison-vintique' ) . '</div>'
		. '<div style="font-family:var(--mv-serif,Georgia,serif);font-size:19px;color:#2B2420;margin-bottom:8px">'
		. esc_html__( 'Quotation required', 'maison-vintique' ) . '</div>'
		. '<div style="font-family:var(--mv-sans,system-ui,sans-serif);font-size:13px;line-height:1.65;color:#5E5A51">'
		. sprintf(
			/* translators: %s: postcode */
			esc_html__( 'We do not hold a published delivery rate for %s, so it is not included in the total above. Place your order as normal and Maison Vintique will confirm the delivery charge with EHD before issuing your pro forma invoice.', 'maison-vintique' ),
			esc_html( $pc )
		)
		. '</div></div>';
	?>
	<script id="mv-delivery-quote-js">
	(function () {
		var HTML = <?php echo wp_json_encode( $html ); ?>;
		function place() {
			if ( document.querySelector( ".mv-delivery-quote" ) ) { return true; }
			var totals = document.querySelector( ".wp-block-woocommerce-cart-order-summary-block, .wp-block-woocommerce-checkout-totals-block, .cart_totals" );
			if ( ! totals ) { return false; }
			var box = document.createElement( "div" );
			box.innerHTML = HTML;
			( totals.parentNode || totals ).appendChild( box.firstChild );
			return true;
		}
		if ( ! place() && window.MutationObserver ) {
			var mo = new MutationObserver( function () { if ( place() ) { mo.disconnect(); } } );
			mo.observe( document.body, { childList: true, subtree: true } );
			window.setTimeout( function () { mo.disconnect(); }, 15000 );
		}
	})();
	</script>
	<?php
}

/**
 * Let a customer ask for a new delivery address from their account.
 *
 * The address is never theirs to set. It goes to the CRM as Pending, Maison
 * Vintique is emailed, and it cannot be delivered to until somebody approves
 * it - which is the whole point of approving addresses in the first place.
 * Storage units, home addresses and event venues are flagged automatically
 * on the CRM side for a person to look at.
 */
add_action( 'wp_ajax_mv_request_address', 'mv_request_address_handler' );
function mv_request_address_handler() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please sign in first.', 'maison-vintique' ) ), 403 );
	}
	check_ajax_referer( 'mv_addr', 'nonce' );

	if ( ! defined( 'MV_CRM_URL' ) || ! defined( 'MV_CRM_BRIDGE_KEY' ) ) {
		wp_send_json_error( array( 'message' => __( 'The address service is not configured. Please email us.', 'maison-vintique' ) ), 500 );
	}

	$line1 = sanitize_text_field( wp_unslash( $_POST['line1'] ?? '' ) );
	$pc    = sanitize_text_field( wp_unslash( $_POST['postcode'] ?? '' ) );

	if ( '' === $line1 || '' === $pc ) {
		wp_send_json_error( array( 'message' => __( 'Please give at least the first line and the postcode.', 'maison-vintique' ) ), 422 );
	}

	$body = array(
		'woo_id'        => get_current_user_id(),
		'line1'         => $line1,
		'line2'         => sanitize_text_field( wp_unslash( $_POST['line2'] ?? '' ) ),
		'city'          => sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ),
		'county'        => sanitize_text_field( wp_unslash( $_POST['county'] ?? '' ) ),
		'postcode'      => $pc,
		'label'         => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
		'location_type' => sanitize_text_field( wp_unslash( $_POST['location_type'] ?? '' ) ),
		'notes'         => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
	);

	$res = wp_remote_post(
		MV_CRM_URL . '/trade-addresses/' . MV_CRM_BRIDGE_KEY,
		array( 'timeout' => 20, 'body' => $body )
	);

	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => __( 'We could not reach the office just now. Please try again, or email us.', 'maison-vintique' ) ), 502 );
	}

	$code = wp_remote_retrieve_response_code( $res );
	$json = json_decode( wp_remote_retrieve_body( $res ), true );

	if ( 200 !== $code || empty( $json['ok'] ) ) {
		$reason = ( 'no-account' === ( $json['reason'] ?? '' ) )
			? __( 'We could not match your trade account. Please email us.', 'maison-vintique' )
			: __( 'That could not be saved. Please check the address and try again.', 'maison-vintique' );
		wp_send_json_error( array( 'message' => $reason ), 400 );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Thank you. Your address has been sent to Maison Vintique and is awaiting approval — you will be told once it can be used.', 'maison-vintique' ),
		)
	);
}

/** Addresses held for this customer, straight from the CRM. */
function mv_customer_addresses() {
	if ( ! is_user_logged_in() || ! defined( 'MV_CRM_URL' ) || ! defined( 'MV_CRM_BRIDGE_KEY' ) ) {
		return array();
	}

	$res = wp_remote_get(
		MV_CRM_URL . '/trade-addresses/' . MV_CRM_BRIDGE_KEY . '/' . get_current_user_id(),
		array( 'timeout' => 15 )
	);

	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return array();
	}

	$json = json_decode( wp_remote_retrieve_body( $res ), true );

	return ( ! empty( $json['ok'] ) && is_array( $json['addresses'] ?? null ) ) ? $json['addresses'] : array();
}


/* =========================================================================
 * PRICE PER BOTTLE
 * ---------------------------------------------------------------------
 * A wine is sold by the case, so the product price IS the case price. But a
 * buyer comparing two wines compares them by the bottle, so the bottle price
 * is the one that should be large and the case price the footnote under it.
 *
 * The division is trivial. The reason it lives here rather than in each
 * template is the number it divides BY: the wine card and the homepage
 * spotlight both need it, and worked out separately in two files they end up
 * quoting different prices for the same wine.
 * ====================================================================== */

/**
 * How many bottles are in a case of this wine.
 *
 * Delegates to mv_case_moq_rule(), which already answers exactly this and
 * already settles which field wins — the ACF `bottles_per_case` first, the
 * `_mv_bottles_per_case` meta as the fallback, then 6. Asking it here means
 * the price on a card and the minimum in the quantity box are worked out from
 * one number: a wine sold in 12s can never be priced in 6s.
 *
 * @param WC_Product|int $product Product or ID.
 * @return int Always at least 1.
 */
function mve_bottles_per_case( $product ) {
	$id = $product instanceof WC_Product ? $product->get_id() : (int) $product;
	if ( ! $id ) {
		return 6;
	}

	if ( function_exists( 'mv_case_moq_rule' ) ) {
		$rule = mv_case_moq_rule( $id );
		// mv_case_moq_rule() returns array( min, step, bottles_per_case ).
		if ( is_array( $rule ) && ! empty( $rule[2] ) ) {
			return max( 1, (int) $rule[2] );
		}
	}

	// Same order as mv_case_moq_rule(), for the unlikely case it is not loaded.
	$per = (int) get_post_meta( $id, 'bottles_per_case', true );
	if ( $per < 1 ) {
		$per = (int) get_post_meta( $id, '_mv_bottles_per_case', true );
	}

	return max( 1, $per > 0 ? $per : 6 );
}

/**
 * The two halves of a wine's price, ready to print.
 *
 * Returns null when there is no single usable price — a variable wine, or one
 * with no price at all — and the caller then falls back to WooCommerce's own
 * price_html, so a range still renders as a range rather than as an invented
 * per-bottle figure.
 *
 * @param WC_Product|int $product Product or ID.
 * @return array|null {
 *     @type string $bottle  Formatted bottle price, e.g. "£21.71".
 *     @type string $case    Formatted case price, e.g. "£130.28".
 *     @type string $suffix  WooCommerce's own price suffix, e.g. "ex VAT". May be ''.
 *     @type int    $bpc     Bottles per case.
 *     @type string $format  Case format as the editor typed it, may be ''.
 * }
 */
function mve_price_per_bottle( $product ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product instanceof WC_Product ) {
		return null;
	}

	if ( $product->is_type( 'variable' ) ) {
		return null;
	}

	$case_price = $product->get_price();
	if ( '' === $case_price || null === $case_price || ! is_numeric( $case_price ) || (float) $case_price <= 0 ) {
		return null;
	}

	$case_price   = (float) $case_price;
	$bpc          = mve_bottles_per_case( $product );
	$bottle_price = $case_price / $bpc;

	$format = function_exists( 'get_field' ) ? (string) get_field( 'case_format', $product->get_id() ) : '';

	/*
	 * "ex VAT" comes from WooCommerce's own suffix setting rather than a string
	 * typed into a template, so the card, the spotlight and the product page
	 * all say whatever the shop is configured to say — and all change together
	 * if that setting ever does.
	 *
	 * Taken once and printed once, at the end of the case line: the two
	 * figures sit on separate lines and the suffix applies to both, so
	 * repeating it on each reads as clutter.
	 */
	$suffix = method_exists( $product, 'get_price_suffix' )
		? trim( $product->get_price_suffix( $bottle_price, 1 ) )
		: '';

	return array(
		'bottle' => wc_price( $bottle_price ),
		'case'   => wc_price( $case_price ),
		'suffix' => $suffix,
		'bpc'    => $bpc,
		'format' => trim( $format ),
	);
}

/**
 * The words after the case price: "per case of 6 x 75cl", or "per case of 6
 * bottles" when no case format has been typed in.
 *
 * @param array $price The array from mve_price_per_bottle().
 * @return string
 */
function mve_case_price_label( $price ) {
	if ( ! empty( $price['format'] ) ) {
		return sprintf(
			/* translators: %s: case format, e.g. "6 x 75cl" */
			__( 'per case of %s', 'maison-vintique-elementor' ),
			$price['format']
		);
	}

	return sprintf(
		/* translators: %d: number of bottles */
		_n( 'per case of %d bottle', 'per case of %d bottles', $price['bpc'], 'maison-vintique-elementor' ),
		$price['bpc']
	);
}


/* =========================================================================
 * THE BASKET COUNT, AND THE EMPTY BASKET
 * ---------------------------------------------------------------------
 * Both from the client's 12 September review, which identified the actual
 * cause of each and is followed here.
 *
 *   1. THE COUNT FROZE because LiteSpeed caches pages for signed-in
 *      customers - so the number baked into the header was whatever it was
 *      when the page was cached. A basket badge of 10 over an empty basket.
 *      Registering it as a cart fragment makes the browser refresh it from
 *      the live basket on every page load, whatever the cache served.
 *
 *   2. THE EMPTY BASKET is placed NEXT TO the Cart block rather than written
 *      into it. The block is React; rewriting what it owns blanked the
 *      totals when that was tried. Nothing it owns is touched here.
 * ====================================================================== */

/**
 * The basket count, as a cart fragment.
 *
 * WooCommerce replaces any element whose CSS selector is a key here whenever
 * fragments refresh - which includes the refresh that runs on page load, and
 * is what defeats the page cache.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function mv_cart_count_fragment( $fragments ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}

	$count = (int) WC()->cart->get_cart_contents_count();

	$fragments['span.mv-header__cart-count'] = sprintf(
		'<span class="mv-header__cart-count" data-cart-count%s>%d</span>',
		$count ? '' : ' hidden',
		$count
	);

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'mv_cart_count_fragment' );

/**
 * Load the script that applies those fragments, everywhere.
 *
 * WooCommerce only enqueues wc-cart-fragments where it thinks it is needed.
 * The basket count is in the header of every page, so it is needed on every
 * page - without this the fragment above is registered and never applied.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'WC' ) && ! is_admin() ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}, 20 );

/**
 * Keep the count right when the BLOCK changes the basket.
 *
 * Fragments cover page loads and the classic AJAX add-to-cart. They do not
 * cover the Cart and Checkout blocks, which talk to the Store API and update
 * their own state without firing the legacy refresh - so removing a line left
 * the header untouched. This reads the block's own data store instead.
 */
function mv_cart_count_live() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	?>
	<script id="mv-cart-count-live">
	(function () {
		function paint(count) {
			var el = document.querySelector('.mv-header__cart-count');
			if (!el) { return; }

			count = parseInt(count, 10);
			if (isNaN(count) || count < 0) { return; }

			el.textContent = String(count);
			if (count > 0) {
				el.removeAttribute('hidden');
			} else {
				el.setAttribute('hidden', '');
			}
		}

		if (window.wp && wp.data && typeof wp.data.subscribe === 'function') {
			var last = null;
			wp.data.subscribe(function () {
				var store = wp.data.select('wc/store/cart');
				if (!store || typeof store.getCartData !== 'function') { return; }

				var data = store.getCartData();
				if (!data || typeof data.itemsCount === 'undefined' || null === data.itemsCount) { return; }

				if (data.itemsCount !== last) {
					last = data.itemsCount;
					paint(data.itemsCount);
				}
			});
		}
	}());
	</script>
	<?php
}
add_action( 'wp_footer', 'mv_cart_count_live', 99 );

/**
 * The house empty basket, printed on the basket page and moved into place.
 *
 * PRINTED WHETHER OR NOT THE BASKET IS EMPTY RIGHT NOW. That is the one
 * change from the reviewed snippet, and it is the difference between fixing
 * half the fault and all of it: if the panel is only printed when PHP sees an
 * empty basket, then arriving with wines in the basket and removing them -
 * which is exactly how the grey sad face was found - leaves no panel in the
 * page for the script to move. It is printed hidden and costs nothing until
 * the block actually goes empty.
 *
 * The markup itself is template-parts/cart-empty.php, the same file the
 * classic cart template uses, so there is one empty basket rather than two
 * that drift.
 */
function mv_empty_basket_panel() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	?>
	<div id="mv-empty-basket" hidden>
		<?php get_template_part( 'template-parts/cart-empty' ); ?>
	</div>
	<script>
	( function () {
		/*
		 * The basket is a WooCommerce block, rendered by React. Nothing is
		 * injected into it and nothing it owns is rewritten - an earlier
		 * attempt to do that blanked the totals. The panel is moved in
		 * alongside the block's empty state, and that state is hidden.
		 *
		 * It TOGGLES rather than running once, because the block switches
		 * between full and empty in the browser: place it when the empty
		 * state appears, put it away if wines come back (an undo, a second
		 * tab, the back button).
		 */
		var panel = document.getElementById( 'mv-empty-basket' );
		if ( ! panel ) { return; }

		var placed = false;

		function blockEmptyState() {
			/* Block selectors only. The classic cart template renders this
			   same panel server-side, and matching its markup here would put
			   two of them on the page. */
			return document.querySelector( '.wp-block-woocommerce-empty-cart-block' )
				|| document.querySelector( '.wc-block-cart__empty-cart__title' );
		}

		function visible( el ) {
			return !! ( el && el.offsetParent !== null );
		}

		function sync() {
			var empty = blockEmptyState();
			var host  = empty
				? ( empty.closest( '.wp-block-woocommerce-empty-cart-block' ) || empty.parentNode )
				: null;

			/* Is the block showing its own empty state right now? The check is
			   on visibility, not presence: the block keeps both halves in the
			   page and shows one, so "it exists" would fire on a full basket
			   too and we would cover the wines with an empty-basket panel.

			   THE HOST IS NEVER HIDDEN BY US. Hiding it was the obvious move
			   and it does not work: this very test would then read its own
			   handiwork, decide the block had left empty mode, and put the
			   panel away again the instant it had shown it. The host stays
			   visible and a class empties it out instead, so what is read here
			   is only ever the block's own doing. */
			var showing = !! ( host && visible( host ) );

			if ( showing && ! placed ) {
				if ( host.parentNode ) {
					host.parentNode.insertBefore( panel, host );
				}
				host.classList.add( 'mv-empty-basket-host' );
				panel.removeAttribute( 'hidden' );
				placed = true;
				return;
			}

			if ( ! showing && placed ) {
				panel.setAttribute( 'hidden', 'hidden' );
				if ( host ) { host.classList.remove( 'mv-empty-basket-host' ); }
				placed = false;
			}
		}

		sync();

		if ( window.MutationObserver ) {
			/* Kept running rather than disconnected after the first placement:
			   the basket can go empty long after load, which is the case this
			   exists for. Guarded by `placed` so our own changes to the DOM do
			   not send it round again. */
			var mo = new MutationObserver( sync );
			mo.observe( document.body, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class', 'hidden', 'style' ] } );
		}
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'mv_empty_basket_panel', 995 );
