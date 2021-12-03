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
        $numCols = $season['num_match_days'];
    }
    $clubs = $racketmanager->getClubs( );
	if ( !empty($competition->seasons) ) { ?>
		<!-- Season Dropdown -->
<div class="alignright" style="clear: both;">
	<form action="admin.php" method="get" style="display: inline;">
		<input type="hidden" name="page" value="racketmanager" />
		<input type="hidden" name="subpage" value="show-competition" />
		<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
		<label for="club_id"><?php _e('Affiliated Club', 'racketmanager') ?></label>		<select size="1" name="club_id" id="club_id">
			<option><?php _e( 'Select club', 'racketmanager' ) ?></option>
<?php foreach ( $clubs AS $club ) { ?>
			<option value="<?php echo $club->id ?>" <?php echo ($club->id == $club_id ?  'selected' :  '') ?>><?php echo $club->name ?></option>
<?php } ?>
		</select>
		<label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'racketmanager' ) ?></label>
		<select size="1" name="season" id="season">
<?php foreach ( $competition->seasons AS $s ) { ?>
			<option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season['name'] ) echo ' selected="selected"' ?>><?php echo $s['name'] ?></option>
<?php } ?>
		</select>
		<input type="submit" name="statsseason" value="<?php _e( 'Show', 'racketmanager' ) ?>" class="button" />
	</form>
</div>
<?php } ?>

<!-- View Player Stats -->
<form id="player-stats-filter" method="post" action="">

	<table class="widefat playerstats" summary="" title="RacketManager Player Stats">
		<thead>
		<tr>
            <th rowspan="2" scope="col" class="playername"><?php _e( 'Name', 'racketmanager' ) ?></th>
			<th rowspan="2" scope="col" class="status"></th>
			<th colspan="<?php echo $numCols ?>" scope="colgroup" class="colspan"><?php _e( $heading, 'racketmanager') ?></th>
		</tr>
		<tr>
<?php
	$matchdaystatsdummy = array();
	for ( $day = 1; $day <= $numCols; $day++ ) {
		$matchdaystatsdummy[$day] = array();
	?>
<th scope="col" class="matchday"><?php if ($competition->is_championship) echo $rounds[$day]['name']; else echo $day; ?></th>
<?php } ?>
		</tr>

		<tbody id="the-list">
<?php if ( $playerstats = $competition->getPlayerStats(array('season' => $season['name'], 'club' => $club_id))  ) {
    $class = '';
    foreach ( $playerstats AS $playerstat ) {
        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">

				<td><?php echo $playerstat->fullname ?></td>

		<?php $matchdaystats = $matchdaystatsdummy;
			$prevTeamNum = $playdowncount = 0;
			$prevMatchDay = $i = 0;
            $prevRound = "";

            for ( $t = 1; $t < $numCols; $t++ ) {
                $teamplay[$t] = 0;
            }

			foreach ( $playerstat->matchdays AS $m => $match) {
                if ( ($competition->is_championship && !$prevRound == $match->final_round) || ( !$competition->is_championship && !$prevMatchDay == $match->match_day) ) {
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

                if ($match->match_winner === $match->team_id) $matchresult = 'Won'; else $matchresult = 'Lost';
                if ($match->rubber_winner === $match->team_id) $rubberresult = 'Won'; else $rubberresult = 'Lost';
                $playerLine = array('team' => $match->team_title, 'pair' => $match->rubber_number, 'matchresult' => $matchresult, 'rubberresult' => $rubberresult, 'playdir' => $playdir);
                if ( $competition->is_championship ) {
                    $d = $primaryLeague->championship->getFinals($match->final_round)['round'];
                    $matchdaystats[$d][$i] = $playerLine;
                } else {
                    $matchdaystats[$match->match_day][$i] = $playerLine;
                }
				$prevMatchDay = $match->match_day;
                $prevRound = $match->final;
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
						$title		= $matchresult.' match & '.$rubberresult.' rubber ';
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
