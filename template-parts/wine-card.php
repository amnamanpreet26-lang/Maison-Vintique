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
 *
 * NEVER 'mv-card' here. That size is registered hard-cropped to 760x600, so
 * WordPress cuts the top and bottom off a tall bottle when the file is
 * uploaded — the whole bottle is gone from the file on disk and no CSS can
 * bring it back. The sizes below are all soft ones: the picture is scaled to
 * fit, never cut.
 *
 * 'mv-bottle' only exists for images uploaded after this change (or after the
 * media library is regenerated), so 'large' and then the original are there to
 * catch everything already on the site.
 * ------------------------------------------------------------------------- */
$mvc_soft_sizes = array( 'mv-bottle', 'large', 'medium_large' );

$mvc_bottle_image = function_exists( 'get_field' ) ? get_field( 'bottle_image', $mvc_id ) : null;
$mvc_image_url    = '';

if ( $mvc_bottle_image ) {
	$mvc_image_url = mve_image_url( $mvc_bottle_image, $mvc_soft_sizes );
}

if ( ! $mvc_image_url && has_post_thumbnail( $mvc_id ) ) {
	$mvc_thumb_id = get_post_thumbnail_id( $mvc_id );
	foreach ( $mvc_soft_sizes as $mvc_size ) {
		$mvc_try = wp_get_attachment_image_url( $mvc_thumb_id, $mvc_size );
		if ( $mvc_try ) {
			$mvc_image_url = $mvc_try;
			break;
		}
	}
	if ( ! $mvc_image_url ) {
		$mvc_image_url = wp_get_attachment_image_url( $mvc_thumb_id, 'full' );
	}
}

if ( ! $mvc_image_url ) {
	$mvc_image_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
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
 * SUB LINE — "Château Toulouse-Lautrec · 6 × 75cl".
 * Just the two raw field values, producer then case format, joined by a
 * middot. Plain text only — no link. Each part is dropped when empty, and
 * the whole line is dropped when both are empty.
 * ------------------------------------------------------------------------- */
$mvc_producer_obj = function_exists( 'get_field' ) ? get_field( 'producer', $mvc_id ) : null;
$mvc_producer      = '';

if ( $mvc_producer_obj instanceof WP_Post ) {
	// ACF field set to return_format => object.
	$mvc_producer = get_the_title( $mvc_producer_obj->ID );
} elseif ( is_array( $mvc_producer_obj ) && ! empty( $mvc_producer_obj['ID'] ) ) {
	// ACF field set to return_format => array.
	$mvc_producer = get_the_title( $mvc_producer_obj['ID'] );
} elseif ( is_numeric( $mvc_producer_obj ) ) {
	// ACF field set to return_format => id (or raw post ID stored).
	$mvc_producer = get_the_title( (int) $mvc_producer_obj );
}

$mvc_case = function_exists( 'get_field' ) ? get_field( 'case_format', $mvc_id ) : '';
$mvc_case = is_string( $mvc_case ) ? trim( $mvc_case ) : '';

$mvc_sub_parts = array_filter(
	array(
		$mvc_producer,
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
 * EMPTY FIELDS — nothing on this card renders a label, a separator or an empty
 * box for a field that has not been filled in. A wine with only a title and a
 * picture gets a card with only a title and a picture.
 * ------------------------------------------------------------------------- */
$mvc_price_html = $product->get_price_html();

// "Technical Details" is only worth offering when the Technical tab actually
// has something in it — otherwise the link sends the customer to a blank tab.
$mvc_tech_fields = array( 'appellation', 'bottles_per_case', 'closure', 'sustainability', 'serving', 'abv', 'bottle_size', 'allergens', 'technical_sheet' );
$mvc_has_tech    = false;
if ( function_exists( 'get_field' ) ) {
	foreach ( $mvc_tech_fields as $mvc_tech_field ) {
		if ( get_field( $mvc_tech_field, $mvc_id ) ) {
			$mvc_has_tech = true;
			break;
		}
	}
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
				<?php if ( $mvc_meta_left ) : ?>
					<span class="mvcard__meta-left"><?php echo esc_html( $mvc_meta_left ); ?></span>
				<?php endif; ?>
				<?php if ( $mvc_meta_right ) : ?>
					<span class="mvcard__meta-right"><?php echo esc_html( $mvc_meta_right ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<h3 class="mvcard__title">
			<a href="<?php echo esc_url( $mvc_permalink ); ?>"><?php echo esc_html( get_the_title( $mvc_id ) ); ?></a>
		</h3>

		<?php if ( $mvc_sub_parts ) : ?>
			<p class="mvcard__sub">
				<?php
				// Plain text, no link — the parts joined by a middot.
				echo esc_html( implode( ' · ', $mvc_sub_parts ) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( $mvc_gated ) : ?>
			<p class="mvcard__price">
				<span class="mvcard__trade"><?php esc_html_e( 'Sign in to view trade pricing', 'maison-vintique-elementor' ); ?></span>
			</p>
		<?php elseif ( $mvc_price_html ) : ?>
			<p class="mvcard__price"><?php echo wp_kses_post( $mvc_price_html ); ?></p>
		<?php endif; ?>

		<a class="mvcard__cta" href="<?php echo esc_url( $mvc_permalink ); ?>">
			<?php esc_html_e( 'View Wine', 'maison-vintique-elementor' ); ?>
		</a>

		<div class="mvcard__links">
			<?php if ( $mvc_has_tech ) : ?>
				<a class="mvcard__link" href="<?php echo esc_url( $mvc_tech_url ); ?>">
					<?php esc_html_e( 'Technical Details', 'maison-vintique-elementor' ); ?>
				</a>
			<?php endif; ?>
			<a class="mvcard__link" href="<?php echo esc_url( $mvc_enquire_url ); ?>">
				<?php esc_html_e( 'Enquire', 'maison-vintique-elementor' ); ?>
			</a>
		</div>

	</div>
</article>
