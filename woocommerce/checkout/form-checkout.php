<?php
/**
 * Checkout — "Order Request".
 *
 * Theme override path: your-theme/woocommerce/checkout/form-checkout.php
 *
 * Markup matches the approved prototype (.co-wrap / .co-sec / .grid2 / .pay)
 * but the FIELDS are still WooCommerce's own — billing and shipping render
 * through woocommerce_checkout_billing / woocommerce_checkout_shipping, so
 * validation, saving, address book and payment gateways all behave normally.
 *
 * The three trade extras from the design (PO reference, requested delivery
 * date, delivery instructions) are registered as real checkout fields in
 * inc/woocommerce.php so they save against the order and show in wp-admin.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$checkout = WC()->checkout();

// Redirect to login when the store requires an account to check out.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<main>
<section class="section checkout-page">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Basket', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php esc_html_e( 'Order Request', 'maison-vintique-elementor' ); ?></span>
	</div>

	<div class="page-h">
		<p class="eyebrow"><?php esc_html_e( 'Order Request', 'maison-vintique-elementor' ); ?></p>
		<h1><?php esc_html_e( 'Checkout', 'maison-vintique-elementor' ); ?></h1>
	</div>

	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<div class="co-wrap">

			<div class="co-main">

				<?php if ( $checkout->get_checkout_fields() ) : ?>

					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<div class="co-sec">
						<h3><?php esc_html_e( 'Billing details', 'maison-vintique-elementor' ); ?></h3>
						<div class="co-fields">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>
					</div>

					<div class="co-sec">
						<h3><?php esc_html_e( 'Delivery', 'maison-vintique-elementor' ); ?></h3>
						<div class="co-fields">
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
						</div>
					</div>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

				<?php endif; ?>

			</div>

			<aside class="co-aside">

				<div class="co-sec">
					<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'maison-vintique-elementor' ); ?></h3>

					<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>

					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>

			</aside>

		</div>

	</form>

</div>
</section>
</main>
