<?php
/**
 * Template for email notification for cup match
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

global $racketmanager;
/** @var string $competition */
/** @var object $match */
/** @var string $round */
/** @var string $cup_link */
/** @var array  $teams */
/** @var string $rules_link */
$competition_name = $competition;
$match_date    = $match->match_date;
$match_time    = mysql2date( $racketmanager->time_format, $match->date );
$email_subject = __( 'Match Details', 'racketmanager' ) . ' - ' . $round . ' - ' . $competition_name;
?>
<?php require 'email-header.php'; ?>
			<?php
			$title_text = sprintf(
				/* translators: %1$s: competition name %2$s: round name */
				__( '%1$s %2$s Match Confirmation', 'racketmanager' ),
				$match->league->title,
				$round,
			);
			$title_level = '1';
			$title_align = 'center';
			require 'components/title.php';
			$title_align = '';
			?>
			<?php
            $paragraph_text = sprintf(
				/* translators: $s: cup link */
				__( 'Please find below details of your next match in the %s.', 'racketmanager' ),
				$cup_link,
			);
			$paragraph_imbed = true;
			require 'components/paragraph.php';
			$paragraph_imbed = false;
			?>
			<?php
			$paragraph_text = __( 'Click the following button to view the match.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<?php
			$action_link_text = __( 'View match', 'racketmanager' );
			require 'components/action-link.php';
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$title_text  = __( 'Match Details', 'racketmanager' );
			$title_level = '2';
			require 'components/title.php';
			?>
			<?php
			$paragraph_text = __( 'Match to be played on', 'racketmanager' ) . ' ' . $match_date . ' ' . __( 'at', 'racketmanager' ) . ' ' . $match_time . '.';
			require 'components/paragraph.php';
			?>
			<?php
			$opponents = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$team        = $teams[ $opponent ];
				$title_text  = $team->title . ' - ' . $team->name;
				$title_level = '3';
				require 'components/title.php';
				?>
				<div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
					<table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
						<tbody>
							<tr>
								<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
									<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
										<tbody>
											<tr>
												<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
													<table width="100%" style="height: 100%; text-align: left; margin-left: 10px;" role="presentation" cellspacing="0" cellpadding="0">
														<tbody>
															<tr style="line-height: 22px;">
																<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Captain', 'racketmanager' ); ?>:</td>
																<td><?php echo esc_html( $team->captain ); ?></td>
															</tr>
															<tr style="line-height: 22px;">
																<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?>:</td>
																<td><?php echo esc_html( $team->captain_tel ); ?></td>
															</tr>
															<tr style="line-height: 22px;">
																<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Email', 'racketmanager' ); ?>:</td>
																<td><?php echo esc_html( $team->captain_email ); ?></td>
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
			}
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$title_text  = __( 'Rules', 'racketmanager' );
			$title_level = '2';
			require 'components/title.php';
			?>
			<?php
            $paragraph_text  = __( 'The rules for the cup can be found', 'racketmanager' ) . ' <a href="' . $rules_link . '">' . __( 'here', 'racketmanager' ) . '</a>.';
			$paragraph_imbed = true;
			require 'components/paragraph.php';
			$paragraph_imbed = false;
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			if ( ! empty( $email_from ) ) {
				$contact_email = $email_from;
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
