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

if ( ! empty( $event ) ) {
	$object    = $event;
	$item_link = '/' . $event->competition->type . '/' . seo_url( $event->name ) . '/' . $curr_season;
} elseif ( ! empty( $league ) ) {
	$object    = $league;
	$item_link = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/' . $league->current_season['name'];
}
if ( empty( $object->team ) ) {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( $object->teams ) {
					$teams = $object->teams;
					?>
					<ol class="list list--bordered">
						<?php
						foreach ( $teams as $team ) {
							?>
							<li class="list__item">
								<div class="">
									<div class="media">
										<div class="media__wrapper">
											<div class="media__img">
												<span class="profile-icon">
													<span class="profile-icon__abbr">
														<?php
														$words    = explode( ' ', $team->title );
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
												<h4 class="media__title">
													<?php
													if ( ! $object->is_championship ) {
														?>
														<a class="nav--link" href=<?php echo esc_attr( $item_link ) . '/team/' . esc_attr( seo_url( $team->title ) ) . '/'; ?>>
														<?php
													}
													?>
													<span><?php echo esc_html( $team->title ); ?></span>
													<?php
													if ( ! $object->is_championship ) {
														?>
														</a>
														<?php
													}
													?>
													<?php
													$favourite_type = 'team';
													$favourite_id   = $team->id;
													require 'includes/favourite-button.php';
													?>
												</h4>
											</div>
											<?php
											if ( ! $object->is_championship ) {
												?>
												<div class="media__aside">
													<a href="/index.php?league_id=<?php echo esc_html( $league->id ); ?>&team_id=<?php echo esc_html( $team->id ); ?>&team=<?php echo esc_html( $team->title ); ?>&season=<?php echo esc_html( $league->current_season['name'] ); ?>&racketmanager_export=calendar" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
														<i class="racketmanager-svg-icon">
															<?php racketmanager_the_svg( 'icon-calendar' ); ?>
														</i>
														<span class="nav-link__value text-uppercase">
															<?php esc_html_e( 'Calendar', 'racketmanager' ); ?>
														</span>
													</a>
												</div>
												<?php
											}
											?>
										</div>
									</div>

									<ul class="list list--flex">
										<?php
										if ( $league->event->competition->is_team_entry ) {
											?>
											<li class="list__item">
												<span class="nav--link">
													<svg width="16" height="16" class="icon-team">
														<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons-extra.svg#icon-team' ); ?>"></use>
													</svg>
													<span class="nav-link__value">
														<?php echo esc_html( $team->info->captain ); ?>
													</span>
												</span>
											</li>
											<?php
										}
										?>
										<?php
										if ( is_user_logged_in() ) {
											if ( $league->event->competition->is_player_entry ) {
												foreach ( $team->info->players as $team_player ) {
													?>
													<h4 class="subheading">
														<?php echo esc_html( $team_player->display_name ); ?>
													</h4>
													<?php
													if ( ! empty( $team_player->contactno ) ) {
														?>
														<li class="list__item">
															<a href="tel:<?php echo esc_html( $team_player->contactno ); ?>" class="nav--link" rel="nofollow">
																<svg width="16" height="16" class="">
																	<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
																</svg>
																<span class="nav--link">
																	<span class="nav-link__value">
																		<?php echo esc_html( $team_player->contactno ); ?>
																	</span>
																</span>
															</a>
														</li>
														<?php
													}
													if ( ! empty( $team_player->email ) ) {
														?>
														<li class="list__item">
															<a href="mailto:<?php echo esc_html( $team_player->email ); ?>" class="nav--link"">
																<svg width="16" height="16" class="">
																	<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
																</svg>
																<span class="nav--link">
																	<span class="nav-link__value">
																		<?php echo esc_html( $team_player->email ); ?>
																	</span>
																</span>
															</a>
														</li>
														<?php
													}
												}
											} else {
												if ( ! empty( $team->info->contactno ) ) {
													?>
													<li class="list__item">
														<a href="tel:<?php echo esc_html( $team->info->contactno ); ?>" class="nav--link" rel="nofollow">
															<svg width="16" height="16" class="">
																<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
															</svg>
															<span class="nav--link">
																<span class="nav-link__value">
																	<?php echo esc_html( $team->info->contactno ); ?>
																</span>
															</span>
														</a>
													</li>
													<?php
												}
												if ( ! empty( $team->info->contactemail ) ) {
													?>
													<li class="list__item">
														<a href="mailto:<?php echo esc_html( $team->info->contactemail ); ?>" class="nav--link"">
															<svg width="16" height="16" class="">
																<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
															</svg>
															<span class="nav--link">
																<span class="nav-link__value">
																	<?php echo esc_html( $team->info->contactemail ); ?>
																</span>
															</span>
														</a>
													</li>
													<?php
												}
											}
										}
										?>
										<?php
										if ( ! empty( $team->info->match_day ) ) {
											?>
											<li class="list__item">
												<span class="nav--link">
													<svg width="16" height="16" class="icon-team">
														<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
													</svg>
													<span class="nav-link__value">
														<?php echo esc_html( $team->info->match_day ); ?>
													</span>
												</span>
											</li>
											<?php
										}
										?>
										<?php
										if ( ! empty( $team->info->match_time ) && '00:00:00' !== $team->info->match_time ) {
											?>
											<li class="list__item">
												<span class="nav--link">
													<svg width="16" height="16" class="icon-team">
														<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#clock-fill' ); ?>"></use>
													</svg>
													<span class="nav-link__value">
														<?php echo esc_html( $team->info->match_time ); ?>
													</span>
												</span>
											</li>
											<?php
										}
										?>
									</ul>

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
} else {
	require 'team-details.php';
}
?>
