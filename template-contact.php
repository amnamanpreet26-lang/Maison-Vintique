<?php
/**
 * Template Name: Contact
 *
 * Create a page, then pick "Contact" under Page Attributes → Template.
 *
 * Left column: contact details (ACF repeater) + a link through to the trade
 * application. Right column: a real, working enquiry form — it posts to
 * admin-post.php, is nonce-checked and honeypot-protected, and emails the
 * address set in the ACF field (falling back to the site admin email).
 * See mve_handle_contact_form() in inc/contact-form.php.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvc_note        = mve_field( 'contact_note' );
	$mvc_apply_label = mve_field( 'contact_apply_label' );
	$mvc_apply_link  = mve_field( 'contact_apply_link' );
	$mvc_form_title  = mve_field( 'contact_form_title', __( 'Request a trade account application', 'maison-vintique-elementor' ) );
	$mvc_submit      = mve_field( 'contact_submit_label', __( 'Request a Trade Account Application', 'maison-vintique-elementor' ) );

	$mvc_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

	// Feedback after a submit — see inc/contact-form.php.
	$mvc_sent  = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => mve_field( 'contact_eyebrow', __( 'Contact', 'maison-vintique-elementor' ) ),
			'title'   => mve_field( 'contact_title', get_the_title() ),
			'intro'   => mve_field( 'contact_intro' ),
		)
	);
	?>

	<main>
		<section class="section mvp-sec">
			<div class="wrap mvp-split">

				<!-- ---------- Details ---------- -->
				<div class="mvp-split__info">

					<?php if ( have_rows( 'contact_details' ) ) : ?>
						<?php
						while ( have_rows( 'contact_details' ) ) :
							the_row();

							$mvc_label = get_sub_field( 'label' );
							$mvc_value = get_sub_field( 'value' );

							if ( ! $mvc_value ) {
								continue;
							}
							?>
							<div class="mvp-info-row">
								<span class="mvp-info-row__k"><?php echo esc_html( $mvc_label ); ?></span>
								<span class="mvp-info-row__v">
									<?php
									// Make emails and phone numbers tappable.
									if ( is_email( $mvc_value ) ) {
										printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $mvc_value ) );
									} elseif ( preg_match( '/^[\d\s()+\-]{7,}$/', $mvc_value ) ) {
										printf( '<a href="tel:%s">%s</a>', esc_attr( preg_replace( '/[^\d+]/', '', $mvc_value ) ), esc_html( $mvc_value ) );
									} else {
										echo esc_html( $mvc_value );
									}
									?>
								</span>
							</div>
						<?php endwhile; ?>
					<?php endif; ?>

					<?php if ( $mvc_note ) : ?>
						<p class="mvp-split__note"><?php echo esc_html( $mvc_note ); ?></p>
					<?php endif; ?>

					<?php if ( $mvc_apply_label ) : ?>
						<a class="btn mvp-btn-ghost" href="<?php echo esc_url( $mvc_apply_link ? $mvc_apply_link : $mvc_account_url ); ?>">
							<?php echo esc_html( $mvc_apply_label ); ?>
						</a>
					<?php endif; ?>

				</div>

				<!-- ---------- Form ---------- -->
				<div class="mvp-card mvp-form">

					<?php if ( $mvc_form_title ) : ?>
						<h2 class="mvp-form__title"><?php echo esc_html( $mvc_form_title ); ?></h2>
					<?php endif; ?>

					<?php if ( 'sent' === $mvc_sent ) : ?>

						<?php // Stage one is done. Say what happens next, because the ?>
						<?php // next step is us, not them. ?>
						<div class="mvp-done">
							<p class="mvp-done__eyebrow"><?php esc_html_e( 'Enquiry received', 'maison-vintique-elementor' ); ?></p>
							<h3 class="mvp-done__title"><?php esc_html_e( 'Thank you — we have your enquiry.', 'maison-vintique-elementor' ); ?></h3>
							<p class="mvp-done__text">
								<?php esc_html_e( 'We have emailed you a confirmation. A member of the team reads every enquiry personally, usually within two working days.', 'maison-vintique-elementor' ); ?>
							</p>
							<ol class="mvp-done__next">
								<li><?php esc_html_e( 'We review your enquiry.', 'maison-vintique-elementor' ); ?></li>
								<li><?php esc_html_e( 'If we are a good fit, we email you a private link to the full trade account application.', 'maison-vintique-elementor' ); ?></li>
								<li><?php esc_html_e( 'You complete that, we carry out our account checks, and your portal is opened.', 'maison-vintique-elementor' ); ?></li>
							</ol>
						</div>

					<?php else : ?>

						<?php if ( 'error' === $mvc_sent ) : ?>
							<p class="mvp-form__msg is-error">
								<?php
								$mvc_flash = mve_enquiry_flash();
								echo esc_html(
									! empty( $mvc_flash['errors']['_form'] )
										? $mvc_flash['errors']['_form']
										: __( 'Please check the highlighted answers and try again.', 'maison-vintique-elementor' )
								);
								?>
							</p>
						<?php endif; ?>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="mvp-form__form">
							<input type="hidden" name="action" value="mve_contact">
							<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
							<?php wp_nonce_field( 'mve_contact', 'mve_contact_nonce' ); ?>

							<div class="mvp-grid">
								<?php // The client's questions, defined once in mve_enquiry_fields(). ?>
								<?php foreach ( mve_enquiry_fields() as $mvc_key => $mvc_field ) : ?>
									<?php mve_enquiry_field( $mvc_key, $mvc_field ); ?>
								<?php endforeach; ?>
							</div>

							<?php // Honeypot — real people never fill this in. ?>
							<div class="mvp-hp" aria-hidden="true">
								<label for="mvc-website-url"><?php esc_html_e( 'Leave this field empty', 'maison-vintique-elementor' ); ?></label>
								<input type="text" id="mvc-website-url" name="mvc_website_url" tabindex="-1" autocomplete="off">
							</div>

							<button type="submit" class="btn btn--primary mvp-form__submit">
								<?php echo esc_html( $mvc_submit ); ?>
							</button>

							<p class="mvp-form__foot">
								<?php esc_html_e( 'This is a short enquiry, not the full application. Sending it does not create an account.', 'maison-vintique-elementor' ); ?>
							</p>
						</form>

					<?php endif; ?>
				</div>

			</div>
		</section>
	</main>

	<?php
endwhile;

get_footer();
