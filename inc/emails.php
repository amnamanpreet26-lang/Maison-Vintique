<?php
/**
 * inc/emails.php
 *
 * Registers the custom emails, adds the Shipped order status, and hooks up
 * everything that fires them.
 *
 * WooCommerce already sends: order confirmation (processing), completed,
 * cancelled, failed, refunded and on-hold. Those keep working — the branded
 * header/footer in woocommerce/emails/ restyles them, so all of them match
 * without a line of extra code.
 *
 * WHERE TO EDIT THE WORDING
 *   WooCommerce → Settings → Emails. Every email in this file appears there
 *   with its own subject, heading and on/off switch.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * REGISTRATION
 * ====================================================================== */

/**
 * Load the classes and hand them to WooCommerce.
 *
 * @param array $emails Registered email classes.
 * @return array
 */
function mve_register_emails( $emails ) {
	require_once get_stylesheet_directory() . '/inc/emails/class-mve-email-base.php';
	require_once get_stylesheet_directory() . '/inc/emails/class-mve-emails.php';

	foreach ( array(
		'MVE_Email_Order_Shipped',
		'MVE_Email_Trade_Application_Received',
		'MVE_Email_Trade_Approved',
		'MVE_Email_Trade_More_Info',
		'MVE_Email_Trade_Welcome',
		'MVE_Email_Invoice',
		'MVE_Email_Payment_Required',
		'MVE_Email_Payment_Reminder',
		'MVE_Email_Password_Changed',
		'MVE_Email_Account_Details',
		'MVE_Email_Product_Available',
		'MVE_Email_Order_Request',
		'MVE_Email_Order_Request_Admin',
		'MVE_Email_Trade_Application_Admin',
		'MVE_Email_Enquiry_Received',
		'MVE_Email_Enquiry_Admin',
		'MVE_Email_Application_Invite',
		'MVE_Email_Enquiry_More_Info',
		'MVE_Email_Enquiry_Declined',
		'MVE_Email_Trade_On_Hold',
	) as $class ) {
		if ( class_exists( $class ) ) {
			$emails[ $class ] = new $class();
		}
	}

	return $emails;
}
add_filter( 'woocommerce_email_classes', 'mve_register_emails' );

/**
 * Send one of our emails.
 *
 * @param string $id      Email id, e.g. 'mve_trade_approved'.
 * @param mixed  $subject Order id/object, or user id/object.
 * @param array  $extra   Anything the email wants.
 * @return bool
 */
function mve_send_email( $id, $subject, $extra = array() ) {
	if ( ! function_exists( 'WC' ) ) {
		return false;
	}
	$mailer = WC()->mailer();
	$emails = $mailer->get_emails();

	foreach ( $emails as $email ) {
		if ( isset( $email->id ) && $email->id === $id && method_exists( $email, 'trigger' ) ) {
			return (bool) $email->trigger( $subject, $extra );
		}
	}
	return false;
}

/* =========================================================================
 * THE GAP AT THE END OF CHECKOUT
 * ---------------------------------------------------------------------
 * WooCommerce sends nothing at all while an order is still "pending payment".
 * Its "New order" and "Order processing" emails hang off the move OUT of that
 * status, which a payment gateway normally does within the same request.
 *
 * A proforma trade flow has no gateway — the customer submits a request, it
 * sits at pending, and both sides are told nothing. This closes exactly that
 * case: an order still pending after checkout gets our own pair, one to the
 * customer and one to the shop. An order a gateway has already moved on is
 * left alone, so no email is ever duplicated.
 * ====================================================================== */

/**
 * Note an order to look at once checkout has finished.
 *
 * DELIBERATELY NOT DECIDED HERE. woocommerce_checkout_order_processed fires
 * BEFORE the payment gateway runs, so every order in the world is still
 * "pending" at this point — deciding now would send a duplicate for every paid
 * order on the site. The decision is made at shutdown, by which time the
 * gateway has either moved the order on or left it exactly where it was.
 *
 * @param int|WC_Order $order Order, or its ID.
 */
function mve_watch_order_request( $order ) {
	$id = $order instanceof WC_Order ? $order->get_id() : (int) $order;
	if ( ! $id ) {
		return;
	}

	if ( ! isset( $GLOBALS['mve_order_requests'] ) ) {
		$GLOBALS['mve_order_requests'] = array();
	}
	$GLOBALS['mve_order_requests'][ $id ] = $id;

	// Priority 5: before WordPress tears anything down, and shutdown runs even
	// when the request ended in wp_send_json()/wp_die(), which the AJAX
	// checkout always does.
	add_action( 'shutdown', 'mve_flush_order_requests', 5 );
}
add_action( 'woocommerce_checkout_order_processed', 'mve_watch_order_request', 20 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'mve_watch_order_request', 20 );

/**
 * Now that checkout is over, email about anything still waiting.
 */
