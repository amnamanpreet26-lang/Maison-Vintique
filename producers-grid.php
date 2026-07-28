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
?>

<main>
<section class="section producers-page">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php echo esc_html( $mvp_title ); ?></span>
	</div>

	<div class="page-h">
		<?php if ( $mvp_eyebrow ) : ?>
			<p class="eyebrow"><?php echo esc_html( $mvp_eyebrow ); ?></p>
		<?php endif; ?>

		<h1><?php echo esc_html( $mvp_title ); ?></h1>

		<?php if ( $mvp_intro ) : ?>
			<p><?php echo esc_html( $mvp_intro ); ?></p>
		<?php else : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			wp_reset_postdata();
			?>
		<?php endif; ?>
	</div>

	<?php if ( $mvp_producers->have_posts() ) : ?>

		<div class="mvprod-grid">
			<?php
			while ( $mvp_producers->have_posts() ) :
				$mvp_producers->the_post();

				$mvp_id = get_the_ID();

				/* --- Location: "Bordeaux, France" ------------------------- */
				$mvp_region_terms  = get_the_terms( $mvp_id, 'producer_region' );
				$mvp_country_terms = get_the_terms( $mvp_id, 'producer_country' );
				$mvp_region        = ( $mvp_region_terms && ! is_wp_error( $mvp_region_terms ) ) ? $mvp_region_terms[0]->name : '';
				$mvp_country       = ( $mvp_country_terms && ! is_wp_error( $mvp_country_terms ) ) ? $mvp_country_terms[0]->name : '';
				$mvp_location      = implode( ', ', array_filter( array( $mvp_region, $mvp_country ) ) );

				/* --- ACF card fields (all optional) ----------------------- */
				$mvp_appellations = function_exists( 'get_field' ) ? get_field( 'appellations', $mvp_id ) : '';
				$mvp_year         = function_exists( 'get_field' ) ? get_field( 'established_year', $mvp_id ) : '';
				$mvp_note         = function_exists( 'get_field' ) ? get_field( 'estate_note', $mvp_id ) : '';

				$mvp_est_parts = array();
				if ( $mvp_year ) {
					/* translators: %s: the year the estate was founded, e.g. 1868 */
					$mvp_est_parts[] = sprintf( __( 'Est. %s', 'maison-vintique-elementor' ), $mvp_year );
				}
				if ( $mvp_note ) {
					$mvp_est_parts[] = $mvp_note;
				}
				$mvp_est_line = implode( ' · ', $mvp_est_parts );

				/* --- Image: featured, else first gallery image ------------ */
				$mvp_image_url = has_post_thumbnail( $mvp_id ) ? get_the_post_thumbnail_url( $mvp_id, 'mv-card' ) : '';
				if ( ! $mvp_image_url && function_exists( 'get_field' ) ) {
					$mvp_gallery = get_field( 'producer_media', $mvp_id );
					if ( ! empty( $mvp_gallery[0]['sizes']['mv-card'] ) ) {
						$mvp_image_url = $mvp_gallery[0]['sizes']['mv-card'];
					} elseif ( ! empty( $mvp_gallery[0]['url'] ) ) {
						$mvp_image_url = $mvp_gallery[0]['url'];
					}
				}

				/* --- Initials for the no-image crest block ---------------- */
				$mvp_initials = '';
				if ( ! $mvp_image_url ) {
					$mvp_words = preg_split( '/\s+/', trim( wp_strip_all_tags( get_the_title() ) ) );
					$mvp_skip  = array( 'de', 'du', 'la', 'le', 'les', 'et', 'of', 'the' );
					foreach ( (array) $mvp_words as $mvp_word ) {
						if ( in_array( mb_strtolower( $mvp_word ), $mvp_skip, true ) ) {
							continue;
						}
						$mvp_first = mb_substr( $mvp_word, 0, 1 );
						if ( preg_match( '/\p{L}/u', $mvp_first ) ) {
							$mvp_initials .= mb_strtoupper( $mvp_first );
						}
					}
					$mvp_initials = mb_substr( $mvp_initials, 0, 3 );
				}

				/* --- "View wines" -> shop, filtered to this estate -------- */
				$mvp_wines_url = function_exists( 'wc_get_page_permalink' )
					? add_query_arg( 'producer', $mvp_id, wc_get_page_permalink( 'shop' ) )
					: get_permalink( $mvp_id );
				?>

				<article class="mvprod">

					<a class="mvprod__media" href="<?php echo esc_url( get_permalink( $mvp_id ) ); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( $mvp_image_url ) : ?>
							<img src="<?php echo esc_url( $mvp_image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $mvp_id ) ); ?>" loading="lazy">
						<?php else : ?>
							<span class="mvprod__initials"><?php echo esc_html( $mvp_initials ); ?></span>
						<?php endif; ?>
					</a>

					<div class="mvprod__body">

						<h2 class="mvprod__title">
							<a href="<?php echo esc_url( get_permalink( $mvp_id ) ); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( $mvp_location ) : ?>
							<p class="mvprod__location"><?php echo esc_html( $mvp_location ); ?></p>
						<?php endif; ?>

						<?php if ( $mvp_appellations ) : ?>
							<p class="mvprod__appellations"><?php echo esc_html( $mvp_appellations ); ?></p>
						<?php endif; ?>

						<?php if ( $mvp_est_line ) : ?>
							<p class="mvprod__est"><?php echo esc_html( $mvp_est_line ); ?></p>
						<?php endif; ?>

						<a class="mvprod__link" href="<?php echo esc_url( $mvp_wines_url ); ?>">
							<?php esc_html_e( 'View wines', 'maison-vintique-elementor' ); ?> <span aria-hidden="true">&rarr;</span>
						</a>

					</div>
				</article>

			<?php endwhile; ?>
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
