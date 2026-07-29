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

/* --- Initials, e.g. "Château de Sancerre" -> "CS" ------------------------
 * Always built (not just when there's no photo) because the flip side of the
 * card shows them in the crest ring.
 * ------------------------------------------------------------------------ */
$mvp_initials = '';
$mvp_words    = preg_split( '/\s+/', trim( wp_strip_all_tags( get_the_title() ) ) );
$mvp_skip     = array( 'de', 'du', 'la', 'le', 'les', 'et', 'of', 'the' );
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

// Small line under the crest on the flip side — the founding year if we have
// one, otherwise fall back to the region/country, same rule the homepage uses.
$mvp_back_sub = $mvp_year
	/* translators: %s: the year the estate was founded, e.g. 1868 */
	? sprintf( __( 'Est. %s', 'maison-vintique-elementor' ), $mvp_year )
	: ( $mvp_region ? $mvp_region : $mvp_country );

/* --- "View wines" -> shop, filtered to this estate ----------------------- */
$mvp_wines_url = function_exists( 'wc_get_page_permalink' )
	? add_query_arg( 'producer', $mvp_id, wc_get_page_permalink( 'shop' ) )
	: get_permalink( $mvp_id );
?>
<article class="mvprod">

	<a class="mvprod__media" href="<?php echo esc_url( get_permalink( $mvp_id ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		/*
		 * Flip card — same EFFECT as the homepage "Estate Partners" section
		 * (photo on the front, crest + initials + est. year on the back), but
		 * deliberately using its own `mvprod__*` class names rather than the
		 * homepage's `estate-card__*` ones.
		 *
		 * Sharing those class names meant the homepage's own estate-card rules
		 * also landed on this card and fought with it — which is what stopped
		 * the front photo showing. Separate names, no collision, and neither
		 * component can break the other.
		 */
		?>
		<div class="mvprod__flip">

			<div class="mvprod__face mvprod__face--front">
				<?php if ( $mvp_image_url ) : ?>
					<img src="<?php echo esc_url( $mvp_image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $mvp_id ) ); ?>" loading="lazy">
				<?php else : ?>
					<span class="mvprod__initials"><?php echo esc_html( $mvp_initials ); ?></span>
				<?php endif; ?>
			</div>

			<div class="mvprod__face mvprod__face--back">
				<span class="mvprod__badge">
					<span class="mvprod__crest">
						<svg viewBox="0 0 100 130" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php echo esc_attr( get_the_title( $mvp_id ) ); ?> crest">
							<mask id="mvCrestProd-<?php echo esc_attr( $mvp_id ); ?>">
								<rect x="0" y="0" width="100" height="130" fill="black"></rect>
								<path d="M50 88 L21 41 L24 17 L24 7 L35 7 L35 17 L43 17 L43 7 L57 7 L57 17 L65 17 L65 7 L76 7 L76 17 L79 41 Z" fill="white"></path>
								<rect x="48.3" y="20.5" width="3.4" height="3.6" fill="black"></rect>
								<path d="M47.6 24 L52.4 24 L52.4 31 C52.4 32.6 56 34 56 39 L56 58 L44 58 L44 39 C44 34 47.6 32.6 47.6 31 Z" fill="black"></path>
								<path d="M28 61 Q50 54 72 61" fill="none" stroke="black" stroke-width="3" stroke-linecap="round"></path>
							</mask>
							<rect x="0" y="0" width="100" height="130" fill="currentColor" mask="url(#mvCrestProd-<?php echo esc_attr( $mvp_id ); ?>)"></rect>
							<g fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
								<path d="M45 91 L50 98 L55 91"></path>
								<path d="M50 98 L50 106"></path>
								<path d="M41 108 Q50 103.5 59 108"></path>
							</g>
						</svg>
					</span>
					<span class="mvprod__ring"><?php echo esc_html( $mvp_initials ); ?></span>
					<?php if ( $mvp_back_sub ) : ?>
						<span class="mvprod__est-year"><?php echo esc_html( $mvp_back_sub ); ?></span>
					<?php endif; ?>
				</span>
			</div>

		</div>
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
