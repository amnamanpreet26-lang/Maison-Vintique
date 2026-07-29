<?php
/**
 * Template Name: For the Trade
 *
 * Create a page, then pick "For the Trade" under Page Attributes → Template.
 * Copy comes from ACF (group_for_the_trade.json); blocks with no content are
 * skipped.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvt_steps_eyebrow = mve_field( 'ft_steps_eyebrow' );
	$mvt_steps_title   = mve_field( 'ft_steps_title' );

	$mvt_primary_label = mve_field( 'ft_cta_primary_label' );
	$mvt_primary_link  = mve_field( 'ft_cta_primary_link' );
	$mvt_second_label  = mve_field( 'ft_cta_secondary_label' );
	$mvt_second_link   = mve_field( 'ft_cta_secondary_link' );

	$mvt_closing = mve_field( 'ft_closing' );

	// Sensible defaults so the buttons work before anything is filled in.
	$mvt_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => mve_field( 'ft_eyebrow', __( 'For the Trade', 'maison-vintique-elementor' ) ),
			'title'   => mve_field( 'ft_title', get_the_title() ),
			'intro'   => mve_field( 'ft_intro' ),
		)
	);
	?>

	<main>

		<!-- ============ HOW IT WORKS ============ -->
		<?php if ( have_rows( 'ft_steps' ) || $mvt_primary_label || $mvt_second_label ) : ?>
			<section class="section mvp-sec">
				<div class="wrap">

					<?php if ( $mvt_steps_eyebrow ) : ?>
						<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvt_steps_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $mvt_steps_title ) : ?>
						<h2 class="mvp-sec__title"><?php echo esc_html( $mvt_steps_title ); ?></h2>
					<?php endif; ?>

					<?php if ( have_rows( 'ft_steps' ) ) : ?>
						<div class="mvp-cols3">
							<?php
							while ( have_rows( 'ft_steps' ) ) :
								the_row();
								?>
								<article class="mvp-card">
									<?php if ( $mvi = get_sub_field( 'index' ) ) : ?>
										<div class="mvp-card__n"><?php echo esc_html( $mvi ); ?></div>
									<?php endif; ?>

									<?php if ( $mvh = get_sub_field( 'title' ) ) : ?>
										<h3><?php echo esc_html( $mvh ); ?></h3>
									<?php endif; ?>

									<?php if ( $mvx = get_sub_field( 'text' ) ) : ?>
										<p><?php echo esc_html( $mvx ); ?></p>
									<?php endif; ?>
								</article>
							<?php endwhile; ?>
						</div>
					<?php endif; ?>

					<?php if ( $mvt_primary_label || $mvt_second_label ) : ?>
						<div class="mvp-actions">
							<?php if ( $mvt_primary_label ) : ?>
								<a class="btn btn--primary" href="<?php echo esc_url( $mvt_primary_link ? $mvt_primary_link : $mvt_account_url ); ?>">
									<?php echo esc_html( $mvt_primary_label ); ?>
								</a>
							<?php endif; ?>

							<?php if ( $mvt_second_label ) : ?>
								<a class="btn mvp-btn-ghost" href="<?php echo esc_url( $mvt_second_link ? $mvt_second_link : $mvt_account_url ); ?>">
									<?php echo esc_html( $mvt_second_label ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				</div>
			</section>
		<?php endif; ?>

		<!-- ============ CLOSING NOTE ============ -->
		<?php if ( $mvt_closing || get_the_content() ) : ?>
			<section class="section mvp-sec mvp-sec--panel">
				<div class="wrap mvp-prose mvp-prose--center">
					<?php if ( $mvt_closing ) : ?>
						<p class="mvp-lead"><?php echo esc_html( $mvt_closing ); ?></p>
					<?php else : ?>
						<?php the_content(); ?>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
