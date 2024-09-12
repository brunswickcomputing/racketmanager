<?php
/**
 * Template for player statistics
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$statistics    = $player->statistics['total'];
$stat_title    = __( 'Summary', 'racketmanager' );
$matches_won   = ! empty( $statistics->matches_won ) ? ( $statistics->matches_won ) : 0;
$matches_lost  = ! empty( $statistics->matches_lost ) ? ( $statistics->matches_lost ) : 0;
$matches_drawn = ! empty( $statistics->matches_tie ) ? ( $statistics->matches_tie ) : 0;
$played        = $matches_won + $matches_lost + $matches_drawn;
if ( $played ) {
	$win_pct = ceil( ( $matches_won / $played ) * 100 );
} else {
	$win_pct = null;
}
$sets_won  = ! empty( $statistics->sets_won ) ? ( $statistics->sets_won ) : 0;
$sets_lost = ! empty( $statistics->sets_lost ) ? ( $statistics->sets_lost ) : 0;
if ( $sets_won || $sets_lost ) {
	$win_pct_sets = ceil( ( $sets_won / ( $sets_won + $sets_lost ) ) * 100 );
} else {
	$win_pct_sets = 0;
}
$games_won  = ! empty( $statistics->games_won ) ? ( $statistics->games_won ) : 0;
$games_lost = ! empty( $statistics->games_lost ) ? ( $statistics->games_lost ) : 0;
if ( $games_won || $games_lost ) {
	$win_pct_games = ceil( ( $games_won / ( $games_won + $games_lost ) ) * 100 );
} else {
	$win_pct_games = 0;
}
$is_walkover    = isset( $walkover_allowed ) ? true : false;
$total_walkover = isset( $player->statistics['walkover'] ) ? array_sum( $player->statistics['walkover'] ) : '';
$walkover       = ! empty( $statistics->walkover ) ? ( $statistics->walkover ) : 0;
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Statistics', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<div class="module player-stats">
				<div class="row stats-header">
					<div class="col-<?php echo empty( $is_walkover ) ? 4 : 3; ?>"></div>
					<div class="col-1 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Played', 'racketmanager' ); ?>"><?php esc_html_e( 'P', 'racketmanager' ); ?></div>
					<div class="col-2 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Won', 'racketmanager' ); ?>"><?php esc_html_e( 'W', 'racketmanager' ); ?></div>
					<div class="col-2 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Sets', 'racketmanager' ); ?>"><?php esc_html_e( 'S', 'racketmanager' ); ?></div>
					<div class="col-2 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Games', 'racketmanager' ); ?>"><?php esc_html_e( 'G', 'racketmanager' ); ?></div>
					<?php
					if ( ! empty( $is_walkover ) ) {
						?>
						<div class="col-1 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Walkover', 'racketmanager' ); ?>"><?php esc_html_e( 'W/O', 'racketmanager' ); ?></div>
						<?php
					}
					?>
					<div class="col-1"></div>
				</div>
				<a href="#" class="stats-link collapsed" data-bs-toggle="collapse" data-bs-target="#team-player-stats-detail" aria-expanded="false" aria-controls="team-player-stats-detail">
					<div class="row stats-summary">
						<?php require RACKETMANAGER_PATH . 'templates/includes/statistics.php'; ?>
						<div class="col-1">
							<div class="">
								<svg width="16" height="16" class="icon-stats">
									<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#caret-up-fill' ); ?>"></use>
								</svg>
							</div>
						</div>
					</div>
				</a>
				<div id="team-player-stats-detail" class="collapse">
					<?php
					foreach ( $player_statistics['detail'] as $stat_title => $statistics ) {
						$matches_won   = ! empty( $statistics['played']['winner'] ) ? ( $statistics['played']['winner'] ) : 0;
						$matches_lost  = ! empty( $statistics['played']['loser'] ) ? ( $statistics['played']['loser'] ) : 0;
						$matches_drawn = ! empty( $statistics['played']['draw'] ) ? ( $statistics['played']['draw'] ) : 0;
						$played        = $matches_won + $matches_lost + $matches_drawn;
						$sets_won      = ! empty( $statistics['sets']['winner'] ) ? ( $statistics['sets']['winner'] ) : 0;
						$sets_lost     = ! empty( $statistics['sets']['loser'] ) ? ( $statistics['sets']['loser'] ) : 0;
						$games_won     = ! empty( $statistics['games']['winner'] ) ? ( $statistics['games']['winner'] ) : 0;
						$games_lost    = ! empty( $statistics['games']['loser'] ) ? ( $statistics['games']['loser'] ) : 0;
						$walkover      = ! empty( $statistics['walkover'] ) ? ( $statistics['walkover'] ) : '';
						?>
						<div class="row stats-detail">
							<?php require RACKETMANAGER_PATH . 'templates/includes/statistics.php'; ?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
