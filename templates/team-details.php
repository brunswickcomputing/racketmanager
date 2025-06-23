<?php
/**
 * Template page for Team List
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $object league or event object
 *  $teams: all teams of league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var object $object */
global $racketmanager;
$envelope           = RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill';
$telephone          = RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill';
$user_can_edit_team = false;
if ( is_user_logged_in() ) {
	if ( current_user_can( 'manage_racketmanager' ) ) {
		$user_can_edit_team = true;
	} else {
		$user   = wp_get_current_user();
		$userid = $user->ID;
		if ( intval( $object->team->club->matchsecretary ) === $userid ) {
			$user_can_edit_team = true;
		}
	}
}
if ( isset( $object->competition ) ) {
	$object_competition = $object->competition;
	$object_event       = $object;
	$object_type        = 'event';
} else {
	$object_competition = $object->event->competition;
	$object_event       = $object->event;
	$object_type        = 'league';
}
$display_opt = $racketmanager->get_options( 'display' );
if ( ! empty( $display_opt['wtn'] ) ) {
	$help_text   = __( 'World Tennis Number', 'racketmanager');
	$format      = substr( $object_event->type, 1, 1 );
	$format_type = Racketmanager_Util::get_match_type( $format );
	if ( $format_type ) {
		$help_text = $format_type . ' ' . $help_text;
	}
	$show_wtn    = true;
} else {
    $format      = null;
	$help_text   = null;
	$format_type = null;
	$show_wtn    = false;
}
?>
	<div class="page-subhead">
		<div class="media">
			<div class="media__wrapper">
				<div class="media__img">
					<span class="profile-icon">
						<span class="profile-icon__abbr">
							<?php
							$words    = explode( ' ', $object->team->title );
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
					<h3 class="media__title">
						<span><?php echo esc_html( $object->team->title ); ?></span>
						<?php
						$favourite_type = 'team';
						$favourite_id   = $object->team->id;
						require_once 'includes/favourite-button.php';
						?>
					</h3>
				</div>
			</div>
		</div>
	</div>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-7">
			<?php
			if ( ! $object->is_championship ) {
				$standings_link = '/' . $object->event->competition->type . '/' . seo_url( $object->title ) . '/' . $object->current_season['name'] . '/';
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
						<div class="module__aside">
							<button href="<?php echo esc_attr( $standings_link ); ?>" class="btn btn--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( $object->id ); ?>" data-season="<?php echo esc_attr( $object->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $standings_link ); ?>" data-link-id="" data-link-type="standings" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'View standings', 'racketmanager' ); ?>">
								<i class="racketmanager-svg-icon">
									<?php racketmanager_the_svg( 'icon-table' ); ?>
								</i>
							</button>
						</div>
					</div>
					<div class="module__content">
						<div class="module-container">
							<ul class="list list--grid list--bordered-left">
								<li class="list__item">
									<div class="stats">
										<div class="stats__body">
											<span class="stats__title"><?php esc_html_e( 'Standing', 'racketmanager' ); ?></span>
											<span class="stats__value standing-status"><?php echo esc_html( $object->team->standings->rank ); ?></span>
										</div>
									</div>
								</li>
								<li class="list__item">
									<div class="stats">
										<div class="stats__body">
											<span class="stats__title"><?php esc_html_e( 'Played', 'racketmanager' ); ?></span>
											<span class="stats__value"><?php echo esc_html( $object->team->standings->done_matches ); ?></span>
										</div>
									</div>
								</li>
								<li class="list__item">
									<div class="stats">
										<div class="stats__body">
											<span class="stats__title"><?php esc_html_e( 'Points', 'racketmanager' ); ?></span>
											<span class="stats__value"><?php echo esc_html( $object->team->standings->points_formatted['primary'] ); ?></span>
										</div>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			<?php
			if ( $object->team->matches ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
						<div class="module__aside">
							<a href="/index.php?league_id=<?php echo esc_html( $object->id ); ?>&team_id=<?php echo esc_html( $object->team->id ); ?>&team=<?php echo esc_html( seo_url( $object->team->title ) ); ?>&season=<?php echo esc_html( $object->current_season['name'] ); ?>&racketmanager_export=calendar" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
								<i class="racketmanager-svg-icon">
									<?php racketmanager_the_svg( 'icon-calendar' ); ?>
								</i>
								<span class="nav-link__value text-uppercase">
									<?php esc_html_e( 'Calendar', 'racketmanager' ); ?>
								</span>
							</a>
						</div>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="module">
								<?php
								$matches = $object->team->matches;
								if ( $object_competition->is_player_entry ) {
									foreach ( $matches as $match ) {
										require RACKETMANAGER_PATH . 'templates/tournament/match.php';
									}
								} else {
									$show_header = false;
									require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<div class="page-content__sidebar col-12 col-lg-5">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title">
						<?php
						if ( $object_competition->is_player_entry ) {
							esc_html_e( 'Contact details', 'racketmanager' );
						} else {
							esc_html_e( 'Team captain', 'racketmanager' );
						}
						?>
					</h3>
					<?php
					if ( $user_can_edit_team ) {
						?>
						<div class="module__aside">
							<button class="btn btn--link" href="" id="teamEditLink" data-team-id="<?php echo esc_attr( $object->team->id ); ?>" data-event-id="<?php echo esc_attr( $object_event->id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Edit team', 'racketmanager' ); ?>">
								<svg width="16" height="16" class="icon ">
									<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
								</svg>
							</button>
						</div>
						<?php
					}
					?>
				</div>
				<div class="module__content">
					<div class="module-container">
						<?php
						if ( $object_competition->is_team_entry ) {
							?>
							<h4 class="subheading" id="captain-name-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $object->team->id ); ?>">
								<?php echo esc_html( $object->team->info->captain ); ?>
							</h4>
							<?php
						}
						?>
						<ul class="list list--naked">
							<?php
							if ( is_user_logged_in() ) {
								if ( $object_competition->is_player_entry ) {
									foreach ( $object->team->info->players as $team_player ) {
										?>
										<h4 class="subheading">
											<?php echo esc_html( $team_player->display_name ); ?>
										</h4>
										<?php
										if ( ! empty( $team_player->contactno ) ) {
											?>
											<li class="list__item">
												<a href="tel:<?php echo esc_html( $team_player->contactno ); ?>" class="nav--link" rel="nofollow">
													<svg width="16" height="16" class="">
														<use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
													</svg>
													<span class="nav--link">
														<span class="nav-link__value" id="captain-contact-no">
															<?php echo esc_html( $team_player->contactno ); ?>
														</span>
													</span>
												</a>
											</li>
											<?php
										}
										if ( ! empty( $team_player->email ) ) {
											?>
											<li class="list__item">
												<a href="mailto:<?php echo esc_html( $team_player->email ); ?>" class="nav--link">
													<svg width="16" height="16" class="">
														<use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
													</svg>
													<span class="nav--link">
														<span class="nav-link__value" id="captain-contact-email">
															<?php echo esc_html( $team_player->email ); ?>
														</span>
													</span>
												</a>
											</li>
											<?php
										}
									}
								} else {
									if ( ! empty( $object->team->info->contactno ) ) {
										?>
										<li class="list__item">
											<a href="tel:<?php echo esc_html( $object->team->info->contactno ); ?>" class="nav--link" rel="nofollow">
												<svg width="16" height="16" class="">
													<use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
												</svg>
												<span class="nav--link">
													<span class="nav-link__value" id="captain-contact-no-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $object->team->id ); ?>">
														<?php echo esc_html( $object->team->info->contactno ); ?>
													</span>
												</span>
											</a>
										</li>
										<?php
									}
									?>
									<?php
									if ( ! empty( $object->team->info->contactemail ) ) {
										?>
										<li class="list__item">
											<a href="mailto:<?php echo esc_html( $object->team->info->contactemail ); ?>" class="nav--link">
												<svg width="16" height="16" class="">
													<use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
												</svg>
												<span class="nav--link">
													<span class="nav-link__value" id="captain-contact-email-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $object->team->id ); ?>">
														<?php echo esc_html( $object->team->info->contactemail ); ?>
													</span>
												</span>
											</a>
										</li>
										<?php
									}
								}
							}
							?>
							<?php
							if ( $object_competition->is_team_entry ) {
								?>
								<li class="list__item">
									<span class="nav--link">
										<svg width="16" height="16" class="icon-team">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
										</svg>
										<span class="nav-link__value" id="team-match-day-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $object->team->id ); ?>">
											<?php echo esc_html( $object->team->info->match_day ); ?>
										</span>
									</span>
								</li>
								<li class="list__item">
									<span class="nav--link">
										<svg width="16" height="16" class="icon-team">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#clock-fill' ); ?>"></use>
										</svg>
										<span class="nav-link__value" id="team-match-time-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $object->team->id ); ?>">
											<?php echo esc_html( mysql2date( $racketmanager->time_format, $object->team->info->match_time ) ); ?>
										</span>
									</span>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<?php
			if ( $object_competition->is_team_entry ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title">
							<?php esc_html_e( 'Club', 'racketmanager' ); ?>
						</h3>
					</div>
					<div class="module__content">
						<div class="module-container">
							<h4 class="subheading">
								<a href="/clubs/<?php echo esc_attr( seo_url( $object->team->club->shortcode ) ); ?>/">
									<span><?php echo esc_html( $object->team->club->name ); ?></span>
								</a>
							</h4>
							<ul class="list list--naked">
								<li class="list__item">
									<span class="nav--link">
										<svg width="16" height="16" class="icon-marker">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-marker' ); ?>"></use>
										</svg>
										<span class="nav-link__value">
											<?php echo esc_html( $object->team->club->address ); ?>
										</span>
									</span>
								</li>
								<li class="list__item">
									<span class="nav--link">
										<svg width="16" height="16" class="icon-captain">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-captain' ); ?>"></use>
										</svg>
										<span class="nav-link__value">
											<?php echo esc_html( $object->team->club->match_secretary_name ); ?>
										</span>
									</span>
								</li>
								<?php
								if ( is_user_logged_in() ) {
									if ( ! empty( $object->team->club->match_secretary_contact_no ) ) {
										?>
										<li class="list__item">
											<a href="tel:<?php echo esc_html( $object->team->club->match_secretary_contact_no ); ?>" class="nav--link" rel="nofollow">
												<svg width="16" height="16" class="">
													<use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
												</svg>
												<span class="nav--link">
													<span class="nav-link__value">
														<?php echo esc_html( $object->team->club->match_secretary_contact_no ); ?>
													</span>
												</span>
											</a>
										</li>
										<?php
									}
									?>
									<?php
									if ( ! empty( $object->team->club->match_secretary_email ) ) {
										?>
										<li class="list__item">
											<a href="mailto:<?php echo esc_html( $object->team->club->match_secretary_email ); ?>" class="nav--link">
												<svg width="16" height="16" class="">
													<use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
												</svg>
												<span class="nav--link">
													<span class="nav-link__value">
														<?php echo esc_html( $object->team->club->match_secretary_email ); ?>
													</span>
												</span>
											</a>
										</li>
										<?php
									}
								}
								?>
								<?php
								if ( ! empty( $object->team->club->website ) ) {
									?>
									<li class="list__item">
										<a href="<?php echo esc_html( $object->team->club->website ); ?>" class="nav--link" target="_blank" rel="noopener nofollow">
											<svg width="16" height="16" class="icon-globe">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#globe' ); ?>"></use>
											</svg>
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $object->team->club->website ); ?>
												</span>
											</span>
										</a>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
					</div>
				</div>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="col-12">
								<ol class="list list--bordered list--count">
									<?php
									foreach ( $object->team->players as $player ) {
										$selected_player = false;
										if ( intval( $player->id ) === get_current_user_id() ) {
											$selected_player = true;
										}
										$player_link = '/' . $object_competition->type . '/' . seo_url( $object->name ) . '/' . $object->current_season['name'] . '/player/' . seo_url( $player->fullname ) . '/';
										?>
										<li class="list__item <?php echo empty( $selected_player ) ? null : 'is-selected'; ?>">
											<div class="media">
												<div class="media__wrapper">
													<div class="media__img">
														<div class="profile-icon">
															<span class="profile-icon__abbr">
																<?php
																$player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
																echo esc_html( $player_initials );
																?>
															</span>
														</div>
													</div>
													<div class="media__content">
														<div class="flex-container">
															<div class="flex-item flex-item--grow">
																<p class="media__title">
																	<a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="<?php echo esc_attr( $object_type ); ?>" data-type-id="<?php echo esc_attr( $object->id ); ?>" data-season="<?php echo esc_attr( $object->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
																		<span class="nav-link__value">
																			<?php echo esc_html( $player->fullname ); ?>
																		</span>
																	</a>
																</p>
																<?php
																if ( $show_wtn ) {
																	?>
																	<div class="media__content-subinfo">
																		<small class="media__subheading">
																			<span class="nav--link">
																				<span class="nav-link__value" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $help_text ); ?>"><?php echo esc_attr( $player->wtn[ $format ] ); ?></span>
																			</span>
																		</small>
																	</div>
																	<?php
																}
																?>
															</div>
															<div class="progress-bar-container">
																<?php
																if ( $player->played ) {
																	?>
																	<div class="clearfix">
																		<span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
																		<span class="pull-right"><?php echo esc_html( $player->matches_won ) . '-' . esc_html( $player->matches_lost ) . ' (' . esc_html( $player->played ) . ')'; ?></span>
																	</div>
																	<div class="progress">
																		<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
																	</div>
																	<?php
																}
																?>
															</div>
														</div>
													</div>
												</div>
											</div>
										</li>
										<?php
									}
									?>
								</ol>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="modal" id="teamModal"></div>
    <script type="text/javascript">
        document.getElementById('teamEditLink').addEventListener('click', function (e) {
            let teamId = this.dataset.teamId;
            let eventId = this.dataset.eventId;
            Racketmanager.teamEditModal(e,teamId,eventId);
        })
    </script>
	<?php

