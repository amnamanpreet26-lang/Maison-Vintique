<?php
/**
 * single-journal.php — one journal entry.
 *
 * Not in the supplied HTML set, but added for the same reason
 * single-producer.php was: every card on /journal/ links to one of these URLs,
 * and without this file they fall through to the PARENT theme's single.php.
 *
 * Journal entries DO use the editor (unlike producers, whose content is all
 * ACF), so this is deliberately simple — hero, featured image, the content,
 * then three more entries.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvj_id  = get_the_ID();
	$mvj_cat = get_the_terms( $mvj_id, 'journal_category' );
	$mvj_cat = ( $mvj_cat && ! is_wp_error( $mvj_cat ) ) ? $mvj_cat[0]->name : '';

	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => $mvj_cat ? $mvj_cat : __( 'The Journal', 'maison-vintique-elementor' ),
			'title'   => get_the_title(),
			'intro'   => get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 30, '…' ) : '',
		)
	);
	?>

	<main>
	<section class="section mvp-sec">
	<div class="wrap">

		<div class="crumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
			/ <a href="<?php echo esc_url( get_post_type_archive_link( 'journal' ) ); ?>"><?php esc_html_e( 'Journal', 'maison-vintique-elementor' ); ?></a>
			/ <span><?php the_title(); ?></span>
		</div>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="mvp-article__media"><?php the_post_thumbnail( 'mv-estate' ); ?></figure>
		<?php endif; ?>

		<article class="mvp-prose mvp-article">
			<?php the_content(); ?>
		</article>

	</div>
	</section>

	<?php
	// More from the Journal — same card as the archive.
	$mvj_more = new WP_Query(
		array(
			'post_type'           => 'journal',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $mvj_id ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( $mvj_more->have_posts() ) :
		?>
		<section class="section mvp-sec mvp-sec--panel">
			<div class="wrap">
				<h2 class="mvp-sec__title"><?php esc_html_e( 'More from the Journal', 'maison-vintique-elementor' ); ?></h2>
				<div class="mvp-grid3">
					<?php
					while ( $mvj_more->have_posts() ) :
						$mvj_more->the_post();
						get_template_part( 'template-parts/journal-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
	?>
	</main>

	<?php
endwhile;

get_footer();
