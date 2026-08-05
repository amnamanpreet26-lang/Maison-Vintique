<?php
/**
 * template-parts/page-split.php
 *
 * The "about us" section — copy on one side, image or video on the other.
 * Used by Our Story and Trade Partners.
 *
 * Usage:
 *   get_template_part( 'template-parts/page-split', null, array(
 *       'prefix' => 'os',   // reads os_split_title / os_split_body / …
 *   ) );
 *
 * The media half is template-parts/page-media.php in 'bare' mode, so the
 * image / self-hosted video / YouTube-Vimeo embed logic lives in exactly one
 * place and this section can never drift away from the full-width block.
 *
 * Fields, all optional — the whole section is skipped when there is neither
 * copy nor media:
 *   {prefix}_split_eyebrow   text
 *   {prefix}_split_title     text
 *   {prefix}_split_body      wysiwyg
 *   {prefix}_split_cta_label text
 *   {prefix}_split_cta_link  url
 *   {prefix}_split_side      select: right | left  (which side the media sits)
 *   …plus the media fields read by page-media.php: {prefix}_media_type,
 *   {prefix}_image, {prefix}_video, {prefix}_video_embed, {prefix}_video_poster,
 *   {prefix}_media_caption
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvsp_prefix = isset( $args['prefix'] ) ? $args['prefix'] : '';
if ( ! $mvsp_prefix ) {
	return;
}

$mvsp_eyebrow   = mve_field( $mvsp_prefix . '_split_eyebrow' );
$mvsp_title     = mve_field( $mvsp_prefix . '_split_title' );
$mvsp_body      = mve_field( $mvsp_prefix . '_split_body' );
$mvsp_cta_label = mve_field( $mvsp_prefix . '_split_cta_label' );
$mvsp_cta_link  = mve_field( $mvsp_prefix . '_split_cta_link' );
$mvsp_side      = mve_field( $mvsp_prefix . '_split_side', 'right' );

// Is there any media to show? Ask the same fields page-media.php reads, so the
// two can't disagree about whether the media half exists.
$mvsp_has_media = (bool) (
	mve_field( $mvsp_prefix . '_image' )
	|| mve_field( $mvsp_prefix . '_video' )
	|| mve_field( $mvsp_prefix . '_video_embed' )
);

$mvsp_has_copy = (bool) ( $mvsp_eyebrow || $mvsp_title || $mvsp_body || $mvsp_cta_label );

if ( ! $mvsp_has_media && ! $mvsp_has_copy ) {
	return;
}

// With only one half filled in, let it run the full width rather than leaving
// a blank column.
$mvsp_classes = 'mvp-split';
if ( ! $mvsp_has_media || ! $mvsp_has_copy ) {
	$mvsp_classes .= ' mvp-split--single';
} elseif ( 'left' === $mvsp_side ) {
	$mvsp_classes .= ' mvp-split--media-left';
}
?>
<section class="section mvp-sec mvp-split-sec">
	<div class="wrap">
		<div class="<?php echo esc_attr( $mvsp_classes ); ?>">

			<?php if ( $mvsp_has_copy ) : ?>
				<div class="mvp-split__copy">
					<?php if ( $mvsp_eyebrow ) : ?>
						<p class="eyebrow"><?php echo esc_html( $mvsp_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $mvsp_title ) : ?>
						<h2 class="mvp-split__title"><?php echo esc_html( $mvsp_title ); ?></h2>
					<?php endif; ?>

					<?php if ( $mvsp_body ) : ?>
						<div class="mvp-split__body"><?php echo wp_kses_post( $mvsp_body ); ?></div>
					<?php endif; ?>

					<?php if ( $mvsp_cta_label ) : ?>
						<a class="btn btn--primary mvp-split__cta" href="<?php echo esc_url( $mvsp_cta_link ? $mvsp_cta_link : '#' ); ?>">
							<?php echo esc_html( $mvsp_cta_label ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $mvsp_has_media ) : ?>
				<div class="mvp-split__media">
					<?php
					get_template_part(
						'template-parts/page-media',
						null,
						array(
							'prefix' => $mvsp_prefix,
							'bare'   => true,
						)
					);
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
