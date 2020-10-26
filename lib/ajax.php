<?php
/**
 * AJAX class for the WordPress plugin LeagueManager
 *
 * @author 	Kolja Schleich
 * @package	LeagueManager
 * @copyright Copyright 2008
*/
class LeagueManagerAJAX
{
	/**
	 * register ajax actions
	 *
	 * @param none
	 * @return void
	 */
	function __construct()
	{
        add_action( 'wp_ajax_leaguemanager_getPlayerName', array(&$this, 'getPlayerName') );
		add_action( 'wp_ajax_leaguemanager_add_team_from_db', array(&$this, 'addTeamFromDB') );
        add_action( 'wp_ajax_leaguemanager_add_teamplayer_from_db', array(&$this, 'addTeamPlayerFromDB') );
		add_action( 'wp_ajax_leaguemanager_set_team_roster_groups', array(&$this, 'setTeamRosterGroups') );
		add_action( 'wp_ajax_leaguemanager_save_team_standings', array(&$this, 'saveTeamStandings') );
		add_action( 'wp_ajax_leaguemanager_save_add_points', array(&$this, 'saveAddPoints') );
		add_action( 'wp_ajax_leaguemanager_insert_logo_from_library', array(&$this, 'insertLogoFromLibrary') );
		add_action( 'wp_ajax_leaguemanager_insert_home_stadium', array(&$this, 'insertHomeStadium') );
		add_action( 'wp_ajax_leaguemanager_set_match_day_popup', array(&$this, 'setMatchDayPopUp') );
		add_action( 'wp_ajax_leaguemanager_set_match_date', array(&$this, 'setMatchDate') );
		
		add_action( 'wp_ajax_leaguemanager_get_match_box', array(&$this, 'getMatchBox') );
		add_action( 'wp_ajax_nopriv_leaguemanager_get_match_box', array(&$this, 'getMatchBox') );

		add_action( 'wp_ajax_leaguemanager_show_rubbers', array(&$this, 'showRubbers') );

		add_action( 'wp_ajax_leaguemanager_view_rubbers', array(&$this, 'viewRubbers') );
		add_action( 'wp_ajax_nopriv_leaguemanager_view_rubbers', array(&$this, 'viewRubbers') );

		add_action( 'wp_ajax_leaguemanager_update_rubbers', array(&$this, 'updateRubbers') );
	}
	function LeagueManagerAJAX()
	{
		$this->__construct();
	}


