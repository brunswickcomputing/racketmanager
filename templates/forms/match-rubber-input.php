<?php
/**
 * Form to allow input of match scores for rubbers
 *
 * @package Racketmanager/Templates;
 */

namespace Racketmanager;

global $racketmanager;
$user_can_update  = $user_can_update_array[0];
$user_type        = $user_can_update_array[1];
$user_team        = $user_can_update_array[2];
$user_message     = $user_can_update_array[3];
$updates_allowed  = true;
$match_complete   = false;
$opponents        = array( 'home', 'away' );
$opponent_players = array( 'player1', 'player2' );
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
if ( 'P' === $match->confirmed && 'admin' !== $user_type ) {
	$updates_allowed = false;
}
$match_approval_mode = false;
$match_editable      = 'is-editable';
if ( $user_can_update && $is_edit_mode ) {
	if ( empty( $match->confirmed ) || 'admin' === $user_type ) {
		$match_editable = 'is-editable';
	} elseif ( 'P' === $match->confirmed ) {
		$match_approval_mode = true;
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
	} else {
		$winner = null;
		$loser  = null;
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
<form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
	<?php wp_nonce_field( 'rubbers-match', 'racketmanager_nonce' ); ?>
	<div class="row mb-3">
		<div id="updateResponse" class="updateResponse"></div>
	</div>

	<input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo esc_html( $match->league_id ); ?>" />
	<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_html( $match->id ); ?>" />
	<input type="hidden" name="current_season" id="current_season" value="<?php echo esc_html( $match->season ); ?>" />
	<input type="hidden" name="num_rubbers" value="<?php echo esc_html( $match->league->num_rubbers ); ?>" />
	<input type="hidden" name="home_club" value="<?php echo esc_html( $match->teams['home']->affiliatedclub ); ?>" />
	<input type="hidden" name="home_team" value="<?php echo esc_html( $match->home_team ); ?>" />
	<input type="hidden" name="away_club" value="<?php echo esc_html( $match->teams['away']->affiliatedclub ); ?>" />
	<input type="hidden" name="away_team" value="<?php echo esc_html( $match->away_team ); ?>" />
	<input type="hidden" name="match_type" value="<?php echo esc_html( $match->league->type ); ?>" />
	<input type="hidden" name="match_round" value="<?php echo esc_html( $match->round ); ?>" />
	<?php
	$rubbers     = $match->get_rubbers();
	$r           = 0;
	$tabbase     = 0;
	$num_players = 2;

	foreach ( $rubbers as $rubber ) {
		$r            = $rubber->rubber_number;
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
		<div class="match <?php echo esc_attr( $match_editable ); ?>" id="rubber-<?php echo esc_attr( $rubber->id ); ?>">
			<div class="match__header">
				<ul class="match__header-title">
					<li class="match__header-title-item">
						<span class="nav--link" title="<?php echo esc_attr( $rubber_title ); ?>">
							<span class="nav-link__value"><?php echo esc_html( $rubber_title ); ?></span>
						</span>
						<input type="hidden" name="id[<?php echo esc_attr( $r ); ?>]" value="<?php echo esc_attr( $rubber->id ); ?>" </>
						<input type="hidden" name="type[<?php echo esc_attr( $rubber->rubber_number ); ?>]" value="<?php echo esc_html( $rubber->type ); ?>" </>
					</li>
				</ul>
			</div>
			<div class="match__body">
				<div class="match__body-wrapper">
					<!-- walkover/share boxes -->
					<div class="row text-center">
						<?php
						$o = 0;
						foreach ( $opponents as $opponent ) {
							?>
							<div class="col-4 <?php echo 0 === $o ? '' : 'order-3'; ?>">
								<label for="walkover<?php echo esc_attr( ucfirst( $opponent ) ); ?>_<?php echo esc_attr( $r ); ?>"><?php echo esc_attr( ucfirst( $opponent ) . ' ' . __( 'walkover', 'racketmanager' ) ); ?></label>
								<input type="radio" class="form-check-input" name="match_status[<?php echo esc_attr( $r ); ?>]" id="walkover<?php echo esc_attr( ucfirst( $opponent ) ); ?>_<?php echo esc_attr( $r ); ?>" value="walkover_<?php echo esc_attr( $opponent_players[ $o ] ); ?>"
									<?php
									if ( isset( $rubber->walkover ) && $opponent === $rubber->walkover ) {
										echo esc_html( ' checked' );
									}
									?>
									<?php
									if ( ! $updates_allowed ) {
										echo esc_html( ' disabled' );
									}
									?>
								/>
							</div>
							<?php
							++$o;
						}
						?>
						<div class="col-4 order-2">
							<div class="col-12">
								<label for="sharedRubber_<?php echo esc_html( $r ); ?>"><?php esc_html_e( 'Share', 'racketmanager' ); ?></label>
								<input type="radio" class="form-check-input" name="match_status[<?php echo esc_html( $r ); ?>]" id="sharedRubber_<?php echo esc_html( $r ); ?>" value="share"
									<?php
									if ( isset( $rubber->share ) && $rubber->share ) {
										echo esc_html( ' checked' );
									}
									?>
									<?php
									if ( ! $updates_allowed ) {
										echo esc_html( ' disabled' );
									}
									?>
								/>
							</div>
						</div>
					</div>
					<div class="row">
						<?php
						$o = 0;
						foreach ( $opponents as $opponent ) {
							?>
							<div class="col-6 col-sm-4 <?php echo 0 === $o ? '' : 'order-2 order-sm-3'; ?>">
								<div class="match__row is-team-<?php echo esc_attr( $o + 1 ); ?>">
									<div class="match__row-title">
										<div class="match__row-title-header">
											<?php echo esc_html( $match->teams[ $opponent ]->title ); ?>
										</div>
										<?php
										foreach ( $rubber_players as $player_number => $player ) {
											$tabindex = $tabbase + 1;
											?>
											<div class="match__row-title-value">
												<span class="match__row-title-value-content">
													<span class="nav-link__value">
														<select class="form-select" tabindex="<?php echo esc_html( $tabindex ); ?>" name="players[<?php echo esc_attr( $rubber->rubber_number ); ?>][<?php echo esc_attr( $opponent ); ?>][<?php echo esc_attr( $player_number ); ?>]" id="players_<?php echo esc_attr( $rubber->rubber_number ); ?>_<?php echo esc_attr( $opponent ); ?>_<?php echo esc_attr( $player_number ); ?>"
															<?php
															if ( ! $updates_allowed ) {
																echo esc_html( ' disabled' );
															}
															?>
														>
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
																<option value="<?php echo esc_attr( $player_option->roster_id ); ?>" <?php selected( $player_option->roster_id, isset( $rubber->players[ $opponent ][ $player_number ]->club_player_id ) ? $rubber->players[ $opponent ][ $player_number ]->club_player_id : null ); ?> <?php echo esc_html( $disabled ); ?>>
																	<?php echo esc_html( $player_option->fullname ); ?>
																</option>
																<?php
															}
															?>
														</select>
													</span>
												</span>
											</div>
											<?php
										}
										?>
									</div>
								</div>
							</div>
							<?php
							++$o;
						}
						?>
						<div class="col-12 col-sm-4 align-self-center order-3 order-sm-2">
							<div class="match__result-wrapper">
								<div class="match__result">
									<?php
									for ( $i = 1; $i <= $match->league->num_sets; $i++ ) {
										if ( ! isset( $rubber->sets[ $i ] ) ) {
											$rubber->sets[ $i ] = array(
												'player1'  => null,
												'player2'  => null,
												'tiebreak' => null,
											);
										}
										$set = $rubber->sets[ $i ];
										if ( $set['player1'] > $set['player2'] ) {
											$winner_set = 'player1';
										} elseif ( $set['player1'] < $set['player2'] ) {
											$winner_set = 'player2';
										} else {
											$winner_set = null;
										}
										$colspan  = ceil( 12 / $match->league->num_sets );
										$tabindex = $tabbase + 10 + ( $i * 10 );
										$set_type = Racketmanager_Util::get_set_type( $match->league->scoring, $match->final_round, $match->league->num_sets, $i, $r, $match->num_rubbers, $match->leg );
										$set_info = Racketmanager_Util::get_set_info( $set_type );
										?>
										<div class="col-<?php echo esc_html( $colspan ); ?>">
											<div class="set-points" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>" data-settype="<?php echo esc_attr( $set_type ); ?>" data-maxwin="<?php echo esc_attr( $set_info->max_win ); ?>" data-maxloss="<?php echo esc_attr( $set_info->max_loss ); ?>" data-minwin="<?php echo esc_attr( $set_info->min_win ); ?>" data-minloss="<?php echo esc_attr( $set_info->min_loss ); ?>">
												<ul class="match-points set-points">
													<?php
													foreach ( $opponent_players as $opponent ) {
														if ( $winner_set === $opponent ) {
															$winner_class = ' match-points__cell-input--won';
														} else {
															$winner_class = '';
														}
														?>
														<li class="match-points__cell">
															<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points match-points__cell-input <?php echo esc_html( $winner_class ); ?>" type="text"
																<?php
																if ( ! $updates_allowed ) {
																	echo esc_html( ' readonly' );
																}
																?>
																size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_<?php echo esc_attr( $opponent ); ?>" name="sets[<?php echo esc_html( $r ); ?>][<?php echo esc_html( $i ); ?>][<?php echo esc_attr( $opponent ); ?>]" value="<?php echo esc_html( $rubber->sets[ $i ][ $opponent ] ); ?>" onblur="Racketmanager.SetCalculator(this)" />
															</li>
														<?php
													}
													?>
												</ul>
												<div id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_tiebreak_wrapper" class="tie-break"
												<?php
												if ( ! isset( $rubber->sets[ $i ]['tiebreak'] ) || '' === $rubber->sets[ $i ]['tiebreak'] ) {
													echo 'style="display:none;"';
												}
												?>
												>
													<?php ++$tabindex; ?>
													<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points match-points__cell-input" type="text"
														<?php
														if ( ! $updates_allowed ) {
															echo esc_html( ' readonly' );
														}
														?>
														size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="sets[<?php echo esc_html( $r ); ?>][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo isset( $rubber->sets[ $i ]['tiebreak'] ) ? esc_html( $rubber->sets[ $i ]['tiebreak'] ) : ''; ?>"  onblur="Racketmanager.SetCalculatorTieBreak(this)"/>
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<!-- retired box -->
					<div class="row text-center">
						<?php
						$o = 0;
						foreach ( $opponents as $opponent ) {
							?>
							<div class="col-4 <?php echo 0 === $o ? '' : 'order-3'; ?>">
								<label for="retired<?php echo esc_attr( ucfirst( $opponent ) ); ?>_<?php echo esc_attr( $r ); ?>"><?php echo esc_attr( ucfirst( $opponent ) . ' ' . __( 'retired', 'racketmanager' ) ); ?></label>
								<input type="radio" class="form-check-input" name="match_status[<?php echo esc_attr( $r ); ?>]" id="retired<?php echo esc_attr( ucfirst( $opponent ) ); ?>_<?php echo esc_attr( $r ); ?>" value="retired_<?php echo esc_attr( $opponent_players[ $o ] ); ?>"
									<?php
									if ( isset( $rubber->retired ) && $opponent === $rubber->retired ) {
										echo esc_html( ' checked' );
									}
									?>
									<?php
									if ( ! $updates_allowed ) {
										echo esc_html( ' disabled' );
									}
									?>
								/>
							</div>
							<?php
							++$o;
						}
						?>
						<div class="col-4 order-2">
							<div class="col-12">
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
			if ( $updates_allowed ) {
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
			<?php
			$tabbase += 100;
			++$r;
	}
	?>
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
												<?php
											}
											?>
											<?php
										}
										?>
									</div>
								</div>
								<?php
								if ( isset( $match->$approval_captain ) && ! empty( $match->comments ) ) {
									?>
									<div class="match-comments">
										<span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->comments[ $opponent ] ); ?></span>
									</div>
									<?php
								} elseif ( 'admin' !== $user_type && ( $opponent === $user_team || 'both' === $user_team ) ) {
									?>
									<div class="match-comments form-floating">
										<textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"></textarea>
										<label for="resultConfirmComments"><?php esc_html_e( 'Challenge comments', 'racketmanager' ); ?></label>
									</div>
									<?php
								}
								?>
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
	if ( $is_edit_mode && 'admin' === $user_type ) {
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
<?php
