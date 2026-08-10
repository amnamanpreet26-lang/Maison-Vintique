<?php
/**
 * single-producer.php — one estate's page.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The `producer` post type is public, so every estate has its own URL
 * (/producers/chateau-toulouse-lautrec/). The theme had no single template for
 * it, so those URLs fell through to the PARENT theme's single.php, which
 * renders the title and the editor content only — and since all of an estate's
 * content lives in ACF fields rather than the editor, the page came out
 * essentially blank.
 *
 * WordPress picks this file up automatically for producer posts.
 *
 * Content comes from acf-json/group_producer.json:
 *   producer_history / producer_philosophy / producer_terroir /
 *   producer_sustainability  (wysiwyg)  -> the story sections
 *   producer_media           (gallery)  -> the estate gallery
 *   established_year / estate_note / appellations -> the facts row
 * Country and Region come from the producer_country / producer_region
 * taxonomies. Every section is skipped when its field is empty.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvs_id = get_the_ID();

	/* --- Location ------------------------------------------------------- */
	$mvs_region_terms  = get_the_terms( $mvs_id, 'producer_region' );
	$mvs_country_terms = get_the_terms( $mvs_id, 'producer_country' );
	$mvs_region        = ( $mvs_region_terms && ! is_wp_error( $mvs_region_terms ) ) ? $mvs_region_terms[0]->name : '';
	$mvs_country       = ( $mvs_country_terms && ! is_wp_error( $mvs_country_terms ) ) ? $mvs_country_terms[0]->name : '';
	$mvs_location      = implode( ', ', array_filter( array( $mvs_region, $mvs_country ) ) );

	/* --- Card fields ---------------------------------------------------- */
	$mvs_year         = function_exists( 'get_field' ) ? get_field( 'established_year', $mvs_id ) : '';
	$mvs_note         = function_exists( 'get_field' ) ? get_field( 'estate_note', $mvs_id ) : '';
	$mvs_appellations = function_exists( 'get_field' ) ? get_field( 'appellations', $mvs_id ) : '';

	/* --- Story sections (wysiwyg, all optional) -------------------------- */
	$mvs_story = array();
	if ( function_exists( 'get_field' ) ) {
		$mvs_story = array_filter(
			array(
				__( 'History', 'maison-vintique-elementor' )        => get_field( 'producer_history', $mvs_id ),
				__( 'Philosophy', 'maison-vintique-elementor' )     => get_field( 'producer_philosophy', $mvs_id ),
				__( 'Terroir', 'maison-vintique-elementor' )        => get_field( 'producer_terroir', $mvs_id ),
				__( 'Sustainability', 'maison-vintique-elementor' ) => get_field( 'producer_sustainability', $mvs_id ),
			)
		);
	}

	$mvs_gallery = function_exists( 'get_field' ) ? get_field( 'producer_media', $mvs_id ) : array();

	/* --- Wines from this estate ------------------------------------------
	 * The ACF `producer` post_object on each wine stores this post's ID, which
	 * is the same lookup the shop's ?producer= filter uses (inc/shop-query.php).
	 * -------------------------------------------------------------------- */
	$mvs_wines = new WP_Query(
		array(
			'post_type'      => 'product',
			'posts_per_page' => 8,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => 'producer',
					'value' => $mvs_id,
				),
			),
		)
	);

	$mvs_shop_url = function_exists( 'wc_get_page_permalink' )
		? add_query_arg( 'producer', $mvs_id, wc_get_page_permalink( 'shop' ) )
		: '';

	// Same dark banner as the other editorial pages.
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => __( 'Estate Partner', 'maison-vintique-elementor' ),
			'title'   => get_the_title(),
			'intro'   => $mvs_location,
		)
	);
	?>

