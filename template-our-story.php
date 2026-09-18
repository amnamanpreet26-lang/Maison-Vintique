<?php
/**
 * Template Name: Our Story
 *
 * @package maison-vintique-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mvs_eyebrow = mve_field( 'os_eyebrow', __( 'Our Story', 'maison-vintique-elementor' ) );
	$mvs_title   = mve_field( 'os_title', get_the_title() );
	$mvs_intro   = mve_field( 'os_intro' );

	$mvs_gallery_heading = mve_field( 'os_gallery_heading' );
	$mvs_gallery_desc    = mve_field( 'os_gallery_desc' );

	$mvs_values_eyebrow = mve_field( 'os_values_eyebrow' );
	$mvs_values_title   = mve_field( 'os_values_title' );

	$mvs_show_body = (bool) mve_field( 'os_show_body', false );
	$mvs_lead      = mve_field( 'os_lead' );
	$mvs_body      = mve_field( 'os_body' );

	$mvs_cta_label = mve_field( 'os_cta_label' );
	$mvs_cta_link  = mve_field( 'os_cta_link' );

	$mvs_has_gallery = function_exists( 'have_rows' ) && have_rows( 'os_gallery' );

	// --- Under-Strip Columns (the new section under the photo strip) ---
	$mvs_under_has = function_exists( 'have_rows' ) && have_rows( 'os_under_cols' );
	?>

	<!-- ============ SECTION 1: HERO BANNER ============ -->
	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => $mvs_eyebrow,
			'title'   => $mvs_title,
			'intro'   => $mvs_intro,
			'prefix'  => 'os',
		)
	);
	?>

	<!-- ============ SECTION 2: STORY GALLERY ============ -->
	<?php if ( $mvs_has_gallery ) : ?>
		<?php
		$mvos_slides = array();

		while ( have_rows( 'os_gallery' ) ) {
			the_row();

			$mvos_image = get_sub_field( 'image' );
			if ( ! $mvos_image ) {
				continue;
			}

			$mvos_slides[] = array(
				'image'      => $mvos_image,
				'title'      => get_sub_field( 'title' ),
				'eyebrow'    => get_sub_field( 'eyebrow' ),
				'text'       => get_sub_field( 'text' ),
				'link_label' => get_sub_field( 'link_label' ),
				'link_url'   => get_sub_field( 'link_url' ),
			);
		}

		if ( ! empty( $mvos_slides ) ) :

			$mvos_total = count( $mvos_slides );
			$mvos_centre = ( $mvos_total >= 3 ) ? 2 : ( $mvos_total - 1 );
			$mvos_left_count  = $mvos_centre;
			$mvos_right_count = $mvos_total - $mvos_centre - 1;
			?>

			<section class="mvos-gallery" aria-label="<?php esc_attr_e( 'Our story', 'maison-vintique-elementor' ); ?>">
				<div class="wrap">

					<?php if ( $mvs_gallery_heading || $mvs_gallery_desc ) : ?>
						<div class="mvos-gallery__intro">
							<?php if ( $mvs_gallery_heading ) : ?>
								<h2 class="mvp-sec__title mvos-gallery__heading"><?php echo esc_html( $mvs_gallery_heading ); ?></h2>
							<?php endif; ?>

							<?php if ( $mvs_gallery_desc ) : ?>
								<p class="mvos-gallery__desc"><?php echo esc_html( $mvs_gallery_desc ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="mvos-gallery__track">
						<?php foreach ( $mvos_slides as $mvos_index => $mvos_slide ) :

							$mvos_img     = $mvos_slide['image'];
							$mvos_src     = ! empty( $mvos_img['sizes']['large'] ) ? $mvos_img['sizes']['large'] : $mvos_img['url'];
							$mvos_is_mid  = ( $mvos_index === $mvos_centre );
							$mvos_classes = 'mvos-gallery__slide';

							if ( $mvos_is_mid ) {
								$mvos_classes .= ' mvos-gallery__slide--centre';
							} else {
								$mvos_classes .= ' mvos-gallery__slide--side';
								$mvos_classes .= ( $mvos_index < $mvos_centre ) ? ' mvos-gallery__slide--left' : ' mvos-gallery__slide--right';
							}
							?>
							<figure class="<?php echo esc_attr( $mvos_classes ); ?>">
								<img
									class="mvos-gallery__img"
									src="<?php echo esc_url( $mvos_src ); ?>"
									alt="<?php echo esc_attr( ! empty( $mvos_img['alt'] ) ? $mvos_img['alt'] : '' ); ?>"
									loading="<?php echo $mvos_is_mid ? 'eager' : 'lazy'; ?>"
								>

								<?php if ( $mvos_is_mid ) : ?>
									<span class="mvos-gallery__overlay" aria-hidden="true"></span>

									<?php if ( $mvos_slide['eyebrow'] || $mvos_slide['title'] || $mvos_slide['text'] || $mvos_slide['link_label'] ) : ?>
										<figcaption class="mvos-gallery__caption">
											<?php if ( $mvos_slide['eyebrow'] ) : ?>
												<p class="mvos-gallery__eyebrow"><?php echo esc_html( $mvos_slide['eyebrow'] ); ?></p>
											<?php endif; ?>

											<?php if ( $mvos_slide['title'] ) : ?>
												<p class="mvos-gallery__title"><?php echo esc_html( $mvos_slide['title'] ); ?></p>
											<?php endif; ?>

											<?php if ( $mvos_slide['text'] ) : ?>
												<p class="mvos-gallery__text"><?php echo esc_html( $mvos_slide['text'] ); ?></p>
											<?php endif; ?>

											<?php if ( $mvos_slide['link_label'] ) : ?>
												<a class="mvos-gallery__link" href="<?php echo esc_url( $mvos_slide['link_url'] ? $mvos_slide['link_url'] : '#' ); ?>">
													<?php echo esc_html( $mvos_slide['link_label'] ); ?>
													<span aria-hidden="true">&rarr;</span>
												</a>
											<?php endif; ?>
										</figcaption>
									<?php endif; ?>
								<?php endif; ?>
							</figure>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<style>
			.mvp-sec-2, .mvos-pillars { padding: 70px 0 !important; }

			.mvos-gallery { width: 100%; padding: 0; }
			.mvos-gallery .wrap { max-width: 100%; margin: 0 auto; padding: 0; }

			.mvos-gallery__intro {
				max-width: 720px;
				margin: 0 auto;
				padding: 50px;
				text-align: center;
			}
			.mvos-gallery__heading { margin: 0 0 14px; }
			.mvos-gallery__desc { margin: 0; font-size: 0.95rem; line-height: 1.7; opacity: 0.85; }

			.mvos-gallery__track {
				position: relative;
				display: flex;
				align-items: stretch;
				width: 100%;
				height: clamp(280px, 10vw, 440px);
				overflow: hidden;
				gap: 1px;
				border-radius: 2px;
			}

			.mvos-gallery__slide { position: relative; margin: 0; overflow: hidden; min-width: 0; }
			.mvos-gallery__slide--side { flex: 1 1 0%; }
			.mvos-gallery__slide--centre { flex: 3.2 1 0%; }

			.mvos-gallery__img {
				position: absolute;
				inset: 0;
				width: 100%;
				height: 100%;
				object-fit: cover;
				display: block;
				transition: transform 0.6s ease;
			}

			.mvos-gallery__slide--side:hover .mvos-gallery__img { transform: scale(1.04); }

			.mvos-gallery__overlay {
				position: absolute;
				inset: 0;
				background: linear-gradient(115deg, rgba(0,0,0,0.62) 0%, rgba(0,0,0,0.30) 55%, rgba(0,0,0,0.05) 100%);
				pointer-events: none;
			}

			.mvos-gallery__caption {
				position: absolute;
				left: 0; bottom: 0;
				z-index: 2;
				max-width: 380px;
				padding: 36px 32px;
				color: #fff;
			}

			.mvos-gallery__eyebrow {
				margin: 0 0 12px;
				font-size: 0.68rem;
				letter-spacing: 0.18em;
				text-transform: uppercase;
				opacity: 0.9;
			}

			.mvos-gallery__title {
				margin: 0 0 14px;
				font-family: Georgia, "Times New Roman", serif;
				font-size: clamp(1.15rem, 1.7vw, 1.6rem);
				line-height: 1.3;
				font-weight: 400;
			}

			.mvos-gallery__text { margin: 0 0 18px; font-size: 0.85rem; line-height: 1.6; opacity: 0.9; }

			.mvos-gallery__link {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				color: #fff;
				text-decoration: none;
				font-size: 0.7rem;
				letter-spacing: 0.18em;
				text-transform: uppercase;
				border-bottom: 1px solid rgba(255,255,255,0.4);
				padding-bottom: 4px;
				transition: border-color 0.3s ease, gap 0.3s ease;
			}
			.mvos-gallery__link:hover { border-color: #fff; gap: 12px; }

			@media (max-width: 1024px) {
				.mvos-gallery__track { height: clamp(240px, 45vw, 360px); }
				.mvos-gallery__caption { max-width: 300px; padding: 24px 22px; }
			}

			@media (max-width: 782px) {
				.mvos-gallery { padding: 24px 0; }
				.mvos-gallery__intro { margin-bottom: 22px; }
				.mvos-gallery__track { height: auto; display: block; }
				.mvos-gallery__slide--side { display: none; }
				.mvos-gallery__slide--centre { position: relative; height: clamp(360px, 90vw, 480px); }
				.mvos-gallery__caption { max-width: 100%; padding: 22px; }
			}
			</style>
		<?php endif; ?>
	<?php endif; ?>

	<!-- ============ SECTION 2.5: UNDER-STRIP COLUMNS ============ -->
	<?php if ( $mvs_under_has ) : ?>
		<section class="mvus" aria-label="<?php esc_attr_e( 'What guides us', 'maison-vintique-elementor' ); ?>">
			<div class="wrap">
				<div class="mvus__grid">
					<?php
					while ( have_rows( 'os_under_cols' ) ) :
						the_row();

						$mvus_n     = get_sub_field( 'index' );
						$mvus_title = get_sub_field( 'title' );
						$mvus_text  = get_sub_field( 'text' );
						$mvus_label = get_sub_field( 'link_label' );
						$mvus_url   = get_sub_field( 'link_url' );
						?>
						<article class="mvus__col">
							<?php if ( $mvus_n ) : ?>
								<p class="mvus__index">
									<span class="mvus__num"><?php echo esc_html( $mvus_n ); ?></span>
									<span class="mvus__rule" aria-hidden="true"></span>
								</p>
							<?php endif; ?>

							<?php if ( $mvus_title ) : ?>
								<h3 class="mvus__title"><?php echo esc_html( $mvus_title ); ?></h3>
							<?php endif; ?>

							<?php if ( $mvus_text ) : ?>
								<p class="mvus__text"><?php echo esc_html( $mvus_text ); ?></p>
							<?php endif; ?>

							<?php if ( $mvus_label ) : ?>
								<a class="mvus__link" href="<?php echo esc_url( $mvus_url ? $mvus_url : '#' ); ?>">
									<?php echo esc_html( $mvus_label ); ?>
									<span aria-hidden="true">&rarr;</span>
								</a>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
				</div>
			</div>
		</section>

		<style>
		.mvus {
			width: 100%;
			padding: 50px 0 80px;
			background: #faf8f4;
		}

		.mvus .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

		.mvus__grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 0;
		}

		.mvus__col { position: relative; padding: 0 36px; }

		.mvus__col + .mvus__col::before {
			content: "";
			position: absolute;
			top: 8px;
			bottom: 8px;
			left: 0;
			width: 1px;
			background: rgba(43, 43, 43, 0.12);
		}

		.mvus__col:first-child { padding-left: 0; }
		.mvus__col:last-child  { padding-right: 0; }

		.mvus__index {
			display: flex;
			align-items: center;
			gap: 12px;
			margin: 0 0 22px;
		}

		.mvus__num {
			    font-family: var(--mv2-serif);
    font-size: 19px;
    letter-spacing: .1em;
    color: var(--mv2-gold);
		}

		.mvus__rule {
			display: inline-block;
			width: 30px;
			height: 1px;
			background: var(--mv2-gold);
		}

		.mvus__title {
			margin: 0 0 14px;
			font-family: var(--mv2-serif);
			font-size: 25px;
			line-height: 1.3;
			font-weight: 500;
			color: #17251F;
		}

		.mvus__text {
			margin: 0 0 22px;
			font-size: 0.9rem;
			line-height: 1.65;
			color: #5a5a5a;
		}

		.mvus__link {
			    font-family: var(--mv-sans);
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-weight: 400;
    color: var(--mv-gold);
    border-bottom: 1px solid var(--mv-gold);
    padding-bottom: 4px;
    white-space: nowrap;
    text-decoration: none;
    display: inline-block;
    transition: opacity 0.2s ease;
		}

		.mvus__link:hover {
			color: #6b5328;
			border-color: #6b5328;
			gap: 12px;
		}

		@media (max-width: 1024px) {
			.mvus { padding: 56px 0 60px; }
			.mvus__col { padding: 0 24px; }
		}

		@media (max-width: 782px) {
			.mvus { padding: 48px 0 52px; }
			.mvus__grid { grid-template-columns: 1fr; }
			.mvus__col { padding: 28px 0; }
			.mvus__col + .mvus__col::before {
				top: 0; bottom: auto;
				left: 0; right: 0;
				width: auto; height: 1px;
			}
			.mvus__col:first-child { padding-top: 0; }
			.mvus__col:last-child  { padding-bottom: 0; }
		}
		</style>
	<?php endif; ?>

	<main>

		<!-- ============ SECTION 3: TEXT + IMAGE / VIDEO ============ -->
		<?php get_template_part( 'template-parts/page-split', null, array( 'prefix' => 'os' ) ); ?>

		<!-- ============ SECTION 4: THE THREE COLUMNS (existing) ============ -->
		<?php if ( have_rows( 'os_values' ) ) : ?>
			<section class="section mvp-sec mvos-pillars">
				<div class="wrap">
					<?php if ( $mvs_values_eyebrow ) : ?>
						<p class="eyebrow eyebrow--center"><?php echo esc_html( $mvs_values_eyebrow ); ?></p>
					<?php endif; ?>

					<?php if ( $mvs_values_title ) : ?>
						<h2 class="mvp-sec__title"><?php echo esc_html( $mvs_values_title ); ?></h2>
					<?php endif; ?>

					<div class="mvos-pillars__grid">
						<?php
						while ( have_rows( 'os_values' ) ) :
							the_row();

							$mvp_n     = get_sub_field( 'index' );
							$mvp_title = get_sub_field( 'title' );
							$mvp_text  = get_sub_field( 'text' );
							$mvp_label = get_sub_field( 'link_label' );
							$mvp_url   = get_sub_field( 'link_url' );
							?>
							<article class="mvos-pillar">
								<?php if ( $mvp_n ) : ?>
									<p class="mvos-pillar__n">
										<span class="mvos-pillar__num"><?php echo esc_html( $mvp_n ); ?></span>
										<span class="mvos-pillar__rule" aria-hidden="true"></span>
									</p>
								<?php endif; ?>

								<?php if ( $mvp_title ) : ?>
									<h3 class="mvos-pillar__title"><?php echo esc_html( $mvp_title ); ?></h3>
								<?php endif; ?>

								<?php if ( $mvp_text ) : ?>
									<p class="mvos-pillar__text"><?php echo esc_html( $mvp_text ); ?></p>
								<?php endif; ?>

								<?php if ( $mvp_label ) : ?>
									<a class="link-arrow mvos-pillar__link" href="<?php echo esc_url( $mvp_url ? $mvp_url : '#' ); ?>">
										<?php echo esc_html( $mvp_label ); ?>
										<span aria-hidden="true">&rarr;</span>
									</a>
								<?php endif; ?>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ SECTION 5: FULL-WIDTH BAND ============ -->
		<?php
		$mvvh_image   = mve_field( 'os_vhero_image' );
		$mvvh_video   = mve_field( 'os_vhero_video' );
		$mvvh_embed   = mve_field( 'os_vhero_embed' );
		$mvvh_height  = mve_field( 'os_vhero_height', 'tall' );
		$mvvh_align   = mve_field( 'os_vhero_align', 'left' );
		$mvvh_colour  = mve_field( 'os_vhero_colour', '#000000' );
		$mvvh_opacity = mve_field( 'os_vhero_opacity', 0.55 );
		$mvvh_title   = mve_field( 'os_vhero_title' );
		$mvvh_eyebrow = mve_field( 'os_vhero_eyebrow' );
		$mvvh_aside   = mve_field( 'os_vhero_aside' );
		$mvvh_text    = mve_field( 'os_vhero_text' );

		if ( $mvvh_image || $mvvh_video || $mvvh_embed ) :

			if ( ! in_array( $mvvh_align, array( 'left', 'center', 'right' ), true ) ) {
				$mvvh_align = 'left';
			}
			if ( ! in_array( $mvvh_height, array( 'tall', 'medium', 'short' ), true ) ) {
				$mvvh_height = 'tall';
			}

			$mvvh_rgb = array( 0, 0, 0 );
			if ( $mvvh_colour && preg_match( '/^#([a-f0-9]{6})$/i', $mvvh_colour, $mvvh_m ) ) {
				$mvvh_rgb = array_map( 'hexdec', str_split( $mvvh_m[1], 2 ) );
			}
			$mvvh_opacity_val = is_numeric( $mvvh_opacity ) ? (float) $mvvh_opacity : 0.55;
			$mvvh_overlay_css = sprintf( 'rgba(%d, %d, %d, %s)', $mvvh_rgb[0], $mvvh_rgb[1], $mvvh_rgb[2], $mvvh_opacity_val );

			$mvvh_aside_side = ( 'right' === $mvvh_align ) ? 'left' : 'right';

			$mvvh_aside_lines = array();
			if ( $mvvh_aside ) {
				$mvvh_aside_lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $mvvh_aside ) ) );
			}
			?>

			<section class="mvos-band mvos-band--<?php echo esc_attr( $mvvh_height ); ?> mvos-band--<?php echo esc_attr( $mvvh_align ); ?>">

				<div class="mvos-band__media">
					<?php if ( $mvvh_video && ! empty( $mvvh_video['url'] ) ) : ?>
						<video
							class="mvos-band__video"
							autoplay muted loop playsinline
							<?php if ( $mvvh_image && ! empty( $mvvh_image['url'] ) ) : ?>poster="<?php echo esc_url( $mvvh_image['url'] ); ?>"<?php endif; ?>
						>
							<source src="<?php echo esc_url( $mvvh_video['url'] ); ?>" type="video/mp4">
						</video>
					<?php elseif ( $mvvh_embed ) : ?>
						<div class="mvos-band__embed">
							<iframe
								src="<?php echo esc_url( $mvvh_embed ); ?>"
								title="<?php echo esc_attr( $mvvh_title ? $mvvh_title : get_the_title() ); ?>"
								frameborder="0"
								allow="autoplay; muted; loop"
								allowfullscreen
							></iframe>
						</div>
					<?php elseif ( $mvvh_image && ! empty( $mvvh_image['url'] ) ) :
						$mvvh_src = ! empty( $mvvh_image['sizes']['xlarge'] ) ? $mvvh_image['sizes']['xlarge'] : $mvvh_image['url'];
						?>
						<img
							class="mvos-band__img"
							src="<?php echo esc_url( $mvvh_src ); ?>"
							alt="<?php echo esc_attr( ! empty( $mvvh_image['alt'] ) ? $mvvh_image['alt'] : '' ); ?>"
							loading="lazy"
						>
					<?php endif; ?>

					<div class="mvos-band__overlay" style="background-color: <?php echo esc_attr( $mvvh_overlay_css ); ?>;"></div>
				</div>

				<?php if ( $mvvh_title || $mvvh_eyebrow || $mvvh_text ) : ?>
					<div class="mvos-band__content">
						<?php if ( $mvvh_title ) : ?>
							<h2 class="mvos-band__title"><?php echo esc_html( $mvvh_title ); ?></h2>
						<?php endif; ?>
						<?php if ( $mvvh_eyebrow ) : ?>
							<p class="mvos-band__eyebrow"><?php echo esc_html( $mvvh_eyebrow ); ?></p>
						<?php endif; ?>
						<?php if ( $mvvh_text ) : ?>
							<p class="mvos-band__text"><?php echo esc_html( $mvvh_text ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $mvvh_aside_lines ) ) : ?>
					<div class="mvos-band__aside mvos-band__aside--<?php echo esc_attr( $mvvh_aside_side ); ?>">
						<?php foreach ( $mvvh_aside_lines as $mvvh_line ) : ?>
							<p><?php echo esc_html( $mvvh_line ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</section>

			<style>
			.mvos-band { position: relative; display: flex; overflow: hidden; color: #fff; }
			.mvos-band--tall   { min-height: clamp(420px, 62vh, 780px); }
			.mvos-band--medium { min-height: clamp(470px, 44vh, 560px); }
			.mvos-band--short  { min-height: clamp(240px, 30vh, 380px); }

			.mvos-band__media { position: absolute; inset: 0; z-index: 0; }
			.mvos-band__img, .mvos-band__video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

			.mvos-band__embed { position: absolute; inset: 0; overflow: hidden; }
			.mvos-band__embed iframe {
				position: absolute;
				top: 50%; left: 50%;
				width: 100%; height: 100%;
				min-width: 100vw; min-height: 56.25vw;
				transform: translate(-50%, -50%);
				pointer-events: none;
			}

			.mvos-band__overlay { position: absolute; inset: 0; }

			.mvos-band__content {
				position: relative;
				z-index: 1;
				display: flex;
				flex-direction: column;
				justify-content: flex-end;
				width: 100%;
				max-width: 480px;
				padding: 48px;
			}

			.mvos-band--left .mvos-band__content { margin-right: auto; align-items: flex-start; text-align: left; }
			.mvos-band--right .mvos-band__content { margin-left: auto; align-items: flex-end; text-align: right; }
			.mvos-band--center .mvos-band__content {
				margin: 0 auto;
				max-width: 620px;
				align-items: center;
				justify-content: center;
				text-align: center;
				padding-bottom: 48px;
			}

			.mvos-band__title { margin: 0 0 10px; font-size: 35px; line-height: 1.25; font-weight: 400; }
			.mvos-band__eyebrow {
				margin: 0;
				font-size: 0.72rem;
				letter-spacing: 0.14em;
				text-transform: uppercase;
				opacity: 0.85;
			}
			.mvos-band__text { margin: 14px 0 0; max-width: 44ch; font-size: 14px; line-height: 1.6; opacity: 0.9; }

			.mvos-band__aside {
				position: absolute;
				bottom: 48px;
				z-index: 1;
				font-size: 0.68rem;
				letter-spacing: 0.14em;
				text-transform: normal;
				line-height: 1.8;
				opacity: 0.85;
			}
			.mvos-band__aside p { margin: 0; }
			.mvos-band__aside--right { right: 48px; text-align: right; width: 37%; }
			.mvos-band__aside--left  { left: 48px; text-align: left; }

			@media (max-width: 782px) {
				.mvos-band__content { max-width: 100%; padding: 28px; }
				.mvos-band__aside { position: static; margin: 0 28px 24px; text-align: left !important; }
			}
			</style>
		<?php endif; ?>

		<!-- ============ SECTION: THE LONG VERSION (off by default) ============ -->
		<?php if ( $mvs_show_body && ( $mvs_lead || $mvs_body ) ) : ?>
			<section class="section mvp-sec-2">
				<div class="wrap mvp-prose">
					<?php if ( $mvs_lead ) : ?>
						<p class="mvp-lead"><?php echo esc_html( $mvs_lead ); ?></p>
					<?php endif; ?>
					<?php echo wp_kses_post( $mvs_body ); ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- ============ SECTION 6: CTA ============ -->
		<?php if ( $mvs_cta_label ) : ?>
			<section class="section mvp-sec mvp-sec--cta">
				<div class="wrap">
					<a class="btn btn--primary" href="<?php echo esc_url( $mvs_cta_link ? $mvs_cta_link : get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
						<?php echo esc_html( $mvs_cta_label ); ?>
					</a>
				</div>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();