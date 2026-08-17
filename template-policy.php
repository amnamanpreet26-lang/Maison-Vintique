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
			if ( ! $mvl_heading ) {
				continue;
			}
			$mvl_sections[] = array(
				'heading' => $mvl_heading,
				'body'    => get_sub_field( 'body' ),
				'id'      => 'policy-' . sanitize_title( $mvl_heading ),
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

				<?php if ( count( $mvl_sections ) > 2 ) : ?>
					<nav class="mv-policy__toc" aria-label="<?php esc_attr_e( 'On this page', 'maison-vintique-elementor' ); ?>">
						<p class="mv-policy__toc-label"><?php esc_html_e( 'On this page', 'maison-vintique-elementor' ); ?></p>
						<ol>
							<?php foreach ( $mvl_sections as $mvl_section ) : ?>
								<li><a href="#<?php echo esc_attr( $mvl_section['id'] ); ?>"><?php echo esc_html( $mvl_section['heading'] ); ?></a></li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>

				<div class="mv-policy__body">
					<?php if ( $mvl_sections ) : ?>
						<?php $mvl_n = 0; ?>
						<?php foreach ( $mvl_sections as $mvl_section ) : ?>
							<?php $mvl_n++; ?>
							<section class="mv-policy__section" id="<?php echo esc_attr( $mvl_section['id'] ); ?>">
								<h2 class="mv-policy__h">
									<span class="mv-policy__n"><?php echo esc_html( $mvl_n ); ?></span>
									<?php echo esc_html( $mvl_section['heading'] ); ?>
								</h2>
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
					<div class="mv-policy__contact">
						<p class="mv-policy__toc-label"><?php esc_html_e( 'Questions about this policy', 'maison-vintique-elementor' ); ?></p>
						<?php echo wp_kses_post( wpautop( $mvl_contact ) ); ?>
					</div>
				<?php endif; ?>

			</div>
		</section>

	</main>

	<?php
endwhile;

get_footer();
