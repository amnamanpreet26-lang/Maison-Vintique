<?php
/**
 * Template Name: Home Page Template
 *
 * Place this file in your active theme's root folder
 * (e.g. wp-content/themes/your-theme/template-home.php).
 * Assign it to a Page from Page Attributes > Template in wp-admin,
 * then set that Page as your static homepage under
 * Settings > Reading > Your homepage displays.
 */

get_header();
?>

<main>

		<!-- ============ HERO ============ -->
		<?php
		$hero_bg_type    = get_field('hero_background_type') ?: 'image';
		$hero_bg_image   = get_field('hero_background_image');
		$hero_bg_video   = get_field('hero_background_video'); // return_format: url
		$hero_bg_opacity = get_field('hero_background_opacity');
		$hero_has_video  = ( 'video' === $hero_bg_type && $hero_bg_video );
		$hero_has_image  = ( ! $hero_has_video && $hero_bg_image );

		$hero_style = '';
		if ( $hero_has_image ) {
			$hero_style .= "background-image: url('" . esc_url($hero_bg_image['url']) . "');";
		}
		if ( $hero_bg_opacity !== '' && $hero_bg_opacity !== false ) {
			$hero_style .= " --hero-bg-opacity: " . esc_attr($hero_bg_opacity) . ";";
		}
		$hero_classes = 'section hero' . ( $hero_has_video ? ' hero--video' : '' );
		?>
		<section class="<?php echo esc_attr( $hero_classes ); ?>" <?php echo $hero_style ? 'style="' . esc_attr($hero_style) . '"' : ''; ?>>
		  <?php if ( $hero_has_video ) : ?>
			<div class="hero__background hero__background--video">
			  <video
				autoplay
				muted
				loop
				playsinline
				preload="auto"
				<?php if ( $hero_bg_image ) : ?>poster="<?php echo esc_url( $hero_bg_image['url'] ); ?>"<?php endif; ?>
			  >
				<source src="<?php echo esc_url( $hero_bg_video ); ?>" type="video/mp4">
			  </video>
			</div>
		  <?php elseif ( $hero_has_image ) : ?>
			<div class="hero__background"></div>
		  <?php endif; ?>
		  <div class="hero__inner">
			<div class="hero__content">
			  <?php if ( $val = get_field('hero_eyebrow') ) : ?>
				<p class="eyebrow"><?php echo esc_html( $val ); ?></p>
			  <?php endif; ?>
			  <?php if ( $val = get_field('hero_title') ) : ?>
				<h1 class="hero__title"><?php echo wp_kses_post( $val ); ?></h1>
			  <?php endif; ?>
			  <?php if ( $val = get_field('hero_text') ) : ?>
				<p class="hero__text"><?php echo esc_html( $val ); ?></p>
			  <?php endif; ?>
			  <div class="hero__actions">
				<?php if ( $label = get_field('hero_cta_primary_label') ) : ?>
				  <a href="<?php echo esc_url( get_field('hero_cta_primary_link') ?: '#' ); ?>" class="btn btn--primary">
					<?php echo esc_html( $label ); ?>
				  </a>
				<?php endif; ?>
				<?php if ( $label = get_field('hero_cta_secondary_label') ) : ?>
				  <a href="<?php echo esc_url( get_field('hero_cta_secondary_link') ?: '#' ); ?>" class="btn btn--outline">
					<?php echo esc_html( $label ); ?>
				  </a>
				<?php endif; ?>
			  </div>
			</div>
			<?php $hero_image = get_field('hero_image'); ?>
			<figure class="hero__media">
			  <?php if ( $hero_image ) : ?>
				<img 
				  src="<?php echo esc_url( $hero_image['url'] ); ?>" 
				  alt="<?php echo esc_attr( $hero_image['alt'] ); ?>"
				>
			  <?php endif; ?>
			  <?php if ( $caption = get_field('hero_image_caption') ) : ?>
				<figcaption><?php echo esc_html( $caption ); ?></figcaption>
			  <?php endif; ?>
			</figure>
		  </div>
		</section>

  <!-- ============ PHILOSOPHY ============ -->
  <section class="section philosophy">
    <div class="container container--narrow">
      <?php if ( $val = get_field('philosophy_eyebrow') ) : ?>
        <p class="eyebrow eyebrow--center"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
      <?php if ( $val = get_field('philosophy_title') ) : ?>
        <h2 class="section__title section__title--center"><?php echo esc_html( $val ); ?></h2>
      <?php endif; ?>
      <?php if ( $val = get_field('philosophy_text') ) : ?>
        <p class="philosophy__text"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
    </div>
  </section>

  <!-- ============ FEATURED ESTATE ============ -->
  <section class="section featured-estate">
    <div class="container featured-estate__inner">

      <?php $estate_image = get_field('estate_image'); ?>
      <?php if ( $estate_image ) : ?>
        <figure class="featured-estate__media">
          <img src="<?php echo esc_url( $estate_image['url'] ); ?>" alt="<?php echo esc_attr( $estate_image['alt'] ); ?>">
        </figure>
      <?php endif; ?>

      <div class="featured-estate__content">
        <?php if ( $val = get_field('estate_eyebrow_top') ) : ?>
          <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php if ( $val = get_field('estate_eyebrow') ) : ?>
          <p class="eyebrow--white"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php if ( $val = get_field('estate_name') ) : ?>
          <h3 class="section__title--white"><?php echo esc_html( $val ); ?></h3>
        <?php endif; ?>

        <?php if ( have_rows('estate_stats') ) : ?>
          <ul class="stat-row">
            <?php while ( have_rows('estate_stats') ) : the_row(); ?>
              <li class="stat-row__item">
                <span class="stat-row__value"><?php echo esc_html( get_sub_field('stat_value') ); ?></span>
                <span class="stat-row__label"><?php echo esc_html( get_sub_field('stat_label') ); ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php endif; ?>

        <?php if ( $val = get_field('estate_text') ) : ?>
          <p class="featured-estate__text"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>

        <?php if ( $label = get_field('estate_cta_label') ) : ?>
          <a href="<?php echo esc_url( get_field('estate_cta_link') ?: '#' ); ?>" class="link-arrow--white"><?php echo esc_html( $label ); ?></a>
        <?php endif; ?>
      </div>

    </div>
  </section>




