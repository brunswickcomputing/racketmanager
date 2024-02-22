<?php
/**
 * Template page for the tennis match scores
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
if ( 'tournament' === $league->event->competition->type ) {
	?>
	<div class="tournament-matches">
		<?php
		foreach ( $matches as $no => $match ) {
			?>
			<?php require RACKETMANAGER_PATH . 'templates/tournament/match.php'; ?>
			<?php
		}
		?>
	</div>
	<?php
	return false;
}
?>
<div class="table-responsive">
	<table class='table align-middle' aria-describedby='<?php esc_html_e( 'Tennis matches', 'racketmanager' ); ?>' title='<?php esc_html_e( 'Match Plan', 'racketmanager' ); ?> <?php the_league_title(); ?>'>
		<thead class="table-dark">
			<tr>
				<?php
				if ( 'championship' === $league->mode ) {
					?>
					<th scope="col">
						<?php esc_html_e( '#', 'racketmanager' ); ?>
					</th>
					<?php
				}
				?>
				<th scope="col" colspan="2" class='match'>
					<?php esc_html_e( 'Match', 'racketmanager' ); ?>
				</th>
				<th scope="col" class='match-score'>
					<?php esc_html_e( 'Score', 'racketmanager' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php $matchday = isset( $_GET['match_day'] ) ? sanitize_text_field( wp_unslash( $_GET['match_day'] ) ) : $league->match_day; //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php
			foreach ( $matches as $no => $match ) {
				if ( empty( $match->leg ) || '1' === $match->leg || ( '2' === $match->leg && ( '-1' !== $match->home_team && '-1' !== $match->away_team ) ) ) {
					if ( isset( $match->teams['home'] ) && isset( $match->teams['away'] ) ) {
						$match_link            = $match->link;
						$user_can_update_array = $racketmanager->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
						$user_can_update       = $user_can_update_array[0];
					} else {
						$user_can_update = false;
					}
					if ( 'championship' !== $league->mode && $matchday !== $match->match_day ) {
						?>
						<tr class='match-day-row'>
							<th scope="col" colspan="3" class='match']>
								Week <?php echo esc_html( $match->match_day ); ?>
							</th>
						</tr>
						<?php
						$matchday = $match->match_day;
					}
					?>
					<tr class='match-row rubber-view <?php echo esc_html( $match->class ); ?>'>
						<?php
						if ( 'championship' === $league->mode ) {
							?>
							<td>
								<?php echo esc_html( $no ); ?>
							</td>
							<?php
						}
						if ( isset( $match->num_rubbers ) && $match->num_rubbers > 0 ) {
							if ( $match->winner_id ) {
								if ( -1 === $match->home_team || -1 === $match->away_team ) {
									?>
									<td class='angledir'></td>
									<?php
								} else {
									?>
									<td class='angledir' title="<?php esc_html_e( 'View rubbers', 'racketmanager' ); ?>">
										<i class="racketmanager-svg-icon angledir">
											<?php racketmanager_the_svg( 'icon-chevron-right' ); ?>
										</i>
									</td>
									<?php
								}
							} else {
								?>
								<td class='angledir'>
									<a href="" class='btn match__btn' type="<?php echo esc_html( $match->league->event->competition->entry_type ); ?>" id="<?php echo esc_html( $match->id ); ?>" onclick="Racketmanager.printScoreCard(event, this)" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Print matchcard', 'racketmanager' ); ?>">
										<i class="racketmanager-svg-icon">
											<?php racketmanager_the_svg( 'icon-printer' ); ?>
										</i>
									</a>
									<?php
									if ( $user_can_update && ( ! isset( $match->confirmed ) || 'P' === $match->confirmed ) && is_numeric( $match->home_team ) && is_numeric( $match->away_team ) ) {
										?>
										<a href="<?php echo esc_html( $match_link ); ?>" class="btn match__btn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Enter match result', 'racketmanager' ); ?>">
											<i class="racketmanager-svg-icon">
												<?php racketmanager_the_svg( 'icon-pencil' ); ?>
											</i>
										</a>
										<?php
									}
									?>
									<span id="feedback-<?php echo esc_html( $match->id ); ?>"></span>
								</td>
								<?php
							}
						} elseif ( $match->winner_id ) {
							?>
							<td class='angledir'></td>
							<?php
						} elseif ( ! strpos( $match->home_team, '_' ) && ! strpos( $match->away_team, '_' ) ) {
							?>
							<td class='angledir'>
								<a href="#" class='' type="<?php echo esc_html( $match->$league->event->competition->entry_type ); ?>" id="<?php echo esc_html( $match->id ); ?>" onclick="Racketmanager.printScoreCard(event, this)" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Print matchcard', 'racketmanager' ); ?>">
									<i class="racketmanager-svg-icon">
										<?php racketmanager_the_svg( 'icon-printer' ); ?>
									</i>
								</a>
								<?php
								if ( $user_can_update ) {
									?>
									<a href="<?php echo esc_html( $match_link ); ?>" class="btn btn__match" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Enter match result', 'racketmanager' ); ?>">
										<i class="racketmanager-svg-icon">
											<?php racketmanager_the_svg( 'icon-pencil' ); ?>
										</i>
									</a>
									<?php
								}
								?>
							</td>
							<?php
						} else {
							?>
							<td class='angledir'></td>
							<?php
						}
						?>
						<td class='match'>
							<?php
							$home_class = '';
							$away_class = '';
							$home_tip   = '';
							$away_tip   = '';
							if ( $match->winner_id === $match->teams['home']->id ) {
								$home_class = 'winner';
								$home_tip   = 'Match winner';
							} elseif ( $match->winner_id === $match->teams['away']->id ) {
								$away_class = 'winner';
								$away_tip   = 'Match winner';
							} elseif ( isset( $match->host ) ) {
								if ( 'home' === $match->host ) {
									$home_class = 'host';
									$home_tip   = 'Home team';
								} elseif ( 'away' === $match->host ) {
									$away_class = 'host';
									$away_tip   = 'Home team';
								}
							}
							?>
							<?php the_match_date(); ?> <?php the_match_time(); ?> <?php the_match_location(); ?><br />
							<?php
							if ( isset( $match->teams['home']->title ) && isset( $match->teams['away']->title ) ) {
								if ( is_numeric( $match->home_team ) && '-1' !== $match->home_team && is_numeric( $match->away_team ) && '-1' !== $match->away_team ) {
									?>
									<a href="<?php echo esc_html( $match_link ); ?>">
										<span title="<?php echo esc_html( $home_tip ); ?>" class="<?php echo esc_html( $home_class ); ?>"><?php echo esc_html( $match->teams['home']->title ); ?></span> - <span title="<?php echo esc_html( $away_tip ); ?>" class="<?php echo esc_html( $away_class ); ?>"><?php echo esc_html( $match->teams['away']->title ); ?></span>
									</a>
									<?php
								} else {
									the_match_title();
								}
							} else {
								the_match_title();
							}
							the_match_report();
							?>
							<?php
							if ( ! empty( $match->leg ) && '-1' !== $match->home_team && '-1' !== $match->away_team ) {
								?>
								<br><?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->leg ); ?>
								<?php
							}
							?>
						</td>
						<td class='match-score'>
							<?php
							if ( isset( $match->home_points ) ) {
								the_match_score();
							} else {
								echo '';
							}
							?>
						</td>
					</tr>
					<?php
					if ( isset( $match->num_rubbers ) && $match->num_rubbers > 0 && isset( $match->rubbers ) && ( $match->winner_id ) ) {
						?>
						<tr class='match-rubber-row <?php echo esc_html( $match->class ); ?>'>
							<td colspan="
									<?php
									if ( 'championship' === $league->mode ) {
										echo '4';
									} else {
										echo '3';
									}
									?>
							">
								<table aria-labelledby='<?php esc_html_e( 'Tennis match rubbers', 'racketmanager' ); ?>' id='rubbers_<?php echo esc_html( $match->id ); ?>' >
									<tbody>
										<?php
										foreach ( $match->rubbers as $rubber ) {
											if ( 0 !== $rubber->home_player_1 && 0 !== $rubber->away_player_1 ) {
												?>
												<tr class='rubber-row <?php echo esc_html( $match->class ); ?>'>
													<th>
														<?php echo esc_html( $rubber->rubber_number ); ?>
													</th>
													<td class="playername
														<?php
														if ( $rubber->winner_id === $match->teams['home']->id ) {
															echo ' winner';
														}
														?>
													" >
														<?php echo esc_html( $rubber->home_player_1_name ); ?>
													</td>
													<td class="playername
														<?php
														if ( $rubber->winner_id === $match->teams['home']->id ) {
															echo ' winner';
														}
														?>
													">
														<?php echo esc_html( $rubber->home_player_2_name ); ?>
													</td>
													<?php
													if ( isset( $rubber->sets ) ) {
														foreach ( $rubber->sets as $set ) {
															?>
															<?php if ( ( '' !== $set['player1'] ) && ( '' !== $set['player2'] ) ) { ?>
																<td class='match-score'>
																	<?php echo esc_html( $set['player1'] ); ?> - <?php echo esc_html( $set['player2'] ); ?>
																</td>
															<?php } else { ?>
																<td class='match-score'></td>
															<?php } ?>
														<?php } ?>
													<?php } ?>
													<td class="playername
														<?php
														if ( $rubber->winner_id === $match->teams['away']->id ) {
															echo ' winner';
														}
														?>
													">
														<?php echo esc_html( $rubber->away_player_1_name ); ?>
													</td>
													<td class="playername
														<?php
														if ( $rubber->winner_id === $match->teams['away']->id ) {
															echo ' winner';
														}
														?>
													">
														<?php echo esc_html( $rubber->away_player_2_name ); ?>
													</td>
												</tr>
												<?php
											}
										}
										?>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
					}
					?>
					<?php
				}
			}
			?>
		</tbody>
	</table>
</div>

<?php the_matches_pagination(); ?>
