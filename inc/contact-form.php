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
 * TWO WAYS IN, ONE FORM
 *   Every "Apply for a Trade Account" button now opens this as a POPUP rather
 *   than sending somebody off to the Contact page and losing them. The popup is
 *   the same questions, the same handler and the same validation — see
 *   mve_render_trade_enquiry_popup() below. The Contact page is still there and
 *   still works, which is what happens with scripts off: the buttons are real
 *   links, and JavaScript upgrades them into the popup.
 *
 * The fields are defined once, in mve_enquiry_fields(). Adding a question is a
 * single edit there and it appears on the form, in the popup, in the
 * validation, in the emails and on the review screen.
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

	/*
	 * The same handler serves the page and the popup. The popup posts with
	 * ajax=1 and wants JSON back; the page wants a redirect with the answers
	 * kept. Everything between here and the end is shared, so the two can
	 * never validate differently.
	 */
	$ajax = ! empty( $_POST['ajax'] );

	$stop = function ( $errors, $values = array() ) use ( $ajax, $redirect ) {
		if ( $ajax ) {
			/*
			 * The popup has one line to say what went wrong, so name the field.
			 * "This is required." on its own is no help when nine boxes could
			 * have been the one.
			 */
			$errors = (array) $errors;
			$key    = key( $errors );
			$first  = (string) reset( $errors );

			if ( '_form' !== $key ) {
				$fields = mve_enquiry_fields();
				if ( isset( $fields[ $key ]['label'] ) ) {
					$first = sprintf(
						/* translators: 1: field name, 2: what is wrong with it */
						__( '%1$s: %2$s', 'maison-vintique-elementor' ),
						$fields[ $key ]['label'],
						$first
					);
				}
			}

			wp_send_json_error(
				array(
					'message' => $first,
					'fields'  => $errors,
				),
				400
			);
		}
		mve_enquiry_bounce( $redirect, $errors, $values );
	};

	if ( ! isset( $_POST['mve_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mve_contact_nonce'] ) ), 'mve_contact' ) ) {
		$stop( array( '_form' => __( 'Your session expired. Please send it again.', 'maison-vintique-elementor' ) ) );
	}

	// Honeypot — bots fill everything in, so silently pretend it worked.
	if ( ! empty( $_POST['mvc_website_url'] ) ) {
		if ( $ajax ) {
			wp_send_json_success( array( 'ok' => true ) );
		}
		wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) . '#contact-form' );
		exit;
	}

	// Light per-IP throttle.
	$throttle = 'mve_contact_' . md5( function_exists( 'mve_get_client_ip' ) ? mve_get_client_ip() : '' );
	if ( get_transient( $throttle ) ) {
		$stop( array( '_form' => __( 'That looked like a duplicate. Please wait a moment and try again.', 'maison-vintique-elementor' ) ) );
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
		$stop( $errors, $values );
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

	if ( $ajax ) {
		wp_send_json_success( array( 'ok' => true ) );
	}

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

/* =========================================================================
 * THE POPUP
 * ---------------------------------------------------------------------
 * "Apply for a Trade Account" used to send people to the Contact page. That is
 * a page load away from whatever they were reading, and most of them never came
 * back. Now the same form opens over the page.
 *
 * It reuses the wine-enquiry popup's shell (.mv-enq), so there is one dialog
 * design on the site rather than two that drift apart, and one CSS block.
 *
 * IT DEGRADES. Every button is a real link to the Contact page. JavaScript
 * intercepts the click. With scripts off the Contact page answers exactly as it
 * did before.
 * ====================================================================== */

/**
 * Attributes that turn any link into a trade-enquiry popup opener.
 *
 * Echo this inside an <a> that already points at the enquiry page:
 *   <a href="..." <?php mve_trade_enquiry_attrs(); ?>>Apply for a Trade Account</a>
 */
function mve_trade_enquiry_attrs() {
	echo ' data-mv-enquire data-mv-popup="mv-trade-enquiry"';
}

/**
 * One field inside the popup.
 *
 * Deliberately the .mv-enq__* classes rather than the page form's .mvp-*: this
 * is the popup shell, and it is styled once for both popups.
 *
 * @param string $name  Field key.
 * @param array  $field Definition from mve_enquiry_fields().
 */
function mve_trade_enquiry_popup_field( $name, $field ) {
	$id    = 'mv-tenq-' . str_replace( '_', '-', $name );
	$width = ! empty( $field['width'] ) ? $field['width'] : 'full';
	$req   = ! empty( $field['required'] );
	$place = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	?>
	<div class="mv-enq__field mv-enq__field--<?php echo esc_attr( $width ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $field['label'] ); ?>
			<?php echo $req ? '<span class="mv-enq__req" aria-hidden="true">*</span>' : ''; ?>
		</label>

		<?php if ( 'textarea' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" name="mvc_<?php echo esc_attr( $name ); ?>" rows="3"
				placeholder="<?php echo esc_attr( $place ); ?>"
				<?php echo $req ? 'required' : ''; ?>></textarea>

		<?php elseif ( 'select' === $field['type'] ) : ?>
			<select id="<?php echo esc_attr( $id ); ?>" name="mvc_<?php echo esc_attr( $name ); ?>"
				<?php echo $req ? 'required' : ''; ?>>
				<?php foreach ( $field['options'] as $key => $option ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>
			<input type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $id ); ?>"
				name="mvc_<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $place ); ?>"
				<?php echo $req ? 'required' : ''; ?>>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The trade enquiry popup, printed once per page.
 *
 * Carries the enquiry page URL on the wrapper so the script can also upgrade
 * links that this theme did not write — a menu item, an Elementor button —
 * without anybody having to add an attribute to them.
 */
function mve_render_trade_enquiry_popup() {
	if ( is_admin() ) {
		return;
	}

	$page = function_exists( 'mve_enquiry_page_url' ) ? mve_enquiry_page_url() : '';
	?>
	<div class="mv-enq" id="mv-trade-enquiry" hidden
		data-mv-enq-page="<?php echo esc_attr( $page ); ?>">
		<div class="mv-enq__backdrop" data-mv-enq-close></div>

		<div class="mv-enq__dialog" role="dialog" aria-modal="true" aria-labelledby="mv-tenq-title">
			<button type="button" class="mv-enq__close" data-mv-enq-close
				aria-label="<?php esc_attr_e( 'Close', 'maison-vintique-elementor' ); ?>">&times;</button>

			<div class="mv-enq__body" data-mv-enq-body>
				<p class="mv-enq__eyebrow"><?php esc_html_e( 'Trade account', 'maison-vintique-elementor' ); ?></p>
				<h2 class="mv-enq__title" id="mv-tenq-title"><?php esc_html_e( 'Request a trade account application', 'maison-vintique-elementor' ); ?></h2>
				<p class="mv-enq__wine">
					<?php esc_html_e( 'Tell us a little about your business. We read every enquiry ourselves, and if we are a good fit we will send you the full application.', 'maison-vintique-elementor' ); ?>
				</p>

				<form class="mv-enq__form" method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

					<input type="hidden" name="action" value="mve_contact">
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $page ? $page : home_url( '/' ) ); ?>">
					<?php wp_nonce_field( 'mve_contact', 'mve_contact_nonce' ); ?>

					<div class="mv-enq__grid">
						<?php foreach ( mve_enquiry_fields() as $name => $field ) : ?>
							<?php mve_trade_enquiry_popup_field( $name, $field ); ?>
						<?php endforeach; ?>
					</div>

					<?php // Honeypot — the same field name the page form uses. ?>
					<div class="mv-enq__hp" aria-hidden="true">
						<label for="mv-tenq-url"><?php esc_html_e( 'Leave this field empty', 'maison-vintique-elementor' ); ?></label>
						<input type="text" id="mv-tenq-url" name="mvc_website_url" tabindex="-1" autocomplete="off">
					</div>

					<p class="mv-enq__error" data-mv-enq-error hidden></p>

					<button type="submit" class="mv-enq__submit"
						data-sending="<?php esc_attr_e( 'Sending…', 'maison-vintique-elementor' ); ?>">
						<?php esc_html_e( 'Request an application', 'maison-vintique-elementor' ); ?>
					</button>

					<p class="mv-enq__foot">
						<?php esc_html_e( 'Trade only. Submitting this does not open an account — it asks us for the application.', 'maison-vintique-elementor' ); ?>
					</p>
				</form>
			</div>

			<?php // Swapped in once it has been sent. ?>
			<div class="mv-enq__done" data-mv-enq-done hidden tabindex="-1">
				<div class="mv-enq__tick" aria-hidden="true">
					<svg viewBox="0 0 48 48" width="44" height="44" focusable="false">
						<circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".35"></circle>
						<path d="M15 24.5l6.5 6.5L33 19" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</div>
				<h2 class="mv-enq__title"><?php esc_html_e( 'Thank you — we have your enquiry.', 'maison-vintique-elementor' ); ?></h2>
				<p class="mv-enq__text">
					<?php esc_html_e( 'A confirmation is on its way to you now. We review enquiries personally, and if we are a good fit we will email you a private link to the full trade application.', 'maison-vintique-elementor' ); ?>
				</p>
				<button type="button" class="mv-enq__submit" data-mv-enq-close><?php esc_html_e( 'Close', 'maison-vintique-elementor' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'mve_render_trade_enquiry_popup', 21 );
