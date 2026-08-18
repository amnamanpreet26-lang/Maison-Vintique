<?php
/**
 * inc/email-tools.php
 *
 * The plumbing behind "am I actually receiving these".
 *
 * ONE ADDRESS FOR THE SHOP
 *   WooCommerce keeps a separate Recipient box inside each admin email, so the
 *   address that receives notifications is set in three or four different
 *   places and it is easy to leave one on the default. mve_notification_email()
 *   is a single setting — WooCommerce → Settings → Emails, at the very top —
 *   and every shop-facing email falls back to it. Anything explicitly set on an
 *   individual email still wins, so nothing already configured is overridden.
 *
 * WHY MAIL DOES NOT ARRIVE
 *   Almost never the templates. It is the host: shared hosting often blocks
 *   PHP's mail() outright, and mail sent from a hosting IP without
 *   authentication is filed as spam by Gmail and Outlook whatever it says. So
 *   this file also records every send and every failure, and the Emails screen
 *   under WooCommerce reports what it found.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * THE ADDRESS THE SHOP IS NOTIFIED ON
 * ====================================================================== */

/**
 * Where notifications to the shop go.
 *
 * @return string One address, or several separated by commas.
 */
function mve_notification_email() {
	$value = trim( (string) get_option( 'mve_notification_email', '' ) );
	if ( '' === $value ) {
		$value = get_option( 'admin_email' );
	}
	return apply_filters( 'mve_notification_email', $value );
}

/**
 * Put that setting at the top of WooCommerce → Settings → Emails.
 *
 * First thing on the screen, because "where do I put my email address" is the
 * first question anybody asks of it.
 *
 * @param array $settings WooCommerce email settings.
 * @return array
 */
function mve_email_settings( $settings ) {
	$ours = array(
		array(
			'title' => __( 'Maison Vintique — where notifications go', 'maison-vintique' ),
			'type'  => 'title',
			'id'    => 'mve_notifications',
			'desc'  => __( 'The address the shop is notified on: new orders, new trade account applications, cancelled and failed orders. Emails to customers are unaffected — those always go to the customer.', 'maison-vintique' ),
		),
		array(
			'title'    => __( 'Send shop notifications to', 'maison-vintique' ),
			'desc'     => __( 'Separate several addresses with commas. Leave empty to use the WordPress admin email.', 'maison-vintique' ),
			'id'       => 'mve_notification_email',
			'type'     => 'text',
			'default'  => '',
			'css'      => 'min-width:340px;',
			'desc_tip' => false,
			'placeholder' => get_option( 'admin_email' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'mve_notifications',
		),
	);

	return array_merge( $ours, $settings );
}
add_filter( 'woocommerce_email_settings', 'mve_email_settings' );

/**
 * Default WooCommerce's own admin emails to that address.
 *
 * Only when the email's own Recipient box has never been filled in — an address
 * somebody deliberately typed into "New order" is left exactly as it is.
 *
 * @param string $recipient Current recipient.
 * @param mixed  $object    Order.
 * @param mixed  $email     The WC_Email, on WooCommerce 3.5+.
 * @return string
 */
function mve_default_admin_recipient( $recipient, $object = null, $email = null ) {
	if ( $email instanceof WC_Email && '' === trim( (string) $email->get_option( 'recipient', '' ) ) ) {
		return mve_notification_email();
	}
	return $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'mve_default_admin_recipient', 10, 3 );
add_filter( 'woocommerce_email_recipient_cancelled_order', 'mve_default_admin_recipient', 10, 3 );
add_filter( 'woocommerce_email_recipient_failed_order', 'mve_default_admin_recipient', 10, 3 );

/* =========================================================================
 * THE MAIL LOG
 * ---------------------------------------------------------------------
 * Two options, both autoload-off. Not a full log — just the last thing that
 * happened, which is all that is needed to answer "did the site even try".
 * ====================================================================== */

/**
 * Record every message handed to the mail server.
 *
 * This fires BEFORE sending, so it records an attempt, not a delivery. Nothing
 * in PHP can tell you a message was delivered — only that it was accepted.
 *
 * @param array $args wp_mail() arguments.
 * @return array
 */
function mve_log_mail_attempt( $args ) {
	$to = isset( $args['to'] ) ? $args['to'] : '';
	update_option(
		'mve_last_mail_sent',
		array(
			'when'    => current_time( 'mysql' ),
			'to'      => is_array( $to ) ? implode( ', ', $to ) : (string) $to,
			'subject' => isset( $args['subject'] ) ? (string) $args['subject'] : '',
		),
		false
	);
	return $args;
}
add_filter( 'wp_mail', 'mve_log_mail_attempt' );

/**
 * Record the reason a message was refused.
 *
 * @param WP_Error $error Error from PHPMailer.
 */
function mve_log_mail_failure( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return;
	}
	$data = $error->get_error_data();
	update_option(
		'mve_last_mail_error',
		array(
			'when'    => current_time( 'mysql' ),
			'message' => $error->get_error_message(),
			'to'      => isset( $data['to'] ) ? ( is_array( $data['to'] ) ? implode( ', ', $data['to'] ) : (string) $data['to'] ) : '',
			'subject' => isset( $data['subject'] ) ? (string) $data['subject'] : '',
		),
		false
	);
}
add_action( 'wp_mail_failed', 'mve_log_mail_failure' );

