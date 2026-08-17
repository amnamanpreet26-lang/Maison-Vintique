<?php
/**
 * inc/invoices.php
 *
 * The Invoices tab in My Account, a printable invoice document, and the
 * per-order actions (View order / Download invoice / Reorder).
 *
 * There is no PDF library here on purpose. A printable HTML invoice with a
 * print stylesheet gives a clean A4 PDF through the browser's own "Save as
 * PDF", works on every host, and adds nothing to maintain. If a true
 * server-side PDF is wanted later, `mve_invoice_html()` is the thing to feed
 * to dompdf.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

const MVE_INVOICES_ENDPOINT = 'invoices';

/* =========================================================================
 * THE INVOICES TAB
 * ====================================================================== */

/** Register the endpoint so /my-account/invoices/ resolves. */
function mve_add_invoices_endpoint() {
	add_rewrite_endpoint( MVE_INVOICES_ENDPOINT, EP_PAGES );
}
add_action( 'init', 'mve_add_invoices_endpoint' );

/**
 * Add it to the account menu, after Orders.
 *
 * @param array $items Menu items.
 * @return array
 */
function mve_account_menu_items( $items ) {
	$out = array();
	foreach ( $items as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'orders' === $key ) {
			$out[ MVE_INVOICES_ENDPOINT ] = __( 'Invoices', 'maison-vintique' );
		}
	}
	// No Orders tab (some setups hide it) — put Invoices before the logout link.
	if ( ! isset( $out[ MVE_INVOICES_ENDPOINT ] ) ) {
		$logout = isset( $out['customer-logout'] ) ? $out['customer-logout'] : null;
		unset( $out['customer-logout'] );
		$out[ MVE_INVOICES_ENDPOINT ] = __( 'Invoices', 'maison-vintique' );
		if ( $logout ) {
			$out['customer-logout'] = $logout;
		}
	}
	return $out;
}
add_filter( 'woocommerce_account_menu_items', 'mve_account_menu_items' );

/** Title for the tab. */
add_filter( 'woocommerce_endpoint_' . MVE_INVOICES_ENDPOINT . '_title', function () {
	return __( 'Invoices', 'maison-vintique' );
} );

/**
 * Render the tab. WooCommerce calls woocommerce_account_{endpoint}_endpoint.
 */
function mve_invoices_endpoint_content() {
	$orders = wc_get_orders(
		array(
			'customer_id' => get_current_user_id(),
			'limit'       => 50,
			'orderby'     => 'date',
			'order'       => 'DESC',
			// Only orders that represent real money owed or paid.
			'status'      => array( 'wc-processing', 'wc-shipped', 'wc-completed', 'wc-on-hold', 'wc-refunded' ),
		)
	);

	wc_get_template(
		'myaccount/invoices.php',
		array( 'mve_orders' => $orders ),
		'',
		get_stylesheet_directory() . '/woocommerce/'
	);
}
add_action( 'woocommerce_account_' . MVE_INVOICES_ENDPOINT . '_endpoint', 'mve_invoices_endpoint_content' );

/* =========================================================================
 * THE INVOICE DOCUMENT
 * ====================================================================== */

/**
 * URL of an order's invoice.
 *
 * @param int  $order_id Order ID.
 * @param bool $download Add the print flag.
 * @return string
 */
function mve_invoice_url( $order_id, $download = false ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return '';
	}
	$args = array(
		'mve_invoice' => $order_id,
		'key'         => $order->get_order_key(),
	);
	if ( $download ) {
		$args['print'] = 1;
	}
	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * Can the current visitor see this invoice?
 *
 * Checks the order key as well as the user, so a logged-out customer can open
 * the link straight from their email — the same way WooCommerce's own
 * order-received page works.
 *
 * @param WC_Order $order Order.
 * @param string   $key   Order key from the URL.
 * @return bool
 */
function mve_can_view_invoice( $order, $key = '' ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}
	if ( $key && hash_equals( $order->get_order_key(), $key ) ) {
		return true;
	}
	return is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id();
}

/**
 * Serve the invoice when ?mve_invoice=123 is requested.
 */
