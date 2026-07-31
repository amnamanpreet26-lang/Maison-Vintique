<?php

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Whether the current visitor may see prices and buy.
 *
 * One rule, shared with inc/woocommerce.php: logged in = yes, logged out = no.
 * No role check, no per-product setting.
 */
if ( ! function_exists( 'mv_is_trade_account' ) ) {
	function mv_is_trade_account() {
		return function_exists( 'mve_is_gated' ) ? ! mve_is_gated() : is_user_logged_in();
	}
}

while ( have_posts() ) :
	the_post();

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	$is_trade = mv_is_trade_account();

	/**
	 * ---------------------------------------------------------------
	 * FIELD SOURCES — three different places, don't mix them up:
	 * 1) ACF fields (group_mv_wine_details)  -> get_field( 'name' )
	 * 2) WooCommerce global attributes        -> $product->get_attribute( 'slug' )
	 * 3) WooCommerce taxonomy terms            -> get_the_terms( ... )
	 * ---------------------------------------------------------------
	 */

	// 1) ACF fields — names must match group_mv_wine_details exactly.
	$producer_obj   = get_field( 'producer' ); // post_object -> WP_Post|false
	$producer       = $producer_obj ? $producer_obj->post_title : '';

	$awards_raw     = get_field( 'awards' ); // textarea, one award per line
	$awards         = $awards_raw ? array_filter( array_map( 'trim', explode( "\n", $awards_raw ) ) ) : array();

	$vintage        = get_field( 'vintage_year' );
	$abv            = get_field( 'abv' ); // number, append % ourselves
	$allergens      = get_field( 'allergens' );
	$bottle         = get_field( 'bottle_size' ) ? get_field( 'bottle_size' ) : '75cl';
	$case_format    = get_field( 'case_format' ) ? get_field( 'case_format' ) : '6 x 75cl';
	$bottles_per_case = get_field( 'bottles_per_case' ) ? get_field( 'bottles_per_case' ) : 6;
	$sustainability = get_field( 'sustainability' );
	$serving        = get_field( 'serving' );
	$technical_sheet = get_field( 'technical_sheet' ); // file URL
	$sell_sheet       = get_field( 'sell_sheet' );      // file URL

	// 2) WooCommerce global attribute — Grape is seeded as an attribute, not an ACF field.
	$grape  = $product->get_attribute( 'grape' );
	$region = $product->get_attribute( 'region' );

	$categories = wc_get_product_category_list( get_the_ID(), ' · ' );

	// Gallery: main image first, then remaining gallery image ids.
	$gallery_ids = $product->get_gallery_image_ids();
	$main_img_id = $product->get_image_id();
	if ( $main_img_id ) {
		array_unshift( $gallery_ids, $main_img_id );
	}
	$gallery_ids = array_values( array_unique( array_filter( $gallery_ids ) ) );
	?>

	<section class="view" id="v-pdp"><div class="wrap">

		<div class="crumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">Wines</a>
			<?php if ( $categories ) : ?>
				/ <span><?php echo wp_kses_post( $categories ); ?></span>
			<?php endif; ?>
			/ <span><?php the_title(); ?></span>
		</div>

		<div class="pdp">

			<div>
				<div class="pdp-media" id="pdpMedia">
					<?php if ( ! empty( $gallery_ids ) ) : ?>
						<?php echo wp_get_attachment_image( $gallery_ids[0], 'large', false, array( 'id' => 'pdpMainImg' ) ); ?>
					<?php else : ?>
						<?php echo wc_placeholder_img( 'large' ); ?>
					<?php endif; ?>
				</div>

				<?php if ( count( $gallery_ids ) > 1 ) : ?>
					<div class="pdp-thumbs" id="pdpThumbs">
						<?php foreach ( $gallery_ids as $i => $img_id ) :
							$full_url = wp_get_attachment_image_url( $img_id, 'large' );
							?>
							<span class="<?php echo 0 === $i ? 'on' : ''; ?>" data-full="<?php echo esc_url( $full_url ); ?>">
								<?php echo wp_get_attachment_image( $img_id, 'thumbnail' ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="pdp-info">

				<p class="cat"><?php
					$mve_colour_terms  = get_the_terms( get_the_ID(), 'wine_colour' );
					$mve_region_terms  = get_the_terms( get_the_ID(), 'wine_region' );
					$mve_country_terms = get_the_terms( get_the_ID(), 'wine_country' );

					$mve_colour  = ( $mve_colour_terms && ! is_wp_error( $mve_colour_terms ) ) ? $mve_colour_terms[0]->name : '';
					$mve_region  = ( $mve_region_terms && ! is_wp_error( $mve_region_terms ) ) ? $mve_region_terms[0]->name : '';
					$mve_country = ( $mve_country_terms && ! is_wp_error( $mve_country_terms ) ) ? $mve_country_terms[0]->name : '';

					$mve_cat_parts = array_filter( array( $mve_colour, $mve_region, $mve_country ) );

					echo esc_html( implode( ' · ', $mve_cat_parts ) );
					?></p>

				<h1><?php the_title(); ?></h1>

				<?php if ( $producer ) : ?>
					<p class="prod"><?php echo esc_html( $producer ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $awards ) ) : ?>
					<div class="awards">
						<?php foreach ( $awards as $award ) : ?>
							<span><?php echo esc_html( $award ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				
								<?php if ( $product->get_short_description() ) : ?>
					<p class="pdp-desc"><?php echo wp_kses_post( $product->get_short_description() ); ?></p>
				<?php endif; ?>

				<div class="pdp-stockline">
					<span class="stock <?php echo $product->is_in_stock() ? 'available' : 'out'; ?>" style="position:static">
						<?php echo $product->is_in_stock() ? esc_html__( 'Available', 'maison-vintique' ) : esc_html__( 'Out of stock', 'maison-vintique' ); ?>
					</span>
					<?php if ( ! $is_trade ) : ?>
						<span class="trade-note" style="margin:0"><?php esc_html_e( 'Trade pricing on login', 'maison-vintique' ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $is_trade ) : ?>
					<p class="pdp-price">
						<?php echo wp_kses_post( $product->get_price_html() ); ?>
						<small><?php esc_html_e( 'per case', 'maison-vintique' ); ?></small>
					</p>
				<?php endif; ?>

				<?php
				/*
				 * NOTE: a second <div class="tabpane on" id="tab-desc"> used to sit
				 * here, duplicating the description that the "Tasting & Story" tab
				 * below already shows. Two elements shared the id "tab-desc", so
				 * getElementById() in the tab script matched THIS one instead of the
				 * real pane — which is why switching tabs misbehaved. Removed.
				 */
				?>

				<?php if ( $is_trade ) : ?>

					<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
						<div class="buybar">
							<div class="qty">
								<button type="button" class="qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'maison-vintique' ); ?>">−</button>
								<?php
								woocommerce_quantity_input(
									array(
										'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
										'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
										'input_value' => 1,
									),
									$product
								);
								?>
								<button type="button" class="qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'maison-vintique' ); ?>">+</button>
							</div>

							<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="btn btn-p">
								<?php esc_html_e( 'Add to Case', 'maison-vintique' ); ?>
							</button>
							<a class="btn btn-o" href="<?php echo esc_url( add_query_arg( 'enquire', $product->get_id(), wc_get_page_permalink( 'shop' ) ) ); ?>">
								<?php esc_html_e( 'Enquire', 'maison-vintique' ); ?>
							</a>
						</div>
					</form>

				<?php else : ?>

					<div class="buybar">
						<a class="btn btn-p" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'View Trade Pricing', 'maison-vintique' ); ?></a>
						<a class="btn btn-o" href="<?php echo esc_url( add_query_arg( 'enquire', $product->get_id(), wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Enquire', 'maison-vintique' ); ?></a>
					</div>

				<?php endif; ?>

				<p class="case-note">
					<?php
					printf(
						/* translators: %s: case format, e.g. "6 x 75cl" */
						esc_html__( 'Sold by the case (%s). Pricing, stock &amp; technical documents available to approved trade accounts.', 'maison-vintique' ),
						esc_html( $case_format )
					);
					?>
				</p>

				<div class="pdp-meta">
					<?php
