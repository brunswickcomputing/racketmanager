<?php
/**
* AJAX response methods
*
*/

/**
* Implement AJAX responses
*
* @author Kolja Schleich
* @author Paul Moffat
* @package    RacketManager
* @subpackage RacketManagerAJAX
*/
class RacketManagerAJAX extends RacketManager {
	/**
	* register ajax actions
	*/
	public function __construct() {
		add_action( 'wp_ajax_racketmanager_getCaptainName', array(&$this, 'getCaptainName') );
		add_action( 'wp_ajax_racketmanager_getPlayerDetails', array(&$this, 'getPlayerDetails') );
		add_action( 'wp_ajax_racketmanager_add_teamplayer_from_db', array(&$this, 'addTeamPlayerFromDB') );
		add_action( 'wp_ajax_racketmanager_save_team_standings', array(&$this, 'saveTeamStandings') );
		add_action( 'wp_ajax_racketmanager_save_add_points', array(&$this, 'saveAddPoints') );
		add_action( 'wp_ajax_racketmanager_insert_home_stadium', array(&$this, 'insertHomeStadium') );
		add_action( 'wp_ajax_racketmanager_set_match_day_popup', array(&$this, 'setMatchDayPopUp') );
		add_action( 'wp_ajax_racketmanager_set_match_date', array(&$this, 'setMatchDate') );
		add_action( 'wp_ajax_racketmanager_checkTeamExists', array(&$this, 'checkTeamExists') );

		// admin/admin.php
		add_action( 'wp_ajax_racketmanager_get_season_dropdown', array(&$this, 'setSeasonDropdown') );
		add_action( 'wp_ajax_racketmanager_get_match_dropdown', array(&$this, 'setMatchesDropdown') );

		add_action( 'wp_ajax_racketmanager_matchcard_team', array(&$this, 'printMatchCardTeam') );
		add_action( 'wp_ajax_nopriv_racketmanager_matchcard_team', array(&$this, 'printMatchCardTeam') );

		add_action( 'wp_ajax_racketmanager_matchcard_player', array(&$this, 'printMatchCardPlayer') );
		add_action( 'wp_ajax_nopriv_racketmanager_matchcard_player', array(&$this, 'printMatchCardPlayer') );

		add_action( 'wp_ajax_racketmanager_show_match', array(&$this, 'showMatch') );
		add_action( 'wp_ajax_racketmanager_update_match', array(&$this, 'updateMatch') );

		add_action( 'wp_ajax_racketmanager_show_rubbers', array(&$this, 'showRubbers') );
		add_action( 'wp_ajax_nopriv_racketmanager_show_rubbers', array(&$this, 'showRubbers') );
		add_action( 'wp_ajax_racketmanager_update_rubbers', array(&$this, 'updateRubbers') );
		add_action( 'wp_ajax_racketmanager_confirm_results', array(&$this, 'confirmResults') );

		add_action( 'wp_ajax_racketmanager_club_player_request', array(&$this, 'playerRequest') );
		add_action( 'wp_ajax_racketmanager_club_players_remove', array(&$this, 'rosterRemove') );

		add_action( 'wp_ajax_racketmanager_team_update', array(&$this, 'updateTeam') );
		add_action( 'wp_ajax_racketmanager_update_club', array(&$this, 'updateClub') );
		add_action( 'wp_ajax_racketmanager_update_player', array(&$this, 'updatePlayer') );

		add_action( 'wp_ajax_racketmanager_tournament_entry', array(&$this, 'tournamentEntryRequest') );

		add_action( 'wp_ajax_racketmanager_notify_teams', array(&$this, 'notifyTeams') );
		add_action( 'wp_ajax_racketmanager_get_team_info', array(&$this, 'getTeamCompetitionInfo') );
		add_action( 'wp_ajax_racketmanager_cup_entry', array(&$this, 'cupEntryRequest') );
		add_action( 'wp_ajax_racketmanager_league_entry', array(&$this, 'leagueEntryRequest') );
		add_action( 'wp_ajax_racketmanager_notify_entries_open', array(&$this, 'notifyEntriesOpen') );
		add_action( 'wp_ajax_racketmanager_notify_tournament_entries_open', array(&$this, 'notifyTournamentEntriesOpen') );
		add_action( 'wp_ajax_racketmanager_chase_match_result', array(&$this, 'chaseMatchResult') );
		add_action( 'wp_ajax_racketmanager_chase_match_approval', array(&$this, 'chaseMatchApproval') );
		add_action( 'wp_ajax_racketmanager_send_fixtures', array(&$this, 'sendFixtures') );

		add_action( 'wp_ajax_racketmanager_add_favourite', array(&$this, 'addFavourite') );
	}

	/**
	* Ajax Response to get player information
	*
	*/
	public function getPlayerDetails() {
		global $wpdb, $racketmanager;
		$name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';

		$sql = "SELECT  P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` as playerId, P.`user_email` FROM $wpdb->racketmanager_club_players R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND C.`id` = R.`affiliatedclub` AND `display_name` like '%s' ORDER BY 1,2,3";
		$sql = $wpdb->prepare($sql, $name);
		$results = $wpdb->get_results($sql);
		$players = array();
		$player = array();
		foreach( $results as $r) {
			$player['label'] = addslashes($r->fullname).' - '.$r->club;
			$player['value'] = addslashes($r->fullname);
			$player['id'] = $r->rosterId;
			$player['clubId'] = $r->clubId;
			$player['club'] = $r->club;
			$player['playerId'] = $r->playerId;
			$player['user_email'] = $r->user_email;
			$player['contactno'] = get_user_meta($r->playerId, 'contactno', true);
			array_push($players, $player);
		}
		die(json_encode($players));
	}

	/**
	* Ajax Response to get captain information
	*
	*/
	public function getCaptainName() {
		global $wpdb;

		$name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';
		$affiliatedClub = isset($_POST['affiliatedClub']) ? $_POST['affiliatedClub'] : '';

		$sql = "SELECT P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` AS `playerId`, P.`user_email` FROM $wpdb->racketmanager_club_players R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND  C.`id` = R.`affiliatedclub` AND C.`id` = '%s' AND `display_name` like '%s' ORDER BY 1,2,3";
		$sql = $wpdb->prepare($sql, $affiliatedClub, $name);
		$results = $wpdb->get_results($sql);
		$captains = array();
		$captain = array();
		foreach( $results as $r) {
			$captain['label'] = addslashes($r->fullname).' - '.$r->club;
			$captain['value'] = addslashes($r->fullname);
			$captain['id'] = $r->playerId;
			$captain['clubId'] = $r->clubId;
			$captain['club'] = $r->club;
			$captain['user_email'] = $r->user_email;
			$captain['contactno'] = get_user_meta($r->playerId, 'contactno', true);
			array_push($captains, $captain);
		}
		die(json_encode($captains));
	}

	/**
	* Ajax Response to save team standings
	*
	*/
	public function saveTeamStandings() {
		global $wpdb, $lmLoader, $racketmanager, $league;
		$ranking = $_POST['ranking'];
		$teams = $league->getRanking($ranking);
		foreach ( $teams as $rank => $teamId ) {
			$old = get_team( $teamId );
			$oldRank = $old->rank;

			if ( $oldRank != 0 ) {
				if ( $rank == $oldRank ) {
					$status = '&#8226;';
				} elseif ( $rank < $oldRank ) {
					$status = '&#8593';
				} else {
					$status = '&#8595';
				}
			} else {
				$status = '&#8226;';
			}

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `rank` = '%d', `status` = '%s' WHERE `team_id` = '%d'", $rank, $status, $teamId ) );
		}
	}

	/**
	* AJAX response to manually set additional points
	*
	* @see admin/standings.php
	*/
	public function saveAddPoints() {
		global $wpdb;

		$teamId = intval($_POST['team_id']);
		$league = get_league(intval($_POST['league_id']));
		$season = $league->getSeason();
		$addPoints = $_POST['points'];

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `add_points` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d' AND `season` = '%s'", $addPoints, $teamId, $league->id, $season ) );
		$league->_rankTeams($league->id);

		die("jQuery('#loading_".$teamId."').fadeOut('fast'); window.location.reload(true);");
	}

	/**
	* AJAX response to get team data from database and insert into player team edit form
	*
	* @see admin/team.php
	*/
	public function addTeamPlayerFromDB() {
		global $racketmanager;

		$teamId = (int)$_POST['team_id'];
		$team = get_team( $teamId );
		$return = "document.getElementById('team_id').value = ".$teamId.";document.getElementById('team').value = '".$team->title."';document.getElementById('affiliatedclub').value = ".$team->affiliatedclub.";document.getElementById('teamPlayer1').value = '".$team->player[1]."';document.getElementById('teamPlayerId1').value = ".$team->playerId[1].";";
		if ( isset($team->player[2]) ) {
			$return .= "document.getElementById('teamPlayer2').value = '".$team->player[2]."';document.getElementById('teamPlayerId2').value = ".$team->playerId[2].";";
		}

		die($return);
	}

	/**
	* insert home team stadium if available
	*
	* @see admin/match.php
	*/
	public function insertHomeStadium() {

		$teamId = (int)$_POST['team_id'];

		$team = get_team( $teamId );

		if ($team) {
			$stadium = trim($team->stadium);
		} else {
			$stadium = "";
		}
		die($stadium);
	}

	/**
	* change all Match Day Pop-ups to match first one set
	*
	* @see admin/match.php
	*/
	public function setMatchDayPopUp() {
		global $racketmanager;
		$matchDay = (int)$_POST['match_day'];
		$i = (int)$_POST['i'];
		$maxMatches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

		if ( $i == 0 && $mode == 'add') {
			$myAjax = "";
			for ( $xx = 1; $xx < $maxMatches; $xx++ ) {
				$myAjax .= "document.getElementById('match_day_".$xx."').value = '".$matchDay."'; ";
			}
			die("".$myAjax."");
		}
	}

	/**
	* change all Match Date fields to match first one set
	*
	* @see admin/match.php
	*/
	public function setMatchDate() {
		global $racketmanager;
		$matchDate = htmlspecialchars($_POST['match_date']);
		$i = (int)$_POST['i'];
		$maxMatches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

		if ( $i == 0 && $mode == 'add' ) {
			$myAjax = "";
			for ( $xx = 1; $xx < $maxMatches; $xx++ ) {
				$myAjax .= "document.getElementById('mydatepicker[".$xx."]').value = '".$matchDate."'; ";
			}
			die("".$myAjax."");
		}
	}

	/**
	* set season dropdown for post metabox for match report
	*
	* @see admin/admin.php
	*/
	public function setSeasonDropdown() {
		$league = get_league(intval($_POST['league_id']));
		$html = $league->getSeasonDropdown(true);
		die($html);
	}

	/**
	* set matches dropdown for post metabox for match report
	*
	* @see admin/admin.php
	*/
	public function setMatchesDropdown() {
		$league = get_league(intval($_POST['league_id']));
		$league->setSeason(htmlspecialchars($_POST['season']));
		$html = $league->getMatchDropdown();

		die($html);
	}

