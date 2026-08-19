<?php
/**
 * woocommerce/emails/email-header.php
 *
 * The top of every Maison Vintique email.
 *
 * Table-based on purpose: Outlook renders through Word, which ignores modern
 * layout entirely. Nested tables and inline attributes are the only things
 * every client agrees on. Everything here is deliberately old-fashioned HTML.
 *
 * The shape, top to bottom:
 *   a thin gold rule across the very top of the card
 *   the dark green band — crest, wordmark, strapline
 *   a diamond ornament separating the band from the title
 *   the title, in serif, with a short gold rule under it
 *   the cream body
 *
 * The crest comes from mve_logo_url(), the same one the site header and footer
 * use, so setting it once in Appearance → Customize → Site Identity puts it
 * everywhere. It used to have its own empty option, which is why no email had
 * a logo on it.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_crest = function_exists( 'mve_logo_url' ) ? mve_logo_url() : get_option( 'mve_email_crest', '' );
$mve_crest = apply_filters( 'mve_email_crest', $mve_crest );
$mve_name  = get_bloginfo( 'name', 'display' );
$mve_strap = apply_filters( 'mve_email_strapline', __( 'A House of Wine · A Legacy of Taste', 'maison-vintique' ) );
$mve_home  = home_url( '/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( $mve_name ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
	<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
		<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
			<tr>
				<td align="center" valign="top">
					<div id="template_header_image"></div>
					<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">

						<?php // A hairline of gold along the very top of the card. ?>
						<tr>
							<td height="3" style="height:3px;line-height:3px;font-size:0;background-color:#a98854;">&nbsp;</td>
						</tr>

						<tr>
							<td align="center" valign="top">

								<!-- Header -->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
									<tr>
										<td id="header_wrapper" style="padding: 38px 40px 34px; text-align: center;">

											<?php if ( $mve_crest ) : ?>
												<a href="<?php echo esc_url( $mve_home ); ?>" style="text-decoration:none;border:0;">
													<img src="<?php echo esc_url( $mve_crest ); ?>"
														alt="<?php echo esc_attr( $mve_name ); ?>"
														width="52" height="52"
														style="display:block;margin:0 auto 18px;border:0;outline:none;width:52px;height:auto;max-width:52px;" />
												</a>
											<?php endif; ?>

											<div style="font-family: Georgia, 'Times New Roman', serif; font-size: 20px; letter-spacing: 6px; color: #f6f1e7; text-transform: uppercase; margin: 0 0 9px; line-height: 1.2;">
												<?php echo esc_html( $mve_name ); ?>
											</div>

											<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; letter-spacing: 3.4px; color: #a98854; text-transform: uppercase; line-height: 1.4;">
												<?php echo esc_html( $mve_strap ); ?>
											</div>

											<?php if ( ! empty( $email_heading ) ) : ?>

												<?php /* Diamond ornament: a rotated square is not safe in email, so it is a character. */ ?>
												<table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 26px auto 0;">
													<tr>
														<td width="60" style="border-bottom:1px solid rgba(169,136,84,0.45);font-size:0;line-height:0;">&nbsp;</td>
														<td style="padding:0 12px;font-family:Georgia,'Times New Roman',serif;font-size:11px;color:#a98854;line-height:1;">&#9670;</td>
														<td width="60" style="border-bottom:1px solid rgba(169,136,84,0.45);font-size:0;line-height:0;">&nbsp;</td>
													</tr>
												</table>

												<h1 style="margin: 20px 0 0;"><?php echo esc_html( $email_heading ); ?></h1>

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
