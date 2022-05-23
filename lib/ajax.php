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

		add_action( 'wp_ajax_racketmanager_get_match_box', array(&$this, 'getMatchBox') );
		add_action( 'wp_ajax_nopriv_racketmanager_get_match_box', array(&$this, 'getMatchBox') );

		add_action( 'wp_ajax_racketmanager_show_rubbers', array(&$this, 'showRubbers') );
		add_action( 'wp_ajax_nopriv_racketmanager_show_rubbers', array(&$this, 'showRubbers') );

		add_action( 'wp_ajax_racketmanager_view_rubbers', array(&$this, 'viewMatchRubbers') );
		add_action( 'wp_ajax_nopriv_racketmanager_view_rubbers', array(&$this, 'viewMatchRubbers') );

		add_action( 'wp_ajax_racketmanager_matchcard_player', array(&$this, 'printMatchCardPlayer') );
		add_action( 'wp_ajax_nopriv_racketmanager_matchcard_player', array(&$this, 'printMatchCardPlayer') );

		add_action( 'wp_ajax_racketmanager_show_match', array(&$this, 'showMatch') );
		add_action( 'wp_ajax_racketmanager_update_match', array(&$this, 'updateMatch') );

		add_action( 'wp_ajax_racketmanager_update_rubbers', array(&$this, 'updateRubbers') );
		add_action( 'wp_ajax_racketmanager_confirm_results', array(&$this, 'confirmResults') );

		add_action( 'wp_ajax_racketmanager_roster_request', array(&$this, 'rosterRequest') );
		add_action( 'wp_ajax_racketmanager_roster_remove', array(&$this, 'rosterRemove') );

		add_action( 'wp_ajax_racketmanager_team_update', array(&$this, 'updateTeam') );
		add_action( 'wp_ajax_racketmanager_update_club', array(&$this, 'clubUpdate') );

		add_action( 'wp_ajax_racketmanager_tournament_entry', array(&$this, 'tournamentEntryRequest') );

		add_action( 'wp_ajax_racketmanager_notify_teams', array(&$this, 'notifyTeams') );
		add_action( 'wp_ajax_racketmanager_get_team_info', array(&$this, 'getTeamCompetitionInfo') );
		add_action( 'wp_ajax_racketmanager_cup_entry', array(&$this, 'cupEntryRequest') );
		add_action( 'wp_ajax_racketmanager_league_entry', array(&$this, 'leagueEntryRequest') );
		add_action( 'wp_ajax_racketmanager_notify_entries_open', array(&$this, 'notifyEntriesOpen') );
		add_action( 'wp_ajax_racketmanager_notify_tournament_entries_open', array(&$this, 'notifyTournamentEntriesOpen') );
		add_action( 'wp_ajax_racketmanager_chase_match_result', array(&$this, 'chaseMatchResult') );

		add_action( 'wp_ajax_racketmanager_add_favourite', array(&$this, 'addFavourite') );
	}

	/**
	* Ajax Response to set match index in widget
	*
	*/
	public function getMatchBox() {
		$widget = new RacketManagerWidget(true);

		$current = $_POST['current'];
		$element = $_POST['element'];
		$operation = $_POST['operation'];
		$league_id = intval($_POST['league_id']);
		$match_limit = ( $_POST['match_limit'] == 'false' ) ? false : intval($_POST['match_limit']);
		$widget_number = intval($_POST['widget_number']);
		$season = htmlspecialchars($_POST['season']);
		$group = ( isset($_POST['group']) ? htmlspecialchars($_POST['group']) : '' );
		$home_only = htmlspecialchars($_POST['home_only']);
		$date_format = htmlspecialchars($_POST['date_format']);

		if ( $operation == 'next' )
		$index = $current + 1;
		elseif ( $operation == 'prev' )
		$index = $current - 1;

		$widget->setMatchIndex( $index, $element );

		if ( isset($group) ) {
			$instance = array( 'league' => $league_id, 'group' => $group, 'match_limit' => $match_limit, 'season' => $season, 'home_only' => $home_only, 'date_format' => $date_format );
		} else {
			$instance = array( 'league' => $league_id, 'match_limit' => $match_limit, 'season' => $season, 'home_only' => $home_only, 'date_format' => $date_format );
		}

		if ( $element == 'next' ) {
			$parent_id = 'next_matches_'.$widget_number;
			$match_box = $widget->showNextMatchBox($widget_number, $instance, false);
		} elseif ( $element == 'prev' ) {
			$parent_id = 'prev_matches_'.$widget_number;
			$match_box = $widget->showPrevMatchBox($widget_number, $instance, false, true);
		}

		die( "jQuery('div#".$parent_id."').fadeOut('fast', function() {
			jQuery('div#".$parent_id."').html('".addslashes_gpc($match_box)."').fadeIn('fast');
		});");
	}

	/**
	* Ajax Response to get player information
	*
	*/
	public function getPlayerDetails() {
		global $wpdb, $racketmanager;
		$name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';

		$sql = "SELECT  P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` as playerId, P.`user_email` FROM $wpdb->racketmanager_roster R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND C.`id` = R.`affiliatedclub` AND `display_name` like '%s' ORDER BY 1,2,3";
		$sql = $wpdb->prepare($sql, $name);
		$results = $wpdb->get_results($sql);
		$players = array();
		$player = array();
		foreach( $results AS $r) {
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

		$sql = "SELECT P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` AS `playerId`, P.`user_email` FROM $wpdb->racketmanager_roster R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND  C.`id` = R.`affiliatedclub` AND C.`id` = '%s' AND `display_name` like '%s' ORDER BY 1,2,3";
		$sql = $wpdb->prepare($sql, $affiliatedClub, $name);
		$results = $wpdb->get_results($sql);
		$captains = array();
		$captain = array();
		foreach( $results AS $r) {
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
		foreach ( $teams AS $rank => $team_id ) {
			$old = get_team( $team_id );
			$oldRank = $old->rank;

			if ( $oldRank != 0 ) {
				if ( $rank == $oldRank )
				$status = '&#8226;';
				elseif ( $rank < $oldRank )
				$status = '&#8593';
				else
				$status = '&#8595';
			} else {
				$status = '&#8226;';
			}

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `rank` = '%d', `status` = '%s' WHERE `team_id` = '%d'", $rank, $status, $team_id ) );
		}
	}

	/**
	* AJAX response to manually set additional points
	*
	* @see admin/standings.php
	*/
	public function saveAddPoints() {
		global $wpdb;

		$team_id = intval($_POST['team_id']);
		$league = get_league(intval($_POST['league_id']));
		$season = $league->getSeason();
		$add_points = $_POST['points'];

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_table} SET `add_points` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d' AND `season` = '%s'", $add_points, $team_id, $league->id, $season ) );
		$league->_rankTeams($league_id);

		die("jQuery('#loading_".$team_id."').fadeOut('fast'); window.location.reload(true);");
	}

	/**
	* AJAX response to get team data from database and insert into player team edit form
	*
	* @see admin/team.php
	*/
	public function addTeamPlayerFromDB() {
		global $racketmanager;

		$team_id = (int)$_POST['team_id'];
		$team = get_team( $team_id );
		$return = "document.getElementById('team_id').value = ".$team_id.";document.getElementById('team').value = '".$team->title."';document.getElementById('affiliatedclub').value = ".$team->affiliatedclub.";document.getElementById('teamPlayer1').value = '".$team->player[1]."';document.getElementById('teamPlayerId1').value = ".$team->playerId[1].";";
		if ( isset($team->player[2]) ) {
			$return .= "document.getElementById('teamPlayer2').value = '".$team->player[2]."';document.getElementById('teamPlayerId2').value = ".$team->playerId[2].";";
		}

		$home = '';

		die($return);
	}

	/**
	* insert home team stadium if available
	*
	* @see admin/match.php
	*/
	public function insertHomeStadium() {

		$team_id = (int)$_POST['team_id'];

		$team = get_team( $team_id );

		if ($team) $stadium = trim($team->stadium);
		else $stadium = "";
		die($stadium);
	}

	/**
	* change all Match Day Pop-ups to match first one set
	*
	* @see admin/match.php
	*/
	public function setMatchDayPopUp() {
		global $racketmanager;
		$match_day = (int)$_POST['match_day'];
		$i = (int)$_POST['i'];
		$max_matches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

		if ( $i == 0 && $mode == 'add') {
			$myAjax = "";
			for ( $xx = 1; $xx < $max_matches; $xx++ ) {
				$myAjax .= "document.getElementById('match_day_".$xx."').value = '".$match_day."'; ";
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
		$match_date = htmlspecialchars($_POST['match_date']);
		$i = (int)$_POST['i'];
		$max_matches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

		if ( $i == 0 && $mode == 'add' ) {
			$myAjax = "";
			for ( $xx = 1; $xx < $max_matches; $xx++ ) {
				$myAjax .= "document.getElementById('mydatepicker[".$xx."]').value = '".$match_date."'; ";
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
	public function viewMatchRubbers() {
		global $racketmanager, $championship;
		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$league = get_league($match->league_id);
		$num_sets = $league->num_sets;
		$pointsspan = 2 + intval($num_sets);
		$num_rubbers = $league->num_rubbers;
		$match_type = $league->type;
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

				<table class="widefat" summary="" style="margin-bottom: 2em;">
					<thead>
						<tr>
							<th style="text-align: center;"><?php _e( 'Pair', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Home Team', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Away Team', 'racketmanager' ) ?></th>
						</tr>
					</thead>
					<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

						<?php $class = '';
						$rubbers = $match->getRubbers();
						$r = 0 ;

						foreach ($rubbers as $rubber) {
							?>
							<tr class="rtr">
								<td rowspan="3" class="rtd centered">
									<?php echo (isset($rubber->rubber_number) ? $rubber->rubber_number : '') ?>
								</td>
								<td class="rtd">
									<input class="player" name="homeplayer1[<?php echo $r ?>]" id="homeplayer1_<?php echo $r ?>" />
								</td>

								<?php for ( $i = 1; $i <= $num_sets; $i++ ) { ?>
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
							<td colspan="<?php echo intval($num_sets) ?>" class="rtd" style="text-align: center;">
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
		$num_sets = $league->num_sets;
		$pointsspan = 2 + intval($num_sets);
		$match_type = $league->type;
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
					<?php if ( $league->mode == 'championship' ) {
					} else {
						echo $match->match_title;
					}
				?>
				</div>
			</div>
			<form id="match-view" action="#" method="post" onsubmit="return checkSelect(this)">
				<?php wp_nonce_field( 'rubbers-match' ) ?>

				<table class="widefat" summary="" style="margin-bottom: 2em;">
					<thead>
						<tr>
							<th style="text-align: center;" colspan="1"><?php _e( 'Team', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'racketmanager' ) ?></th>
							<th style="text-align: center;" colspan="1"><?php _e( 'Team', 'racketmanager' ) ?></th>
						</tr>
					</thead>
					<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

						<?php $class = ''; ?>

						<tr class="rtr">
							<td class="rtd">
								<?php echo $match->teams['home']->title ?>
							</td>

							<?php for ( $i = 1; $i <= $num_sets; $i++ ) { ?>
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
							<td colspan="<?php echo intval($num_sets) ?>" class="rtd" style="text-align: center;">
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
			$updates = false;
			$matchId = $_POST['current_match_id'];
			$match = get_match($matchId);
			$league = get_league($match->league_id);
			$matchConfirmed = 'P';
			$matches[$matchId] = $matchId;
			$home_points[$matchId] = 0;
			$away_points[$matchId] = 0;
			$home_team[$matchId] = $_POST['home_team'];
			$away_team[$matchId] = $_POST['away_team'];
			$custom[$matchId] = $_POST['custom'];
			$season[$matchId] = $_POST['current_season'];
			$matchCount = $league->_updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season, $_POST['match_round'], $matchConfirmed );
			if ( $matchCount > 0 ) {
				$matchMessage = __( 'Result saved', 'racketmanager' );
				$match = get_match($matchId);
				$homePoints = $match->home_points;
				$awayPoints = $match->away_points;
				$this->resultNotification($matchConfirmed, $matchMessage, $match);
			} else {
				$matchMessage = __('No result to save','racketmanager');
			}

			array_push($return,$matchMessage,$homePoints,$awayPoints);

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
			$homepoints = array();
			$awaypoints = array();
			$return = array();
			$msg = '';
			$error = false;
			$updates = false;
			$matchId = $_POST['current_match_id'];
			$match = get_match($matchId);
			$matchRubbers = array();
			$matchRubbers['homepoints'] = isset($_POST['home_points']) ? $_POST['home_points'] : array();
			$matchRubbers['awaypoints'] = isset( $_POST['away_points']) ? $_POST['away_points'] : array();
			$num_rubbers = $_POST['num_rubbers'];
			$home_team = $_POST['home_team'];
			$away_team = $_POST['away_team'];
			$lm_options = $racketmanager->getOptions();
			$matchConfirmed = '';
			$userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
			$userCanUpdate = $userCanUpdateArray[0];
			$userType = $userCanUpdateArray[1];
			$userTeam = $userCanUpdateArray[2];
			$resultConfirmation = $lm_options[$match->league->competitionType]['resultConfirmation'];
			$matchComments = isset($_POST['resultConfirmComments']) ? $_POST['resultConfirmComments'] : '';
			$matchCommentsHome = isset($_POST['resultConfirmCommentsHome']) ? $_POST['resultConfirmCommentsHome'] : '';
			$matchCommentsAway = isset($_POST['resultConfirmCommentsAway']) ? $_POST['resultConfirmCommentsAway'] : '';
			if ($matchCommentsHome) { $matchComments = $match->comments.PHP_EOL.__('Home:','racketmanager').':'.$matchCommentsHome; }
			if ($matchCommentsAway) { $matchComments = $match->comments.PHP_EOL.__('Away:','racketmanager').':'.$matchCommentsAway; }
			if ( $_POST['updateRubber'] == 'results' ) {
				if ( $userCanUpdate ) {
					$playerFound = false;
					if ( $userType == 'player' ) {
						if ( $userTeam == 'home' ) {
							if ( $match->teams['home']->captainId == get_current_user_id() ) {
								$playerFound = true;
							}
							$club = $match->teams['home']->affiliatedclub;
						} else {
							if ( $match->teams['away']->captainId == get_current_user_id() ) {
								$playerFound = true;
							}
							$club = $match->teams['away']->affiliatedclub;
						}
						if ( !$playerFound ) {
							$playerRoster = $racketmanager->getRoster( array('player' => get_current_user_id(), 'club' => $club, 'inactive' => true) );
							$playerRosterId = $playerRoster[0]->roster_id;
							for ($ix = 0; $ix < $num_rubbers; $ix++) {
								$homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : NULL;
								$homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : NULL;
								$awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : NULL;
								$awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : NULL;
								if ( $userTeam == 'home' ) {
									if ( $playerRosterId == $homeplayer1 || $playerRosterId == $homeplayer2 ) {
										$playerFound = true;
									}
								} else {
									if ( $playerRosterId == $awayplayer1 || $playerRosterId == $awayplayer2 ) {
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
					$matchConfirmed = $this->updateRubberResults( $match, $num_rubbers, $lm_options);
				}
			} elseif ( $_POST['updateRubber'] == 'confirm' ) {
				$matchConfirmed = $this->confirmRubberResults();
			}

			if ( $matchConfirmed ) {
				$matchUpdatedby = $this->updateMatchStatus( $matchId, $matchConfirmed, $home_team, $away_team, $matchComments );
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
					$home_points[$matchId] = array_sum($matchRubbers['homepoints']);
					$away_points[$matchId] = array_sum($matchRubbers['awaypoints']);
					$home_team[$matchId] = $home_team;
					$away_team[$matchId] = $away_team;
					$custom[$matchId] = array();
					$season = $_POST['current_season'];
					if ( $league->is_championship ) {
						$round = $league->championship->getFinals($_POST['match_round'])['round'];
						$league->championship->updateFinalResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $round, $season  );
						$msg = __('Match saved','racketmanager');
					} else {
						$matchCount = $league->_updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season );
						if ( $matchCount > 0 ) {
							$msg = sprintf(__('Saved Results of %d matches','racketmanager'), $matchCount);
						} else {
							$msg = __('No matches to save','racketmanager');
						}
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
			array_push($return,$msg,$error,$matchRubbers['homepoints'],$matchRubbers['awaypoints']);

			die(json_encode($return));
		} else {
			die(0);
		}
	}

	public function resultNotification($matchStatus, $matchMessage, $match, $matchUpdatedby=false) {
		global $racketmanager;
		$emailTo = $racketmanager->getConfirmationEmail($match->league->competitionType);
		$rm_options = $racketmanager->getOptions();
		$resultNotification = $rm_options[$match->league->competitionType]['resultNotification'];

		if ( $emailTo > '' ) {
			$messageArgs = array();
			$messageArgs['league'] = $match->league->id;
			if ( $match->league->is_championship ) {
				$messageArgs['round'] = $match->final_round;
			} else {
				$messageArgs['matchday'] = $match->match_day;
			}
			$headers = array();
			$headers['from'] = $racketmanager->getFromUserEmail();
			$subject = $racketmanager->site_name." - ".$matchMessage." - ".$match->league->title." - ".$match->match_title;
			$message = racketmanager_result_notification($match->id, $messageArgs );
			$racketmanager->lm_mail($emailTo, $subject, $message, $headers);
			if ( $matchStatus == 'P' ) {
				$emailFrom = $emailTo;
				unset($headers['from']);
				$headers['from'] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
				$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
				$emailTo = '';
				if ( $matchUpdatedby == 'home' ) {
					if ( $resultNotification == 'captain' ) {
						$emailTo = $match->teams['away']->contactemail;
					} elseif ( $resultNotification == 'secretary' ) {
						$club = get_club($match->teams['away']->affiliatedclub);
						$emailTo = isset($club->matchSecretaryEmail) ? $club->matchSecretaryEmail : '';
					}
				} else {
					if ( $resultNotification == 'captain' ) {
						$emailTo = $match->teams['home']->contactemail;
					} elseif ( $resultNotification == 'secretary' ) {
						$club = get_club($match->teams['away']->affiliatedclub);
						$emailTo = isset($club->matchSecretaryEmail) ? $club->matchSecretaryEmail : '';
					}
				}
				if ( $emailTo > '' ) {
					$subject = $racketmanager->site_name." - ".$match->league->title." - ".$match->match_title." - Result confirmation required";
					$message = racketmanager_captain_result_notification($match->id, $messageArgs );
					$racketmanager->lm_mail($emailTo, $subject, $message, $headers);
				}
			}
		}
	}

	/**
	* update results for each rubber
	*
	*/
	public function updateRubberResults( $match, $numRubbers, $options ) {
		global $wpdb, $racketmanager, $league, $match, $matchRubbers;

		$matchConfirmed = '';
		for ($ix = 0; $ix < $numRubbers; $ix++) {
			$rubberId       = $_POST['id'][$ix];
			$homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : NULL;
			$homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : NULL;
			$awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : NULL;
			$awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : NULL;
			$custom         = isset($_POST['custom'][$ix]) ? $_POST['custom'][$ix] : "";
			$winner         = $loser = '';
			$homescore      = $awayscore = 0;
			$sets           = $custom['sets'];

			foreach ( $sets as $set ) {
				if ( $set['player1'] !== NULL && $set['player2'] !== NULL ) {
					if ( $set['player1'] > $set['player2']) {
						$homescore += 1;
					} elseif ( $set['player1'] < $set['player2']) {
						$awayscore += 1;
					} elseif ( $set['player1'] == 'S' ){
						$homescore += 0.5;
						$awayscore += 0.5;
					}
				}
			}

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
				$this->checkPlayerResult($match, $rubberId, $homeplayer1, $match->home_team, $checkOptions);
				$this->checkPlayerResult($match, $rubberId, $homeplayer2, $match->home_team, $checkOptions);
				$this->checkPlayerResult($match, $rubberId, $awayplayer1, $match->away_team, $checkOptions);
				$this->checkPlayerResult($match, $rubberId, $awayplayer2, $match->away_team, $checkOptions);
			}
		}

		return $matchConfirmed;
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
	public function updateMatchStatus( $matchId, $matchConfirmed, $homeTeam, $awayTeam, $comments ) {
		global $wpdb, $racketmanager, $league, $match;

		$userid = get_current_user_id();
		$homeRoster = $racketmanager->getRoster(array("count" => true, "team" => $homeTeam, "player" => $userid, "inactive" => true));
		if ( $homeRoster > 0 ) { //Home captain
			$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `home_captain` = %d, `comments` = '%s' WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $comments, $matchId));
			return 'home';
		} else {
			$awayRoster = $racketmanager->getRoster(array("count" => true, "team" => $awayTeam, "player" => $userid, "inactive" => true));
			if ( $awayRoster > 0 ) { // Away Captain
				$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `away_captain` = %d, `comments` = '%s' WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $comments, $matchId));
				return 'away';
			} else {
				$matchConfirmed = 'A'; //Admin user
				$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `comments` = '%s' WHERE `id` = '%d'", get_current_user_id(), $matchConfirmed, $comments, $matchId));
				return 'admin';
			}
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
	public function checkPlayerResult( $match, $rubber, $rosterId, $team, $options ) {
		global $wpdb, $racketmanager;

		$player = $racketmanager->getRosterEntry($rosterId, $team);
		if ( !empty($player->system_record) ) return;

		$teamName = get_team($team)->title;
		$currTeamNum = substr($teamName,-1);

		if ( $player ) {
			$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_results_checker} WHERE `player_id` = %d AND `match_id` = %d", $player->player_id, $match->id) );
		}

		if ( isset($options['rosterLeadTime']) ) {
			if ( isset($player->created_date) ) {
				$matchDate = new DateTime($match->date);
				$rosterDate = new DateTime($player->created_date);
				$interval = $rosterDate->diff($matchDate);
				if ( $interval->days < intval($options['rosterLeadTime']) ) {
					$error = sprintf(__('player registered with club only %d days before match','racketmanager'), $interval->days);
					$racketmanager->addResultCheck($match, $team, $player->player_id, $error );
				} elseif ( $interval->invert ) {
					$error = sprintf(__('player registered with club %d days after match','racketmanager'), $interval->days);
					$racketmanager->addResultCheck($match, $team, $player->player_id, $error );
				}
			}
		}

		if ( isset($match->match_day) ) {
			$sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

			$count = $wpdb->get_var($sql);
			if ( $count > 0 ) {
				$error = sprintf(__('player has already played on match day %d','racketmanager'), $match->match_day);
				$racketmanager->addResultCheck($match, $team, $player->player_id, $error );
			}

			if ( isset($options['playedRounds']) ) {
				$league = get_league($match->league_id);
				$numMatchDays = $league->seasons[$match->season]['num_match_days'];
				if ( $match->match_day > ($numMatchDays - $options['playedRounds']) ) {
					$sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

					$count = $wpdb->get_var($sql);
					if ( $count == 0 ) {
						$error = sprintf(__('player has not played before the final %d match days','racketmanager'), $options['playedRounds']);
						$racketmanager->addResultCheck($match, $team, $player->player_id, $error );
					}
				}

			}
			if ( isset($options['playerLocked']) ) {
				$competition = get_competition($match->league->competition_id);
				$playerStats = $competition->getPlayerStats(array('season' => $match->season, 'roster' => $rosterId));
				$prevTeamNum = $playdowncount = $prevMatchDay = 0;
				$teamplay = array();
				foreach ( $playerStats AS $playerStat ) {
					foreach ( $playerStat->matchdays AS $m => $matchDay) {
						if ( $prevMatchDay != $matchDay->match_day ) {
							$i = 0;
						}
						$teamNum = substr($matchDay->team_title,-1) ;
						if (isset($teamplay[$teamNum])) $teamplay[$teamNum] ++;
						else $teamplay[$teamNum] = 1;
					}
					foreach ( $teamplay AS $teamNum => $played) {
						if ($teamNum < $currTeamNum) {
							if ($played > 2) {
								$error = sprintf(__('player is locked to team %d','racketmanager'), $teamNum);
								$racketmanager->addResultCheck($match, $team, $player->player_id, $error );
							}
						}
					}
				}
			}
		}

		return;
	}

	/**
	* save roster requests
	*
	* @see templates/club.php
	*/
	public function rosterRequest() {
		global $wpdb, $racketmanager;

		$return = array();
		$msg = '';
		$error = false;
		$errorField = array();
		$errorMsg = array();
		$errorId = 0;
		$rosterFound = false;
		$custom = array();
		check_admin_referer('roster-request');
		$affiliatedClub = $_POST['affiliatedClub'];
		if ( $_POST['firstName'] == '' ) {
			$error = true;
			$errorField[$errorId] = "firstName";
			$errorMsg[$errorId] = "First name required";
			$errorId ++;
		} else {
			$firstName = trim($_POST['firstName']);
		}
		if ( $_POST['surname'] == '' ) {
			$error = true;
			$errorField[$errorId] = "surname";
			$errorMsg[$errorId] = "Surname required";
			$errorId ++;
		} else {
			$surname = trim($_POST['surname']);
		}
		if ( !isset($_POST['gender']) || $_POST['gender'] == '' ) {
			$error = true;
			$errorField[$errorId] = "gender";
			$errorMsg[$errorId] = "Gender required";
			$errorId ++;
		} else {
			$gender = $_POST['gender'];
		}
		if ( !isset($_POST['btm']) || $_POST['btm'] == '' ) {
			$btmSupplied = false;
			$btm = '';
		} else {
			$btmSupplied = true;
			$btm = $_POST['btm'];
		}
		if ( !isset($_POST['email']) || $_POST['email'] == '' ) {
			$emailSupplied = false;
			$email = '';
		} else {
			$emailSupplied = true;
			$email = $_POST['email'];
		}

		if ( !$error ) {
			$fullName = $firstName . ' ' . $surname;
			$player = $racketmanager->getPlayer(array('fullname' => $fullName));
			if ( !$player ) {
				$playerId = $racketmanager->addPlayer( $firstName, $surname, $gender, $btm, $email);
				$rosterFound = false;
			} else {
				$playerId = $player->ID;
				$rosterCount = $racketmanager->getRoster(array('club' => $affiliatedClub, 'player' => $playerId, 'inactive' => true, 'count' => true));
				if ( $rosterCount == 0 ) {
					$rosterFound = false;
				} else {
					$rosterFound = true;
				}
			}
			if ( $rosterFound == false ) {
				$club = get_club($affiliatedClub);
				$rosterRequestCount = $club->getRosterRequests( array('count' => true, 'firstName' => $firstName, 'surname' => $surname) );
				if ( $rosterRequestCount == 0 ) {
					$userid = get_current_user_id();
					if ( $btmSupplied  ) {
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->racketmanager_roster_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `btm`, `player_id`, `requested_date`, `requested_user`) values (%d, '%s', '%s', '%s', %d, %d, now(), %d) ", $affiliatedClub, $firstName, $surname, $gender, $btm, $playerId, $userid ) );
					} else {
						$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->racketmanager_roster_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `player_id`, `requested_date`, `requested_user`) values (%d, '%s', '%s', '%s', %d, now(), %d)", $affiliatedClub, $firstName, $surname, $gender, $playerId, $userid ) );
					}
					$rosterRequestId = $wpdb->insert_id;
					$rmOptions = $racketmanager->getOptions();
					$options = $rmOptions['rosters'];
					if ( $options['rosterConfirmation'] == 'auto' ) {
						$club->approveRosterRequest( $rosterRequestId );
						$action = 'add';
						$msg = __('Player added to club','racketmanager');
					} else {
						$action = 'request';
						$msg = __('Player request submitted','racketmanager');
					}
					if ( isset($options['rosterConfirmationEmail']) && !is_null($options['rosterConfirmationEmail']) ) {
						$clubName = get_club($affiliatedClub)->name;
						$emailTo = $options['rosterConfirmationEmail'];
						$messageArgs = array();
						$messageArgs['action'] = $action;
						$messageArgs['club'] = $clubName;
						$headers = array();
						$headers['from'] = $racketmanager->getFromUserEmail();
						$subject = $racketmanager->site_name." - ".$msg." - ".$clubName;
						$message = racketmanager_roster_notification($messageArgs);
						$racketmanager->lm_mail($emailTo, $subject, $message, $headers);
					}
				} else {
					$error = true;
					$msg = __('Player request already exists','racketmanager');
				}
			} else {
				$error = true;
				$msg = __('Player already registered with club','racketmanager');
			}
		} else {
			$msg = __('Error in player request','racketmanager');
		}

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

		$userid = get_current_user_id();
		foreach ( $_POST['roster'] AS $roster_id ) {
			$racketmanager->delRoster( intval($roster_id) );
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

		$captain = $_POST['captain-'.$competitionId.'-'.$teamId];
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
	public function clubUpdate() {
		global $wpdb, $racketmanager;

		$updates = false;
		$return = array();
		$msg = '';
		check_admin_referer('club-update');
		$clubId = $_POST['clubId'];

		$contactno = $_POST['clubContactNo'];
		$facilities = $_POST['facilities'];
		$founded = $_POST['founded'];
		$matchSecretaryName = $_POST['matchSecretaryName'];
		$matchSecretaryId = $_POST['matchSecretaryId'];
		$matchSecretaryContactNo = $_POST['matchSecretaryContactNo'];
		$matchSecretaryEmail = $_POST['matchSecretaryEmail'];
		$website = $_POST['website'];

		$club = get_club($clubId);

		if ( $club->contactno != $contactno || $club->facilities != $facilities || $club->founded != $founded || $club->matchsecretary != $matchSecretaryId || $club->website != $website ) {
		}
		if ( $club->matchSecretaryContactNo != $matchSecretaryContactNo || $club->matchSecretaryEmail != $matchSecretaryEmail ) {
			$update = $racketmanager->updatePlayerDetails($matchSecretaryId, $matchSecretaryContactNo, $matchSecretaryEmail);
			if ($update) {
				$updates = true;
			} else {
				$updates = false;
				$msg = "error updating match secretary";
			}
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
			$playerRoster = $racketmanager->getRoster(array('club' => $affiliatedclub, 'player' => $playerId));
			$playerRosterId = $playerRoster[0]->roster_id;
			$affiliatedClubName = get_club($affiliatedclub)->name;
		}
		$competitions = isset($_POST['competition']) ? $_POST['competition'] : array();
		if ( empty($competitions) ) {
			$error = true;
			$errorField[$errorId] = 'competition';
			$errorMsg[$errorId] = __('You must select a competition to enter', 'racketmanager');
			$errorId ++;
		} else {
			$partners = isset($_POST['partner']) ? $_POST['partner'] : array();
			foreach ($competitions AS $competition) {
				$competition = get_competition($competition);
				if ( substr($competition->type,1,1) == 'D' ) {
					$partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;

					if ( empty($partnerId) ) {
						$error = true;
						$errorField[$errorId] = 'partner['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Partner not selected for %s', '$racketmanager'), $competition->name);
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
			$tournamentEntrys = array();
			$i = 0;
			foreach ($competitions AS $i => $competitionId) {
				$tournamentEntry = array();
				$partner = '';
				$partnerName = '';
				$newTeam = false;
				$competition = get_competition($competitionId);
				$tournamentEntry['competitionName'] = $competition->name;
				if (isset($competition->primary_league)) {
					$league = $competition->primary_league;
				} else {
					$leagues = $competition->getLeagues(array( 'competition' => $competition->id ));
					$league = get_league(array_key_first($competition->league_index))->id;
				}
				$team = $playerName;
				if ( substr($competition->type,1,1) == 'D' ) {
					$partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;
					$partner = $racketmanager->getRosterEntry($partnerId);
					$partnerName = $partner->fullname;
					$team .= ' / '.$partnerName;
					$tournamentEntry['partner'] = $partnerName;
				}
				$teamId = $racketmanager->getTeamId($team);
				if (!$teamId) {
					if ( $partnerName != '' ) {
						$team2 = $partnerName.' / '.$playerName;
						$teamId = $racketmanager->getTeamId($team2);
						if (!$teamId) {
							$newTeam = true;
						}
					} else {
						$newTeam = true;
					}
				}
				if ($newTeam) {
					$teamId = $racketmanager->addPlayerTeam( $playerName, $playerRosterId, $partnerName, $partnerId, $contactNo, $contactEmail, $affiliatedclub, $league );
				}
				$racketmanager->addTeamtoTable($league, $teamId, $season);
				$tournamentEntrys[$i] = $tournamentEntry;
			}
			$headers = array();
			$headers[] = 'From: '.$user->display_name.' <'.$racketmanager->admin_email.'>';
			if ( isset($user->user_email) ) {
				$headers[] = 'Cc: '.$user->display_name.' <'.$user->user_email.'>';
			}
			$organisationName = $racketmanager->site_name;
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'tournament-entry', array( 'tournamentEntries' => $tournamentEntrys, 'organisationName' => $organisationName, 'season' => $season, 'tournamentSeason' => $tournamentSeason, 'contactno' => $contactno, 'contactemail' => $contactemail, 'player' => $playerName, 'club' => $affiliatedClubName ), 'email' );
			$racketmanager->lm_mail($emailTo, $emailSubject, $emailMessage, $headers);
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
			foreach ($competitions AS $competitionId) {
				$competition = get_competition($competitionId);
				$team = isset($teams[$competition->id]) ? $teams[$competition->id] : 0;
				if ( empty($team) ) {
					$error = true;
					$errorField[$errorId] = 'team['.$competition->id.']';
					$errorMsg[$errorId] = sprintf(__('Team not selected for %s', '$racketmanager'), $competition->name);
					$errorId ++;
				} else {
					$captain = isset($captains[$competition->id]) ? $captains[$competition->id] : 0;
					$captainId = isset($captainIds[$competition->id]) ? $captainIds[$competition->id] : 0;
					$contactno = isset($contactnos[$competition->id]) ? $contactnos[$competition->id] : '';
					$contactemail = isset($contactemails[$competition->id]) ? $contactemails[$competition->id] : '';
					$matchday = isset($matchdays[$competition->id]) ? $matchdays[$competition->id] : '';
					$matchtime = isset($matchtimes[$competition->id]) ? $matchtimes[$competition->id] : '';
					if ( empty($captain) ) {
						$error = true;
						$errorField[$errorId] = 'captain['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Captain not selected for %s', '$racketmanager'), $competition->name);
						$errorId ++;
					} else {
						if ( empty($contactno) || empty($contactemail) ) {
							$error = true;
							$errorField[$errorId] = 'captain['.$competition->id.']';
							$errorMsg[$errorId] = sprintf(__('Captain contact details missing for %s', '$racketmanager'), $competition->name);
							$errorId ++;
						}
					}
					if ( empty($matchday) ) {
						$error = true;
						$errorField[$errorId] = 'matchday['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Match day not selected for %s', '$racketmanager'), $competition->name);
						$errorId ++;
					}
					if ( empty($matchtime) ) {
						$error = true;
						$errorField[$errorId] = 'matchtime['.$competition->id.']';
						$errorMsg[$errorId] = sprintf(__('Match time not selected for %s', '$racketmanager'), $competition->name);
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
			foreach ($competitions AS $i => $competitionId) {
				$cupEntry = array();
				$competition = get_competition($competitionId);
				$cupEntry['competitionName'] = $competition->name;
				if (isset($competition->primary_league)) {
					$league = $competition->primary_league;
				} else {
					$leagues = $competition->getLeagues(array( 'competition' => $competition->id ));
					$league = get_league(array_key_first($competition->league_index))->id;
				}
				$teamId = isset($teams[$competition->id]) ? $teams[$competition->id] : 0;
				if ( $teamId ) {
					$team = $club->getTeam($teamId);
					$captain = isset($captains[$competition->id]) ? $captains[$competition->id] : 0;
					$captainId = isset($captainIds[$competition->id]) ? $captainIds[$competition->id] : 0;
					$contactno = isset($contactnos[$competition->id]) ? $contactnos[$competition->id] : '';
					$contactemail = isset($contactemails[$competition->id]) ? $contactemails[$competition->id] : '';
					$matchday = isset($matchdays[$competition->id]) ? $matchdays[$competition->id] : '';
					$matchtime = isset($matchtimes[$competition->id]) ? $matchtimes[$competition->id] : '';
					$teamInfo = $competition->getTeamInfo($teamId);
					if ( !$teamInfo ) {
						$team_competition_id = $racketmanager->addTeamCompetition( $teamId, $competitionId, $captainId, $contactno, $contactemail, $matchday, $matchtime );
					} else {
					$returnMsg = $this->updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime);
					}
				}
				$racketmanager->addTeamtoTable($league, $teamId, $season);
				$cupEntry['competitionName']= $competition->name;
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
			$racketmanager->lm_mail($emailTo, $emailSubject, $emailMessage, $headers);

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
			foreach ($competitions AS $competitionId) {
				$competition = get_competition($competitionId);
				$teams = isset($teamCompetition[$competition->id]) ? $teamCompetition[$competition->id] : array();
				if ( empty($teams) ) {
					$error = true;
					$errorField[$errorId] = 'competition['.$competition->id.']';
					$errorMsg[$errorId] = sprintf(__('No teams selected for %s', '$racketmanager'), $competition->name);
					$errorId ++;
				} else {
					foreach ($teams AS $teamId) {
						$teamCompetitionTitle = isset($teamCompetitionTitles[$competition->id][$teamId]) ? $teamCompetitionTitles[$competition->id][$teamId] : '';
						$captain = isset($captains[$competition->id][$teamId]) ? $captains[$competition->id][$teamId] : 0;
						$captainId = isset($captainIds[$competition->id][$teamId]) ? $captainIds[$competition->id][$teamId] : 0;
						$contactno = isset($contactnos[$competition->id][$teamId]) ? $contactnos[$competition->id][$teamId] : '';
						$contactemail = isset($contactemails[$competition->id][$teamId]) ? $contactemails[$competition->id][$teamId] : '';
						$matchday = isset($matchdays[$competition->id][$teamId]) ? $matchdays[$competition->id][$teamId] : '';
						$matchtime = isset($matchtimes[$competition->id][$teamId]) ? $matchtimes[$competition->id][$teamId] : '';
						if ( empty($captain) ) {
							$error = true;
							$errorField[$errorId] = 'captain['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Captain not selected for %s', '$racketmanager'), $teamCompetitionTitle);
							$errorId ++;
						} else {
							if ( empty($contactno) || empty($contactemail) ) {
								$error = true;
								$errorField[$errorId] = 'captain['.$competition->id.']['.$teamId.']';
								$errorMsg[$errorId] = sprintf(__('Captain contact details missing for %s', '$racketmanager'), $teamCompetitionTitle);
								$errorId ++;
							}
						}
						if ( empty($matchday) ) {
							$error = true;
							$errorField[$errorId] = 'matchday['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Match day not selected for %s', '$racketmanager'), $teamCompetitionTitle);
							$errorId ++;
						}
						if ( empty($matchtime) ) {
							$error = true;
							$errorField[$errorId] = 'matchtime['.$competition->id.']['.$teamId.']';
							$errorMsg[$errorId] = sprintf(__('Match time not selected for %s', '$racketmanager'), $teamCompetitionTitle);
							$errorId ++;
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
			$competitionEntries['competitions'] = array();
			$i = 0;
			foreach ($competitions AS $i => $competitionId) {
				if (($key = array_search($competitionId, $leagueCompetitions)) !== false) {
    			unset($leagueCompetitions[$key]);
				}
				$competitionTeams = explode(',', $_POST['competitionTeams'][$competition->id]);
				$competitionEntry = array();
				$competition = get_competition($competitionId);
				$competitionEntry['competitionName'] = $competition->name;
				$teams = isset($teamCompetition[$competition->id]) ? $teamCompetition[$competition->id] : array();
				$leagueEntries = array();
				foreach ($teams AS $t => $teamId) {
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
					$teamInfo = $competition->getTeamInfo($teamId);
					if ( !$teamInfo ) {
						$team_competition_id = $racketmanager->addTeamCompetition( $teamId, $competitionId, $captainId, $contactno, $contactemail, $matchday, $matchtime );
					} else {
						$returnMsg = $this->updateTeamCompetition($competitionId, $teamId, $captainId, $contactno, $contactemail, $matchday, $matchtime);
					}
					$competition->markTeamsEntered($teamId, $season);
					$leagueEntry['teamName'] = $teamCompetitionTitle;
					$leagueEntry['captain'] = $captain;
					$leagueEntry['contactno'] = $contactno;
					$leagueEntry['contactemail'] = $contactemail;
					$leagueEntry['matchday'] = $matchday;
					$leagueEntry['matchtime'] = $matchtime;
					$leagueEntries[] = $leagueEntry;
				}
				foreach ($competitionTeams as $key => $teamId) {
					$competition->markTeamsWithdrawn($season, $affiliatedclub, $teamId);
				}
				$competitionEntry['teams'] = $leagueEntries;
				$competitionDetails[] = $competitionEntry;
				$competition->settings['numCourtsAvailable'][$affiliatedclub] = $numCourtsAvailable;
				$racketmanager->editCompetition($competitionId, $competition->name, $competition->settings);
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
			$racketmanager->lm_mail($emailTo, $emailSubject, $emailMessage, $headers);
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
		$messageSent = false;

		$competition = get_competition($_POST['competitonId']);
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
		$messageSent = false;

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
			$insert = add_user_meta($userid, $metaKey, $id);
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
		$match = get_match($matchId);

		$messageSent = false;
		$return = array();
		$clubs = $this->getClubs();

		$headers = array();
		$fromEmail = $this->getConfirmationEmail($match->league->competitionType);
		$headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$organisationName = $racketmanager->site_name;

		$emailSubject = $racketmanager->site_name." - ".$match->league->title." - ".$match->getTitle()." Match result pending";
		$emailTo = '';
		if ( isset($match->teams['home']->contactemail) ) {
			$emailTo = $match->teams['home']->captain.' <'.$match->teams['home']->contactemail.'>';
			$club = get_club($match->teams['home']->affiliatedclub);
			if ( isset($club->matchSecretaryEmail) ) {
				$headers[] = 'cc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
			}
			$actionURL = $racketmanager->site_url.'/match/'.seoUrl($match->league->title).'/'.$match->season.'/day'.$match->match_day.'/'.seoUrl($match->teams['home']->title).'-vs-'.seoUrl($match->teams['away']->title);
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'match-result-pending', array( 'actionURL' => $actionURL, 'organisationName' => $organisationName ), 'email' );
			$this->lm_mail($emailTo, $emailSubject, $emailMessage, $headers);
			$messageSent = true;
		}

		if ( $messageSent ) {
			$return['msg'] = __('Captain emailed','racketmanager');
		} else {
			$return['error'] = true;
			$return['msg'] = __('No notification','racketmanager');
		}

		die(json_encode($return));
	}

}
?>
