<?php
/**
 * woocommerce/emails/email-header.php
 *
 * The top of every WooCommerce email. Table-based on purpose: Outlook ignores
 * modern layout entirely, and tables are the only thing every client renders
 * the same way.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_crest = apply_filters( 'mve_email_crest', get_option( 'mve_email_crest', '' ) );
$mve_name  = get_bloginfo( 'name', 'display' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
	<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<div id="template_header_image"></div>
					<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
						<tr>
							<td align="center" valign="top">

								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
									<tr>
										<td id="header_wrapper" style="padding: 34px 40px 30px; text-align: center;">
											<?php if ( $mve_crest ) : ?>
												<img src="<?php echo esc_url( $mve_crest ); ?>" alt="<?php echo esc_attr( $mve_name ); ?>" width="34" style="display:block;margin:0 auto 14px;border:0;outline:none;" />
											<?php endif; ?>

											<div style="font-family: Georgia, 'Times New Roman', serif; font-size: 17px; letter-spacing: 5px; color: #f6f1e7; text-transform: uppercase; margin-bottom: 6px;">
												<?php echo esc_html( $mve_name ); ?>
											</div>
											<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; letter-spacing: 3px; color: #a98854; text-transform: uppercase;">
												<?php echo esc_html( apply_filters( 'mve_email_strapline', __( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ) ) ); ?>
											</div>

											<?php if ( ! empty( $email_heading ) ) : ?>
												<h1 style="margin: 26px 0 0; padding-top: 22px; border-top: 1px solid rgba(246,241,231,0.18);"><?php echo esc_html( $email_heading ); ?></h1>
											<?php endif; ?>
										</td>
									</tr>
								</table>
								<!-- End Header -->

							</td>
						</tr>
						<tr>
							<td align="center" valign="top">

								<!-- Body -->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
									<tr>
										<td valign="top" id="body_content">

											<!-- Content -->
											<table border="0" cellpadding="20" cellspacing="0" width="100%">
												<tr>
													<td valign="top">
														<div id="body_content_inner" style="text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;">