<!-- ============ ESTATE PARTNERS / FAMILIES ============ -->
<?php
// This MUST run before the display block below, in the SAME file/scope
$mv_producers = new WP_Query( array(
    'post_type'      => 'producer',
    'posts_per_page' => 8,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'no_found_rows'  => true,
) );
?>

<?php if ( $mv_producers->have_posts() ) : ?>
<section class="section partners">
  <div class="container">
    <div class="section__head">
      <?php if ( $val = get_field('partners_eyebrow') ) : ?>
        <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
      <?php if ( $val = get_field('partners_title') ) : ?>
        <h2 class="section__title"><?php echo esc_html( $val ); ?></h2>
      <?php endif; ?>
      <?php if ( $val = get_field('partners_subtext') ) : ?>
        <p class="section__subtext"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
    </div>
    <div class="producer-carousel" data-count="<?php echo esc_attr( $mv_producers->post_count ); ?>">
      <button class="producer-carousel__arrow producer-carousel__arrow--prev" aria-label="Previous">&#8249;</button>
      <div class="card-grid card-grid--3 producer-carousel__track">
        <?php while ( $mv_producers->have_posts() ) : $mv_producers->the_post();
          $region_terms  = get_the_terms( get_the_ID(), 'producer_region' );
          $country_terms = get_the_terms( get_the_ID(), 'producer_country' );
          $region  = ( $region_terms && ! is_wp_error( $region_terms ) ) ? $region_terms[0]->name : '';
          $country = ( $country_terms && ! is_wp_error( $country_terms ) ) ? $country_terms[0]->name : '';

          // Build ring initials from the estate name, e.g. "Château de Sancerre" -> "CS"
          $title_words = preg_split( '/\s+/', trim( wp_strip_all_tags( get_the_title() ) ) );
          $skip_words  = array( 'de', 'du', 'la', 'le', 'les', 'et', 'of', 'the' );
          $initials    = '';
          foreach ( $title_words as $word ) {
            $word_l = mb_strtolower( $word );
            if ( in_array( $word_l, $skip_words, true ) ) continue;
            $first = mb_substr( $word, 0, 1 );
            if ( ctype_alpha( $first ) || preg_match( '/\p{L}/u', $first ) ) {
              $initials .= mb_strtoupper( $first );
            }
          }
          $initials = mb_substr( $initials, 0, 3 );

          $est_year = get_field( 'established_year' ); // optional ACF field, falls back below
          $back_sub = $est_year ? 'Est. ' . esc_html( $est_year ) : esc_html( $region ?: $country );
        ?>
          <a href="<?php echo esc_url( get_permalink() ); ?>" class="estate-card producer-carousel__slide">
            <div class="estate-card__flip">
              <div class="estate-card__face estate-card__face--front">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'medium_large' ); ?>
                <?php endif; ?>
              </div>
             <div class="estate-card__face estate-card__face--back">
				  <span class="estate-card__badge">
					<?php // Per-estate logo — Producer → Grid Card → Estate Logo. ?>
					<?php $estate_logo = mve_producer_logo( get_the_ID() ); ?>
					<?php if ( $estate_logo ) : ?>
					  <span class="estate-card__logo">
						<img src="<?php echo esc_url( $estate_logo ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
					  </span>
					<?php endif; ?>
					<span class="estate-card__ring"><?php echo esc_html( $initials ); ?></span>
					<?php if ( $back_sub ) : ?>
					  <span class="estate-card__est"><?php echo $back_sub; ?></span>
					<?php endif; ?>
				  </span>
				</div>
            </div>
            <h3 class="estate-card__title"><?php the_title(); ?></h3>
            <?php if ( $country || $region ) : ?>
              <p class="estate-card__meta">
                <?php echo esc_html( trim( $region . ( $region && $country ? ' · ' : '' ) . $country ) ); ?>
              </p>
            <?php endif; ?>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <button class="producer-carousel__arrow producer-carousel__arrow--next" aria-label="Next">&#8250;</button>
    </div>
  </div>
