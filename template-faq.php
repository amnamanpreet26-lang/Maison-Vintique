<?php
/**
 * Template Name: FAQ
 *
 * Create a page, then pick "FAQ" under Page Attributes → Template.
 *
 * Questions come from a two-level ACF repeater (group_faq.json):
 *   faq_groups           -> one per heading ("Trade accounts", "Ordering"…)
 *     group_title
 *     questions          -> repeater
 *       question
 *       answer           (wysiwyg)
 *
 * Rendered as native <details>/<summary>, so the accordion works with no
 * JavaScript at all and stays keyboard- and screen-reader friendly.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => mve_field( 'faq_eyebrow', __( 'Help', 'maison-vintique-elementor' ) ),
			'title'   => mve_field( 'faq_title', get_the_title() ),
			'intro'   => mve_field( 'faq_intro' ),
		)
	);
	?>

	<main>
		<section class="section mvp-sec">
			<div class="wrap">

				<?php if ( have_rows( 'faq_groups' ) ) : ?>

					<div class="mvp-faq">
						<?php
						while ( have_rows( 'faq_groups' ) ) :
							the_row();

							$mvf_group = get_sub_field( 'group_title' );
							?>

							<?php if ( $mvf_group ) : ?>
								<p class="mvp-faq__grp"><?php echo esc_html( $mvf_group ); ?></p>
							<?php endif; ?>

							<?php if ( have_rows( 'questions' ) ) : ?>
								<?php
								while ( have_rows( 'questions' ) ) :
									the_row();

									$mvf_q = get_sub_field( 'question' );
									$mvf_a = get_sub_field( 'answer' );

									if ( ! $mvf_q ) {
										continue;
									}
									?>
									<details>
										<summary><?php echo esc_html( $mvf_q ); ?></summary>
										<div class="mvp-faq__a"><?php echo wp_kses_post( $mvf_a ); ?></div>
									</details>
								<?php endwhile; ?>
							<?php endif; ?>

						<?php endwhile; ?>
					</div>

				<?php else : ?>

					<div class="mvp-prose"><?php the_content(); ?></div>

				<?php endif; ?>

			</div>
		</section>
	</main>

	<?php
endwhile;

get_footer();
