<?php
$season = $competition->getSeasonCompetition();
if ( $competition->is_championship ) {
	$heading = "Round";
	if (isset($competition->primary_league)) {
		$primaryLeague = get_league($competition->primary_league);
	} else {
		$leagues = $competition->getLeagues(array( 'competition' => $competition->id));
		$primaryLeague = get_league(array_key_first($competition->league_index));
	}
	$numCols = $primaryLeague->championship->num_rounds;
	$rounds = array();
	$i = 1;
	foreach ( array_reverse($primaryLeague->championship->getFinals()) AS $final ) {
		$rounds[$i] = $final;
		$i ++;
	}
} else {
	$heading = "Match Day";
	$numCols = isset($season['num_match_days']) ? $season['num_match_days'] : 0;
}
$clubs = $racketmanager->getClubs( );
if ( !empty($competition->seasons) ) { ?>
	<!-- Season Dropdown -->
	<div class="container">
		<div class="row  justify-content-end">
			<div class="col-auto">
				<form action="admin.php" method="get" class="form-control">
					<input type="hidden" name="page" value="racketmanager" />
					<input type="hidden" name="subpage" value="show-competition" />
					<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
					<label for="club_id"><?php _e('Affiliated Club', 'racketmanager') ?></label>		<select size="1" name="club_id" id="club_id">
						<option><?php _e( 'Select club', 'racketmanager' ) ?></option>
						<?php foreach ( $clubs AS $club ) { ?>
							<option value="<?php echo $club->id ?>" <?php echo $club->id == $club_id ?  'selected' :  '' ?>><?php echo $club->name ?></option>
						<?php } ?>
					</select>
					<label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'racketmanager' ) ?></label>
					<select size="1" name="season" id="season">
						<?php foreach ( $competition->seasons AS $s ) { ?>
							<option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season['name'] ) echo ' selected="selected"' ?>><?php echo $s['name'] ?></option>
						<?php } ?>
					</select>
					<input type="submit" name="statsseason" value="<?php _e( 'Show', 'racketmanager' ) ?>" class="btn btn-secondary" />
				</form>
			</div>
		</div>
	</div>
<?php } ?>

<!-- View Player Stats -->
<div class="container">
	<form id="player-stats-filter" method="post" action="">

		<div class="row table-header">
			<div class="col-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
			<div class="col-1"></div>
			<div class="col-9">
				<div class="row justify-content-center">
					<div class="col-auto"><?php _e( $heading, 'racketmanager') ?></div>
				</div>
				<div class="row">
					<?php
					$matchdaystatsdummy = array();
					for ( $day = 1; $day <= $numCols; $day++ ) {
						$matchdaystatsdummy[$day] = array();
						?>
						<div class="col-1 matchday"><?php if ($competition->is_championship) echo $rounds[$day]['name']; else echo $day; ?></div>
					<?php } ?>
				</div>
			</div>
		</div>

		<?php if ( $playerstats = $competition->getPlayerStats(array('season' => isset($season['name']) ? $season['name'] : false, 'club' => $club_id))  ) {
			$class = '';
			foreach ( $playerstats AS $playerstat ) {
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">

					<div class="col-2"><?php echo $playerstat->fullname ?></div>

					<?php $matchdaystats = $matchdaystatsdummy;
					$prevTeamNum = $playdowncount = 0;
					$prevMatchDay = $i = 0;
					$prevRound = "";

					for ( $t = 1; $t < $numCols; $t++ ) {
						$teamplay[$t] = 0;
					}

					foreach ( $playerstat->matchdays AS $m => $match) {
						if ( ($competition->is_championship && !$prevRound == $match->final) || ( !$competition->is_championship && !$prevMatchDay == $match->match_day) ) {
							$i = 0;
						}
						$teamNum = substr($match->team_title,-1) ;
						$teamplay[$teamNum] ++;

						if ( $prevTeamNum == 0) {
							$playdir = '';
						} elseif ( $teamNum > $prevTeamNum ) {
							if ( $teamplay[$prevTeamNum] > 2 ) {
								$playdir = 'playdownerr';
							} else {
								$playdir = 'playdown';
							}
							$playdowncount ++;
						} else {
							$playdir = '';
						}
						$prevTeamNum = $teamNum;

						if ($match->match_winner == $match->team_id) {
							$matchresult = __('Won','racketmanager');
						} elseif ($match->match_loser == $match->team_id ) {
							$matchresult = __('Lost','racketmanager');
						} else {
							$matchresult = __('Drew','racketmanager');
						}
						if ($match->rubber_winner == $match->team_id) {
							$rubberresult = __('Won','racketmanager');
						} elseif ($match->rubber_loser == $match->team_id) {
							$rubberresult = __('Lost','racketmanager');
						} else {
							$rubberresult = __('Drew','racketmanager');
						}
						$playerLine = array('team' => $match->team_title, 'pair' => $match->rubber_number, 'matchresult' => $matchresult, 'rubberresult' => $rubberresult, 'playdir' => $playdir);
						if ( $competition->is_championship ) {
							$d = $primaryLeague->championship->getFinals($match->final)['round'];
							$matchdaystats[$d][$i] = $playerLine;
						} else {
							$matchdaystats[$match->match_day][$i] = $playerLine;
						}
						$prevMatchDay = $match->match_day;
						$prevRound = $match->final;
						$i++;
					} ?>

					<div class="col-1" title="Played Down">
						<?php if ( !$playdowncount == 0 ) { ?>
							<?php echo $playdowncount ?></td>
						<?php } ?>
					</div>
					<div class="col-9">
						<div class="row">
							<?php	foreach ( $matchdaystats AS $daystat ) {
								$dayshow = '';
								$title = '';
								$playdir = '';
								foreach ( $daystat AS $stat ) {
									if ( isset($stat['team']) ) {
										$title		= $matchresult.' match & '.$rubberresult.' rubber ';
										$playdir	= $stat['playdir'];
										$team		= $stat['team'];
										$pair		= $stat['pair'];
										$dayshow	.= $team.'<br />Pair'.$pair.'<br />';
									}
								}
								if ( count($daystat) > 1 ) {
									$playdir = 'playmulti';
								} ?>
								<div class="col-1 matchday <?php echo $playdir ?>" title="<?php echo $title ?>">
									<?php if ( !$dayshow == '' ) { ?>
										<?php echo $dayshow ?>
									<?php } ?>
								</div>
							<?php }
							$matchdaystats = $matchdaystatsdummy; ?>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</form>
</div>
