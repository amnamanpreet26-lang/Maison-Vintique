<?php
/**
 * template-parts/journal-card.php
 *
 * One journal card. Used by archive-journal.php and by the "More from the
 * Journal" row on single-journal.php, so both stay identical.
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvj_id  = get_the_ID();
$mvj_cat = get_the_terms( $mvj_id, 'journal_category' );
$mvj_cat = ( $mvj_cat && ! is_wp_error( $mvj_cat ) ) ? $mvj_cat[0]->name : '';

$mvj_excerpt = get_the_excerpt();
$mvj_excerpt = $mvj_excerpt ? wp_trim_words( $mvj_excerpt, 20, '…' ) : '';

$mvj_image = has_post_thumbnail( $mvj_id ) ? get_the_post_thumbnail_url( $mvj_id, 'mv-card' ) : '';
?>
<a class="mvp-jcard" href="<?php the_permalink(); ?>">

	<div class="mvp-jcard__img<?php echo $mvj_image ? '' : ' is-empty'; ?>"
		<?php if ( $mvj_image ) : ?>style="background-image:url('<?php echo esc_url( $mvj_image ); ?>')"<?php endif; ?>>
	</div>

	<div class="mvp-jcard__body">
		<?php if ( $mvj_cat ) : ?>
			<div class="mvp-jcard__cat"><?php echo esc_html( $mvj_cat ); ?></div>
		<?php endif; ?>

		<h3><?php the_title(); ?></h3>

		<?php if ( $mvj_excerpt ) : ?>
			<p><?php echo esc_html( $mvj_excerpt ); ?></p>
		<?php endif; ?>
	</div>
</a>
