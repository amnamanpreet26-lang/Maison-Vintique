<?php
/**
 * woocommerce/myaccount/invoices.php
 *
 * The Invoices tab. Rendered by mve_invoices_endpoint_content() in
 * inc/invoices.php.
 *
 * @var WC_Order[] $mve_orders
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_orders = isset( $mve_orders ) ? $mve_orders : array();
?>

<div class="acct-welcome">
	<h2><?php esc_html_e( 'Invoices', 'maison-vintique' ); ?></h2>
	<p><?php esc_html_e( 'Every invoice raised on your account. Open one to print it or save it as a PDF.', 'maison-vintique' ); ?></p>
</div>

<?php if ( empty( $mve_orders ) ) : ?>

	<div class="apanel apanel-empty">
		<p><?php esc_html_e( 'No invoices yet. They appear here as soon as your first order is confirmed.', 'maison-vintique' ); ?></p>
		<a class="btn btn-p" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>">
			<?php esc_html_e( 'Browse the portfolio', 'maison-vintique' ); ?>
		</a>
	</div>

<?php else : ?>

	<div class="apanel">
		<div class="apanel-h">
			<h3><?php esc_html_e( 'All invoices', 'maison-vintique' ); ?></h3>
		</div>

		<div class="atable-wrap">
			<table class="atable">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Invoice', 'maison-vintique' ); ?></th>
						<th><?php esc_html_e( 'Date', 'maison-vintique' ); ?></th>
						<th><?php esc_html_e( 'Status', 'maison-vintique' ); ?></th>
						<th><?php esc_html_e( 'Total', 'maison-vintique' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $mve_orders as $mve_order ) : ?>
						<?php
						$mve_paid = $mve_order->is_paid();
						$mve_po   = $mve_order->get_meta( '_mve_po_reference' );
						?>
						<tr>
							<td class="mono">
								#<?php echo esc_html( $mve_order->get_order_number() ); ?>
								<?php if ( $mve_po ) : ?>
									<span class="mv-inv-po"><?php echo esc_html( sprintf( __( 'PO %s', 'maison-vintique' ), $mve_po ) ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( wc_format_datetime( $mve_order->get_date_created(), get_option( 'date_format' ) ) ); ?></td>
							<td>
								<span class="pill <?php echo $mve_paid ? 'ok' : 'due'; ?>">
									<?php echo $mve_paid ? esc_html__( 'Paid', 'maison-vintique' ) : esc_html__( 'Payment due', 'maison-vintique' ); ?>
								</span>
							</td>
							<td><?php echo wp_kses_post( $mve_order->get_formatted_order_total() ); ?></td>
							<td class="acell-action">
								<div class="mv-order-actions">
									<a class="mv-order-action" href="<?php echo esc_url( mve_invoice_url( $mve_order->get_id() ) ); ?>">
										<?php esc_html_e( 'View', 'maison-vintique' ); ?>
									</a>
									<a class="mv-order-action" href="<?php echo esc_url( mve_invoice_url( $mve_order->get_id(), true ) ); ?>" target="_blank" rel="noopener">
										<?php esc_html_e( 'Download', 'maison-vintique' ); ?>
									</a>
									<?php if ( ! $mve_paid && $mve_order->needs_payment() ) : ?>
										<a class="mv-order-action mv-order-action--pay" href="<?php echo esc_url( $mve_order->get_checkout_payment_url() ); ?>">
											<?php esc_html_e( 'Pay', 'maison-vintique' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

<?php endif; ?>