</section>
<?php endif; ?>

  <!-- ============ WINE OF THE MOMENT ============ -->
  <?php
  /*
   * Spotlight — now backed by a real WooCommerce product.
   *
   * Pick the wine in the ACF "spotlight_product" field and the image, name,
   * price and button link all come from the product itself. Everything still
   * has a manual ACF override, so an editor can pin any of it by hand, and if
   * no product is chosen the section behaves exactly as it did before.
   *
   * The price obeys the same one rule as the rest of the site: hidden while
   * logged out. See mve_is_gated() in inc/woocommerce.php.
   */
  $spot_product_field = get_field('spotlight_product');
  $spot_product_id    = 0;
  if ( $spot_product_field ) {
    $spot_product_id = is_object( $spot_product_field ) ? $spot_product_field->ID : (int) $spot_product_field;
  }
  $spot_product = ( $spot_product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $spot_product_id ) : null;
  if ( $spot_product && ! $spot_product->is_visible() ) {
    $spot_product = null;
  }

  $spot_gated = function_exists( 'mve_is_gated' ) ? mve_is_gated( $spot_product_id ) : ! is_user_logged_in();
  ?>
  <section class="section spotlight">
    <div class="container spotlight__inner">

      <?php
      $spotlight_image = get_field('spotlight_image');
      $spot_image_url  = $spotlight_image ? $spotlight_image['url'] : '';
      $spot_image_alt  = $spotlight_image ? $spotlight_image['alt'] : '';

      // Fall back to the product's own image.
      if ( ! $spot_image_url && $spot_product ) {
        $spot_image_url = get_the_post_thumbnail_url( $spot_product_id, 'large' );
        $spot_image_alt = get_the_title( $spot_product_id );
      }
      ?>
      <?php if ( $spot_image_url ) : ?>
        <figure class="spotlight__media">
          <?php if ( $spot_product ) : ?>
            <a href="<?php echo esc_url( get_permalink( $spot_product_id ) ); ?>">
              <img src="<?php echo esc_url( $spot_image_url ); ?>" alt="<?php echo esc_attr( $spot_image_alt ); ?>">
            </a>
          <?php else : ?>
            <img src="<?php echo esc_url( $spot_image_url ); ?>" alt="<?php echo esc_attr( $spot_image_alt ); ?>">
          <?php endif; ?>
        </figure>
      <?php endif; ?>

      <div class="spotlight__content">
        <?php if ( $val = get_field('spotlight_eyebrow') ) : ?>
          <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php
        $spot_name = get_field('spotlight_name');
        if ( ! $spot_name && $spot_product ) {
          $spot_name = get_the_title( $spot_product_id );
        }
        ?>
        <?php if ( $spot_name ) : ?>
          <h3 class="section__title">
            <?php if ( $spot_product ) : ?>
              <a href="<?php echo esc_url( get_permalink( $spot_product_id ) ); ?>"><?php echo esc_html( $spot_name ); ?></a>
            <?php else : ?>
              <?php echo esc_html( $spot_name ); ?>
            <?php endif; ?>
          </h3>
        <?php endif; ?>

        <?php if ( have_rows('spotlight_details') ) : ?>
          <ul class="detail-row">
            <?php while ( have_rows('spotlight_details') ) : the_row(); ?>
              <li>
                <span class="detail-row__label"><?php echo esc_html( get_sub_field('label') ); ?></span>
                <span class="detail-row__value"><?php echo esc_html( get_sub_field('value') ); ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php endif; ?>

        <?php if ( $val = get_field('spotlight_text') ) : ?>
          <p class="spotlight__text"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>

        <div class="spotlight__row">
          <?php if ( $spot_gated ) : ?>

            <?php // Logged out — no price anywhere on the site. ?>
            <span class="mv-trade-tag spotlight__trade"><?php esc_html_e( 'Sign in to view trade pricing', 'maison-vintique' ); ?></span>
            <a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url() ); ?>" class="btn btn--primary btn--small">
              <?php esc_html_e( 'Login to view price', 'maison-vintique' ); ?>
            </a>

          <?php else : ?>

            <?php
            // Manual price wins; otherwise take the product's own.
            $spot_price = get_field('spotlight_price');
            if ( ! $spot_price && $spot_product ) {
              $spot_price = $spot_product->get_price_html();
            }
            ?>
            <?php if ( $spot_price ) : ?>
              <span class="price"><?php echo wp_kses_post( $spot_price ); ?></span>
            <?php endif; ?>

            <?php if ( $val = get_field('spotlight_price_unit') ) : ?>
              <span class="price__unit"><?php echo esc_html( $val ); ?></span>
            <?php endif; ?>

            <?php
            $spot_cta_label = get_field('spotlight_cta_label');
            $spot_cta_link  = get_field('spotlight_cta_link');
            if ( ! $spot_cta_link && $spot_product ) {
              $spot_cta_link = get_permalink( $spot_product_id );
            }
            ?>
            <?php if ( $spot_cta_label ) : ?>
              <a href="<?php echo esc_url( $spot_cta_link ?: '#' ); ?>" class="btn btn--primary btn--small"><?php echo esc_html( $spot_cta_label ); ?></a>
            <?php endif; ?>

          <?php endif; ?>
        </div>
		  <div class="spotlight__button">
			 
		  <?php if ( $label = get_field('spotlight_link_label') ) : ?>
            <a href="<?php echo esc_url( get_field('spotlight_link_url') ?: '#' ); ?>" class="link-arrow"><?php echo esc_html( $label ); ?></a>
          <?php endif; ?>
		</div>
      </div>

    </div>
  </section>

  <!-- ============ CURATED PORTFOLIO ============ -->
