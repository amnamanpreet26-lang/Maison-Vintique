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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
		$lines    = array( sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ) );

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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
		$lines = array( sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ) );

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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
			sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $this->placeholders['{customer_name}'] ) ),
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
