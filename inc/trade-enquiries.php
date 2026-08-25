<?php
/**
 * inc/trade-enquiries.php
 *
 * STAGE ONE OF THE TRADE WORKFLOW — the short enquiry, and the gate.
 *
 * The client's rule is that the full nine-section application must not be
 * public. A visitor asks; Maison Vintique decides whether to let them apply;
 * only then does a private, expiring link to the full form go out.
 *
 *   Apply for a Trade Account   (button, anywhere on the site)
 *        v
 *   Short trade enquiry         (the contact form, with the client's fields)
 *        v
 *   Initial review              (approve to apply / more info / decline)
 *        v
 *   Private invitation          (unique link, expires, single use)
 *        v
 *   Full trade application      (pre-filled from the enquiry)
 *        v
 *   Due-diligence review        (approved / more info / on hold / declined)
 *        v
 *   Portal activated            (welcome email; only now can they sign in)
 *
 * Each enquiry is a post of type mv_enquiry. Not public, not queryable, not in
 * any sitemap — it exists so the office has a list to work through, with the
 * whole history against it.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

const MVE_ENQUIRY_TYPE = 'mv_enquiry';

/* =========================================================================
 * THE RECORD
 * ====================================================================== */

/**
 * Register the enquiry post type.
 *
 * public=false and publicly_queryable=false: an enquiry holds somebody's
 * business details and must never have a front-end URL of its own.
 */
function mve_register_enquiry_type() {
	register_post_type(
		MVE_ENQUIRY_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'Trade Enquiries', 'maison-vintique' ),
				'singular_name'      => __( 'Trade Enquiry', 'maison-vintique' ),
				'menu_name'          => __( 'Trade Enquiries', 'maison-vintique' ),
				'all_items'          => __( 'All Enquiries', 'maison-vintique' ),
				'edit_item'          => __( 'Review Enquiry', 'maison-vintique' ),
				'view_item'          => __( 'View Enquiry', 'maison-vintique' ),
				'search_items'       => __( 'Search enquiries', 'maison-vintique' ),
				'not_found'          => __( 'No enquiries yet.', 'maison-vintique' ),
				'not_found_in_trash' => __( 'Nothing in the bin.', 'maison-vintique' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-forms',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => 'do_not_allow', // they arrive from the form only
			),
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}
add_action( 'init', 'mve_register_enquiry_type' );

/**
 * Where an enquiry can be in the process.
 *
 * ONE LIST, THE WHOLE JOURNEY. The first four are the initial review the client
 * described. Everything after 'applied' mirrors the due-diligence outcome on
 * the applicant's account, so Trade Enquiries reads as a complete history
 * instead of going quiet the moment the invitation is sent — and there is only
 * one screen to look at.
 *
 * Kept in step by mve_sync_enquiry_from_account(), which runs whenever an
 * account's trade status changes, whether that happened on this screen or on
 * the user's profile.
 *
 * @return array
 */
function mve_enquiry_statuses() {
	return array(
		// Stage one — the short enquiry.
		'new'         => __( 'New — awaiting review', 'maison-vintique' ),
		'invited'     => __( 'Approved to apply — invitation sent', 'maison-vintique' ),
		'more_info'   => __( 'More information requested', 'maison-vintique' ),
		'declined'    => __( 'Declined — not suitable at present', 'maison-vintique' ),

		// Stage two — the full application and its due-diligence review.
		'applied'     => __( 'Full application submitted — awaiting review', 'maison-vintique' ),
		'app_info'    => __( 'Application — further information requested', 'maison-vintique' ),
		'app_hold'    => __( 'Application — on hold', 'maison-vintique' ),
		'app_declined' => __( 'Application declined', 'maison-vintique' ),
		'account'     => __( 'Trade account approved — portal active', 'maison-vintique' ),
	);
}

/**
 * Which account status each enquiry status mirrors, and the other way round.
 *
 * @return array enquiry status => trade account status
 */
function mve_enquiry_status_map() {
	return array(
		'applied'      => 'pending',
		'app_info'     => 'info',
		'app_hold'     => 'hold',
		'app_declined' => 'declined',
		'account'      => 'approved',
	);
}

/**
 * The colour each status shows in.
 *
 * @return array
 */
function mve_enquiry_status_colours() {
	return array(
		'new'          => '#a98854',
		'invited'      => '#35618e',
		'more_info'    => '#8a6d3b',
		'declined'     => '#9d3b3b',
		'applied'      => '#5b4b8a',
		'app_info'     => '#8a6d3b',
		'app_hold'     => '#7a6a55',
		'app_declined' => '#9d3b3b',
		'account'      => '#2e7d5b',
	);
}

