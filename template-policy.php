<?php
/**
 * Template Name: Policy / Legal
 *
 * One template for all four legal pages — Privacy Policy, Refund Policy,
 * Terms & Conditions, Cookie Policy — because they are the same shape: a
 * heading, a last-reviewed date, a contents list, then numbered clauses.
 *
 * Create a page, pick "Policy / Legal" under Page Attributes → Template, then
 * fill in the Policy tab. The contents list builds itself from the section
 * headings, and each section gets an id so it can be linked to directly.
 *
 * Starter wording for all four is in POLICIES.md — paste it in and edit. It is
 * a starting point written for a UK trade wine merchant, NOT legal advice, and
 * it needs a solicitor's eye before you rely on it.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvl_updated = mve_field( 'policy_updated' );
	$mvl_intro   = mve_field( 'policy_intro' );
	$mvl_contact = mve_field( 'policy_contact' );

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => mve_field( 'policy_eyebrow', __( 'Legal', 'maison-vintique-elementor' ) ),
			'title'   => mve_field( 'policy_title', get_the_title() ),
			'intro'   => $mvl_intro,
		)
	);

	// Collect the sections once: the contents list needs them before the loop
	// that prints them.
	$mvl_sections = array();
	if ( have_rows( 'policy_sections' ) ) {
		while ( have_rows( 'policy_sections' ) ) {
			the_row();
			$mvl_heading = get_sub_field( 'heading' );
			$mvl_body    = get_sub_field( 'body' );

			// A row with neither heading nor body is just an empty row.
			if ( ! $mvl_heading && ! $mvl_body ) {
				continue;
			}

			// Headings are optional: a block of text on its own is a valid
			// clause, and forcing a heading on it would invent one.
			$mvl_sections[] = array(
				'heading' => $mvl_heading,
				'body'    => $mvl_body,
				'id'      => $mvl_heading ? 'policy-' . sanitize_title( $mvl_heading ) : '',
			);
		}
	}
	?>

	<main class="mv-policy">

		<section class="section mvp-sec">
			<div class="wrap mv-policy__wrap">

				<?php if ( $mvl_updated ) : ?>
					<p class="mv-policy__updated">
						<?php
						printf(
							/* translators: %s: date */
							esc_html__( 'Last reviewed %s', 'maison-vintique-elementor' ),
							esc_html( $mvl_updated )
						);
						?>
					</p>
				<?php endif; ?>


				<div class="mv-policy__body">
					<?php if ( $mvl_sections ) : ?>
						<?php foreach ( $mvl_sections as $mvl_section ) : ?>
							<section class="mv-policy__section"<?php echo $mvl_section['id'] ? ' id="' . esc_attr( $mvl_section['id'] ) . '"' : ''; ?>>
								<?php if ( $mvl_section['heading'] ) : ?>
									<h2 class="mv-policy__h"><?php echo esc_html( $mvl_section['heading'] ); ?></h2>
								<?php endif; ?>

								<?php
								/*
								 * wp_kses_post, NOT esc_html: this is a WYSIWYG
								 * field, so bold, lists, links and tables pasted
								 * in must survive exactly as pasted. kses strips
								 * scripts and iframes but leaves formatting alone.
								 */
								?>
								<?php if ( $mvl_section['body'] ) : ?>
									<div class="mv-policy__prose"><?php echo wp_kses_post( $mvl_section['body'] ); ?></div>
								<?php endif; ?>
							</section>
						<?php endforeach; ?>
					<?php else : ?>
						<?php // Nothing in the repeater — fall back to the page editor. ?>
						<div class="mv-policy__prose"><?php the_content(); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( $mvl_contact ) : ?>
					<?php // Plain text, same as the rest of the page — no panel, no label. ?>
					<div class="mv-policy__contact mv-policy__prose"><?php echo wp_kses_post( wpautop( $mvl_contact ) ); ?></div>
				<?php endif; ?>

			</div>
		</section>

	</main>

	<?php
endwhile;

get_footer();