	/**
	 * Ajax Response to set match index in widget
	 *
	 * @param none
	 * @return void
	 */
	function getMatchBox() {
		$widget = new LeagueManagerWidget(true);

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

    function getPlayerName() {
        global $wpdb, $leaguemanager;
        $name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';
        
        $sql = "SELECT  P.`firstname`, P.`surname`,C.`post_title` as club, R.`id` as rosterId, C.`id` as clubId  FROM $wpdb->leaguemanager_roster R, $wpdb->leaguemanager_players P, $wpdb->posts C WHERE R.`player_id` = P.`id` AND R.`removed_date` IS NULL AND C.`post_type` = 'wpclubs' AND C.`id` = R.`affiliatedclub` AND `fullname` like '%s' ORDER BY 1,2,3";
        $sql = $wpdb->prepare($sql, $name);
        $results = $wpdb->get_results($sql);
        $players = array();
        $player = array();
        foreach( $results AS $r) {
            $player['label'] = addslashes($r->firstname).' '.addslashes($r->surname).' - '.$r->club;
            $player['value'] = addslashes($r->firstname).' '.addslashes($r->surname);
            $player['id'] = $r->rosterId;
            $player['clubId'] = $r->clubId;
            $player['club'] = $r->club;
            array_push($players, $player);
        }
        die(json_encode($players));
    }
    
	/**
	 * SACK response to manually set team ranking
	 *
	 * @since 2.8
	 */
	function saveTeamStandings() {
		global $wpdb, $lmLoader, $leaguemanager;
		$ranking = $_POST['ranking'];
		$ranking = $lmLoader->adminPanel->getRanking($ranking);
		foreach ( $ranking AS $rank => $team_id ) {
			$old = $leaguemanager->getTeam( $team_id );
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

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `rank` = '%d', `status` = '%s' WHERE `team_id` = '%d'", $rank, $status, $team_id ) );
		}
	}


	/**
	* SACK response to manually set additional points
	*
	* @since 2.8
	*/
	function saveAddPoints() {
		global $wpdb, $leaguemanager;
		$team_id = intval($_POST['team_id']);
		$league_id = intval($_POST['league_id']);
        $season = $leaguemanager->getSeason($leaguemanager->getLeague($league_id))['name'];
		$points = $_POST['points'];

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `add_points` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d' AND `season` = '%s'", $points, $team_id, $league_id, $season ) );
		$leaguemanager->rankTeams(1);

		die("Leaguemanager.doneLoading('loading_".$team_id."')");
	}


	/**
	 * SACK response to get team data from database and insert into team edit form
	 *
	 * @since 2.9
	 */
	function addTeamFromDB() {
		global $leaguemanager;

		$team_id = (int)$_POST['team_id'];
		$team = $leaguemanager->getTeam( $team_id );

		$roster = '';

		$home = '';

		$logo = ( !empty($team->logo) ) ? "<img src='".$team->logo."' />" : "";
		die("
            document.getElementById('team_id').value = '".$team_id."';
			document.getElementById('team').value = '".$team->title."';
            document.getElementById('affiliatedclub').value = '".$team->affiliatedclub."';
			document.getElementById('stadium').value = '".$team->stadium."';
			document.getElementById('logo_db').value = '".$team->logo."';
			jQuery('div#logo_db_box').html('".addslashes_gpc($logo)."').fadeIn('fast');
			".$home."
			".$roster."
		");
	}

	/**
	 * SACK response to get team player data from database and insert into team edit form
	 *
	 * @since 2.9
	 */
	function addTeamPlayerFromDB() {
		global $leaguemanager;

		$team_id = (int)$_POST['team_id'];
		$team = $leaguemanager->getTeam( $team_id );
            $return = "document.getElementById('team_id').value = ".$team_id.";document.getElementById('team').value = '".$team->title."';document.getElementById('affiliatedclub').value = ".$team->affiliatedclub.";document.getElementById('teamPlayer1').value = '".$team->player[1]."';document.getElementById('teamPlayerId1').value = ".$team->playerId[1].";";
            if ( isset($team->player[2]) ) {
                $return .= "document.getElementById('teamPlayer2').value = '".$team->player[2]."';document.getElementById('teamPlayerId2').value = ".$team->playerId[2].";";
            }

		$home = '';

		die($return);
	}

	/**
	 * SACK response to display respective ProjectManager Groups as Team Roster
	 *
	 * @since 3.0
	 */
	function setTeamRosterGroups() {
		global $projectmanager;

		$roster = (int)$_POST['roster'];
		$project = $projectmanager->getProject($roster);

		if ( $projectmanager->hasCategories($project->id) ) {
			$html = '<select size="1" name="roster_group" id="roster_group">';
			$html .= '<option value="-1">'.__( 'Select Group (Optional)', 'leaguemanager' ).'</option>';
			foreach ( $projectmanager->getCategories( $project->id ) AS $category ) {
				$html .= '<option value="'.$category->id.'">'.$category->title.'</option>';
			}
			$html .= '</select>';
			//$html = wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'roster_group', 'orderby' => 'name', 'echo' => 0, 'show_option_none' => __('Select Group (Optional)', 'leaguemanager'), 'child_of' => $category ));
			$html = str_replace("\n", "", $html);
		} else {
			$html = "";
		}

		die("jQuery('span#team_roster_groups').fadeOut('fast', function () {
			jQuery('span#team_roster_groups').html('".addslashes_gpc($html)."').fadeIn('fast');
		});");
	}


	/**
	 * insert Logo from Library
	 *
	 * @param none
	 * @return void
	 */
	function insertLogoFromLibrary()
	{
		$logo = htmlspecialchars($_POST['logo']);
		$logo = 'http://' . $logo;
		$html = "<img id='logo_image' src='".$logo."' />";

		if ( $_SERVER['HTTP_HOST'] != substr($logo, 7, strlen($_SERVER['HTTP_HOST'])) ) {
			die("alert('".__('The image cannot be on a remote server', 'leaguemanager')."')");
		} else {
			die("jQuery('div#logo_db_box').fadeOut('fast', function() {
				document.getElementById('logo_db').value = '".$logo."';
				jQuery('div#logo_db_box').html('".addslashes_gpc($html)."').fadeIn('fast');
			});");
		}
	}