$mve_grape_terms = wp_get_post_terms(
    get_the_ID(),
    'wine_grape',
    array(
        'fields' => 'names',
    )
);

$mve_grape_terms = implode( ', ', $mve_grape_terms );
?>

<?php if ( $mve_grape_terms ) : ?>
    <div>
        <span><?php esc_html_e( 'Grape', 'maison-vintique' ); ?></span>
        <b><?php echo esc_html( $mve_grape_terms ); ?></b>
    </div>
<?php endif; ?>
					
					
					<?php $mve_region_terms = get_the_term_list( get_the_ID(),
    'wine_region',
    '',
    ', ',
    ''
);
?>

<?php if ( $mve_region_terms ) : ?>
    <div>
        <span><?php esc_html_e( 'Region', 'maison-vintique' ); ?></span>
        <b><?php echo wp_kses_post( $mve_region_terms ); ?></b>
    </div>
<?php endif; ?>
					<?php if ( $vintage ) : ?><div><span><?php esc_html_e( 'Vintage', 'maison-vintique' ); ?></span><b><?php echo esc_html( $vintage ); ?></b></div><?php endif; ?>
					<?php if ( $abv ) : ?><div><span><?php esc_html_e( 'ABV', 'maison-vintique' ); ?></span><b><?php echo esc_html( $abv ); ?>%</b></div><?php endif; ?>
					<div><span><?php esc_html_e( 'Bottle', 'maison-vintique' ); ?></span><b><?php echo esc_html( $bottle ); ?></b></div>
					<?php if ( $case_format ) : ?><div><span><?php esc_html_e( 'Case', 'maison-vintique' ); ?></span><b><?php echo esc_html( $case_format ); ?></b></div><?php endif; ?>
					<?php if ( $allergens ) : ?><div><span><?php esc_html_e( 'Allergens', 'maison-vintique' ); ?></span><b><?php echo esc_html( $allergens ); ?></b></div><?php endif; ?>
					<div><span><?php esc_html_e( 'Stock', 'maison-vintique' ); ?></span><b><?php echo $product->is_in_stock() ? esc_html__( 'In stock', 'maison-vintique' ) : esc_html__( 'Out of stock', 'maison-vintique' ); ?></b></div>
				</div>

			</div>
		</div>

		<div class="tabs">
			<div class="tabnav">
				<button class="on" data-tab="desc"><?php esc_html_e( 'Tasting & Story', 'maison-vintique' ); ?></button>
				<button data-tab="tech"><?php esc_html_e( 'Technical', 'maison-vintique' ); ?></button>
				<button data-tab="dl"><?php esc_html_e( 'Downloads', 'maison-vintique' ); ?></button>
			</div>

			<div class="tabpane on" id="tab-desc">
				<?php
				// Prefer the ACF Tasting Notes field; fall back to the WC editor description.
				$tasting_notes = get_field( 'tasting_notes' );
				echo wp_kses_post( $tasting_notes ? $tasting_notes : wpautop( $product->get_description() ) );
				?>
			</div>

			<div class="tabpane" id="tab-tech">
				<div class="pdp-meta" style="grid-template-columns:1fr;max-width:420px;border:0;padding:0">
				<?php
