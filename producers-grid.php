<?php
/**
 * Template Name: Producers Grid
 *
 * The "Our Producers" page — a grid of every estate we represent.
 *
 * Assign it to a Page from Page Attributes > Template in wp-admin (create a
 * page called "Producers" and pick "Producers Grid").
 *
 * WHERE THE CONTENT COMES FROM
 * ----------------------------
 * Page header  : the page's own title + editor content, with optional ACF
 *                overrides (producers_eyebrow / producers_title /
 *                producers_intro) from acf-json/group_producers_page.json.
 * Each card    : the `producer` post type —
 *                  image        -> featured image, else first Media Gallery
 *                                  image (ACF `producer_media`), else an
 *                                  initials crest block
 *                  name         -> post title
 *                  country/region -> producer_country / producer_region taxonomies
 *                  appellations -> ACF `appellations`
 *                  est. line    -> ACF `established_year` + `estate_note`
 *                All of these live in acf-json/group_producer.json.
 *
 * Every line is optional — a producer with nothing but a title still renders a
 * valid card.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mvp_eyebrow = ( function_exists( 'get_field' ) && get_field( 'producers_eyebrow' ) )
	? get_field( 'producers_eyebrow' )
	: __( 'Estate Partners', 'maison-vintique-elementor' );

$mvp_title = ( function_exists( 'get_field' ) && get_field( 'producers_title' ) )
	? get_field( 'producers_title' )
	: get_the_title();

$mvp_intro = ( function_exists( 'get_field' ) && get_field( 'producers_intro' ) )
	? get_field( 'producers_intro' )
	: '';

// Paged so the grid keeps working once there are more than a screenful.
$mvp_paged = max( 1, (int) ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ) );

$mvp_per_page = ( function_exists( 'get_field' ) && get_field( 'producers_per_page' ) )
	? (int) get_field( 'producers_per_page' )
	: 12;

$mvp_producers = new WP_Query(
	array(
		'post_type'      => 'producer',
		'posts_per_page' => $mvp_per_page,
		'paged'          => $mvp_paged,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	)
);

// No intro field set? Fall back to the page's own editor content.
if ( ! $mvp_intro ) {
	ob_start();
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	wp_reset_postdata();
	$mvp_intro = trim( wp_strip_all_tags( ob_get_clean() ) );
}

// Same dark banner as the other editorial pages.
get_template_part(
	'template-parts/page-hero',
	null,
	array(
		'eyebrow' => $mvp_eyebrow,
		'title'   => $mvp_title,
		'intro'   => $mvp_intro,
	)
);
?>

<main>
<section class="section producers-page">
<div class="wrap wrap--producers">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php echo esc_html( $mvp_title ); ?></span>
	</div>

	<?php if ( $mvp_producers->have_posts() ) : ?>

		<div class="mvprod-grid">
			<?php
			while ( $mvp_producers->have_posts() ) :
				$mvp_producers->the_post();
				// Same card the /producers/ archive renders — see
				// template-parts/producer-card.php.
				get_template_part( 'template-parts/producer-card' );
			endwhile;
			?>
		</div>

		<?php if ( $mvp_producers->max_num_pages > 1 ) : ?>
			<div class="pager">
				<?php
				$mvp_big   = 999999999;
				$mvp_links = paginate_links(
					array(
						'base'      => str_replace( $mvp_big, '%#%', esc_url( get_pagenum_link( $mvp_big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $mvp_paged,
						'total'     => (int) $mvp_producers->max_num_pages,
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
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>

		<p class="no-wines"><?php esc_html_e( 'No producers have been published yet.', 'maison-vintique-elementor' ); ?></p>

	<?php endif; ?>

</div>
</section>
</main>

<?php get_footer(); ?>
