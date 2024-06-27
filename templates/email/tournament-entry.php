<?php
/**
 * Tournament entry email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

$email_subject = __( 'Tournament Entry', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );
require 'email-header.php';
?>
			<?php
			$title_text  = __( 'Entry confirmation', 'racketmanager' );
			$title_level = '1';
			require 'components/title.php';
			?>
			<?php
			$salutation_link = $player->fullname;
			require 'components/salutation.php';
			?>
			<?php
			/* translators: $s: tournament link */
			$paragraph_text  = sprintf( __( 'Thank you for your entry for the %s tournament. You will find confirmation of your entry below.', 'racketmanager' ), $tournament_link );
			$paragraph_imbed = true;
			require 'components/paragraph.php';
			$paragraph_imbed = false;
			?>
			<?php
			$paragraph_text = __( 'Click the following button if you want to view or change your entry if necessary.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<?php
			$action_link_text = __( 'View entry', 'racketmanager' );
			require 'components/action-link.php';
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$title_text  = __( 'Entry Details', 'racketmanager' );
			$title_level = '2';
			require 'components/title.php';
			?>
			<?php
			$title_text  = __( 'Personal Details', 'racketmanager' );
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
															<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'First name', 'racketmanager' ); ?>:</td>
															<td><?php echo esc_html( $player->firstname ); ?></td>
														</tr>
														<tr style="line-height: 22px;">
															<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Last name', 'racketmanager' ); ?>:</td>
															<td><?php echo esc_html( $player->surname ); ?></td>
														</tr>
														<tr style="line-height: 22px;">
															<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?>:</td>
															<td><?php echo esc_html( $player->contactno ); ?></td>
														</tr>
														<tr style="line-height: 22px;">
															<td style="width: 150px; font-size: 14px; font-weight: 500;"><?php esc_html_e( 'LTA Number', 'racketmanager' ); ?>:</td>
															<td><?php echo esc_html( $player->btm ); ?></td>
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
			<?php require 'components/hr.php'; ?>
			<?php
			$title_text  = __( 'Events', 'racketmanager' );
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
														$t = 1;
														foreach ( $tournament_entries as $tournament_entry ) {
															?>
															<tr style="line-height: 22px;">
																<td style="width: 150px; font-size: 14px; font-weight: 500; vertical-align: top;"><?php esc_html_e( 'Event', 'racketmanager' ); ?> <?php echo esc_html( $t ); ?>:</td>
																<td>
																	<?php echo esc_html( $tournament_entry['event_name'] ); ?>
																	<?php
																	if ( isset( $tournament_entry['partner'] ) ) {
																		?>
																		<br>
																		<?php echo esc_html( $tournament_entry['partner'] ); ?>
																		<?php
																	}
																	?>
																</td>
															</tr>
															<?php
															++$t;
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
			if ( ! empty( $comments ) ) {
				require 'components/hr.php';
				$title_text  = __( 'Additional comments', 'racketmanager' );
				$title_level = '3';
				require 'components/title.php';
				$paragraph_text = $comments;
				require 'components/paragraph.php';
			}
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$paragraph_text = __( 'You will be notified when the draws have taken place.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<?php
			if ( ! empty( $contact_email ) ) {
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
<?php
require 'email-footer.php';
