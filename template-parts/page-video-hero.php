<?php
/**
 * template-parts/page-video-hero.php
 *
 * A full-bleed video band, the same shape as the homepage hero: the video
 * fills the width, a colour overlay sits on top so any text stays readable,
 * and the copy sits over the middle.
 *
 * Unlike template-parts/page-media.php — which puts an image or a video inside
 * the page's normal column — this one breaks out of the container edge to
 * edge.
 *
 * Usage:
 *   get_template_part( 'template-parts/page-video-hero', null, array(
 *       'prefix' => 'os',
 *   ) );
 *
 * Fields, all optional. The whole band is skipped unless there is a video or
 * an image to show:
 *   {prefix}_vhero_video    file (mp4) — plays muted and looped
 *   {prefix}_vhero_embed    url — YouTube / Vimeo, used when there is no mp4
 *   {prefix}_vhero_image    image — the poster, and the whole background when
 *                           there is no video at all
 *   {prefix}_vhero_colour   the overlay colour
 *   {prefix}_vhero_opacity  0-1, how strong that overlay is (default .55)
 *   {prefix}_vhero_eyebrow  text
 *   {prefix}_vhero_title    text
 *   {prefix}_vhero_text     textarea
 *   {prefix}_vhero_height   select: tall | medium | short
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvv_prefix = isset( $args['prefix'] ) ? $args['prefix'] : '';
if ( ! $mvv_prefix ) {
	return;
}

$mvv_video   = mve_field( $mvv_prefix . '_vhero_video' );
$mvv_embed   = mve_field( $mvv_prefix . '_vhero_embed' );
$mvv_image   = mve_field( $mvv_prefix . '_vhero_image' );
$mvv_colour  = mve_field( $mvv_prefix . '_vhero_colour' );
$mvv_opacity = mve_field( $mvv_prefix . '_vhero_opacity', '' );
$mvv_eyebrow = mve_field( $mvv_prefix . '_vhero_eyebrow' );
$mvv_title   = mve_field( $mvv_prefix . '_vhero_title' );
$mvv_text    = mve_field( $mvv_prefix . '_vhero_text' );
$mvv_height  = mve_field( $mvv_prefix . '_vhero_height', 'tall' );

$mvv_video_url = is_array( $mvv_video ) ? ( isset( $mvv_video['url'] ) ? $mvv_video['url'] : '' ) : (string) $mvv_video;
$mvv_image_url = mve_image_url( $mvv_image, array( 'mv-estate', 'large', 'full' ) );

// Nothing to show behind the words — skip the whole band rather than render a
// flat coloured strip.
if ( ! $mvv_video_url && ! $mvv_embed && ! $mvv_image_url ) {
	return;
}

$mvv_style = '';
if ( $mvv_image_url ) {
	$mvv_style .= "background-image:url('" . esc_url( $mvv_image_url ) . "');";
}
// Same reasoning as the homepage hero: without an overlay, white text over
// bright footage is unreadable, so a background always gets one by default.
$mvv_style .= '--mvv-overlay-opacity:' . ( '' === $mvv_opacity || null === $mvv_opacity ? '0.55' : esc_attr( $mvv_opacity ) ) . ';';
if ( $mvv_colour ) {
	$mvv_style .= '--mvv-overlay-colour:' . esc_attr( $mvv_colour ) . ';';
}

$mvv_classes = 'section mvv-hero mvv-hero--' . sanitize_html_class( $mvv_height );
$mvv_has_copy = ( $mvv_eyebrow || $mvv_title || $mvv_text );
?>
<section class="<?php echo esc_attr( $mvv_classes ); ?>" style="<?php echo esc_attr( $mvv_style ); ?>">

	<div class="mvv-hero__bg" aria-hidden="true">
		<?php if ( $mvv_video_url ) : ?>
			<?php // Muted and looped so browsers allow it to autoplay at all. ?>
			<video
				class="mvv-hero__video"
				autoplay
				muted
				loop
				playsinline
				preload="metadata"
				<?php if ( $mvv_image_url ) : ?>poster="<?php echo esc_url( $mvv_image_url ); ?>"<?php endif; ?>
			>
				<source src="<?php echo esc_url( $mvv_video_url ); ?>" type="video/mp4">
			</video>
		<?php elseif ( $mvv_embed ) : ?>
			<div class="mvv-hero__embed">
				<?php
				$mvv_html = wp_oembed_get( $mvv_embed );
				if ( $mvv_html ) {
					echo $mvv_html; // phpcs:ignore WordPress.Security.EscapeOutput -- oEmbed output is sanitised by WordPress.
				}
				?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $mvv_has_copy ) : ?>
		<div class="wrap mvv-hero__inner">
			<?php if ( $mvv_eyebrow ) : ?>
				<p class="eyebrow eyebrow--center eyebrow--on-dark"><?php echo esc_html( $mvv_eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( $mvv_title ) : ?>
				<h2 class="mvv-hero__title"><?php echo esc_html( $mvv_title ); ?></h2>
			<?php endif; ?>

			<?php if ( $mvv_text ) : ?>
				<p class="mvv-hero__text"><?php echo esc_html( $mvv_text ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</section>
