<?php
/**
 * Email contact us section
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $emaillink */
?>
<!-- closing -->
			<div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
				<table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
								<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
									<tbody>
										<tr>
											<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
												<table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
													<tbody>
														<tr>
															<td style="min-width: 5px; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
																<div style="font-size: 16px; color: #000; background-color: transparent; margin: 10px;">
																	<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
																		If you did not initiate this, please <a href="mailto:<?php echo esc_url( $emaillink ); ?>" style="text-decoration: none; color: #006800;">contact the team</a>.
																	</p>
																</div>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
