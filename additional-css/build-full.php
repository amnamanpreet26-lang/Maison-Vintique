<?php
/**
 * Build the pasteable stylesheet.
 *
 *   php additional-css/build-full.php
 *
 * Reads   _site-base.css  + mv-additional.css   (both kept fully commented,
 *                                                because they are the source
 *                                                you actually edit)
 * Writes  mv-customizer-full.css                (comments stripped, tidied
 *                                                indentation, one START/END
 *                                                marker per area)
 *
 * ARRANGES ONLY. No rule is removed, merged, reordered or rewritten — every
 * selector and every declaration comes out exactly as it went in, just with
 * the comments taken off and consistent indentation. CSS depends on source
 * order and on rules that look redundant but aren't, so nothing is dropped.
 *
 * @package maison-vintique-elementor
 */

$dir = __DIR__;

/* -------------------------------------------------------------------------
 * Tokenise a stylesheet into top-level nodes.
 * Returns a list of ['type' => comment|rule|at, 'text' => ..., 'sel' => ...,
 * 'body' => ...]. Nested at-rules (@media, @supports) keep their inner text
 * verbatim so nothing inside them can be mangled.
 * ---------------------------------------------------------------------- */
function mve_css_nodes( $css ) {
	$nodes = array();
	$len   = strlen( $css );
	$i     = 0;
	$buf   = '';

	while ( $i < $len ) {
		// Comment
		if ( '/' === $css[ $i ] && $i + 1 < $len && '*' === $css[ $i + 1 ] ) {
			$end = strpos( $css, '*/', $i + 2 );
			$end = ( false === $end ) ? $len : $end + 2;
			if ( '' !== trim( $buf ) ) {
				$nodes[] = array( 'type' => 'stray', 'text' => $buf );
			}
			$buf     = '';
			$nodes[] = array( 'type' => 'comment', 'text' => substr( $css, $i, $end - $i ) );
			$i       = $end;
			continue;
		}

		// Block
		if ( '{' === $css[ $i ] ) {
			$depth = 1;
			$j     = $i + 1;
			while ( $j < $len && $depth > 0 ) {
				if ( '/' === $css[ $j ] && $j + 1 < $len && '*' === $css[ $j + 1 ] ) {
					$j = strpos( $css, '*/', $j + 2 );
					$j = ( false === $j ) ? $len : $j + 2;
					continue;
				}
				if ( '"' === $css[ $j ] || "'" === $css[ $j ] ) {
					$q = $css[ $j ];
					$j++;
					while ( $j < $len && $css[ $j ] !== $q ) {
						$j += ( '\\' === $css[ $j ] ) ? 2 : 1;
					}
					$j++;
					continue;
				}
				if ( '{' === $css[ $j ] ) { $depth++; }
				if ( '}' === $css[ $j ] ) { $depth--; }
				$j++;
			}

			$sel  = trim( $buf );
			$body = substr( $css, $i + 1, $j - $i - 2 );
			$buf  = '';
			$i    = $j;

			$nodes[] = array(
				'type' => ( 0 === strpos( $sel, '@' ) ) ? 'at' : 'rule',
				'sel'  => $sel,
				'body' => $body,
			);
			continue;
		}

		$buf .= $css[ $i ];
		$i++;
	}

	if ( '' !== trim( $buf ) ) {
		$nodes[] = array( 'type' => 'stray', 'text' => $buf );
	}
	return $nodes;
}

/** Normalise a selector for comparison: collapse whitespace, tidy commas. */
function mve_norm_sel( $sel ) {
	$sel = preg_replace( '/\s+/', ' ', trim( $sel ) );
	$sel = preg_replace( '/\s*,\s*/', ',', $sel );
	return $sel;
}

/** Normalise a declaration block for comparison. */
function mve_norm_body( $body ) {
	$body = preg_replace( '!/\*.*?\*/!s', '', $body );
	$body = preg_replace( '/\s+/', ' ', trim( $body ) );
	$body = preg_replace( '/\s*([:;{}])\s*/', '$1', $body );
	return rtrim( $body, ';' );
}

/** Re-indent a declaration block. */
function mve_format_body( $body, $indent = "\t" ) {
	$body  = preg_replace( '!/\*.*?\*/!s', '', $body );
	$parts = array();
	foreach ( explode( ';', $body ) as $decl ) {
		$decl = trim( preg_replace( '/\s+/', ' ', $decl ) );
		if ( '' !== $decl ) {
			$parts[] = $indent . $decl . ';';
		}
	}
	return implode( "\n", $parts );
}

