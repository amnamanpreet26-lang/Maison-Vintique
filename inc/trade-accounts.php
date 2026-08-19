<?php
/**
 * inc/trade-accounts.php
 *
 * STAGE TWO OF THE TRADE WORKFLOW — the due-diligence review, and the emails
 * that hang off it. Stage one, the short enquiry, is inc/trade-enquiries.php.
 *
 *   full application submitted -> status "pending", "Application received" sent
 *   approved                   -> status "approved", "your account is open" sent
 *                                 AND the portal is activated: only now can
 *                                 they sign in
 *   further information        -> "About your application" with your note
 *   on hold                    -> "Your application is on hold"
 *   declined                   -> same email, marked as declined
 *   first sign-in after that   -> "Welcome", once only
 *
 * The status lives in user meta `mve_trade_status`.
 *
 * TWO SEPARATE GATES, and it is worth being clear which is which:
 *   - SIGNING IN needs approval. See mve_block_unapproved_login() below. This
 *     is the client's "portal activation" requirement.
 *   - SEEING PRICING needs only being signed in, in inc/woocommerce.php, as
 *     agreed in an earlier round. Since nobody can sign in without approval,
 *     the two now amount to the same thing in practice.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

const MVE_TRADE_STATUS_KEY = 'mve_trade_status';

/**
 * The statuses an application can be in.
 *
 * @return array
 */
function mve_trade_statuses() {
	return array(
		'pending'  => __( 'Pending review', 'maison-vintique' ),
		'approved' => __( 'Approved — portal active', 'maison-vintique' ),
		'info'     => __( 'Further information required', 'maison-vintique' ),
		'hold'     => __( 'On hold', 'maison-vintique' ),
		'declined' => __( 'Declined', 'maison-vintique' ),
	);
}

/**
 * A user's trade status.
 *
 * @param int $user_id User ID.
 * @return string
 */
function mve_trade_status( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	$status  = get_user_meta( $user_id, MVE_TRADE_STATUS_KEY, true );
	return $status ? $status : 'pending';
}

/**
 * New registration = new application.
 *
 * @param int $user_id User ID.
 */
function mve_trade_application_submitted( $user_id ) {
	if ( get_user_meta( $user_id, MVE_TRADE_STATUS_KEY, true ) ) {
		return; // already has a status; don't reset it
	}
	update_user_meta( $user_id, MVE_TRADE_STATUS_KEY, 'pending' );
	update_user_meta( $user_id, 'mve_trade_applied', current_time( 'mysql' ) );

	/*
	 * This fires from inside wp_insert_user(), which is before the full trade
	 * application has had a chance to store the business name — so the long
	 * form defers it and sends the same email itself a moment later, with
	 * everything filled in. See mve_send_application_ack().
	 */
	if ( ! apply_filters( 'mve_defer_application_email', false, $user_id ) ) {
		mve_send_application_ack( $user_id );
	}

	/**
	 * Fires when a trade application arrives. Hook this to push it into a CRM.
	 */
	do_action( 'mve_trade_application_submitted', $user_id );
}
add_action( 'woocommerce_created_customer', 'mve_trade_application_submitted' );
add_action( 'user_register', 'mve_trade_application_submitted' );

/**
 * Send the applicant their "we have it" email, once and once only.
 *
 * Both registration paths can reach this — the short form on the login page and
 * the full application — so it records that it has been sent rather than
 * relying on which hook got there first.
 *
 * @param int $user_id Applicant.
 * @return bool
 */
function mve_send_application_ack( $user_id ) {
	if ( get_user_meta( $user_id, 'mve_app_ack_sent', true ) ) {
		return false;
	}

	$sent = mve_send_email( 'mve_trade_application_received', $user_id );

	// Recorded even when the mailer refused it, so a broken mail server does
	// not turn one application into a stream of retries. The Emails screen
	// under WooCommerce reports the refusal.
	update_user_meta( $user_id, 'mve_app_ack_sent', current_time( 'mysql' ) );

	return (bool) $sent;
}

/**
 * Change a user's trade status and send the matching email.
 *
 * @param int    $user_id User ID.
 * @param string $status  One of mve_trade_statuses().
 * @param string $message Optional note, included in the more-info/declined email.
 * @return bool
 */
