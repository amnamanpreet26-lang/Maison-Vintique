<?php
/**
 * woocommerce/emails/email-styles.php
 *
 * Inline CSS for every WooCommerce email. Email clients strip stylesheets and
 * most of them ignore anything clever, so this is deliberately plain: tables,
 * hex colours, no custom properties, no flexbox, web-safe fallback fonts.
 *
 * WooCommerce runs the result through Emogrifier, which inlines these rules
 * onto the elements — which is why the selectors are simple.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_ground = '#f2eee6';
$mve_panel  = '#faf8f3';
$mve_ink    = '#4a4038';
$mve_soft   = '#6f675e';
$mve_dark   = '#17251f';
$mve_gold   = '#a98854';
$mve_line   = '#d6cec1';
// Cormorant Garamond will not load in most email clients — Georgia is the
// closest thing that is installed everywhere.
$mve_serif  = 'Georgia, "Times New Roman", serif';
$mve_sans   = '"Helvetica Neue", Helvetica, Arial, sans-serif';
?>
body {
	margin: 0;
	padding: 0;
	background-color: <?php echo esc_attr( $mve_ground ); ?>;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	color: <?php echo esc_attr( $mve_ink ); ?>;
	-webkit-font-smoothing: antialiased;
}

#wrapper {
	background-color: <?php echo esc_attr( $mve_ground ); ?>;
	margin: 0;
	padding: 32px 12px;
	width: 100%;
}

#template_container {
	background-color: #ffffff;
	border: 1px solid <?php echo esc_attr( $mve_line ); ?>;
	border-radius: 6px;
	box-shadow: none;
}

#template_header {
	background-color: <?php echo esc_attr( $mve_dark ); ?>;
	border-radius: 6px 6px 0 0;
	color: #ffffff;
	font-family: <?php echo $mve_serif; // phpcs:ignore ?>;
	font-weight: normal;
	padding: 0;
}

#template_header h1 {
	color: #ffffff;
	font-family: <?php echo $mve_serif; // phpcs:ignore ?>;
	font-size: 26px;
	font-weight: normal;
	line-height: 1.3;
	margin: 0;
	padding: 0;
	text-align: center;
	text-shadow: none;
}

#template_header_image img {
	margin-left: 0;
	margin-right: 0;
}

#body_content {
	background-color: #ffffff;
}

#body_content table td {
	padding: 34px 40px 28px;
}

#body_content p {
	color: <?php echo esc_attr( $mve_ink ); ?>;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	font-size: 15px;
	line-height: 1.7;
	margin: 0 0 16px;
}

#body_content h2 {
	color: <?php echo esc_attr( $mve_dark ); ?>;
	font-family: <?php echo $mve_serif; // phpcs:ignore ?>;
	font-size: 22px;
	font-weight: normal;
	line-height: 1.3;
	margin: 0 0 14px;
	text-align: left;
}

#body_content h3 {
	color: <?php echo esc_attr( $mve_dark ); ?>;
	font-family: <?php echo $mve_serif; // phpcs:ignore ?>;
	font-size: 18px;
	font-weight: normal;
	margin: 26px 0 10px;
}

#body_content a {
	color: <?php echo esc_attr( $mve_gold ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

.mv-btn a,
a.mv-btn {
	background-color: <?php echo esc_attr( $mve_dark ); ?>;
	border-radius: 4px;
	color: #ffffff !important;
	display: inline-block;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	font-size: 12px;
	font-weight: bold;
	letter-spacing: 1.4px;
	padding: 15px 30px;
	text-decoration: none !important;
	text-transform: uppercase;
}

.mv-note {
	background-color: <?php echo esc_attr( $mve_panel ); ?>;
	border-left: 3px solid <?php echo esc_attr( $mve_gold ); ?>;
	padding: 16px 18px;
	margin: 0 0 18px;
}

.mv-note p {
	margin: 0;
	font-size: 14px;
}

.mv-meta {
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	font-size: 12px;
	color: <?php echo esc_attr( $mve_soft ); ?>;
	letter-spacing: 0.6px;
	text-transform: uppercase;
	margin: 0 0 6px;
}

#template_footer td {
	padding: 0;
	border-radius: 0 0 6px 6px;
}

#template_footer #credit {
	border: 0;
	color: <?php echo esc_attr( $mve_soft ); ?>;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	font-size: 12px;
	line-height: 1.7;
	text-align: center;
	padding: 24px 40px 30px;
}

#template_footer #credit a {
	color: <?php echo esc_attr( $mve_soft ); ?>;
}

.td,
.address {
	color: <?php echo esc_attr( $mve_soft ); ?>;
	border-color: <?php echo esc_attr( $mve_line ); ?>;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
	font-size: 14px;
}

.text {
	color: <?php echo esc_attr( $mve_ink ); ?>;
	font-family: <?php echo $mve_sans; // phpcs:ignore ?>;
}

h2.woocommerce-order-details__title,
.order-details h2 {
	font-family: <?php echo $mve_serif; // phpcs:ignore ?>;
	color: <?php echo esc_attr( $mve_dark ); ?>;
}
