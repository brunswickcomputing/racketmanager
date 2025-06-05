<?php
/**
 * Email match heading section
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $competition_name */
/** @var object $match */
?><!-- match heading -->
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
																	<h1 style="font-size: 24px; font-weight: 900; line-height: 1.25; margin: 0;" align="center">
																		<?php echo esc_html( $competition_name ); ?>
																	</h1>
																	<h2 style="font-size: 20px; font-weight: 700; line-height: 1.25; margin: 0;" align="center">
																		<?php echo esc_html( $match->get_title() ); ?>
																	</h2>
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