/* =========================================================================
 * DIAGNOSIS
 * ====================================================================== */

/**
 * Is something handling mail properly, rather than PHP's mail() function?
 *
 * @return array {
 *     @type string $name    What it is, or ''.
 *     @type bool   $certain Whether it was recognised by name or only guessed.
 * }
 */
function mve_smtp_plugin() {
	$known = array(
		'WPMailSMTP\\Core'             => 'WP Mail SMTP',
		'FluentMail\\App\\Application' => 'FluentSMTP',
		'PostmanEmailLogs'             => 'Post SMTP',
		'Post_SMTP'                    => 'Post SMTP',
		'WPO_MailChecker'              => 'WP Offload SES',
		'MailPoet\\Mailer\\Mailer'     => 'MailPoet',
		'Sendinblue\\Client'           => 'Brevo (Sendinblue)',
		'SendGrid'                     => 'SendGrid',
	);
	foreach ( $known as $class => $name ) {
		if ( class_exists( $class ) ) {
			return array(
				'name'    => $name,
				'certain' => true,
			);
		}
	}

	/*
	 * Something is hooked onto phpmailer_init. That is usually an SMTP plugin —
	 * but it is also how several unrelated things behave, so this is reported
	 * as "possibly", never as "you are fine". Saying all is well when it is not
	 * is the one answer this screen must never give.
	 */
	if ( has_action( 'phpmailer_init' ) ) {
		return array(
			'name'    => __( 'something is configuring PHPMailer', 'maison-vintique' ),
			'certain' => false,
		);
	}

	return array(
		'name'    => '',
		'certain' => false,
	);
}

/**
 * Everything the Emails screen reports.
 *
 * @return array[] Each: label, value, state (ok|warn|bad), note.
 */
