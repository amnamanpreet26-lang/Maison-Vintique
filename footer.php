<?php
/**
 * The footer for our theme.
 *
 * Closes the document opened in header.php. Renders a four-column brand
 * footer (brand blurb, Explore menu, Trade menu, newsletter signup) plus
 * a bottom bar with social icons and legal links.
 *
 * @package maison-vintique-elementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<footer id="site-footer" class="mv-footer">
		<div class="mv-footer__inner">

			<div class="mv-footer__grid">

				<!-- Column 1: Brand -->
				<div class="mv-footer__col mv-footer__col--brand">
					<div class="mv-footer__brand">
						<span class="mv-footer__crest"><img 
        src="https://lightsteelblue-toad-208486.hostingersite.com/wp-content/uploads/2026/08/crest-white.png"
        alt="Maison Vintique"
    ></span>
						<span class="mv-footer__brand-text">
							<span class="mv-footer__name"><?php bloginfo( 'name' ); ?></span>
							<span class="mv-footer__subname"><?php esc_html_e( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ); ?></span>
						</span>
					</div>

					<p class="mv-footer__desc">
						<?php
						echo esc_html(
							get_theme_mod(
								'mve_footer_description',
								__( 'A premium wine importer and distributor representing a curated portfolio of independent producers.', 'maison-vintique' )
							)
						);
						?>
					</p>
				</div>

				<!-- Column 2: Explore menu -->
				<div class="mv-footer__col mv-footer__col--menu">
					<h3 class="mv-footer__heading"><?php esc_html_e( 'Explore', 'maison-vintique' ); ?></h3>
					<nav class="mv-footer__nav" aria-label="<?php esc_attr_e( 'Explore', 'maison-vintique' ); ?>">
						<?php
						// Appearance → Menus → Display location: "Footer — Explore".
						mve_footer_menu(
							'footer-explore',
							array(
								__( 'Our Collection', 'maison-vintique' ) => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
								__( 'Producers', 'maison-vintique' )      => home_url( '/producers/' ),
								__( 'Journal', 'maison-vintique' )        => home_url( '/journal/' ),
								__( 'Our Story', 'maison-vintique' )      => home_url( '/our-story/' ),
							)
						);
						?>
					</nav>
				</div>

				<!-- Column 3: Trade menu -->
				<div class="mv-footer__col mv-footer__col--menu">
					<h3 class="mv-footer__heading"><?php esc_html_e( 'Trade', 'maison-vintique' ); ?></h3>
					<nav class="mv-footer__nav" aria-label="<?php esc_attr_e( 'Trade', 'maison-vintique' ); ?>">
						<?php
						// Appearance → Menus → Display location: "Footer — Trade".
						mve_footer_menu(
							'footer-trade',
							array(
								__( 'Trade Login', 'maison-vintique' )         => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
								__( 'Apply for an Account', 'maison-vintique' ) => home_url( '/trade/' ),
								__( 'Delivery &amp; Terms', 'maison-vintique' ) => home_url( '/terms/' ),
								__( 'Contact', 'maison-vintique' )             => home_url( '/contact/' ),
							)
						);
						?>
					</nav>
				</div>

				<!-- Column 4: Newsletter -->
				<div class="mv-footer__col mv-footer__col--newsletter">
					<h3 class="mv-footer__heading mv-footer__heading--serif"><?php esc_html_e( 'The Journal, to Your Inbox', 'maison-vintique' ); ?></h3>
					<p class="mv-footer__desc"><?php esc_html_e( 'Estate stories and new arrivals, a few times a year.', 'maison-vintique' ); ?></p>

					<form id="mv-newsletter-form" class="mv-newsletter-form" novalidate>
						<label for="mv-newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Your email', 'maison-vintique' ); ?></label>
						<div class="mv-newsletter-form__row">
							<input
								type="email"
								id="mv-newsletter-email"
								name="email"
								class="mv-newsletter-form__input"
								placeholder="<?php esc_attr_e( 'Your email', 'maison-vintique' ); ?>"
								autocomplete="email"
								required
							/>
							<button type="submit" class="mv-newsletter-form__submit">
								<span class="btn-text"><?php esc_html_e( 'Subscribe', 'maison-vintique' ); ?></span>
								<span class="btn-spinner" aria-hidden="true"></span>
							</button>
						</div>
						<?php wp_nonce_field( 'mve_newsletter_subscribe', 'mve_newsletter_nonce' ); ?>
						<p class="mv-newsletter-form__msg" role="status" aria-live="polite"></p>
					</form>
				</div>

			</div>

			<!-- Bottom bar -->
			<div class="mv-footer__bottom">
				<p class="mv-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'maison-vintique' ); ?></p>

				<div class="mv-footer__bottom-right">
					<?php
					/*
					 * Social links come from Appearance → Customize → Social Links.
					 * Icons are inline SVG from inc/social.php — they used to be
					 * loaded from assets/img/social-*.svg, files that were never
					 * in the theme, so every icon rendered as an empty <li>.
					 */
					$mve_social_links = mve_social_links();
					$mve_networks     = mve_social_networks();

					if ( $mve_social_links ) :
						?>
						<ul class="mv-footer__social" aria-label="<?php esc_attr_e( 'Social media', 'maison-vintique' ); ?>">
							<?php foreach ( $mve_social_links as $mve_network => $mve_url ) : ?>
								<li class="mv-footer__social-item">
									<a href="<?php echo esc_url( $mve_url ); ?>" target="_blank" rel="noopener noreferrer"
										aria-label="<?php echo esc_attr( isset( $mve_networks[ $mve_network ]['label'] ) ? $mve_networks[ $mve_network ]['label'] : ucfirst( $mve_network ) ); ?>">
										<?php echo mve_social_icon( $mve_network ); // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVG, no user input ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<nav class="mv-footer__legal" aria-label="<?php esc_attr_e( 'Legal', 'maison-vintique' ); ?>">
						<?php
						wp_nav_menu( array(
							'theme_location' => 'footer-legal',
							'container'      => false,
							'menu_class'     => 'mv-footer-legal-nav',
							'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
							'fallback_cb'    => function () {
								echo '<ul class="mv-footer-legal-nav">';
								echo '<li><a href="' . esc_url( home_url( '/privacy-policy' ) ) . '">' . esc_html__( 'Privacy', 'maison-vintique' ) . '</a></li>';
								echo '<li><a href="' . esc_url( home_url( '/cookies' ) ) . '">' . esc_html__( 'Cookies', 'maison-vintique' ) . '</a></li>';
								echo '</ul>';
							},
							'depth'          => 1,
						) );
						?>
					</nav>
				</div>
			</div>

		</div>
	</footer>
