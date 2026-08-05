<?php
/**
 * Maison Vintique (Elementor child theme) — bootstrap.
 *
 * Parent: Hello Elementor. Requires: Elementor Pro, WooCommerce, ACF Pro.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MVE_VERSION', '1.0.0' );

/**
 * Enqueue parent + child styles.
 */
/**
 * Version an asset by its last-modified time.
 *
 * MVE_VERSION is a hard-coded '1.0.0' that never changes, so browsers and
 * caching plugins held on to old copies of main.js indefinitely — edits to it
 * simply never reached the front end. filemtime() changes every time the file
 * is saved, so each deploy busts the cache exactly once.
 *
 * @param string $rel Path relative to the theme root, e.g. '/assets/js/main.js'.
 * @return string Version string for wp_enqueue_*.
 */
function mve_asset_version( $rel ) {
	$path = get_stylesheet_directory() . $rel;
	return file_exists( $path ) ? (string) filemtime( $path ) : MVE_VERSION;
}

function mve_enqueue() {
	wp_enqueue_style( 'hello-elementor', get_template_directory_uri() . '/style.css', array(), MVE_VERSION );
	wp_enqueue_style( 'mve-style', get_stylesheet_uri(), array( 'hello-elementor' ), mve_asset_version( '/style.css' ) );
	wp_enqueue_style( 'mve-main', get_stylesheet_directory_uri() . '/assets/css/style.css', array( 'mve-style' ), mve_asset_version( '/assets/css/style.css' ) );
	wp_enqueue_script( 'mve-main', get_stylesheet_directory_uri() . '/assets/js/main.js', array(), mve_asset_version( '/assets/js/main.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'mve_enqueue' );

/**
 * Theme supports (WooCommerce + Elementor friendly).
 */
function mve_setup() {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'mv-card', 760, 600, true );
	add_image_size( 'mv-estate', 1000, 800, true );

	/*
	 * Menu locations. Everything listed here shows up as a tickable
	 * "Display location" in Appearance → Menus, and in the Menus screen's
	 * "Manage Locations" tab — assign a menu to one and it renders, no code.
	 *
	 * The three footer-* locations are the ones footer.php prints.
	 */
	register_nav_menus( array(
		'primary'        => __( 'Primary Menu (header)', 'maison-vintique-elementor' ),
		'footer'         => __( 'Footer Menu', 'maison-vintique-elementor' ),
		'footer-explore' => __( 'Footer — Explore (column 2)', 'maison-vintique-elementor' ),
		'footer-trade'   => __( 'Footer — Trade (column 3)', 'maison-vintique-elementor' ),
		'footer-legal'   => __( 'Footer — Legal (bottom bar)', 'maison-vintique-elementor' ),
	) );
}
add_action( 'after_setup_theme', 'mve_setup' );

/**
 * Read an ACF field with a fallback, safely.
 *
 * The editorial page templates are built entirely from ACF fields. This keeps
 * them readable (no function_exists dance on every line) and means the site
 * still renders if ACF is ever deactivated — every field simply falls back.
 *
 * @param string $name     Field name.
 * @param mixed  $fallback Returned when ACF is missing or the field is empty.
 * @param mixed  $post_id  Optional post ID.
 * @return mixed
 */
function mve_field( $name, $fallback = '', $post_id = false ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}
	$value = get_field( $name, $post_id );
	return ( '' === $value || null === $value || false === $value || array() === $value ) ? $fallback : $value;
}

/**
 * have_rows() that doesn't fatal when ACF is inactive.
 */
if ( ! function_exists( 'have_rows' ) ) {
	function have_rows( $selector, $post_id = false ) { // phpcs:ignore
		return false;
	}
}

/**
 * Inline an SVG from this theme's /assets/img directory (used by header.php
 * for the crest logo). Restricted to that folder and to .svg files only.
 */
function mve_inline_svg( $filename ) {
	$filename = basename( (string) $filename ); // no path traversal
	if ( '.svg' !== substr( $filename, -4 ) ) {
		return '';
	}
	$path = get_stylesheet_directory() . '/assets/img/' . $filename;
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Fallback markup for header.php's primary nav if no "Primary" menu has been
 * assigned yet under Appearance → Menus.
 */
function mve_default_primary_menu() {
	$links = array(
		'Shop'      => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		// Ask WordPress for the archive URL rather than hard-coding it — the
		// producer CPT's rewrite slug is "producers", not "producer".
		'Producers' => get_post_type_archive_link( 'producer' ) ?: home_url( '/producers/' ),
		'Journal'   => home_url( '/journal/' ),
		'Our Story' => home_url( '/our-story/' ),
		'Trade'     => home_url( '/trade/' ),
	);
	echo '<ul id="primary-menu" class="mv-nav">';
	foreach ( $links as $label => $url ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Wine taxonomies, producer CPT and WooCommerce attributes.
 */
require_once get_stylesheet_directory() . '/inc/taxonomies.php';
require_once get_stylesheet_directory() . '/inc/woocommerce.php';

/**
 * Shop archive page: sorting ("Vintage: newest") + sidebar filters
 * (category / price / availability). See woocommerce/archive-product.php.
 */
require_once get_stylesheet_directory() . '/inc/shop-query.php';

/**
 * Footer: newsletter AJAX endpoint + footer asset loading.
 *
 * This was missing, which is why the footer menus never appeared — the file
 * was in the theme but nothing ever loaded it.
 */
require_once get_stylesheet_directory() . '/inc/mv-footer.php';

/**
 * Contact page enquiry form (template-contact.php).
 */
require_once get_stylesheet_directory() . '/inc/contact-form.php';

/**
 * Trade account registration on the logged-out My Account page.
 */
require_once get_stylesheet_directory() . '/inc/registration.php';

/**
 * Footer social links — icons + the Customizer panel that holds the URLs.
 */
require_once get_stylesheet_directory() . '/inc/social.php';

/**
 * ACF: load/save field groups from the theme's acf-json folder (version control + handover).
 */
function mve_acf_json_save( $path ) {
	return get_stylesheet_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'mve_acf_json_save' );

function mve_acf_json_load( $paths ) {
	$paths[] = get_stylesheet_directory() . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'mve_acf_json_load' );

/**
 * Register a custom Elementor location so header/footer templates can be assigned
 * (Hello Elementor supports this out of the box; kept here for clarity).
 */
function mve_register_elementor_locations( $manager ) {
	$manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'mve_register_elementor_locations' );





function mv_register_producer_taxonomies() {

    register_taxonomy( 'producer_country', 'producer', array(
        'labels' => array(
            'name'          => 'Countries',
            'singular_name' => 'Country',
            'menu_name'     => 'Countries',
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'country' ),
    ) );

    register_taxonomy( 'producer_region', 'producer', array(
        'labels' => array(
            'name'          => 'Regions',
            'singular_name' => 'Region',
            'menu_name'     => 'Regions',
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'region' ),
    ) );
}
add_action( 'init', 'mv_register_producer_taxonomies' );




function mv_register_journal_cpt() {
    register_post_type( 'journal', array(
        'labels' => array(
            'name'          => 'Journal',
            'singular_name' => 'Journal Entry',
            'menu_name'     => 'Journal',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-book-alt',
        'show_in_rest' => true,
        'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'rewrite'      => array( 'slug' => 'journal' ),
    ) );

    register_taxonomy( 'journal_category', 'journal', array(
        'labels' => array(
            'name'          => 'Categories',
            'singular_name' => 'Category',
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'journal-category' ),
    ) );
}
add_action( 'init', 'mv_register_journal_cpt' );


