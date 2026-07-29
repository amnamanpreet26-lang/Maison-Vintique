<?php
/**
 * Cart page — "Your Basket".
 *
 * Theme override path: your-theme/woocommerce/cart/cart.php
 *
 * Markup matches the approved prototype (.cart-wrap / .cart-line / .summary)
 * while keeping every WooCommerce hook, filter and nonce the default template
 * fires — so quantity updates, coupons, removal, shipping and third-party
 * plugins all keep working.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<main>
<section class="section cart-page">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php esc_html_e( 'Basket', 'maison-vintique-elementor' ); ?></span>
	</div>

	<div class="page-h">
		<p class="eyebrow"><?php esc_html_e( 'Trade Basket', 'maison-vintique-elementor' ); ?></p>
		<h1><?php esc_html_e( 'Your Basket', 'maison-vintique-elementor' ); ?></h1>
	</div>

	<div class="cart-wrap">

		<div class="cart-main">

			<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<?php do_action( 'woocommerce_before_cart_table' ); ?>

				<div class="cart-lines">
					<?php
					do_action( 'woocommerce_before_cart_contents' );

					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

						if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							continue;
						}

						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

						// "Bordeaux Supérieur 2019" — appellation + vintage, falling
						// back to the product's categories.
						$mve_appellation = function_exists( 'get_field' ) ? get_field( 'appellation', $product_id ) : '';
						$mve_vintage     = function_exists( 'get_field' ) ? get_field( 'vintage_year', $product_id ) : '';
						$mve_line_meta   = trim( implode( ' ', array_filter( array( $mve_appellation, $mve_vintage ) ) ) );
						if ( ! $mve_line_meta ) {
							$mve_line_meta = wp_strip_all_tags( wc_get_product_category_list( $product_id, ' · ' ) );
						}

						$mve_case_format = function_exists( 'get_field' ) ? get_field( 'case_format', $product_id ) : '';
						?>
						<div class="cart-line <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

							<div class="thumb">
								<?php
								$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
								echo $product_permalink
									? '<a href="' . esc_url( $product_permalink ) . '">' . $thumbnail . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput
									: $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput
								?>
							</div>

							<div class="info">
								<h3>
									<?php
									if ( $product_permalink ) {
										printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $_product->get_name() ) );
									} else {
										echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' );
									}

									// Backorder notice + variation/addon data.
									do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

									echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput

									if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
										echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
									}
									?>
								</h3>

								<?php if ( $mve_line_meta ) : ?>
									<div class="appel"><?php echo esc_html( $mve_line_meta ); ?></div>
								<?php endif; ?>

								<div class="unit">
									<?php
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) );
									if ( $mve_case_format ) {
										echo ' / ' . esc_html( $mve_case_format );
									}
									?>
								</div>
							</div>

							<div class="qty">
								<?php
								if ( $_product->is_sold_individually() ) {
									$min_quantity = 1;
									$max_quantity = 1;
								} else {
									$min_quantity = 0;
									$max_quantity = $_product->get_max_purchase_quantity();
								}

								$product_quantity = woocommerce_quantity_input(
									array(
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $max_quantity,
										'min_value'    => $min_quantity,
										'product_name' => $_product->get_name(),
									),
									$_product,
									false
								);

								echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
							</div>

							<div class="ln-price">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
							</div>

							<?php
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="rm" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									esc_attr( sprintf( __( 'Remove %s from basket', 'maison-vintique-elementor' ), $_product->get_name() ) ),
									esc_attr( $product_id ),
									esc_attr( $_product->get_sku() )
								),
								$cart_item_key
							);
							?>
						</div>
						<?php
					}

					do_action( 'woocommerce_cart_contents' );
					?>
				</div>

				<div class="cart-actions">
					<?php if ( wc_coupons_enabled() ) : ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
							<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>">
							<button type="submit" class="btn btn-o" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
								<?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?>
							</button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php endif; ?>

					<button type="submit" class="btn btn-o" name="update_cart" value="<?php esc_attr_e( 'Update basket', 'maison-vintique-elementor' ); ?>">
						<?php esc_html_e( 'Update basket', 'maison-vintique-elementor' ); ?>
					</button>

					<?php do_action( 'woocommerce_cart_actions' ); ?>
					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</div>

				<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				<?php do_action( 'woocommerce_after_cart_table' ); ?>
			</form>

			<div class="cart-continue">
				<a class="btn btn-o" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					&lsaquo; <?php esc_html_e( 'Continue shopping', 'maison-vintique-elementor' ); ?>
				</a>
			</div>

		</div>

		<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

		<aside class="summary">
			<h3><?php esc_html_e( 'Order summary', 'maison-vintique-elementor' ); ?></h3>

			<div class="s-row">
				<span><?php esc_html_e( 'Subtotal (ex VAT)', 'maison-vintique-elementor' ); ?></span>
				<span class="mono"><?php wc_cart_totals_subtotal_html(); ?></span>
			</div>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<div class="s-row">
					<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
					<span class="mono"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
				<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>
				<?php wc_cart_totals_shipping_html(); ?>
				<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<div class="s-row">
					<span><?php echo esc_html( $fee->name ); ?></span>
					<span class="mono"><?php wc_cart_totals_fee_html( $fee ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
				<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
					<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
						<div class="s-row">
							<span><?php echo esc_html( $tax->label ); ?></span>
							<span class="mono"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="s-row">
						<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
						<span class="mono"><?php wc_cart_totals_taxes_total_html(); ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

			<div class="s-row total">
				<span><?php esc_html_e( 'Total', 'maison-vintique-elementor' ); ?></span>
				<span class="mono"><?php wc_cart_totals_order_total_html(); ?></span>
			</div>

			<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

			<div class="summary-cta">
				<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
			</div>

			<p class="note">
				<?php esc_html_e( 'Wines are sold by the case. Pay on account or by pro forma at checkout.', 'maison-vintique-elementor' ); ?>
			</p>
		</aside>

	</div>

	<?php
	/*
	 * Cross-sells etc. Rendered AFTER the two-column grid so they don't become
	 * a third grid child. WooCommerce's own cart-totals callback is unhooked in
	 * inc/woocommerce.php (mve_unhook_default_cart_totals) because the summary
	 * aside above already renders the totals — without that it would print a
	 * second, unstyled totals table here.
	 */
	do_action( 'woocommerce_cart_collaterals' );
	?>

</div>
</section>
</main>

<?php do_action( 'woocommerce_after_cart' ); ?>
