<?php
/**
 * template-parts/producer-card.php
 *
 * ONE producer card. The single source of truth for the estate card, used by:
 *   - archive-producer.php  (the /producers/ CPT archive)
 *   - producers-grid.php    ("Producers Grid" page template)
 *
 * Every line is optional — a producer with nothing but a title still renders a
 * valid card.
 *
 * Fields (all from acf-json/group_producer.json, "Grid Card" tab):
 *   established_year  "1868"            -> "Est. 1868"
 *   estate_note       "family estate"   -> joined after the year with a middot
 *   appellations      "Chinon · Saumur" -> the line above it
 * Country/Region come from the producer_country / producer_region taxonomies.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvp_id = get_the_ID();

/* --- Location: "Bordeaux, France" --------------------------------------- */
$mvp_region_terms  = get_the_terms( $mvp_id, 'producer_region' );
$mvp_country_terms = get_the_terms( $mvp_id, 'producer_country' );
$mvp_region        = ( $mvp_region_terms && ! is_wp_error( $mvp_region_terms ) ) ? $mvp_region_terms[0]->name : '';
$mvp_country       = ( $mvp_country_terms && ! is_wp_error( $mvp_country_terms ) ) ? $mvp_country_terms[0]->name : '';
$mvp_location      = implode( ', ', array_filter( array( $mvp_region, $mvp_country ) ) );

/* --- ACF card fields (all optional) ------------------------------------- */
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

/* --- Image: featured, else first gallery image --------------------------- */
$mvp_image_url = has_post_thumbnail( $mvp_id ) ? get_the_post_thumbnail_url( $mvp_id, 'mv-card' ) : '';
if ( ! $mvp_image_url && function_exists( 'get_field' ) ) {
	$mvp_gallery = get_field( 'producer_media', $mvp_id );
	if ( ! empty( $mvp_gallery[0]['sizes']['mv-card'] ) ) {
		$mvp_image_url = $mvp_gallery[0]['sizes']['mv-card'];
	} elseif ( ! empty( $mvp_gallery[0]['url'] ) ) {
		$mvp_image_url = $mvp_gallery[0]['url'];
	}
}

/* --- Initials for the no-image crest block ------------------------------- */
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

/* --- "View wines" -> shop, filtered to this estate ----------------------- */
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
