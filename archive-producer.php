<?php
/**
 * archive-producer.php — the /producers/ archive.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The `producer` post type is registered with has_archive => true and
 * rewrite slug "producers" (inc/taxonomies.php), so WordPress serves a real
 * archive at /producers/. Without this file that URL falls through to the
 * PARENT theme's generic archive.php and comes out as a plain blog-style list
 * — which is why the producers page did not look like the design.
 *
 * WordPress picks this file up automatically for the producer archive and for
 * the Countries/Regions term archives. Nothing needs to call it.
 *
 * The cards themselves come from template-parts/producer-card.php, which the
 * "Producers Grid" page template (producers-grid.php) also uses — so both
 * routes render an identical grid.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mvp_eyebrow = __( 'Estate Partners', 'maison-vintique-elementor' );
$mvp_title   = post_type_archive_title( '', false );
$mvp_intro   = get_the_post_type_description();

// Term archives (Countries / Regions) get their own title + description.
if ( is_tax() ) {
	$mvp_title = single_term_title( '', false );
	$mvp_intro = term_description();
}

if ( ! $mvp_title ) {
	$mvp_title = __( 'Our Producers', 'maison-vintique-elementor' );
}
if ( ! $mvp_intro ) {
	$mvp_intro = __( 'A small circle of independent estates we represent in the UK — each tended by its own family, in its own way. Sign in for allocations and trade pricing.', 'maison-vintique-elementor' );
}

// Same dark banner as the other editorial pages.
get_template_part(
	'template-parts/page-hero',
	null,
	array(
		'eyebrow' => $mvp_eyebrow,
		'title'   => wp_strip_all_tags( $mvp_title ),
		'intro'   => wp_strip_all_tags( $mvp_intro ),
	)
);
?>

<main>
<section class="section producers-page">
<div class="wrap wrap--producers">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php echo esc_html( wp_strip_all_tags( $mvp_title ) ); ?></span>
	</div>

	<?php if ( have_posts() ) : ?>

		<div class="mvprod-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/producer-card' );
			endwhile;
			?>
		</div>

		<div class="pager">
			<?php
			$mvp_big   = 999999999;
			$mvp_links = paginate_links(
				array(
					'base'      => str_replace( $mvp_big, '%#%', esc_url( get_pagenum_link( $mvp_big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
					'prev_text' => '&lsaquo;',
					'next_text' => '&rsaquo;',
					'type'      => 'array',
				)
			);
			if ( $mvp_links ) {
				foreach ( $mvp_links as $mvp_link ) {
					echo wp_kses_post( str_replace( 'current', 'on', $mvp_link ) );
				}
			}
			?>
		</div>

	<?php else : ?>

		<p class="no-wines"><?php esc_html_e( 'No producers have been published yet.', 'maison-vintique-elementor' ); ?></p>

	<?php endif; ?>

</div>
</section>
</main>

<?php get_footer(); ?>
