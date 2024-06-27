<?php
/**
 * Template for favourite notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;

$email_subject = __( 'Match Result - Favourite Notification', 'racketmanager' );
require 'email-header.php';
?>
			<?php
			$title_text  = __( 'Favourite Result', 'racketmanager' );
			$title_level = '1';
			require 'components/title.php';
			?>
			<?php
			$salutation_link = $user->first_name;
			?>
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
															<td style="min-width: 5px; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="center" bgcolor="#fff" valign="top">
																<div style="font-size: 16px; color: #000; background-color: transparent; margin: 10px;">
																	<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
																		<table class="body-action" aria-describedby="<?php esc_html_e( 'outside url wrapping action', 'racketmanager' ); ?>">
																			<?php $match_url .= 'day' . $match->match_day . '/'; ?>
																			<tr>
																				<td class="align-right team"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( $match->teams['home']->title ); ?></a></td>
																				<td class="align-center"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( $match->score ); ?></a></td>
																				<td class="align-left team"><a style="text-decoration: none; color: #006800;" href="<?php echo esc_html( $match_url ); ?>"><?php echo esc_html( $match->teams['away']->title ); ?></a></td>
																			</tr>
																		</table>
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
			<?php
			if ( ! empty( $from_email ) ) {
				$contact_email = $from_email;
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/fav-link-text.php'; ?>
<?php
require 'email-footer.php';
