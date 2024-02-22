<?php
/**
 * Template for club player registration by email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$email_subject = $organisation . ' Club Player Request - ' . $club;
require 'email-header.php';
?>
			<?php $salutation_link = ''; ?>
			<?php require 'components/salutation.php'; ?>
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
																	<h2 class="align-center">Player <?php echo esc_html( ucfirst( $action ) ); ?></h2>
																	<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
																		A new player has been added for <?php echo esc_html( $club ); ?>.
																	</p>
																	<?php if ( $player ) { ?>
																		<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
																			<?php echo esc_html( $player ); ?>
																		</p>
																	<?php } ?>
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
			<?php
			if ( 'request' === $action ) {
				$action_link_text = __( 'View request', 'racketmanager' );
				require 'components/action-link.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php
			if ( 'request' === $action ) {
				require 'components/link-text.php';
			}
			?>
<?php
require 'email-footer.php';
