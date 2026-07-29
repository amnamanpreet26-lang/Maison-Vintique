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
	$mvc_form_title  = mve_field( 'contact_form_title' );

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
						<p class="mvp-form__msg is-ok">
							<?php esc_html_e( 'Thank you — we will be in touch.', 'maison-vintique-elementor' ); ?>
						</p>
					<?php elseif ( 'error' === $mvc_sent ) : ?>
						<p class="mvp-form__msg is-error">
							<?php esc_html_e( 'Sorry, that didn\'t send. Please check your details and try again.', 'maison-vintique-elementor' ); ?>
						</p>
					<?php endif; ?>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="mve_contact">
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
						<?php wp_nonce_field( 'mve_contact', 'mve_contact_nonce' ); ?>

						<div class="mvp-field">
							<label for="mvc-name"><?php esc_html_e( 'Name', 'maison-vintique-elementor' ); ?></label>
							<input type="text" id="mvc-name" name="mvc_name" required
								placeholder="<?php esc_attr_e( 'Your name', 'maison-vintique-elementor' ); ?>">
						</div>

						<div class="mvp-field">
							<label for="mvc-business"><?php esc_html_e( 'Business', 'maison-vintique-elementor' ); ?></label>
							<input type="text" id="mvc-business" name="mvc_business"
								placeholder="<?php esc_attr_e( 'Business name', 'maison-vintique-elementor' ); ?>">
						</div>

						<div class="mvp-field">
							<label for="mvc-email"><?php esc_html_e( 'Email', 'maison-vintique-elementor' ); ?></label>
							<input type="email" id="mvc-email" name="mvc_email" required
								placeholder="you@business.com">
						</div>

						<div class="mvp-field">
							<label for="mvc-message"><?php esc_html_e( 'Message', 'maison-vintique-elementor' ); ?></label>
							<textarea id="mvc-message" name="mvc_message" rows="4" required
								placeholder="<?php esc_attr_e( 'How can we help?', 'maison-vintique-elementor' ); ?>"></textarea>
						</div>

						<?php // Honeypot — real people never fill this in. ?>
						<div class="mvp-hp" aria-hidden="true">
							<label for="mvc-website"><?php esc_html_e( 'Leave this field empty', 'maison-vintique-elementor' ); ?></label>
							<input type="text" id="mvc-website" name="mvc_website" tabindex="-1" autocomplete="off">
						</div>

						<button type="submit" class="btn btn--primary mvp-form__submit">
							<?php esc_html_e( 'Send message', 'maison-vintique-elementor' ); ?>
						</button>
					</form>
				</div>

			</div>
		</section>
	</main>

	<?php
endwhile;

get_footer();
