<?php
/**
 * Template Name: Our Story
 *
 * Create a page, then pick "Our Story" under Page Attributes → Template.
 *
 * THE SHAPE OF THE PAGE, top to bottom — three sections and a button, built
 * to the client's reference. The tabs on the edit screen are numbered in the
 * same order, so "where does this field come out?" is answered by the tab it
 * is on:
 *
 *   1. Hero Images      three photos edge to edge — a large one in the centre
 *                       carrying the heading, a smaller one either side.
 *                       Fixed, not a slider: no arrows, no dots, nothing moves.
 *   2. Three Columns    the numbered columns — the heart of the page
 *   3. Full-width Band  an edge-to-edge photo, the copy at its bottom left
 *   4. Closing Button   optional, one button at the foot
 *
 * WHAT WENT
 * ---------
 * The dark banner that opened every editorial page, the text-beside-a-picture
 * block, and the long paragraphs the client called "too wordy" (and the
 * switch that could bring them back). None of it is in the reference, and an
 * edit screen full of fields that do nothing is how "the fields are shuffled"
 * happens. Anything already typed into those fields is still in the database;
 * it is simply no longer shown or asked for.
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

	/* ---- 1. the hero images ---- */
	$mvs_centre = mve_image_url( mve_field( 'os_strip_centre' ), array( 'mv-estate', 'large', 'full' ) );
	$mvs_left   = mve_image_url( mve_field( 'os_strip_left' ), array( 'large', 'full' ) );
	$mvs_right  = mve_image_url( mve_field( 'os_strip_right' ), array( 'large', 'full' ) );
	$mvs_title  = mve_field( 'os_title', get_the_title() );
	$mvs_kicker = mve_field( 'os_eyebrow' );

	/* ---- 2. the columns ---- */
	$mvs_values_eyebrow = mve_field( 'os_values_eyebrow' );
	$mvs_values_title   = mve_field( 'os_values_title' );

	/* ---- 4. the button ---- */
	$mvs_cta_label = mve_field( 'os_cta_label' );
	$mvs_cta_link  = mve_field( 'os_cta_link' );
	?>

	<main>

		<!-- ============ 1. HERO IMAGES ============ -->
		<?php
		/*
		 * The centre image is the one that matters: it carries the heading, and
		 * the page's <h1> lives on it. Either side image can be left empty and
		 * the centre simply widens to fill the row — so a page with one good
		 * photo still works, and a page with none gets a plain heading instead
		 * of an empty strip.
		 */
		?>
		<?php if ( $mvs_centre ) : ?>
			<section class="section mvos-strip<?php echo ( ! $mvs_left && ! $mvs_right ) ? ' mvos-strip--solo' : ''; ?>">

				<?php if ( $mvs_left ) : ?>
					<figure class="mvos-strip__side mvos-strip__side--left">
						<img src="<?php echo esc_url( $mvs_left ); ?>" alt="" loading="eager">
					</figure>
				<?php endif; ?>

				<figure class="mvos-strip__main">
					<img src="<?php echo esc_url( $mvs_centre ); ?>" alt="" loading="eager">

					<?php if ( $mvs_title || $mvs_kicker ) : ?>
						<figcaption class="mvos-strip__copy">
							<?php if ( $mvs_title ) : ?>
								<h1 class="mvos-strip__title"><?php echo esc_html( $mvs_title ); ?></h1>
							<?php endif; ?>
							<?php if ( $mvs_kicker ) : ?>
								<p class="mvos-strip__kicker"><?php echo esc_html( $mvs_kicker ); ?></p>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>

				<?php if ( $mvs_right ) : ?>
					<figure class="mvos-strip__side mvos-strip__side--right">
						<img src="<?php echo esc_url( $mvs_right ); ?>" alt="" loading="eager">
					</figure>
				<?php endif; ?>

			</section>
		<?php else : ?>
			<?php // No photos yet: the heading still has to exist, for the reader and for search. ?>
			<section class="section mvp-sec mvos-strip-fallback">
				<div class="wrap">
					<h1 class="mvp-sec__title"><?php echo esc_html( $mvs_title ); ?></h1>
					<?php if ( $mvs_kicker ) : ?>
						<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvs_kicker ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ 2. THE THREE COLUMNS ============ -->
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

		<!-- ============ 3. FULL-WIDTH BAND ============ -->
		<?php
		/*
		 * Edge to edge, the copy at the bottom left with a second line in the
		 * far corner — the band's "Text Position" field defaults to that on
		 * this page. Skipped when there is no photo or video to show.
		 */
		?>
		<?php get_template_part( 'template-parts/page-video-hero', null, array( 'prefix' => 'os' ) ); ?>

		<!-- ============ 4. CLOSING BUTTON ============ -->
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
