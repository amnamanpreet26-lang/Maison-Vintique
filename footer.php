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
						<span class="mv-footer__crest"><?php echo mve_inline_svg( 'logo-crest.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="mv-footer__brand-text">
							<span class="mv-footer__name"><?php bloginfo( 'name' ); ?></span>
							<span class="mv-footer__subname"><?php esc_html_e( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ); ?></span>
						</span>
					</div>

					<p class="mv-footer__tagline"><em><?php esc_html_e( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ); ?></em></p>

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
					<ul class="mv-footer__social" aria-label="<?php esc_attr_e( 'Social media', 'maison-vintique' ); ?>">
						<?php
						/**
						 * Filter the social links shown in the footer.
						 * Leave a value empty ('') to hide that icon.
						 */
						$mve_social_links = apply_filters( 'mve_footer_social_links', array(
							'instagram' => 'https://instagram.com/maisonvintique',
							'facebook'  => '',
							'twitter'   => '',
							'pinterest' => '',
							'linkedin'  => '',
						) );

						foreach ( $mve_social_links as $mve_network => $mve_url ) {
							if ( empty( $mve_url ) ) {
								continue;
							}
							?>
							<li class="mv-footer__social-item">
								<a href="<?php echo esc_url( $mve_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $mve_network ) ); ?>">
									<?php echo mve_inline_svg( 'social-' . $mve_network . '.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>

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
	document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('.section');
    if (!sections.length) return;
    const observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    // Animate only once
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        }
    );
    sections.forEach(function (section) {
        observer.observe(section);
    });
});
</script>
</body>
</html>