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
					<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
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
					<h3 class="module__title"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></h3>
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
									$event_link = '/tournament/' . seo_url( $tournament->name ) . '/event/' . seo_url( $entry->event ) . '/';
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
											<a href="<?php echo esc_attr( $event_link ); ?>" onclick="Racketmanager.tabDataLink(event,'tournament',<?php echo esc_attr( $tournament->id ); ?>,'','<?php echo esc_attr( $event_link ); ?>',<?php echo esc_attr( $entry->event_id ); ?>,'events')">
												<?php echo esc_html( $entry->event ); ?>
											</a>
										</div>
										<div class="col-5" name="<?php esc_html_e( 'Partner', 'racketmanager' ); ?>">
											<?php
											if ( ! empty( $entry->partner ) ) {
												$player_link = '/tournament/' . seo_url( $tournament->name ) . '/players/' . seo_url( $entry->partner->display_name ) . '/';
												?>
												<a href="<?php echo esc_attr( $player_link ); ?>" onclick="Racketmanager.tabDataLink(event,'tournament',<?php echo esc_attr( $tournament->id ); ?>,'','<?php echo esc_attr( $player_link ); ?>','<?php echo esc_attr( $entry->partner->id ); ?>','players')">
													<?php echo esc_html( wp_unslash( $entry->partner->display_name ) ); ?>
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
				$player_statistics = $player->statistics;
				$walkover_allowed  = true;
				require RACKETMANAGER_PATH . 'templates/includes/player-statistics.php';
			}
			?>
			</div>
		</div>
	</div>