/* -------------------------------------------------------------------------
 * Which area does a rule belong to? Keyed off the section-header comments
 * already in the sources, so the markers land on real boundaries and nothing
 * has to move.
 * ---------------------------------------------------------------------- */
function mve_section_name( $comment ) {
	/*
	 * ONLY the big banner comments open a section — the ones fenced with a run
	 * of "=". The smaller `/* --- foo --- *\/` sub-headers are just dropped, so
	 * the output gets one START/END pair per area rather than dozens.
	 */
	if ( ! preg_match( '/={6,}/', $comment ) ) {
		return '';
	}

	$text = preg_replace( '!^/\*+|\*+/$!', '', $comment );

	// First line that isn't a rule of "=" characters.
	$line = '';
	foreach ( preg_split( '/\R/', $text ) as $candidate ) {
		$candidate = trim( preg_replace( '/\s+/u', ' ', $candidate ) );
		$candidate = trim( $candidate, "=* \t" );
		if ( '' !== $candidate ) {
			$line = $candidate;
			break;
		}
	}

	if ( '' === $line || mb_strlen( $line ) > 70 ) {
		return '';
	}

	// Drop the "— path/to/file.php" suffix these headers carry.
	$line = preg_replace( '/\s*[\x{2014}\x{2013}-]{1,2}\s*\S*\.php.*$/u', '', $line );
	$line = preg_replace( '/^\d+\.\s*/u', '', $line );

	/*
	 * NOTE: trim() with a multi-byte character in the charlist trims BYTES, so
	 * `trim( $line, " -—" )` cut an em dash in half and left a stray 0xA6 in
	 * the output. Use a /u regex instead.
	 */
	$line = preg_replace( '/^[\s\x{2014}\x{2013}-]+|[\s\x{2014}\x{2013}-]+$/u', '', $line );

	return ( '' === $line ) ? '' : mb_strtoupper( $line );
}

/* ---------------------------------------------------------------------- */

$sources = array( $dir . '/_site-base.css', $dir . '/mv-additional.css' );
foreach ( $sources as $file ) {
	if ( ! file_exists( $file ) ) {
		fwrite( STDERR, "missing: $file\n" );
		exit( 1 );
	}
}

$css   = implode( "\n\n", array_map( 'file_get_contents', $sources ) );
$nodes = mve_css_nodes( $css );

$out        = array();
$section    = '';
$comments   = 0;
$rules_kept = 0;

$close = function () use ( &$out, &$section ) {
	if ( '' !== $section ) {
		$out[]   = "/* ===== {$section} — END ===== */\n";
		$section = '';
	}
};

foreach ( $nodes as $idx => $node ) {

	if ( 'comment' === $node['type'] ) {
		$comments++;
		$name = mve_section_name( $node['text'] );
		if ( '' !== $name && $name !== $section ) {
			$close();
			$section = $name;
			$out[]   = "\n/* ===== {$section} — START ===== */\n";
		}
		continue; // every other comment is dropped
	}

	if ( 'stray' === $node['type'] ) {
		continue;
	}

	$rules_kept++;

	if ( 'at' === $node['type'] ) {
		// Re-indent the inner rules of @media / @supports one level.
		$inner  = mve_css_nodes( $node['body'] );
		$lines  = array();
		foreach ( $inner as $in ) {
			if ( 'rule' !== $in['type'] && 'at' !== $in['type'] ) {
				continue;
			}
			$lines[] = "\t" . mve_norm_sel( $in['sel'] ) . " {\n" . mve_format_body( $in['body'], "\t\t" ) . "\n\t}";
		}
		$out[] = mve_norm_sel( $node['sel'] ) . " {\n" . implode( "\n", $lines ) . "\n}\n";
		continue;
	}

	$out[] = mve_norm_sel( $node['sel'] ) . " {\n" . mve_format_body( $node['body'] ) . "\n}\n";
}
$close();

$header = "/* Maison Vintique — Additional CSS. Generated by additional-css/build-full.php — edit _site-base.css or mv-additional.css, not this file. */\n";

$result = $header . preg_replace( "/\n{3,}/", "\n\n", implode( "\n", $out ) );
file_put_contents( $dir . '/mv-customizer-full.css', rtrim( $result ) . "\n" );

printf(
	"mv-customizer-full.css: %d rules (all kept), %d comments stripped (%d bytes, %d lines)\n",
	$rules_kept,
	$comments,
	strlen( $result ),
	substr_count( $result, "\n" )
);
