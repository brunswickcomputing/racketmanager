<?php
/**
 * Template for player statistics
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="col-<?php echo empty( $is_walkover ) ? 4 : 3; ?> stats-title">
	<?php
	if ( 's' === $stat_title ) {
		$stat_title = __( 'Singles', 'racketmanager' );
	} elseif ( 'd' === $stat_title ) {
		$stat_title = __( 'Doubles', 'racketmanager' );
	}
	?>
	<?php echo esc_html( $stat_title ); ?>
</div>
<div class="col-1 text-center"><?php echo esc_html( $played ); ?></div>
<div class="col-2 text-center">
	<?php echo esc_html( $matches_won ) . '-' . esc_html( $matches_lost ); ?>
	<div class="progress">
		<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
	</div>
</div>
<div class="col-2 text-center">
	<?php echo esc_html( $sets_won ) . '-' . esc_html( $sets_lost ); ?>
	<div class="progress">
		<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct_sets ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct_sets ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
	</div>
</div>
<div class="col-2 text-center">
	<?php echo esc_html( $games_won ) . '-' . esc_html( $games_lost ); ?>
	<div class="progress">
		<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct_games ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct_games ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
	</div>
</div>
<?php
if ( ! empty( $is_walkover ) ) {
	?>
	<div class="col-1 text-center"><?php echo esc_html( $walkover ); ?></div>
	<?php
}
?>
