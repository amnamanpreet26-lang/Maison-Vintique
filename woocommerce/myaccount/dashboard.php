<?php
/**
 * My Account dashboard (shown only on the base /my-account/ page).
 *
 * Theme override path: your-theme/woocommerce/myaccount/dashboard.php
 *
 * Trade fields (group / discount / terms / credit limit) read from user
 * meta so this doesn't break if they're unset. Set them via user profile,
 * ACF user fields, or your Laravel/SSO sync using these meta keys:
 *   mve_trade_group, mve_trade_discount, mve_trade_terms, mve_credit_limit
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$user = wp_get_current_user();

$trade_group    = get_user_meta( $user->ID, 'mve_trade_group', true );
$trade_discount = get_user_meta( $user->ID, 'mve_trade_discount', true );
$trade_terms    = get_user_meta( $user->ID, 'mve_trade_terms', true );
$credit_limit   = (float) get_user_meta( $user->ID, 'mve_credit_limit', true );

// Open orders (processing / on-hold).
$open_orders = wc_get_orders( array(
	'customer_id' => $user->ID,
	'status'      => array( 'wc-processing', 'wc-on-hold' ),
	'limit'       => -1,
	'return'      => 'ids',
) );

// Awaiting payment (pending / on-hold) total.
$awaiting_orders = wc_get_orders( array(
	'customer_id' => $user->ID,
	'status'      => array( 'wc-pending', 'wc-on-hold' ),
	'limit'       => -1,
) );
$awaiting_total = 0.0;
foreach ( $awaiting_orders as $o ) {
	$awaiting_total += (float) $o->get_total();
}

// This calendar year total (paid orders).
$year_orders = wc_get_orders( array(
	'customer_id'   => $user->ID,
	'status'        => array( 'wc-completed', 'wc-processing' ),
	'date_created'  => '>=' . gmdate( 'Y-m-d', strtotime( 'first day of january this year' ) ),
	'limit'         => -1,
) );
$year_total = 0.0;
foreach ( $year_orders as $o ) {
	$year_total += (float) $o->get_total();
}

$credit_available = $credit_limit > 0 ? max( 0, $credit_limit - $awaiting_total ) : null;

// Recent orders for the two tables below.
$recent_orders = wc_get_orders( array(
	'customer_id' => $user->ID,
	'limit'       => 5,
	'orderby'     => 'date',
	'order'       => 'DESC',
) );

$status_pill = array(
	'completed'  => 'p-ok',
	'processing' => 'p-info',
	'on-hold'    => 'p-warn',
	'pending'    => 'p-warn',
	'failed'     => 'p-warn',
	'cancelled'  => 'p-warn',
	'refunded'   => 'p-info',
);
?>

<div class="acct-welcome">
	<h2>
		<?php
		printf(
			/* translators: %s: customer display name */
			esc_html__( 'Welcome back, %s', 'maison-vintique-elementor' ),
			esc_html( $user->display_name )
		);
		?>
	</h2>
	<p>
		<?php if ( $trade_group ) : ?>
			<?php echo esc_html( $trade_group ); ?> <?php esc_html_e( 'trade group', 'maison-vintique-elementor' ); ?>
		<?php endif; ?>
		<?php if ( $trade_discount ) : ?>
			&middot; <?php echo esc_html( $trade_discount ); ?>% <?php esc_html_e( 'agreed discount', 'maison-vintique-elementor' ); ?>
		<?php endif; ?>
		<?php if ( $trade_terms ) : ?>
			&middot; <?php echo esc_html( $trade_terms ); ?>
		<?php endif; ?>
		<?php esc_html_e( 'Not you?', 'maison-vintique-elementor' ); ?>
		<a class="link-accent" href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Log out', 'maison-vintique-elementor' ); ?></a>.
	</p>
</div>

