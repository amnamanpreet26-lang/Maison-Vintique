<?php
/**
 * inc/emails/class-mve-emails.php
 *
 * Every custom email, one small class each. They only declare who they are and
 * what they say — MVE_Email_Base does the rest.
 *
 * Grouped as the brief listed them:
 *   Master 1 Transactional order — Shipped (WooCommerce has the other six)
 *   Master 2 Trade account       — Received, Approved, More information, Welcome
 *   Master 3 Payment             — Invoice, Payment required, Payment reminder
 *   Master 4 Account             — Password changed, Account details
 *                                  (Password reset is WordPress's own; see
 *                                   inc/emails.php, which re-skins it)
 *   Master 5 Product updates     — Product available
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MVE_Email_Base' ) ) {
	return;
}

/* =========================================================================
 * MASTER 1 — TRANSACTIONAL ORDER
 * ====================================================================== */

/** Order shipped. WooCommerce has no "shipped" status, so inc/emails.php adds one. */
class MVE_Email_Order_Shipped extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_order_shipped';
		$this->title          = __( 'Order shipped', 'maison-vintique' );
		$this->description    = __( 'Sent to the customer when an order is marked Shipped.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your order {order_number} is on its way', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Your wine is on its way', 'maison-vintique' );
	}
	protected function body_lines() {
		$lines = array(
			__( 'Your order has left our bonded warehouse and is with the carrier.', 'maison-vintique' ),
		);
		$tracking = $this->object ? $this->object->get_meta( '_mve_tracking_number' ) : '';
		$carrier  = $this->object ? $this->object->get_meta( '_mve_tracking_carrier' ) : '';
		if ( $tracking ) {
			$lines[] = sprintf(
				__( 'Carrier: %1$s &nbsp;·&nbsp; Consignment: %2$s', 'maison-vintique' ),
				esc_html( $carrier ? $carrier : __( 'Our courier', 'maison-vintique' ) ),
				'<strong>' . esc_html( $tracking ) . '</strong>'
			);
		}
		$lines[] = __( 'Someone over 18 must be present to sign for the delivery.', 'maison-vintique' );
		return $lines;
	}
	protected function cta() {
		$url = $this->object ? $this->object->get_meta( '_mve_tracking_url' ) : '';
		return $url ? array( 'label' => __( 'Track this delivery', 'maison-vintique' ), 'url' => $url ) : array();
	}
}

/* =========================================================================
 * MASTER 2 — TRADE ACCOUNT
 * ====================================================================== */

abstract class MVE_Email_Trade_Base extends MVE_Email_Base {
	protected function show_order_details() {
		return false;   // these are about the account, not an order
	}
	protected function business_name() {
		return $this->user ? get_user_meta( $this->user->ID, 'mve_business_name', true ) : '';
	}
}

