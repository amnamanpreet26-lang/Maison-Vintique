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
 * The first four are the initial review the client described. The last two are
 * what happens afterwards, so the list shows the whole journey rather than
 * going quiet the moment the invitation is sent.
 *
 * @return array
 */
function mve_enquiry_statuses() {
	return array(
		'new'       => __( 'New — awaiting review', 'maison-vintique' ),
		'invited'   => __( 'Approved to apply — invitation sent', 'maison-vintique' ),
		'more_info' => __( 'More information requested', 'maison-vintique' ),
		'declined'  => __( 'Declined — not suitable at present', 'maison-vintique' ),
		'applied'   => __( 'Full application submitted', 'maison-vintique' ),
		'account'   => __( 'Trade account approved', 'maison-vintique' ),
	);
}

/**
 * The colour each status shows in.
 *
 * @return array
 */
function mve_enquiry_status_colours() {
	return array(
		'new'       => '#a98854',
		'invited'   => '#35618e',
		'more_info' => '#8a6d3b',
		'declined'  => '#9d3b3b',
		'applied'   => '#5b4b8a',
		'account'   => '#2e7d5b',
	);
}

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
		'mve_enquiry_review',
		__( 'Initial review', 'maison-vintique' ),
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
	$user_id = (int) get_post_meta( $post->ID, '_mve_user_id', true );
	if ( $user_id ) :
		?>
		<p style="margin:16px 0 0">
			<a class="button" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ); ?>">
				<?php esc_html_e( 'Open the full application', 'maison-vintique' ); ?>
			</a>
			<span class="description" style="margin-left:8px">
				<?php esc_html_e( 'They have submitted it. The due-diligence review lives on their profile.', 'maison-vintique' ); ?>
			</span>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * The decision.
 *
 * @param WP_Post $post Enquiry.
 */
function mve_enquiry_review_box( $post ) {
	$status  = mve_enquiry_status( $post->ID );
	$note    = get_post_meta( $post->ID, '_mve_note', true );
	$link    = mve_invite_url( $post->ID );
	$expires = get_post_meta( $post->ID, '_mve_token_expires', true );
	$used    = get_post_meta( $post->ID, '_mve_token_used', true );

	wp_nonce_field( 'mve_enquiry_review', 'mve_enquiry_nonce' );

	$choices = array(
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

	<?php if ( in_array( $status, array( 'applied', 'account' ), true ) ) : ?>
		<p class="description">
			<?php esc_html_e( 'This one is past the initial stage — the decisions now happen on the applicant\'s profile.', 'maison-vintique' ); ?>
		</p>
	<?php else : ?>

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
		<p class="description"><?php esc_html_e( 'Included in the "more information" and "declined" emails. Not sent with an approval.', 'maison-vintique' ); ?></p>

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
