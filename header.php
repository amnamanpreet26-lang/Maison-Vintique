<?php
/**
 * The header for our theme.
 *
 * Renders the Maison Vintique site header: crest + wordmark, primary nav,
 * search, account and cart. Matches the approved header design.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mve_cart_count = 0;
if ( function_exists( 'WC' ) && WC()->cart ) {
	$mve_cart_count = WC()->cart->get_cart_contents_count();
}
$mve_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$mve_cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="site-header" class="mv-header">
	<div class="mv-header__inner">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mv-header__brand" aria-label="<?php bloginfo( 'name' ); ?> — Home">
			<span class="mv-header__crest">
				<?php // One source for the crest, shared with the footer and the emails. ?>
				<img src="<?php echo esc_url( mve_logo_url() ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			</span>
			<span class="mv-header__wordmark">
				<span class="mv-header__name">MAISON VINTIQUE</span>
				<span class="mv-header__place">A House of Wine · A Legacy of Taste</span>
			</span>
		</a>

		<nav class="mv-header__nav" aria-label="Primary">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'mv-nav',
				'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'fallback_cb'    => 'mve_default_primary_menu',
			) );
			?>
		</nav>

		<div class="mv-header__actions">

			<form role="search" method="get" class="mv-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg class="mv-search__icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<circle cx="9" cy="9" r="6.25" stroke="currentColor" stroke-width="1.4"/>
					<path d="M18 18L13.6 13.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
				</svg>
				<label class="screen-reader-text" for="mv-search-input">Search wines…</label>
				<input id="mv-search-input" class="mv-search__input" type="search" name="s" placeholder="Search wines…" value="<?php echo esc_attr( get_search_query() ); ?>">
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
			</form>

			<?php
			/*
			 * Cart FIRST in the markup so that, once signed in, the basket sits
			 * to the LEFT of the account button. Logged out there is no basket
			 * at all — nothing is purchasable (inc/woocommerce.php), so a
			 * basket icon would only offer a route that dead-ends.
			 */
			if ( is_user_logged_in() ) :
				?>
				<a class="mv-header__icon mv-header__cart" href="<?php echo esc_url( $mve_cart_url ); ?>" aria-label="Cart">
					<svg viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<path d="M5 7h12l-1 11.2a1.5 1.5 0 0 1-1.5 1.3H7.5A1.5 1.5 0 0 1 6 18.2L5 7Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
						<path d="M8 7V5.6A3 3 0 0 1 11 2.6a3 3 0 0 1 3 3V7" stroke="currentColor" stroke-width="1.4"/>
					</svg>
					<span class="mv-header__cart-count" data-cart-count><?php echo (int) $mve_cart_count; ?></span>
				</a>
			<?php endif; ?>

			<?php
			/*
			 * Account: icon AND words in one button. A bare person icon reads as
			 * "profile" to most people; trade customers were not finding the
			 * login. The label changes to "My Account" once signed in, because
			 * "Trade Login" would then be telling them to do something they
			 * have already done.
			 */
			$mve_account_label = is_user_logged_in()
				? __( 'My Account', 'maison-vintique' )
				: __( 'Trade Login', 'maison-vintique' );
			?>
			<a class="mv-header__account<?php echo is_user_logged_in() ? ' is-in' : ''; ?>" href="<?php echo esc_url( $mve_account_url ); ?>">
				<svg class="mv-header__account-icon" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<circle cx="11" cy="7.4" r="3.6" stroke="currentColor" stroke-width="1.4"/>
					<path d="M3.6 18.4C4.9 14.9 7.6 13 11 13c3.4 0 6.1 1.9 7.4 5.4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
				</svg>
				<span class="mv-header__account-label"><?php echo esc_html( $mve_account_label ); ?></span>
			</a>

			<button class="mv-header__burger" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-header-mobile-nav">
				<span></span><span></span><span></span>
			</button>

		</div>

	</div>

	<div id="site-header-mobile-nav" class="mv-header__mobile-nav">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'mv-nav mv-nav--mobile',
			'items_wrap'     => '<ul id="%1$s-mobile" class="%2$s">%3$s</ul>',
			'fallback_cb'    => 'mve_default_primary_menu',
		) );
		?>
	</div>
</header>