<?php
$mv_products = new WP_Query( array(
    'post_type'      => 'product',
    'posts_per_page' => 8,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'no_found_rows'  => true,
) );
?>
<?php if ( $mv_products->have_posts() ) : ?>
<section class="section portfolio">
  <div class="container">
    <div class="section__head section__head--split">
      <div>
        <?php if ( $val = get_field('portfolio_eyebrow') ) : ?>
          <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php if ( $val = get_field('portfolio_title') ) : ?>
          <h2 class="section__title"><?php echo esc_html( $val ); ?></h2>
        <?php endif; ?>
        <?php if ( $val = get_field('portfolio_subtext') ) : ?>
          <p class="section__subtext"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
      </div>
      <?php if ( $label = get_field('portfolio_view_all_label') ) : ?>
        <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="link-arrow"><?php echo esc_html( $label ); ?></a>
      <?php endif; ?>
    </div>
    <div class="card-grid card-grid--4">
      <?php while ( $mv_products->have_posts() ) : $mv_products->the_post();
        global $product;
        $product = wc_get_product( get_the_ID() );

        // One shared card for the homepage grid and the shop archive —
        // see template-parts/wine-card.php.
        get_template_part( 'template-parts/wine-card' );
      ?>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

  <!-- ============ WHAT GUIDES US ============ -->
  <?php if ( have_rows('values_items') ) : ?>
  <section class="section values">
    <div class="container">

      <div class="section__head">
        <?php if ( $val = get_field('values_eyebrow') ) : ?>
          <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php if ( $val = get_field('values_title') ) : ?>
          <h2 class="section__title"><?php echo esc_html( $val ); ?></h2>
        <?php endif; ?>
      </div>

      <div class="card-grid card-grid--3 values__grid">
        <?php while ( have_rows('values_items') ) : the_row(); ?>
          <article class="value-card">
            <span class="value-card__index"><?php echo esc_html( get_sub_field('index') ); ?></span>
            <h3 class="value-card__title"><?php echo esc_html( get_sub_field('title') ); ?></h3>
            <p class="value-card__text"><?php echo esc_html( get_sub_field('text') ); ?></p>
          </article>
        <?php endwhile; ?>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- ============ JOURNAL ============ -->
