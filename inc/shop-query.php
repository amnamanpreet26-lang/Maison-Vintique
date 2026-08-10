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
 * The GET keys the sidebar and toolbar submit.
 *
 * @return string[]
 */
function mve_shop_filter_keys() {
	return array(
		's',
		'product_cat',
		'producer',
		'price_max',
		'stock',
		'orderby',
		'wine_country',
		'wine_region',
		'wine_colour',
		'wine_grape',
		'wine_recognition',
	);
}

/**
 * Is this request a filtered shop listing?
 *
 * isset(), not empty(): clearing a checkbox submits the key with an empty
 * value, and that is still a filtered listing — not a plain shop page.
 *
 * @return bool
 */
function mve_shop_is_filtered() {
	foreach ( mve_shop_filter_keys() as $key ) {
		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return true;
		}
	}
	return false;
}

/**
 * Filter down to one wine and stay on the grid.
 *
 * The sidebar form carries a search box, so every filter click submits `s`
 * (empty or not) and WordPress treats the result as a SEARCH. Core then
 * redirects a search that matches exactly one post straight to that post —
 * so narrowing the filters down to a single wine threw the customer onto the
 * product page instead of showing them the one card they had filtered to.
 *
 * Switching it off only for filtered shop requests. Every other canonical
 * redirect on the site — trailing slashes, old permalinks, pagination — is
 * left alone.
 */
function mve_keep_filtered_shop_on_grid( $redirect_url ) {
	if ( is_admin() || ! mve_shop_is_filtered() ) {
		return $redirect_url;
	}

	$mve_is_listing = ( function_exists( 'is_shop' ) && is_shop() )
		|| is_post_type_archive( 'product' )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
		// The sidebar carries a search box, so a filter click always submits
		// `s` and this is the case that actually fires.
		|| is_search();

	return $mve_is_listing ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'mve_keep_filtered_shop_on_grid' );

/**
 * Belt and braces for the same problem.
 *
 * redirect_canonical is the redirect we actually hit, but a one-result query
 * can also be promoted to a single post before it ever gets there. Pinning the
 * query back to "this is a listing" means the archive template renders the
 * grid either way.
 */
function mve_force_shop_listing( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! mve_shop_is_filtered() ) {
		return;
	}

	// A genuine single-product request ALWAYS carries one of these. Without
	// this check, opening a product with a stray ?orderby= on the URL would be
	// forced into archive mode and the product page would break.
	foreach ( array( 'name', 'p', 'product', 'pagename', 'page_id' ) as $mve_single_var ) {
		if ( $query->get( $mve_single_var ) ) {
			return;
		}
	}

	$mve_is_product_query = 'product' === $query->get( 'post_type' )
		|| $query->get( 'wc_query' )
		|| $query->is_post_type_archive( 'product' );

	if ( ! $mve_is_product_query ) {
		return;
	}

	$query->is_single   = false;
	$query->is_singular = false;
	$query->is_page     = false;
	$query->is_archive  = true;
}
add_action( 'parse_query', 'mve_force_shop_listing' );

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
