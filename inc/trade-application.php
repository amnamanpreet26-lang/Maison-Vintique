<?php
/**
 * inc/trade-application.php
 *
 * The Trade Account Application — the web version of the client's nine-section
 * Word form.
 *
 * WHAT IT IS FOR
 *   It is the real "Apply for a trade account" form. Until now that link went
 *   to WooCommerce's registration, which asks for an email and a password.
 *   This asks everything Maison Vintique actually needs before it can open an
 *   account for a licensed business: company and VAT numbers, licensing, AWRS
 *   status, who is authorised to order, where deliveries can go, and the
 *   declarations that have to be agreed.
 *
 * WHERE IT GOES
 *   Create a page, pick "Trade Account Application" under Page Attributes →
 *   Template, and point "Apply for an account" at it (the login page picks it
 *   up automatically — see mve_trade_application_url()).
 *
 * WHAT HAPPENS ON SUBMIT
 *   1. Validated. Anything invalid comes straight back with the values kept.
 *   2. A WordPress customer account is created, status "pending".
 *   3. Every answer is stored against that user, so the reviewer sees the whole
 *      application on the user's profile screen.
 *   4. Uploaded documents are attached to the media library, privately.
 *   5. "Application received" goes to the applicant; a notification goes to
 *      the shop. Approving them on the profile screen sends the approval email.
 *
 * ONE SCHEMA
 *   mve_application_schema() defines every section and field once. The form,
 *   the validation, the storage and the admin display all read it, so adding a
 *   field is a single edit and nothing can fall out of step.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * The whole form, as data.
 *
 * Field types: text, email, tel, url, textarea, date, number, select, radio,
 * checkgroup (many), checkbox (one), file, users (the repeatable table),
 * note (static copy, not a field).
 *
 * @return array
 */
