<?php
/**
 * Contact form handler for template-contact.php.
 *
 * Posts to admin-post.php (so it works with no JavaScript), is nonce-checked,
 * honeypot- and rate-limited, then emails the address in the page's ACF
 * `contact_form_recipient` field — falling back to the site admin email.
 *
 * Hook `mve_contact_submitted` to push the enquiry into a CRM instead of, or
 * as well as, sending the email.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle a contact form submission.
 */
function mve_handle_contact_form() {
	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

	// Only ever bounce back to this site.
	if ( 0 !== strpos( $redirect, home_url() ) ) {
		$redirect = home_url( '/' );
	}

	$fail = add_query_arg( 'contact', 'error', $redirect ) . '#contact-form';

	if ( ! isset( $_POST['mve_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mve_contact_nonce'] ) ), 'mve_contact' ) ) {
		wp_safe_redirect( $fail );
		exit;
	}

	// Honeypot — bots fill everything in, so silently pretend it worked.
	if ( ! empty( $_POST['mvc_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) . '#contact-form' );
		exit;
	}

	$name     = isset( $_POST['mvc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mvc_name'] ) ) : '';
	$business = isset( $_POST['mvc_business'] ) ? sanitize_text_field( wp_unslash( $_POST['mvc_business'] ) ) : '';
	$email    = isset( $_POST['mvc_email'] ) ? sanitize_email( wp_unslash( $_POST['mvc_email'] ) ) : '';
	$message  = isset( $_POST['mvc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mvc_message'] ) ) : '';

	if ( ! $name || ! $message || ! is_email( $email ) ) {
		wp_safe_redirect( $fail );
		exit;
	}

	// Light per-IP throttle, same approach as the newsletter endpoint.
	$throttle_key = 'mve_contact_' . md5( function_exists( 'mve_get_client_ip' ) ? mve_get_client_ip() : '' );
	if ( get_transient( $throttle_key ) ) {
		wp_safe_redirect( $fail );
		exit;
	}
	set_transient( $throttle_key, 1, 30 );

	// Recipient: the page's ACF field, else the site admin.
	$page_id   = url_to_postid( $redirect );
	$recipient = $page_id ? mve_field( 'contact_form_recipient', '', $page_id ) : '';
	if ( ! is_email( $recipient ) ) {
		$recipient = get_option( 'admin_email' );
	}

	$subject = sprintf(
		/* translators: %s: site name */
		__( '[%s] New enquiry from the website', 'maison-vintique-elementor' ),
		get_bloginfo( 'name' )
	);

	$body = sprintf(
		"Name: %s\nBusiness: %s\nEmail: %s\n\nMessage:\n%s\n",
		$name,
		$business ? $business : '—',
		$email,
		$message
	);

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		sprintf( 'Reply-To: %s <%s>', $name, $email ),
	);

	$sent = wp_mail( $recipient, $subject, $body, $headers );

	/**
	 * Fires after a contact enquiry is processed.
	 *
	 * @param array $enquiry name / business / email / message
	 * @param bool  $sent    whether wp_mail() reported success
	 */
	do_action(
		'mve_contact_submitted',
		compact( 'name', 'business', 'email', 'message' ),
		$sent
	);

	wp_safe_redirect( add_query_arg( 'contact', $sent ? 'sent' : 'error', $redirect ) . '#contact-form' );
	exit;
}
add_action( 'admin_post_nopriv_mve_contact', 'mve_handle_contact_form' );
add_action( 'admin_post_mve_contact', 'mve_handle_contact_form' );
