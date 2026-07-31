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
 *   ------------------------------------------------
 *   Title
 *   Appellation · Vintage
 *   Price  (or "Trade pricing on login" when gated)
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
 * ------------------------------------------------------------------------- */
if ( $product->is_in_stock() ) {
	$mvc_stock_label = __( 'Available', 'maison-vintique-elementor' );
	$mvc_stock_state = 'in';
} else {
	$mvc_stock_label = __( 'Out of stock', 'maison-vintique-elementor' );
	$mvc_stock_state = 'out';
}

/* ---------------------------------------------------------------------------
 * META LINE — "France · Bordeaux". The Country and Region taxonomy terms,
 * joined by a middot. Either one on its own renders fine; if a wine has
 * several terms in a taxonomy they are all listed.
 * ------------------------------------------------------------------------- */
$mvc_country_terms = get_the_terms( $mvc_id, 'wine_country' );
$mvc_region_terms  = get_the_terms( $mvc_id, 'wine_region' );

$mvc_countries = ( $mvc_country_terms && ! is_wp_error( $mvc_country_terms ) )
	? wp_list_pluck( $mvc_country_terms, 'name' )
	: array();
$mvc_regions = ( $mvc_region_terms && ! is_wp_error( $mvc_region_terms ) )
	? wp_list_pluck( $mvc_region_terms, 'name' )
	: array();

$mvc_meta = implode( ' · ', array_filter( array_merge( $mvc_countries, $mvc_regions ) ) );

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

		<span class="mvcard__badge mvcard__badge--stock is-<?php echo esc_attr( $mvc_stock_state ); ?>">
			<?php echo esc_html( $mvc_stock_label ); ?>
		</span>
	</a>

	<div class="mvcard__body">

		<h3 class="mvcard__title">
			<a href="<?php echo esc_url( $mvc_permalink ); ?>"><?php echo esc_html( get_the_title( $mvc_id ) ); ?></a>
		</h3>

		<?php if ( $mvc_meta ) : ?>
			<p class="mvcard__meta"><?php echo esc_html( $mvc_meta ); ?></p>
		<?php endif; ?>

		<p class="mvcard__price">
			<?php if ( $mvc_gated ) : ?>
				<span class="mvcard__trade"><?php esc_html_e( 'Trade pricing on login', 'maison-vintique-elementor' ); ?></span>
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
