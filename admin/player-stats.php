<?php
	$season = $leaguemanager->getSeasonCompetition($competition);
//	$clubs = getClubs();
    $clubs = $leaguemanager->getClubs( );
	if ( !empty($competition->seasons) ) { ?>
		<!-- Season Dropdown -->
<div class="alignright" style="clear: both;">
	<form action="admin.php" method="get" style="display: inline;">
		<input type="hidden" name="page" value="leaguemanager" />
		<input type="hidden" name="subpage" value="show-competition" />
		<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
		<label for="club_id"><?php _e('Affiliated Club', 'leaguemanager') ?></label>		<select size="1" name="club_id" id="club_id">
			<option><?php _e( 'Select club', 'leaguemanager' ) ?></option>
<?php foreach ( $clubs AS $club ) { ?>
			<option value="<?php echo $club->id ?>" <?php echo ($club->id == $club_id ?  'selected' :  '') ?>><?php echo $club->name ?></option>
<?php } ?>
		</select>
		<label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'leaguemanager' ) ?></label>
		<select size="1" name="season" id="season">
<?php foreach ( $competition->seasons AS $s ) { ?>
			<option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season['name'] ) echo ' selected="selected"' ?>><?php echo $s['name'] ?></option>
<?php } ?>
		</select>
		<input type="submit" name="statsseason" value="<?php _e( 'Show', 'leaguemanager' ) ?>" class="button" />
	</form>
</div>
<?php } ?>

<!-- View Player Stats -->
<form id="player-stats-filter" method="post" action="">

	<table class="widefat playerstats" summary="" title="LeagueManager Player Stats">
		<thead>
		<tr>
			<th rowspan="2" scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
			<th rowspan="2" scope="col"></th>
			<th colspan="<?php echo $season['num_match_days'] ?>" scope="colgroup" class="colspan"><?php _e( 'Match Day', 'leaguemanager') ?></th>
		</tr>
		<tr>
<?php
	$matchdaystatsdummy = array();
	for ( $day = 1; $day <= $season['num_match_days']; $day++ ) {
		$matchdaystatsdummy[$day] = array();
	?>
			<th scope="col" class="matchday"><?php echo $day ?></th>
<?php } ?>
		</tr>

		<tbody id="the-list">
<?php if ( $playerstats = $leaguemanager->getPlayerStats(array('competition' => $competition_id, 'season' => $season['name'], 'club' => $club_id))  ) { $class = ''; ?>
	<?php foreach ( $playerstats AS $playerstat ) { ?>
			<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">

				<td><?php echo $playerstat->fullname ?></td>

		<?php $matchdaystats = $matchdaystatsdummy;
			$prevTeamNum = $playdowncount = 0;
			$prevMatchDay = $i = 0;

            for ( $t = 1; $t < 10; $t++ ) {
                $teamplay[$t] = 0;
            }
            
			foreach ( $playerstat->matchdays AS $matches) {

				if ( !$prevMatchDay == $matches->match_day ) {
					$i = 0;
				}
				$teamNum = substr($matches->team_title,-1) ;
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
				
				$matchresult = $matches->match_winner == $matches->team_id ? 'Won' : 'Lost';
				$rubberresult = $matches->rubber_winner == $matches->team_id ? 'Won' : 'Lost';
				$matchdaystats[$matches->match_day][$i] = array('team' => $matches->team_title, 'pair' => $matches->rubber_number, 'matchresult' => $matchresult, 'rubberresult' => $rubberresult, 'playdir' => $playdir);
				$prevMatchDay = $matches->match_day;
				$i++;
			}
			
			if ( !$playdowncount == 0 ) {
				echo '<td title="Played Down">'.$playdowncount.'</td>';
			} else {
				echo '<td></td>';
			}
			foreach ( $matchdaystats AS $daystat ) {
				$dayshow = '';
				$title = '';
				foreach ( $daystat AS $stat ) {
					if ( isset($stat['team']) ) {
						$title		.= $matchresult.' match & '.$rubberresult.' rubber ';
						$playdir	= $stat['playdir'];
						$team		= $stat['team'];
						$pair		= $stat['pair'];
						$dayshow	.= $team.'<br />Pair'.$pair.'<br />';
					}
	
				}
				if ( count($daystat) > 1 ) {
					$playdir = 'playmulti';
				}
				if ( $dayshow == '' ) {
					echo '<td class="matchday" title=""></td>';
				} else {
					echo '<td class="matchday '.$playdir.'" title="'.$title.'">'.$dayshow.'</td>';
				}
			}
			$matchdaystats = $matchdaystatsdummy; ?>
			</tr>
	<?php } ?>
<?php } ?>
		</tbody>
	</table>
</form>
