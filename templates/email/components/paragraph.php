<?php
/**
 * Email paragraph section
 *
 * @package Racketmanager/Templates/Email
 */
namespace Racketmanager;

/** @var string $paragraph_text */
?>
<!-- introduction -->
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
																	<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;
																	<?php
																	if ( isset( $paragraph_format ) ) {
																		if ( 'bold' === $paragraph_format ) {
																			echo ' font-weight: 900;';
																		} elseif ( 'italic' === $paragraph_format ) {
																			echo ' font-style: italic;';
																		} elseif ( 'italic-small' === $paragraph_format ) {
																			echo ' font-style: italic; font-size: 14px;';
																		}
																	}
																	?>
																	">
																		<?php
																		if ( empty( $paragraph_imbed ) ) {
																			echo esc_html( $paragraph_text );
																		} else {
																			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																			echo $paragraph_text;
																		}
																		?>
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
