<?php
/**
 * woocommerce/archive-product.php
 *
 * The Shop page ("Wine Portfolio"). WooCommerce loads this file automatically
 * for the shop page and any product taxonomy archive — see the note at the
 * bottom of this file.
 *
 * Uses:
 *  - template-parts/content-product-wine.php  (one wine card — forwards to
 *    template-parts/wine-card.php, the SAME card the homepage
 *    "Curated Portfolio" grid renders, so the two always match)
 *  - template-parts/shop-filters.php          (sidebar filters)
 *  - inc/shop-query.php                       (sorting + filter query logic)
 *  - inc/woocommerce.php                      (trade price gating — already in theme)
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mve_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';

global $wp_query;
$mve_per_page   = (int) $wp_query->get( 'posts_per_page' );
$mve_total      = (int) $wp_query->found_posts;
$mve_paged      = max( 1, (int) ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ) );
$mve_range_from = $mve_total ? ( ( $mve_paged - 1 ) * $mve_per_page ) + 1 : 0;
$mve_range_to   = min( $mve_paged * $mve_per_page, $mve_total );

// Same dark banner as Our Story / Producers / Journal — one shared partial.
get_template_part(
	'template-parts/page-hero',
	null,
	array(
		'eyebrow' => __( 'The Collection', 'maison-vintique-elementor' ),
		'title'   => woocommerce_page_title( false ),
		'intro'   => get_theme_mod( 'mve_shop_subtitle', __( 'Curated estates, filterable by everything that matters.', 'maison-vintique-elementor' ) ),
	)
);
?>

<main>
<section class="section shop-archive">
<div class="wrap">

	<div class="crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <span><?php woocommerce_page_title(); ?></span>
	</div>

	<div class="shop">

		<aside class="filters">
			<?php get_template_part( 'template-parts/shop', 'filters' ); ?>
		</aside>

		<div>
			<div class="shop-toolbar">
				<span class="cnt">
					Showing <b><?php echo esc_html( $mve_range_from . '–' . $mve_range_to ); ?></b> of <b><?php echo esc_html( $mve_total ); ?></b> wines
				</span>

				<form method="get" id="mve-sort-form">
					<?php
					foreach ( $_GET as $mve_key => $mve_value ) {
						if ( 'orderby' === $mve_key ) {
							continue;
						}
						foreach ( (array) $mve_value as $mve_v ) {
							printf(
								'<input type="hidden" name="%s%s" value="%s">',
								esc_attr( $mve_key ),
								is_array( $mve_value ) ? '[]' : '',
								esc_attr( $mve_v )
							);
						}
					}
					?>
					<select class="sel" name="orderby" onchange="document.getElementById('mve-sort-form').submit()">
						<option value="menu_order" <?php selected( $mve_orderby, 'menu_order' ); ?>>Sort: Featured</option>
						<option value="price"      <?php selected( $mve_orderby, 'price' ); ?>>Price: low to high</option>
						<option value="price-desc" <?php selected( $mve_orderby, 'price-desc' ); ?>>Price: high to low</option>
						<option value="vintage"    <?php selected( $mve_orderby, 'vintage' ); ?>>Vintage: newest</option>
						<option value="title"      <?php selected( $mve_orderby, 'title' ); ?>>Name A–Z</option>
					</select>
				</form>
			</div>

			<div class="pgrid" id="pgrid">
				<?php
				if ( woocommerce_product_loop() ) :
					while ( have_posts() ) :
						the_post();
						// Renders template-parts/wine-card.php — badges, "View Wine"
						// and the two inline links, identical to the homepage grid.
						get_template_part( 'template-parts/content', 'product-wine' );
					endwhile;
				else :
					?>
					<p class="no-wines">No wines match your filters — try widening your search.</p>
					<?php
				endif;
				?>
			</div>

			<div class="pager">
				<?php
				$mve_big   = 999999999;
				$mve_links = paginate_links(
					array(
						'base'      => str_replace( $mve_big, '%#%', esc_url( get_pagenum_link( $mve_big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $mve_paged,
						'total'     => (int) $wp_query->max_num_pages,
						'prev_text' => '‹',
						'next_text' => '›',
						'type'      => 'array',
					)
				);
				if ( $mve_links ) {
					foreach ( $mve_links as $mve_link ) {
						echo wp_kses_post( str_replace( 'current', 'on', $mve_link ) );
					}
				}
				?>
			</div>
		</div>

	</div>
</div>
</section>
</main>

<?php
get_footer();

/**
 * ---------------------------------------------------------------------
 * HOW THIS FILE GETS LOADED
 * ---------------------------------------------------------------------
 * Nothing needs to "call" this file. WooCommerce's template loader watches
 * for is_shop() / is_post_type_archive('product') and automatically prefers
 * yourtheme/woocommerce/archive-product.php over its own default the moment
 * this file exists here — that's the entire mechanism.
 */
