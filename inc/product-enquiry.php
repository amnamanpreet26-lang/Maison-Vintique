<?php
/**
 * inc/product-enquiry.php
 *
 * "Enquire" — on every wine, everywhere.
 *
 * The button already existed on the wine card and the product page, but it
 * linked at /shop/?enquire=123, which nothing ever handled: the visitor landed
 * back on the shop with a query string and no way to ask anything. So it was a
 * button that did nothing.
 *
 * Now it opens a popup with the wine already filled in, and the enquiry is
 * emailed to whoever the shop nominates.
 *
 * WHERE THE BUTTON IS
 *   - the homepage "Curated Portfolio" cards
 *   - every card on the shop / collection pages
 *   (both of those are template-parts/wine-card.php — one file, so they cannot
 *    drift apart)
 *   - the single product page, next to Add to Case
 *
 * IT DEGRADES. Each button is a real link to the contact page, and JavaScript
 * turns it into a popup opener. With scripts off it still goes somewhere
 * useful instead of doing nothing — which is the fault it is replacing.
 *
 * WHERE THE ENQUIRIES GO
 *   WooCommerce → Settings → Emails → "Product enquiries go to".
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where a product enquiry is emailed.
 *
 * Its own setting: enquiries about a wine usually want the person who knows
 * the portfolio, not the accounts inbox. Empty falls back to the general
 * notifications address.
 *
 * @return string
 */
function mve_enquiry_email() {
	$value = trim( (string) get_option( 'mve_product_enquiry_email', '' ) );
	if ( '' === $value ) {
		$value = function_exists( 'mve_notification_email' ) ? mve_notification_email() : get_option( 'admin_email' );
	}
	return apply_filters( 'mve_product_enquiry_email', $value );
}

/**
 * The fallback the button points at with scripts off.
 *
 * @param int $product_id Product.
 * @return string
 */
