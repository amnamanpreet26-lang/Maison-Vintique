<?php
/**
 * inc/newsletter-admin.php
 *
 * WooCommerce → Newsletter.
 *
 * Every footer signup has always been stored — in the `mve_newsletter_subscribers`
 * option — but there was nowhere to look at it, so the only trace anybody saw
 * was an email at the moment somebody subscribed. Miss that email and the
 * signup may as well not have happened.
 *
 * This is the list: who, when, and a CSV to hand to Mailchimp or Brevo.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everybody who has subscribed, newest first.
 *
 * @return array email => array( date, ip )
 */
function mve_newsletter_subscribers() {
	$list = get_option( 'mve_newsletter_subscribers', array() );
	if ( ! is_array( $list ) ) {
		return array();
	}

	uasort(
		$list,
		function ( $a, $b ) {
			$x = isset( $a['date'] ) ? strtotime( $a['date'] ) : 0;
			$y = isset( $b['date'] ) ? strtotime( $b['date'] ) : 0;
			return $y <=> $x;
		}
	);

	return $list;
}

/** The screen. */
function mve_newsletter_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Newsletter', 'maison-vintique' ),
		__( 'Newsletter', 'maison-vintique' ),
		'manage_woocommerce',
		'mve-newsletter',
		'mve_newsletter_screen'
	);
}
add_action( 'admin_menu', 'mve_newsletter_menu', 61 );

/**
 * Download the list.
 *
 * Handled on admin_init rather than inside the screen, because a CSV has to be
 * sent before WordPress has printed a single byte of admin HTML.
 */
function mve_newsletter_export() {
	if ( ! isset( $_GET['page'], $_GET['mve_export'] ) || 'mve-newsletter' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- nonce checked below.
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	check_admin_referer( 'mve_newsletter_export' );

	$rows = mve_newsletter_subscribers();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( get_bloginfo( 'name' ) . '-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' ) );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'subscribed' ) );
	foreach ( $rows as $email => $meta ) {
		fputcsv( $out, array( $email, isset( $meta['date'] ) ? $meta['date'] : '' ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	exit;
}
add_action( 'admin_init', 'mve_newsletter_export' );

/**
 * Remove one.
 */
function mve_newsletter_remove() {
	if ( ! isset( $_GET['page'], $_GET['mve_remove'] ) || 'mve-newsletter' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- nonce checked below.
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	check_admin_referer( 'mve_newsletter_remove' );

	$email = sanitize_email( wp_unslash( $_GET['mve_remove'] ) );
	$list  = get_option( 'mve_newsletter_subscribers', array() );

	if ( is_array( $list ) && isset( $list[ $email ] ) ) {
		unset( $list[ $email ] );
		update_option( 'mve_newsletter_subscribers', $list, false );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'mve-newsletter', 'removed' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'mve_newsletter_remove' );

/** Render it. */
function mve_newsletter_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'maison-vintique' ) );
	}

	$rows  = mve_newsletter_subscribers();
	$to    = function_exists( 'mve_newsletter_email' ) ? mve_newsletter_email() : get_option( 'admin_email' );
	$total = count( $rows );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsletter subscribers', 'maison-vintique' ); ?></h1>

		<?php if ( isset( $_GET['removed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- display only. ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Removed.', 'maison-vintique' ); ?></p></div>
		<?php endif; ?>

		<p class="description" style="max-width:760px">
			<?php
			printf(
				/* translators: %s: email address */
				esc_html__( 'Everybody who has signed up through the form in the footer. Each new signup is emailed to %s — change that at the top of WooCommerce → Settings → Emails.', 'maison-vintique' ),
				'<strong>' . esc_html( $to ) . '</strong>'
			);
			?>
		</p>

		<p style="margin:16px 0">
			<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
			<?php echo esc_html( _n( 'subscriber', 'subscribers', $total, 'maison-vintique' ) ); ?>

			<?php if ( $total ) : ?>
				&nbsp;
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mve-newsletter&mve_export=1' ), 'mve_newsletter_export' ) ); ?>">
					<?php esc_html_e( 'Download CSV', 'maison-vintique' ); ?>
				</a>
			<?php endif; ?>

			&nbsp;
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>">
				<?php esc_html_e( 'Change where signups are emailed', 'maison-vintique' ); ?>
			</a>
		</p>

		<?php if ( ! $total ) : ?>
			<div class="notice notice-info inline"><p>
				<?php esc_html_e( 'Nobody has subscribed yet. The form is the one in the site footer.', 'maison-vintique' ); ?>
			</p></div>
		<?php else : ?>
			<table class="widefat striped" style="max-width:820px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'maison-vintique' ); ?></th>
						<th style="width:200px"><?php esc_html_e( 'Subscribed', 'maison-vintique' ); ?></th>
						<th style="width:90px"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $email => $meta ) : ?>
						<tr>
							<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
							<td><?php echo esc_html( isset( $meta['date'] ) ? $meta['date'] : '—' ); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mve-newsletter&mve_remove=' . rawurlencode( $email ) ), 'mve_newsletter_remove' ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Remove this subscriber?', 'maison-vintique' ) ); ?>')"
									style="color:#9d3b3b"><?php esc_html_e( 'Remove', 'maison-vintique' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2 style="margin-top:34px"><?php esc_html_e( 'Sending to them', 'maison-vintique' ); ?></h2>
		<p style="max-width:760px">
			<?php esc_html_e( 'This is a list, not a mailing tool — nothing here sends a campaign. Download the CSV and import it into Mailchimp, Brevo, Klaviyo or whatever you send from. To push signups into one of those automatically as they arrive, hook mve_newsletter_subscribed in a small plugin and call your provider\'s API with the address.', 'maison-vintique' ); ?>
		</p>
	</div>
	<?php
}
