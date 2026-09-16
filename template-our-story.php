<?php
/**
 * Template Name: Our Story
 *
 * Create a page, then pick "Our Story" under Page Attributes → Template.
 *
 * THE SHAPE OF THE PAGE, top to bottom. The tabs on the edit screen are
 * numbered in this same order, so "where does this field come out?" is
 * answered by the tab it is on:
 *
 *   1. Hero              the banner, with an optional photo or video behind it
 *   2. Three Columns     the numbered columns — the heart of the page
 *   3. Text + Image      one block of copy beside a picture or a video
 *   4. Full-width Band   an edge-to-edge photo with a line of copy over it
 *   5. Closing Button    one button at the foot
 *   6. Long Text         OFF by default — see below
 *
 * WHY THE LONG PARAGRAPHS ARE GONE
 * --------------------------------
 * The page used to open with a heading and four full paragraphs, which read as
 * a wall of text — "too wordy", in the client's words. The three columns say
 * the same thing in a fraction of the words and give each idea somewhere to go.
 *
 * Nothing was deleted. The old copy is still in the Long Text tab, and the
 * switch there puts it back. It is simply off to begin with.
 *
 * Every piece of copy is an ACF field (group_our_story.json), and every block
 * is skipped when its fields are empty — so a half-filled page still renders
 * cleanly.
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

	$mvs_values_eyebrow = mve_field( 'os_values_eyebrow' );
	$mvs_values_title   = mve_field( 'os_values_title' );

	// The long version, off unless the editor asks for it. See the note above.
	$mvs_show_body = (bool) mve_field( 'os_show_body', false );
	$mvs_lead      = mve_field( 'os_lead' );
	$mvs_body      = mve_field( 'os_body' );

	$mvs_cta_label = mve_field( 'os_cta_label' );
	$mvs_cta_link  = mve_field( 'os_cta_link' );

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => $mvs_eyebrow,
			'title'   => $mvs_title,
			'intro'   => $mvs_intro,
			// Optional hero background image / video — see the Hero tab.
			'prefix'  => 'os',
		)
	);
	?>

	<main>

		<!-- ============ THE THREE COLUMNS ============ -->
		<?php
		/*
		 * Numbered, divided by a hairline rather than boxed in cards, each one
		 * ending in a link. Three is the shape the design asks for; the
		 * repeater allows a fourth, and the grid handles it.
		 */
		?>
		<?php if ( have_rows( 'os_values' ) ) : ?>
			<section class="section mvp-sec mvos-pillars">
				<div class="wrap">
					<?php if ( $mvs_values_eyebrow ) : ?>
						<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvs_values_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $mvs_values_title ) : ?>
						<h2 class="mvp-sec__title"><?php echo esc_html( $mvs_values_title ); ?></h2>
					<?php endif; ?>

					<div class="mvos-pillars__grid">
						<?php
						while ( have_rows( 'os_values' ) ) :
							the_row();

							$mvp_n     = get_sub_field( 'index' );
							$mvp_title = get_sub_field( 'title' );
							$mvp_text  = get_sub_field( 'text' );
							$mvp_label = get_sub_field( 'link_label' );
							$mvp_url   = get_sub_field( 'link_url' );
							?>
							<article class="mvos-pillar">
								<?php if ( $mvp_n ) : ?>
									<p class="mvos-pillar__n">
										<span class="mvos-pillar__num"><?php echo esc_html( $mvp_n ); ?></span>
										<span class="mvos-pillar__rule" aria-hidden="true"></span>
									</p>
								<?php endif; ?>

								<?php if ( $mvp_title ) : ?>
									<h3 class="mvos-pillar__title"><?php echo esc_html( $mvp_title ); ?></h3>
								<?php endif; ?>

								<?php if ( $mvp_text ) : ?>
									<p class="mvos-pillar__text"><?php echo esc_html( $mvp_text ); ?></p>
								<?php endif; ?>

								<?php // The link is optional — a column without one simply ends. ?>
								<?php if ( $mvp_label ) : ?>
									<a class="link-arrow mvos-pillar__link" href="<?php echo esc_url( $mvp_url ? $mvp_url : '#' ); ?>">
										<?php echo esc_html( $mvp_label ); ?>
										<span aria-hidden="true">&rarr;</span>
									</a>
								<?php endif; ?>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ THE LONG VERSION (off by default) ============ -->
		<?php
		/*
		 * Below the columns rather than above them, so even when it is switched
		 * on the page still opens with the short version.
		 */
		?>
		<?php if ( $mvs_show_body && ( $mvs_lead || $mvs_body ) ) : ?>
			<section class="section mvp-sec">
				<div class="wrap mvp-prose">
					<?php if ( $mvs_lead ) : ?>
						<p class="mvp-lead"><?php echo esc_html( $mvs_lead ); ?></p>
					<?php endif; ?>

					<?php echo wp_kses_post( $mvs_body ); ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ TEXT + IMAGE / VIDEO ============ -->
		<?php // Copy one side, media the other. Skipped when both halves are empty. ?>
		<?php get_template_part( 'template-parts/page-split', null, array( 'prefix' => 'os' ) ); ?>

		<!-- ============ FULL-WIDTH BAND ============ -->
		<?php // Edge to edge. Skipped when there is no photo or video to show. ?>
		<?php get_template_part( 'template-parts/page-video-hero', null, array( 'prefix' => 'os' ) ); ?>

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
