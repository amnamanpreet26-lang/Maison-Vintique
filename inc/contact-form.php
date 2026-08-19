<?php
/**
 * inc/contact-form.php
 *
 * THE SHORT TRADE ENQUIRY — stage one of the trade workflow.
 *
 * This is the same form the Contact page has always had, in the same panel with
 * the same mvp-* classes and styling. What changed is the questions: it now
 * asks the nine things the client listed, and the button says "Request a Trade
 * Account Application."
 *
 * It is NOT the trade application. The full nine-section application is private
 * and reachable only by invitation — see inc/trade-enquiries.php for why, and
 * inc/trade-application.php for the gate.
 *
 * WHAT HAPPENS ON SUBMIT
 *   1. Nonce, honeypot, per-address throttle, validation.
 *   2. The enquiry is stored as a Trade Enquiry, so the office has a list to
 *      work through rather than a mailbox to search.
 *   3. The enquirer gets an acknowledgement; the shop gets a notification with
 *      a button straight to the review screen.
 *
 * The fields are defined once, in mve_enquiry_fields(). Adding a question is a
 * single edit there and it appears on the form, in the validation, in the
 * emails and on the review screen.
 *
 * Hook `mve_contact_submitted` to push an enquiry into a CRM as well.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What they typed last time, after a rejected submit.
 *
 * @return array {errors, values}
 */
function mve_enquiry_flash() {
	static $flash = null;
	if ( null !== $flash ) {
		return $flash;
	}

	$flash = array(
		'errors' => array(),
		'values' => array(),
	);

	$token = isset( $_GET['mvc_ref'] ) ? sanitize_key( wp_unslash( $_GET['mvc_ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	if ( ! $token ) {
		return $flash;
	}

	$stored = get_transient( 'mve_enq_' . $token );
	if ( is_array( $stored ) ) {
		$flash = wp_parse_args( $stored, $flash );
		delete_transient( 'mve_enq_' . $token );
	}
	return $flash;
}

/**
 * The value to put back in a box.
 *
 * @param string $name Field key.
 * @return string
 */
function mve_enquiry_value( $name ) {
	$flash = mve_enquiry_flash();
	return isset( $flash['values'][ $name ] ) ? $flash['values'][ $name ] : '';
}

/**
 * The problem with a box, if there was one.
 *
 * @param string $name Field key.
 * @return string
 */
function mve_enquiry_error( $name ) {
	$flash = mve_enquiry_flash();
	return isset( $flash['errors'][ $name ] ) ? $flash['errors'][ $name ] : '';
}

/**
 * Render one enquiry field, in the contact form's own markup.
 *
 * @param string $name  Field key.
 * @param array  $field Definition from mve_enquiry_fields().
 */
function mve_enquiry_field( $name, $field ) {
	$id       = 'mvc-' . str_replace( '_', '-', $name );
	$value    = mve_enquiry_value( $name );
	$error    = mve_enquiry_error( $name );
	$required = ! empty( $field['required'] );
	$width    = isset( $field['width'] ) ? $field['width'] : 'full';
	$holder   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

	$classes = array( 'mvp-field', 'mvp-field--' . $width );
	if ( $error ) {
		$classes[] = 'is-invalid';
	}
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $field['label'] ); ?>
			<?php echo $required ? '<span class="mvp-req" aria-hidden="true">*</span>' : ''; ?>
		</label>

		<?php if ( 'textarea' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" name="mvc_<?php echo esc_attr( $name ); ?>" rows="3"
				<?php echo $required ? 'required' : ''; ?>
				placeholder="<?php echo esc_attr( $holder ); ?>"><?php echo esc_textarea( $value ); ?></textarea>

		<?php elseif ( 'select' === $field['type'] ) : ?>
			<select id="<?php echo esc_attr( $id ); ?>" name="mvc_<?php echo esc_attr( $name ); ?>" <?php echo $required ? 'required' : ''; ?>>
				<?php foreach ( $field['options'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $value, (string) $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>
			<input type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $id ); ?>"
				name="mvc_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"
				<?php echo $required ? 'required' : ''; ?>
				placeholder="<?php echo esc_attr( $holder ); ?>">
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<p class="mvp-field__error"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Clean one posted value.
 *
 * @param array  $field Definition.
 * @param string $raw   Raw value.
 * @return string
 */
function mve_clean_enquiry_value( $field, $raw ) {
	switch ( $field['type'] ) {
		case 'email':
			return sanitize_email( wp_unslash( (string) $raw ) );

		case 'textarea':
			return sanitize_textarea_field( wp_unslash( (string) $raw ) );

		case 'select':
			$value = sanitize_text_field( wp_unslash( (string) $raw ) );
			return array_key_exists( $value, $field['options'] ) ? $value : '';

		default:
			return sanitize_text_field( wp_unslash( (string) $raw ) );
	}
}

/**
 * Handle a trade enquiry.
 */
function mve_handle_contact_form() {
	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

	// Only ever bounce back to this site.
	if ( 0 !== strpos( $redirect, home_url() ) ) {
		$redirect = home_url( '/' );
	}

	if ( ! isset( $_POST['mve_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mve_contact_nonce'] ) ), 'mve_contact' ) ) {
		mve_enquiry_bounce( $redirect, array( '_form' => __( 'Your session expired. Please send it again.', 'maison-vintique-elementor' ) ), array() );
	}

	// Honeypot — bots fill everything in, so silently pretend it worked.
	if ( ! empty( $_POST['mvc_website_url'] ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) . '#contact-form' );
		exit;
	}

	// Light per-IP throttle.
	$throttle = 'mve_contact_' . md5( function_exists( 'mve_get_client_ip' ) ? mve_get_client_ip() : '' );
	if ( get_transient( $throttle ) ) {
		mve_enquiry_bounce( $redirect, array( '_form' => __( 'That looked like a duplicate. Please wait a moment and try again.', 'maison-vintique-elementor' ) ), array() );
	}

	$fields = mve_enquiry_fields();
	$values = array();
	$errors = array();

	foreach ( $fields as $name => $field ) {
		$raw             = isset( $_POST[ 'mvc_' . $name ] ) ? $_POST[ 'mvc_' . $name ] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below.
		$values[ $name ] = mve_clean_enquiry_value( $field, $raw );

		if ( ! empty( $field['required'] ) && '' === $values[ $name ] ) {
			$errors[ $name ] = __( 'This is required.', 'maison-vintique-elementor' );
			continue;
		}

		if ( 'email' === $field['type'] && $values[ $name ] && ! is_email( $values[ $name ] ) ) {
			$errors[ $name ] = __( 'That does not look like an email address.', 'maison-vintique-elementor' );
		}
	}

	if ( $errors ) {
		mve_enquiry_bounce( $redirect, $errors, $values );
	}

	set_transient( $throttle, 1, 30 );

	// ---- store it ------------------------------------------------------
	$enquiry_id = mve_create_enquiry( $values );

	// ---- tell both sides -----------------------------------------------
	$sent = false;
	if ( $enquiry_id ) {
		$sent = mve_send_email( 'mve_enquiry_received', 0, array( 'enquiry' => $enquiry_id ) );
		mve_send_email( 'mve_enquiry_admin', 0, array( 'enquiry' => $enquiry_id ) );
	}

	// Belt and braces: if the branded emails could not go out, the office still
	// gets told. An enquiry nobody sees is a customer lost.
	if ( ! $enquiry_id || ! mve_shop_was_notified( $enquiry_id ) ) {
		mve_plain_enquiry_notice( $values );
	}

	/**
	 * Fires after a trade enquiry is stored.
	 *
	 * @param array $enquiry    The answers.
	 * @param bool  $sent       Whether the acknowledgement was accepted for sending.
	 * @param int   $enquiry_id The stored enquiry.
	 */
	do_action( 'mve_contact_submitted', $values, $sent, $enquiry_id );

	wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) . '#contact-form' );
	exit;
}
add_action( 'admin_post_nopriv_mve_contact', 'mve_handle_contact_form' );
add_action( 'admin_post_mve_contact', 'mve_handle_contact_form' );

