<?php
/**
 * template-parts/wine-card.php
 *
 * ONE wine card — the single source of truth for the card used by BOTH:
 *   - the homepage "Curated Portfolio" grid (template-home.php)
 *   - the shop / archive grid (woocommerce/archive-product.php via
 *     template-parts/content-product-wine.php)
 *
 * Because both places include this one file, the two grids can never drift
 * apart again — change the card here and it changes in both.
 *
 * Card anatomy (matches the approved design):
 *   [ colour badge ]            [ availability badge ]   <- overlaid on image
 *                                (logged-in visitors only)
 *        (  Award  )                                     <- medallion, on hover
 *        ( Winning )
 *   ------------------------------------------------
 *   RED · BORDEAUX                            FRANCE
 *   Title
 *   Bordeaux Supérieur 2019 · 6 × 75cl        <- producer / appellation / case
 *   Price  (or "Sign in to view trade pricing" when gated)
 *   [        VIEW WINE        ]  -> single product page
 *   Technical Details        Enquire   <- two inline links
 *
 * Price gating is NOT re-implemented here — it already happens theme-wide via
 * the filters in inc/woocommerce.php. This card just asks that file whether the
 * current wine is gated and renders the matching line.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( empty( $product ) || ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product || ! $product->is_visible() ) {
	return;
}

$mvc_id        = $product->get_id();
$mvc_permalink = get_permalink( $mvc_id );
// Logged out = price hidden. See inc/woocommerce.php.
$mvc_gated = function_exists( 'mve_is_gated' ) ? mve_is_gated( $mvc_id ) : ! is_user_logged_in();

/* ---------------------------------------------------------------------------
 * IMAGE — ACF bottle_image first, then the featured image, then the Woo
 * placeholder so a card is never blank.
 * ------------------------------------------------------------------------- */
$mvc_bottle_image = function_exists( 'get_field' ) ? get_field( 'bottle_image', $mvc_id ) : null;
$mvc_image_url    = '';

if ( ! empty( $mvc_bottle_image['sizes']['mv-card'] ) ) {
	$mvc_image_url = $mvc_bottle_image['sizes']['mv-card'];
} elseif ( ! empty( $mvc_bottle_image['url'] ) ) {
	$mvc_image_url = $mvc_bottle_image['url'];
} elseif ( has_post_thumbnail( $mvc_id ) ) {
	$mvc_image_url = get_the_post_thumbnail_url( $mvc_id, 'mv-card' );
}
if ( ! $mvc_image_url ) {
	$mvc_image_url = wc_placeholder_img_src( 'mv-card' );
}

/* ---------------------------------------------------------------------------
 * BADGE 1 (top left) — wine colour. Falls back to the first product category
 * so a card still gets a badge on sites that don't use the colour taxonomy.
 * ------------------------------------------------------------------------- */
$mvc_colour_terms = get_the_terms( $mvc_id, 'wine_colour' );
$mvc_colour       = ( $mvc_colour_terms && ! is_wp_error( $mvc_colour_terms ) ) ? $mvc_colour_terms[0]->name : '';

if ( ! $mvc_colour ) {
	$mvc_cat_terms = get_the_terms( $mvc_id, 'product_cat' );
	$mvc_colour    = ( $mvc_cat_terms && ! is_wp_error( $mvc_cat_terms ) ) ? $mvc_cat_terms[0]->name : '';
}

/* ---------------------------------------------------------------------------
 * BADGE 2 (top right) — availability, straight from the stock status.
 *
 * Trade only: logged-out visitors see no stock at all. Same rule as the price,
 * so a card either shows trade information or it doesn't — there is no state
 * where the price is hidden but the stock is on show.
 * ------------------------------------------------------------------------- */
$mvc_show_stock = ! $mvc_gated;

if ( $product->is_in_stock() ) {
	$mvc_stock_label = __( 'Available', 'maison-vintique-elementor' );
	$mvc_stock_state = 'in';
} else {
	$mvc_stock_label = __( 'Out of stock', 'maison-vintique-elementor' );
	$mvc_stock_state = 'out';
}

