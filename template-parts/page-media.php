<?php
/**
 * template-parts/page-media.php
 *
 * The image-or-video block used by Our Story and Trade Partners. One file so
 * both pages behave identically.
 *
 * Usage:
 *   get_template_part( 'template-parts/page-media', null, array(
 *       'prefix' => 'os',   // reads os_media_type / os_image / os_video / …
 *   ) );
 *
 * Fields, all optional — the whole block is skipped when nothing is set:
 *   {prefix}_media_type     select: image | video | embed
 *   {prefix}_image          image
 *   {prefix}_video          file (mp4) — self-hosted, plays inline, muted loop
 *   {prefix}_video_embed    oEmbed URL (YouTube / Vimeo)
 *   {prefix}_video_poster   image shown before a self-hosted video loads
 *   {prefix}_media_caption  text under the media
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvm_prefix = isset( $args['prefix'] ) ? $args['prefix'] : '';
if ( ! $mvm_prefix ) {
	return;
}

$mvm_type    = mve_field( $mvm_prefix . '_media_type', 'image' );
$mvm_image   = mve_field( $mvm_prefix . '_image' );
$mvm_video   = mve_field( $mvm_prefix . '_video' );
$mvm_embed   = mve_field( $mvm_prefix . '_video_embed' );
$mvm_poster  = mve_field( $mvm_prefix . '_video_poster' );
$mvm_caption = mve_field( $mvm_prefix . '_media_caption' );

// Work out what we can actually render, falling back sensibly.
$mvm_render = '';
if ( 'video' === $mvm_type && $mvm_video ) {
	$mvm_render = 'video';
} elseif ( 'embed' === $mvm_type && $mvm_embed ) {
	$mvm_render = 'embed';
} elseif ( $mvm_image ) {
	$mvm_render = 'image';
} elseif ( $mvm_video ) {
	$mvm_render = 'video';
} elseif ( $mvm_embed ) {
	$mvm_render = 'embed';
}

if ( ! $mvm_render ) {
	return;
}

$mvm_image_url = '';
if ( $mvm_image ) {
	$mvm_image_url = is_array( $mvm_image ) ? $mvm_image['url'] : $mvm_image;
}
$mvm_image_alt = ( is_array( $mvm_image ) && ! empty( $mvm_image['alt'] ) ) ? $mvm_image['alt'] : get_the_title();

$mvm_poster_url = '';
if ( $mvm_poster ) {
	$mvm_poster_url = is_array( $mvm_poster ) ? $mvm_poster['url'] : $mvm_poster;
}
$mvm_video_url = is_array( $mvm_video ) ? ( isset( $mvm_video['url'] ) ? $mvm_video['url'] : '' ) : $mvm_video;
?>
<section class="section mvp-sec mvp-media-sec">
	<div class="wrap">
		<figure class="mvp-media mvp-media--<?php echo esc_attr( $mvm_render ); ?>">

			<?php if ( 'image' === $mvm_render ) : ?>

				<img src="<?php echo esc_url( $mvm_image_url ); ?>" alt="<?php echo esc_attr( $mvm_image_alt ); ?>" loading="lazy">

			<?php elseif ( 'video' === $mvm_render ) : ?>

				<?php // Self-hosted: muted + loop so it can autoplay, controls for sound. ?>
				<video
					class="mvp-media__video"
					playsinline
					muted
					loop
					autoplay
					controls
					preload="metadata"
					<?php if ( $mvm_poster_url ) : ?>poster="<?php echo esc_url( $mvm_poster_url ); ?>"<?php endif; ?>
				>
					<source src="<?php echo esc_url( $mvm_video_url ); ?>" type="video/mp4">
				</video>

			<?php else : ?>

				<?php // YouTube / Vimeo — wp_oembed_get returns a ready iframe. ?>
				<div class="mvp-media__embed">
					<?php
					$mvm_html = wp_oembed_get( $mvm_embed );
					if ( $mvm_html ) {
						echo $mvm_html; // phpcs:ignore WordPress.Security.EscapeOutput -- oEmbed output is already sanitised by WordPress.
					} else {
						printf(
							'<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a></p>',
							esc_url( $mvm_embed )
						);
					}
					?>
				</div>

			<?php endif; ?>

			<?php if ( $mvm_caption ) : ?>
				<figcaption class="mvp-media__caption"><?php echo esc_html( $mvm_caption ); ?></figcaption>
			<?php endif; ?>

		</figure>
	</div>
</section>
