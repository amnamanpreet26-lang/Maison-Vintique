<?php
/**
 * inc/email-preview.php
 *
 * WooCommerce → Emails.
 *
 * Two jobs on one screen.
 *
 * FIRST, "is mail working at all". Nine times in ten "we are not receiving
 * emails" is the host refusing to send them, and there is nothing in a theme
 * that can tell you that from the front end. The panel at the top reports what
 * the site is actually configured to do — how it sends, who it claims to be
 * from, the last message it handed over and the last one refused — so the
 * answer is on screen instead of guessed at.
 *
 * SECOND, the preview. There is no way to see a transactional email without
 * triggering the event that sends it, which for most of them means placing a
 * real order or approving a real account. This renders any of them on demand
 * with sample data, and can send a real copy anywhere you like for testing.
 *
 * The send needs manage_woocommerce, and it sends the SAMPLE, never a real
 * customer's message — nothing here can reach a customer by accident.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/** Add the screen under WooCommerce. */
function mve_email_preview_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Emails', 'maison-vintique' ),
		__( 'Emails', 'maison-vintique' ),
		'manage_woocommerce',
		'mve-email-preview',
		'mve_email_preview_screen'
	);
}
add_action( 'admin_menu', 'mve_email_preview_menu', 60 );

/**
 * The "is mail working" panel.
 *
 * Everything here is read from the site itself — no guesses — so it can be
 * screenshotted and sent to a host verbatim.
 */