function mve_application_schema() {
	$schema = array(

		'business' => array(
			'title'  => __( 'Business information', 'maison-vintique' ),
			'fields' => array(
				'legal_name'        => array( 'label' => __( 'Legal company / proprietor name', 'maison-vintique' ), 'type' => 'text', 'required' => true ),
				'trading_name'      => array( 'label' => __( 'Trading name', 'maison-vintique' ), 'type' => 'text' ),
				'company_number'    => array( 'label' => __( 'Company registration number', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'vat_number'        => array( 'label' => __( 'VAT number', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'date_established'  => array( 'label' => __( 'Date established', 'maison-vintique' ), 'type' => 'date', 'width' => 'half' ),
				'website'           => array( 'label' => __( 'Website', 'maison-vintique' ), 'type' => 'url', 'width' => 'half' ),
				'main_phone'        => array( 'label' => __( 'Main telephone', 'maison-vintique' ), 'type' => 'tel', 'required' => true, 'width' => 'half' ),
				'general_email'     => array( 'label' => __( 'General email', 'maison-vintique' ), 'type' => 'email', 'required' => true, 'width' => 'half' ),
				'registered_office' => array( 'label' => __( 'Registered office address', 'maison-vintique' ), 'type' => 'textarea', 'required' => true ),
				'trading_address'   => array( 'label' => __( 'Trading address', 'maison-vintique' ), 'type' => 'textarea', 'hint' => __( 'Only if different from the registered office.', 'maison-vintique' ) ),
				'legal_structure'   => array(
					'label'    => __( 'Legal structure', 'maison-vintique' ),
					'type'     => 'radio',
					'required' => true,
					'options'  => array(
						'limited'     => __( 'Limited company', 'maison-vintique' ),
						'llp'         => __( 'LLP', 'maison-vintique' ),
						'partnership' => __( 'Partnership', 'maison-vintique' ),
						'sole_trader' => __( 'Sole trader', 'maison-vintique' ),
						'other'       => __( 'Other', 'maison-vintique' ),
					),
				),
				'parent_company'    => array( 'label' => __( 'Parent company', 'maison-vintique' ), 'type' => 'text', 'hint' => __( 'If applicable.', 'maison-vintique' ), 'width' => 'half' ),
				'outlets'           => array( 'label' => __( 'Number of outlets / sites', 'maison-vintique' ), 'type' => 'number', 'width' => 'half', 'min' => 0 ),
				'business_type'     => array(
					'label'   => __( 'Nature of business', 'maison-vintique' ),
					'type'    => 'checkgroup',
					'options' => array(
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
				'order_frequency'   => array(
					'label'   => __( 'Expected order frequency', 'maison-vintique' ),
					'type'    => 'select',
					'width'   => 'half',
					'options' => array(
						''            => __( 'Please choose', 'maison-vintique' ),
						'weekly'      => __( 'Weekly', 'maison-vintique' ),
						'fortnightly' => __( 'Fortnightly', 'maison-vintique' ),
						'monthly'     => __( 'Monthly', 'maison-vintique' ),
						'quarterly'   => __( 'Quarterly', 'maison-vintique' ),
						'occasional'  => __( 'Occasional', 'maison-vintique' ),
					),
				),
				'order_method'      => array(
					'label'   => __( 'Preferred ordering method', 'maison-vintique' ),
					'type'    => 'select',
					'width'   => 'half',
					'options' => array(
						''        => __( 'Please choose', 'maison-vintique' ),
						'website' => __( 'Website', 'maison-vintique' ),
						'email'   => __( 'Email', 'maison-vintique' ),
						'phone'   => __( 'Telephone', 'maison-vintique' ),
						'rep'     => __( 'Through our account manager', 'maison-vintique' ),
					),
				),
			),
		),

		'contacts' => array(
			'title'  => __( 'Primary contacts', 'maison-vintique' ),
			'fields' => array(
				'primary_name'   => array( 'label' => __( 'Primary account contact', 'maison-vintique' ), 'type' => 'text', 'required' => true, 'width' => 'half' ),
				'primary_role'   => array( 'label' => __( 'Job title', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'primary_phone'  => array( 'label' => __( 'Telephone', 'maison-vintique' ), 'type' => 'tel', 'width' => 'half' ),
				'primary_email'  => array( 'label' => __( 'Email', 'maison-vintique' ), 'type' => 'email', 'required' => true, 'width' => 'half', 'hint' => __( 'This becomes the sign-in for the account.', 'maison-vintique' ) ),

				'buyer_name'     => array( 'label' => __( 'Wine buyer / purchasing contact', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'buyer_role'     => array( 'label' => __( 'Job title', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'buyer_phone'    => array( 'label' => __( 'Telephone', 'maison-vintique' ), 'type' => 'tel', 'width' => 'half' ),
				'buyer_email'    => array( 'label' => __( 'Email', 'maison-vintique' ), 'type' => 'email', 'width' => 'half' ),

				'accounts_name'  => array( 'label' => __( 'Accounts contact', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'accounts_email' => array( 'label' => __( 'Invoice email', 'maison-vintique' ), 'type' => 'email', 'width' => 'half' ),
				'accounts_phone' => array( 'label' => __( 'Telephone', 'maison-vintique' ), 'type' => 'tel', 'width' => 'half' ),
				'po_required'    => array(
					'label'   => __( 'Purchase order required?', 'maison-vintique' ),
					'type'    => 'radio',
					'width'   => 'half',
					'options' => array(
						'yes' => __( 'Yes', 'maison-vintique' ),
						'no'  => __( 'No', 'maison-vintique' ),
					),
				),
			),
		),

		'users' => array(
			'title'  => __( 'Authorised website & order users', 'maison-vintique' ),
			'intro'  => __( 'The people allowed to see trade pricing, place or amend orders, and obtain account documents.', 'maison-vintique' ),
			'fields' => array(
				'authorised_users' => array( 'label' => '', 'type' => 'users' ),
			),
		),

		'licensing' => array(
			'title'  => __( 'Alcohol licensing', 'maison-vintique' ),
			'fields' => array(
				'sells_to_public'   => array(
					'label'    => __( 'Does the business sell or supply alcohol to members of the public?', 'maison-vintique' ),
					'type'     => 'radio',
					'required' => true,
					'options'  => array(
						'yes' => __( 'Yes', 'maison-vintique' ),
						'no'  => __( 'No', 'maison-vintique' ),
					),
					'controls' => 'licence-details',
				),
				'licence_number'    => array( 'label' => __( 'Premises licence number', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'licence-details' ),
				'licence_authority' => array( 'label' => __( 'Licensing authority', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'licence-details' ),
				'licence_holder'    => array( 'label' => __( 'Premises licence holder', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'licence-details' ),
				'licence_dps'       => array( 'label' => __( 'Designated premises supervisor', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'licence-details' ),
				'licence_address'   => array( 'label' => __( 'Licensed premises address', 'maison-vintique' ), 'type' => 'textarea', 'group' => 'licence-details' ),
				'licence_none_why'  => array( 'label' => __( 'If no premises licence is held, explain why it is not required', 'maison-vintique' ), 'type' => 'textarea', 'group' => 'licence-details', 'when' => 'no' ),
			),
		),

		'awrs' => array(
			'title'  => __( 'AWRS status', 'maison-vintique' ),
			'intro'  => __( 'An AWRS number is needed only if you sell alcohol on to other businesses for onward sale. It is not needed simply because you buy from us to sell to your own customers.', 'maison-vintique' ),
			'fields' => array(
				'sells_to_trade' => array(
					'label'    => __( 'Do you sell or arrange the sale of alcohol to other businesses for onward sale or supply?', 'maison-vintique' ),
					'type'     => 'radio',
					'required' => true,
					'options'  => array(
						'yes'    => __( 'Yes', 'maison-vintique' ),
						'no'     => __( 'No', 'maison-vintique' ),
						'unsure' => __( 'Unsure', 'maison-vintique' ),
					),
					'controls' => 'awrs-details',
				),
				'awrs_urn'       => array( 'label' => __( 'AWRS Unique Reference Number', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'awrs-details' ),
				'awrs_entity'    => array( 'label' => __( 'Legal entity on the AWRS registration', 'maison-vintique' ), 'type' => 'text', 'width' => 'half', 'group' => 'awrs-details' ),
				'awrs_address'   => array( 'label' => __( 'AWRS registered trading address', 'maison-vintique' ), 'type' => 'textarea', 'group' => 'awrs-details' ),
			),
		),

		'delivery' => array(
			'title'  => __( 'Billing & delivery', 'maison-vintique' ),
			'fields' => array(
				'invoice_address'      => array( 'label' => __( 'Invoice address', 'maison-vintique' ), 'type' => 'textarea', 'required' => true, 'width' => 'half' ),
				'delivery_address'     => array( 'label' => __( 'Primary delivery address', 'maison-vintique' ), 'type' => 'textarea', 'required' => true, 'width' => 'half' ),
				'delivery_contact'     => array( 'label' => __( 'Delivery contact', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'delivery_phone'       => array( 'label' => __( 'Delivery telephone', 'maison-vintique' ), 'type' => 'tel', 'width' => 'half' ),
				'delivery_email'       => array( 'label' => __( 'Delivery email', 'maison-vintique' ), 'type' => 'email', 'width' => 'half' ),
				'booking_required'     => array(
					'label'   => __( 'Booking-in required?', 'maison-vintique' ),
					'type'    => 'radio',
					'width'   => 'half',
					'options' => array( 'yes' => __( 'Yes', 'maison-vintique' ), 'no' => __( 'No', 'maison-vintique' ) ),
				),
				'booking_contact'      => array( 'label' => __( 'Booking-in contact / number', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'opening_hours'        => array( 'label' => __( 'Normal opening hours', 'maison-vintique' ), 'type' => 'text', 'width' => 'half' ),
				'delivery_notes'       => array( 'label' => __( 'Delivery restrictions, access, parking or loading requirements', 'maison-vintique' ), 'type' => 'textarea' ),
				'delivery_location'    => array(
					'label'   => __( 'The delivery location is', 'maison-vintique' ),
					'type'    => 'radio',
					'options' => array(
						'licensed'  => __( 'Licensed premises', 'maison-vintique' ),
						'warehouse' => __( 'Warehouse', 'maison-vintique' ),
						'other'     => __( 'Other authorised business location', 'maison-vintique' ),
					),
				),
				'leave_goods'          => array(
					'label'   => __( 'If no authorised recipient is available, may goods be left at the approved delivery location?', 'maison-vintique' ),
					'type'    => 'radio',
					'options' => array( 'yes' => __( 'Yes', 'maison-vintique' ), 'no' => __( 'No', 'maison-vintique' ) ),
				),
			),
		),

		'payment' => array(
			'title'  => __( 'Payment', 'maison-vintique' ),
			'intro'  => __( 'All new trade accounts operate on payment-before-dispatch terms. Any future credit facility is a separate application and needs our written approval.', 'maison-vintique' ),
			'fields' => array(
				'payment_ack' => array(
					'label'    => __( 'I acknowledge that payment is required before goods are released.', 'maison-vintique' ),
					'type'     => 'checkbox',
					'required' => true,
				),
			),
		),

		'documents' => array(
			'title'  => __( 'Supporting documents', 'maison-vintique' ),
			'intro'  => __( 'PDF, JPG or PNG, up to 8MB each. We may ask for more if something cannot be verified independently.', 'maison-vintique' ),
			'fields' => array(
				'doc_licence'  => array( 'label' => __( 'Premises licence / licence summary', 'maison-vintique' ), 'type' => 'file', 'width' => 'half' ),
				'doc_business' => array( 'label' => __( 'Business document showing trading name and address', 'maison-vintique' ), 'type' => 'file', 'width' => 'half' ),
				'doc_awrs'     => array( 'label' => __( 'AWRS evidence, where applicable', 'maison-vintique' ), 'type' => 'file', 'width' => 'half' ),
				'doc_vat'      => array( 'label' => __( 'VAT evidence, if requested', 'maison-vintique' ), 'type' => 'file', 'width' => 'half' ),
				'doc_other'    => array( 'label' => __( 'Any other supporting document', 'maison-vintique' ), 'type' => 'file', 'width' => 'half' ),
			),
		),

		'declarations' => array(
			'title'  => __( 'Declarations', 'maison-vintique' ),
			'fields' => array(
				'dec_authorised'   => array( 'label' => __( 'I am authorised to submit this application on behalf of the applicant business.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_accurate'     => array( 'label' => __( 'The information supplied is complete, accurate and not misleading.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_legitimate'   => array( 'label' => __( 'Alcohol purchased will be used only for legitimate business purposes.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_authorised_o' => array( 'label' => __( 'Orders will be placed only by authorised individuals and delivered only to approved business locations.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_notify'       => array( 'label' => __( 'We will notify you promptly of changes to ownership, company status, licensing, AWRS status, trading activity or delivery addresses.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_awrs'         => array( 'label' => __( 'We will not carry out wholesale alcohol activity unless appropriately registered under AWRS where that is required.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_verify'       => array( 'label' => __( 'You may verify this information through public records, business databases, licensing authorities and other relevant sources as part of your due diligence.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true ),
				'dec_terms'        => array( 'label' => __( 'I accept the Terms and Conditions of Trade.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true, 'link' => 'terms' ),
				'dec_privacy'      => array( 'label' => __( 'I acknowledge the Privacy Notice.', 'maison-vintique' ), 'type' => 'checkbox', 'required' => true, 'link' => 'privacy' ),
				'dec_marketing'    => array( 'label' => __( 'Optional: I would like trade news, portfolio updates and event invitations.', 'maison-vintique' ), 'type' => 'checkbox' ),

				'signatory_name'   => array( 'label' => __( 'Applicant full name', 'maison-vintique' ), 'type' => 'text', 'required' => true, 'width' => 'half' ),
				'signatory_role'   => array( 'label' => __( 'Position', 'maison-vintique' ), 'type' => 'text', 'required' => true, 'width' => 'half' ),
				'signature'        => array( 'label' => __( 'Signature — type your full name to sign', 'maison-vintique' ), 'type' => 'text', 'required' => true, 'width' => 'half' ),
			),
		),
	);

	/**
	 * Filter the application form. Add, remove or reorder sections and fields
	 * here rather than editing this file.
	 */
	return apply_filters( 'mve_application_schema', $schema );
}

/**
 * Every field, flattened, keyed by name.
 *
 * @return array
 */
function mve_application_fields() {
	$fields = array();
	foreach ( mve_application_schema() as $section ) {
		foreach ( $section['fields'] as $name => $field ) {
			// Normalised once, here, so every reader can rely on the keys
			// existing — including fields added through the filter.
			$fields[ $name ] = wp_parse_args(
				$field,
				array(
					'label'   => '',
					'type'    => 'text',
					'options' => array(),
				)
			);
		}
	}
	return $fields;
}

/**
 * The published page using the application template, if there is one.
 *
 * Empty until somebody actually makes the page, which is what lets the login
 * screen decide between "send them to the full application" and "keep showing
 * the short registration form".
 *
 * Cached, because this runs on the login page and in the footer popup on every
 * request; the cache is cleared whenever any page is saved.
 *
 * @return string URL, or ''.
 */
function mve_trade_application_page_url() {
	$cached = get_transient( 'mve_application_page_url' );
	if ( null !== $cached && false !== $cached ) {
		return (string) $cached;
	}

	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'numberposts'      => 1,
			'meta_key'         => '_wp_page_template',   // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => 'template-trade-application.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'post_status'      => 'publish',
			'suppress_filters' => false,
		)
	);

	$url = $pages ? (string) get_permalink( $pages[0] ) : '';

	// Stored either way — "there isn't one" is worth caching too, otherwise
	// every page load queries for a page that does not exist.
	set_transient( 'mve_application_page_url', $url, HOUR_IN_SECONDS );
	return $url;
}

/**
 * Where "Apply for a trade account" should go. Always a real URL.
 *
 * @return string
 */
function mve_trade_application_url() {
	$url = mve_trade_application_page_url();
	if ( ! $url ) {
		// No page made yet — fall back to the account page so the link is
		// never dead.
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	}
	return apply_filters( 'mve_trade_application_url', $url );
}

/** Forget the cached URL whenever a page is saved. */
add_action( 'save_post_page', function () {
	delete_transient( 'mve_application_page_url' );
} );

/**
 * Once the application page exists it replaces the short registration form on
 * the login screen — two ways to apply is one too many, and the short one does
 * not collect anything the shop actually needs.
 *
 * To keep the short form as well:
 *   add_filter( 'mve_show_registration_form', '__return_true', 20 );
 */
add_filter( 'mve_show_registration_form', function ( $show ) {
	return mve_trade_application_page_url() ? false : $show;
} );

/* =========================================================================
 * THE FORM
 * ---------------------------------------------------------------------
 * Rendered from the schema, so the markup can never drift away from the
 * validation. Classes are mvta-* — a deliberate copy of the contact form's
 * shape (mvp-field) under its own names, so restyling the application never
 * disturbs the contact page and vice versa.
 * ====================================================================== */

/**
 * Values to put back in the boxes after a failed submit.
 *
 * Kept in a short-lived transient rather than the session or a cookie: the
 * form is long, and making somebody retype it because one checkbox was missed
 * is the fastest way to lose an application.
 *
 * @return array {errors, values}
 */
function mve_application_flash() {
	static $flash = null;
	if ( null !== $flash ) {
		return $flash;
	}

	$flash = array(
		'errors' => array(),
		'values' => array(),
	);

	$token = isset( $_GET['mvta_ref'] ) ? sanitize_key( wp_unslash( $_GET['mvta_ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	if ( ! $token ) {
		return $flash;
	}

	$stored = get_transient( 'mve_app_' . $token );
	if ( is_array( $stored ) ) {
		$flash = wp_parse_args( $stored, $flash );
		delete_transient( 'mve_app_' . $token );
	}
	return $flash;
}

/**
 * The value to show in a field: what they typed last time, else nothing.
 *
 * @param string $name Field name.
 * @return mixed
 */
function mve_application_value( $name ) {
	$flash = mve_application_flash();
	return isset( $flash['values'][ $name ] ) ? $flash['values'][ $name ] : '';
}

/**
 * Was this field rejected?
 *
 * @param string $name Field name.
 * @return string Message, or ''.
 */
function mve_application_error( $name ) {
	$flash = mve_application_flash();
	return isset( $flash['errors'][ $name ] ) ? $flash['errors'][ $name ] : '';
}

/**
 * One field.
 *
 * @param string $name  Field name.
 * @param array  $field Schema entry.
 */
function mve_application_field( $name, $field ) {
	$type     = isset( $field['type'] ) ? $field['type'] : 'text';
	$label    = isset( $field['label'] ) ? $field['label'] : '';
	$hint     = isset( $field['hint'] ) ? $field['hint'] : '';
	$required = ! empty( $field['required'] );
	$width    = isset( $field['width'] ) ? $field['width'] : 'full';
	$value    = mve_application_value( $name );
	$error    = mve_application_error( $name );
	$id       = 'mvta-' . str_replace( '_', '-', $name );

	$classes = array( 'mvta-field', 'mvta-field--' . $width, 'mvta-field--' . $type );
	if ( $error ) {
		$classes[] = 'is-invalid';
	}
	if ( ! empty( $field['group'] ) ) {
		$classes[] = 'mvta-dependent';
	}

	$attrs = '';
	if ( ! empty( $field['group'] ) ) {
		// Hidden until its controlling question is answered — see main.js.
		$attrs .= ' data-mvta-group="' . esc_attr( $field['group'] ) . '"';

		// Most dependants appear on "yes"; 'when' flips that for the handful
		// that only make sense after a "no".
		if ( ! empty( $field['when'] ) ) {
			$attrs .= ' data-mvta-when="' . esc_attr( $field['when'] ) . '"';
		}
	}
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?>>

		<?php if ( 'checkbox' === $type ) : ?>

			<label class="mvta-check" for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1"
					<?php checked( (bool) $value ); ?>
					<?php echo $required ? 'required' : ''; ?>>
				<span class="mvta-check__box" aria-hidden="true"></span>
				<span class="mvta-check__label">
					<?php echo esc_html( $label ); ?>
					<?php
					// "I accept the Terms" needs the terms to be reachable.
					if ( ! empty( $field['link'] ) ) {
						$page = ( 'terms' === $field['link'] && function_exists( 'wc_get_page_permalink' ) )
							? wc_get_page_permalink( 'terms' )
							: get_privacy_policy_url();
						if ( $page ) {
							printf(
								' <a href="%s" target="_blank" rel="noopener">%s</a>',
								esc_url( $page ),
								esc_html__( 'Read it', 'maison-vintique' )
							);
						}
					}
					?>
					<?php echo $required ? '<span class="mvta-req" aria-hidden="true">*</span>' : ''; ?>
				</span>
			</label>

		<?php else : ?>

			<?php if ( $label ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>" class="mvta-label">
					<?php echo esc_html( $label ); ?>
					<?php echo $required ? '<span class="mvta-req" aria-hidden="true">*</span>' : ''; ?>
				</label>
			<?php endif; ?>

			<?php
			switch ( $type ) :
				case 'textarea':
					?>
					<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3"
						<?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea( (string) $value ); ?></textarea>
					<?php
					break;

				case 'select':
					?>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php echo $required ? 'required' : ''; ?>>
						<?php foreach ( $field['options'] as $key => $option ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $value, (string) $key ); ?>>
								<?php echo esc_html( $option ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php
					break;

				case 'radio':
					?>
					<div class="mvta-opts" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>"
						<?php echo ! empty( $field['controls'] ) ? 'data-mvta-controls="' . esc_attr( $field['controls'] ) . '"' : ''; ?>>
						<?php foreach ( $field['options'] as $key => $option ) : ?>
							<label class="mvta-opt">
								<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>"
									<?php checked( (string) $value, (string) $key ); ?>
									<?php echo $required ? 'required' : ''; ?>>
								<span><?php echo esc_html( $option ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php
					break;

				case 'checkgroup':
					$chosen = (array) $value;
					?>
					<div class="mvta-opts mvta-opts--multi">
						<?php foreach ( $field['options'] as $key => $option ) : ?>
							<label class="mvta-opt">
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $key ); ?>"
									<?php checked( in_array( (string) $key, array_map( 'strval', $chosen ), true ) ); ?>>
								<span><?php echo esc_html( $option ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php
					break;

				case 'file':
					?>
					<input type="file" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"
						accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
					<?php
					break;

				case 'users':
					mve_application_users_table( $name );
					break;

				default:
					?>
					<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>"
						<?php echo isset( $field['min'] ) ? 'min="' . esc_attr( $field['min'] ) . '"' : ''; ?>
						<?php echo $required ? 'required' : ''; ?>>
					<?php
			endswitch;
			?>

		<?php endif; ?>

		<?php if ( $hint ) : ?>
			<p class="mvta-hint"><?php echo esc_html( $hint ); ?></p>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<p class="mvta-error"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The repeatable "who may order" table.
 *
 * Rendered as real rows rather than a JavaScript widget, so the form still
 * works with scripts blocked — the button just adds more of the same.
 *
 * @param string $name Field name.
 */
function mve_application_users_table( $name ) {
	$rows = (array) mve_application_value( $name );
	$rows = array_values( array_filter( $rows, 'is_array' ) );

	// Always show at least two empty rows — one looks like an oversight.
	while ( count( $rows ) < 2 ) {
		$rows[] = array();
	}
	?>
	<div class="mvta-users" data-mvta-users>
		<div class="mvta-users__head" aria-hidden="true">
			<span><?php esc_html_e( 'Full name', 'maison-vintique' ); ?></span>
			<span><?php esc_html_e( 'Position', 'maison-vintique' ); ?></span>
			<span><?php esc_html_e( 'Email', 'maison-vintique' ); ?></span>
			<span><?php esc_html_e( 'Telephone', 'maison-vintique' ); ?></span>
			<span><?php esc_html_e( 'May place orders', 'maison-vintique' ); ?></span>
		</div>

		<?php foreach ( $rows as $i => $row ) : ?>
			<div class="mvta-users__row" data-mvta-user-row>
				<label>
					<span class="mvta-users__k"><?php esc_html_e( 'Full name', 'maison-vintique' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[<?php echo (int) $i; ?>][name]"
						value="<?php echo esc_attr( isset( $row['name'] ) ? $row['name'] : '' ); ?>">
				</label>
				<label>
					<span class="mvta-users__k"><?php esc_html_e( 'Position', 'maison-vintique' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[<?php echo (int) $i; ?>][role]"
						value="<?php echo esc_attr( isset( $row['role'] ) ? $row['role'] : '' ); ?>">
				</label>
				<label>
					<span class="mvta-users__k"><?php esc_html_e( 'Email', 'maison-vintique' ); ?></span>
					<input type="email" name="<?php echo esc_attr( $name ); ?>[<?php echo (int) $i; ?>][email]"
						value="<?php echo esc_attr( isset( $row['email'] ) ? $row['email'] : '' ); ?>">
				</label>
				<label>
					<span class="mvta-users__k"><?php esc_html_e( 'Telephone', 'maison-vintique' ); ?></span>
					<input type="tel" name="<?php echo esc_attr( $name ); ?>[<?php echo (int) $i; ?>][phone]"
						value="<?php echo esc_attr( isset( $row['phone'] ) ? $row['phone'] : '' ); ?>">
				</label>
				<label class="mvta-users__yes">
					<span class="mvta-users__k"><?php esc_html_e( 'May place orders', 'maison-vintique' ); ?></span>
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[<?php echo (int) $i; ?>][orders]" value="1"
						<?php checked( ! empty( $row['orders'] ) ); ?>>
				</label>
			</div>
		<?php endforeach; ?>

		<button type="button" class="mvta-users__add" data-mvta-add-user>
			<?php esc_html_e( '+ Add another person', 'maison-vintique' ); ?>
		</button>
	</div>
	<?php
}

/**
 * The whole form.
 */
function mve_application_form() {
	$flash  = mve_application_flash();
	$sent   = isset( $_GET['mvta'] ) ? sanitize_key( wp_unslash( $_GET['mvta'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$schema = mve_application_schema();

	if ( 'sent' === $sent ) {
		?>
		<div class="mvta-done">
			<p class="mvta-done__eyebrow"><?php esc_html_e( 'Application received', 'maison-vintique' ); ?></p>
			<h2 class="mvta-done__title"><?php esc_html_e( 'Thank you.', 'maison-vintique' ); ?></h2>
			<p class="mvta-done__text">
				<?php esc_html_e( 'Your application is with our team. We have emailed you a confirmation, and we will be in touch once it has been reviewed — usually within two working days.', 'maison-vintique' ); ?>
			</p>
		</div>
		<?php
		return;
	}

	// Somebody already signed in does not need to apply again.
	if ( is_user_logged_in() ) {
		$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		?>
		<div class="mvta-done">
			<h2 class="mvta-done__title"><?php esc_html_e( 'You already have an account', 'maison-vintique' ); ?></h2>
			<p class="mvta-done__text"><?php esc_html_e( 'Applying again would create a duplicate. Anything that needs changing can be changed from your account.', 'maison-vintique' ); ?></p>
			<p><a class="btn btn--primary" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Go to my account', 'maison-vintique' ); ?></a></p>
		</div>
		<?php
		return;
	}
	?>

	<?php if ( $flash['errors'] ) : ?>
		<div class="mvta-alert is-error" role="alert" tabindex="-1" id="mvta-alert">
			<strong><?php esc_html_e( 'Please check the highlighted answers.', 'maison-vintique' ); ?></strong>
			<?php if ( ! empty( $flash['errors']['_form'] ) ) : ?>
				<span><?php echo esc_html( $flash['errors']['_form'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form class="mvta-form" method="post" enctype="multipart/form-data"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

		<input type="hidden" name="action" value="mve_trade_application">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'mve_trade_application', 'mvta_nonce' ); ?>

		<?php $n = 0; ?>
		<?php foreach ( $schema as $key => $section ) : ?>
			<?php $n++; ?>
			<section class="mvta-sec" id="mvta-<?php echo esc_attr( $key ); ?>">
				<header class="mvta-sec__head">
					<span class="mvta-sec__n"><?php echo esc_html( str_pad( $n, 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h2 class="mvta-sec__title"><?php echo esc_html( $section['title'] ); ?></h2>
				</header>

				<?php if ( ! empty( $section['intro'] ) ) : ?>
					<p class="mvta-sec__intro"><?php echo esc_html( $section['intro'] ); ?></p>
				<?php endif; ?>

				<div class="mvta-grid">
					<?php foreach ( $section['fields'] as $name => $field ) : ?>
						<?php mve_application_field( $name, $field ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php // Honeypot — real people never fill this in. ?>
		<div class="mvta-hp" aria-hidden="true">
			<label for="mvta-company-url"><?php esc_html_e( 'Leave this field empty', 'maison-vintique' ); ?></label>
			<input type="text" id="mvta-company-url" name="mvta_company_url" tabindex="-1" autocomplete="off">
		</div>

		<div class="mvta-submit">
			<p class="mvta-submit__note">
				<?php esc_html_e( 'Submitting this creates your account in a pending state. Pricing becomes visible once we have approved it.', 'maison-vintique' ); ?>
			</p>
			<button type="submit" class="btn btn--primary mvta-submit__btn">
				<?php esc_html_e( 'Submit application', 'maison-vintique' ); ?>
			</button>
		</div>
	</form>
	<?php
}

/* =========================================================================
 * THE SUBMISSION
 * ====================================================================== */

/**
 * Clean one posted value according to its schema type.
 *
 * @param array $field Schema entry.
 * @param mixed $raw   Raw $_POST value.
 * @return mixed
 */
function mve_application_clean( $field, $raw ) {
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	switch ( $type ) {
		case 'checkbox':
			return $raw ? 1 : 0;

		case 'checkgroup':
			$allowed = array_keys( $field['options'] );
			$chosen  = array_map( 'sanitize_text_field', (array) wp_unslash( $raw ) );
			return array_values( array_intersect( $chosen, $allowed ) );

		case 'select':
		case 'radio':
			$value = sanitize_text_field( (string) wp_unslash( $raw ) );
			// Anything not on the list was not on the form — drop it rather
			// than store whatever was posted.
			return array_key_exists( $value, $field['options'] ) ? $value : '';

		case 'email':
			return sanitize_email( (string) wp_unslash( $raw ) );

		case 'url':
			return esc_url_raw( (string) wp_unslash( $raw ) );

		case 'textarea':
			return sanitize_textarea_field( (string) wp_unslash( $raw ) );

		case 'number':
			return '' === $raw ? '' : (string) absint( $raw );

		case 'users':
			$rows = array();
			foreach ( (array) $raw as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$clean = array(
					'name'   => sanitize_text_field( wp_unslash( isset( $row['name'] ) ? $row['name'] : '' ) ),
					'role'   => sanitize_text_field( wp_unslash( isset( $row['role'] ) ? $row['role'] : '' ) ),
					'email'  => sanitize_email( wp_unslash( isset( $row['email'] ) ? $row['email'] : '' ) ),
					'phone'  => sanitize_text_field( wp_unslash( isset( $row['phone'] ) ? $row['phone'] : '' ) ),
					'orders' => empty( $row['orders'] ) ? 0 : 1,
				);
				// A row with nothing in it is not a person.
				if ( $clean['name'] || $clean['email'] ) {
					$rows[] = $clean;
				}
			}
			return $rows;

		default:
			return sanitize_text_field( (string) wp_unslash( $raw ) );
	}
}

/**
 * Store one uploaded document, privately.
 *
 * Attachments are made private so they never appear in the media grid for
 * anyone but an administrator, and never in search or a sitemap. The file URL
 * is still a URL, so a licence scan should not be treated as a secret — but it
 * is not listed anywhere either.
 *
 * @param string $key Field name / $_FILES key.
 * @return int Attachment ID, or 0.
 */
function mve_application_store_file( $key ) {
	if ( empty( $_FILES[ $key ]['name'] ) || ! empty( $_FILES[ $key ]['error'] ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$allowed = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
	);

	$checked = wp_check_filetype( $_FILES[ $key ]['name'], $allowed );
	if ( ! $checked['type'] ) {
		return 0;
	}

	// 8MB, matching what the form promises.
	if ( isset( $_FILES[ $key ]['size'] ) && $_FILES[ $key ]['size'] > 8 * MB_IN_BYTES ) {
		return 0;
	}

	$id = media_handle_upload(
		$key,
		0,
		array(),
		array(
			'test_form' => false,
			'mimes'     => $allowed,
		)
	);

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	wp_update_post(
		array(
			'ID'          => $id,
			'post_status' => 'private',
		)
	);
	update_post_meta( $id, '_mve_application_document', 1 );

	return (int) $id;
}

/**
 * Handle the application.
 */
function mve_handle_trade_application() {
	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	if ( ! $redirect || 0 !== strpos( $redirect, home_url() ) ) {
		$redirect = mve_trade_application_url();
	}

	if ( ! isset( $_POST['mvta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mvta_nonce'] ) ), 'mve_trade_application' ) ) {
		mve_application_bounce( $redirect, array( '_form' => __( 'Your session expired. Please submit the form again.', 'maison-vintique' ) ), array() );
	}

	// Honeypot — a bot fills every box, so pretend it worked and drop it.
	if ( ! empty( $_POST['mvta_company_url'] ) ) {
		wp_safe_redirect( add_query_arg( 'mvta', 'sent', $redirect ) );
		exit;
	}

	// One application a minute per address is more than anybody needs.
	$throttle = 'mve_app_ip_' . md5( function_exists( 'mve_get_client_ip' ) ? mve_get_client_ip() : '' );
	if ( get_transient( $throttle ) ) {
		mve_application_bounce( $redirect, array( '_form' => __( 'That looked like a duplicate submission. Please wait a moment and try again.', 'maison-vintique' ) ), array() );
	}

	$fields = mve_application_fields();
	$values = array();
	$errors = array();

	foreach ( $fields as $name => $field ) {
		if ( 'file' === $field['type'] || 'note' === $field['type'] ) {
			continue;
		}

		$raw            = isset( $_POST[ $name ] ) ? $_POST[ $name ] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned by mve_application_clean().
		$values[ $name ] = mve_application_clean( $field, $raw );

		if ( ! empty( $field['required'] ) && ( '' === $values[ $name ] || array() === $values[ $name ] || 0 === $values[ $name ] ) ) {
			$errors[ $name ] = ( 'checkbox' === $field['type'] )
				? __( 'This has to be agreed before we can accept the application.', 'maison-vintique' )
				: __( 'This is required.', 'maison-vintique' );
			continue;
		}

		if ( 'email' === $field['type'] && $values[ $name ] && ! is_email( $values[ $name ] ) ) {
			$errors[ $name ] = __( 'That does not look like an email address.', 'maison-vintique' );
		}
	}

	// The signature has to match the name typed above it, or it signs nothing.
	if ( empty( $errors['signature'] ) && ! empty( $values['signature'] ) && ! empty( $values['signatory_name'] ) ) {
		$a = strtolower( trim( preg_replace( '/\s+/', ' ', $values['signature'] ) ) );
		$b = strtolower( trim( preg_replace( '/\s+/', ' ', $values['signatory_name'] ) ) );
		if ( $a !== $b ) {
			$errors['signature'] = __( 'Please type the same name as above to sign.', 'maison-vintique' );
		}
	}

	// The sign-in address.
	$email = isset( $values['primary_email'] ) ? $values['primary_email'] : '';
	if ( $email && email_exists( $email ) ) {
		$errors['primary_email'] = __( 'There is already an account on this address. Please sign in instead, or use another address.', 'maison-vintique' );
	}

	if ( $errors ) {
		mve_application_bounce( $redirect, $errors, $values );
	}

	set_transient( $throttle, 1, MINUTE_IN_SECONDS );

	// ---- create the account -------------------------------------------
	$business = ! empty( $values['trading_name'] ) ? $values['trading_name'] : $values['legal_name'];
	$username = function_exists( 'wc_create_new_customer_username' )
		? wc_create_new_customer_username( $email, array( 'first_name' => $values['signatory_name'] ) )
		: sanitize_user( current( explode( '@', $email ) ), true );

	if ( function_exists( 'wc_create_new_customer' ) ) {
		/*
		 * The application does not ask for a password — the Word form doesn't
		 * have one and a nine-section form is long enough. So WooCommerce has to
		 * generate one and send it, which it only does when "generate password"
		 * is switched on in its settings. Forced on for this one call, and
		 * removed immediately, so the store's own setting is untouched
		 * everywhere else.
		 */
		$generate = function () {
			return 'yes';
		};
		add_filter( 'option_woocommerce_registration_generate_password', $generate, 99 );

		$user_id = wc_create_new_customer( $email, $username, '', array( 'display_name' => $business ) );

		remove_filter( 'option_woocommerce_registration_generate_password', $generate, 99 );
	} else {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 20 ),
				'display_name' => $business,
				'role'         => 'customer',
			)
		);
	}

	if ( is_wp_error( $user_id ) ) {
		mve_application_bounce(
			$redirect,
			array( '_form' => $user_id->get_error_message() ),
			$values
		);
	}

	// ---- store the answers --------------------------------------------
	foreach ( $values as $name => $value ) {
		update_user_meta( $user_id, 'mve_app_' . $name, $value );
	}

	// Documents, after the user exists so they can be attributed to them.
	$documents = array();
	foreach ( $fields as $name => $field ) {
		if ( 'file' !== $field['type'] ) {
			continue;
		}
		$attachment = mve_application_store_file( $name );
		if ( $attachment ) {
			wp_update_post(
				array(
					'ID'          => $attachment,
					'post_author' => $user_id,
				)
			);
			$documents[ $name ] = $attachment;
		}
	}
	update_user_meta( $user_id, 'mve_app_documents', $documents );

	// The fields the rest of the shop already reads.
	update_user_meta( $user_id, 'mve_business_name', $business );
	update_user_meta( $user_id, 'billing_company', $business );
	if ( ! empty( $values['main_phone'] ) ) {
		update_user_meta( $user_id, 'mve_phone', $values['main_phone'] );
		update_user_meta( $user_id, 'billing_phone', $values['main_phone'] );
	}
	if ( ! empty( $values['primary_name'] ) ) {
		$parts = explode( ' ', $values['primary_name'], 2 );
		update_user_meta( $user_id, 'billing_first_name', $parts[0] );
		update_user_meta( $user_id, 'billing_last_name', isset( $parts[1] ) ? $parts[1] : '' );
	}

	update_user_meta( $user_id, 'mve_app_submitted', current_time( 'mysql' ) );
	update_user_meta( $user_id, MVE_TRADE_STATUS_KEY, 'pending' );

	// The applicant's "application received" email is sent by
	// mve_trade_application_submitted(), which fires on user_register. This is
	// the one that goes to the shop.
	mve_notify_shop_of_application( $user_id, $values );

	/**
	 * Fires after a full trade application is stored. Hook this to push it
	 * into a CRM or an accounts system.
	 *
	 * @param int   $user_id Applicant.
	 * @param array $values  Every answer.
	 */
	do_action( 'mve_trade_application_received', $user_id, $values );

	wp_safe_redirect( add_query_arg( 'mvta', 'sent', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_mve_trade_application', 'mve_handle_trade_application' );
add_action( 'admin_post_mve_trade_application', 'mve_handle_trade_application' );

/**
 * Send them back to the form with their answers and the problems.
 *
 * @param string $redirect Page URL.
 * @param array  $errors   field => message.
 * @param array  $values   What they typed.
 */
function mve_application_bounce( $redirect, $errors, $values ) {
	$token = wp_generate_password( 16, false, false );
	set_transient(
		'mve_app_' . $token,
		array(
			'errors' => $errors,
			'values' => $values,
		),
		15 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect( add_query_arg( array( 'mvta' => 'error', 'mvta_ref' => $token ), $redirect ) . '#mvta-alert' );
	exit;
}

/**
 * Tell the shop an application has landed.
 *
 * Plain text on purpose: this is an internal notification, and it goes
 * straight into whatever the office uses, not into a marketing inbox.
 *
 * @param int   $user_id Applicant.
 * @param array $values  Answers.
 */
function mve_notify_shop_of_application( $user_id, $values ) {
	$to = apply_filters( 'mve_application_notification_email', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) {
		return;
	}

	$business = ! empty( $values['trading_name'] ) ? $values['trading_name'] : $values['legal_name'];

	$subject = sprintf(
		/* translators: 1: site name, 2: business name */
		__( '[%1$s] Trade account application — %2$s', 'maison-vintique' ),
		get_bloginfo( 'name' ),
		$business
	);

	$lines = array(
		__( 'A new trade account application has been submitted.', 'maison-vintique' ),
		'',
		sprintf( '%s: %s', __( 'Business', 'maison-vintique' ), $business ),
		sprintf( '%s: %s', __( 'Contact', 'maison-vintique' ), isset( $values['primary_name'] ) ? $values['primary_name'] : '' ),
		sprintf( '%s: %s', __( 'Email', 'maison-vintique' ), isset( $values['primary_email'] ) ? $values['primary_email'] : '' ),
		sprintf( '%s: %s', __( 'Telephone', 'maison-vintique' ), isset( $values['main_phone'] ) ? $values['main_phone'] : '' ),
		sprintf( '%s: %s', __( 'AWRS', 'maison-vintique' ), isset( $values['awrs_urn'] ) && $values['awrs_urn'] ? $values['awrs_urn'] : __( 'not supplied', 'maison-vintique' ) ),
		'',
		__( 'Review it here:', 'maison-vintique' ),
		admin_url( 'user-edit.php?user_id=' . $user_id ),
	);

	wp_mail( $to, $subject, implode( "\n", $lines ), array( 'Content-Type: text/plain; charset=UTF-8' ) );
}

/* =========================================================================
 * ADMIN — READ THE APPLICATION
 * ---------------------------------------------------------------------
 * On the user's profile screen, directly under the Trade account panel from
 * inc/trade-accounts.php, so approving and reading the application are the
 * same screen rather than two.
 * ====================================================================== */

/**
 * Turn a stored answer into something readable.
 *
 * @param array $field Schema entry.
 * @param mixed $value Stored value.
 * @return string HTML.
 */
function mve_application_display_value( $field, $value ) {
	if ( '' === $value || null === $value || array() === $value ) {
		return '<span style="color:#8c8c8c">' . esc_html__( '—', 'maison-vintique' ) . '</span>';
	}

	switch ( $field['type'] ) {
		case 'checkbox':
			return $value
				? '<span style="color:#2e7d5b">' . esc_html__( 'Yes', 'maison-vintique' ) . '</span>'
				: '<span style="color:#9d3b3b">' . esc_html__( 'No', 'maison-vintique' ) . '</span>';

		case 'select':
		case 'radio':
			return esc_html( isset( $field['options'][ $value ] ) ? $field['options'][ $value ] : $value );

		case 'checkgroup':
			$labels = array();
			foreach ( (array) $value as $key ) {
				$labels[] = isset( $field['options'][ $key ] ) ? $field['options'][ $key ] : $key;
			}
			return esc_html( implode( ', ', $labels ) );

		case 'email':
			return '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';

		case 'url':
			return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';

		case 'textarea':
			return nl2br( esc_html( $value ) );

		case 'users':
			$rows = '';
			foreach ( (array) $value as $person ) {
				$rows .= '<tr><td>' . esc_html( $person['name'] ) . '</td><td>' . esc_html( $person['role'] ) . '</td><td>'
					. esc_html( $person['email'] ) . '</td><td>' . esc_html( $person['phone'] ) . '</td><td>'
					. ( empty( $person['orders'] ) ? '—' : esc_html__( 'Yes', 'maison-vintique' ) ) . '</td></tr>';
			}
			if ( ! $rows ) {
				return '<span style="color:#8c8c8c">' . esc_html__( 'None listed', 'maison-vintique' ) . '</span>';
			}
			return '<table class="widefat striped" style="max-width:760px"><thead><tr>'
				. '<th>' . esc_html__( 'Name', 'maison-vintique' ) . '</th>'
				. '<th>' . esc_html__( 'Position', 'maison-vintique' ) . '</th>'
				. '<th>' . esc_html__( 'Email', 'maison-vintique' ) . '</th>'
				. '<th>' . esc_html__( 'Telephone', 'maison-vintique' ) . '</th>'
				. '<th>' . esc_html__( 'Orders', 'maison-vintique' ) . '</th>'
				. '</tr></thead><tbody>' . $rows . '</tbody></table>';

		default:
			return esc_html( $value );
	}
}

/**
 * The application, on the user's profile.
 *
 * @param WP_User $user User.
 */
function mve_user_application_panel( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$submitted = get_user_meta( $user->ID, 'mve_app_submitted', true );
	if ( ! $submitted ) {
		return; // registered the short way; nothing to show
	}

	$documents = (array) get_user_meta( $user->ID, 'mve_app_documents', true );
	$fields    = mve_application_fields();
	?>
	<h2><?php esc_html_e( 'Trade account application', 'maison-vintique' ); ?></h2>
	<p class="description" style="margin:-6px 0 12px">
		<?php
		printf(
			/* translators: %s: date and time */
			esc_html__( 'Submitted %s', 'maison-vintique' ),
			esc_html( $submitted )
		);
		?>
	</p>

	<?php foreach ( mve_application_schema() as $section ) : ?>
		<?php
		// Skip a section nobody filled in — an empty grid is just noise.
		$has_answer = false;
		foreach ( $section['fields'] as $name => $field ) {
			$stored = get_user_meta( $user->ID, 'mve_app_' . $name, true );
			if ( '' !== $stored && array() !== $stored && null !== $stored ) {
				$has_answer = true;
				break;
			}
			if ( 'file' === $fields[ $name ]['type'] && ! empty( $documents[ $name ] ) ) {
				$has_answer = true;
				break;
			}
		}
		if ( ! $has_answer ) {
			continue;
		}
		?>
		<h3 style="margin:22px 0 4px"><?php echo esc_html( $section['title'] ); ?></h3>
		<table class="form-table" role="presentation">
			<?php foreach ( $section['fields'] as $name => $field ) : ?>
				<?php
				$field = $fields[ $name ];

				if ( 'file' === $field['type'] ) {
					$attachment = isset( $documents[ $name ] ) ? (int) $documents[ $name ] : 0;
					if ( ! $attachment ) {
						continue;
					}
					?>
					<tr>
						<th style="width:280px"><?php echo esc_html( $field['label'] ); ?></th>
						<td>
							<a href="<?php echo esc_url( wp_get_attachment_url( $attachment ) ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( get_the_title( $attachment ) ); ?>
							</a>
						</td>
					</tr>
					<?php
					continue;
				}

				$stored = get_user_meta( $user->ID, 'mve_app_' . $name, true );
				if ( '' === $stored || array() === $stored || null === $stored ) {
					continue;
				}
				?>
				<tr>
					<th style="width:280px"><?php echo esc_html( $field['label'] ); ?></th>
					<td><?php echo wp_kses_post( mve_application_display_value( $field, $stored ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endforeach; ?>
	<?php
}
add_action( 'show_user_profile', 'mve_user_application_panel', 20 );
add_action( 'edit_user_profile', 'mve_user_application_panel', 20 );
