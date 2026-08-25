<?php
/**
 * inc/age-gate.php
 *
 * "Are you 18 or over?" — the legal-drinking-age gate.
 *
 * IT FAILS CLOSED, ON PURPOSE.
 *
 * The overlay is printed straight after <body> and is visible by DEFAULT, in
 * CSS. It is JavaScript that takes it away, not JavaScript that puts it up. So
 * if a script fails, is blocked, or simply has not run yet, the site stays
 * covered rather than flashing into view. For an alcohol retailer that is the
 * only safe way round.
 *
 * NO FLASH EITHER. A tiny script in <head> checks whether this visitor has
 * already confirmed and, if so, marks the <html> element before the browser
 * paints anything — so a returning visitor never sees the gate blink.
 *
 * Answering "No" does not simply close the box: the overlay stays, the page
 * behind it is never reachable, and it says goodbye instead. There is no way
 * out of that state except leaving.
 *
 * WHERE TO EDIT THE WORDS
 *   Appearance → Customize → Age Verification.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

/** The key the browser remembers the answer under. */
const MVE_AGE_KEY = 'mvAgeConfirmed';

/**
 * Should the gate be printed on this request?
 *
 * @return bool
 */
function mve_show_age_gate() {
	if ( is_admin() ) {
		return false;
	}

	// Switched off in the Customizer.
	if ( ! get_theme_mod( 'mve_age_enabled', true ) ) {
		return false;
	}

	/*
	 * ?mv_age=preview forces it even after it has been answered, so it can be
	 * looked at without clearing browser storage every time.
	 */
	if ( isset( $_GET['mv_age'] ) && 'preview' === $_GET['mv_age'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
		return true;
	}

	return (bool) apply_filters( 'mve_show_age_gate', true );
}

/**
 * How long an answer is remembered.
 *
 * @return int Days.
 */
function mve_age_days() {
	return max( 0, (int) apply_filters( 'mve_age_remember_days', (int) get_theme_mod( 'mve_age_days', 30 ) ) );
}

/**
 * Mark the page as already-confirmed BEFORE it paints.
 *
 * This is deliberately inline and deliberately first: an external file would
 * be fetched after the browser had already drawn a frame, and the gate would
 * flash on every page for somebody who answered it days ago.
 */
function mve_age_gate_head() {
	if ( ! mve_show_age_gate() ) {
		return;
	}

	$preview = isset( $_GET['mv_age'] ) && 'preview' === $_GET['mv_age']; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$days    = mve_age_days();
	?>
	<script id="mv-age-early">
	(function () {
		var root = document.documentElement;

		function answered() {
			if (<?php echo $preview ? 'true' : 'false'; ?>) { return false; }
			try {
				var raw = window.localStorage.getItem('<?php echo esc_js( MVE_AGE_KEY ); ?>');
				if (!raw) { return false; }
				var days = <?php echo (int) $days; ?>;
				// 0 days means "remember for good".
				if (days > 0 && (Date.now() - parseInt(raw, 10)) > days * 86400000) { return false; }
				return true;
			} catch (e) {
				/* Private browsing can throw on storage. Ask again. */
				return false;
			}
		}

		/*
		 * The scroll lock is added HERE, never in the stylesheet. A rule like
		 * `html { overflow: hidden }` would keep the whole site unscrollable
		 * for everybody the moment the gate was switched off in the Customizer,
		 * because nothing would ever be left to take it off again.
		 */
		root.className += answered() ? ' mv-age-ok' : ' mv-age-locked';
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'mve_age_gate_head', 1 );

/**
 * The gate itself, printed immediately after <body>.
 *
 * Not a <dialog>: a dialog is invisible until showModal() runs, which means it
 * cannot block anything if scripts fail. A plain overlay that CSS shows by
 * default can.
 */
function mve_render_age_gate() {
	static $printed = false;

	if ( $printed || ! mve_show_age_gate() ) {
		return;
	}
	$printed = true;

	$title   = get_theme_mod( 'mve_age_title', __( 'Are you 18 or over?', 'maison-vintique' ) );
	$text    = get_theme_mod( 'mve_age_text', __( 'To access our website, you must be of legal drinking age.', 'maison-vintique' ) );
	$yes     = get_theme_mod( 'mve_age_yes', __( 'Yes', 'maison-vintique' ) );
	$no      = get_theme_mod( 'mve_age_no', __( 'No', 'maison-vintique' ) );
	$decor   = get_theme_mod( 'mve_age_image', '' );

	$bye_title = get_theme_mod( 'mve_age_bye_title', __( 'Come back another time', 'maison-vintique' ) );
	$bye_text  = get_theme_mod( 'mve_age_bye_text', __( 'We are sorry — you must be of legal drinking age to enter this website.', 'maison-vintique' ) );

	// The two legal links. Both fall back to whatever WordPress and WooCommerce
	// already know, so they are right without anybody typing a URL.
	$terms = get_theme_mod( 'mve_age_terms_url', '' );
	if ( ! $terms && function_exists( 'wc_get_page_permalink' ) ) {
		$terms = wc_get_page_permalink( 'terms' );
	}
	$privacy = get_theme_mod( 'mve_age_privacy_url', '' );
	if ( ! $privacy ) {
		$privacy = get_privacy_policy_url();
	}
	?>
	<div class="mv-age<?php echo $decor ? ' mv-age--decor' : ''; ?>"
		id="mv-age-gate"
		role="dialog"
		aria-modal="true"
		aria-labelledby="mv-age-title"
		data-days="<?php echo esc_attr( (string) mve_age_days() ); ?>"
		data-key="<?php echo esc_attr( MVE_AGE_KEY ); ?>"
		<?php if ( $decor ) : ?>style="--mv-age-decor:url('<?php echo esc_url( $decor ); ?>')"<?php endif; ?>>

		<div class="mv-age__inner">

			<?php // The hairline frame, inset from the edge with a gap at each corner. ?>
			<span class="mv-age__frame" aria-hidden="true"></span>

			<?php
			/*
			 * Botanical corners. Drawn here rather than shipped as images so
			 * they are crisp at any size and cost nothing to load; upload the
			 * real artwork in the Customizer to replace them.
			 */
			if ( ! $decor ) {
				mve_age_gate_flourishes();
			}
			?>

			<div class="mv-age__panel" data-mv-age-panel>
				<h2 class="mv-age__title" id="mv-age-title"><?php echo esc_html( $title ); ?></h2>

				<?php if ( $text ) : ?>
					<p class="mv-age__text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>

				<?php if ( $terms || $privacy ) : ?>
					<p class="mv-age__legal">
						<?php
						if ( $terms && $privacy ) {
							printf(
								/* translators: 1: terms link, 2: privacy link */
								esc_html__( 'By entering this site, you agree to our %1$s and acknowledge that you have read and understood our %2$s.', 'maison-vintique' ),
								'<a href="' . esc_url( $terms ) . '">' . esc_html__( 'Terms and Conditions', 'maison-vintique' ) . '</a>',
								'<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy Policy', 'maison-vintique' ) . '</a>'
							);
						} elseif ( $privacy ) {
							printf(
								/* translators: %s: privacy link */
								esc_html__( 'By entering this site, you acknowledge that you have read and understood our %s.', 'maison-vintique' ),
								'<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy Policy', 'maison-vintique' ) . '</a>'
							);
						} else {
							printf(
								/* translators: %s: terms link */
								esc_html__( 'By entering this site, you agree to our %s.', 'maison-vintique' ),
								'<a href="' . esc_url( $terms ) . '">' . esc_html__( 'Terms and Conditions', 'maison-vintique' ) . '</a>'
							);
						}
						?>
					</p>
				<?php endif; ?>

				<div class="mv-age__actions">
					<button type="button" class="mv-age__btn mv-age__btn--yes" data-mv-age="yes"><?php echo esc_html( $yes ); ?></button>
					<button type="button" class="mv-age__btn mv-age__btn--no" data-mv-age="no"><?php echo esc_html( $no ); ?></button>
				</div>
			</div>

			<?php // Shown instead of the panel above when they answer "No". ?>
			<div class="mv-age__panel mv-age__panel--bye" data-mv-age-bye hidden>
				<h2 class="mv-age__title"><?php echo esc_html( $bye_title ); ?></h2>
				<?php if ( $bye_text ) : ?>
					<p class="mv-age__text"><?php echo esc_html( $bye_text ); ?></p>
				<?php endif; ?>
			</div>

		</div>
	</div>
	<?php
}
add_action( 'wp_body_open', 'mve_render_age_gate', 1 );

/*
 * Belt and braces. wp_body_open() is where this belongs — as early in the page
 * as it can be — but a Theme Builder header or an older template can leave that
 * hook out entirely, and an age check that silently never prints is the worst
 * possible failure. So the footer prints it too, and mve_render_age_gate()
 * refuses to print twice.
 */
add_action( 'wp_footer', 'mve_render_age_gate', 1 );

/**
 * Vine, leaves and a small bunch of grapes, in each corner.
 *
 * One symbol, used four times and flipped with CSS, so the file stays small.
 */
function mve_age_gate_flourishes() {
	?>
	<svg class="mv-age__seed" aria-hidden="true" focusable="false" width="0" height="0">
		<symbol id="mv-age-vine" viewBox="0 0 200 200">
			<g fill="none" stroke="currentColor" stroke-width="1.15" stroke-linecap="round" stroke-linejoin="round">
				<?php // The stem, sweeping in from the corner. ?>
				<path d="M2 2c22 6 41 19 56 38 16 20 27 45 33 73" />

				<?php // Three leaves along it, each on its own short stalk. ?>
				<path d="M44 34c-1-13 5-25 17-33 4 14 0 27-17 33z" />
				<path d="M44 34c1-11 5-22 12-31" opacity=".5" />

				<path d="M70 74c9-10 23-15 37-13-6 13-19 20-37 13z" />
				<path d="M70 74c9-6 20-10 30-11" opacity=".5" />

				<path d="M88 116c-13-2-24-9-31-20 14-2 26 5 31 20z" />
				<path d="M88 116c-8-4-16-10-22-17" opacity=".5" />

				<?php // A tendril, curling off the stem. ?>
				<path d="M30 18c9 5 11 14 5 18-5 4-12 0-11-6 1-8 11-12 21-7" opacity=".7" />

				<?php // The bunch hangs from the stem rather than floating free. ?>
				<path d="M95 128c3 6 5 12 6 18" opacity=".7" />
			</g>

			<g fill="currentColor" opacity=".5">
				<?php // Grapes: a proper tapering bunch, close to the vine. ?>
				<circle cx="92" cy="150" r="7.4" /><circle cx="108" cy="150" r="7.4" />
				<circle cx="100" cy="162" r="7.4" /><circle cx="116" cy="162" r="7.4" />
				<circle cx="86" cy="163" r="7.4" />
				<circle cx="94" cy="175" r="7.4" /><circle cx="110" cy="175" r="7.4" />
				<circle cx="102" cy="187" r="7.4" />
			</g>
		</symbol>
	</svg>

	<span class="mv-age__corner mv-age__corner--tl" aria-hidden="true"><svg viewBox="0 0 200 200"><use href="#mv-age-vine"></use></svg></span>
	<span class="mv-age__corner mv-age__corner--tr" aria-hidden="true"><svg viewBox="0 0 200 200"><use href="#mv-age-vine"></use></svg></span>
	<span class="mv-age__corner mv-age__corner--bl" aria-hidden="true"><svg viewBox="0 0 200 200"><use href="#mv-age-vine"></use></svg></span>
	<span class="mv-age__corner mv-age__corner--br" aria-hidden="true"><svg viewBox="0 0 200 200"><use href="#mv-age-vine"></use></svg></span>
	<?php
}

/* =========================================================================
 * ONE POPUP AT A TIME
 * ====================================================================== */

/**
 * The trade-pricing popup never shows while the age gate is switched on.
 *
 * Two overlays stacked on a first visit is nobody's idea of a welcome, and the
 * age gate has to be answered first anyway.
 *
 * @param bool $show Whether to show it.
 * @return bool
 */
function mve_age_gate_suppresses_popup( $show ) {
	return mve_show_age_gate() ? false : $show;
}
add_filter( 'mve_show_trade_popup', 'mve_age_gate_suppresses_popup', 20 );

/* =========================================================================
 * CUSTOMIZER
 * ====================================================================== */

/**
 * Appearance → Customize → Age Verification.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function mve_age_gate_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'mve_age',
		array(
			'title'       => __( 'Age Verification', 'maison-vintique' ),
			'priority'    => 90,
			'description' => __( 'Shown to every visitor before they can use the site. Answering "No" closes the site for them. Once answered it is remembered in their browser for the number of days below. Add ?mv_age=preview to any address to see it again yourself.', 'maison-vintique' ),
		)
	);

	$fields = array(
		'mve_age_enabled'     => array(
			'label'    => __( 'Show the age check', 'maison-vintique' ),
			'type'     => 'checkbox',
			'default'  => true,
			'sanitize' => 'mve_sanitize_checkbox',
		),
		'mve_age_title'       => array(
			'label'    => __( 'Question', 'maison-vintique' ),
			'type'     => 'text',
			'default'  => __( 'Are you 18 or over?', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_age_text'        => array(
			'label'    => __( 'Text under it', 'maison-vintique' ),
			'type'     => 'textarea',
			'default'  => __( 'To access our website, you must be of legal drinking age.', 'maison-vintique' ),
			'sanitize' => 'sanitize_textarea_field',
		),
		'mve_age_yes'         => array(
			'label'    => __( 'Yes button', 'maison-vintique' ),
			'type'     => 'text',
			'default'  => __( 'Yes', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_age_no'          => array(
			'label'    => __( 'No button', 'maison-vintique' ),
			'type'     => 'text',
			'default'  => __( 'No', 'maison-vintique' ),
			'sanitize' => 'sanitize_text_field',
		),
		'mve_age_bye_title'   => array(
			'label'       => __( 'Heading after "No"', 'maison-vintique' ),
			'type'        => 'text',
			'default'     => __( 'Come back another time', 'maison-vintique' ),
			'description' => __( 'The site stays closed behind this. There is no way past it.', 'maison-vintique' ),
			'sanitize'    => 'sanitize_text_field',
		),
		'mve_age_bye_text'    => array(
			'label'    => __( 'Text after "No"', 'maison-vintique' ),
			'type'     => 'textarea',
			'default'  => __( 'We are sorry — you must be of legal drinking age to enter this website.', 'maison-vintique' ),
			'sanitize' => 'sanitize_textarea_field',
		),
		'mve_age_terms_url'   => array(
			'label'       => __( 'Terms and Conditions link', 'maison-vintique' ),
			'type'        => 'url',
			'default'     => '',
			'description' => __( 'Leave empty to use the WooCommerce terms page.', 'maison-vintique' ),
			'sanitize'    => 'esc_url_raw',
		),
		'mve_age_privacy_url' => array(
			'label'       => __( 'Privacy Policy link', 'maison-vintique' ),
			'type'        => 'url',
			'default'     => '',
			'description' => __( 'Leave empty to use the WordPress privacy policy page.', 'maison-vintique' ),
			'sanitize'    => 'esc_url_raw',
		),
		'mve_age_image'       => array(
			'label'       => __( 'Decorative background image', 'maison-vintique' ),
			'type'        => 'url',
			'default'     => '',
			'description' => __( 'Optional. A PNG with a transparent middle — the botanical corners. Upload it to the Media Library and paste the URL here. Leave empty to use the vine drawing the theme ships with.', 'maison-vintique' ),
			'sanitize'    => 'esc_url_raw',
		),
		'mve_age_days'        => array(
			'label'       => __( 'Days to remember the answer', 'maison-vintique' ),
			'type'        => 'number',
			'default'     => 30,
			'description' => __( 'How long before a visitor is asked again. 0 means never ask them again.', 'maison-vintique' ),
			'sanitize'    => 'absint',
		),
	);

	$priority = 10;
	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'       => $field['label'],
				'section'     => 'mve_age',
				'type'        => $field['type'],
				'priority'    => $priority,
				'description' => isset( $field['description'] ) ? $field['description'] : '',
			)
		);
		$priority += 10;
	}
}
add_action( 'customize_register', 'mve_age_gate_customizer' );
