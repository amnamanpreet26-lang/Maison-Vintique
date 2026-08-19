<?php
/**
 * inc/trade-popup.php
 *
 * "Trade Pricing Available" — shown once to a logged-out visitor.
 *
 * ONCE, not once per page view: the flag lives in localStorage, so it survives
 * navigation and a return visit. Clearing it is one line in the console, and
 * there is a filter to change how long it stays dismissed.
 *
 * Never shown to a signed-in customer (they already have pricing), on the
 * account pages (they are mid-signup or mid-login), or at checkout.
 *
 * WHERE TO EDIT THE WORDS
 *   Appearance → Customize → Trade Pricing Popup.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Should the popup be printed on this request?
 *
 * @return bool
 */
function mve_show_trade_popup() {
	/*
	 * PREVIEW OVERRIDE.
	 *
	 * The popup is for logged-OUT visitors, so an administrator testing the
	 * site never sees it — which reads exactly like "the popup is broken".
	 * Add ?mv_popup=preview to any URL to force it, signed in or not. It also
	 * ignores the "already dismissed" flag, so it shows every time.
	 */
	if ( isset( $_GET['mv_popup'] ) && 'preview' === $_GET['mv_popup'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
		return true;
	}

	if ( is_admin() ) {
		return false;
	}
	if ( ! get_theme_mod( 'mve_popup_enabled', true ) ) {
		return false;
	}
	/*
	 * "I can't see the popup" is almost always this: an administrator checking
	 * the site is signed in, and the popup is for people who are not. The
	 * Customizer checkbox below shows it to signed-in users too, so it can be
	 * looked at without signing out or opening a private window.
	 */
	if ( is_user_logged_in() && ! get_theme_mod( 'mve_popup_show_logged_in', false ) ) {
		return false;
	}
	// Not while they are trying to sign in, register, or pay.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return false;
	}
	if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) {
		return false;
	}
	return (bool) apply_filters( 'mve_show_trade_popup', true );
}

/**
 * Print the dialog just before </body>.
 *
 * A native <dialog> element: it gets focus trapping, Escape-to-close and a
 * backdrop from the browser, which is a lot of fiddly code not to have to
 * write or maintain.
 */
function mve_render_trade_popup() {
	if ( ! mve_show_trade_popup() ) {
		return;
	}

	$eyebrow = get_theme_mod( 'mve_popup_eyebrow', __( 'Trade Only', 'maison-vintique' ) );
	$title   = get_theme_mod( 'mve_popup_title', __( 'Trade Pricing Available', 'maison-vintique' ) );
	$text    = get_theme_mod( 'mve_popup_text', __( 'Register for a trade account to view your exclusive pricing.', 'maison-vintique' ) );
	$cta     = get_theme_mod( 'mve_popup_cta', __( 'Apply for Trade Account', 'maison-vintique' ) );
	// The short trade enquiry, never the full application — that one is by
	// invitation only. See mve_apply_url().
	$account = function_exists( 'mve_apply_url' )
		? mve_apply_url()
		: ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) );
	$url     = get_theme_mod( 'mve_popup_cta_url', '' );
	$url     = $url ? $url : $account;
	$delay   = (int) get_theme_mod( 'mve_popup_delay', 1200 );
	$decor   = get_theme_mod( 'mve_popup_image', '' );
	$preview = isset( $_GET['mv_popup'] ) && 'preview' === $_GET['mv_popup']; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$days    = (int) apply_filters( 'mve_trade_popup_remember_days', (int) get_theme_mod( 'mve_popup_days', 30 ) );
	?>
	<dialog class="mv-popup<?php echo $decor ? ' mv-popup--decor' : ''; ?>"
		id="mv-trade-popup"
		data-delay="<?php echo esc_attr( $preview ? 200 : max( 0, $delay ) ); ?>"
		data-days="<?php echo esc_attr( max( 0, $days ) ); ?>"
		<?php echo $preview ? 'data-preview="1"' : ''; ?>
		<?php if ( $decor ) : ?>style="--mv-popup-decor:url('<?php echo esc_url( $decor ); ?>')"<?php endif; ?>
		aria-labelledby="mv-popup-title">
		<div class="mv-popup__inner">
			<div class="mv-popup__frame" aria-hidden="true"></div>
			<button type="button" class="mv-popup__close" data-mv-popup-close aria-label="<?php esc_attr_e( 'Close', 'maison-vintique' ); ?>">&times;</button>

			<?php if ( $eyebrow ) : ?>
				<p class="mv-popup__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="mv-popup__title" id="mv-popup-title"><?php echo esc_html( $title ); ?></h2>

			<?php if ( $text ) : ?>
				<p class="mv-popup__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>

			<a class="mv-popup__cta" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cta ); ?></a>

			<button type="button" class="mv-popup__dismiss" data-mv-popup-close>
				<?php esc_html_e( 'Keep browsing', 'maison-vintique' ); ?>
			</button>
		</div>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'mve_render_trade_popup', 30 );