/**
 * Which stage of the process an enquiry is at.
 *
 * @param int $id Enquiry ID.
 * @return string 'enquiry' or 'application'
 */
function mve_enquiry_stage( $id ) {
	return array_key_exists( mve_enquiry_status( $id ), mve_enquiry_status_map() ) ? 'application' : 'enquiry';
}

/**
 * The account that came out of this enquiry, if the application was submitted.
 *
 * @param int $id Enquiry ID.
 * @return int User ID, or 0.
 */
function mve_enquiry_user( $id ) {
	return (int) get_post_meta( $id, '_mve_user_id', true );
}

/**
 * Keep the enquiry in step with the account it produced.
 *
 * Hung off the account's own status change so it does not matter where the
 * decision was made — this screen, the user's profile, or code — the Trade
 * Enquiries list tells the same story either way.
 *
 * @param int    $user_id Applicant.
 * @param string $status  New trade account status.
 */
function mve_sync_enquiry_from_account( $user_id, $status ) {
	$enquiry = (int) get_user_meta( $user_id, 'mve_enquiry_id', true );
	if ( ! $enquiry ) {
		return;
	}

	$mirror = array_flip( mve_enquiry_status_map() );
	if ( ! isset( $mirror[ $status ] ) ) {
		return;
	}

	update_post_meta( $enquiry, '_mve_status', $mirror[ $status ] );
	update_post_meta( $enquiry, '_mve_reviewed', current_time( 'mysql' ) );
}
add_action( 'mve_trade_status_changed', 'mve_sync_enquiry_from_account', 10, 2 );

/**
 * An enquiry's status.
 *
 * @param int $id Enquiry ID.
 * @return string
 */
function mve_enquiry_status( $id ) {
	$status = get_post_meta( $id, '_mve_status', true );
	return $status ? $status : 'new';
}

/**
 * The fields the short enquiry asks for.
 *
 * Exactly the client's list. The contact form renders from this, the handler
 * validates from it, and the admin screen displays from it — so there is one
 * place to add a question.
 *
 * @return array
 */