	/**
	* Ajax Response to get check if Team Exists
	*
	*/
	public function checkTeamExists() {
		global $racketmanager;

		$name = stripslashes($_POST['name']);
		$team = $racketmanager->getTeamId($name);
		if ($team) {
			$found = true;
		} else {
			$found = false;
		}
		die($found);
	}

	/**
	* build screen to view match rubbers for printing
	*
	*/
	public function printMatchCardTeam() {
		global $racketmanager, $championship;
		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$league = get_league($match->league_id);
		$numSets = $league->num_sets;
		$pointsspan = 2 + intval($numSets);
		$sponsorhtml = sponsor_level_cat_func(array("columns" => 1, "title" => 'no', "bio" => 'no', "link" => 'no'), "");
		?>
		<div id="matchrubbers" class="rubber-block">
			<div id="matchheader">
				<div class="leaguetitle"><?php echo $league->title ?></div>
				<div class="matchdate"><?php echo substr($match->date,0,10) ?></div>
				<div class="matchday">
					<?php if ( $league->mode == 'championship' ) {
						echo $league->championship->getFinalName($match->final_round);
					} else {
						echo 'Week'.$match->match_day;
					}?>
				</div>
				<div class="matchtitle"><?php echo $match->match_title ?></div>
			</div>
			<form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'rubbers-match' ) ?>

				<table class="widefat">
					<caption><?php _e('Match card', 'racketmanager') ?></caption>
					<thead>
						<tr>
							<th style="text-align: center;"><?php _e( 'Pair', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Home Team', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="<?php echo $numSets ?>"><?php _e('Sets', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Away Team', 'racketmanager' ) ?></th>
						</tr>
					</thead>
					<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

						<?php
						$rubbers = $match->getRubbers();
						$r = 0 ;

