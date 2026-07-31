<?php
/**
 * Trade account registration.
 *
 * The "Apply for a trade account" panel on the logged-out /my-account/ page
 * renders a real WooCommerce registration form (woocommerce/myaccount/form-login.php).
 * This file makes that work end to end:
 *
 *   1. Reports my-account registration as enabled, so WooCommerce accepts the
 *      submission whatever the store setting says.
 *   2. Validates and stores the two extra trade fields (business name, phone).
 *   3. Shows them on the user's profile in wp-admin.
 *
 * WooCommerce itself still does the real work — validation, account creation,
 * the new-account email and signing the customer in afterwards.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Treat my-account registration as enabled on the front end.
 *
 * The form is on the page, so the submission has to be accepted. This option
 * governs ONLY the my-account registration form — the checkout's own
 * "create an account" checkbox is a separate option
 * (woocommerce_enable_signup_and_login_from_checkout) and is untouched.
 *
 * Left alone in wp-admin so the real setting still shows correctly there.
 */
function mve_enable_account_registration( $value ) {
	if ( is_admin() ) {
		return $value;
	}
	return apply_filters( 'mve_show_registration_form', true ) ? 'yes' : $value;
}
add_filter( 'option_woocommerce_enable_myaccount_registration', 'mve_enable_account_registration' );

/**
 * Require a business name — this is a trade store, not a consumer shop.
 */
function mve_validate_registration( $errors, $username, $email ) {
	if ( empty( $_POST['mve_business'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the nonce before this fires.
		$errors->add( 'mve_business_required', __( 'Please tell us your business name.', 'maison-vintique-elementor' ) );
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'mve_validate_registration', 10, 3 );

/**
 * Store the trade fields against the new account.
 *
 * Business name also goes into billing_company so it pre-fills at checkout and
 * shows on orders; the WooCommerce "customer" fields are the ones the rest of
 * the shop already reads.
 */
function mve_save_registration_fields( $customer_id ) {
	// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the nonce before this fires.
	if ( ! empty( $_POST['mve_business'] ) ) {
		$business = sanitize_text_field( wp_unslash( $_POST['mve_business'] ) );
		update_user_meta( $customer_id, 'mve_business_name', $business );
		update_user_meta( $customer_id, 'billing_company', $business );
	}

	if ( ! empty( $_POST['mve_phone'] ) ) {
		$phone = sanitize_text_field( wp_unslash( $_POST['mve_phone'] ) );
		update_user_meta( $customer_id, 'mve_phone', $phone );
		update_user_meta( $customer_id, 'billing_phone', $phone );
	}
	// phpcs:enable WordPress.Security.NonceVerification
}
add_action( 'woocommerce_created_customer', 'mve_save_registration_fields' );

/**
 * Surface the trade fields on the user profile screen.
 */
function mve_registration_profile_fields( $user ) {
	?>
	<h2><?php esc_html_e( 'Trade account', 'maison-vintique-elementor' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="mve_business_name"><?php esc_html_e( 'Business name', 'maison-vintique-elementor' ); ?></label></th>
			<td>
				<input type="text" name="mve_business_name" id="mve_business_name" class="regular-text"
					value="<?php echo esc_attr( get_user_meta( $user->ID, 'mve_business_name', true ) ); ?>">
			</td>
		</tr>
		<tr>
			<th><label for="mve_phone"><?php esc_html_e( 'Telephone', 'maison-vintique-elementor' ); ?></label></th>
			<td>
				<input type="text" name="mve_phone" id="mve_phone" class="regular-text"
					value="<?php echo esc_attr( get_user_meta( $user->ID, 'mve_phone', true ) ); ?>">
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'mve_registration_profile_fields' );
add_action( 'edit_user_profile', 'mve_registration_profile_fields' );

/**
 * Save those profile edits.
 */
function mve_save_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- WordPress verifies the profile nonce before this fires.
	foreach ( array( 'mve_business_name', 'mve_phone' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification
}
add_action( 'personal_options_update', 'mve_save_profile_fields' );
add_action( 'edit_user_profile_update', 'mve_save_profile_fields' );