function mve_flush_order_requests() {
	if ( empty( $GLOBALS['mve_order_requests'] ) ) {
		return;
	}

	$ids = $GLOBALS['mve_order_requests'];
	$GLOBALS['mve_order_requests'] = array();

	foreach ( $ids as $id ) {
		// Read it again rather than reusing the object from checkout — that
		// one still holds the status it had before the gateway ran.
		$order = wc_get_order( $id );
		if ( ! $order || ! $order->has_status( 'pending' ) ) {
			continue; // paid, on hold, failed — WooCommerce is emailing already
		}

		// Never twice for the same order, whatever route got here.
		if ( $order->get_meta( '_mve_request_notified' ) ) {
			continue;
		}
		$order->update_meta_data( '_mve_request_notified', current_time( 'mysql' ) );
		$order->save();

		mve_send_email( 'mve_order_request', $order );
		mve_send_email( 'mve_order_request_admin', $order );
	}
}

/* =========================================================================
 * MASTER 1 — THE SHIPPED STATUS
 * ---------------------------------------------------------------------
 * WooCommerce has no "shipped" status of its own; an order goes from
 * processing straight to completed. The brief asks for a shipped email, so
 * the status has to exist for it to hang off.
 * ====================================================================== */

/**
 * Register wc-shipped.
 */
function mve_register_shipped_status() {
	register_post_status(
		'wc-shipped',
		array(
			'label'                     => _x( 'Shipped', 'Order status', 'maison-vintique' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: order count */
			'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'maison-vintique' ),
		)
	);
}
add_action( 'init', 'mve_register_shipped_status' );

/**
 * Put it in the order status dropdown, after Processing.
 *
 * @param array $statuses Order statuses.
 * @return array
 */
function mve_add_shipped_to_status_list( $statuses ) {
	$out = array();
	foreach ( $statuses as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'wc-processing' === $key ) {
			$out['wc-shipped'] = _x( 'Shipped', 'Order status', 'maison-vintique' );
		}
	}
	return $out;
}
add_filter( 'wc_order_statuses', 'mve_add_shipped_to_status_list' );

/** Shipped orders still need paying/completing, so treat them as "paid". */
add_filter( 'woocommerce_order_is_paid_statuses', function ( $statuses ) {
	$statuses[] = 'shipped';
	return $statuses;
} );

/** Fire the shipped email when an order moves into that status. */
add_action( 'woocommerce_order_status_shipped', function ( $order_id ) {
	mve_send_email( 'mve_order_shipped', $order_id );
} );

/**
 * Tracking fields on the order screen, so the shipped email has something to
 * say beyond "it has gone".
 */
function mve_tracking_fields( $order ) {
	$fields = array(
		'_mve_tracking_carrier' => __( 'Carrier', 'maison-vintique' ),
		'_mve_tracking_number'  => __( 'Consignment number', 'maison-vintique' ),
		'_mve_tracking_url'     => __( 'Tracking URL', 'maison-vintique' ),
	);
	echo '<div class="mve-tracking" style="margin-top:14px;"><h4>' . esc_html__( 'Delivery tracking', 'maison-vintique' ) . '</h4>';
	foreach ( $fields as $key => $label ) {
		woocommerce_wp_text_input(
			array(
				'id'            => $key,
				'label'         => $label,
				'value'         => $order->get_meta( $key ),
				'wrapper_class' => 'form-field-wide',
			)
		);
	}
	echo '</div>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'mve_tracking_fields' );

/**
 * Save them.
 *
 * @param int $order_id Order ID.
 */
function mve_save_tracking_fields( $order_id ) {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	foreach ( array( '_mve_tracking_carrier', '_mve_tracking_number', '_mve_tracking_url' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Woo verifies the order screen nonce before this runs.
			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification
			$order->update_meta_data( $key, '_mve_tracking_url' === $key ? esc_url_raw( $raw ) : sanitize_text_field( $raw ) );
		}
	}
	$order->save();
}
add_action( 'woocommerce_process_shop_order_meta', 'mve_save_tracking_fields' );

/* =========================================================================
 * MASTER 4 — PASSWORD RESET AND SECURITY NOTICES
 * ====================================================================== */

/**
 * Password changed notice.
 *
 * profile_update gives us the record BEFORE the change, so comparing the
 * hashes is how we know the password specifically was what changed.
 *
 * @param int     $user_id       User ID.
 * @param WP_User $old_user_data User as it was.
 */
function mve_password_changed_notice( $user_id, $old_user_data ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! $old_user_data || $user->user_pass === $old_user_data->user_pass ) {
		return;
	}
	mve_send_email( 'mve_password_changed', $user );
}
add_action( 'profile_update', 'mve_password_changed_notice', 10, 2 );

/** Same notice after a reset-link reset, which does not go through profile_update. */
add_action( 'after_password_reset', function ( $user ) {
	mve_send_email( 'mve_password_changed', $user );
} );

/**
 * Account details / address changed.
 *
 * @param int $user_id User ID.
 */
function mve_account_details_notice( $user_id ) {
	mve_send_email( 'mve_account_details', $user_id, array( 'changed' => array( __( 'account details', 'maison-vintique' ) ) ) );
}
add_action( 'woocommerce_save_account_details', 'mve_account_details_notice' );

