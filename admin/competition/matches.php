<div class="container">
	<div class="row table-header">
		<div class="d-none d-lg-block col-1"><?php _e('Match day', 'racketmanager') ?></div>
		<div class="col-4 col-lg-2"><?php _e('Match date', 'racketmanager') ?></div>
		<div class="col-8 col-lg-5"><?php _e('Match', 'racketmanager') ?></div>
		<div class="d-none d-lg-block col-3"><?php _e('League', 'racketmanager') ?></div>
	</div>

	<?php if ( $matches = $racketmanager->getMatches(array('competition_id' => $competition->id, 'season' => $competition->getSeasonCompetition()['name'], 'orderby' => array('match_day' => 'ASC', 'date' => 'ASC', 'league_id' => 'ASC', 'home_team' => 'ASC'))) ) {
		$class = '';
		$matchDay = '';
		foreach ( $matches AS $match ) {
			if ( $match->match_day != $matchDay ) {
				$matchDay = $match->match_day; ?>
				<div class="row table-row">
					<div class="col-12"><?php echo __('Match Day', 'racketmanager').' '.$matchDay ?></div>
				</div>
			<?php } ?>
			<div class="row table-row">
				<div class="d-none d-lg-block col-1"></div>
				<div class="col-4 col-lg-2"><?php echo $match->date ?></div>
				<div class="col-8 col-lg-5"><?php echo $match->match_title ?></div>
				<div class="d-none d-lg-block col-3"><?php echo $match->league->title ?></div>
			</div>
		<?php } ?>
	<?php } ?>
</div>
