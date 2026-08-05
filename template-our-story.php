<?php
/**
 * Template Name: Our Story
 *
 * Create a page, then pick "Our Story" under Page Attributes → Template.
 *
 * Every piece of copy is an ACF field (group_our_story.json), and every block
 * is skipped when its fields are empty — so a half-filled page still renders
 * cleanly. Where a field is blank the template falls back to the page's own
 * title / editor content.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvs_eyebrow = mve_field( 'os_eyebrow', __( 'Our Story', 'maison-vintique-elementor' ) );
	$mvs_title   = mve_field( 'os_title', get_the_title() );
	$mvs_intro   = mve_field( 'os_intro' );

	$mvs_lead = mve_field( 'os_lead' );
	$mvs_body = mve_field( 'os_body' );

	$mvs_values_eyebrow = mve_field( 'os_values_eyebrow' );
	$mvs_values_title   = mve_field( 'os_values_title' );

	$mvs_cta_label = mve_field( 'os_cta_label' );
	$mvs_cta_link  = mve_field( 'os_cta_link' );

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => $mvs_eyebrow,
			'title'   => $mvs_title,
			'intro'   => $mvs_intro,
		)
	);
	?>

	<main>

		<!-- ============ THE STORY ============ -->
		<?php if ( $mvs_lead || $mvs_body || get_the_content() ) : ?>
			<section class="section mvp-sec">
				<div class="wrap mvp-prose">
					<?php if ( $mvs_lead ) : ?>
						<p class="mvp-lead"><?php echo esc_html( $mvs_lead ); ?></p>
					<?php endif; ?>

					<?php
					if ( $mvs_body ) {
						echo wp_kses_post( $mvs_body );
					} else {
						the_content();
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ IMAGE OR VIDEO ============ -->
		<?php get_template_part( 'template-parts/page-media', null, array( 'prefix' => 'os' ) ); ?>

		<!-- ============ WHAT GUIDES US ============ -->
		<?php if ( have_rows( 'os_values' ) ) : ?>
			<section class="section mvp-sec mvp-sec--panel">
				<div class="wrap">
					<?php if ( $mvs_values_eyebrow ) : ?>
						<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvs_values_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $mvs_values_title ) : ?>
						<h2 class="mvp-sec__title"><?php echo esc_html( $mvs_values_title ); ?></h2>
					<?php endif; ?>

					<div class="mvp-cols3">
						<?php
						while ( have_rows( 'os_values' ) ) :
							the_row();
							?>
							<article class="mvp-card">
								<?php if ( $mvi = get_sub_field( 'index' ) ) : ?>
									<div class="mvp-card__n"><?php echo esc_html( $mvi ); ?></div>
								<?php endif; ?>

								<?php if ( $mvt = get_sub_field( 'title' ) ) : ?>
									<h3><?php echo esc_html( $mvt ); ?></h3>
								<?php endif; ?>

								<?php if ( $mvx = get_sub_field( 'text' ) ) : ?>
									<p><?php echo esc_html( $mvx ); ?></p>
								<?php endif; ?>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ CTA ============ -->
		<?php if ( $mvs_cta_label ) : ?>
			<section class="section mvp-sec mvp-sec--cta">
				<div class="wrap">
					<a class="btn btn--primary" href="<?php echo esc_url( $mvs_cta_link ? $mvs_cta_link : get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
						<?php echo esc_html( $mvs_cta_label ); ?>
					</a>
				</div>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
