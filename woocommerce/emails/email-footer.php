<?php
/**
 * woocommerce/emails/email-footer.php
 *
 * Closes the tables opened by email-header.php and prints the sign-off.
 *
 * Three bands: a cream strip with the wordmark and the useful links, a darker
 * strip with the legal line and the address, and a gold hairline to close the
 * card the same way it opened.
 *
 * The address, telephone and links are all editable — see the fields at the top
 * of WooCommerce → Settings → Emails. Anything left empty is simply skipped, so
 * a half-filled footer still looks deliberate.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_footer_text = get_option( 'woocommerce_email_footer_text' );
$mve_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
$mve_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$mve_name        = get_bloginfo( 'name', 'display' );

$mve_address = trim( (string) get_option( 'mve_email_address_line', '' ) );
$mve_phone   = trim( (string) get_option( 'mve_email_phone', '' ) );
$mve_from    = get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) );
?>
														</div>
													</td>
												</tr>
											</table>
											<!-- End Content -->

										</td>
									</tr>
								</table>
								<!-- End Body -->

							</td>
						</tr>
						<tr>
							<td align="center" valign="top">

								<!-- Footer -->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer">
									<tr>
										<td valign="top" style="padding: 0;">

											<?php // ---- cream strip: wordmark + links ---- ?>
											<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#faf8f3;border-top:1px solid #e4dccf;">
												<tr>
													<td align="center" style="padding: 30px 40px 24px;">

														<div style="font-family: Georgia, 'Times New Roman', serif; font-size: 13px; letter-spacing: 4px; color: #17251f; text-transform: uppercase; margin-bottom: 16px;">
															<?php echo esc_html( $mve_name ); ?>
														</div>

														<?php if ( $mve_footer_text ) : ?>
															<div style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#6f675e;">
																<?php echo wp_kses_post( wpautop( wptexturize( $mve_footer_text ) ) ); ?>
															</div>
														<?php else : ?>

															<table border="0" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
																<tr>
																	<td style="padding:0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;">
																		<a href="<?php echo esc_url( $mve_shop_url ); ?>" style="color:#17251f;text-decoration:none;"><?php esc_html_e( 'The Portfolio', 'maison-vintique' ); ?></a>
																	</td>
																	<td style="font-family:Georgia,serif;color:#c8bda9;font-size:10px;">&#9670;</td>
																	<td style="padding:0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;">
																		<a href="<?php echo esc_url( $mve_account_url ); ?>" style="color:#17251f;text-decoration:none;"><?php esc_html_e( 'Your Account', 'maison-vintique' ); ?></a>
																	</td>
																	<?php if ( $mve_from ) : ?>
																		<td style="font-family:Georgia,serif;color:#c8bda9;font-size:10px;">&#9670;</td>
																		<td style="padding:0 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;">
																			<a href="mailto:<?php echo esc_attr( $mve_from ); ?>" style="color:#17251f;text-decoration:none;"><?php esc_html_e( 'Contact Us', 'maison-vintique' ); ?></a>
																		</td>
																	<?php endif; ?>
																</tr>
															</table>

															<p style="margin:18px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#6f675e;">
																<?php
																printf(
																	/* translators: %s: site name */
																	esc_html__( '%s — trade supply to the UK on-trade and independent merchants.', 'maison-vintique' ),
																	esc_html( $mve_name )
																);
																?>
															</p>

														<?php endif; ?>

													</td>
												</tr>
											</table>

											<?php // ---- dark strip: the small print ---- ?>
											<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#17251f;">
												<tr>
													<td align="center" style="padding: 20px 40px 22px;">

														<?php if ( $mve_address || $mve_phone ) : ?>
															<p style="margin:0 0 8px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#9aa39d;">
																<?php
																echo esc_html( $mve_address );
																if ( $mve_address && $mve_phone ) {
																	echo ' &nbsp;·&nbsp; ';
																}
																echo esc_html( $mve_phone );
																?>
															</p>
														<?php endif; ?>

														<p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:10.5px;line-height:1.7;color:#7c857f;">
															<?php
															printf(
																/* translators: 1: year, 2: site name */
																esc_html__( '© %1$s %2$s. Please drink responsibly. Alcohol is supplied to licensed trade customers only, aged 18 or over.', 'maison-vintique' ),
																esc_html( gmdate( 'Y' ) ),
																esc_html( $mve_name )
															);
															?>
														</p>

													</td>
												</tr>
											</table>

										</td>
									</tr>
								</table>
								<!-- End Footer -->

							</td>
						</tr>

						<?php // Closes the card with the same gold hairline it opened with. ?>
						<tr>
							<td height="3" style="height:3px;line-height:3px;font-size:0;background-color:#a98854;">&nbsp;</td>
						</tr>

					</table>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
