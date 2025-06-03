<?php
/**
 * Send fixtures email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $captain */
/** @var string $competition */
/** @var string $season */
/** @var array  $matches */
/** @var object $team */
$email_subject = __( 'Fixtures', 'racketmanager' );
require 'email-header.php';
?>
	<?php
            $salutation_link = $captain; ?>
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
																	<p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0 0 20px; padding: 0;">
																		Please find attached your fixture list for the <?php echo esc_html( $competition ); ?> <?php echo esc_html( $season ); ?> season.  If you could check your details and notify me of errors.
																	</p>
																	<table class="fixtures" aria-describedby="<?php esc_html_e( 'Fixtures', 'racketmanager' ); ?>">
																		<thead>
																			<tr class="align-center bold">
																			<th><?php esc_html_e( 'Round', 'racketmanager' ); ?></th>
																			<th><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
																			<th><?php esc_html_e( 'Day', 'racketmanager' ); ?></th>
																			<th><?php esc_html_e( 'Time', 'racketmanager' ); ?></th>
																			<th class="align-right"><?php esc_html_e( 'Home', 'racketmanager' ); ?></th>
																			<th></th>
																			<th class="align-left"><?php esc_html_e( 'Away', 'racketmanager' ); ?></th>
																			</tr>
																		</thead>
																		<tbody>
																			<?php foreach ( $matches as $match ) { ?>
																			<tr class="align-center">
																				<td><?php the_match_day(); ?></td>
																				<td><?php echo esc_html( mysql2date( 'd M y', $match->date ) ); ?></td>
																				<td><?php echo esc_html( mysql2date( 'D', $match->date ) ); ?></td>
																				<td><?php the_match_time( $match->start_time ); ?></td>
																				<td class="align-right
																				<?php
																				if ( $match->home_team === $team->id ) {
																					echo ' bold';
																				}
																				?>
																				">
																					<?php echo esc_html( $match->teams['home']->title ); ?>
																				</td>
																				<td>-</td>
																				<td class="align-left
																				<?php
																				if ( $match->away_team === $team->id ) {
																					echo ' bold';
																				}
																				?>
																				">
																					<?php echo esc_html( $match->teams['away']->title ); ?>
																				</td>
																			</tr>
																			<?php } ?>
																		</tbody>
																	</table>
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
			<?php $action_link_text = __( 'View fixtures', 'racketmanager' ); ?>
			<?php require 'components/action-link.php'; ?>
			<?php
			if ( ! empty( $contact_email ) ) {
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