function mve_enquiry_fields() {
	return apply_filters(
		'mve_enquiry_fields',
		array(
			'business' => array(
				'label'    => __( 'Business name', 'maison-vintique' ),
				'type'     => 'text',
				'required' => true,
			),
			'contact'  => array(
				'label'    => __( 'Contact name', 'maison-vintique' ),
				'type'     => 'text',
				'required' => true,
				'width'    => 'half',
			),
			'email'    => array(
				'label'    => __( 'Business email', 'maison-vintique' ),
				'type'     => 'email',
				'required' => true,
				'width'    => 'half',
			),
			'phone'    => array(
				'label'    => __( 'Telephone', 'maison-vintique' ),
				'type'     => 'tel',
				'required' => true,
				'width'    => 'half',
			),
			'website'  => array(
				'label'       => __( 'Website or social media', 'maison-vintique' ),
				'type'        => 'text',
				'width'       => 'half',
				'placeholder' => 'yourbusiness.co.uk',
			),
			'type'     => array(
				'label'    => __( 'Business type', 'maison-vintique' ),
				'type'     => 'select',
				'required' => true,
				'width'    => 'half',
				'options'  => array(
					''            => __( 'Please choose', 'maison-vintique' ),
					'restaurant'  => __( 'Restaurant', 'maison-vintique' ),
					'hotel'       => __( 'Hotel', 'maison-vintique' ),
					'wine_bar'    => __( 'Wine bar', 'maison-vintique' ),
					'pub'         => __( 'Pub', 'maison-vintique' ),
					'independent' => __( 'Independent retailer', 'maison-vintique' ),
					'online'      => __( 'Online retailer', 'maison-vintique' ),
					'events'      => __( 'Events / hospitality', 'maison-vintique' ),
					'caterer'     => __( 'Caterer', 'maison-vintique' ),
					'wholesaler'  => __( 'Wholesaler / distributor', 'maison-vintique' ),
					'other'       => __( 'Other', 'maison-vintique' ),
				),
			),
			'town'     => array(
				'label'    => __( 'Town or city', 'maison-vintique' ),
				'type'     => 'text',
				'required' => true,
				'width'    => 'half',
			),
			'about'    => array(
				'label'       => __( 'Tell us about your business', 'maison-vintique' ),
				'type'        => 'textarea',
				'required'    => true,
				'placeholder' => __( 'A couple of lines — what you do, how long you have been trading, the kind of list you keep.', 'maison-vintique' ),
			),
			'interest' => array(
				'label'       => __( 'What interests you about Maison Vintique?', 'maison-vintique' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Regions, growers, or a particular part of the portfolio.', 'maison-vintique' ),
			),
		)
	);
}

/**
 * Store an enquiry.
 *
 * @param array $values Cleaned values, keyed as mve_enquiry_fields().
 * @return int Post ID, or 0.
 */
function mve_create_enquiry( $values ) {
	$title = ! empty( $values['business'] ) ? $values['business'] : ( ! empty( $values['contact'] ) ? $values['contact'] : __( 'Enquiry', 'maison-vintique' ) );

	$id = wp_insert_post(
		array(
			'post_type'   => MVE_ENQUIRY_TYPE,
			'post_title'  => wp_strip_all_tags( $title ),
			'post_status' => 'publish', // "publish" here only means "not a draft"
		),
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	foreach ( mve_enquiry_fields() as $key => $field ) {
		update_post_meta( $id, '_mve_' . $key, isset( $values[ $key ] ) ? $values[ $key ] : '' );
	}
	update_post_meta( $id, '_mve_status', 'new' );

	return (int) $id;
}

/* =========================================================================
 * THE INVITATION
 * ---------------------------------------------------------------------
 * A token, not a page anybody can find. It is stored on the enquiry, tied to
 * the email address it was sent to, expires, and is spent the moment the
 * application it opened is submitted.
 * ====================================================================== */

/**
 * How long an invitation stays valid.
 *
 * @return int Days.
 */
function mve_invite_days() {
	$days = (int) get_option( 'mve_invite_days', 21 );
	return max( 1, (int) apply_filters( 'mve_invite_days', $days ) );
}

/**
 * Mint a fresh invitation for an enquiry.
 *
 * Re-issuing replaces the old token, so a link that has been forwarded on
 * stops working the moment a new one is sent.
 *
 * @param int $id Enquiry ID.
 * @return string The token.
 */
function mve_issue_invite( $id ) {
	$token = wp_generate_password( 32, false, false );

	update_post_meta( $id, '_mve_token', $token );
	update_post_meta( $id, '_mve_token_issued', current_time( 'mysql' ) );
	update_post_meta( $id, '_mve_token_expires', gmdate( 'Y-m-d H:i:s', time() + ( mve_invite_days() * DAY_IN_SECONDS ) ) );
	delete_post_meta( $id, '_mve_token_used' );

	return $token;
}

/**
 * The private link for an enquiry.
 *
 * @param int $id Enquiry ID.
 * @return string URL, or '' when there is no live token.
 */
function mve_invite_url( $id ) {
	$token = get_post_meta( $id, '_mve_token', true );
	if ( ! $token || ! function_exists( 'mve_trade_application_url' ) ) {
		return '';
	}

	return add_query_arg(
		array(
			'invite' => rawurlencode( $token ),
		),
		mve_trade_application_url()
	);
}

/**
 * Which enquiry, if any, a token opens.
 *
 * @param string $token Raw token from the URL.
 * @return int Enquiry ID, or 0.
 */
function mve_enquiry_for_token( $token ) {
	$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
	if ( strlen( $token ) < 16 ) {
		return 0;
	}

	$found = get_posts(
		array(
			'post_type'        => MVE_ENQUIRY_TYPE,
			'post_status'      => 'any',
			'numberposts'      => 1,
			'meta_key'         => '_mve_token',   // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => $token,         // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	return $found ? (int) $found[0] : 0;
}

/**
 * Why a token cannot be used, if it cannot.
 *
 * @param string $token Raw token.
 * @return array {
 *     @type int    $enquiry Enquiry ID, 0 when there is no match.
 *     @type string $problem '', 'unknown', 'expired' or 'used'.
 * }
 */
function mve_check_invite( $token ) {
	$id = mve_enquiry_for_token( $token );

	if ( ! $id ) {
		return array(
			'enquiry' => 0,
			'problem' => 'unknown',
		);
	}

	if ( get_post_meta( $id, '_mve_token_used', true ) ) {
		return array(
			'enquiry' => $id,
			'problem' => 'used',
		);
	}

	$expires = get_post_meta( $id, '_mve_token_expires', true );
	if ( $expires && strtotime( $expires ) < time() ) {
		return array(
			'enquiry' => $id,
			'problem' => 'expired',
		);
	}

	return array(
		'enquiry' => $id,
		'problem' => '',
	);
}

/**
 * Spend an invitation — called once the application it opened is submitted.
 *
 * @param int $id      Enquiry ID.
 * @param int $user_id The customer that was created.
 */
function mve_spend_invite( $id, $user_id = 0 ) {
	update_post_meta( $id, '_mve_token_used', current_time( 'mysql' ) );
	update_post_meta( $id, '_mve_status', 'applied' );
	if ( $user_id ) {
		update_post_meta( $id, '_mve_user_id', (int) $user_id );
		update_user_meta( $user_id, 'mve_enquiry_id', (int) $id );
	}
}

/**
 * Move an enquiry on, and send whichever email goes with it.
 *
 * Stage-two statuses are the due-diligence outcome on the applicant's account.
 * Those are handed to mve_set_trade_status(), which owns the account side and
 * sends the account emails — and which syncs this enquiry back through
 * mve_sync_enquiry_from_account(). One decision, made in one place, whichever
 * screen it was made on.
 *
 * @param int    $id     Enquiry ID.
 * @param string $status One of mve_enquiry_statuses().
 * @param string $note   Optional note to the applicant.
 * @return bool
 */
function mve_set_enquiry_status( $id, $status, $note = '' ) {
	if ( ! array_key_exists( $status, mve_enquiry_statuses() ) ) {
		return false;
	}

	$previous = mve_enquiry_status( $id );

	// ---- stage two: the account's review -------------------------------
	$map = mve_enquiry_status_map();
	if ( isset( $map[ $status ] ) ) {
		if ( $note ) {
			update_post_meta( $id, '_mve_note', $note );
		}
		if ( $status === $previous ) {
			return true;
		}

		$user_id = mve_enquiry_user( $id );
		if ( $user_id && function_exists( 'mve_set_trade_status' ) ) {
			// This writes the enquiry's own status back on the action.
			return mve_set_trade_status( $user_id, $map[ $status ], $note );
		}

		// No account to decide about — record it and stop, rather than
		// pretending an email went out.
		update_post_meta( $id, '_mve_status', $status );
		update_post_meta( $id, '_mve_reviewed', current_time( 'mysql' ) );
		return true;
	}

	// ---- stage one: the enquiry itself ---------------------------------
	update_post_meta( $id, '_mve_status', $status );
	update_post_meta( $id, '_mve_reviewed', current_time( 'mysql' ) );
	if ( $note ) {
		update_post_meta( $id, '_mve_note', $note );
	}

	// No email for a status that has not moved — saving the screen twice
	// should not send the applicant a second copy of anything.
	if ( $status === $previous ) {
		return true;
	}

	if ( 'invited' === $status ) {
		// A fresh token every time, so an invitation that was forwarded on
		// stops working when a new one is issued.
		mve_issue_invite( $id );
		mve_send_email( 'mve_application_invite', 0, array( 'enquiry' => $id ) );
	} elseif ( 'more_info' === $status ) {
		mve_send_email(
			'mve_enquiry_more_info',
			0,
			array(
				'enquiry' => $id,
				'message' => $note,
			)
		);
	} elseif ( 'declined' === $status ) {
		mve_send_email(
			'mve_enquiry_declined',
			0,
			array(
				'enquiry' => $id,
				'message' => $note,
			)
		);
	}

	/**
	 * Fires when an enquiry moves on. Hook this to push it into a CRM.
	 *
	 * @param int    $id       Enquiry ID.
	 * @param string $status   New status.
	 * @param string $previous Old status.
	 */
	do_action( 'mve_enquiry_status_changed', $id, $status, $previous );

	return true;
}

/* =========================================================================
 * ADMIN — THE LIST
 * ====================================================================== */

/**
 * Columns on the enquiry list.
 *
 * @param array $columns Columns.
 * @return array
 */
function mve_enquiry_columns( $columns ) {
	return array(
		'cb'           => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'        => __( 'Business', 'maison-vintique' ),
		'mve_contact'  => __( 'Contact', 'maison-vintique' ),
		'mve_type'     => __( 'Type', 'maison-vintique' ),
		'mve_where'    => __( 'Where', 'maison-vintique' ),
		'mve_status'   => __( 'Status', 'maison-vintique' ),
		'mve_invite'   => __( 'Invitation', 'maison-vintique' ),
		'date'         => __( 'Received', 'maison-vintique' ),
	);
}
add_filter( 'manage_' . MVE_ENQUIRY_TYPE . '_posts_columns', 'mve_enquiry_columns' );

/**
 * Their content.
 *
 * @param string $column Column key.
 * @param int    $id     Post ID.
 */
function mve_enquiry_column( $column, $id ) {
	switch ( $column ) {
		case 'mve_contact':
			$name  = get_post_meta( $id, '_mve_contact', true );
			$email = get_post_meta( $id, '_mve_email', true );
			echo esc_html( $name );
			if ( $email ) {
				echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
			break;

		case 'mve_type':
			$fields = mve_enquiry_fields();
			$value  = get_post_meta( $id, '_mve_type', true );
			echo esc_html( isset( $fields['type']['options'][ $value ] ) ? $fields['type']['options'][ $value ] : $value );
			break;

		case 'mve_where':
			echo esc_html( get_post_meta( $id, '_mve_town', true ) );
			break;

		case 'mve_status':
			$statuses = mve_enquiry_statuses();
			$colours  = mve_enquiry_status_colours();
			$status   = mve_enquiry_status( $id );
			printf(
				'<span style="display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;color:#fff;background:%s">%s</span>',
				esc_attr( isset( $colours[ $status ] ) ? $colours[ $status ] : '#6f675e' ),
				esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status )
			);
			break;

		case 'mve_invite':
			$expires = get_post_meta( $id, '_mve_token_expires', true );
			$used    = get_post_meta( $id, '_mve_token_used', true );

			if ( $used ) {
				esc_html_e( 'Used', 'maison-vintique' );
			} elseif ( ! $expires ) {
				echo '—';
			} elseif ( strtotime( $expires ) < time() ) {
				echo '<span style="color:#9d3b3b">' . esc_html__( 'Expired', 'maison-vintique' ) . '</span>';
			} else {
				printf(
					/* translators: %s: number of days */
					esc_html__( 'Valid for %s more days', 'maison-vintique' ),
					esc_html( (string) max( 0, (int) ceil( ( strtotime( $expires ) - time() ) / DAY_IN_SECONDS ) ) )
				);
			}
			break;
	}
}
add_action( 'manage_' . MVE_ENQUIRY_TYPE . '_posts_custom_column', 'mve_enquiry_column', 10, 2 );

/* =========================================================================
 * ADMIN — THE REVIEW SCREEN
 * ====================================================================== */

/**
 * Add the two boxes.
 */
function mve_enquiry_meta_boxes() {
	add_meta_box(
		'mve_enquiry_detail',
		__( 'The enquiry', 'maison-vintique' ),
		'mve_enquiry_detail_box',
		MVE_ENQUIRY_TYPE,
		'normal',
		'high'
	);

	add_meta_box(
		'mve_enquiry_application',
		__( 'The full application', 'maison-vintique' ),
		'mve_enquiry_application_box',
		MVE_ENQUIRY_TYPE,
		'normal',
		'default'
	);

	add_meta_box(
		'mve_enquiry_timeline',
		__( 'Where this has got to', 'maison-vintique' ),
		'mve_enquiry_timeline_box',
		MVE_ENQUIRY_TYPE,
		'normal',
		'low'
	);

	// The title changes with the stage, so the box never claims to be the
	// initial review when it is offering the due-diligence outcomes.
	global $post;
	$stage = ( $post && 'application' === mve_enquiry_stage( $post->ID ) )
		? __( 'Due-diligence review', 'maison-vintique' )
		: __( 'Initial review', 'maison-vintique' );

	add_meta_box(
		'mve_enquiry_review',
		$stage,
		'mve_enquiry_review_box',
		MVE_ENQUIRY_TYPE,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_' . MVE_ENQUIRY_TYPE, 'mve_enquiry_meta_boxes' );

/**
 * What they told us.
 *
 * @param WP_Post $post Enquiry.
 */
function mve_enquiry_detail_box( $post ) {
	$fields = mve_enquiry_fields();
	?>
	<table class="form-table" role="presentation">
		<?php foreach ( $fields as $key => $field ) : ?>
			<?php
			$value = get_post_meta( $post->ID, '_mve_' . $key, true );
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			if ( 'select' === $field['type'] && isset( $field['options'][ $value ] ) ) {
				$value = $field['options'][ $value ];
			}
			?>
			<tr>
				<th style="width:230px"><?php echo esc_html( $field['label'] ); ?></th>
				<td>
					<?php
					if ( 'email' === $field['type'] ) {
						printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $value ) );
					} elseif ( 'textarea' === $field['type'] ) {
						echo nl2br( esc_html( $value ) );
					} elseif ( 'website' === $key && $value ) {
						$href = preg_match( '#^https?://#i', $value ) ? $value : 'https://' . $value;
						printf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $href ), esc_html( $value ) );
					} else {
						echo esc_html( $value );
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>

	<?php
}

/* -------------------------------------------------------------------------
 * THE WHOLE PROCESS, ON THE ENQUIRY
 * ---------------------------------------------------------------------
 * The application used to be a button away, on the applicant's user profile,
 * and the due-diligence review happened there too. That is two screens for one
 * decision, and the second one does not look like it has anything to do with
 * trade enquiries. Both are here now.
 * ---------------------------------------------------------------------- */

/**
 * The full application, on the enquiry that produced it.
 *
 * @param WP_Post $post Enquiry.
 */
function mve_enquiry_application_box( $post ) {
	$user_id = mve_enquiry_user( $post->ID );
	$user    = $user_id ? get_userdata( $user_id ) : null;

	if ( ! $user ) {
		?>
		<p class="description" style="margin:0">
			<?php esc_html_e( 'Nothing yet — this appears in full the moment they submit the application.', 'maison-vintique' ); ?>
		</p>
		<?php
		return;
	}

	if ( ! function_exists( 'mve_user_application_panel' ) ) {
		?>
		<p><a class="button" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ); ?>">
			<?php esc_html_e( 'Open the full application', 'maison-vintique' ); ?>
		</a></p>
		<?php
		return;
	}

	// The same panel the profile shows, rendered here. One renderer, so the two
	// can never disagree about what was submitted. The metabox supplies the
	// heading, so the panel does not print its own.
	mve_user_application_panel( $user, false );
}

/**
 * Everything that has happened, in order.
 *
 * @param WP_Post $post Enquiry.
 */
function mve_enquiry_timeline_box( $post ) {
	$user_id  = mve_enquiry_user( $post->ID );
	$statuses = mve_enquiry_statuses();
	$status   = mve_enquiry_status( $post->ID );

	$steps = array(
		array(
			'label' => __( 'Enquiry received', 'maison-vintique' ),
			'when'  => $post->post_date,
			'done'  => true,
			'note'  => __( 'They filled in the short trade enquiry. Both sides were emailed.', 'maison-vintique' ),
		),
		array(
			'label' => __( 'Initial review', 'maison-vintique' ),
			'when'  => get_post_meta( $post->ID, '_mve_reviewed', true ),
			'done'  => 'new' !== $status,
			'note'  => __( 'Whether they may apply at all. Decided in the box on the right.', 'maison-vintique' ),
		),
		array(
			'label' => __( 'Invitation sent', 'maison-vintique' ),
			'when'  => get_post_meta( $post->ID, '_mve_token_issued', true ),
			'done'  => (bool) get_post_meta( $post->ID, '_mve_token', true ),
			'note'  => __( 'The private, expiring link to the full application.', 'maison-vintique' ),
		),
		array(
			'label' => __( 'Application submitted', 'maison-vintique' ),
			'when'  => get_post_meta( $post->ID, '_mve_token_used', true ),
			'done'  => (bool) $user_id,
			'note'  => __( 'The nine sections, with documents. Their account was created, closed until approval.', 'maison-vintique' ),
		),
		array(
			'label' => __( 'Due-diligence review', 'maison-vintique' ),
			'when'  => $user_id ? get_user_meta( $user_id, 'mve_trade_status_changed', true ) : '',
			'done'  => $user_id && 'pending' !== ( function_exists( 'mve_trade_status' ) ? mve_trade_status( $user_id ) : 'pending' ),
			'note'  => __( 'Decided in the box on the right. The applicant is emailed the outcome.', 'maison-vintique' ),
		),
		array(
			'label' => __( 'Trade portal active', 'maison-vintique' ),
			'when'  => 'account' === $status ? get_post_meta( $post->ID, '_mve_reviewed', true ) : '',
			'done'  => 'account' === $status,
			'note'  => __( 'They can sign in, and pricing is visible to them.', 'maison-vintique' ),
		),
	);
	$colours = mve_enquiry_status_colours();
	$colour  = isset( $colours[ $status ] ) ? $colours[ $status ] : '#a98854';
	?>
	<p style="margin:0 0 16px;padding:10px 12px;border-left:3px solid <?php echo esc_attr( $colour ); ?>;background:#fbfbfb">
		<strong><?php esc_html_e( 'Right now:', 'maison-vintique' ); ?></strong>
		<?php echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status ); ?>
	</p>

	<ol style="margin:0;padding:0;list-style:none">
		<?php foreach ( $steps as $step ) : ?>
			<li style="display:flex;gap:12px;padding:0 0 16px;position:relative">
				<span aria-hidden="true" style="flex:0 0 20px;height:20px;line-height:20px;text-align:center;border-radius:50%;font-size:12px;<?php
					echo $step['done']
						? 'background:#2e7d5b;color:#fff'
						: 'background:#e6e6e6;color:#8a8a8a';
				?>"><?php echo $step['done'] ? '&#10003;' : '&middot;'; ?></span>
				<span>
					<strong style="<?php echo $step['done'] ? '' : 'color:#8a8a8a'; ?>"><?php echo esc_html( $step['label'] ); ?></strong>
					<?php if ( $step['done'] && $step['when'] ) : ?>
						<span class="description"> — <?php echo esc_html( $step['when'] ); ?></span>
					<?php elseif ( ! $step['done'] ) : ?>
						<span class="description"> — <?php esc_html_e( 'not yet', 'maison-vintique' ); ?></span>
					<?php endif; ?>
					<?php if ( $step['note'] ) : ?>
						<span class="description" style="display:block"><?php echo esc_html( $step['note'] ); ?></span>
					<?php endif; ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
}

/**
 * The decision.
 *
 * @param WP_Post $post Enquiry.
 */
function mve_enquiry_review_box( $post ) {
	$status  = mve_enquiry_status( $post->ID );
	$stage   = mve_enquiry_stage( $post->ID );
	$note    = get_post_meta( $post->ID, '_mve_note', true );
	$link    = mve_invite_url( $post->ID );
	$expires = get_post_meta( $post->ID, '_mve_token_expires', true );
	$used    = get_post_meta( $post->ID, '_mve_token_used', true );
	$user_id = mve_enquiry_user( $post->ID );

	wp_nonce_field( 'mve_enquiry_review', 'mve_enquiry_nonce' );

	/*
	 * TWO STAGES, ONE SCREEN.
	 *
	 * Stage one decides whether they may APPLY. Stage two — once the full
	 * application is in — is the due-diligence review that decides whether the
	 * trade account opens. Both live here, so there is one place to work from
	 * and nobody has to know that the second stage is stored on a user account.
	 */
	$choices = ( 'application' === $stage )
		? array(
			'account'      => array(
				__( 'Approve the trade account', 'maison-vintique' ),
				__( 'Opens the portal. Emails them the approval, and pricing appears the moment they sign in.', 'maison-vintique' ),
			),
			'app_info'     => array(
				__( 'Request further information', 'maison-vintique' ),
				__( 'Emails them your note below — a VAT number, a licence copy, a trade reference. The account stays closed.', 'maison-vintique' ),
			),
			'app_hold'     => array(
				__( 'Put on hold', 'maison-vintique' ),
				__( 'Emails them to say it is paused. Nothing is lost; pick it up here whenever you are ready.', 'maison-vintique' ),
			),
			'app_declined' => array(
				__( 'Decline the application', 'maison-vintique' ),
				__( 'Emails a short, courteous decline. Your note is included if you write one.', 'maison-vintique' ),
			),
		)
		: array(
			'invited'   => array(
				__( 'Approve to apply', 'maison-vintique' ),
				__( 'Emails them a private link to the full application. This approves them to APPLY — not the trade account itself.', 'maison-vintique' ),
			),
			'more_info' => array(
				__( 'Request further information', 'maison-vintique' ),
				__( 'Emails them your note below and asks them to reply.', 'maison-vintique' ),
			),
			'declined'  => array(
				__( 'Decline — not suitable at present', 'maison-vintique' ),
				__( 'Emails a short, courteous decline. Your note is included if you write one.', 'maison-vintique' ),
			),
		);
	?>
	<p style="margin-top:0">
		<strong><?php esc_html_e( 'Currently:', 'maison-vintique' ); ?></strong>
		<?php
		$statuses = mve_enquiry_statuses();
		echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status );
		?>
	</p>

	<?php if ( 'application' === $stage ) : ?>
		<p class="description" style="margin:-6px 0 14px">
			<?php esc_html_e( 'Stage two — the due-diligence review. The full application is below.', 'maison-vintique' ); ?>
		</p>
	<?php endif; ?>

	<?php foreach ( $choices as $key => $choice ) : ?>
		<p style="margin:0 0 12px">
			<label style="display:block;font-weight:600">
				<input type="radio" name="mve_enquiry_status" value="<?php echo esc_attr( $key ); ?>" <?php checked( $status, $key ); ?>>
				<?php echo esc_html( $choice[0] ); ?>
			</label>
			<span class="description" style="display:block;margin-left:24px"><?php echo esc_html( $choice[1] ); ?></span>
		</p>
	<?php endforeach; ?>

	<p style="margin:0 0 6px">
		<label for="mve_enquiry_note"><strong><?php esc_html_e( 'Note to them', 'maison-vintique' ); ?></strong></label>
	</p>
	<textarea name="mve_enquiry_note" id="mve_enquiry_note" rows="4" class="widefat"
		placeholder="<?php esc_attr_e( 'e.g. Could you send a link to your current wine list?', 'maison-vintique' ); ?>"><?php echo esc_textarea( $note ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Included in the "further information", "on hold" and "declined" emails. Not sent with an approval.', 'maison-vintique' ); ?>
	</p>

	<p style="margin:14px 0 0">
		<em class="description"><?php esc_html_e( 'Choose an outcome, then Update. The email goes out as you save.', 'maison-vintique' ); ?></em>
	</p>

	<?php if ( $user_id ) : ?>
		<hr>
		<p style="margin:0">
			<a class="button button-small" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ); ?>">
				<?php esc_html_e( 'Open their account', 'maison-vintique' ); ?>
			</a>
		</p>
		<p class="description"><?php esc_html_e( 'Only for editing the account itself. The review is done here.', 'maison-vintique' ); ?></p>
	<?php endif; ?>

	<?php if ( $link ) : ?>
		<hr>
		<p style="margin:0 0 4px"><strong><?php esc_html_e( 'Their private link', 'maison-vintique' ); ?></strong></p>
		<input type="text" class="widefat" readonly onclick="this.select()" value="<?php echo esc_attr( $link ); ?>">
		<p class="description">
			<?php
			if ( $used ) {
				esc_html_e( 'Already used — the application has been submitted, and the link no longer opens anything.', 'maison-vintique' );
			} elseif ( $expires && strtotime( $expires ) < time() ) {
				esc_html_e( 'Expired. Choosing "Approve to apply" again issues a fresh link and emails it.', 'maison-vintique' );
			} elseif ( $expires ) {
				printf(
					/* translators: %s: date */
					esc_html__( 'Valid until %s. It was emailed to them — this copy is here in case they lose it.', 'maison-vintique' ),
					esc_html( $expires )
				);
			}
			?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Save the decision.
 *
 * @param int $post_id Enquiry ID.
 */
function mve_save_enquiry_review( $post_id ) {
	if ( ! isset( $_POST['mve_enquiry_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mve_enquiry_nonce'] ) ), 'mve_enquiry_review' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$note = isset( $_POST['mve_enquiry_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mve_enquiry_note'] ) ) : '';
	if ( $note ) {
		update_post_meta( $post_id, '_mve_note', $note );
	}

	if ( ! empty( $_POST['mve_enquiry_status'] ) ) {
		mve_set_enquiry_status( $post_id, sanitize_key( wp_unslash( $_POST['mve_enquiry_status'] ) ), $note );
	}
}
add_action( 'save_post_' . MVE_ENQUIRY_TYPE, 'mve_save_enquiry_review' );

/**
 * How long an invitation lasts, on the WooCommerce email settings screen —
 * next to the address the notifications go to, which is where somebody
 * configuring this workflow will already be.
 *
 * @param array $settings WooCommerce email settings.
 * @return array
 */
function mve_invite_setting( $settings ) {
	$out = array();
	foreach ( $settings as $setting ) {
		// Slip it in just before our notifications section closes.
		if ( isset( $setting['id'], $setting['type'] ) && 'mve_notifications' === $setting['id'] && 'sectionend' === $setting['type'] ) {
			$out[] = array(
				'title'             => __( 'Application invitation expires after', 'maison-vintique' ),
				'desc'              => __( 'Days. How long the private link to the full trade application stays usable once you approve an enquiry.', 'maison-vintique' ),
				'id'                => 'mve_invite_days',
				'type'              => 'number',
				'default'           => 21,
				'css'               => 'width:80px;',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			);
		}
		$out[] = $setting;
	}
	return $out;
}
add_filter( 'woocommerce_email_settings', 'mve_invite_setting', 20 );
