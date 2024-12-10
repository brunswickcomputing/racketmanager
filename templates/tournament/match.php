<?php
/**
 * Template for tournament match
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( ! empty( $match_display ) ) {
	$match_display = 'match--list';
} else {
	$match_display = '';
}
if ( empty( $location_in_header ) ) {
	$location_in_header = false;
}
if ( isset( $match->teams['home'] ) && isset( $match->teams['away'] ) ) {
	if ( $match->league->is_championship ) {
		$match_ref = $match->final_round;
	} else {
		$match_ref = 'day' . $match->match_day;
	}
	if ( empty( $tournament ) ) {
		$match_link = $match->link;

	} else {
		$match_link = '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
	}
	$is_update_allowed = $match->is_update_allowed();
	$user_can_update   = $is_update_allowed->user_can_update;
} else {
	$user_can_update = false;
}
$match_type         = strtolower( substr( $match->league->type, 1, 1 ) );
$winner             = null;
$loser              = null;
$is_tie             = null;
$winner_set         = null;
$player_team        = null;
$player_team_status = null;
if ( ! empty( $tournament_player ) ) {
	if ( isset( $match->teams['home']->player ) && array_search( $tournament_player->display_name, $match->teams['home']->player, true ) ) {
		$player_team = 'home';
		$player_ref  = 'player1';
	} elseif ( isset( $match->teams['away']->player ) && array_search( $tournament_player->display_name, $match->teams['away']->player, true ) ) {
		$player_team = 'away';
		$player_ref  = 'player2';
	}
}
$match_selected = false;
if ( is_user_logged_in() ) {
	if ( isset( $match->teams['home']->player ) && array_search( wp_get_current_user()->display_name, $match->teams['home']->player, true ) ) {
		$match_selected = true;
	} elseif ( isset( $match->teams['away']->player ) && array_search( wp_get_current_user()->display_name, $match->teams['away']->player, true ) ) {
		$match_selected = true;
	}
}
if ( ! empty( $match->winner_id ) ) {
	$match_complete = true;
	if ( $match->winner_id === $match->teams['home']->id ) {
		$winner     = 'home';
		$loser      = 'away';
		$winner_set = 'player1';
	} elseif ( $match->winner_id === $match->teams['away']->id ) {
		$winner     = 'away';
		$loser      = 'home';
		$winner_set = 'player2';
	} elseif ( '-1' === $match->winner_id ) {
		$is_tie = true;
	}
	if ( $winner === $player_team ) {
		$player_team_status = 'winner';
	} elseif ( $loser === $player_team ) {
		$player_team_status = 'loser';
	}
}
?>
		<div class="match tournament-match <?php echo esc_html( $match_display ); ?> <?php echo empty( $match_selected ) ? '' : 'is-selected'; ?>">
			<div class="match__header">
				<ul class="match__header-title">
					<li class="match__header-title-item">
						<?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?>
					</li>
					<?php
					if ( ! empty( $tournament ) ) {
						?>
						<li class="match__header-title-item">
							<a href="<?php echo esc_html( $tournament->link ) . 'draw/' . esc_html( seo_url( $match->league->event->name ) ) . '/'; ?>">
								<?php echo esc_html( $match->league->title ); ?>
							</a>
						</li>
						<?php
					} elseif ( empty( $match_complete ) && ! empty( $match->date ) ) {
						?>
						<li class="match__header-title-item">
							<?php
							if ( empty( $match->start_time ) ) {
								echo esc_html_e( 'Play by', 'racketmanager' ) . ' ';
							}
							?>
							<?php the_match_date(); ?>
							<?php
							if ( ! empty( $match->start_time ) ) {
								echo ' ' . esc_html_e( 'at', 'racketmanager' );
								the_match_time();
							}
							?>
						</li>
						<?php
					}
					if ( $location_in_header && ! empty( $match->location ) ) {
						?>
						<li class="match__header-title-item match__location">
							<?php
							the_match_location();
							?>
						</li>
						<?php
					}
					?>
				</ul>
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
										<?php
										if ( 'final' === $match->final_round ) {
											$prev_match = 'prev_' . $opponent . '_match';
											if ( ! empty( $match->$prev_match->match_title ) ) {
												$match_title = $match->$prev_match->match_title;
											} else {
												$match_title = $team->title;
											}
										} else {
											$match_title = $team->title;
										}
										echo esc_html( $match_title );
										?>
									</div>
									<?php
								} else {
									foreach ( $team->players as $team_player ) {
										?>
										<div class="match__row-title-value">
											<?php
											if ( ! empty( $tournament ) ) {
												$player_link = '/tournament/' . seo_url( $tournament->name ) . '/players/' . seo_url( trim( $team_player->display_name ) ) . '/';
												?>
												<a href="<?php echo esc_attr( $player_link ); ?>" onclick="Racketmanager.tabDataLink(event,'tournament',<?php echo esc_attr( $tournament->id ); ?>,'','<?php echo esc_attr( $player_link ); ?>',<?php echo esc_attr( $team_player->id ); ?>,'players')">
												<?php
											}
											?>
											<?php echo esc_html( trim( $team_player->display_name ) ); ?>
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
							if ( $is_winner ) {
								if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
									?>
									<span class="match__status winner">W</span>
									<?php
								}
							} elseif ( $is_loser ) {
								if ( $match->is_walkover ) {
									?>
									<span class="match__message match-warning"><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></span>
									<?php
								} elseif ( $match->is_retired ) {
									?>
									<span class="match__message match-warning"><?php esc_html_e( 'Retired', 'racketmanager' ); ?></span>
									<?php
								}
								if ( 'loser' === $player_team_status ) {
									?>
									<span class="match__status loser">L</span>
									<?php
								}
							} elseif ( $is_tie ) {
								?>
								<span class="match__message match-warning"><?php esc_html_e( 'Not played', 'racketmanager' ); ?></span>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
				</div>
				<div class="match__result">
					<?php
					$sets = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
					foreach ( $sets as $set ) {
						if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
							?>
							<ul class="match-points">
								<?php
								$opponents = array( 'player1', 'player2' );
								foreach ( $opponents as $opponent ) {
									if ( $set['winner'] === $opponent ) {
										$winner_class = ' winner';
									} else {
										$winner_class = '';
									}
									?>
									<li class="match-points__cell <?php echo esc_html( $winner_class ); ?>">
										<?php
										echo esc_html( $set[ $opponent ] );
										if ( isset( $set['tiebreak'] ) && ! empty( $winner_class ) ) {
											?>
											<span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
											<?php
										}
										?>
										</li>
									<?php
								}
								?>
							</ul>
							<?php
						}
					}
					?>
				</div>
				<?php
				if ( $user_can_update && empty( $match->confirmed ) ) {
					?>
					<div class="match__button">
						<a href="<?php echo esc_url( $match_link ); ?>" class="btn match__btn">
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-pencil' ); ?>
							</i>
						</a>
					</div>
					<?php
				}
				?>
			</div>
			<div class="match__footer">
				<ul class="match__footer-list">
					<li class="match__footer-list-item">
						<?php
						if ( empty( $match->location ) ) {
							if ( isset( $match->host ) ) {
								if ( 'home' === $match->host ) {
									if ( isset( $match->teams['home']->club->shortcode ) ) {
										echo esc_html( $match->teams['home']->club->shortcode );
									}
								} elseif ( 'away' === $match->host ) {
									if ( isset( $match->teams['away']->club->shortcode ) ) {
										echo esc_html( $match->teams['away']->club->shortcode );
									}
								}
							}
						} else {
							the_match_location();
						}
						?>
					</li>
					<?php
					if ( empty( $match_complete ) && ! empty( $tournament ) ) {
						?>
						<li class="match__header-title-item">
							<?php
							if ( empty( $match->start_time ) ) {
								echo esc_html_e( 'Play by', 'racketmanager' ) . ' ';
							}
							?>
							<?php the_match_date(); ?>
							<?php
							if ( ! empty( $match->start_time ) ) {
								echo ' ' . esc_html_e( 'at', 'racketmanager' );
								the_match_time();
							}
							?>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