						foreach ($rubbers as $rubber) {
							?>
							<tr class="rtr">
								<td rowspan="3" class="rtd centered">
									<?php echo isset($rubber->rubber_number) ? $rubber->rubber_number : '' ?>
								</td>
								<td class="rtd">
									<input class="player" name="homeplayer1[<?php echo $r ?>]" id="homeplayer1_<?php echo $r ?>" />
								</td>

								<?php for ( $i = 1; $i <= $numSets; $i++ ) { ?>
									<td rowspan="2" class="rtd">
										<input class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" />
										:
										<input class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" />
									</td>
								<?php } ?>

								<td class="rtd">
									<input class="player" name="awayplayer1[<?php echo $r ?>]" id="awayplayer1_<?php echo $r ?>" />
								</td>
							</tr>
							<tr class="rtr">
								<td class="rtd">
									<input class="player" name="homeplayer2[<?php echo $r ?>]" id="homeplayer2_<?php echo $r ?>" />
								</td>
								<td class="rtd">
									<input class="player" name="awayplayer2[<?php echo $r ?>]" id="awayplayer2_<?php echo $r ?>">
								</td>
							</tr>
							<tr>
								<td colspan="<?php echo $pointsspan ?>" class="rtd" style="text-align: center;">
									<input class="points" type="text" size="2" id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
									:
									<input class="points" type="text" size="2" id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
								</td>
							</tr>
							<?php
							$r ++;
						}
						?>
						<tr>
							<td class="rtd centered">
							</td>
							<td class="rtd">
								<input class="player" name="homesig" id="homesig" placeholder="Home Captain Signature" />
							</td>
							<td colspan="<?php echo intval($numSets) ?>" class="rtd" style="text-align: center;">
								<input class="points" type="text" size="2" id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
								:
								<input class="points" type="text" size="2" id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
							</td>
							<td class="rtd">
								<input class="player" name="awaysig" id="awaysig" placeholder="Away Captain Signature" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php echo $sponsorhtml ?>
		</div>
		<?php
		die();
	}

	/**
	* build screen to print matchcard for players
	*
	*/
	public function printMatchCardPlayer() {
		global $racketmanager, $championship;
		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$league = get_league($match->league_id);
		$numSets = $league->num_sets;
		$sponsorhtml = sponsor_level_cat_func(array("columns" => 1, "title" => 'no', "bio" => 'no', "link" => 'no'), "");
		?>
		<div id="matchrubbers" class="rubber-block">
			<div id="matchheader">
				<div class="leaguetitle"><?php echo $league->title ?></div>
				<div class="matchdate"><?php echo substr($match->date,0,10) ?></div>
				<div class="matchday">
					<?php if ( $league->mode == 'championship' ) {
						echo $league->championship->getFinalName($match->final_round);
					} else {
						echo 'Week'.$match->match_day;
					}?>
				</div>
				<div class="matchtitle">
					<?php if ( $league->mode != 'championship' ) {
						echo $match->match_title;
					}
				?>
				</div>
			</div>
			<form id="match-view" action="#" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'rubbers-match' ) ?>

				<table class="widefat">
				<caption><?php _e('Match card', 'racketmanager') ?></caption>
					<thead>
						<tr>
							<th style="text-align: center;" colspan="1"><?php _e( 'Team', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="<?php echo $numSets ?>"><?php _e('Sets', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Team', 'racketmanager' ) ?></th>
						</tr>
					</thead>
					<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

						<tr class="rtr">
							<td class="rtd">
								<?php echo $match->teams['home']->title ?>
							</td>

							<?php for ( $i = 1; $i <= $numSets; $i++ ) { ?>
								<td class="rtd">
									<input class="points" type="text" size="2" id="set_<?php echo $i ?>_player1" name="custom[sets][<?php echo $i ?>][player1]" />
									:
									<input class="points" type="text" size="2" id="set_<?php echo $i ?>_player2" name="custom[sets][<?php echo $i ?>][player2]" />
								</td>
							<?php } ?>

							<td class="rtd">
								<?php echo $match->teams['away']->title ?>
							</td>
						</tr>
						<tr>
							<td class="rtd">
								<input class="player" name="homesig" id="homesig" placeholder="Home Captain Signature" />
							</td>
							<td colspan="<?php echo intval($numSets) ?>" class="rtd" style="text-align: center;">
								<input class="points" type="text" size="2" id="home_points" name="home_points" />
								:
								<input class="points" type="text" size="2" id="away_points" name="away_points" />
							</td>
							<td class="rtd">
								<input class="player" name="awaysig" id="awaysig" placeholder="Away Captain Signature" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php echo $sponsorhtml ?>
		</div>
		<?php
		die();
	}

	/**
	* build screen to allow input of scores for match
	*
	*/
	public function showMatch() {
		global $racketmanager;
		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$racketmanager->showMatchScreen($match);
		die();
	}

	/**
	* update match scores
	*
	*/
	public function updateMatch() {
		global $racketmanager, $league, $match;

		if ( isset($_POST['updateMatch'])) {
			check_admin_referer('scores-match');
			$return = array();
			$matchId = $_POST['current_match_id'];
			$match = get_match($matchId);
			$league = get_league($match->league_id);
			$matchConfirmed = 'P';
			$matches[$matchId] = $matchId;
			$homePoints[$matchId] = 0;
			$awayPoints[$matchId] = 0;
			$homeTeam[$matchId] = $_POST['home_team'];
			$awayTeam[$matchId] = $_POST['away_team'];
			$custom[$matchId] = $_POST['custom'];
			$season[$matchId] = $_POST['current_season'];

			$errMsg = array();
			$errField = array();
			$error = false;

			$setPrefix = 'set_';

			$matchValidate = $this->validateMatchScore($match, $custom[$matchId], $setPrefix, $errMsg, $errField);
			$error = $matchValidate[0];
			$errMsg = $matchValidate[1];
			$errField = $matchValidate[2];
			$matchMessage = implode('<br>', $errMsg);

			if ( !$error ) {
				$matchCount = $league->_updateResults( $matches, $homePoints, $awayPoints, $homeTeam, $awayTeam, $custom, $season, $_POST['match_round'], $matchConfirmed );
				if ( $matchCount > 0 ) {
					$matchMessage = __( 'Result saved', 'racketmanager' );
					$match = get_match($matchId);
					$homePoints[$matchId] = $match->home_points;
					$awayPoints[$matchId] = $match->away_points;
					$this->resultNotification($matchConfirmed, $matchMessage, $match);
				} else {
					$matchMessage = __('No result to save','racketmanager');
				}
			}

			array_push($return,$matchMessage,$homePoints[$matchId],$awayPoints[$matchId],$error,$errField);

			die(json_encode($return));
		} else {
			die(0);
		}
	}

	/**
	* build screen to allow input of match rubber scores
	*
	*/
	public function showRubbers() {
		global $racketmanager, $league, $match;

		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$racketmanager->showRubbersScreen($match);
		die();
	}

	/**
	* update match rubber scores
	*
	*/
	public function updateRubbers() {
		global $racketmanager, $league, $match, $matchRubbers;

		if ( isset($_POST['updateRubber'])) {
			check_admin_referer('rubbers-match');
			$updatedRubbers = '';
			$return = array();
			$msg = '';
			$errField = array();
			$error = false;
			$matchId = $_POST['current_match_id'];
			$match = get_match($matchId);
			$matchRubbers = array();
			$matchRubbers['homepoints'] = isset($_POST['home_points']) ? $_POST['home_points'] : array();
			$matchRubbers['awaypoints'] = isset( $_POST['away_points']) ? $_POST['away_points'] : array();
			$numRubbers = $_POST['num_rubbers'];
			$homeClub = $_POST['home_club'];
			$homeTeam = $_POST['home_team'];
			$awayClub = $_POST['away_club'];
			$awayTeam = $_POST['away_team'];
			$rmOptions = $racketmanager->getOptions();
			$matchConfirmed = '';
			$userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
			$userCanUpdate = $userCanUpdateArray[0];
			$userType = $userCanUpdateArray[1];
			$userTeam = $userCanUpdateArray[2];
			$resultConfirmation = $rmOptions[$match->league->competitionType]['resultConfirmation'];
			$matchComments = isset($_POST['resultConfirmComments']) ? $_POST['resultConfirmComments'] : '';
			$matchCommentsHome = isset($_POST['resultConfirmCommentsHome']) ? $_POST['resultConfirmCommentsHome'] : '';
			$matchCommentsAway = isset($_POST['resultConfirmCommentsAway']) ? $_POST['resultConfirmCommentsAway'] : '';
			if ($matchCommentsHome) { $matchComments = $match->comments.PHP_EOL.__('Home:','racketmanager').':'.$matchCommentsHome; }
			if ($matchCommentsAway) { $matchComments = $match->comments.PHP_EOL.__('Away:','racketmanager').':'.$matchCommentsAway; }
			if ( $_POST['updateRubber'] == 'results' ) {
				if ( $userCanUpdate ) {
					$playerFound = false;
					if ( $userType == 'player' ) {
						if ( $userTeam == 'home' || $userTeam == 'both' ) {
							if ( $match->teams['home']->captainId == get_current_user_id() ) {
								$playerFound = true;
							}
							$clubId = $match->teams['home']->affiliatedclub;
						} else {
							if ( $match->teams['away']->captainId == get_current_user_id() ) {
								$playerFound = true;
							}
							$clubId = $match->teams['away']->affiliatedclub;
						}
						if ( !$playerFound ) {
							$club = get_club($clubId);
							$clubPlayer = $club->getPlayers( array('player' => get_current_user_id(), 'inactive' => true) );
							$clubPlayerId = $clubPlayer[0]->roster_id;
							for ($ix = 1; $ix <= $numRubbers; $ix++) {
								$homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : null;
								$homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : null;
								$awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : null;
								$awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : null;
								if ( $userTeam == 'home' || $userTeam == 'both' ) {
									if ( $clubPlayerId == $homeplayer1 || $clubPlayerId == $homeplayer2 ) {
										$playerFound = true;
									}
								}
								if ( $userTeam == 'away' || $userTeam == 'both' ) {
									if ( $clubPlayerId == $awayplayer1 || $clubPlayerId == $awayplayer2 ) {
										$playerFound = true;
									}
								}
							}
						}
						if ( !$playerFound ) {
							$userCanUpdate = false;
							$msg = __('Player cannot submit results', 'racketmanager');
							$error = true;
						}
					}
				}
				if ( $userCanUpdate ) {
					$rubberResult = $this->updateRubberResults( $match, $numRubbers, $rmOptions);
					$error = $rubberResult[0];
					$matchConfirmed = $rubberResult[1];
					$errMsg = $rubberResult[2];
					$errField = $rubberResult[3];
					$updatedRubbers = $rubberResult[4];
					$msg = implode('<br>', $errMsg);
				}
			} elseif ( $_POST['updateRubber'] == 'confirm' ) {
				$matchConfirmed = $this->confirmRubberResults();
			}

			if ( !$error ) {
				if ( $matchConfirmed ) {
					$matchUpdatedby = $this->updateMatchStatus( $matchId, $matchConfirmed, $homeClub, $awayClub, $matchComments, $userTeam );
					switch ( $matchConfirmed ) {
						case "A":
						$matchMessage = 'Result Approved';
						break;
						case "C":
						$matchMessage = 'Result Challenged';
						break;
						case "P":
						$matchMessage = 'Result Saved';
						break;
						default:
						$matchConfirmed = '';
					}

					$msg = sprintf(__('%s','racketmanager'), $matchMessage);
					if ( ( $matchConfirmed == 'A' && $resultConfirmation == 'auto' ) || ( $userType == 'admin' ) ) {
						$leagueId = $_POST['current_league_id'];
						$league = get_league($leagueId);
						$matchId = $_POST['current_match_id'];
						$matches[$matchId] = $matchId;
						$homePoints[$matchId] = array_sum($matchRubbers['homepoints']);
						$awayPoints[$matchId] = array_sum($matchRubbers['awaypoints']);
						$homeTeam[$matchId] = $homeTeam;
						$awayTeam[$matchId] = $awayTeam;
						$custom[$matchId] = array();
						$season = $_POST['current_season'];
						if ( $league->is_championship ) {
							$round = $league->championship->getFinals($_POST['match_round'])['round'];
							$league->championship->updateFinalResults( $matches, $homePoints, $awayPoints, $homeTeam, $awayTeam, $custom, $round, $season  );
							$msg = __('Match saved','racketmanager');
							$updated = true;
						} else {
							$matchCount = $league->_updateResults( $matches, $homePoints, $awayPoints, $homeTeam, $awayTeam, $custom, $season );
							if ( $matchCount > 0 ) {
								$msg = sprintf(__('Saved Results of %d matches','racketmanager'), $matchCount);
								$updated = true;
							} else {
								$msg = __('No matches to save','racketmanager');
								$updated = false;
							}
						}
						if ( $userType != 'admin' ) {
							if ( $updated ) {
								$matchConfirmed = 'Y';
							}
							$this->resultNotification($matchConfirmed, $matchMessage, $match, $matchUpdatedby);
						}
					} elseif ( $matchConfirmed == 'A' ) {
						$this->resultNotification($matchConfirmed, $matchMessage, $match, $matchUpdatedby);
					} elseif ( $matchConfirmed == 'C' ) {
						$this->resultNotification($matchConfirmed, $matchMessage, $match, $matchUpdatedby);
					} elseif ( !current_user_can( 'manage_racketmanager' ) && $matchConfirmed == 'P' ) {
						$this->resultNotification($matchConfirmed, $matchMessage, $match, $matchUpdatedby);
					}
				} else {
					if ( !$msg ) {
						$msg = __('No results to save','racketmanager');
						$error = true;
					}
				}
			}
			array_push($return,$msg,$error,$matchRubbers['homepoints'],$matchRubbers['awaypoints'], $errField, $updatedRubbers);

			die(json_encode($return));
		} else {
			die(0);
		}
	}

	/**
	* update results for each rubber
	*
	*/
	public function updateRubberResults( $match, $numRubbers, $options ) {
		global $wpdb, $racketmanager, $league, $match, $matchRubbers;

		$return = array();
		$error = false;
		$errMsg = array();
		$errField = array();
		$matchConfirmed = '';
		$homeTeamScore = 0;
		$awayTeamScore = 0;
		$players = array();
		$playerOptions = $racketmanager->getOptions('player');
		$club = get_club($match->teams['home']->affiliatedclub);
		$player['walkover']['male']['home'] = $club->getPlayer($playerOptions['walkover']['male']);
		$player['walkover']['female']['home'] = $club->getPlayer($playerOptions['walkover']['female']);
		$player['noplayer']['male']['home'] = $club->getPlayer($playerOptions['noplayer']['male']);
		$player['noplayer']['female']['home'] = $club->getPlayer($playerOptions['noplayer']['female']);
		$player['share']['male']['home'] = $club->getPlayer($playerOptions['share']['male']);
		$player['share']['female']['home'] = $club->getPlayer($playerOptions['share']['female']);
		$club = get_club($match->teams['away']->affiliatedclub);
		$player['walkover']['male']['away'] = $club->getPlayer($playerOptions['walkover']['male']);
		$player['walkover']['female']['away'] = $club->getPlayer($playerOptions['walkover']['female']);
		$player['noplayer']['male']['away'] = $club->getPlayer($playerOptions['noplayer']['male']);
		$player['noplayer']['female']['away'] = $club->getPlayer($playerOptions['noplayer']['female']);
		$player['share']['male']['away'] = $club->getPlayer($playerOptions['share']['male']);
		$player['share']['female']['away'] = $club->getPlayer($playerOptions['share']['female']);
		$updatedRubbers = array();

		$match = get_match($match->id);
		$match->delResultCheck();

		for ($ix = 1; $ix <= $numRubbers; $ix++) {
			$rubberId       = $_POST['id'][$ix];
			$walkover 		= '';
			$share			= false;
			$homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : null;
			$homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : null;
			$awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : null;
			$awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : null;
			$custom         = isset($_POST['custom'][$ix]) ? $_POST['custom'][$ix] : "";
			$winner         = $loser = '';

			$homescore = 0;
			$awayscore = 0;
			$setPrefix = 'set_'.$ix.'_';
			$validateMatch = true;
			$playoff = false;

			if ( isset($_POST['sharedRubber'][$ix]) ) {
				if ( isset($_POST['walkoverHome'][$ix]) || isset($_POST['walkoverAway'][$ix]) ) {
					$errField[] = 'sharedRubber_'.$ix;
					$errMsg[] = __('Share and walkover not allowed in same rubber', 'racketmanager');
					$error = true;
					$validateMatch = false;
				} else {
					$share = true;
					if ( $match->league->type == 'MD' ) {
						$homeplayer1 = $player['share']['male']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['share']['male']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'WD') {
						$homeplayer1 = $player['share']['female']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['share']['female']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'XD') {
						$homeplayer1 = $player['share']['male']['home']->roster_id;
						$homeplayer2 = $player['share']['female']['home']->roster_id;
						$awayplayer1 = $player['share']['male']['away']->roster_id;
						$awayplayer2 = $player['share']['female']['away']->roster_id;
					}
				}
			}
			if ( isset($_POST['walkoverHome'][$ix]) ) {
				$walkover = 'home';
				if ( isset($_POST['walkoverAway'][$ix]) ) {
					$errField[] = 'walkoverHome_'.$ix;
					$errMsg[] = __('Both teams cannot have a walkover', 'racketmanager');
					$error = true;
					$validateMatch = false;
				} else {
					if ( $match->league->type == 'MD' ) {
						$homeplayer1 = $player['walkover']['male']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['noplayer']['male']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'WD') {
						$homeplayer1 = $player['walkover']['female']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['noplayer']['female']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'XD') {
						$homeplayer1 = $player['walkover']['male']['home']->roster_id;
						$homeplayer2 = $player['walkover']['female']['home']->roster_id;
						$awayplayer1 = $player['noplayer']['male']['away']->roster_id;
						$awayplayer2 = $player['noplayer']['female']['away']->roster_id;
					}
				}
			} else {
				if ( isset($_POST['walkoverAway'][$ix]) ) {
					$walkover = 'away';
					if ( $match->league->type == 'MD' ) {
						$homeplayer1 = $player['noplayer']['male']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['walkover']['male']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'WD') {
						$homeplayer1 = $player['noplayer']['female']['home']->roster_id;
						$homeplayer2 = $homeplayer1;
						$awayplayer1 = $player['walkover']['female']['away']->roster_id;
						$awayplayer2 = $awayplayer1;
					} elseif ( $match->league->type == 'XD') {
						$homeplayer1 = $player['noplayer']['male']['home']->roster_id;
						$homeplayer2 = $player['noplayer']['female']['home']->roster_id;
						$awayplayer1 = $player['walkover']['male']['away']->roster_id;
						$awayplayer2 = $player['walkover']['female']['away']->roster_id;
					}
				}
			}
			if (isset($match->league->scoring) && ($match->league->scoring == 'TP' || $match->league->scoring == 'MP') && $ix == $numRubbers ) {
				if ( $homeTeamScore != $awayTeamScore ) {
					$validateMatch = false;
				} else {
					$playoff = true;
				}
			}
			if ( $validateMatch ) {
				$playerTypes = array('homeplayer1', 'homeplayer2', 'awayplayer1', 'awayplayer2');
				foreach ($playerTypes as $type) {
					if ( empty($$type) ) {
						$errField[] = $type.'_'.$ix;
						$errMsg[] = __('Player not selected', 'racketmanager');
						$error = true;
					} else {
						$playerRef = $$type;
						$rosterEntry = $racketmanager->getClubPlayer($playerRef);
						if ( !$rosterEntry->system_record ) {
							$playerFound = array_search($playerRef, $players);
							if ( $playerFound === false ) {
								if ( $playoff ) {
									$errField[] = $type.'_'.$ix;
									$errMsg[] = __('Player for playoff must have played', 'racketmanager');
									$error = true;
								} else {
									$players[] = $playerRef;
								}
							} else {
								if ( !$playoff ) {
									$errField[] = $type.'_'.$ix;
									$errMsg[] = __('Player already selected', 'racketmanager');
									$error = true;
								}
							}
						}
					}
				}
				$rubberNumber = $ix;
				$matchValidate = $this->validateMatchScore($match, $custom, $setPrefix, $errMsg, $errField, $rubberNumber, $walkover, $share);
				$error = $matchValidate[0];
				$errMsg = $matchValidate[1];
				$errField = $matchValidate[2];
				$homescore = $matchValidate[3];
				$awayscore = $matchValidate[4];
				$sets = $matchValidate[5];
				$custom['sets'] = $sets;
				if ( $walkover ) {
					$custom['walkover'] = $walkover;
				}
				if ( $share ) {
					$custom['share'] = true;
				}
				if ( is_numeric($homescore) ) {
					$homeTeamScore += $homescore;
				}
				if ( is_numeric($awayscore) ) {
					$awayTeamScore += $awayscore;
				}

				if ( !$error ) {
					if ( $homescore > $awayscore) {
						$winner = $match->home_team;
						$loser = $match->away_team;
					} elseif ( $homescore < $awayscore) {
						$winner = $match->away_team;
						$loser = $match->home_team;
					} elseif ( 'NULL' === $homescore && 'NULL' === $awayscore ) {
						$winner = 0;
						$loser = 0;
					} else {
						$winner = -1;
						$loser = -1;
					}
					if (isset($homeplayer1) && isset($homeplayer2) && isset($awayplayer1) && isset($awayplayer2) && ( !empty($homescore) || !empty($awayscore) ) ) {
						$homescore = !empty($homescore) ? $homescore : 0;
						$awayscore = !empty($awayscore) ? $awayscore : 0;
						$matchRubbers['homepoints'][$ix] = $homescore;
						$matchRubbers['awaypoints'][$ix] = $awayscore;

						$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_rubbers} SET `home_points` = '%s',`away_points` = '%s',`home_player_1` = '%s',`home_player_2` = '%s',`away_player_1` = '%s',`away_player_2` = '%s',`winner_id` = '%d',`loser_id` = '%d',`custom` = '%s' WHERE `id` = '%d'", $homescore, $awayscore, $homeplayer1, $homeplayer2, $awayplayer1, $awayplayer2, $winner, $loser, maybe_serialize($custom), $rubberId));
						$matchConfirmed = 'P';

						$checkOptions = $options['checks'];
						$playerOptions = $options['player'];
						$rubberPlayers = array($homeplayer1, $homeplayer2, $awayplayer1, $awayplayer2);
						foreach ( $rubberPlayers as $playerRef ) {
							if ( !empty($playerRef) ) {
								$this->checkPlayerResult($match, $rubberId, $playerRef, $match->home_team, $checkOptions, $playerOptions);
							}
						}
						$updatedRubbers[$rubberId]['players']['home'][] = $homeplayer1;
						$updatedRubbers[$rubberId]['players']['home'][] = $homeplayer2;
						$updatedRubbers[$rubberId]['players']['away'][] = $awayplayer1;
						$updatedRubbers[$rubberId]['players']['away'][] = $awayplayer2;
						$updatedRubbers[$rubberId]['sets'] = $sets;
					}
				}
			}
		}

		array_push($return, $error, $matchConfirmed, $errMsg, $errField, $updatedRubbers);
		return $return;
	}

	/**
	* validate Match Score
	*
	*/
	public function validateMatchScore($match, $custom, $setPrefixStart, $errMsg, $errField, $rubberNumber=false, $walkover=false, $share=false) {

		$numSetstoWin = $match->league->numSetstoWin;
		$sets = $custom['sets'];
		$return = array();
		$homescore = 0;
		$awayscore = 0;
		$error = false;
		$scoring = isset($match->league->scoring) ? $match->league->scoring : 'TB';
		$s = 1;
		$setsUpdated = array();
		foreach ( $sets as $set ) {
			$setPrefix = $setPrefixStart.$s.'_';
			if ( $scoring == 'TB' ) {
				$setType = 'tiebreak';
			} elseif ( $scoring == 'TM' ) {
				if ( $s == $match->league->num_sets ) {
					$setType = 'matchtiebreak';
				} else {
					$setType = 'tiebreak';
				}
			} elseif ( $scoring == 'F4' ) {
				$setType = 'fast4';
			} elseif ( $scoring == 'FM' ) {
				if ( $s == $match->league->num_sets ) {
					$setType = 'matchtiebreak';
				} else {
					$setType = 'fast4';
				}
			} elseif ( $scoring == 'PR' ) {
				$setType = 'pro';
			} elseif ( $scoring == 'TP' ) {
				$setType = 'tiebreak';
				if ( $rubberNumber && $rubberNumber == $match->league->num_rubbers && $s != 1 ) {
					$setType = 'null';
				}
			} elseif ( $scoring == 'MP' ) {
				if ( $s == $match->league->num_sets ) {
					$setType = 'matchtiebreak';
				} else {
					$setType = 'tiebreak';
				}
				if ( $rubberNumber && $rubberNumber == $match->league->num_rubbers ) {
					$setType = 'matchtiebreak';
					if ( $s != 1 ) {
						$setType = 'null';
					}
				}
			}
			if ( $s > $numSetstoWin && $homescore == $numSetstoWin || $awayscore == $numSetstoWin ) {
				$setType = 'null';
			}
			$setValidate = $this->validateSetScore($set, $setPrefix, $errMsg, $errField, $setType, $walkover, $share);
			$set = $setValidate[2];
			$errMsg = $setValidate[0];
			$errField = $setValidate[1];
			if ( $errMsg ) {
				$error = true;
			}
			$setPlayer1 = strtoupper($set['player1']);
			$setPlayer2 = strtoupper($set['player2']);
			if ( $setPlayer1 !== null && $setPlayer2 !== null ) {
				if ( $setPlayer1 > $setPlayer2 ) {
					$homescore += 1;
				} elseif ( $setPlayer1 < $setPlayer2 ) {
					$awayscore += 1;
				} elseif ( $setPlayer1 == 'S' ){
					$homescore += 0.5;
					$awayscore += 0.5;
				}
			}
			$setsUpdated[$s] = $set;
			$s++;
		}
		if ( $walkover ) {
			if ( $walkover == 'home' ) {
				$awayscore -= 1;
			} elseif ( $walkover == 'away' ) {
				$homescore -= 1;
			}
		}

		array_push($return, $error, $errMsg, $errField, $homescore, $awayscore, $setsUpdated);
		return $return;
	}

	/**
	* validate set score
	*
	*/
	public function validateSetScore($set, $setPrefix, $errMsg, $errField, $setType, $walkover, $share) {
		$return = array();
		if ( $setType == 'tiebreak' ) {
			$maxWin = 7;
			$minWin = 6;
			$maxLoss = $maxWin - 2;
			$minLoss = $minWin - 2;
		} elseif ( $setType == 'matchtiebreak' ) {
			$maxWin = 1;
			$minWin = 1;
			$maxLoss = $maxWin - 1;
			$minLoss = $minWin - 1;
		} elseif ( $setType == 'fast4' ) {
			$maxWin = 4;
			$minWin = 4;
			$maxLoss = $maxWin - 1;
			$minLoss = $minWin - 1;
		} elseif ( $setType == 'standard' ) {
			$maxWin = 99;
			$minWin = 6;
			$maxLoss = $maxWin - 2;
			$minLoss = $minWin - 2;
		} elseif ( $setType == 'pro' ) {
			$maxWin = 9;
			$minWin = 8;
			$maxLoss = $maxWin - 2;
			$minLoss = $minWin - 2;
		} elseif ( $setType == 'null' ) {
			$maxWin = 0;
			$minWin = 0;
			$maxLoss = 0;
			$minLoss = 0;
		}
		$set['player1'] = strtoupper($set['player1']);
		$set['player2'] = strtoupper($set['player2']);
		if ( $set['player1'] !== null && $set['player2'] !== null ) {
			if ( $walkover ) {
				if ( $setType == 'null' ) {
					$set['player1'] = "";
					$set['player2'] = "";
				} elseif ( $walkover == 'home' ) {
					$set['player1'] = strval($minWin);
					$set['player2'] = "0";
				} else {
					if ( $walkover == 'away' ) {
						$set['player1'] = "0";
						$set['player2'] = strval($minWin);
					}
				}
			} elseif ( $setType == 'null' ) {
				if ( $set['player1'] != '' ) {
					$errMsg[] = __('Set score should be empty', 'racketmanager');
					$errField[] = $setPrefix.'player1';
				}
				if ( $set['player2'] != '' ) {
					$errMsg[] = __('Set score should be empty', 'racketmanager');
					$errField[] = $setPrefix.'player2';
				}
			} elseif ( $share ) {
				$set['player1'] = 'S';
				$set['player2'] = 'S';
			} elseif ( $set['player1'] == 'S' || $set['player2'] == 'S' ) {
				if ( $set['player1'] != 'S' ) {
					$errMsg[] = __('Both scores must be shared', 'racketmanager');
					$errField[] = $setPrefix.'player1';
				}
				if ( $set['player2'] != 'S' ) {
					$errMsg[] = __('Both scores must be shared', 'racketmanager');
					$errField[] = $setPrefix.'player2';
				}
			} elseif ( $set['player1'] > $set['player2']) {
				if ( $set['player1'] < $minWin ) {
					$errMsg[] = __('Winning set score too low', 'racketmanager');
					$errField[] = $setPrefix.'player1';
				} elseif ( $set['player1'] > $maxWin ) {
					$errMsg[] = __('Winning set score too high', 'racketmanager');
					$errField[] = $setPrefix.'player1';
				} elseif ( $set['player1'] == $minWin && $set['player2'] > $minLoss ) {
					$errMsg[] = __('Games difference must be at least 2', 'racketmanager');
					$errField[] = $setPrefix.'player1';
					$errField[] = $setPrefix.'player2';
				} elseif ( $set['player1'] == $maxWin && $set['player2'] < $maxLoss ) {
					$errMsg[] = __('Games difference incorrect', 'racketmanager');
					$errField[] = $setPrefix.'player1';
					$errField[] = $setPrefix.'player2';
				}
			} elseif ( $set['player1'] < $set['player2']) {
				if ( $set['player2'] < $minWin ) {
					$errMsg[] = __('Winning set score too low', 'racketmanager');
					$errField[] = $setPrefix.'player2';
				} elseif ( $set['player2'] > $maxWin ) {
					$errMsg[] = __('Winning set score too high', 'racketmanager');
					$errField[] = $setPrefix.'player2';
				} elseif ( $set['player2'] == $minWin && $set['player1'] > $minLoss ) {
					$errMsg[] = __('Games difference must be at least 2', 'racketmanager');
					$errField[] = $setPrefix.'player1';
					$errField[] = $setPrefix.'player2';
				} elseif ( $set['player2'] == $maxWin && $set['player1'] < $maxLoss ) {
					$errMsg[] = __('Games difference incorrect', 'racketmanager');
					$errField[] = $setPrefix.'player1';
					$errField[] = $setPrefix.'player2';
				}
			} elseif ( $set['player1'] == '' || $set['player2'] == '' ) {
				$errMsg[] = __('Set score not entered', 'racketmanager');
				if ( $set['player1'] == '' ) {
					$errField[] = $setPrefix.'player1';
				}
				if ( $set['player2'] == '' ) {
					$errField[] = $setPrefix.'player2';
				}
			} elseif ( $set['player1'] == $set['player2'] ) {
				$errMsg[] = __('Set scores must be different', 'racketmanager');
				$errField[] = $setPrefix.'player1';
				$errField[] = $setPrefix.'player2';
			}
		}
		array_push($return, $errMsg, $errField, $set);
		return $return;
	}

	/**
	* ressult notification
	*
	*/
	public function resultNotification($matchStatus, $matchMessage, $match, $matchUpdatedby=false) {
		global $racketmanager;
		$adminEmail = $racketmanager->getConfirmationEmail($match->league->competitionType);
		$rmOptions = $racketmanager->getOptions();
		$resultNotification = $rmOptions[$match->league->competitionType]['resultNotification'];

		if ( $adminEmail > '' ) {
			$messageArgs = array();
			$messageArgs['league'] = $match->league->id;
			if ( $match->league->is_championship ) {
				$messageArgs['round'] = $match->final_round;
			} else {
				$messageArgs['matchday'] = $match->match_day;
			}
			$headers = array();
			$confirmationEmail = '';
			if ( $matchStatus == 'P' ) {
				if ( $matchUpdatedby == 'home' ) {
					if ( $resultNotification == 'captain' ) {
						$confirmationEmail = $match->teams['away']->contactemail;
					} elseif ( $resultNotification == 'secretary' ) {
						$club = get_club($match->teams['away']->affiliatedclub);
						$confirmationEmail = isset($club->matchSecretaryEmail) ? $club->matchSecretaryEmail : '';
					}
				} else {
					if ( $resultNotification == 'captain' ) {
						$confirmationEmail = $match->teams['home']->contactemail;
					} elseif ( $resultNotification == 'secretary' ) {
						$club = get_club($match->teams['away']->affiliatedclub);
						$confirmationEmail = isset($club->matchSecretaryEmail) ? $club->matchSecretaryEmail : '';
					}
				}
			}
			if ( $confirmationEmail ) {
				$emailTo = $confirmationEmail;
				$headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$adminEmail.'>';
				$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$adminEmail.'>';
				$subject = $racketmanager->site_name." - ".$match->league->title." - ".$match->match_title." - Result confirmation required";
				$message = racketmanager_captain_result_notification($match->id, $messageArgs );
			} else {
				$emailTo = $adminEmail;
				$headers[] = $racketmanager->getFromUserEmail();
				$subject = $racketmanager->site_name." - ".$match->league->title." - ".$match->match_title." - ".$matchMessage;
				if ( $matchStatus == 'Y' ) {
					$match = get_match($match->id);
					if ( $match->hasResultChecks() ) {
						$messageArgs['errors'] = true;
						$subject .= " - ".__('Check results', 'racketmanager');
					} else {
						$messageArgs['complete'] = true;
						$subject .= " - ".__('Match complete', 'racketmanager');
					}
				}
				$message = racketmanager_result_notification($match->id, $messageArgs );
			}
			wp_mail($emailTo, $subject, $message, $headers);
		}
	}

	/**
	* confirm results of rubbers
	*
	*/
	public function confirmRubberResults() {

		$matchConfirmed = '';
		if ( isset($_POST['resultConfirm'])) {
			switch ( $_POST['resultConfirm'] ) {
				case "confirm":
				$matchConfirmed = 'A';
				break;
				case "challenge":
				$matchConfirmed = 'C';
				break;
				default:
				$matchConfirmed = '';
			}
		}

		return $matchConfirmed;
	}

	/**
	* update match status
	*
	*/
	public function updateMatchStatus( $matchId, $matchConfirmed, $homeClub, $awayClub, $comments, $userTeam ) {
		global $wpdb, $racketmanager, $league, $match;

		$userid = get_current_user_id();
		if ( isset($_POST['resultHome']) ) {
			$captain = 'home';
		} elseif ( isset($_POST['resultAway']) ) {
			$captain = 'away';
		} elseif ( $userTeam == 'home' ) {
			$captain = 'home';
		} elseif ( $userTeam == 'away' ) {
			$captain = 'away';
		} elseif ( $userTeam == 'both' ) {
			$captain = 'home';
		} else {
			$captain = 'admin';
		}

		if ( $captain == 'home' ) { //Home captain
			$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `home_captain` = %d, `comments` = '%s' WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $comments, $matchId));
			return 'home';
		} elseif ( $captain == 'away' ) {
			$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `away_captain` = %d, `comments` = '%s' WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $comments, $matchId));
			return 'away';
		} else {
			$matchConfirmed = 'A'; //Admin user
			$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `comments` = '%s' WHERE `id` = '%d'", get_current_user_id(), $matchConfirmed, $comments, $matchId));
			return 'admin';
		}
	}

	/**
	* confirm results
	*
	* @see admin/results.php
	*/
	public function confirmResults() {
		global $league;

		$updateCount = 0;
		$return ='';
		$custom = array();
		check_admin_referer('results-update');

		foreach ( $_POST['league'] as $league_id ) {
			$league = get_league($league_id);
			$matchCount = $league->_updateResults( $_POST['matches'][$league_id], $_POST['home_points'][$league_id], $_POST['away_points'][$league_id], $_POST['home_team'][$league_id], $_POST['away_team'][$league_id], $custom, $_POST['season'][$league_id] );
			$updateCount += $matchCount;
		}
		if ( $updateCount == 0 ) {
			$return = __('No results to update','racketmanager');
		} else {
			$return = sprintf(__('Updated Results of %d matches','racketmanager'), $updateCount);
		}

		die(json_encode($return));
	}

	/**
	* update match results and automatically calculate score
	*
	* @param match $match
	* @return none
	*/
	public function checkPlayerResult( $match, $rubber, $rosterId, $team, $options, $playerOptions ) {
		global $wpdb, $racketmanager, $match;

		$match = get_match($match->id);
		$player = $racketmanager->getClubPlayer($rosterId, $team);
		if ( !empty($player->system_record) ) {
			if ( $player->gender == 'M' ) {
				$gender = 'male';
			} elseif ( $player->gender == 'F' ) {
				$gender = 'female';
			} else {
				$gender = 'unknown';
			}
			if ( isset($playerOptions['unregistered'][$gender]) && $player->player_id == $playerOptions['unregistered'][$gender] ) {
				$error = __('Unregistered player', 'racketmanager');
				$match->addResultCheck( $team, $player->player_id, $error );
			}
			return;
		}

		$teamName = get_team($team)->title;
		$currTeamNum = substr($teamName,-1);

		if ( !is_numeric($rosterId) ) {
			$error = __('Player not selected', 'racketmanager');
			$match->addResultCheck( $team, 0, $error );
		}

		if ( $player ) {
			if ( isset($options['rosterLeadTime']) && isset($player->created_date) ) {
				$matchDate = new DateTime($match->date);
				$rosterDate = new DateTime($player->created_date);
				$interval = $rosterDate->diff($matchDate);
				if ( $interval->days < intval($options['rosterLeadTime']) ) {
					$error = sprintf(__('registered with club only %d days before match','racketmanager'), $interval->days);
					$match->addResultCheck( $team, $player->player_id, $error );
				} elseif ( $interval->invert ) {
					$error = sprintf(__('registered with club %d days after match','racketmanager'), $interval->days);
					$match->addResultCheck( $team, $player->player_id, $error );
				}
			}
			if ( !empty($player->locked) ) {
				$error = __('locked', 'racketmanager');
				$match->addResultCheck( $team, $player->player_id, $error );
			}

			if ( isset($match->match_day) ) {
				$sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

				$count = $wpdb->get_var($sql);
				if ( $count > 0 ) {
					$error = sprintf(__('already played on match day %d','racketmanager'), $match->match_day);
					$match->addResultCheck( $team, $player->player_id, $error );
				}

				if ( isset($options['playedRounds']) ) {
					$league = get_league($match->league_id);
					$numMatchDays = $league->seasons[$match->season]['num_match_days'];
					if ( $match->match_day > ($numMatchDays - $options['playedRounds']) ) {
						$sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

						$count = $wpdb->get_var($sql);
						if ( $count == 0 ) {
							$error = sprintf(__('not played before the final %d match days','racketmanager'), $options['playedRounds']);
							$match->addResultCheck( $team, $player->player_id, $error );
						}
					}

				}
				if ( isset($options['playerLocked']) ) {
					$competition = get_competition($match->league->competition_id);
					$playerStats = $competition->getPlayerStats(array('season' => $match->season, 'player' => $rosterId));
					$teamplay = array();
					foreach ( $playerStats as $playerStat ) {
						foreach ( $playerStat->matchdays as $matchDay) {
							$teamNum = substr($matchDay->team_title,-1) ;
							if (isset($teamplay[$teamNum])) {
								$teamplay[$teamNum] ++;
							}
							else {
								$teamplay[$teamNum] = 1;
							}
						}
						foreach ( $teamplay as $teamNum => $played) {
							if ($teamNum < $currTeamNum && $played > $options['playerLocked']) {
								$error = sprintf(__('locked to team %d','racketmanager'), $teamNum);
								$match->addResultCheck( $team, $player->player_id, $error );
							}
						}
					}
				}
			}
		}

	}

	/**
	* save roster requests
	*
	* @see templates/club.php
	*/
	public function playerRequest() {
		global $racketmanager;

		$return = array();
		$msg = '';
		$error = false;
		$errorField = array();
		$errorMsg = array();
		check_admin_referer('club-player-request');
		$playerValid = $racketmanager->validatePlayer();
		if ($playerValid[0]) {
			$newPlayer = $playerValid[1];
			$club = get_club($_POST['affiliatedClub']);
			$club->registerPlayer($newPlayer);
		} else {
			$errorField = $playerValid[1];
			$errorMsg = $playerValid[2];
			$racketmanager->setMessage(__('Error in player request','racketmanager'), true);
		}
		$msg = $racketmanager->message;
		$error = $racketmanager->error;

		array_push($return, $msg, $error, $errorField, $errorMsg);
		die(json_encode($return));

	}

	/**
	* remove roster entry
	*
	* @see admin/settings.php
	*/
	public function rosterRemove() {
		global $racketmanager;

		$return = array();
		check_admin_referer('roster-remove');

		foreach ( $_POST['roster'] as $roster_id ) {
			$racketmanager->delClubPlayer( intval($roster_id) );
		}
		die(json_encode($return));
	}

	/**
	* update Team
	*
	* @see templates/club.php
	*/
	public function updateTeam() {

		check_admin_referer('team-update');
		$return = array();
		$competitionId = $_POST['competition_id'];
		$teamId = $_POST['team_id'];

		$captainId = $_POST['captainId-'.$competitionId.'-'.$teamId];
		$contactno = $_POST['contactno-'.$competitionId.'-'.$teamId];
		$contactemail = $_POST['contactemail-'.$competitionId.'-'.$teamId];
		$matchday = $_POST['matchday-'.$competitionId.'-'.$teamId];
		$matchtime = $_POST['matchtime-'.$competitionId.'-'.$teamId];

		$msg = $this->updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime);

		array_push($return, $msg);
		die(json_encode($return));

	}

	public function updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime) {
		global $wpdb, $racketmanager, $competition;

		$updates = false;
		$msg = '';

		$competition = get_competition($competitionId);
		$team = $competition->getTeamInfo($teamId);

		if ( $team->captainId != $captainId || $team->match_day != $matchday || $team->match_time != $matchtime ) {
			$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_team_competition} SET `captain` = '%s', `match_day` = '%s', `match_time` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captainId, $matchday, $matchtime, $teamId, $competitionId ) );
			$updates = true;
		}
		if ( $team->contactno != $contactno || $team->contactemail != $contactemail ) {
			$update = $racketmanager->updatePlayerDetails($captainId, $contactno, $contactemail);
			if ($update) {
				$updates = true;
			} else {
				$updates = false;
				$msg = "Error updating team";
			}
		}

		if ( $updates ) {
			$msg = "Team updated";
		} elseif ( empty($msg) ) {
			$msg = "Nothing to update";
		}

		return $msg;
	}

	/**
	* update Club
	*
	* @see templates/club.php
	*/
	public function updateClub() {
		global $wpdb, $racketmanager;

		$updates = false;
		$return = array();
		$msg = '';
		check_admin_referer('club-update');
		$clubId = $_POST['clubId'];

		$contactno = $_POST['clubContactNo'];
		$facilities = $_POST['facilities'];
		$founded = $_POST['founded'];
		$matchSecretaryId = $_POST['matchSecretaryId'];
		$matchSecretaryContactNo = $_POST['matchSecretaryContactNo'];
		$matchSecretaryEmail = $_POST['matchSecretaryEmail'];
		$website = $_POST['website'];
		$address = $_POST['address'];

		$club = get_club($clubId);

		if ( $club->contactno != $contactno || $club->facilities != $facilities || $club->founded != $founded || $club->matchsecretary != $matchSecretaryId || $club->website != $website || $club->matchSecretaryContactNo != $matchSecretaryContactNo || $club->matchSecretaryEmail != $matchSecretaryEmail || $club->address != $address ) {
			$club->contactno = $contactno;
			$club->facilities = $facilities;
			$club->founded = $founded;
			$club->matchsecretary = $matchSecretaryId;
			$club->website = $website;
			$club->matchSecretaryContactNo = $matchSecretaryContactNo;
			$club->matchSecretaryEmail = $matchSecretaryEmail;
			$club->address = $address;
			$club->update($club);
			$updates = true;
		}

		if ( $updates ) {
			$msg = "Club updated";
		} elseif ( empty($msg) ) {
			$msg = "nothing to update";
		}

		array_push($return, $msg);
		die(json_encode($return));
	}

	/**
	* update Player
	*
	* @see templates/player.php
	*/
	public function updatePlayer() {
		global $wpdb, $racketmanager;

		$errorField = array();
		$errorMsg = array();
		$return = array();
		$msg = '';
		check_admin_referer('player-update');
		$playerId = $_POST['playerId'];

		$playerValid = $racketmanager->validatePlayer();
		if ($playerValid[0]) {
			$player = get_player($playerId);
			$newPlayer = $playerValid[1];
			$player->update($newPlayer->firstname, $newPlayer->surname, $newPlayer->gender, $newPlayer->btm, $newPlayer->email, $newPlayer->locked);
			$error = $racketmanager->error;
			$msg = $racketmanager->message;
		} else {
			$error = true;
			$errorField = $playerValid[1];
			$errorMsg = $playerValid[2];
			$msg = __('Error with player details', 'racketmanager');
		}

		array_push($return, $msg, $error, $errorField, $errorMsg);
		die(json_encode($return));
	}

	/**
	* tournament entry request
	*
	* @see templates/tournamententry.php
	*/
	public function tournamentEntryRequest() {
		global $wpdb, $racketmanager, $racketmanager_shortcodes;

		$return = array();
		$msg = '';
		$error = false;
		$errorField = array();
		$errorMsg = array();
		$errorId = 0;

		check_admin_referer('tournament-entry');

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
		} else {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('You must be logged in to submit a tournament entry', 'racketmanager');
			$errorId ++;
		}
		$season = $_POST['season'];
		$tournamentSeason = $_POST['tournamentSeason'];
		$tournamentSecretaryEmail = $_POST['tournamentSecretaryEmail'];
		$playerId = $_POST['playerId'];
		$contactno = isset($_POST['contactno']) ? $_POST['contactno'] : '';
		$contactemail = isset($_POST['contactemail']) ? $_POST['contactemail'] : '';
		if ( $contactemail == '' ) {
			$error = true;
			$errorField[$errorId] = 'contactemail';
			$errorMsg[$errorId] = __('Email address required', 'racketmanager');
			$errorId ++;
		}
		$affiliatedclub = isset($_POST['affiliatedclub']) ? $_POST['affiliatedclub'] : 0;
		if ($affiliatedclub == 0) {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('Select the club you are a member of', 'racketmanager');
			$errorId ++;
		} else {
			$playerName = $user->display_name;
			$club = get_club($affiliatedclub);
			$clubPlayers = $club->getPlayers(array('player' => $playerId));
			$clubPlayerId = $clubPlayers[0]->roster_id;
			$affiliatedClubName = $club->name;
		}
		$competitions = isset($_POST['competition']) ? $_POST['competition'] : array();
		if ( empty($competitions) ) {
			$error = true;
			$errorField[$errorId] = 'competition';
			$errorMsg[$errorId] = __('You must select a competition to enter', 'racketmanager');
			$errorId ++;
		} else {
			$partners = isset($_POST['partner']) ? $_POST['partner'] : array();
			foreach ($competitions as $competition) {
				$competition = get_competition($competition);
				if ( substr($competition->type,1,1) == 'D' ) {
					$partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;

					if ( empty($partnerId) ) {
						$error = true;
						$errorField[$errorId] = 'partner['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Partner not selected for %s', 'racketmanager'), $competition->name);
						$errorId ++;
					}
				}
			}
		}
		$acceptance = isset($_POST['acceptance']) ? $_POST['acceptance'] : '';
		if ( empty($acceptance) ) {
			$error = true;
			$errorField[$errorId] = 'acceptance';
			$errorMsg[$errorId] = __('You must agree to the rules', 'racketmanager');
			$errorId ++;
		}

		if ( !$error ) {
			$emailTo = $tournamentSecretaryEmail;
			$emailSubject = $racketmanager->site_name." ".ucfirst($tournamentSeason)." ".$season." Tournament Entry";
			$tournamentEntries = array();
			$i = 0;
			foreach ($competitions as $i => $competitionId) {
				$tournamentEntry = array();
				$partner = '';
				$partnerName = '';
				$newTeam = false;
				$competition = get_competition($competitionId);
				$tournamentEntry['competitionName'] = $competition->name;
				if (isset($competition->primary_league)) {
					$league = $competition->primary_league;
				} else {
					$leagues = $competition->getLeagues();
					$league = $leagues[0]->id;
				}
				$teamName = $playerName;
				if ( substr($competition->type,1,1) == 'D' ) {
					$partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;
					$partner = $racketmanager->getClubPlayer($partnerId);
					$partnerName = $partner->fullname;
					$teamName .= ' / '.$partnerName;
					$tournamentEntry['partner'] = $partnerName;
				}
				$teamId = $racketmanager->getTeamId($teamName);
				if (!$teamId) {
					if ( $partnerName != '' ) {
						$teamName2 = $partnerName.' / '.$playerName;
						$teamId = $racketmanager->getTeamId($teamName2);
						if (!$teamId) {
							$newTeam = true;
						}
					} else {
						$newTeam = true;
					}
				}
				if ($newTeam) {
					$teamId = $racketmanager->addPlayerTeam( $playerName, $clubPlayerId, $partnerName, $partnerId, $contactno, $contactemail, $affiliatedclub, $league );
				} else {
					$racketmanager->editTeamPlayer( $teamId, $playerName, $clubPlayerId, $partnerName, $partnerId, $contactno, $contactemail, $affiliatedclub, $league );
				}
				$racketmanager->addTeamtoTable($league, $teamId, $season);
				$tournamentEntries[$i] = $tournamentEntry;
			}
			$headers = array();
			if ( isset($user->user_email) ) {
				$headers[] = 'Cc: '.$user->display_name.' <'.$user->user_email.'>';
				$emailFrom = $user->user_email;
			} else {
				$emailFrom = $racketmanager->admin_email;
			}
			$headers[] = 'From: '.$user->display_name.' <'.$emailFrom.'>';
			$organisationName = $racketmanager->site_name;
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'tournament-entry', array( 'tournamentEntries' => $tournamentEntries, 'organisationName' => $organisationName, 'season' => $season, 'tournamentSeason' => $tournamentSeason, 'contactno' => $contactno, 'contactemail' => $contactemail, 'player' => $playerName, 'club' => $affiliatedClubName ), 'email' );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
			$msg = __('Tournament entry complete', 'racketmanager');
		} else {
			$msg = __('Errors in tournament entry form', 'racketmanager');
		}

		array_push($return, $msg, $error, $errorMsg, $errorField);
		die(json_encode($return));

	}

	/**
	* notify teams
	*
	* @see templates/email/match-notification.php
	*/
	public function notifyTeams() {
		global $match, $racketmanager;

		$return ='';
		$messageSent = false;

		$match = get_match($_POST['matchId']);
		$messageSent = $racketmanager->notifyNextMatchTeams($match);

		if ( $messageSent ) {
			$return = __('Teams notified','racketmanager');
		} else {
			$return = __('No notification','racketmanager');
		}

		die($return);
	}

	/**
	* Ajax Response to get captain information
	*
	*/
	public function getTeamCompetitionInfo() {
		global $wpdb;

		$teamInfo = array();
		$teamId = isset($_POST['team']) ? $_POST['team'] : '';
		$competitionId = isset($_POST['competition']) ? $_POST['competition'] : '';

		$competition = get_competition($competitionId);
		$team = $competition->getTeamInfo($teamId);
		if ( $team ) {
			$teamInfo['captain'] = addslashes($team->captain);
			$teamInfo['captainid'] = $team->captainId;
			$teamInfo['user_email'] = $team->contactemail;
			$teamInfo['contactno'] = $team->contactno;
			$teamInfo['match_day'] = $team->match_day;
			$teamInfo['match_time'] = $team->match_time;
		}

		die(json_encode($teamInfo));
	}

	/**
	* cup entry request
	*
	* @see templates/cupentry.php
	*/
	public function cupEntryRequest() {
		global $wpdb, $racketmanager, $racketmanager_shortcodes;

		$return = array();
		$msg = '';
		$error = false;
		$errorField = array();
		$errorMsg = array();
		$errorId = 0;

		check_admin_referer('cup-entry');

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
		} else {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('You must be logged in to submit a cup entry', 'racketmanager');
			$errorId ++;
		}
		$season = $_POST['season'];
		$cupSeason = $_POST['cupSeason'];
		$affiliatedclub = isset($_POST['affiliatedClub']) ? $_POST['affiliatedClub'] : 0;
		if ($affiliatedclub == 0) {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('Select the club you are a member of', 'racketmanager');
			$errorId ++;
		} else {
			$club = get_club($affiliatedclub);
			$affiliatedClubName = $club->name;
		}
		$competitions = isset($_POST['competition']) ? $_POST['competition'] : array();
		if ( empty($competitions) ) {
			$error = true;
			$errorField[$errorId] = 'competition';
			$errorMsg[$errorId] = __('You must select a competition to enter', 'racketmanager');
			$errorId ++;
		} else {
			$teams = isset($_POST['team']) ? $_POST['team'] : array();
			$captains = isset($_POST['captain']) ? $_POST['captain'] : array();
			$captainIds = isset($_POST['captainId']) ? $_POST['captainId'] : array();
			$contactnos = isset($_POST['contactno']) ? $_POST['contactno'] : array();
			$contactemails = isset($_POST['contactemail']) ? $_POST['contactemail'] : array();
			$matchdays = isset($_POST['matchday']) ? $_POST['matchday'] : array();
			$matchtimes = isset($_POST['matchtime']) ? $_POST['matchtime'] : array();
			foreach ($competitions as $competitionId) {
				$competition = get_competition($competitionId);
				$team = isset($teams[$competition->id]) ? $teams[$competition->id] : 0;
				if ( empty($team) ) {
					$error = true;
					$errorField[$errorId] = 'team['.$competition->id.']';
					$errorMsg[$errorId] = sprintf(__('Team not selected for %s', 'racketmanager'), $competition->name);
					$errorId ++;
				} else {
					$captain = isset($captains[$competition->id]) ? $captains[$competition->id] : 0;
					$contactno = isset($contactnos[$competition->id]) ? $contactnos[$competition->id] : '';
					$contactemail = isset($contactemails[$competition->id]) ? $contactemails[$competition->id] : '';
					$matchday = isset($matchdays[$competition->id]) ? $matchdays[$competition->id] : '';
					$matchtime = isset($matchtimes[$competition->id]) ? $matchtimes[$competition->id] : '';
					if ( empty($captain) ) {
						$error = true;
						$errorField[$errorId] = 'captain['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Captain not selected for %s', 'racketmanager'), $competition->name);
						$errorId ++;
					} else {
						if ( empty($contactno) || empty($contactemail) ) {
							$error = true;
							$errorField[$errorId] = 'captain['.$competition->id.']';
							$errorMsg[$errorId] = sprintf(__('Captain contact details missing for %s', 'racketmanager'), $competition->name);
							$errorId ++;
						}
					}
					if ( empty($matchday) ) {
						$error = true;
						$errorField[$errorId] = 'matchday['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Match day not selected for %s', 'racketmanager'), $competition->name);
						$errorId ++;
					}
					if ( empty($matchtime) ) {
						$error = true;
						$errorField[$errorId] = 'matchtime['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Match time not selected for %s', 'racketmanager'), $competition->name);
						$errorId ++;
					}
				}
			}
		}
		$acceptance = isset($_POST['acceptance']) ? $_POST['acceptance'] : '';
		if ( empty($acceptance) ) {
			$error = true;
			$errorField[$errorId] = 'acceptance';
			$errorMsg[$errorId] = __('You must agree to the rules', 'racketmanager');
			$errorId ++;
		}

		if ( !$error ) {
			$emailTo = $racketmanager->getConfirmationEmail('cup');
			$emailSubject = $racketmanager->site_name." ".ucfirst($cupSeason)." ".$season." Cup Entry - ".$affiliatedClubName;
			$cupEntrys = array();
			$i = 0;
			foreach ($competitions as $i => $competitionId) {
				$cupEntry = array();
				$competition = get_competition($competitionId);
				if (isset($competition->primary_league)) {
					$league = $competition->primary_league;
				} else {
					$league = get_league(array_key_first($competition->league_index))->id;
				}
				$teamId = isset($teams[$competition->id]) ? $teams[$competition->id] : 0;
				if ( $teamId ) {
					$team = get_team($teamId);
					$captain = isset($captains[$competition->id]) ? $captains[$competition->id] : 0;
					$captainId = isset($captainIds[$competition->id]) ? $captainIds[$competition->id] : 0;
					$contactno = isset($contactnos[$competition->id]) ? $contactnos[$competition->id] : '';
					$contactemail = isset($contactemails[$competition->id]) ? $contactemails[$competition->id] : '';
					$matchday = isset($matchdays[$competition->id]) ? $matchdays[$competition->id] : '';
					$matchtime = isset($matchtimes[$competition->id]) ? $matchtimes[$competition->id] : '';
					$teamInfo = $competition->getTeamInfo($teamId);
					if ( !$teamInfo ) {
						$racketmanager->addTeamCompetition( $teamId, $competitionId, $captainId, $contactno, $contactemail, $matchday, $matchtime );
					} else {
						$this->updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime);
					}
				}
				$racketmanager->addTeamtoTable($league, $teamId, $season);
				$cupEntry['competitionName'] = $competition->name;
				$cupEntry['teamName'] = $team->title;
				$cupEntry['captain'] = $captain;
				$cupEntry['contactno'] = $contactno;
				$cupEntry['contactemail'] = $contactemail;
				$cupEntry['matchday'] = $matchday;
				$cupEntry['matchtime'] = $matchtime;
				$cupEntrys[$i] = $cupEntry;
			}
			$headers = array();
			$headers[] = 'From: '.$affiliatedClubName.' <'.$racketmanager->admin_email.'>';
			if ( isset($user->user_email) ) {
				$headers[] = 'Cc: '.$user->display_name.' <'.$user->user_email.'>';
			}
			$organisationName = $racketmanager->site_name;
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'cup-entry', array( 'cupEntries' => $cupEntrys, 'organisationName' => $organisationName, 'season' => $season, 'cupSeason' => $cupSeason, 'club' => $affiliatedClubName ), 'email' );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);

			$msg = __('Cup entry complete', 'racketmanager');
		} else {
			$msg = __('Errors in cup entry form', 'racketmanager');
		}

		array_push($return, $msg, $error, $errorMsg, $errorField);
		die(json_encode($return));

	}

	/**
	* league entry request
	*
	* @see templates/leagueentry.php
	*/
	public function leagueEntryRequest() {
		global $racketmanager, $racketmanager_shortcodes;

		$return = array();
		$msg = '';
		$error = false;
		$errorField = array();
		$errorMsg = array();
		$errorId = 0;
		$courtsNeeded = array();

		check_admin_referer('league-entry');

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
		} else {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('You must be logged in to submit a league entry', 'racketmanager');
			$errorId ++;
		}
		$season = $_POST['season'];
		$leagueSeason = $_POST['leagueSeason'];
		$affiliatedclub = isset($_POST['affiliatedClub']) ? $_POST['affiliatedClub'] : 0;
		if ($affiliatedclub == 0) {
			$error = true;
			$errorField[$errorId] = 'affiliatedclub';
			$errorMsg[$errorId] = __('Select the club you are a member of', 'racketmanager');
			$errorId ++;
		} else {
			$club = get_club($affiliatedclub);
			$affiliatedClubName = $club->name;
		}

		$competitions = isset($_POST['competition']) ? $_POST['competition'] : array();
		if ( empty($competitions) ) {
			$error = true;
			$errorField[$errorId] = 'competition';
			$errorMsg[$errorId] = __('You must select a competition to enter', 'racketmanager');
			$errorId ++;
		} else {
			$teamCompetition = isset($_POST['teamCompetition']) ? $_POST['teamCompetition'] : array();
			$teamCompetitionTitles = isset($_POST['teamCompetitionTitle']) ? $_POST['teamCompetitionTitle'] : array();
			$teamCompetitionLeague = isset($_POST['teamCompetitionLeague']) ? $_POST['teamCompetitionLeague'] : array();
			$captains = isset($_POST['captain']) ? $_POST['captain'] : array();
			$captainIds = isset($_POST['captainId']) ? $_POST['captainId'] : array();
			$contactnos = isset($_POST['contactno']) ? $_POST['contactno'] : array();
			$contactemails = isset($_POST['contactemail']) ? $_POST['contactemail'] : array();
			$matchdays = isset($_POST['matchday']) ? $_POST['matchday'] : array();
			$matchtimes = isset($_POST['matchtime']) ? $_POST['matchtime'] : array();
			foreach ($competitions as $competitionId) {
				$competition = get_competition($competitionId);
				$week = isset($competition->offset) ? $competition->offset : '0';
				if ( !isset($courtsNeeded[$week]) ) {
					$courtsNeeded[$week] = array();
				}
				$teams = isset($teamCompetition[$competition->id]) ? $teamCompetition[$competition->id] : array();
				if ( empty($teams) ) {
					$error = true;
					$errorField[$errorId] = 'competition['.$competition->id.']';
					$errorMsg[$errorId] = sprintf(__('No teams selected for %s', 'racketmanager'), $competition->name);
					$errorId ++;
				} else {
					foreach ($teams as $teamId) {
						$teamCompetitionTitle = isset($teamCompetitionTitles[$competition->id][$teamId]) ? $teamCompetitionTitles[$competition->id][$teamId] : '';
						$captain = isset($captains[$competition->id][$teamId]) ? $captains[$competition->id][$teamId] : 0;
						$contactno = isset($contactnos[$competition->id][$teamId]) ? $contactnos[$competition->id][$teamId] : '';
						$contactemail = isset($contactemails[$competition->id][$teamId]) ? $contactemails[$competition->id][$teamId] : '';
						$matchday = isset($matchdays[$competition->id][$teamId]) ? $matchdays[$competition->id][$teamId] : '';
						$matchtime = isset($matchtimes[$competition->id][$teamId]) ? $matchtimes[$competition->id][$teamId] : '';
						if ( empty($captain) ) {
							$error = true;
							$errorField[$errorId] = 'captain['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Captain not selected for %s', 'racketmanager'), $teamCompetitionTitle);
							$errorId ++;
						} else {
							if ( empty($contactno) || empty($contactemail) ) {
								$error = true;
								$errorField[$errorId] = 'captain['.$competition->id.']['.$teamId.']';
								$errorMsg[$errorId] = sprintf(__('Captain contact details missing for %s', 'racketmanager'), $teamCompetitionTitle);
								$errorId ++;
							}
						}
						if ( empty($matchtime) ) {
							$error = true;
							$errorField[$errorId] = 'matchtime['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Match time not selected for %s', 'racketmanager'), $teamCompetitionTitle);
							$errorId ++;
						} else {
							if ( strlen($matchtime) == 5 ) {
								$matchtime = $matchtime.':00';
							}
						}
						if ( empty($matchday) ) {
							$error = true;
							$errorField[$errorId] = 'matchday['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Match day not selected for %s', 'racketmanager'), $teamCompetitionTitle);
							$errorId ++;
						} else {
							$matchDayTime = $matchday.' '.$matchtime;
							if ( isset($courtsNeeded[$week][$matchDayTime]) ) {
								$courtsNeeded[$week][$matchDayTime]['teams'] += 1;
								$courtsNeeded[$week][$matchDayTime]['courts'] += $competition->num_rubbers;
							} else {
								$courtsNeeded[$week][$matchDayTime] = array('teams' => 1, 'courts' => $competition->num_rubbers);
							}
						}
					}
				}
			}
		}
		$numCourtsAvailable = isset($_POST['numCourtsAvailable']) ? $_POST['numCourtsAvailable'] : '';
		if ( empty($numCourtsAvailable) ) {
			$error = true;
			$errorField[$errorId] = 'numCourtsAvailable';
			$errorMsg[$errorId] = __('You must agree specify the number of courts available', 'racketmanager');
			$errorId ++;
		} else {
			foreach ($courtsNeeded as $week) {
				foreach ( $week as $matchDay => $value) {
					$courtNeeds = $value['courts'] / $value['teams'];
					$courtNeedsbyDay = $courtNeeds * ceil($value['teams']/2);
					if ( $courtNeedsbyDay > $numCourtsAvailable ) {
						$error = true;
						$errorField[$errorId] = 'numCourtsAvailable';
						$errorText = __('There are not enough courts available for', 'racketmanager');
						$errorMsg[$errorId] = $errorText.' '.$matchDay;
						$errorId ++;
					}
				}
			}
		}
		$acceptance = isset($_POST['acceptance']) ? $_POST['acceptance'] : '';
		if ( empty($acceptance) ) {
			$error = true;
			$errorField[$errorId] = 'acceptance';
			$errorMsg[$errorId] = __('You must agree to the rules', 'racketmanager');
			$errorId ++;
		}

		if ( !$error ) {
			$leagueCompetitions = explode(',', $_POST['leagueCompetitions']);
			$competitionEntries = array();
			$competitionDetails = array();
			$competitionEntries['numCourtsAvailable'] = $numCourtsAvailable;
		foreach ($competitions as $competitionId) {
				if (($key = array_search($competitionId, $leagueCompetitions)) !== false) {
    			unset($leagueCompetitions[$key]);
				}
				$competitionTeams = explode(',', $_POST['competitionTeams'][$competition->id]);
				$competitionEntry = array();
				$competition = get_competition($competitionId);
				$competitionEntry['competitionName'] = $competition->name;
				$teams = isset($teamCompetition[$competition->id]) ? $teamCompetition[$competition->id] : array();
				$leagueEntries = array();
				foreach ($teams as $teamId) {
					if (($key = array_search($teamId, $competitionTeams)) !== false) {
	    			unset($competitionTeams[$key]);
					}
					$leagueEntry = array();
					$teamCompetitionTitle = isset($teamCompetitionTitles[$competition->id][$teamId]) ? $teamCompetitionTitles[$competition->id][$teamId] : '';
					$captain = isset($captains[$competition->id][$teamId]) ? $captains[$competition->id][$teamId] : 0;
					$captainId = isset($captainIds[$competition->id][$teamId]) ? $captainIds[$competition->id][$teamId] : 0;
					$contactno = isset($contactnos[$competition->id][$teamId]) ? $contactnos[$competition->id][$teamId] : '';
					$contactemail = isset($contactemails[$competition->id][$teamId]) ? $contactemails[$competition->id][$teamId] : '';
					$matchday = isset($matchdays[$competition->id][$teamId]) ? $matchdays[$competition->id][$teamId] : '';
					$matchtime = isset($matchtimes[$competition->id][$teamId]) ? $matchtimes[$competition->id][$teamId] : '';
					$leagueId = isset($teamCompetitionLeague[$competition->id][$teamId]) ? $teamCompetitionLeague[$competition->id][$teamId] : '';
					$teamInfo = $competition->getTeamInfo($teamId);
					if ( !$teamInfo ) {
						$racketmanager->addTeamCompetition( $teamId, $competitionId, $captainId, $contactno, $contactemail, $matchday, $matchtime );
					} else {
						$this->updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime);
					}
					if ( $leagueId ) {
						$competition->markTeamsEntered($teamId, $season);
					} else {
						$competition->addTeamToCompetition($teamId, $season);
					}
					$leagueEntry['teamName'] = $teamCompetitionTitle;
					$leagueEntry['captain'] = $captain;
					$leagueEntry['contactno'] = $contactno;
					$leagueEntry['contactemail'] = $contactemail;
					$leagueEntry['matchday'] = $matchday;
					$leagueEntry['matchtime'] = $matchtime;
					$leagueEntries[] = $leagueEntry;
				}
				foreach ($competitionTeams as $team) {
					if ( !empty($team->leagueId) ) {
						$competition->markTeamsWithdrawn($season, $affiliatedclub, $team->teamId);
					}
				}
				$competitionEntry['teams'] = $leagueEntries;
				$competitionDetails[] = $competitionEntry;
				$competition->settings['numCourtsAvailable'][$affiliatedclub] = $numCourtsAvailable;
				$competition->setSettings($competition->settings);
			}
			$competitionEntries['competitions'] = $competitionDetails;
			foreach ($leagueCompetitions as $key => $competitionId) {
				$competition = get_competition($competitionId);
				$competition->markTeamsWithdrawn($season, $affiliatedclub);
			}

			$emailTo = $racketmanager->getConfirmationEmail('league');
			$emailSubject = $racketmanager->site_name." ".ucfirst($leagueSeason)." ".$season." League Entry - ".$affiliatedClubName;
			$headers = array();
			$headers[] = 'From: '.$affiliatedClubName.' <'.$racketmanager->admin_email.'>';
			if ( isset($user->user_email) ) {
				$headers[] = 'Cc: '.$user->display_name.' <'.$user->user_email.'>';
			}
			$organisationName = $racketmanager->site_name;
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'league-entry', array( 'competitionEntries' => $competitionEntries, 'organisationName' => $organisationName, 'season' => $season, 'leagueSeason' => $leagueSeason, 'club' => $affiliatedClubName ), 'email' );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
			$msg = __('league entry complete', 'racketmanager');
		} else {
			$msg = __('Errors in league entry form', 'racketmanager');
		}

		array_push($return, $msg, $error, $errorMsg, $errorField);
		die(json_encode($return));

	}

	/**
	* notify match secretaries of competition entries open
	*
	* @see templates/email/competition-entry-open.php
	*/
	public function notifyEntriesOpen() {
		global $racketmanager;

		$return ='';

		$competition = get_competition($_POST['competitionId']);
		$latestSeason = $_POST['latestSeason'];
		$competitionTitle = explode(" ", $competition->name);
		$competitionSeason = seourl($competitionTitle[0]);
		$competitionType = $competition->competitiontype;

		$return = $racketmanager->notifyEntryOpen($competitionType, $latestSeason, $competitionSeason);

		die(json_encode($return));
	}

	/**
	* notify match secretaries of tournament entries open
	*
	* @see templates/email/competition-entry-open.php
	*/
	public function notifyTournamentEntriesOpen() {
		global $racketmanager;

		$return ='';

		$tournamentId = $_POST['tournamentId'];
		$tournament = $racketmanager->getTournament( array( 'id' => $tournamentId) );
		$latestSeason = $tournament->season;
		$competitionSeason = $tournament->type;
		$competitionType = 'tournament';

		$return = $racketmanager->notifyEntryOpen($competitionType, $latestSeason, $competitionSeason);

		die(json_encode($return));
	}

	/**
	* add item as favourite
	*
	*/
	public function addFavourite() {
		global $racketmanager;

		$return = array();

		$type = $_POST['type'];
		$id = $_POST['id'];
		$userid = get_current_user_id();
		$metaKey = 'favourite-'.$type;
		$meta = get_user_meta($userid, $metaKey);
		$favouriteFound = (array_search($id, $meta,true));
		if ( !is_numeric($favouriteFound) ) {
			add_user_meta($userid, $metaKey, $id);
			$msg = __('Favourite added', 'racketmanager');
			$action = 'add';
		} else {
			delete_user_meta($userid, $metaKey, $id);
			$msg = __('Favourite removed', 'racketmanager');
			$action = 'del';
		}

		array_push($return, $action, $msg);
		die(json_encode($return));
	}

	/**
	* contact captain for match result
	*
	* @see templates/email/match-result-pending.php
	*/
	public function chaseMatchResult() {
		global $racketmanager, $racketmanager_shortcodes, $match;

		$matchId = $_POST['matchId'];
		$messageSent = false;
		$return = array();
		$messageSent = $racketmanager->_chaseMatchResult($matchId);
		if ( $messageSent ) {
			$return['msg'] = __('Captain emailed','racketmanager');
		} else {
			$return['error'] = true;
			$return['msg'] = __('No notification','racketmanager');
		}

		die(json_encode($return));
	}

	/**
	* contact captain for match approval
	*
	* @see templates/email/match-approval-pending.php
	*/
	public function chaseMatchApproval() {
		global $racketmanager, $racketmanager_shortcodes, $match;

		$matchId = $_POST['matchId'];
		$messageSent = false;
		$return = array();
		$messageSent = $racketmanager->_chaseMatchApproval($matchId);
		if ( $messageSent ) {
			$return['msg'] = __('Captain emailed','racketmanager');
		} else {
			$return['error'] = true;
			$return['msg'] = __('No notification','racketmanager');
		}

		die(json_encode($return));
	}

	/**
	* send fixtures to captains
	*
	* @see templates/email/match-result-pending.php
	*/
	public function sendFixtures() {
		global $racketmanager, $racketmanager_shortcodes, $competition;

		$competitionId = $_POST['competitionId'];
		$competition = get_competition($competitionId);
		$season = $competition->current_season['name'];

		$messageSent = false;
		$return = array();

		$fromEmail = $this->getConfirmationEmail($competition->competitiontype);
		$organisationName = $racketmanager->site_name;

		$leagues = $competition->getLeagues(array());
		foreach ($leagues as $league) {
			$league = get_league($league->id);
			$teams = $league->getLeagueTeams(array('getDetails' => true));
			foreach ($teams as $team) {
				$matches = $league->getMatches(array('final' => '', 'team_id' => $team->id));
				$headers = array();
				$headers[] = 'From: '.ucfirst($competition->competitiontype).' Secretary <'.$fromEmail.'>';
				$emailSubject = $racketmanager->site_name." - ".$league->title." - Season ".$team->season." - Fixtures - ".$team->title;
				$emailTo = '';
				if ( isset($team->contactemail) ) {
					$emailTo = $team->captain.' <'.$team->contactemail.'>';
					$club = get_club($team->affiliatedclub);
					if ( isset($club->matchSecretaryEmail) ) {
						$headers[] = 'cc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
					}
					$actionURL = $racketmanager->site_url.'/'.$competition->competitiontype.'s/'.seoUrl($league->title).'/'.$team->season.'/day0/'.seoUrl($team->title);
					$emailMessage = $racketmanager_shortcodes->loadTemplate( 'send-fixtures', array( 'competition' => $competition->name, 'captain' => $team->captain, 'season' => $season, 'matches' => $matches, 'team' => $team, 'actionURL' => $actionURL, 'organisationName' => $organisationName ), 'email' );
					wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
					$messageSent = true;
				}
			}
		}

		if ( $messageSent ) {
			$return['msg'] = __('Captains emailed','racketmanager');
		} else {
			$return['error'] = true;
			$return['msg'] = __('No notification','racketmanager');
		}

		die(json_encode($return));
	}

}
?>
