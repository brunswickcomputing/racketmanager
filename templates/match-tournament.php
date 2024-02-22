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
$user_can_update    = $user_can_update_array[0];
$user_type          = $user_can_update_array[1];
$user_message       = $user_can_update_array[3];
$tournament_head    = empty( $tournament ) ? null : $tournament->name . ' ';
$tournament_head   .= __( 'Tournament', 'racketmanager' );
$player_team        = null;
$player_team_status = null;
$match_editable     = false;
$is_edit_mode       = isset( $is_edit_mode ) ? $is_edit_mode : false;
if ( $user_can_update ) {
	$match_editable = 'is-editable';
}
?>
<?php
if ( $match ) {
	if ( ! empty( $match->winner_id ) ) {
		$match_complete = true;
		if ( $match->winner_id === $match->teams['home']->id ) {
			$winner = 'home';
			$loser  = 'away';
		} elseif ( $match->winner_id === $match->teams['away']->id ) {
			$winner = 'away';
			$loser  = 'home';
		}
		if ( $winner === $player_team ) {
			$player_team_status = 'winner';
		} elseif ( $loser === $player_team ) {
			$player_team_status = 'loser';
		}
	}
	?>
	<div class="tournament__match">
		<div class="tournament-head">
			<div class="hgroup">
				<h1 class="hgroup__heading">
					<?php echo esc_html_e( 'Match details', 'racketmanager' ); ?>
				</h1>
				<p class="hgroup__subheading">
					<?php
					if ( ! empty( $tournament ) ) {
						?>
						<a href="<?php echo esc_html( $tournament->link ); ?>">
							<?php echo esc_html( $tournament_head ); ?>
						</a>
						<?php
					}
					?>
			</p>
			</div>
		</div>
		<div class="match-info-meta wrapper--padding-medium">
			<div class="row">
				<div class="col-6 col-sm-4">
					<svg width="20" height="20" class="match-info-meta__icon">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-bracket' ); ?>"></use>
					</svg>
					<div class="match-info-meta__content">
						<span>
							<strong>
								<?php esc_html_e( 'Draw', 'racketmanager' ); ?>
							</strong>
						</span>
						<span class="text--muted-small">
							<a href="<?php echo esc_html( $tournament_link ) . 'draw/' . esc_html( seo_url( $match->league->event->name ) ) . '/'; ?>">
								<?php echo esc_html( $match->league->event->name ); ?>
							</a>
						</span>
					</div>
				</div>
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
								<span class="match_date">
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
				$location = $match->location;
				if ( empty( $location ) && isset( $match->host ) ) {
					if ( 'home' === $match->host ) {
						$location = $match->teams['home']->club->shortcode;
					} elseif ( 'away' === $match->host ) {
						$location = $match->teams['away']->club->shortcode;
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
				<div class="match <?php echo esc_attr( $match_editable ); ?> tournament-match">
					<div class="match__header">
						<ul class="match__header-title">
							<li class="match__header-title-item">
								<?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?>
							</li>
							<li class="match__header-title-item">
								<?php echo esc_html( $match->league->title ); ?>
							</li>
						</ul>
						<div class="match__header-aside">
							<?php
							if ( $user_can_update ) {
								?>
								<button tabindex="500" class="button button-primary" type="button" id="updateMatchResults" onclick="Racketmanager.updateMatchResults(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
								<?php
							}
							?>
						</div>
					</div>
					<div id="splash" class="d-none">
						<div class="d-flex justify-content-center">
							<div class="spinner-border" role="status">
							<span class="visually-hidden">Loading...</span>
							</div>
						</div>
					</div>
					<div class="match__body">
						<div class="match__row-wrapper">
							<?php
							$opponents = array( 'home', 'away' );
							foreach ( $opponents as $opponent ) {
								if ( $winner === $opponent ) {
									$is_winner    = true;
									$winner_class = ' winner';
								} else {
									$is_winner    = false;
									$winner_class = '';
								}
								if ( $loser === $opponent ) {
									$is_loser = true;
								} else {
									$is_loser = false;
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
													<?php echo esc_html( trim( $team_player ) ); ?>
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
							if ( 'admin' === $user_type ) {
								?>
								<div class="walkover" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_html_e( 'Walkover', 'racketmanager' ); ?>">
									<div class="form-check">
										<input class="form-check-input" name="match_status" type="radio" value="walkover_player1" id="walkover_player1" aria-describedby="<?php esc_html_e( 'Team 1 walkover', 'racketmanager' ); ?>">
									</div>
									<div class="match__result-status"><?php echo esc_html_e( 'W/O', 'racketmanager' ); ?></div>
									<div class="form-check">
										<input class="form-check-input" name="match_status" type="radio" value="walkover_player2" id="walkover_player2" aria-describedby="<?php esc_html_e( 'Team 2 walkover', 'racketmanager' ); ?>">
									</div>
								</div>
								<?php
							}
							?>
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
									<span class="set-group" id="set_<?php echo esc_html( $i ); ?>" data-settype="<?php echo esc_attr( $set_type ); ?>" data-maxwin="<?php echo esc_attr( $set_info->max_win ); ?>" data-maxloss="<?php echo esc_attr( $set_info->max_loss ); ?>" data-minwin="<?php echo esc_attr( $set_info->min_win ); ?>" data-minloss="<?php echo esc_attr( $set_info->min_loss ); ?>">
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
														<input type="text" class="points match-points__cell-input <?php echo esc_html( $winner_point_class ); ?>" id="set_<?php echo esc_html( $i ); ?>_<?php echo esc_html( $opponent ); ?>" name="sets[<?php echo esc_html( $i ); ?>][<?php echo esc_html( $opponent ); ?>]" value="<?php echo isset( $set[ $opponent ] ) ? esc_html( $set[ $opponent ] ) : ''; ?>" onblur="Racketmanager.SetCalculator(this)" />
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
													<input type="text" class="points match-points__cell-input" id="set_<?php echo esc_html( $i ); ?>_tiebreak" name="sets[<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo isset( $set['tiebreak'] ) ? esc_html( $set['tiebreak'] ) : ''; ?>" onblur="Racketmanager.SetCalculatorTieBreak(this)" />
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
							<div class="walkover" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_html_e( 'Retired', 'racketmanager' ); ?>">
								<div class="form-check">
									<input class="form-check-input" name="match_status" type="radio" value="retired_player1" id="retired_player1" aria-describedby="<?php esc_html_e( 'Team 1 retirement', 'racketmanager' ); ?>">
								</div>
								<div class="match__result-status">Ret</div>
								<div class="form-check">
									<input class="form-check-input" name="match_status" type="radio" value="retired_player2" id="retired_player2" aria-describedby="<?php esc_html_e( 'Team 2 retirement', 'racketmanager' ); ?>">
								</div>
							</div>
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
								<?php
								if ( $user_can_update ) {
									?>
					<div class="row mb-3">
						<div id="updateResponse" class="updateResponse"></div>
					</div>
									<?php
								} else {
									?>
					<div class="row mb-3 justify-content-center">
						<div class="col-auto">
											<?php if ( 'notLoggedIn' === $user_message ) { ?>
							You need to <a href="<?php echo esc_html( wp_login_url( wp_get_current_url() ) ); ?>">login</a> to update the result.
												<?php
											} else {
												esc_html_e( 'User not allowed to update result', 'racketmanager' );
											}
											?>
						</div>
					</div>
									<?php
								}
								$page_referrer = wp_get_referer();
								if ( ! $page_referrer ) {
									$page_referrer = $tournament->link . 'matches/';
								}
								?>
				<div class="col-6">
					<a href="<?php echo esc_url( $page_referrer ); ?>">
						<button tabindex="500" class="btn btn-secondary" type="button"><?php esc_html_e( 'Return', 'racketmanager' ); ?></button>
					</a>
				</div>
			</form>
		</div>
	</div>
								<?php
}
?>