/** Sent the moment an application is submitted. */
class MVE_Email_Trade_Application_Received extends MVE_Email_Trade_Base {
	public function __construct() {
		$this->id             = 'mve_trade_application_received';
		$this->title          = __( 'Trade — application received', 'maison-vintique' );
		$this->description    = __( 'Sent when someone applies for a trade account.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'We have your trade application — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Application received', 'maison-vintique' );
	}
	protected function body_lines() {
		$business = $this->business_name();
		return array(
			$business
				? sprintf( __( 'Thank you for applying for a trade account for <strong>%s</strong>.', 'maison-vintique' ), esc_html( $business ) )
				: __( 'Thank you for applying for a trade account.', 'maison-vintique' ),
			__( 'We review every application by hand, usually within two working days. We may come back to you for a VAT number or a trade reference before we can approve it.', 'maison-vintique' ),
			__( 'You will not be able to see pricing or place orders until the account is approved.', 'maison-vintique' ),
		);
	}
	protected function note() {
		return __( 'Nothing further is needed from you at this stage — we will be in touch.', 'maison-vintique' );
	}
}

/** Approved — the account can now trade. */
class MVE_Email_Trade_Approved extends MVE_Email_Trade_Base {
	public function __construct() {
		$this->id             = 'mve_trade_approved';
		$this->title          = __( 'Trade — approved', 'maison-vintique' );
		$this->description    = __( 'Sent when a trade account is approved.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your trade account is open — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Your trade account is open', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			__( 'Your trade account has been approved. Sign in and the full portfolio opens up: wholesale pricing, live availability, technical sheets and downloadable invoices.', 'maison-vintique' ),
			__( 'Orders are placed by proforma. Your account manager will confirm terms and delivery windows on your first order.', 'maison-vintique' ),
		);
	}
	protected function cta() {
		return array(
			'label' => __( 'Sign in to your account', 'maison-vintique' ),
			'url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
		);
	}
}

/** Declined, or we need more from them before we can decide. */
class MVE_Email_Trade_More_Info extends MVE_Email_Trade_Base {
	public function __construct() {
		$this->id             = 'mve_trade_more_info';
		$this->title          = __( 'Trade — more information / declined', 'maison-vintique' );
		$this->description    = __( 'Sent when an application needs more information, or is declined. The note you type is included.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'About your trade application — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'About your application', 'maison-vintique' );
	}
	protected function body_lines() {
		$declined = ! empty( $this->extra['declined'] );
		$lines    = array();

		$lines[] = $declined
			? __( 'Thank you for your interest. We are not able to open a trade account for you at this time.', 'maison-vintique' )
			: __( 'Thank you for applying. Before we can approve your account we need a little more from you.', 'maison-vintique' );

		if ( ! $declined ) {
			$lines[] = __( 'Reply to this email with the details below and we will pick the application straight back up.', 'maison-vintique' );
		}
		return $lines;
	}
	protected function note() {
		// Whatever the admin typed when they chose this action.
		return ! empty( $this->extra['message'] ) ? nl2br( esc_html( $this->extra['message'] ) ) : '';
	}
}

/** Welcome, sent after the first successful sign-in. */
class MVE_Email_Trade_Welcome extends MVE_Email_Trade_Base {
	public function __construct() {
		$this->id             = 'mve_trade_welcome';
		$this->title          = __( 'Trade — welcome', 'maison-vintique' );
		$this->description    = __( 'Sent once, the first time an approved account signs in.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Welcome to {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Welcome', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			__( 'A few things worth knowing now that you are trading with us:', 'maison-vintique' ),
			__( '<strong>Pricing</strong> — wholesale prices show on every wine once you are signed in.', 'maison-vintique' ),
			__( '<strong>Documents</strong> — technical sheets sit on each wine, and every invoice is in your account under Invoices.', 'maison-vintique' ),
			__( '<strong>Allocations</strong> — limited parcels are offered to accounts first. Tell your account manager what you are looking for.', 'maison-vintique' ),
		);
	}
	protected function cta() {
		return array(
			'label' => __( 'Browse the portfolio', 'maison-vintique' ),
			'url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		);
	}
}

/* =========================================================================
 * MASTER 3 — PAYMENT
 * ====================================================================== */

/** Invoice, with the PDF-printable invoice link. */
class MVE_Email_Invoice extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_invoice';
		$this->title          = __( 'Payment — invoice', 'maison-vintique' );
		$this->description    = __( 'Sends the invoice for an order. Triggered from the order screen.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Invoice for order {order_number} — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Your invoice', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			sprintf( __( 'Please find the invoice for order %s below.', 'maison-vintique' ), '<strong>' . esc_html( $this->placeholders['{order_number}'] ) . '</strong>' ),
			__( 'A printable copy is always available in your account under Invoices.', 'maison-vintique' ),
		);
	}
	protected function cta() {
		if ( ! $this->object instanceof WC_Order ) {
			return array();
		}
		return array(
			'label' => __( 'View invoice', 'maison-vintique' ),
			'url'   => function_exists( 'mve_invoice_url' ) ? mve_invoice_url( $this->object->get_id() ) : $this->object->get_view_order_url(),
		);
	}
}

