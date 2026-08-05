<?php
/**
 * template-parts/page-hero.php
 *
 * The dark centred banner at the top of every editorial page (Our Story,
 * Trade Partners, FAQ, Contact, Journal). One file so all of them stay
 * identical.
 *
 * Usage:
 *   get_template_part( 'template-parts/page-hero', null, array(
 *       'eyebrow' => 'Our Story',
 *       'title'   => 'A house of wine, a legacy of taste',
 *       'intro'   => 'Maison Vintique is…',   // plain text or safe HTML
 *       'prefix'  => 'os',                    // optional, see below
 *   ) );
 *
 * BACKGROUND IMAGE / VIDEO
 * ------------------------
 * Pass 'prefix' and the hero reads its own background fields, all optional:
 *
 *   {prefix}_hero_bg_image    image  — still background
 *   {prefix}_hero_bg_video    file   — mp4, plays behind the text, muted+looped
 *   {prefix}_hero_bg_overlay  number — 0-100, how dark the tint over it is
 *
 * With none of them set the hero renders exactly as before: the flat dark
 * panel. The overlay exists because white text over an untinted photo is
 * unreadable; it defaults to 55% and the editor can dial it per page.
 *
 * The video is muted and looped because browsers refuse to autoplay anything
 * with sound, and it carries the image as its poster so there is something on
 * screen while it buffers (and on the phones that decline to autoplay at all).
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvh_eyebrow = isset( $args['eyebrow'] ) ? $args['eyebrow'] : '';
$mvh_title   = isset( $args['title'] ) ? $args['title'] : get_the_title();
$mvh_intro   = isset( $args['intro'] ) ? $args['intro'] : '';
$mvh_prefix  = isset( $args['prefix'] ) ? $args['prefix'] : '';

/* ---------------------------------------------------------------------------
 * BACKGROUND — explicit args win, otherwise read the page's own ACF fields.
 * ------------------------------------------------------------------------- */
$mvh_bg_image = isset( $args['bg_image'] ) ? $args['bg_image'] : '';
$mvh_bg_video = isset( $args['bg_video'] ) ? $args['bg_video'] : '';
$mvh_overlay  = isset( $args['overlay'] ) ? $args['overlay'] : '';

if ( $mvh_prefix && function_exists( 'mve_field' ) ) {
	if ( '' === $mvh_bg_image ) {
		$mvh_bg_image = mve_field( $mvh_prefix . '_hero_bg_image' );
	}
	if ( '' === $mvh_bg_video ) {
		$mvh_bg_video = mve_field( $mvh_prefix . '_hero_bg_video' );
	}
	if ( '' === $mvh_overlay ) {
		$mvh_overlay = mve_field( $mvh_prefix . '_hero_bg_overlay' );
	}
}

// ACF image/file fields can come back as an array, an id or a bare url.
$mvh_image_url = '';
if ( $mvh_bg_image ) {
	if ( is_array( $mvh_bg_image ) ) {
		$mvh_image_url = isset( $mvh_bg_image['url'] ) ? $mvh_bg_image['url'] : '';
	} elseif ( is_numeric( $mvh_bg_image ) ) {
		$mvh_image_url = wp_get_attachment_image_url( (int) $mvh_bg_image, 'full' );
	} else {
		$mvh_image_url = $mvh_bg_image;
	}
}

$mvh_video_url = '';
if ( $mvh_bg_video ) {
	if ( is_array( $mvh_bg_video ) ) {
		$mvh_video_url = isset( $mvh_bg_video['url'] ) ? $mvh_bg_video['url'] : '';
	} elseif ( is_numeric( $mvh_bg_video ) ) {
		$mvh_video_url = wp_get_attachment_url( (int) $mvh_bg_video );
	} else {
		$mvh_video_url = $mvh_bg_video;
	}
}

$mvh_has_bg = ( $mvh_image_url || $mvh_video_url );

// 0-100 from the editor, 0-1 for CSS. Blank means "use the default", which is
// not the same as an explicit 0 (no tint at all) — hence the '' check.
$mvh_overlay_alpha = .55;
if ( '' !== $mvh_overlay && null !== $mvh_overlay ) {
	$mvh_overlay_alpha = max( 0, min( 100, (float) $mvh_overlay ) ) / 100;
}

$mvh_classes = 'section mvp-hero';
if ( $mvh_has_bg ) {
	$mvh_classes .= ' mvp-hero--bg';
}
?>
<section class="<?php echo esc_attr( $mvh_classes ); ?>">

	<?php if ( $mvh_has_bg ) : ?>
		<?php
		// The image is painted as the layer's own background so it stays put
		// under the video — which matters when prefers-reduced-motion hides
		// the video, or a phone declines to autoplay it.
		$mvh_bg_style = $mvh_image_url ? ' style="background-image:url(' . esc_url( $mvh_image_url ) . ')"' : '';
		?>
		<div class="mvp-hero__bg" aria-hidden="true"<?php echo $mvh_bg_style; // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_url above ?>>
			<?php if ( $mvh_video_url ) : ?>
				<video
					class="mvp-hero__bg-video"
					playsinline
					muted
					loop
					autoplay
					preload="metadata"
					tabindex="-1"
					<?php if ( $mvh_image_url ) : ?>poster="<?php echo esc_url( $mvh_image_url ); ?>"<?php endif; ?>
				>
					<source src="<?php echo esc_url( $mvh_video_url ); ?>" type="video/mp4">
				</video>
			<?php elseif ( $mvh_image_url ) : ?>
				<img class="mvp-hero__bg-image" src="<?php echo esc_url( $mvh_image_url ); ?>" alt="" loading="eager">
			<?php endif; ?>

			<span class="mvp-hero__scrim" style="opacity:<?php echo esc_attr( $mvh_overlay_alpha ); ?>"></span>
		</div>
	<?php endif; ?>

	<div class="wrap">
		<?php if ( $mvh_eyebrow ) : ?>
			<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvh_eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( $mvh_title ) : ?>
			<h1><?php echo esc_html( $mvh_title ); ?></h1>
		<?php endif; ?>

		<?php if ( $mvh_intro ) : ?>
			<p class="mvp-hero__intro"><?php echo wp_kses_post( $mvh_intro ); ?></p>
		<?php endif; ?>
	</div>
</section>
