<?php
/**
 * Template for email notification for match
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var object $tournament */
/** @var string $round */
/** @var string $draw_link */
/** @var array  $teams */
/** @var string $rules_link */
$competition_name = $match->league->title;
$tournament_name  = $tournament->name;
$tournament_date  = $tournament->date;
$tournament_venue = $tournament->venue_name;
$match_date       = $match->match_date;
$email_subject    = __( 'Next match confirmation', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
?>
<?php require 'email-header.php'; ?>
			<?php
            $title_text = sprintf(
				/* translators: %1$s: league name %2$s: round name */
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
				/* translators: %1$s: draw link %2$s: tournament link */
				__( 'Please find below details of your next match in the %1$s event in the %2$s tournament.', 'racketmanager' ),
				$draw_link,
				$tournament->link,
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
			$paragraph_text = __( 'Match to be played by', 'racketmanager' ) . ' ' . $match_date;
			require 'components/paragraph.php';
			?>
			<?php
			$opponents = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$title_text  = $teams[ $opponent ]->title;
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
															<?php
															foreach ( $teams[ $opponent ]->player as $player ) {
																?>
																<tr style="line-height: 22px;">
																	<td style="width: 50%; font-weight: normal;"><?php echo esc_html( $player->fullname ); ?></td>
																	<td><?php echo esc_html( $teams[ $opponent ]->club ); ?></td>
																</tr>
																<?php
															}
															?>
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
			$title_text  = __( 'Contact Details', 'racketmanager' );
			$title_level = '2';
			require 'components/title.php';
			?>
			<?php
			$opponents = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$title_text  = $teams[ $opponent ]->title;
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
															<?php
															foreach ( $teams[ $opponent ]->player as $player ) {
																if ( $player->contactno ) {
																	?>
																	<tr style="line-height: 22px;">
																		<td style="width: 50%; font-weight: normal;"><?php echo esc_html( $player->fullname ); ?></td>
																		<td><a href="tel:<?php echo esc_html( $player->contactno ); ?>"><?php echo esc_html( $player->contactno ); ?></a></td>
																	</tr>
																	<?php
																}
															}
															?>
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
			$paragraph_text  = __( 'The rules for the tournament can be found', 'racketmanager' ) . ' <a href="' . $rules_link . '">' . __( 'here', 'racketmanager' ) . '</a>.';
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
