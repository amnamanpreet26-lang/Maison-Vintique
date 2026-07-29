<?php
/**
 * template-parts/page-hero.php
 *
 * The dark centred banner at the top of every editorial page (Our Story,
 * For the Trade, FAQ, Contact, Journal). One file so all five stay identical.
 *
 * Usage:
 *   get_template_part( 'template-parts/page-hero', null, array(
 *       'eyebrow' => 'Our Story',
 *       'title'   => 'A house of wine, a legacy of taste',
 *       'intro'   => 'Maison Vintique is…',   // plain text or safe HTML
 *   ) );
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mvh_eyebrow = isset( $args['eyebrow'] ) ? $args['eyebrow'] : '';
$mvh_title   = isset( $args['title'] ) ? $args['title'] : get_the_title();
$mvh_intro   = isset( $args['intro'] ) ? $args['intro'] : '';
?>
<section class="section mvp-hero">
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