/* ---------------------------------------------------------------------------
 * META ROW — "Red · Bordeaux" on the left, the country on the right.
 * ------------------------------------------------------------------------- */
$mvc_country_terms = get_the_terms( $mvc_id, 'wine_country' );
$mvc_region_terms  = get_the_terms( $mvc_id, 'wine_region' );

$mvc_countries = ( $mvc_country_terms && ! is_wp_error( $mvc_country_terms ) )
	? wp_list_pluck( $mvc_country_terms, 'name' )
	: array();
$mvc_regions = ( $mvc_region_terms && ! is_wp_error( $mvc_region_terms ) )
	? wp_list_pluck( $mvc_region_terms, 'name' )
	: array();

// Left: colour, then region. Right: country.
$mvc_meta_left  = implode( ' · ', array_filter( array_merge( array( $mvc_colour ), $mvc_regions ) ) );
$mvc_meta_right = implode( ' · ', $mvc_countries );

/* ---------------------------------------------------------------------------
 * SUB LINE — "Château Toulouse-Lautrec · Bordeaux Supérieur 2019 · 6 × 75cl".
 * Producer, then appellation + vintage, then case format, joined by a middot.
 * Each part is dropped when its field is empty.
 * ------------------------------------------------------------------------- */
$mvc_producer_obj = function_exists( 'get_field' ) ? get_field( 'producer', $mvc_id ) : null;
$mvc_producer     = '';
$mvc_producer_url = '';
if ( $mvc_producer_obj ) {
	$mvc_producer_id  = is_object( $mvc_producer_obj ) ? $mvc_producer_obj->ID : (int) $mvc_producer_obj;
	$mvc_producer     = get_the_title( $mvc_producer_id );
	$mvc_producer_url = get_permalink( $mvc_producer_id );
}

$mvc_case = function_exists( 'get_field' ) ? get_field( 'case_format', $mvc_id ) : '';

// These two were missing while $mvc_appellation_line below still used them,
// which threw two PHP notices per card and left the appellation out of the
// sub line entirely.
$mvc_appellation = function_exists( 'get_field' ) ? get_field( 'appellation', $mvc_id ) : '';
$mvc_vintage     = function_exists( 'get_field' ) ? get_field( 'vintage_year', $mvc_id ) : '';

// Wines are usually titled "<Estate> <Cuvée> <Year>", which would repeat the
// producer straight back at the reader. Drop it when the title already says it.
$mvc_show_producer = ( '' !== $mvc_producer )
	&& ( false === stripos( get_the_title( $mvc_id ), $mvc_producer ) );

// "Bordeaux Supérieur 2019" — one part, so the middots fall in the right places.
$mvc_appellation_line = trim( $mvc_appellation . ' ' . $mvc_vintage );

$mvc_sub_parts = array_filter(
	array(
		$mvc_show_producer ? $mvc_producer : '',
		$mvc_appellation_line,
		$mvc_case,
	)
);

/* ---------------------------------------------------------------------------
 * AWARD — the medallion revealed over the image on hover. The ACF `awards`
 * field is a textarea with one award per line; the medallion itself always
 * reads "Award Winning" (as designed) and the first line becomes its tooltip,
 * so a wine with three awards still gets one clean mark.
 * ------------------------------------------------------------------------- */
$mvc_awards_raw = function_exists( 'get_field' ) ? get_field( 'awards', $mvc_id ) : '';
$mvc_award      = '';
if ( $mvc_awards_raw ) {
	$mvc_award_lines = array_filter( array_map( 'trim', preg_split( '/\R/', $mvc_awards_raw ) ) );
	$mvc_award       = $mvc_award_lines ? reset( $mvc_award_lines ) : '';
}

/* ---------------------------------------------------------------------------
 * INLINE LINKS — Technical Details deep-links to the Technical tab on the
 * single product page; Enquire uses the same ?enquire=ID pattern as the PDP.
 * ------------------------------------------------------------------------- */
