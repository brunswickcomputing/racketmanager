<?php
/**
 * Template for individual tournament player
 *
 * @package Racketmanager/Templates/Tournament
 */

namespace Racketmanager;

$player = $tournament_player;
require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
?>
	<div class="module module--card">
		<div class="module__content">
			<div class="module-container">
				<div class="module">
					<dl class="list list--flex">
						<?php
						if ( is_user_logged_in() ) {
							?>
							<?php
							if ( ! empty( $tournament_player->email ) ) {
								?>
								<div class="list__item">
									<dt class="list__label"><?php esc_html_e( 'Email', 'racketmanager' ); ?></dt>
									<dd class="list__value">
										<?php echo esc_html( $tournament_player->email ); ?>
									</dd>
								</div>
								<?php
							}
							?>
							<?php
							if ( ! empty( $tournament_player->contactno ) ) {
								?>
								<div class="list__item">
									<dt class="list__label"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></dt>
									<dd class="list__value">
										<?php echo esc_html( $tournament_player->contactno ); ?>
									</dd>
								</div>
								<?php
							}
						} else {
							?>
							<dd>
								<?php esc_html_e( 'You need to ', 'racketmanager' ); ?><a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'login', 'racketmanager' ); ?></a> <?php esc_html_e( 'to see contact details', 'racketmanager' ); ?>
							</dd>
							<?php
						}
						?>
					</dl>
				</div>
			</div>
		</div>
	</div>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-7">
			<div class="module module--card">
				<div class="module__banner">
					<h4 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h4>
				</div>
				<div class="module__content">
					<div class="module-container">
						<?php
						$matches = $tournament->matches;
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
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-5">
			<div class="module module--card">
				<div class="module__banner">
					<h4 class="module__title"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></h4>
				</div>
				<div class="module__content">
					<div class="module-container">
						<?php
						if ( ! empty( $tournament_player->teams ) ) {
							?>
							<div class="col-12">
								<div class="row mb-2 row-header">
									<div class="col-1"></div>
									<div class="col-6">
										<?php esc_html_e( 'Event', 'racketmanager' ); ?>
									</div>
									<div class="col-5">
										<?php esc_html_e( 'Partner', 'racketmanager' ); ?>
									</div>
								</div>
								<?php
								foreach ( $tournament_player->teams as $entry ) {
									?>
									<div class="row mb-2 row-list">
										<div class="col-1" name="<?php esc_html_e( 'Favourite', 'racketmanager' ); ?>">
											<?php
											$hidden         = true;
											$event          = $entry;
											$favourite_type = 'competition';
											$favourite_id   = $event->id;
											require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
											?>
										</div>
										<div class="col-6" name="<?php esc_html_e( 'Event', 'racketmanager' ); ?>">
											<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/event/<?php echo esc_html( seo_url( $entry->event ) ); ?>">
												<?php echo esc_html( $entry->event ); ?>
											</a>
										</div>
										<div class="col-5" name="<?php esc_html_e( 'Partner', 'racketmanager' ); ?>">
											<?php
											if ( ! empty( $entry->partner ) ) {
												?>
												<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( $entry->partner ) ); ?>">
													<?php echo esc_html( wp_unslash( $entry->partner ) ); ?>
												</a>
												<?php
											}
											?>
										</div>
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
			<?php
			if ( ! empty( $player->statistics ) ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h4 class="module__title"><?php esc_html_e( 'Statistics', 'racketmanager' ); ?></h4>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="table-responsive">
								<table id="tournament-player-stats" class="table table-borderless player-stats" aria-describedby="<?php esc_html_e( 'Player Tournament Statistics', 'racketmanager' ); ?>">
									<thead>
										<tr>
											<th scope="col">
											</th>
											<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Played', 'racketmanager' ); ?>">
												<?php esc_html_e( 'P', 'racketmanager' ); ?>
											</th>
											<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Won', 'racketmanager' ); ?>">
												<?php esc_html_e( 'W', 'racketmanager' ); ?>
											</th>
											<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Sets', 'racketmanager' ); ?>">
												<?php esc_html_e( 'S', 'racketmanager' ); ?>
											</th>
											<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Games', 'racketmanager' ); ?>">
												<?php esc_html_e( 'G', 'racketmanager' ); ?>
											</th>
											<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Walkover', 'racketmanager' ); ?>">
												<?php esc_html_e( 'W/O', 'racketmanager' ); ?>
											</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$stat_rows = array(
											__( 'Singles', 'racketmanager' ) => 's',
											__( 'Doubles', 'racketmanager' ) => 'd',
											__( 'Totals', 'racketmanager' ) => 't',
										);
										foreach ( $stat_rows as $stat_title => $stat_type ) {
											if ( 't' === $stat_type ) {
												$total_win  = isset( $player->statistics['played']['winner'] ) ? array_sum( $player->statistics['played']['winner'] ) : 0;
												$total_loss = isset( $player->statistics['played']['loser'] ) ? array_sum( $player->statistics['played']['loser'] ) : 0;
												$played     = $total_win + $total_loss;
											} elseif ( isset( $player->statistics['played']['winner'][ $stat_type ] ) ) {
												$played_won  = isset( $player->statistics['played']['winner'][ $stat_type ] ) ? $player->statistics['played']['winner'][ $stat_type ] : 0;
												$played_lost = isset( $player->statistics['played']['loser'][ $stat_type ] ) ? $player->statistics['played']['loser'][ $stat_type ] : 0;
												$played      = $played_won + $played_lost;
											} else {
												$played = '';
											}
											if ( $played ) {
												?>
												<tr>
													<th scope="row">
													<?php echo esc_html( $stat_title ); ?>
													</th>
													<td>
														<?php
														echo esc_html( $played );
														?>
													</td>
													<td>
														<?php
														if ( 't' === $stat_type ) {
															$matches_won  = $total_win;
															$matches_lost = $total_loss;
														} else {
															$matches_won  = $played_won;
															$matches_lost = $played_lost;
														}
														$win_pct = ceil( ( $matches_won / $played ) * 100 );
														echo esc_html( $matches_won ) . '-' . esc_html( $matches_lost );
														?>
														<div class="progress">
															<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ); ?>%"></div>
														</div>
													</td>
													<td>
														<?php
														if ( 't' === $stat_type ) {
															$sets_won  = isset( $player->statistics['sets']['winner'] ) ? array_sum( $player->statistics['sets']['winner'] ) : 0;
															$sets_lost = isset( $player->statistics['sets']['loser'] ) ? array_sum( $player->statistics['sets']['loser'] ) : 0;
														} else {
															$sets_won  = isset( $player->statistics['sets']['winner'][ $stat_type ] ) ? $player->statistics['sets']['winner'][ $stat_type ] : 0;
															$sets_lost = isset( $player->statistics['sets']['loser'][ $stat_type ] ) ? $player->statistics['sets']['loser'][ $stat_type ] : 0;
														}
														if ( $sets_won || $sets_lost ) {
															$win_pct = ceil( ( $sets_won / ( $sets_won + $sets_lost ) ) * 100 );
														} else {
															$win_pct = 0;
														}
														echo esc_html( $sets_won ) . '-' . esc_html( $sets_lost );
														?>
														<div class="progress">
															<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ); ?>%"></div>
														</div>
													</td>
													<td>
														<?php
														if ( 't' === $stat_type ) {
															$games_won  = isset( $player->statistics['games']['winner'] ) ? array_sum( $player->statistics['games']['winner'] ) : 0;
															$games_lost = isset( $player->statistics['games']['loser'] ) ? array_sum( $player->statistics['games']['loser'] ) : 0;
														} else {
															$games_won  = isset( $player->statistics['games']['winner'][ $stat_type ] ) ? $player->statistics['games']['winner'][ $stat_type ] : 0;
															$games_lost = isset( $player->statistics['games']['loser'][ $stat_type ] ) ? $player->statistics['games']['loser'][ $stat_type ] : 0;
														}
														if ( $games_won || $games_lost ) {
															$win_pct = ceil( ( $games_won / ( $games_won + $games_lost ) ) * 100 );
														} else {
															$win_pct = 0;
														}
														echo esc_html( $games_won ) . '-' . esc_html( $games_lost );
														?>
														<div class="progress">
															<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ); ?>%"></div>
														</div>
													</td>
													<td>
														<?php
														if ( 't' === $stat_type ) {
															$total_walkover = isset( $player->statistics['walkover'] ) ? array_sum( $player->statistics['walkover'] ) : '';
															echo esc_html( $total_walkover );
														} elseif ( isset( $player->statistics['walkover'][ $stat_type ] ) ) {
																echo esc_html( $player->statistics['walkover'][ $stat_type ] );
														}
														?>
													</td>
												</tr>
													<?php
											}
											?>
											<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<?php
			}
			?>
			</div>
		</div>
	</div>

