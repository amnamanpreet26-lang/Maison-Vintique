<?php
/**
 * My Account navigation.
 *
 * Theme override path: your-theme/woocommerce/myaccount/navigation.php
 *
 * Pulls the menu from wc_get_account_menu_items() so it always matches
 * whatever endpoints are actually registered (Dashboard, Orders, Downloads,
 * Addresses, Account details, Log out, plus anything a plugin adds).
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

// One small inline icon per known endpoint. Anything unrecognised falls back
// to $icon_default so a plugin adding a new tab never breaks the layout.
$icons = array(
	'dashboard'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>',
	'orders'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M7 6V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1"/><path d="M9 11a3 3 0 0 0 6 0"/></svg>',
	'downloads'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 21h16"/></svg>',
	'edit-address'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg>',
	'edit-account'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4.5 5-6 8-6s6.5 1.5 8 6"/></svg>',
	'customer-logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
);
$icon_default = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>';
?>
<nav class="acct-nav woocommerce-MyAccount-navigation">
	<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
		$is_active = false !== strpos( wc_get_account_menu_item_classes( $endpoint ), 'is-active' );
	?>
		<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
			class="<?php echo $is_active ? 'on' : ''; ?>">
			<?php echo isset( $icons[ $endpoint ] ) ? $icons[ $endpoint ] : $icon_default; // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVGs, no user input ?>
			<span><?php echo esc_html( $label ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>