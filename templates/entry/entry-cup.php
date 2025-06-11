<?php
/**
 *
 * Template page to display a cup entry form
 *
 * @package Racketmanager/Templates
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

use stdClass;

/** @var object $club */
/** @var object $competition */
/** @var object $events */
/** @var array  $mens_teams */
/** @var array  $ladies_teams */
/** @var array  $mixed_teams */
/** @var array  $match_days */
/** @var string $season */
global $racketmanager;
$no_entry_link = true;
if ( $competition->is_open ) {
	$changes_allowed = true;
} else {
	$changes_allowed = false;
}
if ( ! empty( $club->entry ) ) {
	$entered    = true;
	$form_title = __( 'Entry details', 'racketmanager' );
} else {
	$entered    = false;
	$form_title = __( 'Enter online', 'racketmanager' );
}
?>
<div class="container">
	<?php
	require_once RACKETMANAGER_PATH . 'templates/includes/competition-header.php';
	?>
	<form id="form-entry" action="" method="post">
		<?php wp_nonce_field( 'cup-entry' ); ?>
		<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
		<input type="hidden" name="competitionId" value="<?php echo esc_html( $competition->id ); ?>" />
		<input type="hidden" name="competitionType" value="<?php echo esc_html( $competition->type ); ?>" />
		<div class="module module--card">
			<div class="module__content">
				<div class="module-container">
					<div class="entry-subhead">
						<div class="hgroup">
							<h3 class="hgroup__heading">
								<?php echo esc_html( $form_title ); ?>
							</h3>
							<?php
							if ( ! empty( $competition->date_closing ) ) {
								?>
								<span class="hgroup__subheading">
									<?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
									<time datetime="<?php echo esc_attr( $competition->date_closing ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $competition->date_closing ) ); ?></time>
								</span>
								<?php
							}
							?>
						</div>
						<div class="entry-subhead__aside">
							<a href="/rules/cup-rules/" target="_blank" class="btn btn-primary">
								<?php esc_html_e( 'Cup Rules', 'racketmanager' ); ?>
							</a>
						</div>
					</div>
					<?php
					if ( ! $changes_allowed ) {
						if ( $entered ) {
							$alert_class = 'info';
							$alert_msg[] = __( 'Cup entries are now closed.', 'racketmanager' );
							$alert_msg[] = __( 'These are the latest entry details.', 'racketmanager' );
						} else {
							$alert_class = 'warning';
							$alert_msg[] = __( 'Cup not currently open for entries', 'racketmanager' );
						}
						?>
						<div class="alert_rm mt-3 alert--<?php echo esc_attr( $alert_class ); ?>">
							<div class="alert__body">
								<?php
								foreach ( $alert_msg as $msg ) {
									?>
									<div class="alert__body-inner">
										<?php echo esc_html( $msg ); ?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
						<?php
					}
					?>
					<div class="club-entry__body">
						<div id="club-details">
							<input type="hidden" name="clubId" id="clubId" value="<?php echo esc_html( $club->id ); ?>" />
							<div class="media">
								<div class="media__wrapper">
									<div class="media__img">
										<span class="profile-icon">
											<span class="profile-icon__abbr">
												<?php
												$words    = explode( ' ', $club->shortcode );
												$initials = null;
												foreach ( $words as $w ) {
													$initials .= $w[0];
												}
												echo esc_html( $initials );
												?>
											</span>
										</span>
									</div>
									<div class="media__content">
										<h4 class="media__title"><?php echo esc_html( $club->name ); ?></h4>
									</div>
								</div>
							</div>
						</div>
						<ol class="list list--naked" id="entry-details">
							<li id="liEventDetails" class="club-entry__panel">
								<div id="entryDetails">
									<div class="hgroup" id="event">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php
											echo esc_html__( 'Select all events your wish to enter', 'racketmanager' );
											?>
										</p>
									</div>
									<div id="eventFeedback" class="invalid-feedback"></div>
									<div class="form-checkboxes">
										<?php
										foreach ( $events as $event ) {
											if ( empty( $event->team ) ) {
												$event->team = new stdClass();
											}
											?>
											<div class="form-check form-check-lg">
												<input class="form-check-input eventId noModal" id="event-<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" type="checkbox" value=<?php echo esc_html( $event->id ); ?> aria-controls="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo esc_attr( $event->status ); ?> <?php echo $changes_allowed ? null : 'disabled'; ?>>
												<label class="form-check-label" for="event-<?php echo esc_html( $event->id ); ?>">
													<?php echo esc_html( $event->name ); ?>
												</label>
												<div id="event-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
											</div>
											<?php
											if ( 'MD' === $event->type ) {
												$team_list = $mens_teams;
											} elseif ( 'WD' === $event->type ) {
												$team_list = $ladies_teams;
											} elseif ( 'XD' === $event->type ) {
												$team_list = $mixed_teams;
											} else {
                                                $team_list = array();
											}
											?>
											<div class="form-checkboxes__conditional <?php echo $event->status ? '' : 'form-checkboxes__conditional--hidden'; ?>" id="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $event->status ? 'aria-expanded="true"' : ''; ?>>
												<div class="form-floating mb-3">
													<select size="1" class="cupteam form-select" name="team[<?php echo esc_html( $event->id ); ?>]" id="team-<?php echo esc_html( $event->id ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?>>
														<option value="" disabled <?php selected( empty( $event->team->team_id ), true ); ?>><?php esc_html_e( 'Select team', 'racketmanager' ); ?></option>
														<?php
														foreach ( $team_list as $team ) {
															?>
															<option value="<?php echo esc_html( $team->id ); ?>" <?php selected( $team->id, empty( $event->team->team_id ) ? null : $event->team->team_id ); ?>><?php echo esc_html( $team->title ); ?></option>
															<?php
														}
														?>
													</select>
													<label class="form-label" for="team-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Team', 'racketmanager' ); ?></label>
													<div id="team-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
												</div>
												<div class="mb-3" id="notify-<?php echo esc_html( $event->id ); ?>"></div>
												<div id="splash-<?php echo esc_html( $event->id ); ?>" class="d-none">
													<div class="d-flex justify-content-center">
														<output class="spinner-border">
    														<span class="visually-hidden">Loading...</span>
														</output>
													</div>
												</div>
												<div class="mb-3" id="team-dtls-<?php echo esc_html( $event->id ); ?>">
													<div class="row">
														<fieldset class="col-md-6">
															<legend><?php esc_html_e( 'Captain', 'racketmanager' ); ?></legend>
															<div id="captain-dtls-<?php echo esc_html( $event->id ); ?>">
																<div class="row">
																	<div class="form-floating mb-3">
																		<input type="text" class="form-control teamcaptain" name="captain[<?php echo esc_html( $event->id ); ?>]" id="captain-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->captain ) ? esc_html( $event->team->team_info->captain ) : ''; ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
																		<input type="hidden" name="captainId[<?php echo esc_html( $event->id ); ?>]" id="captainId-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->captain_id ) ? esc_html( $event->team->team_info->captain_id ) : ''; ?>" />
																		<label class="form-label" for="captain-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
																		<div id="captain-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
																	</div>
																	<div class="col-md-6 form-floating mb-3">
																		<input type="tel" class="form-control" name="contactno[<?php echo esc_html( $event->id ); ?>]" id="contactno-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->contactno ) ? esc_html( $event->team->team_info->contactno ) : ''; ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
																		<label class="form-label" for="contactno-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
																		<div id="contactno-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
																	</div>
																	<div class="col-md-6 form-floating mb-3">
																		<input type="email" class="form-control" name="contactemail[<?php echo esc_html( $event->id ); ?>]" id="contactemail-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->contactemail ) ? esc_html( $event->team->team_info->contactemail ) : ''; ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
																		<label class="form-label" for="contactemail-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
																		<div id="contactemail-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
																	</div>
																</div>
															</div>
														</fieldset>
														<fieldset class="col-md-6">
															<legend><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
															<div class="row">
																<div class="col-md-6 form-floating mb-3 form-group match-time">
																	<select class="form-select" name="matchday[<?php echo esc_html( $event->id ); ?>]" id="matchday-<?php echo esc_html( $event->id ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?>>
																		<?php
																		foreach ( $match_days as $key => $match_day ) {
																			?>
																			<option value="<?php echo esc_html( $key ); ?>" <?php ( selected( $match_day, empty( $event->team->team_info->match_day ) ? null : $event->team->team_info->match_day ) ); ?>><?php echo esc_html( $match_day ); ?></option>
																			<?php
																		}
																		?>
																	</select>
																	<label class="form-label" for="matchday-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
																	<div id="matchday-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
																</div>
																<div class="col-md-6 form-floating mb-3 form-group match-time">
																	<input type="time" class="form-control" name="matchtime[<?php echo esc_html( $event->id ); ?>]" id="matchtime-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->match_time ) ? esc_html( $event->team->team_info->match_time ) : ''; ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
																	<label class="form-label" for="matchtime-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
																	<div id="matchtime-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
																</div>
															</div>
														</fieldset>
													</div>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
							</li>
							<li id="liCommentDetails" class="club-entry__panel">
								<div id="comment_Details">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Additional information', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php esc_html_e( 'Please leave any additional information for the Organiser here', 'racketmanager' ); ?>
										</p>
									</div>
									<div class="col-12 col-md-8">
										<div class="form-floating">
											<textarea class="form-control" placeholder="<?php esc_attr_e( 'Additional information', 'racketmanager' ); ?>" id="commentDetails" name="commentDetails" <?php echo $changes_allowed ? null : 'disabled'; ?>></textarea>
											<label for="commentDetails"><?php esc_attr_e( 'Additional information', 'racketmanager' ); ?></label>
											<div id="commentDetailsFeedback" class="invalid-feedback"></div>
										</div>
									</div>
								</div>
							</li>
							<?php require_once RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                        </ol>
						<div id="entry-acceptance" class="col-12 col-md-8">
							<div class="form-check form-switch form-check-reverse mb-3">
								<label class="form-check-label switch" for="acceptance">
									<?php
									$rules_link = '<a href="/rules/cup-rules" target="_blank">' . __( 'the rules', 'racketmanager' ) . '</a>';
									/* Translators: %s: link to tournament rules */
									printf( __( 'I agree to abide by %s.', 'racketmanager' ), $rules_link ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</label>
								<input class="form-check-input switch" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false" <?php echo $changes_allowed ? null : 'disabled'; ?> />
								<div id="acceptanceFeedback" class="invalid-feedback"></div>
							</div>
						</div>
					</div>
					<div class="club-entry__footer">
						<div class="alert_rm" id="entryAlert" style="display:none;">
							<div class="alert__body">
								<div class="alert__body-inner" id="entryAlertResponse">
								</div>
							</div>
						</div>
						<div class="btn__group">
							<div class="club-entry__submit">
								<button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" data-type="cup"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></button>
							</div>
							<a href="/clubs/<?php echo esc_html( seo_url( $club->shortcode ) ); ?>/" class="btn btn--cancel"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	<?php require_once RACKETMANAGER_PATH . 'js/entry-link.js'; ?>
</script>
