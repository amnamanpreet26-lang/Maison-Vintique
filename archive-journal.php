<?php
/**
 * archive-journal.php — /journal/
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The `journal` post type is registered with has_archive => true and rewrite
 * slug "journal" (functions.php), so WordPress already serves /journal/.
 * Without this file that URL fell through to the PARENT theme's generic
 * archive.php — the same gap that made /producers/ look wrong before
 * archive-producer.php was added.
 *
 * WordPress picks this up automatically for the journal archive and for
 * journal_category term archives.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mvj_eyebrow = __( 'The Journal', 'maison-vintique-elementor' );
$mvj_title   = post_type_archive_title( '', false );
$mvj_intro   = get_the_post_type_description();

if ( is_tax() ) {
	$mvj_eyebrow = __( 'The Journal', 'maison-vintique-elementor' );
	$mvj_title   = single_term_title( '', false );
	$mvj_intro   = term_description();
}

if ( ! $mvj_title ) {
	$mvj_title = __( 'Stories from the estates', 'maison-vintique-elementor' );
}
if ( ! $mvj_intro ) {
	$mvj_intro = __( 'Harvest reports, producer interviews and regional guides — a few times a year, never a newsletter for its own sake.', 'maison-vintique-elementor' );
}

get_template_part(
	'template-parts/page-hero',
	null,
	array(
		'eyebrow' => $mvj_eyebrow,
		'title'   => wp_strip_all_tags( $mvj_title ),
		'intro'   => wp_strip_all_tags( $mvj_intro ),
	)
);
?>

<main>
<section class="section mvp-sec">
<div class="wrap">

	<?php if ( have_posts() ) : ?>

		<div class="mvp-grid3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/journal-card' );
			endwhile;
			?>
		</div>

		<div class="pager">
			<?php
			$mvj_big   = 999999999;
			$mvj_links = paginate_links(
				array(
					'base'      => str_replace( $mvj_big, '%#%', esc_url( get_pagenum_link( $mvj_big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
					'prev_text' => '&lsaquo;',
					'next_text' => '&rsaquo;',
					'type'      => 'array',
				)
			);
			if ( $mvj_links ) {
				foreach ( $mvj_links as $mvj_link ) {
					echo wp_kses_post( str_replace( 'current', 'on', $mvj_link ) );
				}
			}
			?>
		</div>

	<?php else : ?>

		<p class="no-wines"><?php esc_html_e( 'No journal entries have been published yet.', 'maison-vintique-elementor' ); ?></p>

	<?php endif; ?>

</div>
</section>
</main>

<?php get_footer(); ?>