<?php
$mv_journal = new WP_Query( array(
    'post_type'      => 'journal',
    'posts_per_page' => 3,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
) );
?>
<?php if ( $mv_journal->have_posts() ) : ?>
<section class="section journal">
  <div class="container">
    <div class="section__head section__head--split">
      <div>
        <?php if ( $val = get_field('journal_eyebrow') ) : ?>
          <p class="eyebrow"><?php echo esc_html( $val ); ?></p>
        <?php endif; ?>
        <?php if ( $val = get_field('journal_title') ) : ?>
          <h2 class="section__title"><?php echo esc_html( $val ); ?></h2>
        <?php endif; ?>
      </div>
      <?php if ( $label = get_field('journal_view_all_label') ) : ?>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'journal' ) ); ?>" class="link-arrow"><?php echo esc_html( $label ); ?></a>
      <?php endif; ?>
    </div>
    <div class="card-grid card-grid--3">
      <?php while ( $mv_journal->have_posts() ) : $mv_journal->the_post();
        $cats    = get_the_terms( get_the_ID(), 'journal_category' );
        $cat     = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
        $excerpt = wp_trim_words( get_the_excerpt(), 15, '…' );
      ?>
        <a href="<?php the_permalink(); ?>" class="journal-card">
          <?php if ( has_post_thumbnail() ) : ?>
            <figure class="journal-card__media">
              <?php the_post_thumbnail( 'medium_large' ); ?>
            </figure>
          <?php endif; ?>
          <?php if ( $cat ) : ?>
            <p class="journal-card__category"><?php echo esc_html( $cat ); ?></p>
          <?php endif; ?>
          <h3 class="journal-card__title"><?php the_title(); ?></h3>
          <?php if ( $excerpt ) : ?>
            <p class="journal-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
          <?php endif; ?>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

  <!-- ============ TRADE ============ -->
<?php $trade_bg = get_field('trade_bg_image'); ?>
<section class="section trade"<?php if ( $trade_bg ) : ?> style="background-image: url('<?php echo esc_url( $trade_bg['url'] ); ?>');"<?php endif; ?>>
    <div class="container container--narrow">
      <?php if ( $val = get_field('trade_eyebrow') ) : ?>
        <p class="eyebrow eyebrow--center eyebrow--on-dark"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
      <?php if ( $val = get_field('trade_title') ) : ?>
        <h2 class="section__title section__title--center trade__title"><?php echo esc_html( $val ); ?></h2>
      <?php endif; ?>
      <?php if ( $val = get_field('trade_text') ) : ?>
        <p class="trade__text"><?php echo esc_html( $val ); ?></p>
      <?php endif; ?>
      <div class="trade__actions">
        <?php if ( $label = get_field('trade_cta_primary_label') ) : ?>
          <a href="<?php echo esc_url( get_field('trade_cta_primary_link') ?: '#' ); ?>" class="btn btn--primary"><?php echo esc_html( $label ); ?></a>
        <?php endif; ?>
        <?php if ( $label = get_field('trade_cta_secondary_label') ) : ?>
          <a href="<?php echo esc_url( get_field('trade_cta_secondary_link') ?: '#' ); ?>" class="btn btn--outline btn--on-dark"><?php echo esc_html( $label ); ?></a>
        <?php endif; ?>
      </div>
    </div>
</section>

</main>

<?php get_footer(); ?>