add_action( 'woocommerce_customer_save_address', function ( $user_id, $load_address ) {
	$label = 'billing' === $load_address ? __( 'billing address', 'maison-vintique' ) : __( 'delivery address', 'maison-vintique' );
	mve_send_email( 'mve_account_details', $user_id, array( 'changed' => array( $label ) ) );
}, 10, 2 );

/**
 * Send WordPress's own password-reset email through our branded wrapper.
 *
 * The reset link has to come from WordPress — only it can mint a valid key —
 * so the message is intercepted and re-wrapped rather than replaced.
 *
 * @param string  $message Default message.
 * @param string  $key     Reset key.
 * @param string  $login   User login.
 * @param WP_User $user    User.
 * @return string
 */
function mve_brand_password_reset_email( $message, $key, $login, $user ) {
	if ( ! function_exists( 'WC' ) || ! is_object( $user ) ) {
		return $message;
	}

	$reset_url = function_exists( 'wc_get_page_permalink' )
		? add_query_arg(
			array(
				'key'   => $key,
				'login' => rawurlencode( $user->user_login ),
			),
			wc_get_page_permalink( 'myaccount' ) . 'lost-password/'
		)
		: network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $login ), 'login' );

	$name = $user->first_name ? $user->first_name : $user->display_name;

	ob_start();
	wc_get_template( 'emails/email-header.php', array( 'email_heading' => __( 'Reset your password', 'maison-vintique' ) ), '', get_stylesheet_directory() . '/woocommerce/' );
	printf( '<p>%s</p>', esc_html( sprintf( __( 'Hello %s,', 'maison-vintique' ), $name ) ) );
	printf( '<p>%s</p>', esc_html__( 'Someone asked to reset the password on your trade account. Use the button below to choose a new one.', 'maison-vintique' ) );
	printf(
		'<p class="mv-btn" style="margin:26px 0 8px;"><a class="mv-btn" href="%1$s">%2$s</a></p>',
		esc_url( $reset_url ),
		esc_html__( 'Choose a new password', 'maison-vintique' )
	);
	printf( '<div class="mv-note"><p>%s</p></div>', esc_html__( 'If you did not ask for this, you can ignore this email — your password will not change.', 'maison-vintique' ) );
	wc_get_template( 'emails/email-footer.php', array(), '', get_stylesheet_directory() . '/woocommerce/' );
	$html = ob_get_clean();

	// WordPress sends this as plain text unless we say otherwise.
	add_filter( 'wp_mail_content_type', 'mve_html_mail_content_type' );
	add_action( 'phpmailer_init', 'mve_reset_content_type_once' );

	return $html;
}
add_filter( 'retrieve_password_message', 'mve_brand_password_reset_email', 20, 4 );

/** @return string */
function mve_html_mail_content_type() {
	return 'text/html';
}

/**
 * Put the content type back immediately after that one message is built, so
 * every other plugin's plain-text mail is unaffected.
 */
function mve_reset_content_type_once() {
	remove_filter( 'wp_mail_content_type', 'mve_html_mail_content_type' );
	remove_action( 'phpmailer_init', 'mve_reset_content_type_once' );
}

/* =========================================================================
 * MASTER 5 — BACK IN STOCK
 * ====================================================================== */

/**
 * Notify everyone waiting when a product comes back into stock.
 *
 * @param WC_Product $product Product.
 */
function mve_notify_back_in_stock( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return;
	}

	$waiting = (array) get_post_meta( $product->get_id(), '_mve_stock_waitlist', true );
	if ( ! $waiting ) {
		return;
	}

	foreach ( array_unique( array_filter( array_map( 'intval', $waiting ) ) ) as $user_id ) {
		mve_send_email(
			'mve_product_available',
			$user_id,
			array(
				'product_name' => $product->get_name(),
				'product_url'  => $product->get_permalink(),
			)
		);
	}

	// Told once; clear the list so a later restock does not re-send.
	delete_post_meta( $product->get_id(), '_mve_stock_waitlist' );
}
add_action( 'woocommerce_product_set_stock_status', function ( $product_id, $status ) {
	if ( 'instock' === $status ) {
		mve_notify_back_in_stock( wc_get_product( $product_id ) );
	}
}, 10, 2 );

/**
 * Add the current user to a product's waitlist. Called from the product page.
 */
function mve_join_stock_waitlist() {
	check_ajax_referer( 'mve_waitlist', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	if ( ! $product_id || ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please sign in first.', 'maison-vintique' ) ) );
	}

	$list   = (array) get_post_meta( $product_id, '_mve_stock_waitlist', true );
	$list[] = get_current_user_id();
	update_post_meta( $product_id, '_mve_stock_waitlist', array_values( array_unique( array_filter( array_map( 'intval', $list ) ) ) ) );

	wp_send_json_success( array( 'message' => __( 'We will email you when it is back.', 'maison-vintique' ) ) );
}
add_action( 'wp_ajax_mve_join_waitlist', 'mve_join_stock_waitlist' );
