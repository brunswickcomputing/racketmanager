<?php
/**
 *
 * Template page to display a tournament entry form
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $tournament: tournament object
 *  $events: events object
 *  $player: player object
 *  $club_players: club Players array
 *  $season: season name
 *  $type: event type
 *  $malePartners: male partners object
 *  $femalePartners: female partners object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

?>
	<div class="media tournament-head">
		<div class="media__wrapper">
			<div class="media__img"></div>
			<div class="media__content">
				<h1 class="media__title"><?php echo esc_html( $tournament->name ) . ' - ' . esc_html__( 'Tournament', 'racketmanager' ); ?></h1>
				<div class="media__content-subinfo">
					<small class="media__subheading">
						<?php echo esc_html( $tournament->venue_name ); ?>
					</small>
					<?php
					if ( ! empty( $tournament->date_start ) && ! empty( $tournament->date ) ) {
						?>
						<small class="media__subheading">
							<i class="racketmanager-svg-icon small">
								<?php racketmanager_the_svg( 'icon-calendar' ); ?>
							</i>
							<?php echo esc_html( mysql2date( 'j M', $tournament->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( 'j M', $tournament->date ) ); ?>
						</small>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<form id="form-entry" action="" method="post">
		<?php wp_nonce_field( 'tournament-entry' ); ?>
		<input type="hidden" name="tournamentId" value="<?php echo esc_html( $tournament->id ); ?>" />
		<input type="hidden" name="season" value="<?php echo esc_html( $tournament->season ); ?>" />
		<div class="module module--card">
			<div class="module__content">
				<div class="module-container">
					<div class="entry-subhead">
						<div class="hgroup">
							<h3 class="hgroup__heading">
								<?php esc_html_e( 'Enter online', 'racketmanager' ); ?>
							</h3>
							<span class="hgroup__subheading">
								<?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
								<time datetime="<?php echo esc_attr( $tournament->closing_date ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->closing_date ) ); ?></time>
							</span>
						</div>
						<div class="entry-subhead__aside">
							<a role="button" href="/rules/tournament-rules/" target="_blank" class="btn btn-primary">
								<?php esc_html_e( 'Tournament Rules', 'racketmanager' ); ?>
							</a>
						</div>
					</div>
					<div class="individual-entry__body">
						<div id="personal-details">
							<div class="media">
								<div class="media__wrapper">
									<div class="media__img">
										<span class="profile-icon">
											<span class="profile-icon__abbr">
												<?php
												$player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
												echo esc_html( $player_initials );
												?>
											</span>
										</span>
									</div>
									<div class="media__content">
										<h4 class="media__title"><?php echo esc_html( $player->display_name ); ?></h4>
										<span class="media__subheading">
											<?php
											switch ( $player->gender ) {
												case 'M':
													$gender = __( 'Male', 'racketmanager' );
													break;
												case 'F':
													$gender = __( 'Female', 'racketmanager' );
													break;
												default:
													$gender = '';
											}
											?>
											<span><?php echo esc_html( $gender ); ?></span>
											<?php
											if ( ! empty( $player->age ) ) {
												?>
												<span>, <?php echo esc_html( $player->age ); ?></span>
												<?php
											}
											?>
										</span>
									</div>
								</div>
							</div>
						</div>
						<ol class="list list--naked">
							<li id="liPlayerDetails" class="individual-entry__panel">
								<div id="playerDetails">
									<input type="hidden" id="playerId" name="playerId" value="<?php echo esc_html( $player->ID ); ?>" />
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'My details', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading"><?php esc_html_e( 'Check if your details are correct, and change them if necessary', 'racketmanager' ); ?></p>
									</div>
									<div class="row">
										<div id="contactDetails" class="col-12 col-md-6">
											<div class="border p-3">
												<h5 class="subheading"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></h5>
												<dl class="list list--flex">
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'Phone', 'racketmanager' ); ?></dt>
														<dd class="list__value">
															<input type="tel" class="form-control" id="contactno" name="contactno" value="<?php echo esc_html( $player->contactno ); ?>" />
														</dd>
													</div>
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'Email', 'racketmanager' ); ?></dt>
														<dd class="list__value">
															<input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo esc_html( $player->user_email ); ?>" />
														</dd>
													</div>
												</dl>
											</div>
										</div>
										<div id="membershipDetails" class="col-12 col-md-6">
											<div class="border p-3">
												<h5 class="subheading"><?php esc_html_e( 'Membership', 'racketmanager' ); ?></h5>
												<dl class="list list--flex">
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'Club', 'racketmanager' ); ?></dt>
														<dd class="list__value">
															<?php
															switch ( count( $club_players ) ) {
																case 1:
																	?>
																	<input type="text" class="form-control" id="affiliatedclubname" name="affiliatedclubname" value="<?php echo esc_html( get_club( $club_players[0]->affiliatedclub )->name ); ?>" disabled />
																	<input type="hidden" id="affiliatedclub" name="affiliatedclub" value="<?php echo esc_html( $club_players[0]->affiliatedclub ); ?>" />
																	<?php
																	break;
																case 0:
																	esc_html_e( 'You must be a member of a club to enter a tournament', 'racketmanager' );
																	?>
																	<input type="hidden" id="affiliatedclub" name="affiliatedclub" value="" />
																	<?php
																	break;
																default:
																	?>
																	<select class="form-select" size="1" name="affiliatedclub" id="affiliatedclub" >
																		<option value="0"><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
																		<?php
																		foreach ( $club_players as $club_player ) {
																			$club = get_club( $club_player->affiliatedclub );
																			?>
																			<option value="<?php echo esc_html( $club->id ); ?>"><?php echo esc_html( $club->name ); ?></option>
																			<?php
																		}
																		?>
																	</select>
																	<?php
																	break;
															}
															?>
														</dd>
													</div>
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'LTA Number', 'racketmanager' ); ?></dt>
														<dd class="list__value">
															<input type="number" class="form-control" id="btm" name="btm" value="<?php echo esc_html( $player->btm ); ?>" />
														</dd>
													</div>
												</dl>
											</div>
										</div>
									</div>
								</div>
							</li>
							<li id="liEventDetails" class="individual-entry__panel">
								<div id="entryDetails">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php
											if ( empty( $player->age ) ) {
												$age = 'unknown';
											} else {
												$age = $player->age;
											}
											/* Translators: %1$s: gender %2$s age. */
											echo esc_html( sprintf( __( 'Events are filtered by your gender (%1$s) and playing age (%2$s)', 'racketmanager' ), $gender, $age ) );
											?>
										</p>
									</div>
									<div class="form-checkboxes">
										<?php
										foreach ( $events as $event ) {
											$entered    = false;
											$partner_id = null;
											if ( isset( $player->entry[ $event->id ] ) ) {
												$entered      = true;
												$player_entry = $player->entry[ $event->id ];
												$partner_id   = ! empty( $player_entry->partner_id ) ? $player_entry->partner_id : null;
											}
											?>
											<div class="form-check form-checkboxes__item">
												<input class="form-check-input form-checkboxes__input" id="event-<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" type="checkbox" value=<?php echo esc_html( $event->id ); ?> aria-controls="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $entered ? 'checked' : ''; ?>>
												<label class="form-check-label form-label form-checkboxes__label" for="event-<?php echo esc_html( $event->id ); ?>">
													<?php echo esc_html( $event->name ); ?>
												</label>
											</div>
											<?php
											if ( substr( $event->type, 1, 1 ) === 'D' ) {
												if ( 'M' === $player->gender ) {
													if ( substr( $event->type, 0, 1 ) === 'M' ) {
														$partner_list = $male_partners;
													} else {
														$partner_list = $female_partners;
													}
												} elseif ( 'F' === $player->gender ) {
													if ( substr( $event->type, 0, 1 ) === 'W' ) {
														$partner_list = $female_partners;
													} else {
														$partner_list = $male_partners;
													}
												}
												?>
												<div class="form-checkboxes__conditional <?php echo $partner_id ? '' : 'form-checkboxes__conditional--hidden'; ?>" id="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $partner_id ? 'aria-expanded="true"' : ''; ?>>
													<label class="form-label" for="partner-<?php echo esc_html( $event->id ); ?>"><?php esc_html_e( 'Partner', 'racketmanager' ); ?></label>
													<select class="form-select" size="1" name="partner[<?php echo esc_html( $event->id ); ?>]" id="partner-<?php echo esc_html( $event->id ); ?>" >
														<option value="0"><?php esc_html_e( 'Select partner', 'racketmanager' ); ?></option>
														<?php
														foreach ( $partner_list as $partner ) {
															?>
															<option value="<?php echo esc_html( $partner->player_id ); ?>"
																<?php
																if ( $partner_id === $partner->player_id ) {
																	echo ' selected';
																}
																?>
															><?php echo esc_html( $partner->fullname . ' - ' . get_club( $partner->affiliatedclub )->name ); ?></option>
															<?php
														}
														?>
													</select>
												</div>
												<?php
											}
										}
										?>
									<div>
								</div>
							</li>
							<li id="liCommentDetails" class="individual-entry__panel">
								<div id="comment_Details">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Additional information', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php echo esc_html_e( 'Please leave any additional information for the Tournament Organiser including medical conditions here', 'racketmanager' ); ?>
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
									$rules_link = '<a href="/rules/tournament-rules" target="_blank">' . __( 'the rules of the tournament', 'racketmanager' ) . '</a>';
									/* Translators: %s: link to tournament rules */
									printf( __( 'I agree to abide by %s.', 'racketmanager' ), $rules_link ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</label>
								<input class="form-check-input switch" id="acceptance" name="acceptance" type="checkbox" role="switch">
							</div>
						</div>
					</div>
					<div class="individual-entry__footer">
						<div class="updateResponse mb-3" id="entryResponse" name="entryResponse"></div>
						<div class="btn__group">
							<div class="individual-entry__submit">
								<button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" onclick="Racketmanager.entryRequest(event, 'tournament')"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></button>
							</div>
							<a role="button" href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/" class="btn btn--cancel"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div><!-- .entry-content -->
