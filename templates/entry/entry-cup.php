<?php
/**
 *
 * Template page to display a cup entry form
 *
 * @package Racketmanager/Templates
 * The following variables are usable:
 *  $events: events object
 *  $season: season name
 *  $type: competition type
 *  $mens_teams: male teams object
 *  $ladies_teams: female teams object
 *  $mixed_teams: mixed teams object
 *  $weekdays: days of week array
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

?>
<div class="container">
	<?php
	$competition_season = $competition->current_season;
	require RACKETMANAGER_PATH . 'templates/includes/competition-header.php';
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
								<?php esc_html_e( 'Enter online', 'racketmanager' ); ?>
							</h3>
							<?php
							if ( ! empty( $competition->closing_date ) ) {
								?>
								<span class="hgroup__subheading">
									<?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
									<time datetime="<?php echo esc_attr( $competition->closing_date ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $competition->closing_date ) ); ?></time>
								</span>
								<?php
							}
							?>
						</div>
						<div class="entry-subhead__aside">
							<a role="button" href="/rules/cup-rules/" target="_blank" class="btn btn-primary">
								<?php esc_html_e( 'Cup Rules', 'racketmanager' ); ?>
							</a>
						</div>
					</div>
					<div class="club-entry__body">
						<div id="club-details">
							<input type="hidden" name="affiliatedClub" id="affiliatedClub" value="<?php echo esc_html( $club->id ); ?>" />
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
						<ol class="list list--naked">
							<li id="liEventDetails" class="club-entry__panel">
								<div id="entryDetails">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php
											echo esc_html__( 'Select all events your wish to enter', 'racketmanager' );
											?>
										</p>
									</div>
									<div class="form-checkboxes">
										<?php
										foreach ( $events as $event ) {
											if ( empty( $event->team ) ) {
												$event->team = new \stdClass();
											}
											?>
											<div class="form-check form-checkboxes__item eventList">
												<input class="form-check-input form-checkboxes__input eventId" id="event-<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" type="checkbox" value=<?php echo esc_html( $event->id ); ?> aria-controls="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo esc_attr( $event->status ); ?>>
												<label class="form-check-label form-label form-checkboxes__label" for="event-<?php echo esc_html( $event->id ); ?>">
													<?php echo esc_html( $event->name ); ?>
												</label>
											</div>
											<?php
											if ( 'MD' === $event->type ) {
												$team_list = $mens_teams;
											} elseif ( 'WD' === $event->type ) {
												$team_list = $ladies_teams;
											} elseif ( 'XD' === $event->type ) {
												$team_list = $mixed_teams;
											}
											?>
											<div class="form-checkboxes__conditional <?php echo $event->status ? '' : 'form-checkboxes__conditional--hidden'; ?>" id="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $event->status ? 'aria-expanded="true"' : ''; ?>>
												<div class="form-floating mb-3">
													<select size="1" class="cupteam form-select" name="team[<?php echo esc_html( $event->id ); ?>]" id="team-<?php echo esc_html( $event->id ); ?>" >
														<option value=""><?php esc_html_e( 'Select team', 'racketmanager' ); ?></option>
														<?php
														foreach ( $team_list as $team ) {
															?>
															<option value="<?php echo esc_html( $team->id ); ?>" <?php ( ! empty( $event->team->team_id ) ? selected( $team->id, $event->team->team_id ) : '' ); ?>><?php echo esc_html( $team->title ); ?></option>
															<?php
														}
														?>
													</select>
													<label class="form-label" for="team-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Team', 'racketmanager' ); ?></label>
												</div>
												<div class="mb-3" id="notify-<?php echo esc_html( $event->id ); ?>"></div>
												<div id="splash-<?php echo esc_html( $event->id ); ?>" class="d-none">
													<div class="d-flex justify-content-center">
														<div class="spinner-border" role="status">
														<span class="visually-hidden">Loading...</span>
														</div>
													</div>
												</div>
												<div class="mb-3" id="team-dtls-<?php echo esc_html( $event->id ); ?>">
													<div class="form-floating mb-3 form-group match-time">
														<select class="form-select" name="matchday[<?php echo esc_html( $event->id ); ?>]" id="matchday-<?php echo esc_html( $event->id ); ?>">
															<?php
															foreach ( $weekdays as $key => $weekday ) {
																?>
																<option value="<?php echo esc_html( $key ); ?>" <?php ( ! empty( $event->team->team_id ) ? selected( $key, $event->team->team_info->match_day ) : '' ); ?>><?php echo esc_html( $weekday ); ?></option>
																<?php
															}
															?>
														</select>
														<label class="form-label" for="matchday-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
													</div>
													<div class="form-floating mb-3 form-group match-time">
														<input type="time" class="form-control" name="matchtime[<?php echo esc_html( $event->id ); ?>]" id="matchtime-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->match_time ) ? esc_html( $event->team->team_info->match_time ) : ''; ?>" />
														<label class="form-label" for="matchtime-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
													</div>
													<div id="captain-dtls-<?php echo esc_html( $event->id ); ?>">
														<div class="form-floating mb-3">
															<input type="text" class="form-control teamcaptain" name="captain[<?php echo esc_html( $event->id ); ?>]" id="captain-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->captain ) ? esc_html( $event->team->team_info->captain ) : ''; ?>" />
															<input type="hidden" name="captainId[<?php echo esc_html( $event->id ); ?>]" id="captainId-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->captain_id ) ? esc_html( $event->team->team_info->captain_id ) : ''; ?>" />
															<label class="form-label" for="captain-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
														</div>
														<div class="form-floating mb-3">
															<input type="tel" class="form-control" name="contactno[<?php echo esc_html( $event->id ); ?>]" id="contactno-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->contactno ) ? esc_html( $event->team->team_info->contactno ) : ''; ?>" />
															<label class="form-label" for="contactno-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
														</div>
														<div class="form-floating mb-3">
															<input type="email" class="form-control" name="contactemail[<?php echo esc_html( $event->id ); ?>]" id="contactemail-<?php echo esc_html( $event->id ); ?>" value="<?php echo ! empty( $event->team->team_info->contactemail ) ? esc_html( $event->team->team_info->contactemail ) : ''; ?>" />
															<label class="form-label" for="contactemail-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
														</div>
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
											<?php echo esc_html_e( 'Please leave any additional information for the Organiser here', 'racketmanager' ); ?>
										</p>
									</div>
									<div class="col-12 col-md-8">
										<div class="form-floating">
											<textarea class="form-control" placeholder="<?php echo esc_attr_e( 'Additional information', 'racketmanager' ); ?>" id="commentDetails" name="commentDetails"></textarea>
											<label for="commentDetails"><?php esc_attr_e( 'Additional information', 'racketmanager' ); ?></label>
										</div>
									</div>
								</div>
							</li>
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
								<input class="form-check-input switch" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false">
							</div>
						</div>
					</div>
					<div class="club-entry__footer">
						<div class="updateResponse mb-3" id="entryResponse" name="entryResponse"></div>
						<div class="btn__group">
							<div class="club-entry__submit">
								<button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" onclick="Racketmanager.entryRequest(event, 'cup')"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></button>
							</div>
							<a role="button" href="/clubs/<?php echo esc_html( seo_url( $club->shortcode ) ); ?>/" class="btn btn--cancel"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