/** Payment required — order placed, nothing received yet. */
class MVE_Email_Payment_Required extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_payment_required';
		$this->title          = __( 'Payment — required', 'maison-vintique' );
		$this->description    = __( 'Asks the customer to settle an order that is awaiting payment.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Payment required for order {order_number}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Payment required', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			sprintf( __( 'Order %s is confirmed and reserved, and is waiting on payment before we release it from bond.', 'maison-vintique' ), '<strong>' . esc_html( $this->placeholders['{order_number}'] ) . '</strong>' ),
		);
	}
	protected function cta() {
		return $this->object instanceof WC_Order
			? array( 'label' => __( 'Pay for this order', 'maison-vintique' ), 'url' => $this->object->get_checkout_payment_url() )
			: array();
	}
}

/** Payment reminder — the polite chase. */
class MVE_Email_Payment_Reminder extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_payment_reminder';
		$this->title          = __( 'Payment — reminder', 'maison-vintique' );
		$this->description    = __( 'A reminder for an order still unpaid. Send from the order screen, or schedule it.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Reminder: order {order_number} is still outstanding', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'A gentle reminder', 'maison-vintique' );
	}
	protected function body_lines() {
		$days  = ! empty( $this->extra['days'] ) ? (int) $this->extra['days'] : 0;
		$lines = array();

		$lines[] = $days
			? sprintf(
				/* translators: 1: order number, 2: number of days */
				__( 'Order %1$s has been outstanding for %2$d days. If it has already been paid, please ignore this.', 'maison-vintique' ),
				'<strong>' . esc_html( $this->placeholders['{order_number}'] ) . '</strong>',
				$days
			)
			: sprintf( __( 'Order %s is still showing as unpaid on our side. If it has already been paid, please ignore this.', 'maison-vintique' ), '<strong>' . esc_html( $this->placeholders['{order_number}'] ) . '</strong>' );

		$lines[] = __( 'If there is a query on the invoice, reply to this email and we will look into it.', 'maison-vintique' );
		return $lines;
	}
	protected function cta() {
		return $this->object instanceof WC_Order
			? array( 'label' => __( 'Settle this order', 'maison-vintique' ), 'url' => $this->object->get_checkout_payment_url() )
			: array();
	}
}

/* =========================================================================
 * MASTER 4 — ACCOUNT
 * ====================================================================== */

/** Password changed — a security notice, not a reset link. */
class MVE_Email_Password_Changed extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_password_changed';
		$this->title          = __( 'Account — password changed', 'maison-vintique' );
		$this->description    = __( 'Security notice sent whenever an account password changes.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your password was changed — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Your password was changed', 'maison-vintique' );
	}
	protected function show_order_details() {
		return false;
	}
	protected function body_lines() {
		return array(
			__( 'The password on your trade account was just changed.', 'maison-vintique' ),
		);
	}
	protected function note() {
		return __( '<strong>If this was not you</strong>, reply to this email immediately and we will lock the account while we look into it.', 'maison-vintique' );
	}
}

/** Account details changed. */
class MVE_Email_Account_Details extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_account_details';
		$this->title          = __( 'Account — details updated', 'maison-vintique' );
		$this->description    = __( 'Sent when a customer changes their account details or addresses.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your account details were updated — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Account details updated', 'maison-vintique' );
	}
	protected function show_order_details() {
		return false;
	}
	protected function body_lines() {
		$changed = ! empty( $this->extra['changed'] ) ? (array) $this->extra['changed'] : array();
		$lines   = array(
			__( 'The details on your trade account have been updated.', 'maison-vintique' ),
		);
		if ( $changed ) {
			$lines[] = __( 'What changed:', 'maison-vintique' ) . ' <strong>' . esc_html( implode( ', ', $changed ) ) . '</strong>';
		}
		return $lines;
	}
	protected function cta() {
		return array(
			'label' => __( 'Review your details', 'maison-vintique' ),
			'url'   => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-account' ) : home_url( '/my-account/' ),
		);
	}
	protected function note() {
		return __( 'If you did not make this change, reply to this email and let us know.', 'maison-vintique' );
	}
}

