<?php
/**
 * Trade Login — the logged-out /my-account/ page.
 *
 * Theme override path: your-theme/woocommerce/myaccount/form-login.php
 *
 * Left card: the real WooCommerce login form (same field names, nonce and
 * hooks as the default template, so login, "remember me", redirects and any
 * security plugin keep working).
 *
 * Right card: the trade-application panel from the design. If WooCommerce
 * account registration is enabled it renders the real registration form;
 * otherwise it shows the benefits checklist and links to the trade
 * application page.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$mve_registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );

/**
 * Where "Apply for an account" points when registration is disabled.
 * Filter this to send applicants at a Gravity/Contact Form 7 page instead.
 */
$mve_apply_url = apply_filters( 'mve_trade_application_url', home_url( '/trade/' ) );
?>

<main>
<section class="section login-page">
<div class="wrap">

	<div class="page-h page-h--center">
		<p class="eyebrow"><?php esc_html_e( 'Trade Access', 'maison-vintique-elementor' ); ?></p>
		<h1><?php esc_html_e( 'Trade Login', 'maison-vintique-elementor' ); ?></h1>
		<p class="login-lede">
			<?php
			echo wp_kses_post(
				__( 'You can <b>order online</b> (your basket is submitted as an order request for approval) or <b>order by pro forma</b> — we confirm stock, pricing and delivery, then issue a pro forma invoice. Payment is taken only after approval.', 'maison-vintique-elementor' )
			);
			?>
		</p>
	</div>

	<div class="auth">

		<!-- ---------------- Sign in ---------------- -->
		<div class="auth-card">
			<h2><?php esc_html_e( 'Sign in', 'maison-vintique-elementor' ); ?></h2>
			<p class="sub"><?php esc_html_e( 'Access your trade prices, orders and documents.', 'maison-vintique-elementor' ); ?></p>

			<form class="woocommerce-form woocommerce-form-login login" method="post">

				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<div class="field">
					<label for="username"><?php esc_html_e( 'Email address', 'maison-vintique-elementor' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="you@business.com" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
				</div>

				<div class="field">
					<label for="password"><?php esc_html_e( 'Password', 'maison-vintique-elementor' ); ?>&nbsp;<span class="required">*</span></label>
					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
				</div>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="rowbtwn">
					<label class="woocommerce-form__label-for-checkbox">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
						<span><?php esc_html_e( 'Remember me', 'maison-vintique-elementor' ); ?></span>
					</label>
					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'maison-vintique-elementor' ); ?></a>
				</div>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

				<button type="submit" class="btn btn-p btn-block woocommerce-button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Sign in', 'maison-vintique-elementor' ); ?>">
					<?php esc_html_e( 'Sign in', 'maison-vintique-elementor' ); ?>
				</button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>

			</form>
		</div>

		<!-- ------------ Apply for an account ------------ -->
		<div class="auth-card auth-alt">
			<h2><?php esc_html_e( 'Apply for a trade account', 'maison-vintique-elementor' ); ?></h2>
			<p class="sub"><?php esc_html_e( 'For restaurants, hotels and merchants.', 'maison-vintique-elementor' ); ?></p>

			<ul class="checklist">
				<li><?php esc_html_e( 'Your agreed trade pricing, visible once approved', 'maison-vintique-elementor' ); ?></li>
				<li><?php esc_html_e( 'Order online, or by pro forma — no impersonal checkout', 'maison-vintique-elementor' ); ?></li>
				<li><?php esc_html_e( 'Technical sheets, tasting notes &amp; sell sheets', 'maison-vintique-elementor' ); ?></li>
				<li><?php esc_html_e( 'Invoices, statements and delivery notes in one place', 'maison-vintique-elementor' ); ?></li>
			</ul>

			<?php if ( $mve_registration_enabled ) : ?>

				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<div class="field">
							<label for="reg_username"><?php esc_html_e( 'Username', 'maison-vintique-elementor' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						</div>
					<?php endif; ?>

					<div class="field">
						<label for="reg_email"><?php esc_html_e( 'Email address', 'maison-vintique-elementor' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>" />
					</div>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<div class="field">
							<label for="reg_password"><?php esc_html_e( 'Password', 'maison-vintique-elementor' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
						</div>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

					<button type="submit" class="btn btn-o btn-block woocommerce-Button woocommerce-button" name="register" value="<?php esc_attr_e( 'Apply for an account', 'maison-vintique-elementor' ); ?>">
						<?php esc_html_e( 'Apply for an account', 'maison-vintique-elementor' ); ?>
					</button>

					<?php do_action( 'woocommerce_register_form_end' ); ?>

				</form>

			<?php else : ?>

				<a class="btn btn-o btn-block" href="<?php echo esc_url( $mve_apply_url ); ?>">
					<?php esc_html_e( 'Apply for an account', 'maison-vintique-elementor' ); ?>
				</a>

			<?php endif; ?>

			<p class="auth-note">
				<?php esc_html_e( 'Applications are reviewed personally — usually within one working day.', 'maison-vintique-elementor' ); ?>
			</p>
		</div>

	</div>

</div>
</section>
</main>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