function mve_mail_diagnosis() {
	$rows = array();

	// --- who the mail claims to be from ---------------------------------
	$from  = apply_filters( 'woocommerce_email_from_address', get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ), null );
	$site  = wp_parse_url( home_url(), PHP_URL_HOST );
	$site  = preg_replace( '/^www\./', '', (string) $site );
	$fdom  = strtolower( (string) substr( strrchr( (string) $from, '@' ), 1 ) );
	$match = $fdom && $site && ( $fdom === $site || substr( $fdom, -strlen( $site ) - 1 ) === '.' . $site );

	$rows[] = array(
		'label' => __( 'Emails are sent "from"', 'maison-vintique' ),
		'value' => $from ? $from : __( 'not set', 'maison-vintique' ),
		'state' => $match ? 'ok' : 'warn',
		'note'  => $match
			? __( 'Matches the site domain, which is what mail providers expect.', 'maison-vintique' )
			: sprintf(
				/* translators: 1: from domain, 2: site domain */
				__( 'This is on %1$s but the site is %2$s. Gmail and Outlook treat that as forged and bin it. Change it under Email sender options below to an address on your own domain.', 'maison-vintique' ),
				$fdom ? $fdom : __( 'no domain', 'maison-vintique' ),
				$site
			),
	);

	// --- how it is being sent -------------------------------------------
	$smtp = mve_smtp_plugin();
	if ( $smtp['name'] && $smtp['certain'] ) {
		$state = 'ok';
		$note  = __( 'Mail is going out through a real mail service. If a message still does not arrive, that service\'s own log will say why it was refused or filtered.', 'maison-vintique' );
	} elseif ( $smtp['name'] ) {
		$state = 'warn';
		$note  = __( 'Something is changing how mail is sent, but it is not a mail plugin this recognises — so it may or may not be going out through a real service. Worth confirming.', 'maison-vintique' );
	} else {
		$state = 'bad';
		$note  = __( 'Nothing is configured, so WordPress is asking the web server to send mail directly. Most hosts either block this or have it filed as spam. This is the single most common reason emails never arrive — install WP Mail SMTP or FluentSMTP and point it at Brevo, Postmark, SendGrid, Google Workspace or your own mailbox.', 'maison-vintique' );
	}

	$rows[] = array(
		'label' => __( 'Sending method', 'maison-vintique' ),
		'value' => $smtp['name'] ? $smtp['name'] : __( "PHP's own mail() function", 'maison-vintique' ),
		'state' => $state,
		'note'  => $note,
	);

	// --- is mail() even available ---------------------------------------
	$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
	if ( ! $smtp['name'] && ( ! function_exists( 'mail' ) || in_array( 'mail', $disabled, true ) ) ) {
		$rows[] = array(
			'label' => __( 'PHP mail()', 'maison-vintique' ),
			'value' => __( 'switched off by the host', 'maison-vintique' ),
			'state' => 'bad',
			'note'  => __( 'The function has been disabled on this server, so no email can leave the site at all until an SMTP plugin is set up. Nothing in the theme can work around this.', 'maison-vintique' ),
		);
	}

	// --- the log ---------------------------------------------------------
	$last = get_option( 'mve_last_mail_sent', array() );
	$rows[] = array(
		'label' => __( 'Last message the site tried to send', 'maison-vintique' ),
		'value' => ! empty( $last['when'] )
			? sprintf( '%s — %s', $last['when'], $last['subject'] )
			: __( 'none recorded yet', 'maison-vintique' ),
		'state' => ! empty( $last['when'] ) ? 'ok' : 'warn',
		'note'  => ! empty( $last['to'] )
			? sprintf(
				/* translators: %s: email address */
				__( 'Sent to %s. If the site recorded it here but nothing arrived, the message left WordPress and was lost or filtered afterwards — that is a mail-service problem, not a theme one.', 'maison-vintique' ),
				$last['to']
			)
			: __( 'Nothing has been sent since this was added. Use the test below.', 'maison-vintique' ),
	);

	$error = get_option( 'mve_last_mail_error', array() );
	if ( ! empty( $error['when'] ) ) {
		$rows[] = array(
			'label' => __( 'Last refusal from the mail server', 'maison-vintique' ),
			'value' => $error['when'],
			'state' => 'bad',
			'note'  => $error['message'],
		);
	}

	// --- where the shop is notified --------------------------------------
	$rows[] = array(
		'label' => __( 'Shop notifications go to', 'maison-vintique' ),
		'value' => mve_notification_email(),
		'state' => 'ok',
		'note'  => __( 'Change this at the top of WooCommerce → Settings → Emails.', 'maison-vintique' ),
	);

	return apply_filters( 'mve_mail_diagnosis', $rows );
}