/* =========================================================================
 * MASTER 5 — PRODUCT / TRADE UPDATES
 * ====================================================================== */

/** Back in stock, to whoever asked to be told. */
class MVE_Email_Product_Available extends MVE_Email_Base {
	public function __construct() {
		$this->id             = 'mve_product_available';
		$this->title          = __( 'Product — back in stock', 'maison-vintique' );
		$this->description    = __( 'Sent to accounts who asked to be notified when a wine comes back into stock.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Back in stock: {product_name}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Back in stock', 'maison-vintique' );
	}
	protected function show_order_details() {
		return false;
	}
	protected function body_lines() {
		$name = ! empty( $this->extra['product_name'] ) ? $this->extra['product_name'] : __( 'A wine you were watching', 'maison-vintique' );
		$this->placeholders['{product_name}'] = $name;
		return array(
			sprintf( __( '<strong>%s</strong> is available again.', 'maison-vintique' ), esc_html( $name ) ),
			__( 'Parcels like this tend to move quickly, so it is worth ordering sooner rather than later.', 'maison-vintique' ),
		);
	}
	protected function cta() {
		return ! empty( $this->extra['product_url'] )
			? array( 'label' => __( 'View this wine', 'maison-vintique' ), 'url' => $this->extra['product_url'] )
			: array();
	}
}

/* =========================================================================
 * TO THE SHOP
 * ---------------------------------------------------------------------
 * Everything above goes to a customer. These three go to Maison Vintique, so
 * the office is told when something needs attention rather than having to keep
 * an eye on wp-admin.
 *
 * They all set $to_customer = false BEFORE parent::__construct(), which is what
 * gives them a Recipient box on their WooCommerce settings screen. Left empty
 * there they use the one address at the top of WooCommerce → Settings → Emails.
 * ====================================================================== */

/** A trade application has landed and needs reviewing. */
class MVE_Email_Trade_Application_Admin extends MVE_Email_Base {

	protected $to_customer = false;

	public function __construct() {
		$this->id             = 'mve_trade_application_admin';
		$this->title          = __( 'Trade — new application (to the shop)', 'maison-vintique' );
		$this->description    = __( 'Sent to you when someone submits the trade account application.', 'maison-vintique' );
		$this->customer_email = false;
		$this->placeholders   = array( '{business_name}' => '' );
		parent::__construct();
	}

	/**
	 * The subject line names the business, and the subject is built before the
	 * body, so the placeholder has to be filled in here.
	 */
	protected function prepare() {
		$business = $this->answer( 'trading_name' );
		$business = $business ? $business : $this->answer( 'legal_name' );
		$business = $business ? $business : ( $this->user ? $this->user->display_name : '' );

		$this->placeholders['{business_name}'] = $business;
	}

	public function get_default_subject() {
		return __( '[{site_title}] New trade application — {business_name}', 'maison-vintique' );
	}

	public function get_default_heading() {
		return __( 'New trade application', 'maison-vintique' );
	}

	protected function show_order_details() {
		return false;
	}

	/**
	 * One answer, for the summary. Reads what the application stored.
	 *
	 * @param string $key Field name.
	 * @return string
	 */
	protected function answer( $key ) {
		if ( ! $this->user ) {
			return '';
		}
		$value = get_user_meta( $this->user->ID, 'mve_app_' . $key, true );
		return is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value;
	}

