<?php
/**
 * Rebuild mv-customizer-full.css = the site's base CSS + this theme's block.
 *
 *   php additional-css/build-full.php
 *
 * WHY: the Customizer holds ONE stylesheet. `mv-additional.css` is only the
 * part this theme owns (so it stays reviewable in git); `_site-base.css` is
 * everything that was already in Additional CSS before it. Concatenating them
 * here means the pasteable file can never drift from the reviewed source.
 *
 * Not loaded by WordPress — it's a build script, run by hand.
 *
 * @package maison-vintique-elementor
 */

$dir  = __DIR__;
$base = $dir . '/_site-base.css';
$mine = $dir . '/mv-additional.css';
$out  = $dir . '/mv-customizer-full.css';

foreach ( array( $base, $mine ) as $file ) {
	if ( ! file_exists( $file ) ) {
		fwrite( STDERR, "missing: $file\n" );
		exit( 1 );
	}
}

$header = "/* ==========================================================================\n"
	. "   MAISON VINTIQUE — COMPLETE ADDITIONAL CSS\n"
	. "   --------------------------------------------------------------------------\n"
	. "   Paste this WHOLE file into Appearance → Customize → Additional CSS,\n"
	. "   replacing everything that is there.\n"
	. "\n"
	. "   It is generated — do not edit by hand. Edit either:\n"
	. "     _site-base.css      the styles that predate this theme's work\n"
	. "     mv-additional.css   the styles this theme adds\n"
	. "   then re-run:  php additional-css/build-full.php\n"
	. "   ========================================================================== */\n\n";

$css = $header
	. rtrim( file_get_contents( $base ) ) . "\n\n\n"
	. rtrim( file_get_contents( $mine ) ) . "\n";

file_put_contents( $out, $css );

printf(
	"wrote %s — %d bytes (%d lines)\n",
	basename( $out ),
	strlen( $css ),
	substr_count( $css, "\n" )
);
