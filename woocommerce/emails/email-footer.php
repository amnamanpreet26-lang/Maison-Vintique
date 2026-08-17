<?php
/**
 * woocommerce/emails/email-footer.php
 *
 * Closes the tables opened by email-header.php and prints the sign-off.
 *
 * @package maison-vintique-elementor
 */

defined( 'ABSPATH' ) || exit;

$mve_footer_text = get_option( 'woocommerce_email_footer_text' );
$mve_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
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
								<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
									<tr>
										<td valign="top" style="padding: 0; background-color: #faf8f3; border-top: 1px solid #d6cec1; border-radius: 0 0 6px 6px;">
											<table border="0" cellpadding="10" cellspacing="0" width="100%">
												<tr>
													<td colspan="2" valign="middle" id="credit">
														<?php if ( $mve_footer_text ) : ?>
															<?php echo wp_kses_post( wpautop( wptexturize( $mve_footer_text ) ) ); ?>
														<?php else : ?>
															<p style="margin:0 0 8px;">
																<a href="<?php echo esc_url( $mve_account_url ); ?>" style="color:#a98854;">
																	<?php esc_html_e( 'Your trade account', 'maison-vintique' ); ?>
																</a>
															</p>
															<p style="margin:0;">
																<?php
																printf(
																	/* translators: %s: site name */
																	esc_html__( '%s — trade supply to the UK on-trade and independent merchants.', 'maison-vintique' ),
																	esc_html( get_bloginfo( 'name', 'display' ) )
																);
																?>
															</p>
														<?php endif; ?>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<!-- End Footer -->

							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>
