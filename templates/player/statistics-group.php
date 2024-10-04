<?php
/**
 *
 * Template page for a player statistics group
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<a href="#" class="stats-link collapsed" data-bs-toggle="collapse" data-bs-target="#player-stats-detail-<?php echo esc_attr( $stats_type ); ?>" aria-expanded="false" aria-controls="player-stats-detail-<?php echo esc_attr( $stats_type ); ?>">
	<div class="row stats-summary">
		<?php require 'statistics-row.php'; ?>
		<div class="col-1">
			<div class="">
				<svg width="16" height="16" class="icon-stats">
					<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#caret-up-fill' ); ?>"></use>
				</svg>
			</div>
		</div>
	</div>
</a>
<div id="player-stats-detail-<?php echo esc_attr( $stats_type ); ?>" class="collapse">
	<?php
	if ( isset( $player_stats['breakdown'] ) ) {
		foreach ( $player_stats['breakdown'] as $stat_title => $statistics ) {
			?>
			<div class="row stats-detail">
				<?php require 'statistics-row.php'; ?>
			</div>
			<?php
		}
	}
	?>
</div>
