<?php
/**
 * Template for round draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

foreach ( $final->matches as $match ) {
	if ( empty( $match->leg ) || '2' === $match->leg ) {
		$winner = null;
		$loser  = null;
		$is_tie = false;
		if ( empty( $match->leg ) ) {
			if ( ! empty( $match->winner_id ) ) {
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
			}
		} elseif ( ! empty( $match->winner_id_tie ) ) {
				$match_complete = true;
			if ( $match->winner_id_tie === $match->teams['home']->id ) {
				$winner = 'home';
				$loser  = 'away';
			} elseif ( $match->winner_id_tie === $match->teams['away']->id ) {
				$winner = 'away';
				$loser  = 'home';
			}
		}
		?>
		<div class="score-row draws-score-row round-<?php echo esc_attr( $f ); ?> carousel-index-<?php echo esc_attr( $f ); ?> <?php echo empty( $last_round ) ? '' : 'last-round'; ?> ">
			<div class="score-row__wrapper" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>" onclick="Racketmanager.viewMatch(event)">
				<?php
				if ( is_numeric( $match->home_team ) && $match->home_team >= 1 && is_numeric( $match->away_team ) && $match->away_team >= 1 ) {
					if ( empty( $tournament ) ) {
						$match_link = $match->link;
					} else {
						$match_link = '/tournament/' . seo_url( $tournament->name ) . '/match/' . $match->id . '/';
					}
					?>
					<a href="<?php echo esc_url( ( $match_link ) ); ?>" class="score-row__anchor" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>">
					</a>
					<?php
				}
				?>
				<div class="score-row__players-wrapper">
					<?php
					foreach ( $match->teams as $team_ref => $team ) {
						if ( $winner === $team_ref ) {
							$winner_class = 'winner';
						} else {
							$winner_class = null;
						}
						switch ( substr( $match->league->event->type, 0, 1 ) ) {
							case 'M':
								$team_name = str_replace( 'Mens ', '', $team->title );
								break;
							case 'W':
								$team_name = str_replace( 'Ladies ', '', $team->title );
								break;
							case 'X':
								$team_name = str_replace( 'Mixed ', '', $team->title );
								break;
							default:
								$team_name = $team->title;
								break;
						}
						?>
						<div class="player-row">
							<div class="player-row__team-wrapper <?php echo esc_html( $winner_class ); ?>">
								<?php
								if ( empty( $team->player ) ) {
									?>
									<div class="player-row__team">
										<?php
										if ( is_numeric( $team->id ) ) {
											if ( -1 !== $team->id ) {
												?>
												<a class="" href="/<?php echo esc_attr( seo_url( $match->league->event->competition->type ) ); ?>s/<?php echo esc_attr( seo_url( $match->league->event->competition->name ) ); ?>/<?php echo esc_attr( seo_url( $match->league->title ) ); ?>/<?php echo esc_attr( seo_url( $team->title ) ); ?>">
												<?php
											}
											?>
												<p>
													<?php echo esc_html( $team_name ); ?>
													<?php
													if ( isset( $team->rank ) && intval( $team->rank ) <= intval( $match->league->championship->num_seeds ) ) {
														?>
														<span class="seeding"><?php echo esc_html( $team->rank ); ?></span>
														<?php
													}
													?>
												</p>
											<?php
											if ( -1 !== $team->id ) {
												?>
												</a>
												<?php
											}
										} else {
											?>
											<p>&nbsp;</p>
											<?php
										}
										?>
									</div>
									<?php
								} else {
									foreach ( $team->player as $player ) {
										?>
										<div class="player-row__team <?php echo esc_attr( $player_class ); ?>">
											<?php
											if ( ! empty( $tournament ) ) {
												?>
												<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( trim( $player ) ) ); ?>">
												<?php
											}
											?>
											<p>
												<?php echo esc_html( trim( $player ) ); ?>
												<?php
												if ( isset( $team->rank ) && intval( $team->rank ) <= intval( $match->league->championship->num_seeds ) ) {
													?>
													<span class="seeding"><?php echo esc_html( $team->rank ); ?></span>
													<?php
												}
												?>
											</p>
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
									?>
									<?php
								}
								?>
							</div>
							<div class="player-row__score-wrapper">
								<div class="player-row__score-badge">
									<?php
									if ( $winner ) {
										if ( $winner === $team_ref ) {
											?>
										<span class="match__status winner">W</span>
											<?php
										}
									} elseif ( $is_tie ) {
										?>
										<span class="match__status tie">T</span>
										<?php
									} elseif ( empty( $match->leg ) && ! empty( $match->host ) && $team_ref === $match->host ) {
										?>
										<span><?php esc_html_e( 'H', 'racketmanager' ); ?></span>
										<?php
									}
									?>
								</div>
								<div class="d-none d-lg-flex player-row__score-game-wrapper">
									<?php
									if ( empty( $match->leg ) && empty( $match->sets ) ) {
										if ( 'home' === $team_ref ) {
											$points = $match->home_points;
										} else {
											$points = $match->away_points;
										}
										if ( ! empty( $winner ) ) {
											?>
											<div class="player-row__score-game <?php echo esc_html( $winner_class ); ?>">
												<?php echo esc_html( sprintf( '%g', $points ) ); ?>
											</div>
											<?php
										}
										?>
										<?php
									} elseif ( ! empty( $match->leg ) ) {
										if ( 'home' === $team_ref ) {
											$points = $match->home_points_tie;
										} else {
											$points = $match->away_points_tie;
										}
										if ( ! empty( $winner ) ) {
											?>
											<div class="player-row__score-game <?php echo esc_html( $winner_class ); ?>">
												<?php echo esc_html( sprintf( '%g', $points ) ); ?>
											</div>
											<?php
										}
										?>
										<?php
									} elseif ( ! empty( $match->rubbers ) ) {
										foreach ( $match->rubbers as $rubber ) {
											if ( 'home' === $team_ref ) {
												$set_ref     = 'player1';
												$set_ref_alt = 'player2';
											} else {
												$set_ref     = 'player2';
												$set_ref_alt = 'player1';
											}
											$sets = isset( $rubber->custom['sets'] ) ? $rubber->custom['sets'] : array();
											foreach ( $sets as $set ) {
												if ( isset( $set[ $set_ref ] ) && '' !== $set[ $set_ref ] ) {
													if ( $set[ $set_ref ] > $set [ $set_ref_alt ] ) {
														$winner_class_set = 'winner';
													} else {
														$winner_class_set = null;
													}
													?>
													<div class="player-row__score-game  <?php echo esc_html( $winner_class_set ); ?>">
														<?php echo esc_html( $set[ $set_ref ] ); ?>
														<?php
														if ( isset( $set['tiebreak'] ) ) {
															?>
															<span class="player-row__tie-break"></span>
															<?php
														}
														?>
													</div>
													<?php
												}
											}
										}
									} elseif ( ! empty( $match->sets ) ) {
										if ( 'home' === $team_ref ) {
											$set_ref     = 'player1';
											$set_ref_alt = 'player2';
										} else {
											$set_ref     = 'player2';
											$set_ref_alt = 'player1';
										}
										$sets = $match->sets;
										foreach ( $sets as $set ) {
											if ( isset( $set[ $set_ref ] ) && '' !== $set[ $set_ref ] ) {
												if ( $set[ $set_ref ] > $set [ $set_ref_alt ] ) {
													$winner_class_set = 'winner';
												} else {
													$winner_class_set = null;
												}
												?>
												<div class="player-row__score-game  <?php echo esc_html( $winner_class_set ); ?>">
													<?php echo esc_html( $set[ $set_ref ] ); ?>
													<?php
													if ( isset( $set['tiebreak'] ) && ! empty( $winner_class_set ) ) {
														?>
														<span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
														<?php
													}
													?>
												</div>
												<?php
											}
										}
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
		<?php
	}
}
