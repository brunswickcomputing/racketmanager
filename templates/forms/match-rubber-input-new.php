<?php
/**
 * Form to allow input of match scores for rubbers
 *
 * @package Racketmanager/Templates;
 */

namespace Racketmanager;

global $racketmanager;
$user_can_update = $user_can_update_array[0];
$user_type       = $user_can_update_array[1];
$user_team       = $user_can_update_array[2];
$user_message    = $user_can_update_array[3];
$updates_allowed = true;
if ( 'P' === $match->confirmed && 'admin' !== $user_type ) {
	$updates_allowed = false;
}
$opponents        = array( 'home', 'away' );
$opponents_points = array( 'player1', 'player2' );
$winner_set       = null;
if ( ! empty( $home_club_player['m'] ) ) {
	$club_players['home']['m'] = $home_club_player['m'];
}
if ( ! empty( $home_club_player['f'] ) ) {
	$club_players['home']['f'] = $home_club_player['f'];
}
if ( ! empty( $away_club_player['m'] ) ) {
	$club_players['away']['m'] = $away_club_player['m'];
}
if ( ! empty( $away_club_player['f'] ) ) {
	$club_players['away']['f'] = $away_club_player['f'];
}
$rubbers           = $match->get_rubbers();
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
$match_approval_mode = false;
$match_editable      = false;
if ( $user_can_update && $is_edit_mode ) {
	if ( empty( $match->confirmed ) || 'admin' === $user_type ) {
		$match_editable = 'is-editable';
	} elseif ( 'P' === $match->confirmed ) {
		$match_approval_mode = true;
	}
}
?>
<div id="matchrubbers">
	<form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
		<?php wp_nonce_field( 'rubbers-match', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="updated_form" value="new" />
		<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_html( $match->id ); ?>" />
		<div class="row mb-3">
			<div id="updateResponse" class="updateResponse"></div>
		</div>
		<ul class="match-group">
			<?php
			foreach ( $rubbers as $rubber ) {
				if ( ! empty( $rubber->winner_id ) ) {
					if ( $rubber->winner_id === $match->home_team ) {
						$winner = 'home';
						$loser  = 'away';
					} elseif ( $rubber->winner_id === $match->away_team ) {
						$winner = 'away';
						$loser  = 'home';
					}
					if ( $winner === $team ) {
						$team_status = 'winner';
					} elseif ( $loser === $team ) {
						$team_status = 'loser';
					}
				} else {
					$winner = null;
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
					foreach ( $rubber_players as $p => $player ) {
						$rubber_players[ $p ]['gender'] = 'm';
					}
				} elseif ( 'W' === substr( $rubber->type, 0, 1 ) ) {
					foreach ( $rubber_players as $p => $player ) {
						$rubber_players[ $p ]['gender'] = 'f';
					}
				} elseif ( 'X' === substr( $rubber->type, 0, 1 ) ) {
					$rubber_players['1']['gender'] = 'm';
					$rubber_players['2']['gender'] = 'f';
				}
				?>
				<input type="hidden" name="id[<?php echo esc_attr( $rubber->rubber_number ); ?>]" value="<?php echo esc_html( $rubber->id ); ?>" </>
				<input type="hidden" name="type[<?php echo esc_attr( $rubber->rubber_number ); ?>]" value="<?php echo esc_html( $rubber->type ); ?>" </>
				<li class="match-group__item">
					<div class="match <?php echo esc_attr( $match_editable ); ?>" id="rubber-<?php echo esc_attr( $rubber->id ); ?>">
						<div class="match__header">
							<ul class="match__header-title">
								<li class="match__header-title-item">
									<span title="<?php echo esc_attr( $rubber_title ); ?>" class="nav--link">
										<span class="nav-link__value"><?php echo esc_html( $rubber_title ); ?></span>
									</span>
								</li>
							</ul>
							<?php
							if ( $match_editable ) {
								?>
								<div class="match__header-aside">
									<div class="match__header-aside-block">
										<div class="form-check">
											<input class="form-check-input" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" id="match_status_<?php echo esc_attr( $rubber->rubber_number ); ?>" type="radio" value="share" aria-describedby="<?php esc_html_e( 'Share rubber', 'racketmanager' ); ?>">
											<label class="form-check-label" for="match_status_<?php echo esc_attr( $rubber->rubber_number ); ?>"><?php esc_html_e( 'Share rubber', 'racketmanager' ); ?></label>
										</div>
									</div>
								</div>
								<?php
							}
							?>
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
												<?php echo esc_html( $match->teams[ $opponent ]->title ); ?>
											</div>
											<?php
											foreach ( $rubber_players as $player_number => $player ) {
												$player_ref = $opponent . '_player' . $player_number;
												?>
												<div class="match__row-title-value">
													<span class="match__row-title-value-content">
														<span class="nav-link__value <?php echo esc_html( $winner_class ); ?>">
															<?php
															if ( $match_editable ) {
																?>
																<select class="form-select" name="players[<?php echo esc_attr( $rubber->rubber_number ); ?>][<?php echo esc_attr( $opponent ); ?>][<?php echo esc_attr( $player_number ); ?>]" id="players_<?php echo esc_attr( $rubber->rubber_number ); ?>_<?php echo esc_attr( $opponent ); ?>_<?php echo esc_attr( $player_number ); ?>">
																	<option value="0">
																		<?php esc_html_e( 'Select player', 'racketmanager' ); ?>
																	</option>
																	<?php
																	foreach ( $club_players[ $opponent ][ $player['gender'] ] as $player_option ) {
																		if ( ! empty( $player_option->removed_date ) ) {
																			$disabled = 'disabled';
																		} else {
																			$disabled = '';
																		}
																		?>
																		<option value="<?php echo esc_attr( $player_option->roster_id ); ?>" <?php selected( $player_option->roster_id, $rubber->$player_ref ); ?> <?php echo esc_html( $disabled ); ?>>
																			<?php echo esc_html( $player_option->fullname ); ?>
																		</option>
																		<?php
																	}
																	?>
																</select>
																<?php
															} elseif ( ! empty( $rubber->players[ $opponent ][ $player_number ] ) ) {
																echo esc_html( $rubber->players[ $opponent ][ $player_number ]->fullname );
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
										if ( ! $match_editable ) {
											if ( $is_winner ) {
												if ( empty( $team_status ) || 'winner' === $team_status ) {
													$match_status_class = 'winner';
													$match_status_text  = 'W';
												}
											} elseif ( $is_loser ) {
												if ( 'loser' === $team_status ) {
													$match_status_class = 'loser';
													$match_status_text  = 'L';
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
											}
										}
										?>
										<span class="match__message <?php echo esc_attr( $match_message_class ); ?>" id="match-message-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
												<?php echo esc_html( $match_message_text ); ?>
										</span>
										<span class="match__status <?php echo esc_attr( $match_status_class ); ?>" id="match-status-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
												<?php echo esc_html( $match_status_text ); ?>
										</span>
									</div>
									<?php
								}
								?>
							</div>
							<div class="match__result">
								<div class="walkover" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_html_e( 'Walkover', 'racketmanager' ); ?>">
									<div class="form-check">
										<input class="form-check-input" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" type="radio" value="walkover_player1" id="walkover_player1-<?php echo esc_attr( $rubber->rubber_number ); ?>" aria-describedby="<?php esc_html_e( 'Team 1 walkover', 'racketmanager' ); ?>" <?php echo 'home' === $winner && '2' === $rubber->status ? 'checked' : ''; ?>>
									</div>
									<div class="match__result-status">
										<?php esc_html_e( 'W/O', 'racketmanager' ); ?>
									</div>
									<div class="form-check">
										<input class="form-check-input" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" type="radio" value="walkover_player2" id="walkover_player2-<?php echo esc_attr( $rubber->rubber_number ); ?>" aria-describedby="<?php esc_html_e( 'Team 2 walkover', 'racketmanager' ); ?>" <?php echo 'away' === $winner && '2' === $rubber->status ? 'checked' : ''; ?>>
									</div>
								</div>
									<?php
									$sets = isset( $rubber->sets ) ? $rubber->sets : array();
									for ( $i = 1; $i <= $match->league->num_sets; $i++ ) {
										$set = isset( $sets[ $i ] ) ? $sets[ $i ] : array();
										if ( isset( $set['player1'] ) && isset( $set['player2'] ) ) {
											if ( $set['player1'] > $set['player2'] ) {
												$winner_set = 'player1';
											} else {
												$winner_set = 'player2';
											}
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
												<li class="match-points__cell<?php echo $match_editable ? '-input' : null; ?>">
													<input type="text" class="points points__cell-input <?php echo esc_html( $winner_class ); ?>" id="set_<?php echo esc_attr( $rubber->rubber_number ); ?>_<?php echo esc_html( $i ); ?>_<?php echo esc_html( $opponent ); ?>" name="sets[<?php echo esc_attr( $rubber->rubber_number ); ?>][<?php echo esc_html( $i ); ?>][<?php echo esc_html( $opponent ); ?>]" value="<?php echo isset( $set[ $opponent ] ) ? esc_html( $set[ $opponent ] ) : ''; ?>"
														<?php
														if ( ! $match_editable ) {
															echo 'disabled';
														}
														?>
													/>
												</li>
												<?php
											}
											?>
										</ul>
										<?php
										if ( $match_editable ) {
											?>
											<ul class="match-points tie-break" id="set_<?php echo esc_html( $i ); ?>_tie-break_wrapper">
												<li class="match-points__cell-input">
													<input type="text" class="points points__cell-input" id="set_<?php echo esc_attr( $rubber->rubber_number ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="sets[<?php echo esc_attr( $rubber->rubber_number ); ?>][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo isset( $set['tiebreak'] ) ? esc_html( $set['tiebreak'] ) : ''; ?>" />
												</li>
											</ul>
											<?php
										}
										?>
										<?php
									}
									?>
								<div class="walkover" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_html_e( 'Retired', 'racketmanager' ); ?>">
									<div class="form-check">
										<input class="form-check-input" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" type="radio" value="retired_player1" id="retired_player1-<?php echo esc_attr( $rubber->rubber_number ); ?>" aria-describedby="<?php esc_html_e( 'Team 1 retirement', 'racketmanager' ); ?>" <?php echo 'home' === $winner && '4' === $rubber->status ? 'checked' : ''; ?>>
									</div>
									<div class="match__result-status">
										<?php esc_html_e( 'Ret', 'racketmanager' ); ?>
									</div>
									<div class="form-check">
										<input class="form-check-input" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" type="radio" value="retired_player2" id="retired_player2-<?php echo esc_attr( $rubber->rubber_number ); ?>" aria-describedby="<?php esc_html_e( 'Team 2 retirement', 'racketmanager' ); ?>" <?php echo 'away' === $winner && '4' === $rubber->status ? 'checked' : ''; ?>>
									</div>
								</div>
							</div>
						</div>
						<?php
						if ( $match_editable ) {
							?>
							<div class="match__footer">
								<ul class="match__footer-title">
								</ul>
								<div class="match__footer-aside text-uppercase">
									<a href="" onclick="Racketmanager.resetMatchScores(event, 'rubber-<?php echo esc_attr( $rubber->id ); ?>')">
										<?php echo esc_html_e( 'Reset scores', 'racketmanager' ); ?>
									</a>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
		if ( ! empty( $match->home_captain ) || ! empty( $match->away_captain ) ) {
			?>
			<div class="mt-3" id="approvals">
				<div class="match <?php echo esc_attr( $match_editable ); ?>">
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
												if ( ! empty( $match->comments ) ) {
													?>
													</div>
													<div class="match__row-title-value">
														<span class="match__row-title-value-content">
															<span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->comments[ $opponent ] ); ?></span>
														</span>
													<?php
												}
												?>
												<?php
											} else {
												if ( 'admin' !== $user_type && ( $opponent === $user_team || 'both' === $user_team ) ) {
													?>
													<div class="approval-check">
														<div class="form-check">
															<input type="hidden" name="result_<?php echo esc_attr( $opponent ); ?>" />
															<input class="form-check-input" type="radio" name="resultConfirm" id="resultConfirm" value="confirm" required />
															<label class="form-check-label"><?php esc_html_e( 'Confirm', 'racketmanager' ); ?></label>
														</div>
														<div class="form-check">
															<input class="form-check-input" type="radio" name="resultConfirm" id="resultChallenge" value="challenge" required />
															<label class="form-check-label"><?php esc_html_e( 'Challenge', 'racketmanager' ); ?></label>
														</div>
													</div>
												</div>
													<div class="match__row-title-value">
														<div class="form-floating">
															<textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"></textarea>
															<label for="resultConfirmComments"><?php esc_html_e( 'Challenge comments', 'racketmanager' ); ?></label>
														</div>
													<?php
												}
												?>
												<?php
											}
											?>
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
		} else {
			if ( $is_edit_mode ) {
				?>
				<div class="row mt-3 mb-3">
					<div>
						<div class="form-floating">
							<textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="matchComments[result]" id="matchComments"><?php echo esc_html( $match->comments['result'] ); ?></textarea>
							<label for="matchComments"><?php esc_html_e( 'Match Comments', 'racketmanager' ); ?></label>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			<?php
		}
		?>
		<?php
		if ( $is_edit_mode && ( ( ! $match_complete || 'admin' === $user_type ) || $match_approval_mode ) ) {
			if ( 'admin' === $user_type || ( 'away' !== $user_team && ! isset( $match->home_captain ) ) || ( 'home' !== $user_team && ! isset( $match->away_captain ) ) ) {
				if ( $match_approval_mode ) {
					$update_rubber = 'confirm';
					$action_text   = __( 'Confirm Result', 'racketmanager' );
				} else {
					$update_rubber = 'results';
					$action_text   = __( 'Save Result', 'racketmanager' );
				}
				?>
				<div class="row mb-3">
					<div class="col-12">
						<input type="hidden" name="updateRubber" id="updateRubber" value="<?php echo esc_html( esc_html( $update_rubber ) ); ?>" />
						<button tabindex="500" class="button button-primary" type="button" id="updateRubberResults" onclick="Racketmanager.updateResults(this)"><?php echo esc_html( $action_text ); ?></button>
					</div>
				</div>
				<?php
			} else {
				?>
				<div class="row mb-3">
					<div class="col-12 updateResponse message-error">
						<?php esc_html_e( 'Team result already entered', 'racketmanager' ); ?>
					</div>
				</div>
				<?php
			}
		}
		?>
	</form>
</div>
<?php