/**
 * Did the branded notification to the shop actually go?
 *
 * @param int $enquiry_id Enquiry.
 * @return bool
 */
function mve_shop_was_notified( $enquiry_id ) {
	return (bool) get_post_meta( $enquiry_id, '_mve_shop_notified', true );
}

/**
 * The fallback notification — plain text, no WooCommerce needed.
 *
 * @param array $values Answers.
 */
function mve_plain_enquiry_notice( $values ) {
	$to = function_exists( 'mve_notification_email' ) ? mve_notification_email() : get_option( 'admin_email' );
	if ( ! $to ) {
		return;
	}

	$lines = array( __( 'A new trade enquiry has been submitted.', 'maison-vintique-elementor' ), '' );
	foreach ( mve_enquiry_fields() as $name => $field ) {
		if ( empty( $values[ $name ] ) ) {
			continue;
		}
		$lines[] = sprintf( '%s: %s', $field['label'], $values[ $name ] );
	}
	$lines[] = '';
	$lines[] = admin_url( 'edit.php?post_type=' . MVE_ENQUIRY_TYPE );

	wp_mail(
		$to,
		sprintf(
			/* translators: 1: site name, 2: business name */
			__( '[%1$s] Trade enquiry — %2$s', 'maison-vintique-elementor' ),
			get_bloginfo( 'name' ),
			isset( $values['business'] ) ? $values['business'] : ''
		),
		implode( "\n", $lines ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);
}

/**
 * Send them back with their answers and the problems.
 *
 * @param string $redirect Page URL.
 * @param array  $errors   field => message.
 * @param array  $values   What they typed.
 */
function mve_enquiry_bounce( $redirect, $errors, $values ) {
	$token = wp_generate_password( 16, false, false );
	set_transient(
		'mve_enq_' . $token,
		array(
			'errors' => $errors,
			'values' => $values,
		),
		15 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'contact' => 'error',
				'mvc_ref' => $token,
			),
			$redirect
		) . '#contact-form'
	);
	exit;
}