<main>
<section class="section producer-single">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'maison-vintique-elementor' ); ?></a>
		/ <a href="<?php echo esc_url( get_post_type_archive_link( 'producer' ) ); ?>"><?php esc_html_e( 'Producers', 'maison-vintique-elementor' ); ?></a>
		/ <span><?php the_title(); ?></span>
	</div>

	<!-- ============ ESTATE HEADER ============ -->
	<div class="mvest-hero">

		<div class="mvest-hero__content">

			<?php if ( $mvs_year || $mvs_appellations || $mvs_note ) : ?>
				<ul class="mvest-facts">
					<?php if ( $mvs_year ) : ?>
						<li>
							<span><?php esc_html_e( 'Established', 'maison-vintique-elementor' ); ?></span>
							<b><?php echo esc_html( $mvs_year ); ?></b>
						</li>
					<?php endif; ?>
					<?php if ( $mvs_appellations ) : ?>
						<li>
							<span><?php esc_html_e( 'Appellations', 'maison-vintique-elementor' ); ?></span>
							<b><?php echo esc_html( $mvs_appellations ); ?></b>
						</li>
					<?php endif; ?>
					<?php if ( $mvs_note ) : ?>
						<li>
							<span><?php esc_html_e( 'Estate', 'maison-vintique-elementor' ); ?></span>
							<b><?php echo esc_html( $mvs_note ); ?></b>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="mvest-hero__intro"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( $mvs_shop_url && $mvs_wines->have_posts() ) : ?>
				<a class="btn btn-p" href="#wine-from-estate">
					<?php esc_html_e( 'View wines from this estate', 'maison-vintique-elementor' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php
		// Only emit the figure when there IS an image — an empty one would
		// otherwise hold open a 4:3 block of background.
		if ( has_post_thumbnail() ) :
			?>
			<figure class="mvest-hero__media"><?php the_post_thumbnail( 'mv-estate' ); ?></figure>
		<?php elseif ( ! empty( $mvs_gallery[0]['url'] ) ) : ?>
			<figure class="mvest-hero__media">
				<img src="<?php echo esc_url( $mvs_gallery[0]['url'] ); ?>" alt="<?php the_title_attribute(); ?>">
			</figure>
		<?php endif; ?>

	</div>

	<!-- ============ ESTATE STORY ============ -->
	<?php if ( $mvs_story ) : ?>
		<div class="mvest-story">
			<?php foreach ( $mvs_story as $mvs_heading => $mvs_body ) : ?>
				<section class="mvest-story__block">
					<h2><?php echo esc_html( $mvs_heading ); ?></h2>
					<div class="mvest-story__body"><?php echo wp_kses_post( $mvs_body ); ?></div>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- ============ GALLERY ============ -->
	<?php if ( ! empty( $mvs_gallery ) && count( $mvs_gallery ) > 1 ) : ?>
		<div class="mvest-gallery">
			<h2><?php esc_html_e( 'The estate', 'maison-vintique-elementor' ); ?></h2>
			<div class="mvest-gallery__grid">
				<?php foreach ( $mvs_gallery as $mvs_image ) : ?>
					<?php if ( empty( $mvs_image['url'] ) ) { continue; } ?>
					<figure>
						<img
							src="<?php echo esc_url( ! empty( $mvs_image['sizes']['mv-card'] ) ? $mvs_image['sizes']['mv-card'] : $mvs_image['url'] ); ?>"
							alt="<?php echo esc_attr( ! empty( $mvs_image['alt'] ) ? $mvs_image['alt'] : get_the_title() ); ?>"
							loading="lazy">
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ============ WINES FROM THIS ESTATE ============ -->
	<?php if ( $mvs_wines->have_posts() ) : ?>
		<div class="mvest-wines" id="wine-from-estate">
			<div class="section__head section__head--split">
				<h2><?php esc_html_e( 'Wines from this estate', 'maison-vintique-elementor' ); ?></h2>
				<?php if ( $mvs_shop_url ) : ?>
					<a href="<?php echo esc_url( $mvs_shop_url ); ?>" class="link-arrow">
						<?php esc_html_e( 'View all', 'maison-vintique-elementor' ); ?> &rarr;
					</a>
				<?php endif; ?>
			</div>

			<div class="pgrid">
				<?php
				while ( $mvs_wines->have_posts() ) :
					$mvs_wines->the_post();
					$GLOBALS['product'] = wc_get_product( get_the_ID() );
					// Same card as the homepage portfolio and the shop archive.
					get_template_part( 'template-parts/wine-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endif; ?>

</div>
</section>
</main>

	<?php
endwhile;

get_footer();
