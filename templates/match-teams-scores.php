<?php
/**
 * Form to allow input of match scores for rubbers
 *
 * @package Racketmanager/Templates;
 */

namespace Racketmanager;

global $racketmanager;
$opponents        = array( 'home', 'away' );
$opponents_points = array( 'player1', 'player2' );
$winner_set       = null;
if ( ! empty( $match_player ) ) {
	$match->player = $match_player;
}
if ( ! empty( $match->player ) ) {
	$rubbers = $match->rubbers;
} else {
	$rubbers = $match->get_rubbers();
}
$team              = null;
$team_status       = null;
$team_match_status = null;
$winner            = null;
$loser             = null;
$match_complete    = false;
if ( ! empty( $competition_team ) ) {
	if ( $competition_team === $match->home_team ) {
		$team     = 'home';
		$team_ref = 'player1';
	} elseif ( $competition_team === $match->away_team ) {
		$team     = 'away';
		$team_ref = 'player2';
	}
}
if ( ! empty( $match->winner_id ) ) {
	$match_complete = true;
	if ( $match->winner_id === $match->home_team ) {
		$winner = 'home';
		$loser  = 'away';
	} elseif ( $match->winner_id === $match->away_team ) {
		$winner = 'away';
		$loser  = 'home';
	}
	if ( $winner === $team ) {
		$team_match_status = 'winner';
	} elseif ( $loser === $team ) {
		$team_match_status = 'loser';
	}
	if ( isset( $team_statistics ) ) {
		++$team_statistics['played'][ $team_statistics ][ $match_type ];
		++$team_statistics['played'][ $team_statistics ]['t'];
	}
}
?>
<div id="matchrubbers">
	<ul class="match-group">
		<?php
		if ( ! empty( $match->player ) ) {
			if ( $match->league->is_championship ) {
				$match_ref = $match->final_round;
			} else {
				$match_ref = 'day' . $match->match_day;
			}
			$match_link = $match->link;
			?>
			<div class="match--team-match match">
				<a class="team-match__wrapper" href="<?php echo esc_attr( $match_link ); ?>">
					<div class="match__header">
						<span class="match__header-title">
							<?php
							if ( ! empty( $match->final_round ) ) {
								?>
							<span><?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?>&nbsp;&#8226;&nbsp;</span>
								<?php
							} elseif ( ! empty( $match->match_day ) ) {
								?>
							<span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->match_day ); ?>&nbsp;&#8226;&nbsp;</span>
								<?php
							}
							?>
							<span>
								<time
									datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( 'j. F Y', the_match_date() ) ); ?></time>
							</span>
						</span>
					</div>
					<div class="match__body">
						<div class="team-match">
							<div
								class="team-match__name <?php echo esc_attr( 'home' === $winner ? 'winner' : '' ); ?> is-team-1">
								<span class="nav--link">
									<span class="nav-link__value">
										<?php echo esc_html( $match->teams['home']->title ); ?>
									</span>
								</span>
							</div>
							<div class="score">
								<span class="is-team-1"><?php echo esc_html( sprintf( '%g', $match->home_points ) ); ?></span>
								<span class="score-separator">-</span>
								<span class="is-team-2"><?php echo esc_html( sprintf( '%g', $match->away_points ) ); ?></span>
							</div>
							<div
								class="team-match__name <?php echo esc_attr( 'away' === $winner ? 'winner' : '' ); ?> is-team-2">
								<span class="nav--link">
									<span class="nav-link__value">
										<?php echo esc_html( $match->teams['away']->title ); ?>
									</span>
								</span>
							</div>
						</div>
					</div>
				</a>
			</div>
			<?php
		}
		?>
		<?php
		foreach ( $rubbers as $rubber ) {
			$player_team        = null;
			$player_ref         = null;
			$player_team_status = null;
			$winner             = null;
			$loser              = null;
			$team_status        = null;
			$is_tie             = false;
			if ( ! empty( $rubber->winner_id ) ) {
				if ( $rubber->winner_id === $match->home_team ) {
					$winner = 'home';
					$loser  = 'away';
				} elseif ( $rubber->winner_id === $match->away_team ) {
					$winner = 'away';
					$loser  = 'home';
				} elseif ( '-1' === $rubber->winner_id ) {
					$is_tie = true;
				}
				if ( $winner === $team ) {
					$team_status = 'winner';
				} elseif ( $loser === $team ) {
					$team_status = 'loser';
				}
			}
			$rubber_title = $rubber->type . $rubber->rubber_number;
			if ( 'D' === substr( $rubber->type, 1, 1 ) ) {
				$rubber_players = array(
					'1' => array(),
					'2' => array(),
				);
				$doubles        = true;
			} else {
				$rubber_players = array( '1' => array() );
				$doubles        = false;
			}
			if ( 'M' === substr( $rubber->type, 0, 1 ) ) {
				foreach ( $rubber_players as $p => $rubber_player ) {
					$rubber_players[ $p ]['gender'] = 'm';
				}
			} elseif ( 'W' === substr( $rubber->type, 0, 1 ) ) {
				foreach ( $rubber_players as $p => $rubber_player ) {
					$rubber_players[ $p ]['gender'] = 'f';
				}
			} elseif ( 'X' === substr( $rubber->type, 0, 1 ) ) {
				$rubber_players['1']['gender'] = 'm';
				$rubber_players['2']['gender'] = 'f';
			}
			if ( ! empty( $match->player ) ) {
				foreach ( $opponents as $opponent ) {
					foreach ( $rubber_players as $p => $rubber_player ) {
						if ( $rubber->players[ $opponent ][ $p ]->fullname === $match->player->display_name ) {
							$player_team = $opponent;
							$player_ref  = 'player' . $p;
							break 2;
						}
					}
				}
				if ( $winner === $player_team ) {
					$player_team_status = 'winner';
				} elseif ( $loser === $player_team ) {
					$player_team_status = 'loser';
				}
			}
			?>
			<li class="match-group__item">
				<div class="match"
					id="rubber-<?php echo esc_attr( $rubber->id ); ?>">
					<div class="match__header">
						<ul class="match__header-title">
							<li class="match__header-title-item">
								<span title="<?php echo esc_attr( $rubber_title ); ?>" class="nav--link">
									<span class="nav-link__value"><?php echo esc_html( $rubber_title ); ?></span>
								</span>
							</li>
						</ul>
					</div>
					<div class="match__body">
						<div class="match__row-wrapper">
							<?php
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
							<div class="match__row">
								<div class="match__row-title">
									<div class="match__row-title-header">
										<?php
										if ( 'W' === $match->teams[ $opponent ]->status ) {
											$title_text = $match->teams[ $opponent ]->title . ' ' . __( 'has withdrawn', 'racketmanager' );
											?>
											<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
											<?php
										}
										?>
										<?php echo esc_html( $match->teams[ $opponent ]->title ); ?>
										<?php
										if ( 'W' === $match->teams[ $opponent ]->status ) {
											?>
											</s> 
											<?php
										}
										?>
									</div>
									<?php
									foreach ( $rubber_players as $player_number => $rubber_player ) {
										$player_ref = $opponent . '_player' . $player_number;
										?>
										<div class="match__row-title-value">
											<span class="match__row-title-value-content">
												<span class="nav-link__value <?php echo esc_html( $winner_class ); ?>">
													<?php
													if ( ! empty( $rubber->players[ $opponent ][ $player_number ] ) ) {
														$player_detail = $rubber->players[ $opponent ][ $player_number ];
														if ( empty( $player_detail->system_record ) ) {
															?>
															<a href="/<?php echo esc_attr( $match->league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $match->league->event->name ) ); ?>/<?php echo esc_attr( $match->season ); ?>/player/<?php echo esc_attr( seo_url( $player_detail->fullname ) ); ?>/">
															<?php
														}
														?>
														<span class="<?php echo empty( $player_detail->description ) ? '' : 'is-invalid'; ?>"
														<?php
														if ( ! empty( $player_detail->description ) ) {
															?>
															data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php echo esc_attr( $player_detail->description ); ?>"
															<?php
														}
														?>
														><?php echo esc_html( $player_detail->fullname ); ?></span>
														<?php
														if ( empty( $player_detail->system_record ) ) {
															?>
															</a>
															<?php
														}
														?>
														<?php
													}
													?>
												</span>
											</span>
										</div>
										<?php
									}
									?>
								</div>
								<?php
								$match_message_class = null;
								$match_message_text  = null;
								$match_status_class  = null;
								$match_status_text   = null;
								if ( $is_winner ) {
									if ( ! empty( $match->player ) ) {
										if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
											$match_status_class = 'winner';
											$match_status_text  = 'W';
										}
									} elseif ( empty( $team_status ) || 'winner' === $team_status ) {
										$match_status_class = 'winner';
										$match_status_text  = 'W';
									}
								} elseif ( $is_loser ) {
									if ( ! empty( $match->player ) ) {
										if ( 'loser' === $player_team_status ) {
											$match_status_class = 'loser';
											$match_status_text  = 'L';
										}
									} elseif ( 'loser' === $team_status ) {
										$match_status_class = 'loser';
										$match_status_text  = 'L';
									} else {
										$match_status_class = 'd-none';
										$match_status_text  = '';
									}
									if ( $rubber->is_walkover ) {
										$match_message_class = 'match-warning';
										$match_message_text  = __( 'Walkover', 'racketmanager' );
										if ( empty( $match_status_class ) ) {
											$match_status_class = 'd-none';
										}
										if ( isset( $team_statistics ) && 'winner' === $team_status ) {
											++$team_statistics['walkover'][ $match_type ];
											++$team_statistics['walkover']['t'];
										}
									} elseif ( $rubber->is_retired ) {
										$match_message_class = 'match-warning';
										$match_message_text  = __( 'Retired', 'racketmanager' );
									}
								} elseif ( $is_tie ) {
									if ( $rubber->is_walkover ) {
										$match_message_class = 'match-warning';
										$match_message_text  = __( 'Walkover', 'racketmanager' );
										$match_status_class  = 'd-none';
										$match_status_text   = '';
									} elseif ( $rubber->is_shared ) {
										$match_status_class  = 'tie';
										$match_message_class = 'match-warning';
										$match_status_text   = 'T';
										$match_message_text  = __( 'Shared', 'racketmanager' );
									}
								}
								?>
								<span class="match__message <?php echo esc_attr( $match_message_class ); ?>"
									id="match-message-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
									<?php echo esc_html( $match_message_text ); ?>
								</span>
								<span class="match__status <?php echo esc_attr( $match_status_class ); ?>"
									id="match-status-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
									<?php echo esc_html( $match_status_text ); ?>
								</span>
							</div>
								<?php
							}
							?>
						</div>
						<div class="match__result">
							<?php
							$sets = isset( $rubber->sets ) ? $rubber->sets : array();
							for ( $i = 1; $i <= $match->league->num_sets; $i++ ) {
								$set = isset( $sets[ $i ] ) ? $sets[ $i ] : array();
								if ( ! empty( $set['player1'] ) || ! empty( $set['player2'] ) ) {
									if ( $set['player1'] > $set['player2'] ) {
										$winner_set = 'player1';
									} elseif ( $set['player1'] < $set['player2'] ) {
										$winner_set = 'player2';
									} else {
										$winner_set = null;
									}
									?>
									<ul class="match-points">
										<?php
										foreach ( $opponents_points as $opponent ) {
											if ( $winner_set === $opponent ) {
												$winner_class = ' winner';
											} else {
												$winner_class = '';
											}
											?>
											<li class="match-points__cell <?php echo esc_html( $winner_class ); ?>">
												<?php echo isset( $set[ $opponent ] ) ? esc_html( $set[ $opponent ] ) : ''; ?>
												<?php
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
								?>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
	if ( empty( $match_player ) && ( ! empty( $match->home_captain ) || ! empty( $match->away_captain ) ) ) {
		?>
		<div class="mt-3" id="approvals">
			<div class="match">
				<div class="match__header">
					<ul class="match__header-title">
						<li class="match__header-title-item">
							<span class="nav-link__value"><?php esc_html_e( 'Approvals', 'racketmanager' ); ?></span>
						</li>
					</ul>
				</div>
				<div class="match__body">
					<div class="match__row-wrapper">
						<?php
						foreach ( $opponents as $opponent ) {
							?>
							<div class="match__row">
								<div class="match__row-title">
									<div class="match__row-title-header">
										<?php echo esc_html( $match->teams[ $opponent ]->title ); ?>
									</div>
									<div class="match__row-title-value">
										<?php
										$approval_captain = $opponent . '_captain';
										if ( isset( $match->$approval_captain ) ) {
											?>
											<span class="match__row-title-value-content">
												<span class="nav-link__value"><?php echo esc_html( $racketmanager->get_player_name( $match->$approval_captain ) ); ?></span>
											</span>
											<?php
										}
										?>
									</div>
									<?php
									if ( ! empty( $match->comments[ $opponent ] ) ) {
										?>
										<div class="match__row-title-value">
											<span class="match__row-title-value-content">
												<span class="nav-link__value match-comments"title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->comments[ $opponent ] ); ?></span>
											</span>
										</div>
										<?php
									}
									?>
								</div>
							</div>
							<?php
						}
						?>
						<?php
						if ( ! empty( $match->comments['result'] ) ) {
							?>
							<div class="match__row match__row-comments">
								<div class="match__row-title">
									<div class="match__row-title-header">
										<?php esc_html_e( 'Comments', 'racketmanager' ); ?>
									</div>
									<div class="match__row-title-value">
										<span class="match__row-title-value-content">
											<span class="nav-link__value match-comments"title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->comments['result'] ); ?></span>
										</span>
									</div>
								</div>
							</div>
							<?php
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
