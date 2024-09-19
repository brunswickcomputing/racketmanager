<?php
/**
 *
 * Template page for a player titles
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__content">
		<div class="module__banner">
			<h4 class="module__title"><?php esc_html_e( 'Statistics', 'racketmanager' ); ?></h4>
		</div>
		<div class="module-container">
			<div class="module player-stats">
				<div class="row stats-header">
					<div class="col-2"></div>
					<div class="col-1 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Played', 'racketmanager' ); ?>"><?php esc_html_e( 'P', 'racketmanager' ); ?></div>
					<div class="col-4 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Win-loss-tie', 'racketmanager' ); ?>"><?php esc_html_e( 'W', 'racketmanager' ); ?></div>
					<div class="col-4"></div>
					<div class="col-1"></div>
				</div>
				<?php
				$stats_type   = 'totals';
				$player_stats = $player->stats[ $stats_type ];
				$statistics   = $player_stats['total'];
				$stat_title   = null;
				require 'statistics-group.php';
				foreach ( $player->stats['seasons'] as $stats_type => $player_stats ) {
					$statistics = $player_stats['total'];
					$stat_title = $stats_type;
					require 'statistics-group.php';
				}
				?>
			</div>
		</div>
	</div>
</div>