	/**
	 * insert home team stadium if available
	 *
	 * @param none
	 * @rturn void
	 */
	function insertHomeStadium()
	{
		global $leaguemanager;
		$team_id = (int)$_POST['team_id'];
		$i = (int)$_POST['i'];

		$team = $leaguemanager->getTeam( $team_id );
		die("document.getElementById('location[".$i."]').value = '".$team->stadium."';");
	}

	/**
	 * change all Match Day Pop-ups to match first one set
	 *
	 * @param none
	 * @rturn void
	 */
	function setMatchDayPopUp()
	{
		global $leaguemanager;
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
	 * @param none
	 * @rturn void
	 */
	function setMatchDate()
	{
		global $leaguemanager;
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
	 * build screen to show match rubbers
	 *
	 * @param none
	 * @rturn void
	 */
	function viewRubbers() {
		global $leaguemanager, $championship;
		$matchId = $_POST['matchId'];
		$match = $leaguemanager->getMatch($matchId);
		$league = $leaguemanager->getCurrentLeague();
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
<?php if ( $league->mode = 'Championship' ) {
    echo $championship->getFinalName($match->final);
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
					<th style="text-align: center;"><?php _e( 'Pair', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="1"><?php _e( 'Home Team', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="1"><?php _e( 'Away Team', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >
    
    <?php $class = '';
        $rubbers = $leaguemanager->getRubbers(array("match_id" => $matchId));
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
                        <input class="points" type="text" size="2" disabled id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
                        :
                        <input class="points" type="text" size="2" disabled id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
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
				<input class="points" type="text" size="2" disabled id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
				:
				<input class="points" type="text" size="2" disabled id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
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
	 * build screen to show match rubbers
	 *
	 * @param none
	 * @rturn void
	 */
    function showRubbers() {
		global $leaguemanager;
		$matchId = $_POST['matchId'];
		$match = $leaguemanager->getMatch($matchId);
		$league = $leaguemanager->getCurrentLeague();
		$num_sets = $league->num_sets;
		$num_rubbers = $league->num_rubbers;
		$match_type = $league->type;
		switch ($match_type) {
		case 'MD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterMen;
					$homeRoster[$r][2] = $homeRosterMen;
					$awayRoster[$r][1] = $awayRosterMen;
					$awayRoster[$r][2] = $awayRosterMen;
				}
				break;
		case 'WD':
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterWomen;
					$homeRoster[$r][2] = $homeRosterWomen;
					$awayRoster[$r][1] = $awayRosterWomen;
					$awayRoster[$r][2] = $awayRosterWomen;
				}
				break;
		case 'XD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterMen;
					$homeRoster[$r][2] = $homeRosterWomen;
					$awayRoster[$r][1] = $awayRosterMen;
					$awayRoster[$r][2] = $awayRosterWomen;
				}
				break;
		case 'LD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				$homeRoster[0][1] = $homeRosterWomen;
				$homeRoster[0][2] = $homeRosterWomen;
				$homeRoster[1][1] = $homeRosterMen;
				$homeRoster[1][2] = $homeRosterMen;
				$homeRoster[2][1] = $homeRosterMen;
				$homeRoster[2][2] = $homeRosterWomen;
				$awayRoster[0][1] = $awayRosterWomen;
				$awayRoster[0][2] = $awayRosterWomen;
				$awayRoster[1][1] = $awayRosterMen;
				$awayRoster[1][2] = $awayRosterMen;
				$awayRoster[2][1] = $awayRosterMen;
				$awayRoster[2][2] = $awayRosterWomen;
				break;
		}
	?>
<div id="matchrubbers" class="rubber-block">
	<div id="matchheader">
		<div class="leaguetitle"><?php echo $league->title ?></div>
		<div class="matchdate"><?php echo substr($match->date,0,10) ?></div>
		<div class="matchday">Week <?php echo $match->match_day ?></div>
		<div class="matchtitle"><?php echo $match->match_title ?></div>
	</div>
    <form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
        <?php wp_nonce_field( 'rubbers-match' ) ?>

        <input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $matchId ?>" />
        <input type="hidden" name="num_rubbers" value="<?php echo $num_rubbers ?>" />
        <input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
        <input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
    
        <table class="widefat" summary="" style="margin-bottom: 2em;">
            <thead>
                <tr>
					<th style="text-align: center;"><?php _e( 'Pair', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;"><?php _e( 'Home Team', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;"><?php _e( 'Away Team', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >
    
    <?php $class = '';
        $rubbers = $leaguemanager->getRubbers(array("match_id" => $matchId));
        $r = $tabbase = 0 ;
        
        foreach ($rubbers as $rubber) {
    ?>
                <tr class="rtr '.$class.'">
                    <input type="hidden" name="id[<?php echo $r ?>]" value="<?php echo $rubber->id ?>" </>
					<td rowspan="3" class="rtd centered">
						<?php echo (isset($rubber->rubber_number) ? $rubber->rubber_number : '') ?>
					</td>
					<td class="rtd">
<?php $tabindex = $tabbase + 1; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer1[<?php echo $r ?>]" id="homeplayer1_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $homeRoster[$r][1] AS $roster ) {
	isset($roster->removed_date) ? $disabled = 'disabled' : $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->home_player_1)) selected($roster->roster_id, $rubber->home_player_1 ); echo $disabled; ?>>
								<?php echo $roster->firstname ?> <?php echo $roster->surname ?>
							</option>
<?php } ?>
						</select>
					</td>

                    <?php for ( $i = 1; $i <= $num_sets; $i++ ) {
                        if (!isset($rubber->sets[$i])) {
                            $rubber->sets[$i] = array('player1' => '', 'player2' => '');
                        } ?>
<?php $tabindex = $tabbase + 10 + $i; ?>
                        <td class="rtd centered" rowspan="2">
                            <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" value="<?php echo $rubber->sets[$i]['player1'] ?>" />
                            :
<?php $tabindex = $tabbase + 11 + $i; ?>
                            <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" value="<?php echo $rubber->sets[$i]['player2'] ?>" />
                        </td>
                    <?php } ?>

                    <td class="rtd">
<?php $tabindex = $tabbase + 3; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer1[<?php echo $r ?>]" id="awayplayer1_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $awayRoster[$r][1] AS $roster ) {
	isset($roster->removed_date) ? $disabled = 'disabled' : $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->away_player_1)) selected($roster->roster_id, $rubber->away_player_1 ); echo $disabled; ?>>
								<?php echo $roster->firstname ?> <?php echo $roster->surname ?>
							</option>
<?php } ?>
						</select>
                    </td>
                </tr>
                <tr>
                    <td class="rtd">
<?php $tabindex = $tabbase + 2; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer2[<?php echo $r ?>]" id="homeplayer2_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $homeRoster[$r][2] AS $roster ) {
	isset($roster->removed_date) ? $disabled = 'disabled' : $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->home_player_2)) selected($roster->roster_id, $rubber->home_player_2 ); echo $disabled; ?>>
							<?php echo $roster->firstname ?> <?php echo $roster->surname ?>
							</option>
<?php } ?>
						</select>
                    </td>
                    <td class="rtd">
