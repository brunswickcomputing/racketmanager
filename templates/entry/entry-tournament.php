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
 *  $club_memberships: club memberships array
 *  $season: season name
 *  $type: event type
 *  $malePartners: male partners object
 *  $femalePartners: female partners object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $racketmanager;
/** @var object $player */
/** @var object $tournament */
/** @var array $club_memberships */
$withdrawal_allowed = false;
$entry_option = false;
if ( get_current_user_id() !== intval( $player->id ) && ! current_user_can( 'manage_racketmanager' ) ) {
	$changes_allowed = false;
} elseif ( ! $tournament->is_open ) {
	$changes_allowed = false;
	if ( $tournament->is_closed ) {
		$withdrawal_allowed = true;
	}
} else {
	$changes_allowed    = true;
	$withdrawal_allowed = true;
}
if ( ! empty( $player->entry ) ) {
	$entered    = true;
	$form_title = __( 'Entry details', 'racketmanager' );
} else {
	$entered    = false;
	$form_title = __( 'Enter online', 'racketmanager' );
}
?>
<div class="container">
	<?php require RACKETMANAGER_PATH . 'templates/includes/tournament-header.php'; ?>
	<form id="form-entry" action="" method="post">
		<?php wp_nonce_field( 'tournament-entry' ); ?>
		<input type="hidden" name="tournamentId" id="tournamentId" value="<?php echo esc_html( $tournament->id ); ?>" />
		<input type="hidden" name="tournamentDateEnd" id="tournamentDateEnd" value="<?php echo esc_html( $tournament->date ); ?>" />
		<input type="hidden" name="season" id="season" value="<?php echo esc_html( $tournament->season ); ?>" />
		<input type="hidden" name="playerId" id="playerId" value="<?php echo esc_attr( $player->id ); ?>" />
		<input type="hidden" id="playerGender" value="<?php echo esc_attr( $player->gender ); ?>" />
		<input type="hidden" id="competitionFee" name="competitionFee" value="<?php echo esc_attr( $tournament->fees->competition ); ?>" />
		<div class="module module--card">
			<div class="module__content">
				<div class="module-container">
					<div class="entry-subhead">
						<div class="hgroup">
							<h3 class="hgroup__heading">
								<?php echo esc_html( $form_title ); ?>
							</h3>
							<span class="hgroup__subheading">
								<?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
								<time datetime="<?php echo esc_attr( $tournament->date_closing ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->date_closing ) ); ?></time>
							</span>
						</div>
						<div class="entry-subhead__aside">
							<a role="button" href="/rules/tournament-rules/" target="_blank" class="btn btn-primary">
								<?php esc_html_e( 'Tournament Rules', 'racketmanager' ); ?>
							</a>
						</div>
					</div>
					<?php
					if ( ! $changes_allowed ) {
						if ( $tournament->is_closed ) {
							$alert_class = 'warning';
							$alert_msg[] = __( 'Tournament entries are now closed.', 'racketmanager' );
							$alert_msg[] = __( 'You can still withdraw from events.', 'racketmanager' );
						} elseif ( ! $tournament->is_open ) {
							if ( $entered ) {
								$alert_class = 'info';
								$alert_msg[] = __( 'Tournament entries are now closed.', 'racketmanager' );
								$alert_msg[] = __( 'These are the latest entry details.', 'racketmanager' );
							} else {
								$alert_class = 'warning';
								$alert_msg[] = __( 'Tournament not currently open for entries', 'racketmanager' );
							}
						} else {
							$alert_class = 'info';
							$alert_msg[] = __( 'You can not make changes to a entry form for someone else.', 'racketmanager' );
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
										<div class="media__content-subinfo">
											<span class="media__subheading">
												<?php
												$gender = match ($player->gender) {
													'M'     => __('Male', 'racketmanager'),
													'F'     => __('Female', 'racketmanager'),
													default => '',
												};
												?>
												<ul class="list list--inline player-atts">
													<li class="list__item" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_html_e( 'Gender', 'racketmanager' ); ?>"><span><?php echo esc_html( $gender ); ?></span></li>
													<?php $player_age = empty( $player->age ) ? __( 'Unknown', 'racketmanager' ) : $player->age; ?>
													<li class="list__item" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?php esc_html_e( 'Playing age for tournament', 'racketmanager' ); ?>"><span><?php echo esc_html( $player_age ); ?></span></li>
												</ul>
											</span>
										</div>
										<div class="media__content-subinfo">
											<ul class="list list--inline">
												<?php
												$display_opt = $racketmanager->get_options( 'display' );
												if ( empty( $display_opt['wtn'] ) ) {
													$rating    = $player->rating;
													$help_text = __( 'L&WLTA Tennis Rating for', 'racketmanager');
												} else {
													$rating    = $player->wtn;
													$help_text = __( 'World Tennis Number for', 'racketmanager');
												}
												$match_types = Racketmanager_Util::get_match_types();
												foreach ( $match_types as $match_type => $description ) {
													?>
													<li class="list__item" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php printf( esc_html( $help_text . ' ' . $description ) ); ?>">
														<span class="tag tag-pair" >
															<span class="tag-pair__title"><?php echo esc_html( $description ); ?></span>
															<span class="tag-pair__value"><?php echo esc_html( $rating[ $match_type ] ); ?></span>
														</span>
													</li>
													<?php
												}
												?>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
						<ol class="list list--naked">
							<li id="liPlayerDetails" class="individual-entry__panel">
								<div id="playerDetails">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'My details', 'racketmanager' ); ?></h4>
										<?php
										if ( $changes_allowed ) {
											?>
											<p class="hgroup__subheading"><?php esc_html_e( 'Check if your details are correct, and change them if necessary', 'racketmanager' ); ?></p>
											<?php
										}
										?>
									</div>
									<div class="row">
										<div id="contactDetails" class="col-12 col-md-6">
											<div class="border p-3">
												<h5 class="subheading"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></h5>
												<dl class="list list--flex">
													<div class="list__item">
                                                        <dt class="list__label"><?php esc_html_e( 'Phone', 'racketmanager' ); ?></dt>
														<dd class="list__value">
                                                            <label for="contactno"></label><input type="tel" class="form-control" id="contactno" name="contactno" value="<?php echo esc_html( $player->contactno ); ?>" <?php echo $changes_allowed ? null : 'readonly'; ?> />
                                                            <div id="contactno-feedback" class="invalid-feedback"></div>
														</dd>
													</div>
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'Email', 'racketmanager' ); ?></dt>
														<dd class="list__value">
                                                            <label for="contactemail"></label><input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo esc_html( $player->user_email ); ?>" <?php echo $changes_allowed ? null : 'readonly'; ?> />
															<div id="contactemail-feedback" class="invalid-feedback"></div>
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
															switch ( count( $club_memberships ) ) {
																case 1:
																	?>
                                                                    <label for="clubName"></label><input type="text" class="form-control" id="clubName" name="clubName" value="<?php echo esc_html( get_club( $club_memberships[0]->club_id )->name ); ?>" disabled />
																	<input type="hidden" id="clubId" name="clubId" value="<?php echo esc_html( $club_memberships[0]->club_id ); ?>" />
																	<?php
																	break;
																case 0:
																	esc_html_e( 'You must be a member of a club to enter a tournament', 'racketmanager' );
																	?>
																	<input type="hidden" id="clubId" name="clubId" value="" />
																	<?php
																	break;
																default:
																	?>
                                                                    <label for="clubId"></label><select class="form-select" size="1" name="clubId" id="clubId" <?php echo $changes_allowed ? null : 'readonly'; ?>>
																		<option value="0"><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
																		<?php
																		foreach ( $club_memberships as $club_player ) {
																			$club = get_club( $club_player->club_id );
																			?>
																			<option value="<?php echo esc_html( $club->id ); ?>"><?php echo esc_html( $club->name ); ?></option>
																			<?php
																		}
																		?>
																	</select>
																	<div id="clubId-feedback" class="invalid-feedback"></div>
																	<?php
																	break;
															}
															?>
														</dd>
													</div>
													<div class="list__item">
														<dt class="list__label"><?php esc_html_e( 'LTA Number', 'racketmanager' ); ?></dt>
														<dd class="list__value">
                                                            <label for="btm"></label><input type="number" class="form-control" id="btm" name="btm" value="<?php echo esc_html( $player->btm ); ?>" <?php echo $changes_allowed ? null : 'readonly'; ?> />
															<div id="btm-feedback" class="invalid-feedback"></div>
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
									<div class="hgroup" id="event">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
										<?php
										if ( ! empty( $tournament->num_entries ) ) {
											/* Translators: %1$s: gender %2$s age. */
											?>
											<p class="hgroup__subheading">
												<?php echo esc_html( sprintf( __( 'You may enter a maximum of %d events.', 'racketmanager' ), $tournament->num_entries ) ); ?>
											</p>
											<?php
										}
										?>
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
									<div id="event-feedback" class="invalid-feedback"></div>
								</div>
								<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
								<div class="container form-checkboxes">
									<?php
									$fee_total          = 0;
									$tournament_events  = array();
									$events_entered     = 0;
									if ( empty( $events ) ) {
										?>
										<div class="row">
											<div class="col-12">
												<div class="alert_rm mt-3 alert--warning">
													<div class="alert__body">
														<div class="alert__body-inner">
															<?php esc_html_e( 'There are no events that you are eligible to enter.', 'racketmanager' ); ?>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php
									} else {
										foreach ( $events as $event ) {
											$disabled     = null;
											if ( ! $changes_allowed ) {
												$disabled = true;
											}
											$entered      = false;
											$partner_id   = null;
											$partner_name = null;
											if ( isset( $player->entry[ $event->id ] ) ) {
												if ( $withdrawal_allowed ) {
													$disabled = false;
												}
												++$events_entered;
												$entered         = true;
												$fee_total      += $tournament->fees->event;
												$player_entry    = $player->entry[ $event->id ];
												$partner_id      = ! empty( $player_entry->partner->id ) ? $player_entry->partner->id : null;
												$partner_name    = ! empty( $player_entry->partner->display_name ) ? $player_entry->partner->display_name : null;
											}
											?>
											<div class="row">
												<div class="col-8 col-lg-6 tournament-entry--row">
													<div class="form-check form-check-lg">
														<input type="hidden" name="eventFee[<?php echo esc_attr( $event->id ); ?>]" id="eventFee-<?php echo esc_attr( $event->id ); ?>" value="<?php echo esc_attr( $tournament->fees->event ); ?>" />
														<input class="form-check-input form-check--event hasModal" id="event-<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" type="checkbox" value=<?php echo esc_html( $event->id ); ?> aria-controls="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $entered ? 'checked' : ''; ?> <?php echo $disabled ? 'disabled' : null; ?> >
														<label class="form-check-label" for="event-<?php echo esc_html( $event->id ); ?>"><?php echo esc_html( $event->name ); ?></label>
														<div id="event-<?php echo esc_html( $event->id ); ?>-feedback" class="invalid-feedback"></div>
													</div>
													<?php
													$is_doubles = false;
													if ( substr( $event->type, 1, 1 ) === 'D' ) {
														$is_doubles = true;
													}
													?>
												</div>
												<div class="col-4 col-lg-6">
													<div class="container">
														<div class="row tournament-entry--row">
															<div class="col-6 <?php echo $is_doubles ? 'is-doubles' : null; ?>" id="conditional-event-<?php echo esc_html( $event->id ); ?>">
																<?php
																if ( $is_doubles ) {
																	?>
																	<input type="hidden" name="partner[<?php echo esc_attr( $event->id ); ?>]" id="partner-<?php echo esc_html( $event->id ); ?>" value="<?php echo esc_html( $partner_id ); ?>" />
																	<?php
																	if ( $changes_allowed ) {
																		?>
																		<a href="/<?php echo esc_attr( seo_url( $event->name ) ); ?>-<?php echo esc_html( seo_url( __( 'set partner', 'racketmanager' ) ) ); ?>" class="tournamentEventEntry" data-event-id="<?php echo esc_attr( $event->id ); ?>">
																		<?php
																	}
																	?>
																	<span id="partnerName-<?php echo esc_html( $event->id ); ?>"><?php echo esc_html( $partner_name ); ?></span>
																	<input type="hidden" name="partnerId[<?php echo esc_attr( $event->id ); ?>]" id="partnerId-<?php echo esc_html( $event->id ); ?>" value="<?php echo esc_attr( $partner_id ); ?>" />
																	<?php
																	if ( $changes_allowed ) {
																		?>
																		</a>
																		<?php
																	}
																	?>
																	<div id="partner-<?php echo esc_html( $event->id ); ?>-feedback" class="invalid-feedback"></div>
																	<?php
																}
																?>
															</div>
															<div class="col-6">
																<span class="event-price" id="event-price-fmt-<?php echo esc_html( $event->id ); ?>"><?php /** @noinspection PhpExpressionResultUnusedInspection */
																	$entered ? the_currency_amount( $tournament->fees->event ) : null; ?></span>
																<input type="hidden" class="event-price-amt" name="event-price[<?php echo esc_html( $event->id ); ?>]" id="event-price-<?php echo esc_html( $event->id ); ?>" value="<?php echo $entered ? esc_html( $tournament->fees->event ) : null; ?>" />
															</div>
														</div>
													</div>
												</div>
											</div>
											<?php
											$tournament_events[] = $event->id;
										}
									}
									?>
									<input type="hidden" name="eventsEntered" id="eventsEntered" value="<?php echo esc_attr( $events_entered ); ?>" />
									<input type="hidden" name="tournamentEvents" value="<?php echo esc_html( implode( ',', $tournament_events ) ); ?>" />
								</div>
							</li>
							<li id="liCommentDetails" class="individual-entry__panel">
								<div id="comment_Details">
									<div class="hgroup">
										<h4 class="hgroup__heading"><?php esc_html_e( 'Additional information', 'racketmanager' ); ?></h4>
										<p class="hgroup__subheading">
											<?php esc_html_e( 'Please leave any additional information for the Tournament Organiser including medical conditions here', 'racketmanager' ); ?>
										</p>
									</div>
									<div class="col-12 col-md-8">
										<div class="form-floating">
											<textarea class="form-control" placeholder="<?php esc_attr_e( 'Additional information', 'racketmanager' ); ?>" id="commentDetails" name="commentDetails" <?php echo $changes_allowed ? null : 'readonly'; ?>></textarea>
											<label for="commentDetails"><?php esc_attr_e( 'Additional information', 'racketmanager' ); ?></label>
											<div id="commentDetails-feedback" class="invalid-feedback"></div>
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
								<input class="form-check-input switch" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false" <?php echo $changes_allowed || $withdrawal_allowed ? null : 'disabled'; ?>>
								<div id="acceptance-feedback" class="invalid-feedback"></div>
							</div>
						</div>
					</div>
					<div class="individual-entry__footer">
						<div class="alert_rm" id="entryAlert" style="display:none;">
							<div class="alert__body">
								<div class="alert__body-inner" id="entryAlertResponse">
								</div>
							</div>
						</div>
						<div class="price-row">
                            <?php
                            $fee_total      += $tournament->fees->competition;
                            ?>
							<div class="price-cost" id="priceCostTotalFmt"><?php echo empty( $fee_total ) ? null : esc_html__( 'Total:', 'racketmanager' ) . ' '; ?><?php the_currency_amount( $fee_total ); ?></div>
							<input type="hidden" name="priceCostTotal" id="priceCostTotal" value=<?php echo esc_attr( $fee_total ); ?> />
						</div>
						<?php
						if ( ! empty( $tournament->payments) ) {
							$total_pay = 0;
							foreach ( $tournament->payments as $payment ) {
								$total_pay += $payment->amount;
							}
							?>
							<div class="price-row">
								<div class="price-cost" id="pricePaidTotalFmt"><?php echo empty( $total_pay ) ? null : esc_html__( 'Paid:', 'racketmanager' ) . ' '; ?><?php the_currency_amount( $total_pay ); ?></div>
								<input type="hidden" name="pricePaidTotal" id="pricePaidTotal" value=<?php echo esc_attr( $total_pay ); ?> />
							</div>
							<?php
						}
						?>
						<div class="btn__group">
							<?php
							if ( $changes_allowed || $withdrawal_allowed ) {
								?>
								<div class="individual-entry__submit">
									<button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" data-type="tournament"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></button>
								</div>
								<?php
							}
							?>
							<a role="button" href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/" class="btn btn--cancel"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
							<?php
							if ( $withdrawal_allowed ) {
								?>
								<a role="button" class="btn btn--withdraw" id="tournamentWithdraw"><?php esc_html_e( 'Withdraw', 'racketmanager' ); ?></a>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
    const eventLinks = document.querySelectorAll('.tournamentEventEntry');
    eventLinks.forEach(el => el.addEventListener('click', function (e) {
        let eventId = this.dataset.eventId;
        Racketmanager.partnerModal(e, eventId);
    }));
    document.getElementById('entrySubmit').addEventListener('click', function (e) {
        let type = this.dataset.type;
        Racketmanager.entryRequest(e, type);
    });
    document.getElementById('tournamentWithdraw').addEventListener('click', function (e) {
        Racketmanager.withdrawTournament(e);
    });
</script>
<div class="modal" id="partnerModal" tabindex="-1"></div>
