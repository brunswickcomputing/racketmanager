
<table class='widefat crosstable' summary='' title='<?php echo __( 'Crosstable', 'racketmanager' )." ".$league->title ?>'>
	<thead>
		<tr>
			<th colspan='2' style='text-align: center;'><?php _e( 'Club', 'racketmanager' ) ?></th>
			<?php for ( $i = 1; $i <= count($teams); $i++ ) { ?>
				<th style='text-align: center;'><?php echo $i ?></th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $teams AS $rank => $team ) {
			$team = get_leagueteam($team);
			if ( $team->home == 1 ) $team->title = '<strong>'.$team->title.'</strong>'; ?>
			<tr class='<?php echo $team->class ?>'>
				<th scope='row' class='rank'><?php echo $rank + 1 ?></th>
				<td>
					<?php echo $team->title ?>
				</td>
				<?php for ( $i = 1; $i <= count($teams); $i++ ) { ?>
					<?php echo $league->getCrosstableField($team->id, $teams[$i-1]->id, $team->home); ?>
				<?php } ?>
			</tr>
		<?php } ?>
	</tbody>
</table>
