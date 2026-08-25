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

				<?php
				/*
				 * Awards. The card shows a single "Award Winning" medallion
				 * because there is no room for more; here there is, so every
				 * line of the ACF field gets its own medal and its full text.
				 */
				if ( ! empty( $awards ) ) :
					?>
					<div class="mv-awards">
						<p class="mv-awards__label"><?php esc_html_e( 'Awards &amp; recognition', 'maison-vintique' ); ?></p>
						<ul class="mv-awards__list">
							<?php foreach ( $awards as $award ) : ?>
								<li class="mv-awards__item">
									<svg class="mv-awards__medal" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<circle cx="12" cy="9" r="5.4" fill="none" stroke="currentColor" stroke-width="1.5"/>
										<path d="M8.4 13.4 6.6 21l5.4-2.7 5.4 2.7-1.8-7.6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
									</svg>
									<span class="mv-awards__text"><?php echo esc_html( $award ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				
								<?php if ( $product->get_short_description() ) : ?>
					<p class="pdp-desc"><?php echo wp_kses_post( $product->get_short_description() ); ?></p>
				<?php endif; ?>

				<div class="pdp-stockline">
					<?php // Stock is trade information — hidden until the visitor logs in. ?>
					<?php if ( $is_trade ) : ?>
						<span class="stock <?php echo $product->is_in_stock() ? 'available' : 'out'; ?>" style="position:static">
							<?php echo $product->is_in_stock() ? esc_html__( 'Available', 'maison-vintique' ) : esc_html__( 'Out of stock', 'maison-vintique' ); ?>
						</span>
					<?php endif; ?>
					<?php if ( ! $is_trade ) : ?>
						<span class="trade-note" style="margin:0"><?php esc_html_e( 'Sign in to view trade pricing', 'maison-vintique' ); ?></span>
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
						<?php $mve_qty_rules = mve_quantity_rules( $product ); ?>
						<div class="buybar">
							<?php
							/*
							 * The rules travel to the browser on the wrapper. The
							 * stepper puts them back on the input before it does
							 * anything else, so the box counts correctly even if a
							 * plugin, or a cached copy of this page, printed a
							 * different step into the markup.
							 */
							?>
							<div class="qty" data-min="<?php echo esc_attr( $mve_qty_rules['min'] ); ?>" data-step="<?php echo esc_attr( $mve_qty_rules['step'] ); ?>">
								<button type="button" class="qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'maison-vintique' ); ?>">−</button>
								<?php
								/*
								 * min_value and input_value are deliberately NOT set
								 * here. mve_min_order_qty() in inc/woocommerce.php
								 * owns both: it reads the case size off the product
								 * and opens the box on it. Hard-coding 1 here meant
								 * the box started below its own minimum, which is
								 * what made the first press of + jump to the case
								 * size and the second one double it.
								 */
								woocommerce_quantity_input(
									array(
										'max_value' => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
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
					<?php if ( $is_trade ) : ?><div><span><?php esc_html_e( 'Stock', 'maison-vintique' ); ?></span><b><?php echo $product->is_in_stock() ? esc_html__( 'In stock', 'maison-vintique' ) : esc_html__( 'Out of stock', 'maison-vintique' ); ?></b></div><?php endif; ?>
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
				
					<?php if ( ! $technical_sheet ) : ?>
						<p style="font-size:12.5px;color:var(--taupe)"><?php esc_html_e( 'No documents uploaded for this wine yet.', 'maison-vintique' ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<div class="dl">
						DOC
						<div><b><?php esc_html_e( 'Technical Sheet', 'maison-vintique' ); ?></b><div style="font-size:12px;color:var(--taupe)">PDF</div></div>
						<button class="btn btn-o" style="margin-left:auto;padding:8px 14px" disabled><?php esc_html_e( 'Trade only', 'maison-vintique' ); ?></button>
					</div>

					<div class="gate">
						<b><?php esc_html_e( 'Trade documents are locked', 'maison-vintique' ); ?></b>
						<p><?php esc_html_e( 'Log in with an approved trade account to download technical sheets and hi-res imagery.', 'maison-vintique' ); ?></p>
						<a class="btn btn-p" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Trade Login', 'maison-vintique' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php
		// "You may also like" — renders the SAME card as the homepage portfolio
		// and the shop archive (template-parts/wine-card.php).
		$related_ids = wc_get_related_products( $product->get_id(), 3 );

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
	/* Product page behaviour is INLINE on purpose.
	   It previously lived in assets/js/main.js, but that file is cached by the
	   browser, so until the cache cleared the page had no tab script at all and
	   the tabs stopped responding. Inline markup can never go stale. */
	(function () {
		'use strict';

		/* ---- Tabs ---- */
		var tabButtons = document.querySelectorAll('.tabnav button');
		var tabPanes   = document.querySelectorAll('.tabpane');

		function activateTab(name) {
			var pane = document.getElementById('tab-' + name);
			if (!pane) { return false; }
			Array.prototype.forEach.call(tabButtons, function (b) {
				b.classList.toggle('on', b.getAttribute('data-tab') === name);
			});
			Array.prototype.forEach.call(tabPanes, function (p) {
				p.classList.toggle('on', p === pane);
			});
			return true;
		}

		Array.prototype.forEach.call(tabButtons, function (btn) {
			btn.addEventListener('click', function () {
				activateTab(btn.getAttribute('data-tab'));
			});
		});

		/* Open a tab straight from the URL. Accepts both forms, because a bare
		   #hash is easy for a plugin or a redirect to drop:
		     /wine/xyz/#tab-tech    <- what the wine cards link to
		     /wine/xyz/?tab=tech    <- survives anything that strips fragments */
		function openTabFromUrl(scroll) {
			var name = '';
			var hash = /^#tab-([\w-]+)$/.exec(window.location.hash || '');
			if (hash) {
				name = hash[1];
			} else {
				var q = /[?&]tab=([\w-]+)/.exec(window.location.search || '');
				if (q) { name = q[1]; }
			}
			if (!name || !activateTab(name)) { return; }
			if (scroll) {
				var tabs = document.querySelector('.tabs');
				if (tabs) { tabs.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
			}
		}

		if (tabPanes.length) {
			openTabFromUrl(true);
			window.addEventListener('hashchange', function () { openTabFromUrl(true); });
		}

		/* ---- Gallery ---- */
		var thumbs  = document.querySelectorAll('#pdpThumbs span');
		var mainImg = document.getElementById('pdpMainImg');
		var current = 0;

		if (thumbs.length && mainImg) {
			var showThumb = function (index) {
				if (index < 0) { index = thumbs.length - 1; }
				if (index >= thumbs.length) { index = 0; }

				var t = thumbs[index];
				var full = t.getAttribute('data-full');
				if (!full) { return; }

				/* WordPress renders the main image WITH srcset and sizes. Setting
				   src alone leaves the browser free to keep the srcset candidate
				   it already picked — which is why the image never changed. Both
				   have to go before the new src is honoured. */
				mainImg.removeAttribute('srcset');
				mainImg.removeAttribute('sizes');
				mainImg.setAttribute('src', full);

				var inner = t.querySelector('img');
				if (inner) { mainImg.setAttribute('alt', inner.getAttribute('alt') || ''); }

				Array.prototype.forEach.call(thumbs, function (s) { s.classList.remove('on'); });
				t.classList.add('on');
				current = index;
			};

			Array.prototype.forEach.call(thumbs, function (t, i) {
				t.setAttribute('tabindex', '0');
				t.setAttribute('role', 'button');
				t.addEventListener('click', function () { showThumb(i); });
				t.addEventListener('keydown', function (e) {
					if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showThumb(i); }
				});
			});

			var strip = document.getElementById('pdpThumbs');
			if (strip) {
				strip.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowRight') { e.preventDefault(); showThumb(current + 1); }
					if (e.key === 'ArrowLeft')  { e.preventDefault(); showThumb(current - 1); }
				});
			}

			// Swipe the main image on touch
			var media = document.getElementById('pdpMedia');
			if (media) {
				var startX = null;
				media.addEventListener('touchstart', function (e) {
					startX = e.changedTouches[0].clientX;
				}, { passive: true });
				media.addEventListener('touchend', function (e) {
					if (startX === null) { return; }
					var dx = e.changedTouches[0].clientX - startX;
					if (Math.abs(dx) > 40) { showThumb(dx < 0 ? current + 1 : current - 1); }
					startX = null;
				}, { passive: true });
			}
		}

		/* ---- Quantity stepper ----
		   Reads min, max and step off the input rather than assuming 1, so it
		   always agrees with what the browser's own validation will accept. It
		   used to add 1 blindly: on a wine with a case minimum that produced a
		   number the form then refused to submit. */
		var qtyWrap = document.querySelector('.qty');
		if (!qtyWrap) { return; }

		var input = qtyWrap.querySelector('input.qty');
		var minus = qtyWrap.querySelector('.qty-minus');
		var plus  = qtyWrap.querySelector('.qty-plus');
		if (!input || !minus || !plus) { return; }

		/* THE LAST WORD ON min AND step.
		   Whatever PHP put in the markup, the numbers the theme intends are on
		   the wrapper. Writing them onto the input here means the browser's own
		   validation, its arrow keys and this stepper all agree — and it works
		   even if the page came from a cache, or a plugin set the step after we
		   did. Without this, "the theme is fixed but the site still counts in
		   sixes" is a real outcome. */
		var wantMin  = parseFloat(qtyWrap.getAttribute('data-min'));
		var wantStep = parseFloat(qtyWrap.getAttribute('data-step'));

		if (!isNaN(wantStep) && wantStep > 0) { input.setAttribute('step', String(wantStep)); }
		if (!isNaN(wantMin)) {
			input.setAttribute('min', String(wantMin));
			if (!input.value || parseFloat(input.value) < wantMin) { input.value = String(wantMin); }
		}

		function num(attr, fallback) {
			var n = parseFloat(input.getAttribute(attr));
			return isNaN(n) ? fallback : n;
		}

		function refresh() {
			var min = num('min', 1);
			var max = num('max', Infinity);
			var val = parseFloat(input.value);
			minus.disabled = !isNaN(val) && val <= min;
			plus.disabled  = !isNaN(val) && max !== Infinity && val >= max;
		}

		function set(next) {
			input.value = String(next);
			// bubbles: true — WooCommerce and any cart script listen further up
			// the tree, and a non-bubbling event never reaches them.
			input.dispatchEvent(new Event('change', { bubbles: true }));
			input.dispatchEvent(new Event('input', { bubbles: true }));
			refresh();
		}

		function step(direction) {
			var min  = num('min', 1);
			var max  = num('max', Infinity);
			var by   = Math.max(1, num('step', 1));
			var val  = parseFloat(input.value);
			if (isNaN(val)) { val = min; }

			// Stay on the ladder the browser expects: valid values are
			// min, min+step, min+2*step … so snap to it before moving.
			var steps = Math.round((val - min) / by);
			var next  = min + (steps + direction) * by;

			if (next < min) { next = min; }
			if (max !== Infinity && next > max) { next = max; }

			set(next);
		}

		minus.addEventListener('click', function () { step(-1); });
		plus.addEventListener('click', function () { step(1); });

		// Whatever they type by hand still has to be a value the form accepts —
		// and the two buttons have to agree with it afterwards, or one of them
		// can be left disabled on a number it should not be.
		input.addEventListener('blur', function () {
			var min = num('min', 1);
			var max = num('max', Infinity);
			var by  = Math.max(1, num('step', 1));
			var val = parseFloat(input.value);

			if (isNaN(val) || val < min) { set(min); return; }

			var snapped = min + Math.round((val - min) / by) * by;
			if (max !== Infinity && snapped > max) { snapped = max; }
			set(snapped);
		});

		refresh();
	})();
	</script>

	<?php
endwhile;

get_footer( 'shop' );