function mve_maybe_render_invoice() {
	if ( empty( $_GET['mve_invoice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only, authorised by order key or user below.
		return;
	}

	$order_id = absint( $_GET['mve_invoice'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$order    = wc_get_order( $order_id );

	if ( ! mve_can_view_invoice( $order, $key ) ) {
		wp_die(
			esc_html__( 'You do not have permission to view this invoice.', 'maison-vintique' ),
			esc_html__( 'Invoice', 'maison-vintique' ),
			array( 'response' => 403 )
		);
	}

	$print = ! empty( $_GET['print'] ); // phpcs:ignore WordPress.Security.NonceVerification

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	echo mve_invoice_html( $order, $print ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
	exit;
}
add_action( 'template_redirect', 'mve_maybe_render_invoice' );

/**
 * The invoice, as a standalone HTML document.
 *
 * Self-contained (inline CSS, no theme assets) so it prints identically
 * wherever it is opened, and so it could be handed to a PDF renderer as-is.
 *
 * @param WC_Order $order Order.
 * @param bool     $print Open the print dialog on load.
 * @return string
 */
function mve_invoice_html( $order, $print = false ) {
	$number   = $order->get_order_number();
	$date     = wc_format_datetime( $order->get_date_created(), get_option( 'date_format' ) );
	$company  = get_bloginfo( 'name', 'display' );
	$vat      = get_option( 'mve_company_vat', '' );
	$reg      = get_option( 'mve_company_reg', '' );
	$address  = get_option( 'mve_company_address', '' );
	$po       = $order->get_meta( '_mve_po_reference' );
	$paid     = $order->is_paid();

	ob_start();
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( sprintf( __( 'Invoice %1$s — %2$s', 'maison-vintique' ), $number, $company ) ); ?></title>
<style>
	*{box-sizing:border-box}
	body{margin:0;padding:32px;background:#f2eee6;color:#4a4038;
		font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6}
	.inv{max-width:820px;margin:0 auto;background:#fff;border:1px solid #d6cec1;border-radius:6px;padding:44px 48px}
	.inv-top{display:flex;justify-content:space-between;align-items:flex-start;gap:30px;
		border-bottom:1px solid #d6cec1;padding-bottom:24px;margin-bottom:26px}
	.inv-brand{font-family:Georgia,serif;font-size:19px;letter-spacing:4px;text-transform:uppercase;color:#17251f}
	.inv-strap{font-size:9px;letter-spacing:2.4px;text-transform:uppercase;color:#a98854;margin-top:5px}
	.inv-co{font-size:11.5px;color:#6f675e;margin-top:14px;white-space:pre-line}
	.inv-title{font-family:Georgia,serif;font-size:30px;color:#17251f;margin:0;text-align:right}
	.inv-num{font-size:12px;color:#6f675e;text-align:right;margin-top:6px}
	.pill{display:inline-block;margin-top:10px;padding:4px 12px;border-radius:999px;font-size:10px;
		font-weight:700;letter-spacing:1.4px;text-transform:uppercase}
	.pill.paid{background:#e4efe7;color:#2e7d5b}
	.pill.due{background:#fbf2f0;color:#9d3b3b}
	.cols{display:flex;gap:40px;margin-bottom:26px;flex-wrap:wrap}
	.col{flex:1 1 220px;min-width:0}
	.lbl{font-size:10px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#a6a096;margin-bottom:7px}
	table{width:100%;border-collapse:collapse;margin-bottom:22px}
	th{text-align:left;font-size:10px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
		color:#a6a096;border-bottom:1px solid #d6cec1;padding:0 8px 9px}
	td{padding:12px 8px;border-bottom:1px solid #ece7de;vertical-align:top}
	th.r,td.r{text-align:right}
	.tot{margin-left:auto;width:100%;max-width:320px}
	.tot td{border:0;padding:6px 8px}
	.tot tr.grand td{border-top:1px solid #d6cec1;padding-top:12px;
		font-family:Georgia,serif;font-size:19px;color:#17251f}
	.foot{border-top:1px solid #d6cec1;margin-top:26px;padding-top:18px;font-size:11.5px;color:#6f675e}
	.noprint{text-align:center;margin:0 auto 22px;max-width:820px}
	.noprint button{background:#17251f;color:#fff;border:0;border-radius:4px;padding:12px 26px;
		font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;cursor:pointer}
	@media print{
		body{background:#fff;padding:0}
		.inv{border:0;border-radius:0;padding:0;max-width:none}
		.noprint{display:none}
	}
</style>
</head>
<body>
<div class="noprint">
	<button type="button" onclick="window.print()"><?php esc_html_e( 'Print / save as PDF', 'maison-vintique' ); ?></button>
</div>

<div class="inv">
	<div class="inv-top">
		<div>
			<div class="inv-brand"><?php echo esc_html( $company ); ?></div>
			<div class="inv-strap"><?php esc_html_e( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ); ?></div>
			<?php if ( $address || $vat || $reg ) : ?>
				<div class="inv-co"><?php
					echo esc_html( $address );
					if ( $vat ) {
						echo "\n" . esc_html( sprintf( __( 'VAT no. %s', 'maison-vintique' ), $vat ) );
					}
					if ( $reg ) {
						echo "\n" . esc_html( sprintf( __( 'Company no. %s', 'maison-vintique' ), $reg ) );
					}
				?></div>
			<?php endif; ?>
		</div>
		<div>
			<h1 class="inv-title"><?php esc_html_e( 'Invoice', 'maison-vintique' ); ?></h1>
			<div class="inv-num">
				<?php echo esc_html( sprintf( __( 'No. %s', 'maison-vintique' ), $number ) ); ?><br>
				<?php echo esc_html( $date ); ?>
			</div>
			<div style="text-align:right">
				<span class="pill <?php echo $paid ? 'paid' : 'due'; ?>">
					<?php echo $paid ? esc_html__( 'Paid', 'maison-vintique' ) : esc_html__( 'Payment due', 'maison-vintique' ); ?>
				</span>
			</div>
		</div>
	</div>

	<div class="cols">
		<div class="col">
			<div class="lbl"><?php esc_html_e( 'Invoiced to', 'maison-vintique' ); ?></div>
			<?php echo wp_kses_post( $order->get_formatted_billing_address( '—' ) ); ?>
			<?php if ( $order->get_billing_email() ) : ?>
				<br><?php echo esc_html( $order->get_billing_email() ); ?>
			<?php endif; ?>
		</div>
		<?php if ( $order->get_formatted_shipping_address() ) : ?>
			<div class="col">
				<div class="lbl"><?php esc_html_e( 'Delivered to', 'maison-vintique' ); ?></div>
				<?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
			</div>
		<?php endif; ?>
		<div class="col">
			<div class="lbl"><?php esc_html_e( 'Details', 'maison-vintique' ); ?></div>
			<?php echo esc_html( sprintf( __( 'Order %s', 'maison-vintique' ), $number ) ); ?><br>
			<?php if ( $po ) : ?>
				<?php echo esc_html( sprintf( __( 'PO reference: %s', 'maison-vintique' ), $po ) ); ?><br>
			<?php endif; ?>
			<?php echo esc_html( sprintf( __( 'Payment: %s', 'maison-vintique' ), $order->get_payment_method_title() ? $order->get_payment_method_title() : __( 'Proforma', 'maison-vintique' ) ) ); ?>
		</div>
	</div>

	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Wine', 'maison-vintique' ); ?></th>
				<th class="r"><?php esc_html_e( 'Qty', 'maison-vintique' ); ?></th>
				<th class="r"><?php esc_html_e( 'Unit', 'maison-vintique' ); ?></th>
				<th class="r"><?php esc_html_e( 'Total', 'maison-vintique' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $order->get_items() as $item ) : ?>
				<?php
				$product = $item->get_product();
				$qty     = $item->get_quantity();
				$unit    = $qty ? ( $item->get_subtotal() / $qty ) : 0;
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $item->get_name() ); ?></strong>
						<?php if ( $product && $product->get_sku() ) : ?>
							<br><span style="font-size:11px;color:#a6a096"><?php echo esc_html( sprintf( __( 'SKU %s', 'maison-vintique' ), $product->get_sku() ) ); ?></span>
						<?php endif; ?>
					</td>
					<td class="r"><?php echo esc_html( $qty ); ?></td>
					<td class="r"><?php echo wp_kses_post( wc_price( $unit, array( 'currency' => $order->get_currency() ) ) ); ?></td>
					<td class="r"><?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<table class="tot">
		<?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
			<tr class="<?php echo 'order_total' === $key ? 'grand' : ''; ?>">
				<td><?php echo esc_html( $total['label'] ); ?></td>
				<td class="r"><?php echo wp_kses_post( $total['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>

	<div class="foot">
		<?php if ( $order->get_customer_note() ) : ?>
			<p><strong><?php esc_html_e( 'Note:', 'maison-vintique' ); ?></strong> <?php echo esc_html( $order->get_customer_note() ); ?></p>
		<?php endif; ?>
		<p><?php echo esc_html( get_option( 'mve_invoice_terms', __( 'Payment is due per your agreed account terms. Wine remains our property until paid for in full.', 'maison-vintique' ) ) ); ?></p>
	</div>
</div>

<?php if ( $print ) : ?>
	<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 * ORDER ACTIONS — VIEW / DOWNLOAD INVOICE / REORDER
 * ====================================================================== */

/**
 * Add our actions to the Orders table.
 *
 * @param array    $actions Existing actions.
 * @param WC_Order $order   Order.
 * @return array
 */
function mve_order_actions( $actions, $order ) {
	// WooCommerce's own "View" wording is vague on a trade site.
	if ( isset( $actions['view'] ) ) {
		$actions['view']['name'] = __( 'View order', 'maison-vintique' );
	}

	$actions['mve_invoice'] = array(
		'url'  => mve_invoice_url( $order->get_id(), true ),
		'name' => __( 'Download invoice', 'maison-vintique' ),
	);

	if ( mve_order_is_reorderable( $order ) ) {
		$actions['mve_reorder'] = array(
			'url'  => wp_nonce_url(
				add_query_arg( 'mve_reorder', $order->get_id(), wc_get_account_endpoint_url( 'orders' ) ),
				'mve_reorder_' . $order->get_id()
			),
			'name' => __( 'Reorder', 'maison-vintique' ),
		);
	}

	return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', 'mve_order_actions', 10, 2 );

/**
 * Is there anything in this order still worth reordering?
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function mve_order_is_reorderable( $order ) {
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product && $product->is_purchasable() && $product->is_in_stock() ) {
			return true;
		}
	}
	return false;
}

/**
 * Handle ?mve_reorder=123 — put the order's lines back in the basket.
 */
function mve_handle_reorder() {
	if ( empty( $_GET['mve_reorder'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$order_id = absint( $_GET['mve_reorder'] );
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mve_reorder_' . $order_id ) ) {
		wc_add_notice( __( 'That reorder link has expired. Please try again.', 'maison-vintique' ), 'error' );
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_user_logged_in() || (int) $order->get_customer_id() !== get_current_user_id() ) {
		wc_add_notice( __( 'That order could not be found on your account.', 'maison-vintique' ), 'error' );
		return;
	}

	$added   = 0;
	$skipped = array();

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();

		if ( ! $product || ! $product->is_purchasable() ) {
			$skipped[] = $item->get_name();
			continue;
		}
		if ( ! $product->is_in_stock() || ! $product->has_enough_stock( $item->get_quantity() ) ) {
			$skipped[] = $item->get_name();
			continue;
		}

		$added += WC()->cart->add_to_cart(
			$product->get_id(),
			$item->get_quantity(),
			$item->get_variation_id(),
			(array) $item->get_meta( '_variation_data', true )
		) ? 1 : 0;
	}

	if ( $added ) {
		wc_add_notice(
			sprintf(
				/* translators: %d: number of wines */
				_n( '%d wine added back to your basket.', '%d wines added back to your basket.', $added, 'maison-vintique' ),
				$added
			),
			'success'
		);
	}

	if ( $skipped ) {
		wc_add_notice(
			sprintf(
				/* translators: %s: comma-separated wine names */
				__( 'Not available right now, so left out: %s', 'maison-vintique' ),
				esc_html( implode( ', ', $skipped ) )
			),
			'notice'
		);
	}

	if ( ! $added && ! $skipped ) {
		wc_add_notice( __( 'There was nothing on that order to add.', 'maison-vintique' ), 'notice' );
	}

	wp_safe_redirect( $added ? wc_get_cart_url() : wc_get_account_endpoint_url( 'orders' ) );
	exit;
}
add_action( 'template_redirect', 'mve_handle_reorder' );

/* =========================================================================
 * ADMIN — SEND AN INVOICE / PAYMENT EMAIL FROM THE ORDER SCREEN
 * ====================================================================== */

/**
 * Add our emails to the order screen's actions dropdown.
 *
 * @param array $actions Actions.
 * @return array
 */
function mve_order_email_actions( $actions ) {
	$actions['mve_send_invoice']          = __( 'Email invoice to customer', 'maison-vintique' );
	$actions['mve_send_payment_required'] = __( 'Email "payment required"', 'maison-vintique' );
	$actions['mve_send_payment_reminder'] = __( 'Email payment reminder', 'maison-vintique' );
	return $actions;
}
add_filter( 'woocommerce_order_actions', 'mve_order_email_actions' );

add_action( 'woocommerce_order_action_mve_send_invoice', function ( $order ) {
	mve_send_email( 'mve_invoice', $order );
	$order->add_order_note( __( 'Invoice emailed to the customer.', 'maison-vintique' ), false, true );
} );

add_action( 'woocommerce_order_action_mve_send_payment_required', function ( $order ) {
	mve_send_email( 'mve_payment_required', $order );
	$order->add_order_note( __( '"Payment required" emailed to the customer.', 'maison-vintique' ), false, true );
} );

add_action( 'woocommerce_order_action_mve_send_payment_reminder', function ( $order ) {
	$created = $order->get_date_created();
	$days    = $created ? (int) floor( ( time() - $created->getTimestamp() ) / DAY_IN_SECONDS ) : 0;
	mve_send_email( 'mve_payment_reminder', $order, array( 'days' => $days ) );
	$order->add_order_note( __( 'Payment reminder emailed to the customer.', 'maison-vintique' ), false, true );
} );
