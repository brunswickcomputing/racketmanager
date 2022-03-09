
<div class="container">
	<div class="row table-header">
		<div class="col-auto"> </div>
		<div class="col-2"><?php _e( 'Club', 'racketmanager' ) ?></div>
		<div class="col-9 container">
			<div class="row align-items-center">
				<?php for ( $i = 1; $i <= count($teams); $i++ ) { ?>
					<div class="col-2 fixture"><?php echo $i ?></div>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php foreach ( $teams AS $rank => $team ) {
		$team = get_leagueteam($team);
		if ( $team->home == 1 ) {
			$team->title = '<strong>'.$team->title.'</strong>';
		} ?>
		<div class="row table-row <?php echo $team->class ?>">
			<div class="col-auto rank">
				<?php echo $rank + 1 ?>
			</div>
			<div class="col-2">
				<?php echo $team->title ?>
			</div>
			<div class="col-9 container">
				<div class="row align-items-center">
					<?php for ( $i = 1; $i <= count($teams); $i++ ) { ?>
						<div class="col-2 fixture"><?php echo $league->getCrosstableField($team->id, $teams[$i-1]->id, $team->home); ?></div>
					<?php } ?>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
