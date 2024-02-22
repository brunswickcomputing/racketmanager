<?php
/**
 *
 * Template page to display club teams
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $teams: array of team objects
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

foreach ( $teams as $team ) {
	?>
													<div class="team" id="<?php echo esc_html( $team->title ); ?>">
														<h4 class="title"><?php echo esc_html( $team->title ); ?></h4>
														<form id="team-update-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-Frm" action="" method="post" class="form-control">
		<?php wp_nonce_field( 'team-update', 'racketmanager_nonce' ); ?>
															<input type="hidden" id="team_id" name="team_id" value="<?php echo esc_html( $team->id ); ?>" />
															<input type="hidden" id="event_id" name="event_id" value="<?php echo esc_html( $event->id ); ?>" />
		<?php
		if ( ! empty( $team->captain ) || $user_can_update_club ) {
			?>
																<div class="form-floating mb-1">
																	<input type="text" class="teamcaptain form-control" id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->captain ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
																	<input type="hidden" id="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->captain_id ); ?>" />
																	<label for="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
																	<div id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
																</div>
															<?php } ?>
															<?php
															if ( is_user_logged_in() ) {
																if ( ! empty( $team->contactno ) || $user_can_update_club ) {
																	?>
																	<div class="form-floating mb-1">
																		<input type="tel" class="form-control" id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->contactno ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
																		<label for="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
																		<div id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
																	</div>
																<?php } ?>
																<?php
																if ( ! empty( $team->contactemail ) || $user_can_update_club ) {
																	?>
																	<div class="form-floating mb-1">
																		<input type="email" class="form-control" id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->contactemail ); ?>" size="30" <?php disabled( $user_can_update_club, false ); ?> />
																		<label for="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
																		<div id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
																	</div>
																<?php } ?>
															<?php } ?>
															<?php
															if ( ! empty( $team->match_day ) ) {
																?>
																<div class="form-floating mb-1">
																	<?php
																	if ( $user_can_update_club ) {
																		?>
																		<select class="form-select" size="1" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" >
																			<option><?php esc_html_e( 'Select match day', 'racketmanager' ); ?></option>
																			<?php foreach ( $matchdays as $key => $matchday ) { ?>
																				<option value="<?php echo esc_html( $key ); ?>"
																				<?php
																				if ( isset( $team->match_day ) ) {
																					selected( $matchday, $team->match_day );
																				}
																				?>
																				<?php disabled( $user_can_update_club, false ); ?>><?php echo esc_html( $matchday ); ?></option>
																			<?php } ?>
																		</select>
																		<div id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
																	<?php } else { ?>
																		<input type="text" class="form-control" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->match_day ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
																	<?php } ?>
																	<label for="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
																</div>
															<?php } ?>
															<?php
															if ( ! empty( $team->match_time ) || $user_can_update_club ) {
																?>
																<div class="form-floating mb-1">
																	<input type="time" class="form-control" id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->match_time ); ?>" size="30" <?php disabled( $user_can_update_club, false ); ?> />
																	<label for="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
																	<div id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-feedback" class="invalid-feedback"></div>
																</div>
															<?php } ?>
															<?php
															if ( $user_can_update_club ) {
																?>
																<button class="btn mb-3" type="button" id="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" onclick="Racketmanager.updateTeam(this)">
																	<?php esc_html_e( 'Update details', 'racketmanager' ); ?>
																</button>
																<div class="updateResponse" id="updateTeamResponse-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="updateTeamResponse-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"></div>
															<?php } ?>
														</form>
													</div>
												<?php } ?>