<?php $tabindex = $tabbase + 4; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer2[<?php echo $r ?>]" id="awayplayer2_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $awayRoster[$r][2] AS $roster ) {
    isset($roster->removed_date) ? $disabled = 'disabled' : $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->away_player_2)) selected($roster->roster_id, $rubber->away_player_2 ); echo $disabled; ?>>
							<?php echo $roster->firstname ?> <?php echo $roster->surname ?>
                            </option>
<?php } ?>
						</select>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="rtd" style="text-align: center;">
                        <input class="points" type="text" size="2" disabled id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" value="<?php echo (isset($rubber->home_points) ? $rubber->home_points : '') ?>" />
                        :
                        <input class="points" type="text" size="2" disabled id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" value="<?php echo (isset($rubber->away_points) ? $rubber->away_points : '') ?>" />
                    </td>
                </tr>
    <?php
		$tabbase +=100;
        $r ++;
        }
    ?>
            </tbody>
        </table>
        <input type="hidden" name="updateRubber" value="results" />
        <button tabindex="500" class="button button-primary" type="button" id="updateRubberResults" onclick="Leaguemanager.updateRubbers(this)">Update Results</button>
        <p id="UpdateResponse"></p>
    </form>
</div>
<?php
    die();
    }
    
    function updateRubbers()
    {
        global $wpdb, $leaguemanager;
        
        if ( isset($_POST['updateRubber'])) {
            check_admin_referer('rubbers-match');
            
            if ( 'results' == $_POST['updateRubber'] ) {

                $homepoints = isset($_POST['home_points']) ? $_POST['home_points'] : array();
                $awaypoints = isset( $_POST['away_points']) ? $_POST['away_points'] : array();
                $num_rubbers = $_POST['num_rubbers'];
                $home_team = $_POST['home_team'];
                $away_team = $_POST['away_team'];
                $homepoints = array();
                $awaypoints = array();
                $return = array();
                
                for ($ix = 0; $ix < $num_rubbers; $ix++) {
                    
                    $rubberId       = $_POST['id'][$ix];
                    $homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : NULL;
                    $homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : NULL;
                    $awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : NULL;
                    $awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : NULL;
                    $custom         = isset($_POST['custom'][$ix]) ? $_POST['custom'][$ix] : "";
                    $winner         = $loser = '';
                    $homescore      = '0';
                    $awayscore      = '0';
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
                        $winner = $home_team;
                        $loser = $away_team;
                    } elseif ( $homescore < $awayscore) {
                        $winner = $away_team;
                        $loser = $home_team;
					} elseif ( 'NULL' === $homescore && 'NULL' === $awayscore ) {
						$winner = 0;
						$loser = 0;
					} elseif ( '' == $homescore && '' == $awayscore ) {
						$winner = 0;
						$loser = 0;
					} else {
						$winner = -1;
						$loser = -1;
                    }
                    
                    if (isset($homeplayer1) || isset($homeplayer2) || isset($awayplayer1) || isset($awayplayer2) || empty($homescore) || empty($awayscore) ) {
                        $homescore = !empty($homescore) ? $homescore : "0";
                        $awayscore = !empty($awayscore) ? $awayscore : "0";
                        $homepoints[$ix] = $homescore;
                        $awaypoints[$ix] = $awayscore;

                        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_rubbers} SET `home_points` = '%s',`away_points` = '%s',`home_player_1` = '%s',`home_player_2` = '%s',`away_player_1` = '%s',`away_player_2` = '%s',`winner_id` = '%d',`loser_id` = '%d',`custom` = '%s' WHERE `id` = '%d'", $homescore, $awayscore, $homeplayer1, $homeplayer2, $awayplayer1, $awayplayer2, $winner, $loser, maybe_serialize($custom), $rubberId));
                        $msg = 'Results Updated';
                    } else {
                        $msg = 'Nothing to update';
                    }
                }
            }
            
            array_push($return,$msg,$homepoints,$awaypoints);
            
            die(json_encode($return));
		} else {
			die(0);
		}
    }
}
?>
