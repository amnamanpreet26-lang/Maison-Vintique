<?php
/**
 * Template Name: Trade Account Application
 *
 * Create a page — "Apply for a Trade Account" — then pick this template under
 * Page Attributes → Template. That is all: the login page and the trade popup
 * both find it on their own (mve_trade_application_url()), so no link has to be
 * updated by hand.
 *
 * The form itself is built from mve_application_schema() in
 * inc/trade-application.php. Everything on this page other than the form is an
 * ACF field, and every one of them is optional — a blank field is skipped, and
 * the page still renders.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvta_note  = mve_field( 'ta_note' );
	$mvta_login = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => mve_field( 'ta_eyebrow', __( 'Trade Accounts', 'maison-vintique-elementor' ) ),
			'title'   => mve_field( 'ta_title', get_the_title() ),
			'intro'   => mve_field( 'ta_intro' ),
			'prefix'  => 'ta',
		)
	);
	?>

	<main>
		<section class="section mvta-sec-wrap">
			<div class="wrap mvta-wrap">

				<!-- ---------- What to expect ---------- -->
				<?php if ( have_rows( 'ta_steps' ) || $mvta_note || get_the_content() ) : ?>
					<aside class="mvta-aside">

						<?php if ( get_the_content() ) : ?>
							<div class="mvta-aside__prose"><?php the_content(); ?></div>
						<?php endif; ?>

						<?php if ( have_rows( 'ta_steps' ) ) : ?>
							<ol class="mvta-steps">
								<?php
								while ( have_rows( 'ta_steps' ) ) :
									the_row();

									$mvta_step_title = get_sub_field( 'title' );
									$mvta_step_text  = get_sub_field( 'text' );

									if ( ! $mvta_step_title && ! $mvta_step_text ) {
										continue;
									}
									?>
									<li class="mvta-steps__item">
										<?php if ( $mvta_step_title ) : ?>
											<h3 class="mvta-steps__title"><?php echo esc_html( $mvta_step_title ); ?></h3>
										<?php endif; ?>
										<?php if ( $mvta_step_text ) : ?>
											<p class="mvta-steps__text"><?php echo esc_html( $mvta_step_text ); ?></p>
										<?php endif; ?>
									</li>
								<?php endwhile; ?>
							</ol>
						<?php endif; ?>

						<?php if ( $mvta_note ) : ?>
							<p class="mvta-aside__note"><?php echo esc_html( $mvta_note ); ?></p>
						<?php endif; ?>

						<?php // Somebody who already applied does not need the form. ?>
						<p class="mvta-aside__login">
							<?php esc_html_e( 'Already have an account?', 'maison-vintique-elementor' ); ?>
							<a href="<?php echo esc_url( $mvta_login ); ?>"><?php esc_html_e( 'Sign in', 'maison-vintique-elementor' ); ?></a>
						</p>
					</aside>
				<?php endif; ?>

				<!-- ---------- The application ---------- -->
				<div class="mvta-panel">
					<?php mve_application_form(); ?>
				</div>

			</div>
		</section>
	</main>

	<?php
endwhile;

get_footer();