function mve_set_trade_status( $user_id, $status, $message = '' ) {
	if ( ! array_key_exists( $status, mve_trade_statuses() ) ) {
		return false;
	}

	$previous = mve_trade_status( $user_id );
	update_user_meta( $user_id, MVE_TRADE_STATUS_KEY, $status );
	update_user_meta( $user_id, 'mve_trade_status_changed', current_time( 'mysql' ) );

	if ( $status === $previous ) {
		return true; // no email for a no-op
	}

	if ( 'approved' === $status ) {
		mve_send_email( 'mve_trade_approved', $user_id );

		// The enquiry that started all this shows the outcome too, so the Trade
		// Enquiries list reads as a complete history rather than going quiet
		// the moment the application was submitted.
		$enquiry = (int) get_user_meta( $user_id, 'mve_enquiry_id', true );
		if ( $enquiry ) {
			update_post_meta( $enquiry, '_mve_status', 'account' );
		}
	} elseif ( 'hold' === $status ) {
		mve_send_email( 'mve_trade_on_hold', $user_id, array( 'message' => $message ) );
	} elseif ( 'info' === $status ) {
		mve_send_email( 'mve_trade_more_info', $user_id, array( 'message' => $message ) );
	} elseif ( 'declined' === $status ) {
		mve_send_email(
			'mve_trade_more_info',
			$user_id,
			array(
				'message'  => $message,
				'declined' => true,
			)
		);
	}

	do_action( 'mve_trade_status_changed', $user_id, $status, $previous );
	return true;
}

/**
 * Welcome email on first sign-in after approval, once and once only.
 *
 * @param string  $login Username.
 * @param WP_User $user  User.
 */
function mve_trade_welcome_on_first_login( $login, $user = null ) {
	if ( ! $user instanceof WP_User ) {
		$user = get_user_by( 'login', $login );
	}
	if ( ! $user instanceof WP_User ) {
		return;
	}
	if ( 'approved' !== mve_trade_status( $user->ID ) ) {
		return;
	}
	if ( get_user_meta( $user->ID, 'mve_trade_welcomed', true ) ) {
		return;
	}

	update_user_meta( $user->ID, 'mve_trade_welcomed', current_time( 'mysql' ) );
	mve_send_email( 'mve_trade_welcome', $user );
}
add_action( 'wp_login', 'mve_trade_welcome_on_first_login', 10, 2 );

/* =========================================================================
 * PORTAL ACTIVATION
 * ---------------------------------------------------------------------
 * "Only once the full application and due-diligence review have been approved
 * should the trade portal account be activated."
 *
 * The account exists from the moment the application is submitted — it has to,
 * because it is where the answers are stored — but it cannot be signed into
 * until somebody approves it.
 *
 * SCOPED ON PURPOSE. This only ever applies to accounts that have a trade
 * application on file. Anyone created before this workflow existed, and every
 * administrator and shop manager, signs in exactly as they always did. A gate
 * that locks the shop out of its own site is worse than no gate.
 * ====================================================================== */

/**
 * Block sign-in for an application that has not been approved.
 *
 * @param WP_User|WP_Error $user     Authenticated user, or an error.
 * @param string           $password Unused.
 * @return WP_User|WP_Error
 */
function mve_block_unapproved_login( $user, $password = '' ) {
	if ( ! $user instanceof WP_User ) {
		return $user;   // wrong password etc. — not ours to answer
	}

	// Never lock out anybody who can run the shop.
	if ( user_can( $user, 'edit_posts' ) || user_can( $user, 'manage_woocommerce' ) ) {
		return $user;
	}

	// Only accounts created BY the full application. A legacy customer with no
	// application on file is not part of this workflow.
	if ( ! get_user_meta( $user->ID, 'mve_app_submitted', true ) ) {
		return $user;
	}

	$status = mve_trade_status( $user->ID );
	if ( 'approved' === $status ) {
		return $user;
	}

	if ( ! apply_filters( 'mve_require_approval_to_sign_in', true, $user ) ) {
		return $user;
	}

	$messages = array(
		'pending'  => __( 'Thank you — your trade application is with us. Your portal opens as soon as our account review is complete, and we will email you the moment it does.', 'maison-vintique' ),
		'info'     => __( 'We need a little more before we can open your account. Please check your email — we have written to you about it.', 'maison-vintique' ),
		'hold'     => __( 'Your application is on hold while we complete our checks. We will be in touch as soon as we can take it further.', 'maison-vintique' ),
		'declined' => __( 'We are not able to open a trade account on this address at the moment. Please get in touch if you think that is a mistake.', 'maison-vintique' ),
	);

	return new WP_Error(
		'mve_not_activated',
		isset( $messages[ $status ] ) ? $messages[ $status ] : $messages['pending']
	);
}
add_filter( 'wp_authenticate_user', 'mve_block_unapproved_login', 20, 2 );

