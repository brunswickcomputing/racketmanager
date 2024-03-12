<?php
/**
 * Template page for Team List
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league league object
 *  $teams: all teams of league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

?>
	<div class="page-subhead">
		<div class="media">
			<div class="media__wrapper">
				<div class="media__img">
					<span class="profile-icon">
						<span class="profile-icon__abbr">
							<?php
							$words    = explode( ' ', $league->team->title );
							$initials = null;
							foreach ( $words as $w ) {
								$initials .= $w[0];
							}
							echo esc_html( $initials );
							?>
						</span>
					</span>
				</div>
				<div class="media__content">
					<h3 class="media__title">
						<span><?php echo esc_html( $league->team->title ); ?></span>
						<?php
						$favourite_type = 'team';
						$favourite_id   = $league->team->id;
						require 'includes/favourite-button.php';
						?>
					</h3>
				</div>
			</div>
		</div>
	</div>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-7">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
					<div class="module__aside">
						<a role="button" class="btn btn--link calendar-add" href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->current_season['name'] ); ?>/" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'View standings', 'racketmanager' ); ?>">
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-table' ); ?>
							</i>
						</a>
					</div>
				</div>
				<div class="module__content">
					<div class="module-container">
						<ul class="list list--grid list--bordered-left">
							<li class="list__item">
								<div class="stats">
									<div class="stats__body">
										<span class="stats__title"><?php esc_html_e( 'Standing', 'racketmanager' ); ?></span>
										<span class="stats__value standing-status"><?php echo esc_html( $league->team->standings->rank ); ?></span>
									</div>
								</div>
							</li>
							<li class="list__item">
								<div class="stats">
									<div class="stats__body">
										<span class="stats__title"><?php esc_html_e( 'Played', 'racketmanager' ); ?></span>
										<span class="stats__value"><?php echo esc_html( $league->team->standings->done_matches ); ?></span>
									</div>
								</div>
							</li>
							<li class="list__item">
								<div class="stats">
									<div class="stats__body">
										<span class="stats__title"><?php esc_html_e( 'Points', 'racketmanager' ); ?></span>
										<span class="stats__value"><?php echo esc_html( $league->team->standings->points_formatted['primary'] ); ?></span>
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<?php
			if ( $league->team->matches ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
						<div class="module__aside">
							<a href="/index.php?league_id=<?php echo esc_html( $league->id ); ?>&team_id=<?php echo esc_html( the_team_id() ); ?>&team=<?php echo esc_html( the_team_name() ); ?>&season=<?php echo esc_html( $league->current_season['name'] ); ?>&racketmanager_export=calendar" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
								<i class="racketmanager-svg-icon">
									<?php racketmanager_the_svg( 'icon-calendar' ); ?>
								</i>
								<span class="nav-link__value text-uppercase">
									<?php esc_html_e( 'Calendar', 'racketmanager' ); ?>
								</span>
							</a>
						</div>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="module">
								<?php
								$matches     = $league->team->matches;
								$show_header = false;
								require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<div class="page-content__sidebar col-12 col-lg-5">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title">
						<?php esc_html_e( 'Team captain', 'racketmanager' ); ?>
					</h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<h4 class="subheading">
							<?php echo esc_html( $league->team->info->captain ); ?>
						</h4>
						<ul class="list list--naked">
							<?php
							if ( is_user_logged_in() ) {
								if ( ! empty( $league->team->info->contactno ) ) {
									?>
									<li class="list__item">
										<a href="tel:<?php echo esc_html( $league->team->info->contactno ); ?>" class="nav--link" rel="nofollow">
											<svg width="16" height="16" class="">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
											</svg>
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $league->team->info->contactno ); ?>
												</span>
											</span>
										</a>
									</li>
									<?php
								}
								?>
								<?php
								if ( ! empty( $league->team->info->contactemail ) ) {
									?>
									<li class="list__item">
										<a href="mailto:<?php echo esc_html( $league->team->info->contactemail ); ?>" class="nav--link"">
											<svg width="16" height="16" class="">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
											</svg>
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $league->team->info->contactemail ); ?>
												</span>
											</span>
										</a>
									</li>
									<?php
								}
							}
							?>
							<li class="list__item">
								<span class="nav--link">
									<svg width="16" height="16" class="icon-team">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
									</svg>
									<span class="nav-link__value">
										<?php echo esc_html( $league->team->info->match_day ); ?>
									</span>
								</span>
							</li>
							<li class="list__item">
								<span class="nav--link">
									<svg width="16" height="16" class="icon-team">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#clock-fill' ); ?>"></use>
									</svg>
									<span class="nav-link__value">
										<?php echo esc_html( $league->team->info->match_time ); ?>
									</span>
								</span>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title">
						<?php esc_html_e( 'Club', 'racketmanager' ); ?>
					</h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<h4 class="subheading">
							<a href="/clubs/<?php echo esc_attr( seo_url( $league->team->info->club->shortcode ) ); ?>/">
								<span><?php echo esc_html( $league->team->info->club->name ); ?></span>
							</a>
						</h4>
						<ul class="list list--naked">
							<li class="list__item">
								<span class="nav--link">
									<svg width="16" height="16" class="icon-marker">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-marker' ); ?>"></use>
									</svg>
									<span class="nav-link__value">
										<?php echo esc_html( $league->team->info->club->address ); ?>
									</span>
								</span>
							</li>
							<li class="list__item">
								<span class="nav--link">
									<svg width="16" height="16" class="icon-captain">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-captain' ); ?>"></use>
									</svg>
									<span class="nav-link__value">
										<?php echo esc_html( $league->team->info->club->match_secretary_name ); ?>
									</span>
								</span>
							</li>
							<?php
							if ( is_user_logged_in() ) {
								if ( ! empty( $league->team->info->club->match_secretary_contact_no ) ) {
									?>
									<li class="list__item">
										<a href="tel:<?php echo esc_html( $league->team->info->club->match_secretary_contact_no ); ?>" class="nav--link" rel="nofollow">
											<svg width="16" height="16" class="">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
											</svg>
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $league->team->info->club->match_secretary_contact_no ); ?>
												</span>
											</span>
										</a>
									</li>
									<?php
								}
								?>
								<?php
								if ( ! empty( $match->teams[ $opponent ]->club->match_secretary_email ) ) {
									?>
									<li class="list__item">
										<a href="mailto:<?php echo esc_html( $league->team->info->club->match_secretary_email ); ?>" class="nav--link"">
											<svg width="16" height="16" class="">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
											</svg>
											<span class="nav--link">
												<span class="nav-link__value">
													<?php echo esc_html( $league->team->info->club->match_secretary_email ); ?>
												</span>
											</span>
										</a>
									</li>
									<?php
								}
							}
							?>
							<?php
							if ( ! empty( $match->teams[ $opponent ]->club->website ) ) {
								?>
								<li class="list__item">
									<a href="<?php echo esc_html( $league->team->info->club->website ); ?>" class="nav--link" target="_blank" rel="noopener nofollow">
										<svg width="16" height="16" class="icon-globe">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#globe' ); ?>"></use>
										</svg>
										<span class="nav--link">
											<span class="nav-link__value">
												<?php echo esc_html( $league->team->info->club->website ); ?>
											</span>
										</span>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="col-12">
							<ol class="list list--bordered list--count">
								<?php
								foreach ( $league->team->players as $player ) {
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
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