	protected function body_lines() {
		$business = $this->placeholders['{business_name}'];

		$lines = array(
			sprintf(
				/* translators: %s: business name */
				__( '<strong>%s</strong> has applied for a trade account.', 'maison-vintique' ),
				esc_html( $business )
			),
		);

		// The handful of answers that decide whether it can be approved at all.
		$summary = array(
			__( 'Contact', 'maison-vintique' )      => $this->answer( 'primary_name' ),
			__( 'Email', 'maison-vintique' )        => $this->answer( 'primary_email' ),
			__( 'Telephone', 'maison-vintique' )    => $this->answer( 'main_phone' ),
			__( 'Company no.', 'maison-vintique' )  => $this->answer( 'company_number' ),
			__( 'VAT no.', 'maison-vintique' )      => $this->answer( 'vat_number' ),
			__( 'Premises licence', 'maison-vintique' ) => $this->answer( 'licence_number' ),
			__( 'AWRS', 'maison-vintique' )         => $this->answer( 'awrs_urn' ),
		);

		$rows = '';
		foreach ( $summary as $label => $value ) {
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			$rows .= '<tr><td style="padding:4px 16px 4px 0;color:#6f675e;white-space:nowrap">' . esc_html( $label )
				. '</td><td style="padding:4px 0"><strong>' . esc_html( $value ) . '</strong></td></tr>';
		}
		if ( $rows ) {
			$lines[] = '<table cellpadding="0" cellspacing="0" style="width:100%">' . $rows . '</table>';
		}

		$lines[] = __( 'The full application — licensing, AWRS, authorised users, delivery, payment and the signed declarations — is on their profile, along with any documents they uploaded.', 'maison-vintique' );

		return $lines;
	}

	protected function cta() {
		return $this->user
			? array(
				'label' => __( 'Review this application', 'maison-vintique' ),
				'url'   => admin_url( 'user-edit.php?user_id=' . $this->user->ID ),
			)
			: array();
	}

	protected function note() {
		return __( 'Approving or declining them on that screen is what emails the applicant — they are not told anything until you do.', 'maison-vintique' );
	}
}

/**
 * An order request has been placed but no gateway has moved it on.
 *
 * WooCommerce only emails once an order leaves "pending", so a proforma flow
 * where the customer submits a request and waits would otherwise send nobody
 * anything. inc/emails.php fires these two, and only in that case, so a normal
 * paid order still gets WooCommerce's own emails and not a duplicate.
 */
class MVE_Email_Order_Request extends MVE_Email_Base {

