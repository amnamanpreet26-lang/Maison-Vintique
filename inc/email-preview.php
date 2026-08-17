<?php
/**
 * inc/email-preview.php
 *
 * WooCommerce → Email Preview.
 *
 * There is no way to see a transactional email without triggering the event
 * that sends it, which for most of them means placing a real order or
 * approving a real account. This screen renders any of them on demand with
 * sample data, and sends a real one to your own address.
 *
 * Nothing here can email a customer: the send button always goes to the
 * logged-in administrator, never to the address on the sample order.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/** Add the screen under WooCommerce. */
function mve_email_preview_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Email Preview', 'maison-vintique' ),
		__( 'Email Preview', 'maison-vintique' ),
		'manage_woocommerce',
		'mve-email-preview',
		'mve_email_preview_screen'
	);
}
add_action( 'admin_menu', 'mve_email_preview_menu', 60 );

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
	$sent     = false;
	$order    = mve_preview_sample_order();

	// Send a test to the administrator.
	if ( isset( $_POST['mve_send_test'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mve_email_preview' ) ) {
		$selected = sanitize_text_field( wp_unslash( $_POST['mve_send_test'] ) );
		$to       = wp_get_current_user()->user_email;
		$html     = mve_render_email_preview( $selected );

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
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Email Preview', 'maison-vintique' ); ?></h1>

		<p class="description" style="max-width:760px">
			<?php esc_html_e( 'Renders any transactional email on demand so you can check the wording and the layout without placing a real order. Test sends always go to your own address — a customer can never receive one from this screen.', 'maison-vintique' ); ?>
		</p>

		<?php if ( $sent ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: %s: email address */
					esc_html__( 'Test sent to %s. If it does not arrive, the site cannot send mail at all — see the note below.', 'maison-vintique' ),
					'<strong>' . esc_html( wp_get_current_user()->user_email ) . '</strong>'
				);
				?>
			</p></div>
		<?php elseif ( isset( $_POST['mve_send_test'] ) ) : ?>
			<div class="notice notice-error"><p>
				<?php esc_html_e( 'WordPress could not hand that message to the mail server. That is a hosting/SMTP problem, not a template problem — see the note below.', 'maison-vintique' ); ?>
			</p></div>
		<?php endif; ?>

		<?php if ( ! $order ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'There are no orders yet, so the order emails will preview with an empty items table. Everything else previews normally.', 'maison-vintique' ); ?>
			</p></div>
		<?php endif; ?>

		<form method="get" style="margin:18px 0">
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

			<form method="post" style="margin-bottom:14px">
				<?php wp_nonce_field( 'mve_email_preview' ); ?>
				<input type="hidden" name="mve_send_test" value="<?php echo esc_attr( $selected ); ?>">
				<button class="button">
					<?php
					printf(
						/* translators: %s: admin email */
						esc_html__( 'Send a test to %s', 'maison-vintique' ),
						esc_html( wp_get_current_user()->user_email )
					);
					?>
				</button>
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

		<h2 style="margin-top:34px"><?php esc_html_e( 'If test emails never arrive', 'maison-vintique' ); ?></h2>
		<p style="max-width:760px">
			<?php esc_html_e( 'That is almost always the host, not WordPress. Shared hosting frequently blocks the PHP mail() function outright, and mail sent from a hosting IP without authentication is usually rejected or filed as spam by Gmail and Outlook regardless.', 'maison-vintique' ); ?>
		</p>
		<p style="max-width:760px">
			<?php esc_html_e( 'The fix is to send through a real mail service: install an SMTP plugin (WP Mail SMTP, FluentSMTP) and point it at your provider — Brevo, Postmark, SendGrid or your own mailbox. Until that is done, no email from this site is reliable, whatever the template looks like here.', 'maison-vintique' ); ?>
		</p>
	</div>
	<?php
}
