<?php
/**
 *
 * Template page for a player statistics row
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="col-2 stats-title">
	<?php
	$matches_won   = ! empty( $statistics['win'] ) ? ( $statistics['win'] ) : 0;
	$matches_lost  = ! empty( $statistics['loss'] ) ? ( $statistics['loss'] ) : 0;
	$matches_drawn = ! empty( $statistics['tie'] ) ? ( $statistics['tie'] ) : 0;
	$played        = $matches_won + $matches_lost + $matches_drawn;
	if ( $played ) {
		$win_pct = ceil( ( $matches_won / $played ) * 100 );
	} else {
		$win_pct = null;
	}
	$win_loss  = $matches_won . '-' . $matches_lost;
	$win_loss .= empty( $matches_drawn ) ? '' : '-' . esc_html( $matches_drawn );
	if ( 'S' === $stat_title ) {
		$stat_title = __( 'Singles', 'racketmanager' );
	} elseif ( 'D' === $stat_title ) {
		$stat_title = __( 'Doubles', 'racketmanager' );
	} elseif ( 'X' === $stat_title ) {
		$stat_title = __( 'Mixed', 'racketmanager' );
	} elseif ( 'totals' === $stats_type ) {
		$stat_title = __( 'Career', 'racketmanager' );
	}
	?>
	<?php echo esc_html( $stat_title ); ?>
</div>
<div class="col-1 text-center"><?php echo esc_html( $played ); ?></div>
<div class="col-4 text-center">
	<?php echo esc_html( $win_loss ); ?>
</div>
<div class="col-4 text-center align-content-center">
	<div class="progress">
		<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
	</div>
</div>