$appellation = get_field( 'appellation' );
?>

<?php if ( $appellation ) : ?>
    <div>
        <span><?php esc_html_e( 'Appellation', 'maison-vintique' ); ?></span>
        <b><?php echo esc_html( $appellation ); ?></b>
    </div>
<?php endif; ?>
					<?php
$closure = get_field( 'closure' );
?>

<?php if ( $closure ) : ?>
    <div>
        <span><?php esc_html_e( 'Closure', 'maison-vintique' ); ?></span>
        <b><?php echo esc_html( $closure ); ?></b>
    </div>
<?php endif; ?>
					<div><span><?php esc_html_e( 'Bottles per case', 'maison-vintique' ); ?></span><b><?php echo esc_html( $bottles_per_case ); ?></b></div>
					<?php if ( $sustainability ) : ?><div><span><?php esc_html_e( 'Sustainability', 'maison-vintique' ); ?></span><b><?php echo esc_html( $sustainability ); ?></b></div><?php endif; ?>
					<?php if ( $serving ) : ?><div><span><?php esc_html_e( 'Serving', 'maison-vintique' ); ?></span><b><?php echo esc_html( $serving ); ?></b></div><?php endif; ?>
				</div>
			</div>

			<div class="tabpane" id="tab-dl">
				<?php if ( $is_trade ) : ?>
					<?php if ( $technical_sheet ) : ?>
						<div class="dl">
							DOC
							<div><b><?php esc_html_e( 'Technical Sheet', 'maison-vintique' ); ?></b><div style="font-size:12px;color:var(--taupe)">PDF</div></div>
							<a class="btn btn-o" style="margin-left:auto;padding:8px 14px" href="<?php echo esc_url( $technical_sheet ); ?>" download>
								<?php esc_html_e( 'Download', 'maison-vintique' ); ?>
							</a>
						</div>
					<?php endif; ?>
					<?php if ( $sell_sheet ) : ?>
						<div class="dl">
							DOC
							<div><b><?php esc_html_e( 'Sell Sheet', 'maison-vintique' ); ?></b><div style="font-size:12px;color:var(--taupe)">PDF</div></div>
							<a class="btn btn-o" style="margin-left:auto;padding:8px 14px" href="<?php echo esc_url( $sell_sheet ); ?>" download>
								<?php esc_html_e( 'Download', 'maison-vintique' ); ?>
							</a>
						</div>
					<?php endif; ?>
					<?php if ( ! $technical_sheet && ! $sell_sheet ) : ?>
						<p style="font-size:12.5px;color:var(--taupe)"><?php esc_html_e( 'No documents uploaded for this wine yet.', 'maison-vintique' ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<div class="dl">
						DOC
						<div><b><?php esc_html_e( 'Technical Sheet', 'maison-vintique' ); ?></b><div style="font-size:12px;color:var(--taupe)">PDF</div></div>
						<button class="btn btn-o" style="margin-left:auto;padding:8px 14px" disabled><?php esc_html_e( 'Trade only', 'maison-vintique' ); ?></button>
					</div>
					<div class="dl">
						DOC
						<div><b><?php esc_html_e( 'Sell Sheet', 'maison-vintique' ); ?></b><div style="font-size:12px;color:var(--taupe)">PDF</div></div>
						<button class="btn btn-o" style="margin-left:auto;padding:8px 14px" disabled><?php esc_html_e( 'Trade only', 'maison-vintique' ); ?></button>
					</div>
					<div class="gate">
						<b><?php esc_html_e( 'Trade documents are locked', 'maison-vintique' ); ?></b>
						<p><?php esc_html_e( 'Log in with an approved trade account to download technical sheets, sell sheets and hi-res imagery.', 'maison-vintique' ); ?></p>
						<a class="btn btn-p" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Trade Login', 'maison-vintique' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php
		// "You may also like" — renders the SAME card as the homepage portfolio
		// and the shop archive (template-parts/wine-card.php).
		$related_ids = wc_get_related_products( $product->get_id(), 4 );

		// Keep a handle on the wine being viewed: the shared card partial writes
		// to $GLOBALS['product'], so it has to be restored afterwards or anything
		// below this section would be looking at the last related wine.
		$mve_current_product = $product;

		if ( $related_ids ) :
			?>
			<div class="related" style="padding-bottom:70px">
				<h2><?php esc_html_e( 'You may also like', 'maison-vintique' ); ?></h2>
				<div class="pgrid" id="related">
					<?php
					foreach ( $related_ids as $related_id ) :
						$related = wc_get_product( $related_id );
						if ( ! $related ) {
							continue;
						}
						// The partial reads the global $product and the global post.
						$GLOBALS['post'] = get_post( $related_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						setup_postdata( $GLOBALS['post'] );

						$GLOBALS['product'] = $related;
						$product            = $related;

						get_template_part( 'template-parts/wine-card' );
					endforeach;

					wp_reset_postdata();
					$GLOBALS['product'] = $mve_current_product;
					$product            = $mve_current_product;
					?>
				</div>
			</div>
		<?php endif; ?>

	</div></section>

	<script>
	/* Quantity stepper. The tab switching, the "#tab-tech" deep link and the
	   gallery all moved to assets/js/main.js so they load on every page and
	   can't be skipped if this inline block ever fails to print. */
	(function(){
		var qtyWrap = document.querySelector('.qty');
		if (!qtyWrap) { return; }

		var input = qtyWrap.querySelector('input.qty');
		var minus = qtyWrap.querySelector('.qty-minus');
		var plus  = qtyWrap.querySelector('.qty-plus');
		if (!input || !minus || !plus) { return; }

		minus.addEventListener('click', function(){
			var val = parseInt(input.value, 10) || 1;
			var min = parseInt(input.getAttribute('min'), 10) || 1;
			input.value = Math.max(min, val - 1);
			input.dispatchEvent(new Event('change'));
		});
		plus.addEventListener('click', function(){
			var val = parseInt(input.value, 10) || 1;
			input.value = val + 1;
			input.dispatchEvent(new Event('change'));
		});
	})();
	</script>

	<?php
endwhile;

get_footer( 'shop' );