function mve_enquiry_fallback_url( $product_id = 0 ) {
	$url = function_exists( 'mve_enquiry_page_url' ) ? mve_enquiry_page_url() : '';
	if ( ! $url ) {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
	return $product_id ? add_query_arg( 'enquire', (int) $product_id, $url ) : $url;
}

/**
 * One Enquire button.
 *
 * @param WC_Product|int $product Product or ID.
 * @param string         $class   Extra classes.
 * @param string         $label   Button text.
 */
function mve_enquiry_button( $product, $class = '', $label = '' ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$label = $label ? $label : __( 'Enquire', 'maison-vintique' );
	?>
	<a class="mv-enquire <?php echo esc_attr( $class ); ?>"
		href="<?php echo esc_url( mve_enquiry_fallback_url( $product->get_id() ) ); ?>"
		data-mv-enquire
		data-product="<?php echo esc_attr( $product->get_id() ); ?>"
		data-wine="<?php echo esc_attr( $product->get_name() ); ?>">
		<?php echo esc_html( $label ); ?>
	</a>
	<?php
}

/**
 * The fields an enquiry asks for.
 *
 * Short on purpose. This is "tell me more about this wine", not an
 * application — every extra box costs enquiries.
 *
 * @return array
 */
function mve_enquiry_form_fields() {
	return apply_filters(
		'mve_enquiry_form_fields',
		array(
			'name'     => array(
				'label'    => __( 'Your name', 'maison-vintique' ),
				'type'     => 'text',
				'required' => true,
				'width'    => 'half',
			),
			'business' => array(
				'label' => __( 'Business', 'maison-vintique' ),
				'type'  => 'text',
				'width' => 'half',
			),
			'email'    => array(
				'label'    => __( 'Email', 'maison-vintique' ),
				'type'     => 'email',
				'required' => true,
				'width'    => 'half',
			),
			'phone'    => array(
				'label' => __( 'Telephone', 'maison-vintique' ),
				'type'  => 'tel',
				'width' => 'half',
			),
			'cases'    => array(
				'label'       => __( 'Cases you are interested in', 'maison-vintique' ),
				'type'        => 'number',
				'width'       => 'half',
				'min'         => 1,
				'placeholder' => '1',
			),
			'when'     => array(
				'label'   => __( 'When do you need it?', 'maison-vintique' ),
				'type'    => 'select',
				'width'   => 'half',
				'options' => array(
					''          => __( 'No particular date', 'maison-vintique' ),
					'this_week' => __( 'This week', 'maison-vintique' ),
					'this_month' => __( 'This month', 'maison-vintique' ),
					'next_month' => __( 'Next month', 'maison-vintique' ),
					'planning'  => __( 'Just planning ahead', 'maison-vintique' ),
				),
			),
			'message'  => array(
				'label'       => __( 'Your enquiry', 'maison-vintique' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Availability, pricing, a sample, tasting notes — whatever you need.', 'maison-vintique' ),
			),
		)
	);
}

/**
 * The popup, printed once per page.
 *
 * One dialog reused by every button on the page rather than one per card —
 * a shop page of thirty wines would otherwise carry thirty copies of the same
 * form.
 */
function mve_render_enquiry_popup() {
	if ( is_admin() ) {
		return;
	}
	?>
	<div class="mv-enq" id="mv-enquiry" hidden>
		<div class="mv-enq__backdrop" data-mv-enq-close></div>

		<div class="mv-enq__dialog" role="dialog" aria-modal="true" aria-labelledby="mv-enq-title">
			<button type="button" class="mv-enq__close" data-mv-enq-close
				aria-label="<?php esc_attr_e( 'Close', 'maison-vintique' ); ?>">&times;</button>

			<div class="mv-enq__body" data-mv-enq-body>
				<p class="mv-enq__eyebrow"><?php esc_html_e( 'Enquiry', 'maison-vintique' ); ?></p>
				<h2 class="mv-enq__title" id="mv-enq-title"><?php esc_html_e( 'Ask us about this wine', 'maison-vintique' ); ?></h2>

				<?php // Filled in by the button that opened the popup. ?>
				<p class="mv-enq__wine" data-mv-enq-wine></p>

				<form class="mv-enq__form" method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

					<input type="hidden" name="action" value="mve_product_enquiry">
					<input type="hidden" name="product_id" value="" data-mv-enq-product>
					<input type="hidden" name="redirect_to" value="">
					<?php wp_nonce_field( 'mve_product_enquiry', 'mve_enq_nonce' ); ?>

					<div class="mv-enq__grid">
						<?php foreach ( mve_enquiry_form_fields() as $name => $field ) : ?>
							<?php
							$id    = 'mv-enq-' . str_replace( '_', '-', $name );
							$width = isset( $field['width'] ) ? $field['width'] : 'full';
							?>
							<div class="mv-enq__field mv-enq__field--<?php echo esc_attr( $width ); ?>">
								<label for="<?php echo esc_attr( $id ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php echo ! empty( $field['required'] ) ? '<span class="mv-enq__req" aria-hidden="true">*</span>' : ''; ?>
								</label>

								<?php if ( 'textarea' === $field['type'] ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3"
										placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
										<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>></textarea>

								<?php elseif ( 'select' === $field['type'] ) : ?>
									<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
										<?php foreach ( $field['options'] as $key => $option ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $option ); ?></option>
										<?php endforeach; ?>
									</select>

								<?php else : ?>
									<input type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( $id ); ?>"
										name="<?php echo esc_attr( $name ); ?>"
										<?php echo isset( $field['min'] ) ? 'min="' . esc_attr( $field['min'] ) . '"' : ''; ?>
										placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
										<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<?php // Honeypot — real people never fill this in. ?>
					<div class="mv-enq__hp" aria-hidden="true">
						<label for="mv-enq-url"><?php esc_html_e( 'Leave this field empty', 'maison-vintique' ); ?></label>
						<input type="text" id="mv-enq-url" name="mve_enq_url" tabindex="-1" autocomplete="off">
					</div>

					<p class="mv-enq__error" data-mv-enq-error hidden></p>

					<button type="submit" class="mv-enq__submit"
						data-sending="<?php esc_attr_e( 'Sending…', 'maison-vintique' ); ?>">
						<?php esc_html_e( 'Send enquiry', 'maison-vintique' ); ?>
					</button>

					<p class="mv-enq__foot">
						<?php esc_html_e( 'We answer enquiries personally, usually the same working day.', 'maison-vintique' ); ?>
					</p>
				</form>
			</div>

			<?php // Swapped in once it has been sent. ?>
			<div class="mv-enq__done" data-mv-enq-done hidden tabindex="-1">
				<div class="mv-enq__tick" aria-hidden="true">
					<svg viewBox="0 0 48 48" width="44" height="44" focusable="false">
						<circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".35"></circle>
						<path d="M15 24.5l6.5 6.5L33 19" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</div>
				<h2 class="mv-enq__title"><?php esc_html_e( 'Thank you — enquiry sent.', 'maison-vintique' ); ?></h2>
				<p class="mv-enq__text"><?php esc_html_e( 'We have it, and somebody who knows the portfolio will come back to you — usually the same working day.', 'maison-vintique' ); ?></p>
				<button type="button" class="mv-enq__submit" data-mv-enq-close><?php esc_html_e( 'Close', 'maison-vintique' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'mve_render_enquiry_popup', 20 );

/* =========================================================================
 * THE SUBMISSION
 * ---------------------------------------------------------------------
 * One handler, reached two ways: by fetch() from the popup, and by a plain
 * form post if scripts are off. Same validation either way.
 * ====================================================================== */

/**
 * Handle an enquiry.
 */
function mve_handle_product_enquiry() {
	$ajax = ! empty( $_POST['ajax'] );

	$fail = function ( $message ) use ( $ajax ) {
		if ( $ajax ) {
			wp_send_json_error( array( 'message' => $message ), 400 );
		}
		wp_safe_redirect( add_query_arg( 'enquiry', 'error', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
		exit;
	};

	if ( ! isset( $_POST['mve_enq_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mve_enq_nonce'] ) ), 'mve_product_enquiry' ) ) {
		$fail( __( 'Your session expired. Please close this and try again.', 'maison-vintique' ) );
	}

	// Honeypot — a bot fills everything in, so pretend it worked and drop it.
	if ( ! empty( $_POST['mve_enq_url'] ) ) {
		if ( $ajax ) {
			wp_send_json_success( array( 'ok' => true ) );
		}
		wp_safe_redirect( add_query_arg( 'enquiry', 'sent', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
		exit;
	}

	// One a minute per address is more than anybody needs.
	$throttle = 'mve_enq_' . md5( function_exists( 'mve_get_client_ip' ) ? mve_get_client_ip() : '' );
	if ( get_transient( $throttle ) ) {
		$fail( __( 'That looked like a duplicate. Please wait a moment and try again.', 'maison-vintique' ) );
	}

	$values = array();
	foreach ( mve_enquiry_form_fields() as $name => $field ) {
		$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below.

		switch ( $field['type'] ) {
			case 'email':
				$values[ $name ] = sanitize_email( $raw );
				break;
			case 'textarea':
				$values[ $name ] = sanitize_textarea_field( $raw );
				break;
			case 'number':
				$values[ $name ] = '' === $raw ? '' : (string) absint( $raw );
				break;
			case 'select':
				$clean           = sanitize_text_field( $raw );
				$values[ $name ] = array_key_exists( $clean, $field['options'] ) ? $clean : '';
				break;
			default:
				$values[ $name ] = sanitize_text_field( $raw );
		}

		if ( ! empty( $field['required'] ) && '' === $values[ $name ] ) {
			$fail( sprintf( /* translators: %s: field label */ __( '%s is needed before we can send this.', 'maison-vintique' ), $field['label'] ) );
		}
	}

	if ( ! is_email( $values['email'] ) ) {
		$fail( __( 'That does not look like an email address.', 'maison-vintique' ) );
	}

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : null;

	set_transient( $throttle, 1, MINUTE_IN_SECONDS );

	$sent = false;
	if ( function_exists( 'mve_send_email' ) ) {
		$sent = mve_send_email(
			'mve_product_enquiry',
			0,
			array(
				'enquiry' => $values,
				'product' => $product_id,
			)
		);
	}

	// Belt and braces: if the branded email could not go out, plain text does.
	if ( ! $sent ) {
		mve_plain_product_enquiry( $values, $product );
	}

	// And a short acknowledgement to the person who asked.
	if ( function_exists( 'mve_send_email' ) ) {
		mve_send_email(
			'mve_product_enquiry_ack',
			0,
			array(
				'enquiry' => $values,
				'product' => $product_id,
			)
		);
	}

	/**
	 * Fires after a product enquiry. Hook this to push it into a CRM.
	 *
	 * @param array           $values     The answers.
	 * @param WC_Product|null $product    The wine, if one was named.
	 */
	do_action( 'mve_product_enquiry_received', $values, $product );

	if ( $ajax ) {
		wp_send_json_success( array( 'ok' => true ) );
	}

	$back = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	if ( ! $back || 0 !== strpos( $back, home_url() ) ) {
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	}
	wp_safe_redirect( add_query_arg( 'enquiry', 'sent', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_mve_product_enquiry', 'mve_handle_product_enquiry' );
add_action( 'admin_post_mve_product_enquiry', 'mve_handle_product_enquiry' );

/**
 * The fallback notification — plain text, no WooCommerce needed.
 *
 * @param array           $values  Answers.
 * @param WC_Product|null $product The wine.
 */
function mve_plain_product_enquiry( $values, $product ) {
	$to = mve_enquiry_email();
	if ( ! $to ) {
		return;
	}

	$wine  = $product instanceof WC_Product ? $product->get_name() : __( 'not specified', 'maison-vintique' );
	$lines = array(
		__( 'A product enquiry has come in from the website.', 'maison-vintique' ),
		'',
		sprintf( '%s: %s', __( 'Wine', 'maison-vintique' ), $wine ),
	);

	foreach ( mve_enquiry_form_fields() as $name => $field ) {
		if ( empty( $values[ $name ] ) ) {
			continue;
		}
		$value = $values[ $name ];
		if ( 'select' === $field['type'] && isset( $field['options'][ $value ] ) ) {
			$value = $field['options'][ $value ];
		}
		$lines[] = sprintf( '%s: %s', $field['label'], $value );
	}

	if ( $product instanceof WC_Product ) {
		$lines[] = '';
		$lines[] = $product->get_permalink();
	}

	wp_mail(
		$to,
		sprintf(
			/* translators: 1: site name, 2: wine name */
			__( '[%1$s] Enquiry — %2$s', 'maison-vintique' ),
			get_bloginfo( 'name' ),
			$wine
		),
		implode( "\n", $lines ),
		array(
			'Content-Type: text/plain; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', $values['name'], $values['email'] ),
		)
	);
}

/**
 * Where enquiries go, on the WooCommerce email settings screen.
 *
 * @param array $settings WooCommerce email settings.
 * @return array
 */
function mve_enquiry_email_setting( $settings ) {
	$out = array();
	foreach ( $settings as $setting ) {
		if ( isset( $setting['id'], $setting['type'] ) && 'mve_notifications' === $setting['id'] && 'sectionend' === $setting['type'] ) {
			$out[] = array(
				'title'       => __( 'Product enquiries go to', 'maison-vintique' ),
				'desc'        => __( 'Who is emailed when somebody uses the Enquire button on a wine. Leave empty to use the main address above — enquiries about a wine usually want whoever knows the portfolio rather than the accounts inbox.', 'maison-vintique' ),
				'id'          => 'mve_product_enquiry_email',
				'type'        => 'text',
				'default'     => '',
				'css'         => 'min-width:340px;',
				'placeholder' => __( 'the same as above', 'maison-vintique' ),
			);
		}
		$out[] = $setting;
	}
	return $out;
}
add_filter( 'woocommerce_email_settings', 'mve_enquiry_email_setting', 15 );
