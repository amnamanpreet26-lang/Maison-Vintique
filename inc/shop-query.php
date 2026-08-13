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
 * A LISTING STAYS A LISTING.
 *
 * The symptom: narrow the shop down to one wine and WordPress throws the
 * customer onto that wine's product page instead of showing the one card they
 * filtered to.
 *
 * Two earlier attempts at this only engaged when the request carried one of the
 * sidebar's own GET parameters. That misses the case this keeps happening in —
 * a plain collection or category link (/product-category/…/, /wine-country/…/)
 * carries no sidebar parameters at all, so the guards never ran.
 *
 * So the question is no longer "did the sidebar submit something", it is
 * "did this URL ask for a LIST or for ONE PRODUCT". A URL that names a post
 * (name/p/product/pagename) is a single product and is left completely alone.
 * Anything else that resolves to products — the shop, a product taxonomy, a
 * product search, the sidebar filters — is a list, and a list is never allowed
 * to turn into a product page no matter how few results it has.
 *
 * @param WP_Query $q Query to inspect. Defaults to the main query.
 * @return bool
 */
function mve_query_wants_a_listing( $q = null ) {
	if ( null === $q ) {
		global $wp_query;
		$q = $wp_query;
	}
	if ( ! $q instanceof WP_Query ) {
		return false;
	}

	// $q->query is what the URL actually asked for, before WordPress decided
	// what kind of page it was. That is the part we can still trust after a
	// one-result query has been promoted to a single post.
	$vars = (array) $q->query;

	// Naming a specific post makes it a single-product request. Nothing below
	// applies and nothing here touches it.
	foreach ( array( 'name', 'p', 'product', 'pagename', 'page_id', 'attachment', 'attachment_id' ) as $single_var ) {
		if ( ! empty( $vars[ $single_var ] ) ) {
			return false;
		}
	}

	if ( isset( $vars['post_type'] ) && 'product' === $vars['post_type'] ) {
		return true;
	}

	if ( isset( $vars['s'] ) ) {
		return true;
	}

	// Every taxonomy attached to products — product_cat, product_tag and all
	// the wine_* ones. Checked by query var AND by name, because a taxonomy can
	// be registered with either.
	foreach ( get_object_taxonomies( 'product' ) as $tax ) {
		$tax_object = get_taxonomy( $tax );
		$query_var  = ( $tax_object && $tax_object->query_var ) ? $tax_object->query_var : $tax;
		if ( ! empty( $vars[ $query_var ] ) || ! empty( $vars[ $tax ] ) ) {
			return true;
		}
	}

	return mve_shop_is_filtered();
}

/**
 * Never redirect a listing to a single product.
 *
 * Scoped to listings only, so every other canonical redirect on the site —
 * trailing slashes, old permalinks, pagination — behaves exactly as before.
 */
function mve_keep_listing_on_grid( $redirect_url ) {
	if ( is_admin() ) {
		return $redirect_url;
	}

	$is_listing = ( function_exists( 'is_shop' ) && is_shop() )
		|| is_post_type_archive( 'product' )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
		|| is_search()
		|| mve_query_wants_a_listing();

	return $is_listing ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'mve_keep_listing_on_grid' );

/**
 * Keep the query itself flagged as an archive.
 *
 * Stops a one-result query being promoted to a single post before the redirect
 * stage ever runs.
 */
function mve_force_shop_listing( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! mve_query_wants_a_listing( $query ) ) {
		return;
	}

	$query->is_single   = false;
	$query->is_singular = false;
	$query->is_page     = false;
	$query->is_archive  = true;
	$query->is_404      = false;
}
add_action( 'parse_query', 'mve_force_shop_listing' );

/**
 * Last line of defence: a listing ALWAYS gets the grid template.
 *
 * The two hooks above depend on guessing which mechanism WordPress used to turn
 * one result into a single product. This one does not guess — if the URL asked
 * for a list, the archive template renders, whatever WordPress decided.
 */
function mve_filtered_shop_template( $template ) {
	if ( is_admin() || ! mve_query_wants_a_listing() ) {
		return $template;
	}

	$archive = locate_template( array( 'woocommerce/archive-product.php' ) );
	if ( ! $archive && function_exists( 'WC' ) ) {
		$archive = WC()->plugin_path() . '/templates/archive-product.php';
	}

	return ( $archive && file_exists( $archive ) ) ? $archive : $template;
}
add_filter( 'template_include', 'mve_filtered_shop_template', 99 );

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
