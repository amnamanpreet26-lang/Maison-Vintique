<?php
/**
 * My Account page.
 *
 * Theme override path: your-theme/woocommerce/myaccount/my-account.php
 *
 * This intentionally stays minimal and hooks into WooCommerce's own actions
 * (woocommerce_account_navigation / woocommerce_account_content) instead of
 * hardcoding the dashboard markup here. That routing is what makes Orders,
 * Downloads, Addresses etc. each show their own correct content instead of
 * always showing the dashboard.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_my_account', $current_user );
?>

<?php wc_print_notices(); ?>

<div class="acct">

	<?php do_action( 'woocommerce_account_navigation' ); // loads myaccount/navigation.php ?>

	<div class="woocommerce-MyAccount-content">
		<?php do_action( 'woocommerce_account_content' ); // loads dashboard.php, or the current endpoint (orders, downloads, addresses...) ?>
	</div>

</div>

<?php do_action( 'woocommerce_after_my_account', $current_user ); ?>