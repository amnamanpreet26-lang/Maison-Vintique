<?php
/**
 * Footer support: nav menu locations, asset loading, and the newsletter
 * subscribe AJAX endpoint.
 *
 * Add this line once near the top of functions.php:
 *   require_once get_template_directory() . '/inc/mv-footer.php';
 *
 * @package maison-vintique-elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the three footer menu locations used in footer.php.
 * (If 'footer' is already registered elsewhere for a single combined
 * menu, you can remove it — the footer template no longer uses it.)
 */
function mve_register_footer_menus() {
	register_nav_menus( array(
		'footer-explore' => __( 'Footer — Explore', 'maison-vintique' ),
		'footer-trade'   => __( 'Footer — Trade', 'maison-vintique' ),
		'footer-legal'   => __( 'Footer — Legal (Privacy / Cookies)', 'maison-vintique' ),
	) );
}
add_action( 'after_setup_theme', 'mve_register_footer_menus' );

/**
 * Enqueue the footer stylesheet and the newsletter form script.
 */
function mve_enqueue_footer_assets() {
	$theme_uri = get_template_directory_uri();
	$theme_dir = get_template_directory();

	wp_enqueue_style(
		'mve-footer',
		$theme_uri . '/assets/css/footer.css',
		array(),
		file_exists( $theme_dir . '/assets/css/footer.css' ) ? filemtime( $theme_dir . '/assets/css/footer.css' ) : '1.0.0'
	);

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