<?php wp_footer(); ?>
<script>
/**
 * Fade-up reveal for .section.
 *
 * Every section starts at opacity 0 in the CSS, so whatever happens here
 * decides whether the page is visible at all. Three things were making the
 * page below the hero arrive late:
 *
 *   1. threshold 0.15 with a -50px bottom margin meant a section had to be
 *      15% ON SCREEN before it even began its 0.8s fade — so you scrolled,
 *      saw a gap, and the content caught up afterwards. Worse, a section
 *      taller than ~6x the viewport can never show 15% of itself at once, so
 *      it would never have revealed at all.
 *   2. it waited for DOMContentLoaded, so sections already on screen at load
 *      still faded in from nothing instead of just being there.
 *   3. no failsafe: one JS error earlier on the page and everything below the
 *      hero stayed invisible for good.
 *
 * Now: anything on screen at load is shown immediately with no animation,
 * everything else starts its fade ~200px BEFORE it scrolls into view, and if
 * IntersectionObserver is missing the whole lot is simply shown.
 */
(function () {
	var sections = document.querySelectorAll('.section');
	if (!sections.length) return;

	function show(el) {
		el.classList.add('is-visible');
		// The CSS parks will-change on every section, which keeps a compositor
		// layer alive for the life of the page. Once a section has finished
		// revealing it does not need one.
		el.style.willChange = 'auto';
	}

	// No observer support, or the visitor asked for reduced motion: show
	// everything now and skip the animation entirely.
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (!('IntersectionObserver' in window) || reduceMotion) {
		Array.prototype.forEach.call(sections, show);
		return;
	}

	// Start the fade this far before a section reaches the viewport, so it has
	// finished by the time the visitor actually gets to it.
	var LEAD = 200;

	function viewportH() {
		return window.innerHeight || document.documentElement.clientHeight;
	}

	var pending = Array.prototype.slice.call(sections);

	// Reveal everything at or above the trigger line. Deliberately a sweep over
	// positions rather than an IntersectionObserver: jump straight down the
	// page (an anchor link, the End key, a restored scroll position) and a
	// section can go from below the viewport to above it between two frames.
	// IntersectionObserver reports nothing for that — it was not intersecting
	// before and is not intersecting now — so the section stays invisible for
	// good, and you find it blank on the way back up. A position check cannot
	// miss it.
	function sweep() {
		var limit = viewportH() + LEAD;
		pending = pending.filter(function (section) {
			if (section.getBoundingClientRect().top >= limit) return true;
			show(section);
			return false;
		});
		if (!pending.length) stop();
	}

	// Anything already in view when the page opens is shown with no transition
	// — the first screenful should be there, not fade in.
	pending = pending.filter(function (section) {
		if (section.getBoundingClientRect().top >= viewportH()) return true;
		section.style.transition = 'none';
		show(section);
		return false;
	});

	// Flush that style change NOW. Without this read the browser batches the
	// whole lot and only ever sees the end state — transition back on, opacity
	// going 0 to 1 — so the first screenful animates anyway, which is the exact
	// thing we are trying to avoid.
	void document.body.offsetHeight;

	Array.prototype.forEach.call(sections, function (section) {
		section.style.transition = '';
	});

	var queued = false;

	function onScroll() {
		if (queued) return;          // at most one sweep per frame
		queued = true;
		requestAnimationFrame(function () {
			queued = false;
			sweep();
		});
	}

	function stop() {
		window.removeEventListener('scroll', onScroll);
		window.removeEventListener('resize', onScroll);
	}

	if (!pending.length) return;

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onScroll, { passive: true });

	// Catch the sections that sit just below the fold, plus anything that moved
	// once images and fonts had loaded.
	sweep();
	window.addEventListener('load', sweep);
})();
</script>
</body>
</html>