/**
 * Customizer panel for the wording.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function mve_popup_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'mve_popup',
		array(
			'title'       => __( 'Trade Pricing Popup', 'maison-vintique' ),
			'priority'    => 91,
			'description' => __( 'Shown once to visitors who are not signed in. Never shown to signed-in customers, or on the account, basket and checkout pages.', 'maison-vintique' ),
		)
	);

	$fields = array(
		'mve_popup_enabled' => array(
			'label'   => __( 'Show the popup', 'maison-vintique' ),
			'type'    => 'checkbox',
			'default' => true,
			'sanitize' => 'mve_sanitize_checkbox',
		),
		'mve_popup_show_logged_in' => array(
			'label'       => __( 'Show it to signed-in users too (for testing)', 'maison-vintique' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description' => __( 'Normally the popup is only for visitors who are not signed in, which means you never see it while you are logged in to wp-admin. Tick this to see it yourself, then untick it when you are done. Adding ?mv_popup=preview to any address does the same thing for one page view.', 'maison-vintique' ),
			'sanitize'    => 'mve_sanitize_checkbox',
		),
		'mve_popup_eyebrow' => array(
			'label'   => __( 'Eyebrow', 'maison-vintique' ),
			'type'    => 'text',
			'default' => __( 'Trade Only', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_popup_title'   => array(
			'label'   => __( 'Heading', 'maison-vintique' ),
			'type'    => 'text',
			'default' => __( 'Trade Pricing Available', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_popup_text'    => array(
			'label'   => __( 'Text', 'maison-vintique' ),
			'type'    => 'textarea',
			'default' => __( 'Register for a trade account to view your exclusive pricing.', 'maison-vintique' ),
			'sanitize' => 'sanitize_textarea_field',
		),
		'mve_popup_cta'     => array(
			'label'   => __( 'Button label', 'maison-vintique' ),
			'type'    => 'text',
			'default' => __( 'Apply for Trade Account', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_popup_cta_url' => array(
			'label'       => __( 'Button link', 'maison-vintique' ),
			'type'        => 'url',
			'default'     => '',
			'description' => __( 'Leave empty to send them to the account page.', 'maison-vintique' ),
			'sanitize'    => 'esc_url_raw',
		),
		'mve_popup_image'   => array(
			'label'       => __( 'Decorative background image', 'maison-vintique' ),
			'type'        => 'url',
			'default'     => '',
			'description' => __( 'Optional. A PNG with a transparent middle — botanical corners, a border engraving. Upload it to the Media Library and paste the URL here.', 'maison-vintique' ),
			'sanitize'    => 'esc_url_raw',
		),
		'mve_popup_delay'   => array(
			'label'       => __( 'Delay before it appears (milliseconds)', 'maison-vintique' ),
			'type'        => 'number',
			'default'     => 1200,
			'description' => __( 'Appearing instantly reads as an ad. About a second in reads as an offer.', 'maison-vintique' ),
			'sanitize'    => 'absint',
		),
		'mve_popup_days'    => array(
			'label'       => __( 'Days to stay dismissed', 'maison-vintique' ),
			'type'        => 'number',
			'default'     => 30,
			'description' => __( 'How long before a visitor who closed it could see it again. 0 means never again.', 'maison-vintique' ),
			'sanitize'    => 'absint',
		),
	);

	$priority = 10;
	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'       => $field['label'],
				'section'     => 'mve_popup',
				'type'        => $field['type'],
				'priority'    => $priority,
				'description' => isset( $field['description'] ) ? $field['description'] : '',
			)
		);
		$priority += 10;
	}
}
add_action( 'customize_register', 'mve_popup_customizer' );

/**
 * Checkbox sanitiser.
 *
 * @param mixed $value Raw.
 * @return bool
 */
function mve_sanitize_checkbox( $value ) {
	return (bool) $value;
}
