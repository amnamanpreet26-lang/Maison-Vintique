<?php
/**
 * inc/emails/class-mve-email-base.php
 *
 * Shared plumbing for every custom Maison Vintique email.
 *
 * WooCommerce's WC_Email wants each email to define its own templates, options,
 * placeholder handling and send method. Eleven emails written that way is
 * eleven near-identical files. This base does all of it once; a subclass only
 * declares who it is and what it says.
 *
 * Every subclass gets, for free:
 *   - the branded header/footer (they render through WooCommerce's wrappers)
 *   - a plain-text version generated from the HTML
 *   - the standard WooCommerce settings screen (enable, subject, heading, type)
 *   - {site_title}, {customer_name}, {order_number} … placeholders
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

abstract class MVE_Email_Base extends WC_Email {

	/** @var WP_User|null Recipient, for the account/trade emails. */
	public $user;

	/** @var array Extra values passed to send(), e.g. a note from the admin. */
	public $extra = array();

	/**
	 * Whether this email goes to the customer (true) or to the shop (false).
	 * Drives the default recipient.
	 *
	 * @var bool
	 */
	protected $to_customer = true;

	public function __construct() {
		$this->template_base  = get_stylesheet_directory() . '/woocommerce/';
		$this->template_html  = 'emails/' . $this->id . '.php';
		$this->template_plain = 'emails/plain/' . $this->id . '.php';
		$this->placeholders   = array_merge(
			array(
				'{site_title}'    => $this->get_blogname(),
				'{customer_name}' => '',
				'{order_number}'  => '',
				'{order_date}'    => '',
			),
			(array) $this->placeholders
		);

		parent::__construct();

		if ( ! $this->to_customer ) {
			$this->recipient = $this->shop_recipient();
		}
	}

	/**
	 * Where a shop-facing email goes.
	 *
	 * Whatever was typed into this email's own Recipient box, and failing that
	 * the one address at the top of WooCommerce → Settings → Emails. Resolved
	 * again at send time so a change takes effect immediately.
	 *
	 * @return string
	 */
	protected function shop_recipient() {
		$own = trim( (string) $this->get_option( 'recipient', '' ) );
		if ( '' !== $own ) {
			return $own;
		}
		return function_exists( 'mve_notification_email' )
			? mve_notification_email()
			: get_option( 'admin_email' );
	}

	/**
	 * Last chance to set things up before the message is built.
	 *
	 * The subject is rendered before the body, so a placeholder a subclass only
	 * fills in while writing the body would never reach the subject line. This
	 * runs before either.
	 */
	protected function prepare() {}

	/**
	 * The body, as an array of paragraphs. This is the ONLY thing most
	 * subclasses need to write.
	 *
	 * @return string[]
	 */
	abstract protected function body_lines();

	/**
	 * Optional call-to-action: array( 'label' => …, 'url' => … ).
	 *
	 * @return array
	 */
	protected function cta() {
		return array();
	}

	/**
	 * Optional highlighted note under the body.
	 *
	 * @return string
	 */
	protected function note() {
		return '';
	}

	/**
	 * How the email opens.
	 *
	 * Written once here rather than as the first line of eleven body_lines(),
	 * so it is styled consistently and reads as a letter rather than a notice.
	 * Return '' for an email that should not greet anybody — the ones that go
	 * to the shop rather than to a person.
	 *
	 * @return string
	 */
	protected function salutation() {
		if ( ! $this->to_customer ) {
			return '';
		}

		$name = trim( (string) $this->placeholders['{customer_name}'] );

		return $name
			/* translators: %s: customer's first name */
			? sprintf( __( 'Hello %s,', 'maison-vintique' ), esc_html( $name ) )
			: __( 'Hello,', 'maison-vintique' );
	}

	/**
	 * Optional framed panel — an invitation link, a summary, an amount owing.
	 * Raw HTML, placed between the body and the note.
	 *
	 * @return string
	 */
	protected function panel() {
		return '';
	}

	/**
	 * Optional numbered "what happens next" list, one string per step.
	 *
	 * @return string[]
	 */
	protected function steps() {
		return array();
	}

	/**
	 * Trigger the email.
	 *
	 * Accepts either a user (account and trade emails) or an order
	 * (transactional ones), so subclasses do not each reimplement this.
	 *
	 * @param int|WC_Order|WP_User $subject Order ID, order, user ID or user.
	 * @param array                $extra   Anything the template wants.
	 * @return bool Whether the mail was handed to wp_mail().
	 */
	public function trigger( $subject = 0, $extra = array() ) {
		$this->setup_locale();
		$this->extra = (array) $extra;

		if ( $subject instanceof WC_Order ) {
			$this->object = $subject;
		} elseif ( is_numeric( $subject ) && function_exists( 'wc_get_order' ) && wc_get_order( $subject ) ) {
			$this->object = wc_get_order( $subject );
		}

		if ( $this->object instanceof WC_Order ) {
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{customer_name}'] = $this->object->get_billing_first_name();
		} else {
			$user = $subject instanceof WP_User ? $subject : get_userdata( (int) $subject );
			if ( $user instanceof WP_User ) {
				$this->user                            = $user;
				$this->placeholders['{customer_name}'] = $user->first_name ? $user->first_name : $user->display_name;
				if ( $this->to_customer ) {
					$this->recipient = $user->user_email;
				}
			}
		}

		// Re-resolved every time, so editing the notification address takes
		// effect on the next email rather than the next page load.
		if ( ! $this->to_customer ) {
			$this->recipient = $this->shop_recipient();
		}

		$this->prepare();

		$sent = false;
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
		return $sent;
	}

	/**
	 * HTML body. Subclasses supply the words; this lays them out.
	 */
	public function get_content_html() {
		ob_start();

		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $this->get_heading() ), '', $this->template_base );

		// The opening line, set in the serif a size up, the way a letter opens.
		$salutation = $this->salutation();
		if ( $salutation ) {
			echo '<p class="mv-salute">' . wp_kses_post( $salutation ) . '</p>';
		}

		foreach ( $this->body_lines() as $line ) {
			echo '<p>' . wp_kses_post( $line ) . '</p>';
		}

		$panel = $this->panel();
		if ( $panel ) {
			echo '<div class="mv-panel">' . wp_kses_post( $panel ) . '</div>';
		}

		$cta = $this->cta();
		if ( ! empty( $cta['label'] ) && ! empty( $cta['url'] ) ) {
			/*
			 * Wrapped in a table rather than left as a bare <a>: Outlook ignores
			 * padding on inline elements, so a plain link renders as a thin
			 * strip of colour with the text hanging off it.
			 */
			printf(
				'<table border="0" cellpadding="0" cellspacing="0" style="margin:28px 0 10px;"><tr><td class="mv-btn" style="border-radius:4px;"><a class="mv-btn" href="%1$s">%2$s</a></td></tr></table>',
				esc_url( $cta['url'] ),
				esc_html( $cta['label'] )
			);
		}

		$steps = $this->steps();
		if ( $steps ) {
			echo '<hr class="mv-rule" />';
			echo '<p class="mv-meta">' . esc_html__( 'What happens next', 'maison-vintique' ) . '</p>';
			echo '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
			$n = 0;
			foreach ( $steps as $step ) {
				$n++;
				printf(
					'<tr><td class="mv-step__n" valign="top" width="36" style="padding:0 0 14px;">%1$s</td><td class="mv-step__t" valign="top" style="padding:0 0 14px;">%2$s</td></tr>',
					esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ),
					wp_kses_post( $step )
				);
			}
			echo '</table>';
		}

		// Last, deliberately: the caveat belongs after the thing it qualifies,
		// not between the reader and the button.
		$note = $this->note();
		if ( $note ) {
			echo '<div class="mv-note"><p>' . wp_kses_post( $note ) . '</p></div>';
		}

		// Order emails get WooCommerce's own order table underneath.
		if ( $this->object instanceof WC_Order && $this->show_order_details() ) {
			do_action( 'woocommerce_email_order_details', $this->object, false, false, $this );
			do_action( 'woocommerce_email_order_meta', $this->object, false, false, $this );
			do_action( 'woocommerce_email_customer_details', $this->object, false, false, $this );
		}

		wc_get_template( 'emails/email-footer.php', array(), '', $this->template_base );

		return ob_get_clean();
	}

	/**
	 * Plain-text body, generated from the HTML rather than maintained twice.
	 * Two copies of the same words drift apart the first time anyone edits one.
	 */
	public function get_content_plain() {
		$text = $this->get_heading() . "\n" . str_repeat( '=', strlen( $this->get_heading() ) ) . "\n\n";

		$salutation = $this->salutation();
		if ( $salutation ) {
			$text .= wp_strip_all_tags( $salutation ) . "\n\n";
		}

		foreach ( $this->body_lines() as $line ) {
			$text .= wordwrap( wp_strip_all_tags( $line ), 72 ) . "\n\n";
		}

		$panel = $this->panel();
		if ( $panel ) {
			$text .= wordwrap( wp_strip_all_tags( $panel ), 72 ) . "\n\n";
		}

		$steps = $this->steps();
		if ( $steps ) {
			$text .= __( 'What happens next', 'maison-vintique' ) . "\n\n";
			$n = 0;
			foreach ( $steps as $step ) {
				$n++;
				$text .= $n . '. ' . wordwrap( wp_strip_all_tags( $step ), 69, "\n   " ) . "\n";
			}
			$text .= "\n";
		}

		$note = $this->note();
		if ( $note ) {
			$text .= wordwrap( wp_strip_all_tags( $note ), 72 ) . "\n\n";
		}

		$cta = $this->cta();
		if ( ! empty( $cta['label'] ) && ! empty( $cta['url'] ) ) {
			$text .= $cta['label'] . ': ' . $cta['url'] . "\n\n";
		}

		if ( $this->object instanceof WC_Order && $this->show_order_details() ) {
			$text .= "\n" . wc_get_template_html(
				'emails/plain/email-order-details.php',
				array(
					'order'         => $this->object,
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'         => $this,
				)
			);
		}

		$text .= "\n\n" . wp_strip_all_tags( get_option( 'woocommerce_email_footer_text' ) );

		return $text;
	}

	/**
	 * Whether to append WooCommerce's order table. Order emails yes, account
	 * and trade emails no.
	 *
	 * @return bool
	 */
	protected function show_order_details() {
		return true;
	}

	/**
	 * Public read of the above, for the admin preview screen — it needs to know
	 * whether to hand this email a sample order, and cannot call a protected
	 * method from outside.
	 *
	 * @return bool
	 */
	public function show_order_details_for_preview() {
		return $this->show_order_details();
	}

	/**
	 * Same again for prepare(), so the preview screen renders an email exactly
	 * as its trigger would — placeholders filled in and all.
	 */
	public function prepare_for_preview() {
		$this->prepare();
	}

	/**
	 * The standard WooCommerce settings screen for this email.
	 */
	public function init_form_fields() {
		$fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'maison-vintique' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'maison-vintique' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'maison-vintique' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Available placeholders: %s', 'maison-vintique' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'maison-vintique' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'The line shown in the dark banner at the top of the email.', 'maison-vintique' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'maison-vintique' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'maison-vintique' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);

		if ( ! $this->to_customer ) {
			$fields = array_merge(
				array(
					'recipient' => array(
						'title'       => __( 'Recipient(s)', 'maison-vintique' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %s: admin email */
							__( 'Comma-separated. Defaults to %s.', 'maison-vintique' ),
							'<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>'
						),
						'placeholder' => '',
						'default'     => '',
						'desc_tip'    => true,
					),
				),
				$fields
			);
		}

		$this->form_fields = $fields;
	}
}
