<?php
/**
 * template-parts/content-product-wine.php
 *
 * One wine card inside .pgrid. Pulls from the "Wine Details" ACF field
 * group (group_mv_wine_details / acf-json/group_wine_details.json).
 *
 * Price gating (Visibility Tier) is NOT re-implemented here — it already
 * happens theme-wide via the `woocommerce_get_price_html`,
 * `woocommerce_is_purchasable` and `woocommerce_product_add_to_cart_text`
 * filters in inc/woocommerce.php. This card just calls the normal
 * WooCommerce functions and the existing filters do the rest.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$mve_producer      = get_field( 'producer' );      // post_object -> WP_Post|null
$mve_vintage_year  = get_field( 'vintage_year' );
$mve_abv           = get_field( 'abv' );
$mve_case_format   = get_field( 'case_format' );
$mve_bottle_image  = get_field( 'bottle_image' );  // image array (return_format: array)
$mve_awards_raw    = get_field( 'awards' );
$mve_first_award   = $mve_awards_raw ? trim( strtok( $mve_awards_raw, "\n" ) ) : '';
$mve_tier          = mve_product_tier( $product->get_id() ); // from inc/woocommerce.php

$mve_image_url = ! empty( $mve_bottle_image['sizes']['mv-card'] )
	? $mve_bottle_image['sizes']['mv-card']
	: ( has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'mv-card' ) : wc_placeholder_img_src() );
?>
<div class="pcard">
	<a href="<?php the_permalink(); ?>" class="pcard-media">
		<img src="<?php echo esc_url( $mve_image_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
		<?php if ( 'allocation' === $mve_tier ) : ?>
			<span class="badge badge-alloc">By allocation</span>
		<?php elseif ( ! $product->is_in_stock() ) : ?>
			<span class="badge badge-oos">Out of stock</span>
		<?php endif; ?>
	</a>

	<div class="pcard-body">
		<p class="pcard-cat">
	<?php
	$mve_colour_terms  = get_the_terms( get_the_ID(), 'wine_colour' );
	$mve_region_terms  = get_the_terms( get_the_ID(), 'wine_region' );
	$mve_country_terms = get_the_terms( get_the_ID(), 'wine_country' );


	$mve_colour  = ( $mve_colour_terms && ! is_wp_error( $mve_colour_terms ) ) ? $mve_colour_terms[0]->name : '';
	$mve_region  = ( $mve_region_terms && ! is_wp_error( $mve_region_terms ) ) ? $mve_region_terms[0]->name : '';
	$mve_country = ( $mve_country_terms && ! is_wp_error( $mve_country_terms ) ) ? $mve_country_terms[0]->name : '';


	$mve_cat_parts = array_filter( array( $mve_colour, $mve_region, $mve_country ) );

	echo esc_html( implode( ' · ', $mve_cat_parts ) );
	?>
</p>

		<h3 class="pcard-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>


		<p class="pcard-meta">
			<?php echo wp_strip_all_tags( wc_get_product_category_list( get_the_ID(), ', ' ) ); ?>
		</p>

		<div class="pcard-foot">
			<span class="pcard-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>

			<?php if ( $product->is_purchasable() && ( $product->is_in_stock() || $product->backorders_allowed() ) ) : ?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
					data-quantity="1"
					data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
					class="btn btn-p btn-sm ajax_add_to_cart add_to_cart_button">
					<?php echo esc_html( apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Add to Cart', 'woocommerce' ), $product ) ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
