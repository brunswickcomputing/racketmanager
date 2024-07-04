<?php
/**
 * Template for match result pending notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $match;
$competition_name = $match->league->title;
$match_date       = $match->match_date;
$email_subject    = __( 'Match Result Pending', 'racketmanager' ) . ' - ' . $competition_name . ' - ' . $organisation;
?>
<?php require 'email-header.php'; ?>
			<?php require 'components/match-heading.php'; ?>
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
																	<?php
																	$message_detail = 'The result of this match is outstanding';
																	if ( $time_period ) {
																		$message_detail .= ' more than ' . $time_period . ' hours after the match was due to be played';
																	}
																	$message_detail .= '.';
																	?>
																	<p><?php echo esc_html( $message_detail ); ?></p>
																	<p>Please provide the result as soon as possible.</p>
																	<?php
																	if ( $time_period ) {
																		echo '<p>Failure to do so may result in a point deduction.</p>';
																	}
																	?>
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
			<?php $action_link_text = __( 'Enter result', 'racketmanager' ); ?>
			<?php require 'components/action-link.php'; ?>
			<?php
			if ( ! empty( $from_email ) ) {
				$contact_email = $from_email;
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
