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

/** @var object $league */
global $racketmanager_shortcodes;
if ( ! empty( $league->player ) ) {
	$player = $league->player;
	require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
	?>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-6">
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
		<div class="page-content__sidebar col-12 col-lg-6">
			<?php
			if ( ! empty( $league->player->statistics ) ) {
				$player_statistics = $league->player->statistics;
				require RACKETMANAGER_PATH . 'templates/includes/player-statistics.php';
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
							$selected_player = false;
							if ( intval( $player->id ) === get_current_user_id() ) {
								$selected_player = true;
							}
							$player_link = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/' . $league->current_season['name'] . '/player/' . seo_url( $player->fullname ) . '/';
							$team_link   = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/' . $league->current_season['name'] . '/team/' . seo_url( $player->team->title ) . '/';
							?>
							<li class="list__item <?php echo empty( $selected_player ) ? null : 'is-selected'; ?>">
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
														<a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( $league->id ); ?>" data-season="<?php echo esc_attr( $league->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players" >
															<span class="nav-link__value">
																<?php echo esc_html( $player->fullname ); ?>
															</span>
														</a>
													</p>
													<p class="media__subheading">
														<a href="<?php echo esc_attr( $team_link ); ?>" class="nav--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( $league->id ); ?>" data-season="<?php echo esc_attr( $league->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $team_link ); ?>" data-link-id="<?php echo esc_attr( $player->team->id ); ?>" data-link-type="teams">
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
															<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
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
				} else {
					esc_html_e( 'No players found', 'racketmanager' );
				}
				?>
			</div>
		</div>
	</div>
	<?php
}
?>
