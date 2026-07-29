<?php
/**
 * inc/shop-query.php
 *
 * Powers the sidebar/toolbar on woocommerce/archive-product.php:
 *  - "Vintage: newest" custom sort (orderby=vintage, using ACF vintage_year)
 *  - Category / price / availability filtering from template-parts/shop-filters.php
 *  - `?producer=ID` filtering, used by the "View wines →" links on producers-grid.php
 *
 * Trade price-on-login gating stays in inc/woocommerce.php — this file only
 * touches the product query, not pricing/purchasability.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Vintage: newest" sort option.
 */
function mve_catalog_ordering_args( $args ) {
	if ( isset( $_GET['orderby'] ) && 'vintage' === $_GET['orderby'] ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'vintage_year'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$args['order']    = 'DESC';
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'mve_catalog_ordering_args' );

/**
 * Apply the Category / Price / Availability filters to the main shop query.
 */
function mve_filter_shop_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Category (multi-select checkboxes).
	if ( ! empty( $_GET['product_cat'] ) ) {
		$tax_query   = $query->get( 'tax_query' ) ?: array();
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array_map( 'sanitize_title', (array) $_GET['product_cat'] ),
		);
		$query->set( 'tax_query', $tax_query );
	}

	$meta_query = $query->get( 'meta_query' ) ?: array();

	// Producer — powers the "View wines →" link on the Producers Grid page
	// (producers-grid.php). The ACF `producer` post_object stores the producer
	// post ID in postmeta, so a plain meta compare is all this needs.
	if ( ! empty( $_GET['producer'] ) ) {
		$meta_query[] = array(
			'key'   => 'producer',
			'value' => (int) $_GET['producer'],
		);
	}

	// Price ceiling from the range slider.
	if ( ! empty( $_GET['price_max'] ) ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => floatval( $_GET['price_max'] ),
			'compare' => '<=',
			'type'    => 'DECIMAL',
		);
	}

	// Availability radio.
	if ( ! empty( $_GET['stock'] ) && 'instock' === $_GET['stock'] ) {
		$meta_query[] = array(
			'key'   => '_stock_status',
			'value' => 'instock',
		);
	}

	if ( $meta_query ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'woocommerce_product_query', 'mve_filter_shop_query' );