	public function __construct() {
		$this->id             = 'mve_order_request';
		$this->title          = __( 'Order request received (to the customer)', 'maison-vintique' );
		$this->description    = __( 'Sent when an order is submitted for approval rather than paid for immediately.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}

	public function get_default_subject() {
		return __( 'We have your order request {order_number}', 'maison-vintique' );
	}

	public function get_default_heading() {
		return __( 'Order request received', 'maison-vintique' );
	}

	protected function body_lines() {
		return array(
			__( 'Thank you — your order request is with us. Nothing has been charged.', 'maison-vintique' ),
			__( 'We will confirm stock, pricing and a delivery window, then send a proforma invoice. Payment is taken only after you have approved that.', 'maison-vintique' ),
		);
	}

	protected function cta() {
		return $this->object
			? array(
				'label' => __( 'View this order', 'maison-vintique' ),
				'url'   => $this->object->get_view_order_url(),
			)
			: array();
	}
}

/** The same event, to the shop. */
class MVE_Email_Order_Request_Admin extends MVE_Email_Base {

	protected $to_customer = false;

	public function __construct() {
		$this->id             = 'mve_order_request_admin';
		$this->title          = __( 'Order request received (to the shop)', 'maison-vintique' );
		$this->description    = __( 'Sent to you when an order is submitted for approval rather than paid for immediately.', 'maison-vintique' );
		$this->customer_email = false;
		parent::__construct();
	}

	public function get_default_subject() {
		return __( '[{site_title}] New order request {order_number}', 'maison-vintique' );
	}

	public function get_default_heading() {
		return __( 'New order request', 'maison-vintique' );
	}

	protected function body_lines() {
		$lines = array(
			__( 'An order request has been submitted and is waiting on you. It has not been paid for.', 'maison-vintique' ),
		);
		if ( $this->object ) {
			$lines[] = sprintf(
				/* translators: 1: customer name, 2: company */
				__( 'From: <strong>%1$s</strong>%2$s', 'maison-vintique' ),
				esc_html( trim( $this->object->get_billing_first_name() . ' ' . $this->object->get_billing_last_name() ) ),
				$this->object->get_billing_company() ? ' — ' . esc_html( $this->object->get_billing_company() ) : ''
			);
		}
		return $lines;
	}

	protected function cta() {
		return $this->object
			? array(
				'label' => __( 'Open this order', 'maison-vintique' ),
				'url'   => $this->object->get_edit_order_url(),
			)
			: array();
	}
}

/* =========================================================================
 * STAGE ONE — THE SHORT TRADE ENQUIRY
 * ---------------------------------------------------------------------
 * These hang off a Trade Enquiry rather than a user or an order — nobody has
 * an account yet, which is the whole point of the stage. The enquiry ID is
 * passed in $extra, and prepare() reads the answers off it.
 * ====================================================================== */

abstract class MVE_Email_Enquiry_Base extends MVE_Email_Base {

	/** @var int The enquiry this email is about. */
	protected $enquiry = 0;

	protected function show_order_details() {
		return false;
	}

	/**
	 * One answer from the enquiry.
	 *
	 * @param string $key Field key, without the _mve_ prefix.
	 * @return string
	 */
	protected function answer( $key ) {
		return $this->enquiry ? (string) get_post_meta( $this->enquiry, '_mve_' . $key, true ) : '';
	}

	/**
	 * Pull the enquiry in, and address the email at the person who sent it.
	 */
	protected function prepare() {
		$this->enquiry = ! empty( $this->extra['enquiry'] ) ? (int) $this->extra['enquiry'] : 0;

		$business = $this->answer( 'business' );
		$contact  = $this->answer( 'contact' );

		$this->placeholders['{business_name}'] = $business;
		$this->placeholders['{contact_name}']  = $contact;

		// First name only — "Hello Claire," not "Hello Claire Devereux,".
		$first = $contact ? current( preg_split( '/\s+/', trim( $contact ) ) ) : '';
		$this->placeholders['{customer_name}'] = $first;

		// Emails to the enquirer go to the address they gave us. The ones to
		// the shop already have their recipient and must not be redirected.
		if ( $this->to_customer ) {
			$email = $this->answer( 'email' );
			if ( is_email( $email ) ) {
				$this->recipient = $email;
			}
		}
	}

	public function __construct() {
		$this->placeholders = array(
			'{business_name}' => '',
			'{contact_name}'  => '',
		);
		parent::__construct();
	}
}

/** Sent the moment the short enquiry is submitted. */
class MVE_Email_Enquiry_Received extends MVE_Email_Enquiry_Base {
	public function __construct() {
		$this->id             = 'mve_enquiry_received';
		$this->title          = __( 'Trade enquiry — received', 'maison-vintique' );
		$this->description    = __( 'Sent to somebody who submits the short trade enquiry form.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'We have your trade enquiry — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Thank you for your enquiry', 'maison-vintique' );
	}
	protected function body_lines() {
		$business = $this->placeholders['{business_name}'];
		return array(
			$business
				/* translators: %s: business name */
				? sprintf( __( 'Thank you for getting in touch about a trade account for <strong>%s</strong>.', 'maison-vintique' ), esc_html( $business ) )
				: __( 'Thank you for getting in touch about a trade account.', 'maison-vintique' ),
			__( 'We read every enquiry by hand rather than opening accounts automatically — it is a small portfolio and we like to know who we are selling to.', 'maison-vintique' ),
		);
	}
	protected function steps() {
		return array(
			__( 'We review your enquiry, usually within two working days.', 'maison-vintique' ),
			__( 'If we look like a good fit, we email you a private link to the full trade account application.', 'maison-vintique' ),
			__( 'You complete that, we carry out our account and licensing checks, and your trade portal is opened.', 'maison-vintique' ),
		);
	}
	protected function note() {
		return __( 'Nothing further is needed from you at this stage. Do keep an eye on your junk folder in case our reply lands there.', 'maison-vintique' );
	}
}

/** The same event, to the shop. */
class MVE_Email_Enquiry_Admin extends MVE_Email_Enquiry_Base {

	protected $to_customer = false;

	public function __construct() {
		$this->id             = 'mve_enquiry_admin';
		$this->title          = __( 'Trade enquiry — new (to the shop)', 'maison-vintique' );
		$this->description    = __( 'Sent to you when somebody submits the short trade enquiry form.', 'maison-vintique' );
		$this->customer_email = false;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( '[{site_title}] Trade enquiry — {business_name}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'New trade enquiry', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			sprintf(
				/* translators: %s: business name */
				__( '<strong>%s</strong> would like to apply for a trade account.', 'maison-vintique' ),
				esc_html( $this->placeholders['{business_name}'] )
			),
		);
	}
	protected function panel() {
		if ( ! $this->enquiry ) {
			return '';
		}

		$fields = function_exists( 'mve_enquiry_fields' ) ? mve_enquiry_fields() : array();
		$rows   = '';

		foreach ( $fields as $key => $field ) {
			$value = $this->answer( $key );
			if ( '' === trim( $value ) ) {
				continue;
			}
			if ( 'select' === $field['type'] && isset( $field['options'][ $value ] ) ) {
				$value = $field['options'][ $value ];
			}
			$rows .= '<tr>'
				. '<td class="mv-panel__k" valign="top" width="150" style="padding:0 14px 8px 0;">' . esc_html( $field['label'] ) . '</td>'
				. '<td valign="top" style="padding:0 0 8px;font-size:14px;">' . nl2br( esc_html( $value ) ) . '</td>'
				. '</tr>';
		}

		return $rows ? '<table border="0" cellpadding="0" cellspacing="0" width="100%">' . $rows . '</table>' : '';
	}
	protected function cta() {
		return $this->enquiry
			? array(
				'label' => __( 'Review this enquiry', 'maison-vintique' ),
				'url'   => admin_url( 'post.php?post=' . $this->enquiry . '&action=edit' ),
			)
			: array();
	}
	protected function note() {
		return __( 'Approving it there emails them a private, expiring link to the full application. Nothing is sent to them until you decide.', 'maison-vintique' );
	}

	/**
	 * Record that the office was told, so the plain-text fallback in
	 * inc/contact-form.php only fires when this genuinely did not go out.
	 */
	public function trigger( $subject = 0, $extra = array() ) {
		$sent = parent::trigger( $subject, $extra );
		if ( $sent && $this->enquiry ) {
			update_post_meta( $this->enquiry, '_mve_shop_notified', current_time( 'mysql' ) );
		}
		return $sent;
	}
}

/** THE INVITATION — the private link to the full application. */
class MVE_Email_Application_Invite extends MVE_Email_Enquiry_Base {
	public function __construct() {
		$this->id             = 'mve_application_invite';
		$this->title          = __( 'Trade enquiry — invitation to apply', 'maison-vintique' );
		$this->description    = __( 'Sent when you approve an enquiry. Carries the private, expiring link to the full trade account application.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your trade account application — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'You are invited to apply', 'maison-vintique' );
	}
	protected function body_lines() {
		$business = $this->placeholders['{business_name}'];
		return array(
			$business
				/* translators: %s: business name */
				? sprintf( __( 'Thank you for your enquiry about <strong>%s</strong>. We would be glad to see a full application.', 'maison-vintique' ), esc_html( $business ) )
				: __( 'Thank you for your enquiry. We would be glad to see a full application.', 'maison-vintique' ),
			__( 'The link below opens the trade account application. It asks about your company, alcohol licensing, AWRS registration where it applies, delivery and payment details, who may order on the account, and the declarations we are required to hold. Set aside about fifteen minutes, and have your licence details to hand.', 'maison-vintique' ),
		);
	}
	protected function panel() {
		$expires = $this->enquiry ? get_post_meta( $this->enquiry, '_mve_token_expires', true ) : '';
		if ( ! $expires ) {
			return '';
		}

		return '<p><span class="mv-panel__k">' . esc_html__( 'This link is yours alone', 'maison-vintique' ) . '</span></p>'
			. '<p><span class="mv-panel__v">' . esc_html(
				sprintf(
					/* translators: %s: date */
					__( 'It expires on %s', 'maison-vintique' ),
					date_i18n( get_option( 'date_format' ), strtotime( $expires ) )
				)
			) . '</span></p>'
			. '<p style="margin:0;font-size:13px;">' . esc_html__( 'Please do not forward it — it is tied to your enquiry, and it stops working once your application has been submitted.', 'maison-vintique' ) . '</p>';
	}
	protected function cta() {
		$url = ( $this->enquiry && function_exists( 'mve_invite_url' ) ) ? mve_invite_url( $this->enquiry ) : '';
		return $url
			? array(
				'label' => __( 'Open your application', 'maison-vintique' ),
				'url'   => $url,
			)
			: array();
	}
	protected function note() {
		return __( 'Being invited to apply is not the same as being approved. Your trade account is opened only once we have completed our account and due-diligence review of the full application.', 'maison-vintique' );
	}
}

/** More information needed before we can invite them to apply. */
class MVE_Email_Enquiry_More_Info extends MVE_Email_Enquiry_Base {
	public function __construct() {
		$this->id             = 'mve_enquiry_more_info';
		$this->title          = __( 'Trade enquiry — more information', 'maison-vintique' );
		$this->description    = __( 'Sent when you ask an enquirer for more before deciding.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'About your trade enquiry — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'A little more, please', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			__( 'Thank you for your enquiry. Before we can move it on, we would like to know a bit more.', 'maison-vintique' ),
		);
	}
	protected function note() {
		$message = ! empty( $this->extra['message'] ) ? $this->extra['message'] : '';
		return $message
			? nl2br( esc_html( $message ) )
			: __( 'Simply reply to this email and we will pick it up from there.', 'maison-vintique' );
	}
}

/** Not suitable at present. */
class MVE_Email_Enquiry_Declined extends MVE_Email_Enquiry_Base {
	public function __construct() {
		$this->id             = 'mve_enquiry_declined';
		$this->title          = __( 'Trade enquiry — declined', 'maison-vintique' );
		$this->description    = __( 'Sent when an enquiry is not one you can take forward at the moment.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'About your trade enquiry — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'About your enquiry', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			__( 'Thank you for thinking of us, and for taking the time to tell us about your business.', 'maison-vintique' ),
			__( 'We are not able to take this forward at the moment. It is a small portfolio with limited allocations, and that means saying no to businesses we would otherwise be glad to supply — it is not a reflection on yours.', 'maison-vintique' ),
			__( 'Do come back to us if things change. We are always happy to look again.', 'maison-vintique' ),
		);
	}
	protected function note() {
		$message = ! empty( $this->extra['message'] ) ? $this->extra['message'] : '';
		return $message ? nl2br( esc_html( $message ) ) : '';
	}
}

/** The due-diligence review is paused. */
class MVE_Email_Trade_On_Hold extends MVE_Email_Trade_Base {
	public function __construct() {
		$this->id             = 'mve_trade_on_hold';
		$this->title          = __( 'Trade — application on hold', 'maison-vintique' );
		$this->description    = __( 'Sent when a full application is put on hold during the due-diligence review.', 'maison-vintique' );
		$this->customer_email = true;
		parent::__construct();
	}
	public function get_default_subject() {
		return __( 'Your trade application is on hold — {site_title}', 'maison-vintique' );
	}
	public function get_default_heading() {
		return __( 'Your application is on hold', 'maison-vintique' );
	}
	protected function body_lines() {
		return array(
			__( 'Your trade account application is with us and is paused for the moment while we complete our checks. Nothing has gone wrong, and nothing is needed from you unless we ask.', 'maison-vintique' ),
			__( 'We will be in touch as soon as we can take it further.', 'maison-vintique' ),
		);
	}
	protected function note() {
		$message = ! empty( $this->extra['message'] ) ? $this->extra['message'] : '';
		return $message ? nl2br( esc_html( $message ) ) : '';
	}
}
