<?php
/**
 * Template page for a single tournament match
 * The following variables are usable:
 *  $tournament: details of tournament
 *  $match: contains data of displayed match
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $wp_query;
$post_id            = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$tournament_link    = empty( $tournament ) ? null : '/tournament/' . seo_url( $tournament->name ) . '/';
$winner             = null;
$winner_set         = null;
$loser              = null;
$is_tie             = false;
$user_can_update    = $is_update_allowed->user_can_update;
$tournament_head    = empty( $tournament ) ? null : $tournament->name . ' ';
$tournament_head   .= __( 'Tournament', 'racketmanager' );
$player_team        = null;
$player_team_status = null;
$match_editable     = false;
$is_edit_mode       = isset( $is_edit_mode ) ? $is_edit_mode : false;
if ( $user_can_update ) {
	$match_editable = 'is-editable';
}
$allow_schedule_match     = false;
$allow_reset_match_result = false;
$show_menu                = false;
switch ( $match->league->event->competition->type ) {
	case 'league':
		$image = 'images/bootstrap-icons.svg#table';
		break;
	case 'cup':
		$image = 'images/bootstrap-icons.svg#trophy-fill';
		break;
	case 'tournament':
		$image = 'images/lta-icons.svg#icon-bracket';
		break;
	default:
		$image = null;
		break;
}
if ( $match ) {
	$match_status = null;
	if ( ! empty( $match->winner_id ) ) {
		if ( 'admin' === $is_update_allowed->user_type ) {
			$allow_reset_match_result = true;
			$show_menu                = true;
		}
		$match_complete = true;
		if ( $match->winner_id === $match->teams['home']->id ) {
			$winner = 'home';
			$loser  = 'away';
		} elseif ( $match->winner_id === $match->teams['away']->id ) {
			$winner = 'away';
			$loser  = 'home';
		} elseif ( '-1' === $match->winner_id ) {
			$is_tie = true;
		}
		if ( $winner === $player_team ) {
			$player_team_status = 'winner';
		} elseif ( $loser === $player_team ) {
			$player_team_status = 'loser';
		}
		if ( $match->is_walkover && ! empty( $match->custom['walkover'] ) ) {
			if ( 'home' === $match->custom['walkover'] ) {
				$match_status = 'walkover_player1';
			} elseif ( 'away' === $match->custom['walkover'] ) {
				$match_status = 'walkover_player2';
			}
		} elseif ( $match->is_retired ) {
			if ( 'home' === $match->custom['retired'] ) {
				$match_status = 'retired_player1';
			} elseif ( 'away' === $match->custom['retired'] ) {
				$match_status = 'retired_player2';
			}
		} elseif ( $match->is_shared ) {
			$match_status = 'share';
		}
	} elseif ( $user_can_update ) {
		$allow_schedule_match = true;
		$show_menu            = true;
	}
	?>
	<div class="tournament__match">
		<div class="page-subhead competition">
			<div class="media tournament-head">
				<div class="media__wrapper">
					<div class="media__img">
						<svg width="16" height="16" class="media__img-element--icon">
							<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
						</svg>
					</div>
					<div class="media__content">
						<h1 class="media__title"><?php esc_html_e( 'Match details', 'racketmanager' ); ?></h1>
						<div class="media__content-subinfo">
							<small class="media__subheading">
								<ul class="match__header-title">
									<?php
									if ( ! empty( $tournament ) ) {
										?>
										<li class="match__header-title-item">
											<a href="<?php echo esc_html( $tournament->link ); ?>">
												<span class="nav--link">
													<span class="nav-link__value">
														<?php echo esc_html( $tournament->name ) . ' ' . esc_html__( 'Tournament', 'racketmanager' ); ?>
													</span>
												</span>
											</a>
										</li>
										<li class="match__header-title-item">
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $tournament->venue_name ); ?>
												</span>
											</span>
										</li>
										<?php
									}
									?>
								</ul>
							</small>
							<?php
							if ( ! empty( $tournament->date_start ) && ! empty( $tournament->date ) ) {
								?>
								<small class="media__subheading">
									<span class="nav--link">
										<span class="nav-link__value">
											<?php racketmanager_the_svg( 'icon-calendar' ); ?>
											<?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->date ) ); ?>
										</span>
									</span>
								</small>
								<?php
							}
							?>
						</div>
					</div>
					<div class="media__aside">
						<?php
						if ( is_user_logged_in() && $match_editable && ( $show_menu ) ) {
							?>
							<div class="match__change">
								<div class="dropdown">
									<a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
										<svg width="16" height="16" class="icon ">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
										</svg>
									</a>
									<ul class="dropdown-menu dropdown-menu-end">
										<?php
										if ( $allow_schedule_match ) {
											?>
											<li>
												<a class="dropdown-item" href="" onclick="Racketmanager.matchOptions(event, '<?php echo esc_attr( $match->id ); ?>', 'schedule_match')">
													<?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?>
												</a>
											</li>
											<?php
										}
										?>
									</ul>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="match-info-meta wrapper--padding-medium">
			<div class="row">
				<div class="col-6 col-sm-4">
					<svg width="20" height="20" class="match-info-meta__icon">
						<?php
						if ( $match->league->is_championship ) {
							$svg_link_text     = __( 'Draw', 'racketmanager' );
							$svg_link          = $match->league->event->name;
							$svg_link_location = $tournament_link . 'draw/' . seo_url( $match->league->event->name ) . '/';
							?>
							<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-bracket' ); ?>"></use>
							<?php
						} else {
							if ( $match->league->event->is_box ) {
								$season_text = __( 'round', 'racketmanager' ) . '-' . $match->season;
							} else {
								$season_text = $match->season;
							}
							$svg_link_text     = __( 'League', 'racketmanager' );
							$svg_link          = $match->league->title;
							$svg_link_location = '/league/' . seo_url( $match->league->title ) . '/' . $season_text . '/';
							?>
							<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#table' ); ?>"></use>
							<?php
						}
						?>
					</svg>
					<div class="match-info-meta__content">
						<span>
							<strong>
								<?php echo esc_html( $svg_link_text ); ?>
							</strong>
						</span>
						<span class="text--muted-small">
							<a href="<?php echo esc_attr( $svg_link_location ); ?>">
								<?php echo esc_html( $svg_link ); ?>
							</a>
						</span>
					</div>
				</div>
				<?php
				if ( ! empty( $match->date ) ) {
					?>
					<div class="col-6 col-sm-4">
						<svg width="20" height="20" class="match-info-meta__icon">
							<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar' ); ?>"></use>
						</svg>
						<div class="match-info-meta__content">
							<span>
								<strong>
									<?php esc_html_e( 'Time', 'racketmanager' ); ?>
								</strong>
							</span>
							<span class="text--muted-small">
								<time datetime="<?php echo esc_html( $match->date ); ?>">
									<span class="match_date" id="match-tournament-date-header">
										<?php echo esc_html( mysql2date( 'D j M', $match->date ) ); ?>
									</span>
									<?php
									if ( ! empty( $match->start_time ) ) {
										?>
										<span class="match_time">
											<?php echo esc_html__( 'at', 'racketmanager' ) . ' ' . esc_html( mysql2date( 'G:i', $match->date ) ); ?>
										</span>
										<?php
									}
									?>
								</time>
							</span>
						</div>
					</div>
					<?php
				}
				?>
				<?php
				$location = $match->location;
				if ( empty( $location ) && isset( $match->host ) ) {
					if ( 'home' === $match->host ) {
						$location = empty( $match->teams['home']->club->shortcode ) ? null : $match->teams['home']->club->shortcode;
					} elseif ( 'away' === $match->host ) {
						$location = empty( $match->teams['away']->club->shortcode ) ? null : $match->teams['away']->club->shortcode;
					}
				}

				if ( ! empty( $location ) ) {
					?>
					<div class="d-none d-sm-block col-sm-4">
						<svg width="20" height="20" class="match-info-meta__icon">
							<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#geo-alt-fill' ); ?>"></use>
						</svg>
						<div class="match-info-meta__content">
							<span>
								<strong>
									<?php esc_html_e( 'Location', 'racketmanager' ); ?>
								</strong>
							</span>
							<span class="text--muted-small">
								<?php echo esc_html( $location ); ?>
							</span>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<div class="wrapper--padding-medium">
			<?php $form_id = 'match-view'; ?>
			<form id="<?php echo esc_html( $form_id ); ?>" action="" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'scores-match', 'racketmanager_nonce' ); ?>
				<input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo esc_html( $match->league_id ); ?>" />
				<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_html( $match->id ); ?>" />
				<input type="hidden" name="current_season" id="current_season" value="<?php echo esc_html( $match->season ); ?>" />
				<input type="hidden" name="home_team" value="<?php echo esc_html( $match->home_team ); ?>" />
				<input type="hidden" name="away_team" value="<?php echo esc_html( $match->away_team ); ?>" />
				<input type="hidden" name="match_type" value="tournament" />
				<input type="hidden" name="match_round" value="<?php echo esc_html( $match->final_round ); ?>" />
				<input type="hidden" name="updateMatch" id="updateMatch" value="results" />
				<input name="match_status" type="hidden" id="match_status" value="<?php echo esc_attr( $match_status ); ?>" />
				<?php
				$page_referrer = wp_get_referer();
				if ( ! $page_referrer ) {
					if ( ! empty( $tournament ) ) {
						$page_referrer = $tournament->link . 'matches/';
					}
				}
				?>
				<div class="alert_rm" id="matchAlert" style="display:none;">
					<div class="alert__body">
						<div class="alert__body-inner" id="alertResponse">
						</div>
					</div>
				</div>
				<div class="match__buttons mb-3">
					<a href="<?php echo esc_url( $page_referrer ); ?>">
						<button tabindex="500" class="btn btn-plain" type="button"><?php esc_html_e( 'Return', 'racketmanager' ); ?></button>
					</a>
					<?php
					if ( $user_can_update ) {
						?>
						<button tabindex="500" class="btn btn-primary" type="button" id="updateMatchResults" onclick="Racketmanager.updateMatchResults(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
						<?php
					}
					?>
				</div>
				<div class="match tournament-match <?php echo esc_attr( $match_editable ); ?>">
					<div class="match__header">
						<ul class="match__header-title">
							<?php
							if ( $match->league->is_championship ) {
								?>
								<li class="match__header-title-item">
									<?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?>
								</li>
								<?php
							}
							?>
							<li class="match__header-title-item">
								<?php echo esc_html( $match->league->title ); ?>
							</li>
						</ul>
						<?php
						if ( $user_can_update ) {
							?>
							<div class="match__header-aside text-uppercase">
								<div class="match__header-aside-block">
									<a href="" class="nav__link" onclick="Racketmanager.statusModal(event, '<?php echo esc_attr( $match->id ); ?>')">
										<svg width="16" height="16" class="icon-plus nav-link__prefix">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#plus-lg' ); ?>"></use>
										</svg>
										<span class="nav-link__value"><?php esc_html_e( 'Match status', 'racketmanager' ); ?></span>
									</a>
								</div>
							</div>
							<?php
						}
						?>
					</div>
					<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
					<div class="match__body">
						<div class="match__row-wrapper">
							<?php
							$opponents = array( 'home', 'away' );
							foreach ( $opponents as $opponent ) {
								$is_winner    = false;
								$is_loser     = false;
								$winner_class = null;
								if ( $winner === $opponent ) {
									$is_winner    = true;
									$winner_class = ' winner';
								} elseif ( $loser === $opponent ) {
									$is_loser = true;
								}
								?>
								<div class="match__row <?php echo esc_html( $winner_class ); ?>">
									<div class="match__row-title">
										<?php
										$team = $match->teams[ $opponent ];
										if ( empty( $team->player ) ) {
											?>
											<div class="match__row-title-value">
												<?php echo esc_html( $team->title ); ?>
											</div>
											<?php
										} else {
											foreach ( $team->player as $team_player ) {
												?>
												<div class="match__row-title-value">
													<?php
													if ( ! empty( $tournament ) ) {
														?>
														<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( trim( $team_player ) ) ); ?>">
														<?php
													}
													?>
													<?php
													if ( ! empty( $team->is_withdrawn ) ) {
														$title_text = $match->teams['home']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
														?>
														<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $title_text ); ?>">
														<?php
													}
													?>
													<?php echo esc_html( trim( $team_player ) ); ?>
													<?php
													if ( ! empty( $team->is_withdrawn ) ) {
														?>
														</s>
														<?php
													}
													?>
													<?php
													if ( ! empty( $tournament ) ) {
														?>
														</a>
														<?php
													}
													?>
												</div>
												<?php
											}
										}
										?>
									</div>
									<?php
									$match_message_class = null;
									$match_message_text  = null;
									$match_status_class  = null;
									$match_status_text   = null;
									if ( $is_winner ) {
										if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
											$match_status_class = 'winner';
											$match_status_text  = 'W';
										}
									} elseif ( $is_loser ) {
										if ( 'loser' === $player_team_status ) {
											$match_status_class = 'loser';
											$match_status_text  = 'L';
										}
										if ( $match->is_walkover ) {
											$match_message_class = 'match-warning';
											$match_message_text  = __( 'Walkover', 'racketmanager' );
										} elseif ( $match->is_retired ) {
											$match_message_class = 'match-warning';
											$match_message_text  = __( 'Retired', 'racketmanager' );
										}
									} elseif ( $is_tie ) {
										$match_status_class  = 'tie';
										$match_message_class = 'match-warning';
										$match_status_text   = 'T';
										$match_message_text  = __( 'Not played', 'racketmanager' );
									}
									?>
									<span class="match__message <?php echo esc_attr( $match_message_class ); ?>" id="match-message-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
										<?php echo esc_html( $match_message_text ); ?>
									</span>
									<span class="match__status <?php echo esc_attr( $match_status_class ); ?>" id="match-status-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
										<?php echo esc_html( $match_status_text ); ?>
									</span>
								</div>
								<?php
							}
							?>
						</div>
						<div class="match__result">
							<?php
							$sets = $match->sets;
							for ( $i = 1; $i <= $match->league->num_sets; $i++ ) {
								$set = ! empty( $sets[ $i ] ) ? $sets[ $i ] : array();
								if ( isset( $set['player1'] ) && isset( $set['player2'] ) ) {
									if ( $set['player1'] > $set['player2'] ) {
										$winner_set = 'player1';
									} elseif ( $set['player1'] < $set['player2'] ) {
										$winner_set = 'player2';
									} else {
										$winner_set = null;
									}
								} else {
									$winner_set = null;
								}
								?>
								<?php
								if ( $match_editable || ( ! empty( $set['player1'] ) || ! empty( $set['player2'] ) ) ) {
									$set_type = Racketmanager_Util::get_set_type( $match->league->scoring, $match->final_round, $match->league->num_sets, $i, false, $match->num_rubbers, $match->leg );
									$set_info = Racketmanager_Util::get_set_info( $set_type );
									?>
									<span class="set-group" id="set_<?php echo esc_html( $i ); ?>" data-settype="<?php echo esc_attr( $set_type ); ?>" data-maxwin="<?php echo esc_attr( $set_info->max_win ); ?>" data-maxloss="<?php echo esc_attr( $set_info->max_loss ); ?>" data-minwin="<?php echo esc_attr( $set_info->min_win ); ?>" data-minloss="<?php echo esc_attr( $set_info->min_loss ); ?>" data-tiebreakset="<?php echo esc_attr( $set_info->tiebreak_set ); ?>">
										<ul class="match-points">
											<?php
											$opponents = array( 'player1', 'player2' );
											foreach ( $opponents as $opponent ) {
												if ( $winner_set === $opponent ) {
													$winner_class       = ' winner';
													$winner_point_class = ' match-points__cell-input--won';
												} else {
													$winner_class       = '';
													$winner_point_class = '';
												}
												?>
												<li class="match-points__cell <?php echo esc_html( $winner_class ); ?>">
													<?php
													if ( $match_editable ) {
														?>
														<input type="text" class="points match-points__cell-input <?php echo esc_html( $winner_point_class ); ?>" id="set_<?php echo esc_html( $i ); ?>_<?php echo esc_html( $opponent ); ?>" name="sets[<?php echo esc_html( $i ); ?>][<?php echo esc_html( $opponent ); ?>]" value="<?php echo isset( $set[ $opponent ] ) ? esc_html( $set[ $opponent ] ) : ''; ?>" onblur="SetCalculator(this)" />
														<?php
													} else {
														?>
														<?php echo isset( $set[ $opponent ] ) ? esc_html( $set[ $opponent ] ) : ''; ?>
														<?php
														if ( isset( $set['tiebreak'] ) && ! empty( $winner_class ) ) {
															?>
															<span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
															<?php
														}
														?>
														<?php
													}
													?>
												</li>
												<?php
											}
											?>
										</ul>
										<?php
										if ( $match_editable ) {
											?>
											<ul class="match-points tie-break" id="set_<?php echo esc_html( $i ); ?>_tiebreak_wrapper"
											<?php
											if ( ! isset( $set['tiebreak'] ) || '' === $set['tiebreak'] ) {
												echo 'style="display:none;"';
											}
											?>
											>
												<li class="match-points__cell">
													<input type="text" class="points match-points__cell-input" id="set_<?php echo esc_html( $i ); ?>_tiebreak" name="sets[<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo isset( $set['tiebreak'] ) ? esc_html( $set['tiebreak'] ) : ''; ?>" onblur="SetCalculatorTieBreak(this)" />
												</li>
											</ul>
											<?php
										}
										?>
									</span>
									<?php
								}
								?>
								<?php
							}
							?>
						</div>
					</div>
					<div class="match__footer">
						<ul class="match__footer-title">
						</ul>
						<?php
						if ( $match_editable ) {
							?>
							<div class="match__footer-aside text-uppercase">
								<a href="" onclick="Racketmanager.resetMatchScores(event, '<?php echo esc_html( $form_id ); ?>')">
									<?php echo esc_html_e( 'Reset scores', 'racketmanager' ); ?>
								</a>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</form>
		</div>
	</div>
	<script>
		<?php require RACKETMANAGER_PATH . 'js/setcalculator.js'; ?>
	</script>
	<?php require RACKETMANAGER_PATH . 'templates/includes/modal-score.php'; ?>
	<?php require RACKETMANAGER_PATH . 'templates/includes/match-modal.php'; ?>
	<?php
}
?>
