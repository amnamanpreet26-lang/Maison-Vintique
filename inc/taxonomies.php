<?php
/**
 * Producer CPT and WooCommerce attribute seeding.
 * Wine filtering now uses WooCommerce's built-in Product Category and
 * global Product Attributes (Country, Region, Colour, Grape, Recognition,
 * Bottle Size, Case Format) only — the old custom wine_country / wine_region /
 * wine_appellation / etc. taxonomies have been removed to avoid duplicate/
 * confusing sidebar boxes.
 *
 * @package maison-vintique-elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Producer custom post type (estates behind the wines).
 */
function mve_register_producer_cpt() {
	register_post_type(
		'producer',
		array(
			'label'        => __( 'Producers', 'maison-vintique-elementor' ),
			'labels'       => array(
				'name'          => __( 'Producers', 'maison-vintique-elementor' ),
				'singular_name' => __( 'Producer', 'maison-vintique-elementor' ),
				'add_new_item'  => __( 'Add New Producer', 'maison-vintique-elementor' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-store',
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'producers' ),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		)
	);
}
add_action( 'init', 'mve_register_producer_cpt' );
/**
 * Seed WooCommerce global product attributes (used for variations / filters).
 * Runs once on theme activation.
 */
function mve_seed_wc_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}
	$existing = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' );
	$attrs    = array(
		'country'     => 'Country',
		'region'      => 'Region',
		'colour'      => 'Colour',
		'grape'       => 'Grape',
		'recognition' => 'Recognition',
		'bottle-size' => 'Bottle Size',
		'case-format' => 'Case Format',
	);
	foreach ( $attrs as $name => $label ) {
		if ( in_array( $name, $existing, true ) ) {
			continue;
		}
		wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $name,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
	}
}
add_action( 'after_switch_theme', 'mve_seed_wc_attributes' );




/**
 * Hierarchical product taxonomy (behaves like Product Category).
 * Add one register_taxonomy() call per taxonomy you need.
 */
function mve_register_product_taxonomies() {

	$taxonomies = array(
		'wine_country'     => array(
			'name'   => __( 'Countries', 'maison-vintique-elementor' ),
			'single' => __( 'Country', 'maison-vintique-elementor' ),
			'slug'   => 'wine-country',
		),
		'wine_region'      => array(
			'name'   => __( 'Regions', 'maison-vintique-elementor' ),
			'single' => __( 'Region', 'maison-vintique-elementor' ),
			'slug'   => 'wine-region',
		),
		'wine_colour'      => array(
			'name'   => __( 'Colours', 'maison-vintique-elementor' ),
			'single' => __( 'Colour', 'maison-vintique-elementor' ),
			'slug'   => 'wine-colour',
		),
		'wine_grape'       => array(
			'name'   => __( 'Grapes', 'maison-vintique-elementor' ),
			'single' => __( 'Grape', 'maison-vintique-elementor' ),
			'slug'   => 'wine-grape',
		),
		'wine_recognition' => array(
			'name'   => __( 'Recognitions', 'maison-vintique-elementor' ),
			'single' => __( 'Recognition', 'maison-vintique-elementor' ),
			'slug'   => 'wine-recognition',
		),
	);

	foreach ( $taxonomies as $taxonomy => $cfg ) {
		register_taxonomy(
			$taxonomy,
			array( 'product' ),
			array(
				'label'             => $cfg['name'],
				'labels'            => array(
					'name'          => $cfg['name'],
					'singular_name' => $cfg['single'],
					/* translators: %s: taxonomy singular label. */
					'add_new_item'  => sprintf( __( 'Add New %s', 'maison-vintique-elementor' ), $cfg['single'] ),
					/* translators: %s: taxonomy singular label. */
					'parent_item'   => sprintf( __( 'Parent %s', 'maison-vintique-elementor' ), $cfg['single'] ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $cfg['slug'],
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	}
}
add_action( 'init', 'mve_register_product_taxonomies' );