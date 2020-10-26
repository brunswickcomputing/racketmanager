<?php
/**
Template page for the standings table in extended form (default)

The following variables are usable:

	$league: contains data about the league
	$teams: contains all teams of current league

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>

<?php if ( isset($_GET['team']) && !$widget ) { ?>
	<?php leaguemanager_team($_GET['team']); ?>
<?php } else { ?>

	<?php if ( $teams ) { ?>

		<table style="width: 100%" class="leaguemanager standingstable" summary="" title="<?php _e( 'Standings', 'leaguemanager' ) .' '.$league->title ?>">
			<thead>
				<tr>
					<th class="num"><?php echo _e( 'Pos', 'leaguemanager' ) ?></th>
					<th class="num">&#160;</th>
				<?php if ( $league->show_logo ) { ?>
					<th class="logo">&#160;</th>
				<?php }?>

					<th class="team"><?php _e( 'Team', 'leaguemanager' ) ?></th>
				<?php if ( isset($league->standings['pld']) && 1 == $league->standings['pld'] ) { ?>
					<th class="num"><?php _e( 'Pld', 'leaguemanager' ) ?></th>
				<?php } ?>
				<?php if ( isset($league->standings['won']) && 1 == $league->standings['won'] ) { ?>
					<th class="num"><?php echo _e( 'W','leaguemanager' ) ?></th>
				<?php } ?>
				<?php if ( isset($league->standings['tie']) && 1 == $league->standings['tie'] ) { ?>
					<th class="num"><?php echo _e( 'T','leaguemanager' ) ?></th>
				<?php } ?>
				<?php if ( isset($league->standings['lost']) && 1 == $league->standings['lost'] ) { ?>
					<th class="num"><?php echo _e( 'L','leaguemanager' ) ?></th>
				<?php } ?>
					<?php do_action( 'leaguemanager_standings_header_'.$league->sport ) ?>
					<th class="num"><?php _e( 'Pts Adjust', 'leaguemanager' ) ?></th>
					<th class="num"><?php _e( 'Points', 'leaguemanager' ) ?></th>
					<th width="100" class="last5"><?php _e( 'Last 5', 'leaguemanager' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $teams AS $team ) { ?>

					<tr class='<?php echo $team->class ?>'>
						<td class='num'><span class="rank"><?php echo $team->rank ?></span></td>
						<td class="num"><?php echo $team->status ?></td>
<?php if ( $league->show_logo ) { ?>
						<td class="logo">
	<?php if ( $team->logo != '' ) { ?>
							<img src='<?php echo $leaguemanager->getImageUrl($team->logo, false, 'tiny') ?>' alt='<?php _e('Logo','leaguemanager') ?>' title='<?php _e('Logo','leaguemanager')." ".$team->title ?>' />
	<?php } ?>
						</td>
<?php } ?>

						<td><?php echo $team->title ?></a></td>
<?php if ( isset($league->standings['pld']) && 1 == $league->standings['pld'] ) { ?>
						<td class='num'><?php echo $team->done_matches ?></td>
<?php } ?>
<?php if ( isset($league->standings['won']) && 1 == $league->standings['won'] ) { ?>
						<td class='num'><?php echo $team->won_matches ?></td>
<?php } ?>
<?php if ( isset($league->standings['tie']) && 1 == $league->standings['tie'] ) { ?>
						<td class='num'><?php echo $team->draw_matches ?></td>
<?php } ?>
<?php if ( isset($league->standings['lost']) && 1 == $league->standings['lost'] ) { ?>
						<td class='num'><?php echo $team->lost_matches ?></td>
<?php } ?>
						<?php do_action( 'leaguemanager_standings_columns_'.$league->sport, $team, $league->point_rule ) ?>
						<td class='num'><?php echo $team->add_points ?></td>
						<td class='num'><?php echo $team->points ?></td>

					<?php
					// Show latest results if enabled
					// Open the td tag
						$last5 = '';

					// Get Next Match
						//$next_results = get_next_match($team->id, 1);
						$matcharray = array("time" => "next", "team_id" => $team->id, "limit" => 1, "league_id" => $league->id);
						$next_results = $leaguemanager->getMatches( $matcharray );
						$last5 = '<td style="text-align: right;" class="last5Icon last5">';
						if ( $next_results ) {
							foreach ($next_results as $next_result)
							{
								$homeTeam = $leaguemanager->getTeam( $next_result->home_team );
								$awayTeam = $leaguemanager->getTeam( $next_result->away_team );
								$homeTeamName = $homeTeam->title;
								$awayTeamName = $awayTeam->title;
								$myMatchDate = mysql2date(get_option('date_format'), $next_result->date);
								$tooltipTitle = 'Next Match: '.$homeTeamName.' - '.$awayTeamName.' ['.$myMatchDate.']';
								$last5 .= '<a href="?match_'.$league->id.'='."$next_result->id".'"  class="N last5-bg" title="'.$tooltipTitle.'">&nbsp;</a>';
							}
						} else {
							$last5 .= '<a class="N last5-bg" title="Next Match: No Game Scheduled">&nbsp;</a>';
						}

						// Get the latest results
						//$results = get_last_matches($team->id, 5);
						//$results = get_latest_results($team->id, 5);
						$matcharray = array("time" => "latest", "team_id" => $team->id, "limit" => 5, "league_id" => $league->id);
						$results = $leaguemanager->getMatches( $matcharray );
						foreach ($results as $result)
						{
							$result->hadPenalty = ( isset($result->penalty) && $result->penalty['home'] != '' && $result->penalty['away'] != '' ) ? true : false;
							$result->hadOvertime = ( isset($result->overtime) && $result->overtime['home'] != '' && $result->overtime['away'] != '' ) ? true : false;
							if ( $result->hadPenalty ) {
								$result->homeScore = $result->penalty['home']+$result->overtime['home'];
								$result->awayScore = $result->penalty['away']+$result->overtime['away'];
							} elseif ( $result->hadOvertime ) {
								$result->homeScore = $result->overtime['home'];
								$result->awayScore = $result->overtime['away'];
							} else {
								$result->homeScore = $result->home_points;
								$result->awayScore = $result->away_points;
							}

							$homeTeam = $leaguemanager->getTeam( $result->home_team );
							$awayTeam = $leaguemanager->getTeam( $result->away_team );
							$homeTeamName = $homeTeam->title;
							$awayTeamName = $awayTeam->title;
							$homeTeamScore = $result->homeScore;
							$awayTeamScore = $result->awayScore;
							$myMatchDate = mysql2date(get_option('date_format'), $result->date);
							$tooltipTitle = $homeTeamScore.':'.$awayTeamScore. ' - '.$homeTeamName.' - '.$awayTeamName.' ['.$myMatchDate.']';
							if ($team->id == $result->home_team) {
								if ($result->homeScore > $result->awayScore)
								{
									$last5 .= '<span class="W last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								}
								elseif ($result->homeScore < $result->awayScore)
								{
									$last5 .= '<span class="L last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								}
								elseif ($result->homeScore == $result->awayScore)
								{
									$last5 .= '<span class="D last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								}
							} elseif ($team->id == $result->away_team) {
								if ($result->homeScore < $result->awayScore) {
									$last5 .= '<span class="W last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								} elseif ($result->homeScore > $result->awayScore) {
									$last5 .= '<span class="L last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								} elseif ($result->homeScore == $result->awayScore) {
									$last5 .= '<span class="D last5-bg" title="'.$tooltipTitle.'">&nbsp;</span>';
								}
							}
						}

						// Close the td tag
						$last5 .= '</td>';
						echo $last5;
					?>


					</tr>
				<?php } ?>
			</tbody>
		</table>

	<?php } ?>
<?php } ?>
<script>
jQuery(document).ready(function() {
					   
					   jQuery("a.last5-bg").click(function(e) {
													  e.stopPropagation();
													  e.preventDefault();
													  var params2 = '';
													  var inputs = document.getElementsByTagName('input');
													  var values = [];
													  for(i=0;i<inputs.length;i++) {
													  var input = inputs[i];
													  if (input.name !== '') {
													  params2 += '&' + input.name + '=' + input.value;
													  }
													  }
													  var selects = document.getElementsByTagName('select');
													  values = [];
													  for(i=0;i<selects.length;i++) {
													  var select = selects[i];
													  if (select.name !== '') {
													  params2 += '&' + select.name + '=' + select.value;
													  }
													  }
													  var href = this.href;
													  var parts = href.split('?');
													  var url = parts[0];
													  parts[1] += params2 ;
													  var params = parts[1].split('&');
													  var pp, inputsnew = '';
													  for(var i = 0, n = params.length; i < n; i++) {
													  pp = params[i].split('=');
													  inputsnew += '<input type="hidden" name="' + pp[0] + '" value="' + pp[1] + '" />';
													  }
													  jQuery("body").append('<form action="'+url+'" method="post" id="poster">'+inputsnew+'</form>');
													  jQuery("#poster").submit();
													  });
					   });
</script>