<div class="astats">
	<div class="astat">
		<div class="l"><?php esc_html_e( 'Open orders', 'maison-vintique-elementor' ); ?></div>
		<div class="v"><?php echo (int) count( $open_orders ); ?></div>
	</div>
	<div class="astat">
		<div class="l"><?php esc_html_e( 'Awaiting payment', 'maison-vintique-elementor' ); ?></div>
		<div class="v burg"><?php echo wp_kses_post( wc_price( $awaiting_total ) ); ?></div>
	</div>
	<?php if ( null !== $credit_available ) : ?>
	<div class="astat">
		<div class="l"><?php esc_html_e( 'Credit available', 'maison-vintique-elementor' ); ?></div>
		<div class="v"><?php echo wp_kses_post( wc_price( $credit_available ) ); ?></div>
	</div>
	<?php endif; ?>
	<div class="astat">
		<div class="l"><?php esc_html_e( 'This year', 'maison-vintique-elementor' ); ?></div>
		<div class="v"><?php echo wp_kses_post( wc_price( $year_total ) ); ?></div>
	</div>
</div>

<div class="apanel">
	<div class="apanel-h">
		<h3><?php esc_html_e( 'Recent orders', 'maison-vintique-elementor' ); ?></h3>
		<a class="link-action" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View all', 'maison-vintique-elementor' ); ?> &rarr;</a>
	</div>
	<?php if ( $recent_orders ) : ?>
	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order', 'maison-vintique-elementor' ); ?></th>
				<th><?php esc_html_e( 'Date', 'maison-vintique-elementor' ); ?></th>
				<th><?php esc_html_e( 'Total', 'maison-vintique-elementor' ); ?></th>
				<th><?php esc_html_e( 'Status', 'maison-vintique-elementor' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $recent_orders as $order ) :
			$status     = $order->get_status();
			$pill_class = isset( $status_pill[ $status ] ) ? $status_pill[ $status ] : 'p-info';
		?>
			<tr>
				<td class="mono">#<?php echo esc_html( $order->get_order_number() ); ?></td>
				<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
				<td class="mono"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
				<td><span class="pill <?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span></td>
				<td>
					<?php if ( $order->needs_payment() ) : ?>
						<a class="link-action" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Pay', 'maison-vintique-elementor' ); ?></a>
					<?php else : ?>
						<a class="link-action" href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'View', 'maison-vintique-elementor' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p class="apanel-empty"><?php esc_html_e( 'No orders yet.', 'maison-vintique-elementor' ); ?></p>
	<?php endif; ?>
</div>

<div class="apanel" id="invoices">
	<div class="apanel-h">
		<h3><?php esc_html_e( 'Invoices & documents', 'maison-vintique-elementor' ); ?></h3>
	</div>
	<?php if ( $recent_orders ) : ?>
	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'Document', 'maison-vintique-elementor' ); ?></th>
				<th><?php esc_html_e( 'Date', 'maison-vintique-elementor' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'maison-vintique-elementor' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $recent_orders as $order ) :
			// If you use an invoicing plugin (e.g. WooCommerce PDF Invoices),
			// hook 'mve_account_invoice_url' to return its PDF URL instead.
			$invoice_url = apply_filters( 'mve_account_invoice_url', $order->get_view_order_url(), $order );
		?>
			<tr>
				<td class="mono">INV-<?php echo esc_html( $order->get_order_number() ); ?></td>
				<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
				<td class="mono"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
				<td><a class="link-action" href="<?php echo esc_url( $invoice_url ); ?>"><?php esc_html_e( 'View', 'maison-vintique-elementor' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p class="apanel-empty"><?php esc_html_e( 'No documents yet.', 'maison-vintique-elementor' ); ?></p>
	<?php endif; ?>
</div>

<div class="apanel">
	<div class="apanel-h">
		<h3><?php esc_html_e( 'Addresses', 'maison-vintique-elementor' ); ?></h3>
		<a class="link-action" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'Edit', 'maison-vintique-elementor' ); ?> &rarr;</a>
	</div>
	<div class="addr-grid">
		<div class="addr">
			<h4><?php esc_html_e( 'Billing', 'maison-vintique-elementor' ); ?></h4>
			<p>
			<?php
			$billing = wc_get_account_formatted_address( 'billing' );
			echo $billing ? wp_kses_post( $billing ) : esc_html__( 'No billing address on file.', 'maison-vintique-elementor' );
			?>
			</p>
		</div>
		<div class="addr">
			<h4><?php esc_html_e( 'Default delivery', 'maison-vintique-elementor' ); ?></h4>
			<p>
			<?php
			$shipping = wc_get_account_formatted_address( 'shipping' );
			echo $shipping ? wp_kses_post( $shipping ) : esc_html__( 'No delivery address on file.', 'maison-vintique-elementor' );
			?>
			</p>
		</div>
	</div>
</div>