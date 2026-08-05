<?php
/**
 * Footer social links.
 *
 * WHERE THE LINKS ARE EDITED
 * --------------------------
 *   Appearance → Customize → Social Links
 *
 * Paste a full profile URL into a network's box and its icon appears in the
 * footer; clear the box and it disappears. No code, no deploy.
 *
 * WHY THE ICONS LIVE HERE
 * -----------------------
 * The footer used to call mve_inline_svg( 'social-instagram.svg' ), but that
 * file — and every other social-*.svg — was never in assets/img. The helper
 * returns an empty string for a missing file, so the Instagram link rendered
 * as an empty, invisible <li>. Keeping the icon paths in PHP means there is
 * no file to go missing.
 *
 * ADDING ANOTHER NETWORK
 * ----------------------
 * Add one entry to mve_social_networks() with its label and SVG path data.
 * It then shows up in the Customizer automatically — nothing else to touch.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The networks the footer knows about.
 *
 * 'path' is the inner markup of a 24x24 viewBox SVG.
 *
 * @return array[] keyed by network slug.
 */
function mve_social_networks() {
	$networks = array(
		'instagram' => array(
			'label' => __( 'Instagram', 'maison-vintique-elementor' ),
			'path'  => '<rect x="2.5" y="2.5" width="19" height="19" rx="5.5" fill="none" stroke="currentColor" stroke-width="1.7"/>'
				. '<circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="1.7"/>'
				. '<circle cx="17.4" cy="6.6" r="1.25" fill="currentColor"/>',
		),
		'facebook'  => array(
			'label' => __( 'Facebook', 'maison-vintique-elementor' ),
			'path'  => '<path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5H16.7V3.6a21 21 0 0 0-2.4-.12c-2.4 0-4 1.46-4 4.14v2.31H7.6V13h2.7v8z" fill="currentColor"/>',
		),
		'x'         => array(
			'label' => __( 'X (Twitter)', 'maison-vintique-elementor' ),
			'path'  => '<path d="M17.2 3h3.3l-7.2 8.2L21.8 21h-6.6l-5.2-6.5L4.2 21H.9l7.7-8.8L.5 3h6.8l4.7 5.9zm-1.2 16h1.8L7.9 4.8H6z" fill="currentColor"/>',
		),
		'linkedin'  => array(
			'label' => __( 'LinkedIn', 'maison-vintique-elementor' ),
			'path'  => '<path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-.95 1.83-1.95 3.77-1.95 4.03 0 4.78 2.5 4.78 5.76V21h-4v-5.6c0-1.34-.03-3.07-1.9-3.07-1.9 0-2.2 1.46-2.2 2.97V21h-4z" fill="currentColor"/>',
		),
		'pinterest' => array(
			'label' => __( 'Pinterest', 'maison-vintique-elementor' ),
			'path'  => '<path d="M12 2a10 10 0 0 0-3.65 19.31c-.09-.78-.17-1.98.03-2.83.19-.79 1.2-5.05 1.2-5.05s-.3-.61-.3-1.52c0-1.42.82-2.48 1.85-2.48.87 0 1.3.66 1.3 1.45 0 .88-.57 2.2-.86 3.42-.24 1.02.51 1.86 1.52 1.86 1.83 0 3.23-1.93 3.23-4.7 0-2.46-1.77-4.18-4.29-4.18-2.92 0-4.64 2.19-4.64 4.46 0 .88.34 1.83.77 2.35a.3.3 0 0 1 .07.3l-.28 1.15c-.05.19-.15.23-.34.14-1.28-.6-2.08-2.47-2.08-3.98 0-3.24 2.35-6.21 6.79-6.21 3.56 0 6.33 2.54 6.33 5.94 0 3.54-2.23 6.39-5.33 6.39-1.04 0-2.02-.54-2.36-1.18l-.64 2.45c-.23.89-.86 2.01-1.28 2.69A10 10 0 1 0 12 2z" fill="currentColor"/>',
		),
		'youtube'   => array(
			'label' => __( 'YouTube', 'maison-vintique-elementor' ),
			'path'  => '<path d="M21.6 7.2a2.5 2.5 0 0 0-1.76-1.77C18.25 5 12 5 12 5s-6.25 0-7.84.43A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.76 1.77C5.75 19 12 19 12 19s6.25 0 7.84-.43a2.5 2.5 0 0 0 1.76-1.77A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.1V8.9l5.2 3.1z" fill="currentColor"/>',
		),
	);

	/**
	 * Filter the available networks — add one here to have it appear in the
	 * Customizer and the footer together.
	 */
	return apply_filters( 'mve_social_networks', $networks );
}

/**
 * The links actually set, in network order, skipping any left blank.
 *
 * @return array slug => url
 */
function mve_social_links() {
	$links = array();

	foreach ( mve_social_networks() as $slug => $network ) {
		$url = trim( (string) get_theme_mod( 'mve_social_' . $slug, '' ) );
		if ( '' !== $url ) {
			$links[ $slug ] = $url;
		}
	}

	/**
	 * Legacy/dev override. Returning slug => url here bypasses the Customizer.
	 */
	return apply_filters( 'mve_footer_social_links', $links );
}

/**
 * One social icon as inline SVG.
 *
 * @param string $slug Network slug.
 * @return string
 */
function mve_social_icon( $slug ) {
	$networks = mve_social_networks();
	if ( empty( $networks[ $slug ]['path'] ) ) {
		return '';
	}
	return '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
		. $networks[ $slug ]['path'] . '</svg>';
}

/**
 * Customizer panel: Appearance → Customize → Social Links.
 */
function mve_social_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'mve_social',
		array(
			'title'       => __( 'Social Links', 'maison-vintique-elementor' ),
			'priority'    => 90,
			'description' => __( 'Paste the full profile URL for each network. Leave a box empty to hide that icon from the footer.', 'maison-vintique-elementor' ),
		)
	);

	$priority = 10;
	foreach ( mve_social_networks() as $slug => $network ) {
		$setting = 'mve_social_' . $slug;

		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$setting,
			array(
				'label'       => $network['label'],
				'section'     => 'mve_social',
				'type'        => 'url',
				'priority'    => $priority,
				'input_attrs' => array( 'placeholder' => 'https://' ),
			)
		);

		$priority += 10;
	}
}
add_action( 'customize_register', 'mve_social_customizer' );