/* =========================================================================
 * ADMIN — REVIEW APPLICATIONS ON THE USER SCREEN
 * ====================================================================== */

/**
 * Trade status column in Users.
 *
 * @param array $columns Columns.
 * @return array
 */
function mve_users_trade_column( $columns ) {
	$columns['mve_trade'] = __( 'Trade status', 'maison-vintique' );
	return $columns;
}
add_filter( 'manage_users_columns', 'mve_users_trade_column' );

/**
 * Its content.
 *
 * @param string $output      Existing output.
 * @param string $column_name Column.
 * @param int    $user_id     User ID.
 * @return string
 */
function mve_users_trade_column_value( $output, $column_name, $user_id ) {
	if ( 'mve_trade' !== $column_name ) {
		return $output;
	}
	$statuses = mve_trade_statuses();
	$status   = mve_trade_status( $user_id );
	$colours  = array(
		'approved' => '#2e7d5b',
		'pending'  => '#a98854',
		'info'     => '#35618e',
		'hold'     => '#8a6d3b',
		'declined' => '#9d3b3b',
	);
	$colour = isset( $colours[ $status ] ) ? $colours[ $status ] : '#6f675e';
	$label  = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;

	return '<span style="display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;color:#fff;background:' . esc_attr( $colour ) . '">' . esc_html( $label ) . '</span>';
}
add_filter( 'manage_users_custom_column', 'mve_users_trade_column_value', 10, 3 );

/**
 * The review panel on a user's profile.
 *
 * @param WP_User $user User.
 */
function mve_user_trade_panel( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$status   = mve_trade_status( $user->ID );
	$statuses = mve_trade_statuses();
	?>
	<h2><?php esc_html_e( 'Trade account', 'maison-vintique' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="mve_trade_status"><?php esc_html_e( 'Status', 'maison-vintique' ); ?></label></th>
			<td>
				<select name="mve_trade_status" id="mve_trade_status">
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Changing this emails the customer. Approving sends the "your account is open" email; the other two send the "about your application" email with your note below.', 'maison-vintique' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="mve_trade_note"><?php esc_html_e( 'Note to the customer', 'maison-vintique' ); ?></label></th>
			<td>
				<textarea name="mve_trade_note" id="mve_trade_note" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Please send a VAT number and one trade reference.', 'maison-vintique' ); ?>"></textarea>
				<p class="description"><?php esc_html_e( 'Included in the email when you choose "More information requested" or "Declined". Not stored.', 'maison-vintique' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Business', 'maison-vintique' ); ?></th>
			<td>
				<?php
				$business = get_user_meta( $user->ID, 'mve_business_name', true );
				$phone    = get_user_meta( $user->ID, 'mve_phone', true );
				$applied  = get_user_meta( $user->ID, 'mve_trade_applied', true );
				echo esc_html( $business ? $business : __( '—', 'maison-vintique' ) );
				if ( $phone ) {
					echo ' &nbsp;·&nbsp; ' . esc_html( $phone );
				}
				if ( $applied ) {
					echo '<br><span class="description">' . esc_html( sprintf( __( 'Applied %s', 'maison-vintique' ), $applied ) ) . '</span>';
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'mve_user_trade_panel' );
add_action( 'edit_user_profile', 'mve_user_trade_panel' );

/**
 * Save the panel.
 *
 * @param int $user_id User ID.
 */
function mve_save_user_trade_panel( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['mve_trade_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- the profile screen nonce is checked by WordPress before this fires.
		return;
	}
	$status  = sanitize_text_field( wp_unslash( $_POST['mve_trade_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$message = isset( $_POST['mve_trade_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mve_trade_note'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	mve_set_trade_status( $user_id, $status, $message );
}
add_action( 'personal_options_update', 'mve_save_user_trade_panel' );
add_action( 'edit_user_profile_update', 'mve_save_user_trade_panel' );

/*
 * TO REQUIRE APPROVAL BEFORE PRICING IS VISIBLE
 * ---------------------------------------------
 * Not switched on, because the agreed rule is "logged in sees pricing". Drop
 * this into functions.php to make approval the requirement instead:
 *
 *   add_filter( 'mve_is_gated', function ( $gated ) {
 *       return is_user_logged_in() ? ( 'approved' !== mve_trade_status() ) : true;
 *   } );
 */