function mve_email_status_panel() {
	$colours = array(
		'ok'   => '#2e7d5b',
		'warn' => '#a98854',
		'bad'  => '#9d3b3b',
	);
	?>
	<h2 style="margin-top:26px"><?php esc_html_e( 'Can this site send email?', 'maison-vintique' ); ?></h2>
	<table class="widefat striped" style="max-width:900px">
		<tbody>
		<?php foreach ( mve_mail_diagnosis() as $row ) : ?>
			<?php $colour = isset( $colours[ $row['state'] ] ) ? $colours[ $row['state'] ] : '#6f675e'; ?>
			<tr>
				<th scope="row" style="width:230px;vertical-align:top;padding:12px">
					<?php echo esc_html( $row['label'] ); ?>
				</th>
				<td style="padding:12px">
					<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?php echo esc_attr( $colour ); ?>;margin-right:8px"></span>
					<strong><?php echo esc_html( $row['value'] ); ?></strong>
					<?php if ( ! empty( $row['note'] ) ) : ?>
						<p class="description" style="margin:6px 0 0"><?php echo esc_html( $row['note'] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Every email we can preview: our own, plus WooCommerce's order emails.
 *
 * @return array id => label
 */
function mve_previewable_emails() {
	if ( ! function_exists( 'WC' ) ) {
		return array();
	}
	$out = array();
	foreach ( WC()->mailer()->get_emails() as $email ) {
		if ( empty( $email->id ) ) {
			continue;
		}
		$out[ $email->id ] = $email->get_title() ? $email->get_title() : $email->id;
	}
	ksort( $out );
	return $out;
}

/**
 * The most recent real order, used as sample data.
 *
 * A real order makes the preview honest — real line items, real totals, real
 * addresses. Without one the order emails still render, just with the table
 * empty.
 *
 * @return WC_Order|null
 */
function mve_preview_sample_order() {
	$orders = wc_get_orders(
		array(
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array_keys( wc_get_order_statuses() ),
		)
	);
	return $orders ? $orders[0] : null;
}

/**
 * Build one email's HTML with sample data.
 *
 * @param string $id Email id.
 * @return string
 */
function mve_render_email_preview( $id ) {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}

	$order = mve_preview_sample_order();
	$user  = wp_get_current_user();

	foreach ( WC()->mailer()->get_emails() as $email ) {
		if ( empty( $email->id ) || $email->id !== $id ) {
			continue;
		}

		// Populate the email the way its own trigger would, WITHOUT sending.
		if ( method_exists( $email, 'trigger' ) && $email instanceof MVE_Email_Base ) {
			$email->object = null;
			$email->user   = $user;
			$email->extra  = array(
				'message'      => __( 'Sample note: please send a VAT number and one trade reference.', 'maison-vintique' ),
				'days'         => 14,
				'changed'      => array( __( 'billing address', 'maison-vintique' ) ),
				'product_name' => __( 'Château Toulouse-Lautrec Bordeaux Supérieur 2019', 'maison-vintique' ),
				'product_url'  => home_url( '/' ),
			);
			if ( $order && $email->show_order_details_for_preview() ) {
				$email->object = $order;
			}
			// Whatever the email fills in for itself before sending.
			$email->prepare_for_preview();
			$email->placeholders['{customer_name}'] = $user->first_name ? $user->first_name : $user->display_name;
			if ( $order ) {
				$email->placeholders['{order_number}'] = $order->get_order_number();
				$email->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			}
		} elseif ( $order ) {
			// A core WooCommerce order email.
			$email->object                         = $order;
			$email->recipient                      = $order->get_billing_email();
			$email->placeholders['{order_number}'] = $order->get_order_number();
			$email->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		}

		return $email->get_content_html();
	}

	return '';
}

/** The screen itself. */
function mve_email_preview_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'maison-vintique' ) );
	}

	$emails   = mve_previewable_emails();
	$selected = isset( $_GET['email'] ) ? sanitize_text_field( wp_unslash( $_GET['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only selection.
	$sent     = null;
	$to       = wp_get_current_user()->user_email;
	$order    = mve_preview_sample_order();

	// Send a real copy, to whichever address was typed in.
	if ( isset( $_POST['mve_send_test'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mve_email_preview' ) ) {
		$selected = sanitize_text_field( wp_unslash( $_POST['mve_send_test'] ) );

		$typed = isset( $_POST['mve_test_to'] ) ? sanitize_email( wp_unslash( $_POST['mve_test_to'] ) ) : '';
		if ( $typed && is_email( $typed ) ) {
			$to = $typed;
		}

		$html = mve_render_email_preview( $selected );

		if ( $html && $to ) {
			$sent = wp_mail(
				$to,
				sprintf(
					/* translators: %s: email title */
					__( '[TEST] %s', 'maison-vintique' ),
					isset( $emails[ $selected ] ) ? $emails[ $selected ] : $selected
				),
				$html,
				array( 'Content-Type: text/html; charset=UTF-8' )
			);
		} else {
			$sent = false;
		}
	}

	$error = get_option( 'mve_last_mail_error', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Emails', 'maison-vintique' ); ?></h1>

		<p class="description" style="max-width:820px">
			<?php esc_html_e( 'Check whether the site can send mail at all, then render any transactional email on demand to see its wording and layout without placing a real order.', 'maison-vintique' ); ?>
		</p>

		<?php if ( true === $sent ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: %s: email address */
					esc_html__( 'Handed to the mail server for %s. That means it left WordPress — if it never arrives, it was lost or filtered after that point, which is a mail-service problem. Check the junk folder first.', 'maison-vintique' ),
					'<strong>' . esc_html( $to ) . '</strong>'
				);
				?>
			</p></div>
		<?php elseif ( false === $sent ) : ?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'The mail server refused it. Nothing was sent.', 'maison-vintique' ); ?></p>
				<?php if ( ! empty( $error['message'] ) ) : ?>
					<p><code><?php echo esc_html( $error['message'] ); ?></code></p>
				<?php endif; ?>
				<p><?php esc_html_e( 'This is hosting, not the theme. See the panel below.', 'maison-vintique' ); ?></p>
			</div>
		<?php endif; ?>

		<?php mve_email_status_panel(); ?>

		<?php if ( ! $order ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'There are no orders yet, so the order emails will preview with an empty items table. Everything else previews normally.', 'maison-vintique' ); ?>
			</p></div>
		<?php endif; ?>

		<h2 style="margin-top:34px"><?php esc_html_e( 'Look at an email', 'maison-vintique' ); ?></h2>

		<form method="get" style="margin:14px 0">
			<input type="hidden" name="page" value="mve-email-preview">
			<select name="email" style="min-width:340px">
				<option value=""><?php esc_html_e( '— choose an email —', 'maison-vintique' ); ?></option>
				<?php foreach ( $emails as $id => $label ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $selected, $id ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="button button-primary"><?php esc_html_e( 'Preview', 'maison-vintique' ); ?></button>
		</form>

		<?php if ( $selected ) : ?>
			<?php $html = mve_render_email_preview( $selected ); ?>

			<form method="post" style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
				<?php wp_nonce_field( 'mve_email_preview' ); ?>
				<input type="hidden" name="mve_send_test" value="<?php echo esc_attr( $selected ); ?>">
				<label for="mve_test_to"><?php esc_html_e( 'Send a real copy to', 'maison-vintique' ); ?></label>
				<input type="email" id="mve_test_to" name="mve_test_to" style="min-width:280px"
					value="<?php echo esc_attr( $to ); ?>" required>
				<button class="button"><?php esc_html_e( 'Send it', 'maison-vintique' ); ?></button>
				<span class="description">
					<?php esc_html_e( 'Try a Gmail address and an Outlook one — they filter very differently.', 'maison-vintique' ); ?>
				</span>
			</form>

			<?php if ( $html ) : ?>
				<iframe
					title="<?php esc_attr_e( 'Email preview', 'maison-vintique' ); ?>"
					style="width:100%;max-width:820px;height:900px;border:1px solid #d6cec1;background:#f2eee6"
					srcdoc="<?php echo esc_attr( $html ); ?>"></iframe>
			<?php else : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'That email could not be rendered.', 'maison-vintique' ); ?></p></div>
			<?php endif; ?>
		<?php endif; ?>

		<h2 style="margin-top:34px"><?php esc_html_e( 'If emails never arrive', 'maison-vintique' ); ?></h2>
		<p style="max-width:820px">
			<?php esc_html_e( 'Work through these in order. The first two account for nearly every case.', 'maison-vintique' ); ?>
		</p>
		<ol style="max-width:820px;line-height:1.8">
			<li>
				<strong><?php esc_html_e( 'Send through a real mail service.', 'maison-vintique' ); ?></strong>
				<?php esc_html_e( 'Install WP Mail SMTP or FluentSMTP and point it at Brevo, Postmark, SendGrid, Google Workspace or your own mailbox. Shared hosting frequently blocks PHP\'s mail() outright, and mail from a hosting IP without authentication is filed as spam by Gmail and Outlook regardless of what it says. Until this is done no email from the site is reliable.', 'maison-vintique' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Send "from" your own domain.', 'maison-vintique' ); ?></strong>
				<?php esc_html_e( 'WooCommerce → Settings → Emails → Email sender options. An address on gmail.com, or on the host\'s domain, is treated as forged. Add SPF and DKIM records for your domain as well — your mail provider gives you both.', 'maison-vintique' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Check the junk folder, then the mail service\'s own log.', 'maison-vintique' ); ?></strong>
				<?php esc_html_e( 'If the panel above says the site handed the message over but nothing arrived, it was accepted and then filtered — the provider\'s log will say why.', 'maison-vintique' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Check each email is switched on.', 'maison-vintique' ); ?></strong>
				<?php esc_html_e( 'WooCommerce → Settings → Emails lists every one with its own on/off switch, subject and heading. The ones that go to you also have their own Recipient box — leave it empty and it uses the single address at the top of that screen.', 'maison-vintique' ); ?>
			</li>
		</ol>
		<p style="max-width:820px">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>">
				<?php esc_html_e( 'Open WooCommerce email settings', 'maison-vintique' ); ?>
			</a>
		</p>
	</div>
	<?php
}
