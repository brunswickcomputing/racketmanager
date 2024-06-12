<?php
/**
 * Template for match for teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$user_can_update = $user_can_update_array[0];
$user_type       = $user_can_update_array[1];
$user_team       = $user_can_update_array[2];
$user_message    = $user_can_update_array[3];
?>
	<div id="match-header" class="team-match-header module module--dark module--card">
		<?php echo $racketmanager->show_match_header( $match, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="page-content row">
		<div class="page-content__sidebar col-12 col-lg-4">
			<div class="row">
				<div class="col-12 col-sm-6 col-lg-12">
					<div class="module module--card">
						<div class="module__banner">
							<h4 class="module__title">
								<?php esc_html_e( 'How does it work?', 'racketmanager' ); ?>
							</h4>
						</div>
						<div class="module__content">
							<div class="module-container">
								<h5 class="subheading">
									<?php esc_html_e( 'Results', 'racketmanager' ); ?>
								</h5>
								<ul class="list list--naked small-txt">
									<li class="list__item"><?php esc_html_e( "Only valid results are allowed. In the case of a non-played match, you can edit the status via the 'match status' button.", 'racketmanager' ); ?></li>
									<li class="list__item"><?php esc_html_e( 'You can also mark a rubber as walkover, retired or not played.', 'racketmanager' ); ?></li>
								</ul>
							</div>
							<div class="module-container">
								<h5 class="subheading">
									<?php esc_html_e( 'Players', 'racketmanager' ); ?>
								</h5>
								<ul class="list list--naked small-txt">
									<li class="list__item"><?php esc_html_e( 'You can add players to a match by choosing from the select list.', 'racketmanager' ); ?></li>
									<li class="list__item"><?php esc_html_e( "When the player is not yet in the list, you can choose the '* Unregistered player' and add the name of the missing player in the comments.", 'racketmanager' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__main col-12 col-lg-8">
			<div class="module module--card">
				<div class="module__banner">
					<h4 class="module__title">
						<?php esc_html_e( 'Rubber results', 'racketmanager' ); ?>
					</h4>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div id="viewMatchRubbers">
							<div id="splash" class="d-none">
								<div class="d-flex justify-content-center">
									<div class="spinner-border" role="status">
									<span class="visually-hidden">Loading...</span>
									</div>
								</div>
							</div>
							<div id="showMatchRubbers">
								<?php
								$is_edit_mode    = true;
								$user_can_update = $user_can_update_array[0];
								$user_type       = $user_can_update_array[1];
								$user_team       = $user_can_update_array[2];
								$user_message    = $user_can_update_array[3];
								if ( ! $user_can_update ) {
									$is_edit_mode = false;
								}
								$updates_allowed = true;
								if ( 'P' === $match->confirmed && 'admin' !== $user_type ) {
									$updates_allowed = false;
								}
								$opponents        = array( 'home', 'away' );
								$opponent_players = array( 'player1', 'player2' );
								$winner_set       = null;
								if ( ! empty( $home_club_player['m'] ) ) {
									$club_players['home']['m'] = $home_club_player['m'];
								} else {
									$club_players['home']['m'] = array();
								}
								if ( ! empty( $home_club_player['f'] ) ) {
									$club_players['home']['f'] = $home_club_player['f'];
								} else {
									$club_players['home']['f'] = array();
								}
								if ( ! empty( $away_club_player['m'] ) ) {
									$club_players['away']['m'] = $away_club_player['m'];
								} else {
									$club_players['away']['m'] = array();
								}
								if ( ! empty( $away_club_player['f'] ) ) {
									$club_players['away']['f'] = $away_club_player['f'];
								} else {
									$club_players['away']['f'] = array();
								}
								$rubbers             = $match->get_rubbers();
								$team                = null;
								$team_status         = null;
								$match_complete      = false;
								$match_approval_mode = false;
								$match_editable      = false;
								if ( $user_can_update && $is_edit_mode ) {
									if ( empty( $match->confirmed ) || 'D' === $match->confirmed || 'admin' === $user_type ) {
										$match_editable = 'is-editable';
									} elseif ( 'P' === $match->confirmed ) {
										$match_approval_mode = true;
									}
								}
								$match_status = null;
								if ( $match->is_walkover ) {
									if ( 'home' === $match->walkover ) {
										$match_status = 'walkover_player1';
									} else {
										$match_status = 'walkover_player2';
									}
								} elseif ( $match->is_shared ) {
									$match_status = 'share';
								} elseif ( $match->is_retired ) {
									if ( 'home' === $match->retired ) {
										$match_status = 'retired_player1';
									} else {
										$match_status = 'retired_player2';
									}
								}
								?>
								<div id="matchrubbers">
									<form id="match-rubbers" class="team-match-result" action="#" method="post" onsubmit="return checkSelect(this)">
										<?php wp_nonce_field( 'rubbers-match', 'racketmanager_nonce' ); ?>
										<input type="hidden" name="updated_form" value="new" />
										<input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_html( $match->id ); ?>" />
										<input type="hidden" name="new_match_status" id="match_status" value="<?php echo esc_html( $match_status ); ?>" />
										<div class="alert_rm" id="matchAlert" style="display:none;">
											<div class="alert__body">
												<div class="alert__body-inner" id="alertResponse">
												</div>
											</div>
										</div>
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
												$page_referrer = wp_get_referer();
												if ( ! $page_referrer ) {
													$page_referrer = $match->league->event->competition->type . '/' . seo_url( $match->league->title ) . '/' . $match->season . '/';
													if ( ! empty( $tournament ) ) {
														$page_referrer = $tournament->link . 'matches/';
													}
												}
												?>
												<div class="row mb-3">
													<div class="col-12 match__buttons">
														<input type="hidden" name="updateRubber" id="updateRubber" value="<?php echo esc_html( esc_html( $update_rubber ) ); ?>" />
														<a tabindex="999" class="btn btn-plain" type="button" id="cancelResults" href="<?php echo esc_html( $page_referrer ); ?>"><?php echo esc_html_e( 'Cancel', 'racketmanager' ); ?></a>
														<button tabindex="500" class="btn btn-primary" type="button" id="updateRubberResults" onclick="Racketmanager.updateResults(this)"><?php echo esc_html( $action_text ); ?></button>
													</div>
												</div>
												<?php
											} else {
												?>
												<div class="mb-3">
													<div class="alert_rm alert--warning">
														<div class="alert__body">
															<div class="alert__body-inner">
																<span><?php esc_html_e( 'Team result already entered', 'racketmanager' ); ?></span>
															</div>
														</div>
													</div>
												</div>
												<?php
											}
										}
										?>
										<ul class="match-group">
											<?php
											$tabbase = 0;
											foreach ( $rubbers as $rubber ) {
												$winner = null;
												$loser  = null;
												$is_tie = false;
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
												} else {
													$winner = null;
												}
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
															if ( $match_editable && is_user_logged_in() ) {
																?>
																<div class="match__header-aside text-uppercase">
																	<div class="match__header-aside-block">
																		<a href="" class="nav__link" onclick="Racketmanager.scoreStatusModal(event, '<?php echo esc_attr( $rubber->id ); ?>', '<?php echo esc_attr( $rubber->rubber_number ); ?>')">
																			<svg width="16" height="16" class="icon-plus nav-link__prefix">
																				<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#plus-lg' ); ?>"></use>
																			</svg>
																			<span class="nav-link__value"><?php esc_html_e( 'Score status', 'racketmanager' ); ?></span>
																		</a>
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
																				$tabindex   = $tabbase + 1;
																				$player_ref = $opponent . '_player' . $player_number;
																				?>
																				<div class="match__row-title-value">
																					<span class="match__row-title-value-content">
																						<span class="nav-link__value <?php echo esc_html( $winner_class ); ?>">
																							<?php
																							if ( $match_editable ) {
																								?>
																								<select class="form-select" tabindex="<?php echo esc_html( $tabindex ); ?>" name="players[<?php echo esc_attr( $rubber->rubber_number ); ?>][<?php echo esc_attr( $opponent ); ?>][<?php echo esc_attr( $player_number ); ?>]" id="players_<?php echo esc_attr( $rubber->rubber_number ); ?>_<?php echo esc_attr( $opponent ); ?>_<?php echo esc_attr( $player_number ); ?>">
																									<option value="0">&nbsp;</option>
																									<?php
																									foreach ( $club_players[ $opponent ][ $player['gender'] ] as $player_option ) {
																										if ( ! empty( $player_option->removed_date ) ) {
																											$disabled = 'disabled';
																										} else {
																											$disabled = '';
																										}
																										$player_display = $player_option->fullname;
																										if ( ! empty( $player_option->btm ) ) {
																											$player_display .= ' - ' . $player_option->btm;
																										}
																										?>
																										<option value="<?php echo esc_attr( $player_option->roster_id ); ?>" <?php selected( $player_option->roster_id, isset( $rubber->players[ $opponent ][ $player_number ]->club_player_id ) ? $rubber->players[ $opponent ][ $player_number ]->club_player_id : null ); ?> <?php echo esc_html( $disabled ); ?>>
																											<?php echo esc_html( $player_display ); ?>
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
																		if ( $is_winner ) {
																			$match_status_class = 'winner';
																			$match_status_text  = 'W';
																		} elseif ( $is_loser ) {
																			$match_status_class = 'loser';
																			$match_status_text  = 'L';
																			if ( $rubber->is_walkover ) {
																				$match_message_class = 'match-warning';
																				$match_message_text  = __( 'Walkover', 'racketmanager' );
																				if ( empty( $match_status_class ) ) {
																					$match_status_class = 'd-none';
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
																				$match_message_text  = __( 'Not played', 'racketmanager' );
																			}
																		}
																		?>
																		<span class="match__message <?php echo esc_attr( $match_message_class ); ?> <?php echo empty( $match_message_text ) ? 'd-none' : ''; ?>" id="match-message-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
																				<?php echo esc_html( $match_message_text ); ?>
																		</span>
																		<span class="match__status <?php echo esc_attr( $match_status_class ); ?>" id="match-status-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $match->teams[ $opponent ]->id ); ?>">
																				<?php echo esc_html( $match_status_text ); ?>
																		</span>
																	</div>
																	<?php
																}
																?>
																<?php
																$rubber_status = null;
																if ( $rubber->is_walkover ) {
																	if ( 'home' === $rubber->walkover ) {
																		$rubber_status = 'walkover_player1';
																	} else {
																		$rubber_status = 'walkover_player2';
																	}
																} elseif ( $rubber->is_shared ) {
																	$rubber_status = 'share';
																} elseif ( $rubber->is_retired ) {
																	if ( 'home' === $rubber->retired ) {
																		$rubber_status = 'retired_player1';
																	} else {
																		$rubber_status = 'retired_player2';
																	}
																}
																?>
																<input type="text" class="d-none" id="match_status_<?php echo esc_attr( $rubber->rubber_number ); ?>" name="match_status[<?php echo esc_attr( $rubber->rubber_number ); ?>]" value="<?php echo esc_html( $rubber_status ); ?>" />
															</div>
															<div class="match__result">
																<?php
																$sets = isset( $rubber->sets ) ? $rubber->sets : array();
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
																	$tabindex = $tabbase + 10 + ( $i * 10 );
																	$set_type = Racketmanager_Util::get_set_type( $match->league->scoring, $match->final_round, $match->league->num_sets, $i, $r, $match->num_rubbers, $match->leg );
																	$set_info = Racketmanager_Util::get_set_info( $set_type );
																	?>
																	<ul class="match-points set-points" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>" data-settype="<?php echo esc_attr( $set_type ); ?>" data-maxwin="<?php echo esc_attr( $set_info->max_win ); ?>" data-maxloss="<?php echo esc_attr( $set_info->max_loss ); ?>" data-minwin="<?php echo esc_attr( $set_info->min_win ); ?>" data-minloss="<?php echo esc_attr( $set_info->min_loss ); ?>" data-tiebreakset="<?php echo esc_attr( $set_info->tiebreak_set ); ?>">
																		<?php
																		foreach ( $opponent_players as $opponent ) {
																			if ( $winner_set === $opponent ) {
																				$winner_class = ' match-points__cell-input--won';
																			} else {
																				$winner_class = '';
																			}
																			?>
																			<li class="match-points__cell">
																				<?php
																				if ( $match_editable ) {
																					?>
																					<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points match-points__cell-input <?php echo esc_html( $winner_class ); ?>" type="text"
																						<?php
																						if ( ! $updates_allowed ) {
																							echo esc_html( ' readonly' );
																						}
																						?>
																						size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_<?php echo esc_attr( $opponent ); ?>" name="sets[<?php echo esc_html( $r ); ?>][<?php echo esc_html( $i ); ?>][<?php echo esc_attr( $opponent ); ?>]" value="<?php echo esc_html( $rubber->sets[ $i ][ $opponent ] ); ?>" onblur="Racketmanager.SetCalculator(this)" />
																					<?php
																				} else {
																					echo esc_html( $rubber->sets[ $i ][ $opponent ] );
																					if ( isset( $set['tiebreak'] ) && ! empty( $winner_class ) ) {
																						?>
																						<span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
																						<?php
																					}
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
																		<div id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_tiebreak_wrapper" class="match-points set-points tie-break"
																			<?php
																			if ( ! isset( $rubber->sets[ $i ]['tiebreak'] ) || '' === $rubber->sets[ $i ]['tiebreak'] ) {
																				echo 'style="display:none;"';
																			}
																			?>
																			>
																			<?php ++$tabindex; ?>
																			<input tabindex="<?php echo esc_html( $tabindex ); ?>" class="points match-points__cell-input" type="number" min="0"
																				<?php
																				if ( ! $updates_allowed ) {
																					echo esc_html( ' readonly' );
																				}
																				?>
																				size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="sets[<?php echo esc_html( $r ); ?>][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo isset( $rubber->sets[ $i ]['tiebreak'] ) ? esc_html( $rubber->sets[ $i ]['tiebreak'] ) : ''; ?>"  onblur="Racketmanager.SetCalculatorTieBreak(this)"/>
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
												</li>
												<?php
												$tabbase += 100;
												++$r;
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
									</form>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal" id="scoreStatusModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header modal__header">
					<h4 class="modal-title"><?php esc_html_e( 'Score status', 'racketmanager' ); ?></h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="container-fluid">
						<div class="row">
							<div class="col-sm-6">
								<select class="form-select">
									<option value="" disabled selected><?php esc_html_e( 'Status', 'racketmanager' ); ?></option>
									<option value="walkover_home"><?php esc_html_e( 'Walkover - no home team', 'racketmanager' ); ?></option>
									<option value="walkover_away"><?php esc_html_e( 'Walkover - no away team', 'racketmanager' ); ?></option>
									<option value="retired_home"><?php esc_html_e( 'Retired - home team player', 'racketmanager' ); ?></option>
									<option value="retired_away"><?php esc_html_e( 'Retired - team player', 'racketmanager' ); ?></option>
									<option value="shared"><?php esc_html_e( 'Shared - not played', 'racketmanager' ); ?></option>
								</select>
							</div>
							<div class="col-sm-6">
								<ul class="list list--naked">
									<li class="list__item">
										<dt class=""><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></dt>
										<dd class=""><?php esc_html_e( 'The match has not started and at least one team cannot play.', 'racketmanager' ); ?></dd>
									</li>
									<li class="list__item">
										<dt class=""><?php esc_html_e( 'Retired', 'racketmanager' ); ?></dt>
										<dd class=""><?php esc_html_e( 'A player retired from a match in progress.', 'racketmanager' ); ?></dd>
									</li>
									<li class="list__item">
										<dt class=""><?php esc_html_e( 'Shared', 'racketmanager' ); ?></dt>
										<dd class=""><?php esc_html_e( 'Not played (and will not be played)', 'racketmanager' ); ?></dd>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary">Save changes</button>
				</div>
			</div>
		</div>
	</div>
