<?php
/**
 * Template page for the match table
 *
 * @package Racketmanager/Templates
 * The following variables are usable:
 *  $league: contains data of current league
 *  $matches: contains all matches for current league
 *  $teams: contains teams of current league in an associative array
 *  $season: current season
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $racketmanager_shortcodes;
if ( ! empty( $league->player ) ) {
	$player = $league->player;
	require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
	?>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-8">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="module">
							<?php
							foreach ( $league->player->matches as $match ) {
								$match_args['match']        = $match;
								$match_args['match_player'] = $league->player;
								$template                   = 'match-teams-scores';
								echo $racketmanager_shortcodes->load_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$template,
									$match_args, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								);
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-4">
			<?php
			if ( ! empty( $league->player->statistics ) ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Player statistics', 'racketmanager' ); ?></h3>
											</div>
					<div class="module__content">
						<div class="module-container">
							<div class="module">
								<div class="table-responsive">
									<table id="team-player-stats" class="table table-borderless player-stats" aria-describedby="<?php esc_html_e( 'Player Team Statistics', 'racketmanager' ); ?>">
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
											</tr>
										</thead>
										<tbody>
											<?php
											$player_statistics = $league->player->statistics;
											debug_to_console( $player_statistics );
											$stat_rows         = array(
												__( 'Doubles', 'racketmanager' ) => 'd',
											);
											foreach ( $stat_rows as $stat_title => $stat_type ) {
												$matches_won   = ! empty( $player_statistics['played']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['played']['winner'][ $stat_type ] ) : 0;
												$matches_lost  = ! empty( $player_statistics['played']['loser'][ $stat_type ] ) ? array_sum( $player_statistics['played']['loser'][ $stat_type ] ) : 0;
												$matches_drawn = ! empty( $player_statistics['played']['draw'][ $stat_type ] ) ? array_sum( $player_statistics['played']['draw'][ $stat_type ] ) : 0;
												$played        = $matches_won + $matches_lost + $matches_drawn;
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
															$matches_won = ! empty( $player_statistics['played']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['played']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $matches_won );
															?>
														</td>
														<td>
															<?php
															$sets_won = ! empty( $player_statistics['sets']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['sets']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $sets_won );
															?>
														</td>
														<td>
															<?php
															$games_won = ! empty( $player_statistics['games']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['games']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $games_won );
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
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
} else {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( $league->players ) {
					?>
					<ol class="list list--bordered list--count">
						<?php
						foreach ( $league->players as $player ) {
							?>
							<li class="list__item">
								<div class="media">
									<div class="media__wrapper">
										<div class="media__img">
											<div class="profile-icon">
												<span class="profile-icon__abbr">
													<?php
													$player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
													echo esc_html( $player_initials );
													?>
												</span>
											</div>
										</div>
										<div class="media__content">
											<div class="flex-container">
												<div class="flex-item flex-item--grow">
													<p class="media__title">
														<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $player->fullname ) ); ?>/" class="nav--link">
															<span class="nav-link__value">
																<?php echo esc_html( $player->fullname ); ?>
															</span>
														</a>
													</p>
													<p class="media__subheading">
														<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $player->team->title ) ); ?>/" class="nav--link">
															<span class="nav-link__value">
																<?php echo esc_html( $player->team->title ); ?>
															</span>
														</a>
													</p>
												</div>
												<div class="progress-bar-container">
													<?php
													if ( $player->played ) {
														?>
														<div class="clearfix">
															<span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
															<span class="pull-right"><?php echo esc_html( $player->matches_won ) . '-' . esc_html( $player->matches_lost ) . ' (' . esc_html( $player->played ) . ')'; ?></span>
														</div>
														<div class="progress">
															<div class="progress-bar bg-success" role="progress-bar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
														</div>
														<?php
													}
													?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</li>
							<?php
						}
						?>
					</ol>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
}
?>