// Both forms: ?tab=tech survives anything that strips the fragment, and the
// hash keeps the link working if JavaScript is off. main.js reads either.
$mvc_tech_url    = add_query_arg( 'tab', 'tech', $mvc_permalink ) . '#tab-tech';
$mvc_enquire_url = function_exists( 'wc_get_page_permalink' )
	? add_query_arg( 'enquire', $mvc_id, wc_get_page_permalink( 'shop' ) )
	: $mvc_permalink;
?>
<article class="mvcard" id="mvcard-<?php echo esc_attr( $mvc_id ); ?>">

	<a class="mvcard__media" href="<?php echo esc_url( $mvc_permalink ); ?>" tabindex="-1" aria-hidden="true">
		<img src="<?php echo esc_url( $mvc_image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $mvc_id ) ); ?>" loading="lazy">

		<?php if ( $mvc_colour ) : ?>
			<span class="mvcard__badge mvcard__badge--colour"><?php echo esc_html( $mvc_colour ); ?></span>
		<?php endif; ?>

		<?php if ( $mvc_show_stock ) : ?>
			<span class="mvcard__badge mvcard__badge--stock is-<?php echo esc_attr( $mvc_stock_state ); ?>">
				<?php echo esc_html( $mvc_stock_label ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $mvc_award ) : ?>
			<?php // Medallion, faded in over the image on hover / keyboard focus. ?>
			<span class="mvcard__award" title="<?php echo esc_attr( $mvc_award ); ?>">
				<span class="mvcard__award-label">
					<?php esc_html_e( 'Award', 'maison-vintique-elementor' ); ?><br>
					<?php esc_html_e( 'Winning', 'maison-vintique-elementor' ); ?>
				</span>
				<span class="mvcard__award-sr"><?php echo esc_html( $mvc_award ); ?></span>
			</span>
		<?php endif; ?>
	</a>

	<div class="mvcard__body">

		<?php // Colour · region on the left, country on the right. ?>
		<?php if ( $mvc_meta_left || $mvc_meta_right ) : ?>
			<p class="mvcard__meta">
				<span class="mvcard__meta-left"><?php echo esc_html( $mvc_meta_left ); ?></span>
				<span class="mvcard__meta-right"><?php echo esc_html( $mvc_meta_right ); ?></span>
			</p>
		<?php endif; ?>

		<h3 class="mvcard__title">
			<a href="<?php echo esc_url( $mvc_permalink ); ?>"><?php echo esc_html( get_the_title( $mvc_id ) ); ?></a>
		</h3>

		<?php if ( $mvc_sub_parts ) : ?>
			<p class="mvcard__sub">
				<?php
				$mvc_first = true;
				foreach ( $mvc_sub_parts as $mvc_part ) {
					if ( ! $mvc_first ) {
						echo '<span class="mvcard__sub-sep" aria-hidden="true"> &middot; </span>';
					}
					$mvc_first = false;

					if ( $mvc_show_producer && $mvc_part === $mvc_producer && $mvc_producer_url ) {
						printf(
							'<a class="mvcard__sub-link" href="%s">%s</a>',
							esc_url( $mvc_producer_url ),
							esc_html( $mvc_part )
						);
					} else {
						echo esc_html( $mvc_part );
					}
				}
				?>
			</p>
		<?php endif; ?>

		<p class="mvcard__price">
			<?php if ( $mvc_gated ) : ?>
				<span class="mvcard__trade"><?php esc_html_e( 'Sign in to view trade pricing', 'maison-vintique-elementor' ); ?></span>
			<?php else : ?>
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			<?php endif; ?>
		</p>

		<a class="mvcard__cta" href="<?php echo esc_url( $mvc_permalink ); ?>">
			<?php esc_html_e( 'View Wine', 'maison-vintique-elementor' ); ?>
		</a>

		<div class="mvcard__links">
			<a class="mvcard__link" href="<?php echo esc_url( $mvc_tech_url ); ?>">
				<?php esc_html_e( 'Technical Details', 'maison-vintique-elementor' ); ?>
			</a>
			<a class="mvcard__link" href="<?php echo esc_url( $mvc_enquire_url ); ?>">
				<?php esc_html_e( 'Enquire', 'maison-vintique-elementor' ); ?>
			</a>
		</div>

	</div>
</article>
