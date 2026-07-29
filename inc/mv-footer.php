<?php
/**
 * Footer support: asset loading and the newsletter subscribe AJAX endpoint.
 *
 * Loaded from functions.php. The footer MENU LOCATIONS are registered in
 * functions.php alongside the header one (mve_setup) so every location this
 * theme has lives in a single list — see register_nav_menus() there.
 *
 * @package maison-vintique-elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render one footer menu column.
 *
 * Assign a menu in Appearance → Menus → "Display location" and it renders.
 * Until then the column falls back to $defaults so the footer never shows an
 * empty gap — as soon as a menu is assigned to the location, it takes over.
 *
 * @param string $location Registered nav menu location, e.g. 'footer-explore'.
 * @param array  $defaults label => url pairs used when no menu is assigned.
 */
function mve_footer_menu( $location, $defaults = array() ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'mv-footer-nav',
				'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);
		return;
	}

	if ( ! $defaults ) {
		return;
	}

	echo '<ul class="mv-footer-nav">';
	foreach ( $defaults as $label => $url ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Enqueue the footer stylesheet and the newsletter form script.
 *
 * NOTE: paths use get_stylesheet_directory*() — this is a CHILD theme, so
 * get_template_directory*() would point at Hello Elementor and 404.
 */
function mve_enqueue_footer_assets() {
	$theme_uri = get_stylesheet_directory_uri();
	$theme_dir = get_stylesheet_directory();

	// Only enqueue the stylesheet if it actually exists — the theme currently
	// ships its footer styles via Customizer → Additional CSS instead.
	if ( file_exists( $theme_dir . '/assets/css/footer.css' ) ) {
		wp_enqueue_style(
			'mve-footer',
			$theme_uri . '/assets/css/footer.css',
			array(),
			filemtime( $theme_dir . '/assets/css/footer.css' )
		);
	}

	wp_enqueue_script(
		'mve-footer',
		$theme_uri . '/assets/js/footer.js',
		array(),
		file_exists( $theme_dir . '/assets/js/footer.js' ) ? filemtime( $theme_dir . '/assets/js/footer.js' ) : '1.0.0',
		true
	);

	wp_localize_script( 'mve-footer', 'mveNewsletter', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'i18n'    => array(
			'success'       => __( 'Thanks — you\'re on the list.', 'maison-vintique' ),
			'invalidEmail'  => __( 'Please enter a valid email address.', 'maison-vintique' ),
			'alreadySubbed' => __( 'That email is already subscribed.', 'maison-vintique' ),
			'genericError'  => __( 'Something went wrong. Please try again.', 'maison-vintique' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'mve_enqueue_footer_assets' );

/**
 * AJAX handler: mv_newsletter_subscribe
 * Validates + nonce-checks the email, stores it, and fires an action so
 * you can hook in Mailchimp / Klaviyo / Brevo / etc. without touching
 * this file again.
 */
function mve_handle_newsletter_subscribe() {
	check_ajax_referer( 'mve_newsletter_subscribe', 'nonce' );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( array( 'code' => 'invalid_email' ), 400 );
	}

	// Very light throttling per IP so the endpoint can't be hammered.
	$ip           = mve_get_client_ip();
	$throttle_key = 'mve_nl_throttle_' . md5( $ip );
	if ( get_transient( $throttle_key ) ) {
		wp_send_json_error( array( 'code' => 'rate_limited' ), 429 );
	}
	set_transient( $throttle_key, 1, 10 ); // 10 seconds between attempts.

	$subscribers = get_option( 'mve_newsletter_subscribers', array() );

	if ( isset( $subscribers[ $email ] ) ) {
		wp_send_json_error( array( 'code' => 'already_subscribed' ), 200 );
	}

	$subscribers[ $email ] = array(
		'date' => current_time( 'mysql' ),
		'ip'   => $ip,
	);
	update_option( 'mve_newsletter_subscribers', $subscribers, false );

	/**
	 * Fires after a new newsletter signup is stored.
	 * Hook your ESP (Mailchimp, Klaviyo, Brevo, ConvertKit, etc.) here:
	 *
	 * add_action( 'mve_newsletter_subscribed', function ( $email ) {
	 *     // call your ESP's API with $email
	 * } );
	 */
	do_action( 'mve_newsletter_subscribed', $email );

	// Optional: let the site admin know.
	$admin_email = get_option( 'admin_email' );
	if ( $admin_email ) {
		wp_mail(
			$admin_email,
			sprintf( '[%s] New newsletter subscriber', get_bloginfo( 'name' ) ),
			sprintf( 'New subscriber: %s', $email )
		);
	}

	wp_send_json_success( array( 'email' => $email ) );
}
add_action( 'wp_ajax_mv_newsletter_subscribe', 'mve_handle_newsletter_subscribe' );
add_action( 'wp_ajax_nopriv_mv_newsletter_subscribe', 'mve_handle_newsletter_subscribe' );

/**
 * Small helper to grab the visitor's IP for throttling/logging.
 */
function mve_get_client_ip() {
	foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = explode( ',', wp_unslash( $_SERVER[ $key ] ) )[0]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return trim( sanitize_text_field( $ip ) );
		}
	}
	return '';
}