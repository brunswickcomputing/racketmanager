<?php
/**
 * Crosstable administration viewing panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$team_count = count( $teams );
?>
<div class="container">
	<div class="row table-header">
		<div class="col-auto"> </div>
		<div class="col-2"><?php esc_html_e( 'Club', 'racketmanager' ); ?></div>
		<div class="col-9 container">
			<div class="row align-items-center">
				<?php for ( $i = 1; $i <= $team_count; $i++ ) { ?>
					<div class="col-2 fixture"><?php echo esc_html( $i ); ?></div>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
	foreach ( $teams as $rank => $team ) {
		$team = get_league_team( $team );
		if ( 1 === $team->home ) {
			$team->title = '<strong>' . $team->title . '</strong>';
		}
		?>
		<div class="row table-row <?php echo esc_html( $team->class ); ?>">
			<div class="col-auto rank">
				<?php echo esc_html( $rank + 1 ); ?>
			</div>
			<div class="col-2">
				<?php echo esc_html( $team->title ); ?>
			</div>
			<div class="col-9 container">
				<div class="row align-items-center">
					<?php for ( $i = 1; $i <= $team_count; $i++ ) { ?>
						<div class="col-2 fixture"><?php echo $league->get_crosstable_field( $team->id, $teams[ $i - 1 ]->id, $team->home ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php } ?>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
