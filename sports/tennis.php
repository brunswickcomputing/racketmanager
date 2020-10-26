<?php
/**
 * Tennis Class 
 * 
 * @author 	Kolja Schleich
 * @package	LeagueManager
 * @copyright Copyright 2008
*/
class LeagueManagerTennis extends LeagueManager
{

	/**
	 * sports key
	 *
	 * @var string
	 */
	var $key = 'tennis';


	/**
	 * load specifif settings
	 *
	 * @param none
	 * @return void
	 */
	function __construct()
	{
		add_filter( 'leaguemanager_sports', array(&$this, 'sports') );
		add_filter( 'rank_teams_'.$this->key, array(&$this, 'rankTeams') );
		add_filter( 'team_points_'.$this->key, array(&$this, 'calculatePoints'), 10, 3 );

		add_filter( 'leaguemanager_point_rules_list', array(&$this, 'getPointRuleList') );
		add_filter( 'leaguemanager_point_rules',  array(&$this, 'getPointRules') );

		add_filter( 'leaguemanager_export_matches_header_'.$this->key, array(&$this, 'exportMatchesHeader') );
		add_filter( 'leaguemanager_export_matches_data_'.$this->key, array(&$this, 'exportMatchesData'), 10, 2 );
		add_filter( 'leaguemanager_import_matches_'.$this->key, array(&$this, 'importMatches'), 10, 3 );
        add_filter( 'leaguemanager_import_fixtures_'.$this->key, array(&$this, 'importFixtures'), 10, 2);
        add_filter( 'leaguemanager_import_results_'.$this->key, array(&$this, 'importResults'), 10, 3 );
		add_filter( 'leaguemanager_export_teams_header_'.$this->key, array(&$this, 'exportTeamsHeader') );
		add_filter( 'leaguemanager_export_teams_data_'.$this->key, array(&$this, 'exportTeamsData'), 10, 2 );
		add_filter( 'leaguemanager_import_teams_'.$this->key, array(&$this, 'importTeams'), 10, 2 );

		add_filter( 'leaguemanager_matchtitle_'.$this->key, array(&$this, 'matchTitle'), 10, 3 );

		add_action( 'matchtable_header_'.$this->key, array(&$this, 'displayMatchesHeader'), 10, 0);
		add_action( 'matchtable_columns_'.$this->key, array(&$this, 'displayMatchesColumns') );
		add_action( 'leaguemanager_standings_header_'.$this->key, array(&$this, 'displayStandingsHeader') );
		add_action( 'leaguemanager_standings_columns_'.$this->key, array(&$this, 'displayStandingsColumns'), 10, 2 );
		add_action( 'team_edit_form_'.$this->key, array(&$this, 'editTeam') );

		add_action( 'edit_matches_header_'.$this->key, array(&$this, 'editMatchesHeader') );
		add_action( 'edit_matches_columns_'.$this->key, array(&$this, 'editMatchesColumns'), 10, 5 );	
		
		add_filter( 'leaguemanager_done_matches_'.$this->key, array(&$this, 'getNumDoneMatches'), 10, 2 );
		add_filter( 'leaguemanager_won_matches_'.$this->key, array(&$this, 'getNumWonMatches'), 10, 2 );
		add_filter( 'leaguemanager_tie_matches_'.$this->key, array(&$this, 'getNumTieMatches'), 10, 2 );
		add_filter( 'leaguemanager_lost_matches_'.$this->key, array(&$this, 'getNumLostMatches'), 10, 2 );
		add_action( 'leaguemanager_save_standings_'.$this->key, array(&$this, 'saveStandings'), 10, 2 );
		add_action( 'leaguemanager_get_standings_'.$this->key, array(&$this, 'getStandingsFilter'), 10, 3 );

		add_action( 'league_settings_'.$this->key, array(&$this, 'leagueSettings') );
		
		add_action( 'leaguemanager_update_results_'.$this->key, array(&$this, 'updateResults') );
	}
	function LeagueManagerSoccer()
	{
		$this->__construct();
	}


	/**
	 * add sports to list
	 *
	 * @param array $sports
	 * @return array
	 */
	function sports( $sports )
	{
		$sports[$this->key] = __( 'Tennis', 'leaguemanager' );
		return $sports;
	}


	/**
	 * get Point Rule list
	 *
	 * @param array $rules
	 * @return array
	 */
	function getPointRuleList( $rules )
	{
		$rules[$this->key] = __('Tennis', 'leaguemanager');

		return $rules;
	}


	/**
	 * get Point rules
	 *
	 * @param array $rules
	 * @return array
	 */
	function getPointRules( $rules )
	{
/*		$rules[$this->key] = array( 'forwin' => 3, 'fordraw' => 0, 'forloss' => 0, 'forwin_split' => 2, 'forloss_split' => 1 ); */
		$rules[$this->key] = array( 'forwin' => 1, 'fordraw' => 0, 'forloss' => 0, 'forwin_split' => 0, 'forloss_split' => 0, 'forshare' => 0.5 );

		return $rules;
	}


	/**
	 * add league settings
	 *
	 * @param object $league
	 * @return void
	 */
	function leagueSettings( $competition )
	{
		$competition->num_sets = isset($competition->num_sets) ? $competition->num_sets : '';
        $competition->num_rubbers = isset($competition->num_rubbers) ? $competition->num_rubbers : '';
		$competition->type = isset($competition->type) ? $competition->type : '';
		echo "<tr valign='top'>";
			echo "<th scope='row'><label for='num_sets'>".__('Number of Sets', 'leaguemanager')."</label></th>";
			echo "<td><input type='number' name='settings[num_sets]' id='num_sets' value='".$competition->num_sets."' size='3' /></td>";
		echo "</tr>";
        echo "<tr valign='top'>";
			echo "<th scope='row'><label for='num_rubbers'>".__('Number of Rubbers', 'leaguemanager')."</label></th>";
			echo "<td><input type='number' name='settings[num_rubbers]' id='num_rubbers' value='".$competition->num_rubbers."' size='3' /></td>";
        echo "</tr>";
		echo "<tr valign='top'>";
			echo "<th scope='row'><label for='competition_type'>".__('Type', 'leaguemanager')."</label></th>";
		echo "<td>";
				echo "<select size='1' name='competition_type' id='competition_type'>";
					echo "<option>"._e( 'Select', 'leaguemanager')."</option>";
					echo "<option value='WD' ".($competition->type == 'WD' ? 'selected' : '').">".__( 'Ladies Doubles', 'leaguemanager')."</option>";
					echo "<option value='MD' ".($competition->type == 'MD' ? 'selected' : '').">".__( 'Mens Doubles', 'leaguemanager')."</option>";
					echo "<option value='XD' ".($competition->type == 'XD' ? 'selected' : '').">".__( 'Mixed Doubles', 'leaguemanager')."</option>";
				echo "</select>";
			echo "</td>";
		echo "</tr>";
	}

	/**
	 * calculate Points: add match score
	 *
	 * @param array $points
	 * @param int $team_id
	 * @param array $rule
	 */
	function calculatePoints( $points, $team_id, $rule )
	{
		global $leaguemanager;

		extract($rule);
		$data = $this->getStandingsData($team_id);
		$points['plus'] = $data['sets_won'] + $data['straight_set']['win'] * $forwin + $data['split_set']['win'] * $forwin_split + $data['split_set']['lost'] * $forloss_split + $data['sets_shared'] * $forshare;
		$points['minus'] = $data['sets_allowed'] + $data['straight_set']['lost'] * $forwin + $data['split_set']['win'] * $forloss_split + $data['split_set']['lost'] * $forwin_split + $data['sets_shared'] * $forshare;
		return $points;
	}


	/**
	 * rank Teams
	 *
	 * @param array $teams
	 * @return array of teams
	 */
	function rankTeams( $teams )
	{
		foreach ( $teams AS $key => $team ) {
			$points[$key] = $team->points['plus']+$team->add_points;
			$sets_diff[$key] = $team->sets_won - $team->sets_allowed;
			$sets_won[$key] = $team->sets_won;
            $sets_allowed[$key] = $team->sets_allowed;
			$games_diff[$key] = $team->games_won - $team->games_allowed;
            $games_won[$key] = $team->games_won;
			$games_allowed[$key] = $team->games_allowed;
		}
		array_multisort( $points, SORT_DESC, $sets_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $teams );
		return $teams;
	}

	
	/**
	 * get number of done matches for partners
	 *
	 * @param int $num_done
	 * @param int $team_id
	 * @return int
	 */
	function getNumDoneMatches( $num_done, $team_id )
	{
		global $wpdb, $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
		$season = $leaguemanager->getSeason($league);

		$matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => '', "home_points" => "not_null", "away_points" => "not_null", "limit" => false) );
		foreach ( $matches AS $match ) {
			if ( isset($match->home_partner) && isset($match->guest_partner) ) {
				if ( $match->home_partner == $team_id || $match->guest_partner == $team_id )
					$num_done++;
			}
		}
		return $num_done;
	}


	/**
	 * get number of won matches for partners
	 *
	 * @param int $num_won
	 * @param int $team_id
	 * @return int
	 */
	function getNumWonMatches( $num_won, $team_id )
	{
		global $wpdb, $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
		$season = $leaguemanager->getSeason($league);

		$matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => '', "limit" => false) );
		foreach ( $matches AS $match ) {
			if ( isset($match->home_partner) && isset($match->guest_partner) ) {
				if ( $match->home_partner == $team_id && $match->winner_id == $match->home_team )
					$num_won++;
				elseif ( $match->guest_partner == $team_id && $match->winner_id == $match->away_team )
					$num_won++;
					
			}
		}
		return $num_won;
	}


	/**
	 * get number of tie matches for partners
	 *
	 * @param int $num_tie
	 * @param int $team_id
	 * @return int
	 */
	function getNumTieMatches( $num_tie, $team_id )
	{
		global $wpdb, $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
		$season = $leaguemanager->getSeason($league);

		$matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => '', "winner_id" => -1, "loser_id" => -1, "limit" => false) );
		foreach ( $matches AS $match ) {
			if ( isset($match->home_partner) && isset($match->guest_partner) ) {
				if ( $match->home_partner == $team_id || $match->guest_partner == $team_id )
					$num_tie++;
					
			}
		}
		return $num_tie;
	}
	

	/**
	 * get number of lost matches for partners
	 *
	 * @param int $num_lost
	 * @param int $team_id
	 * @return int
	 */
	function getNumLostMatches( $num_lost, $team_id )
	{
		global $wpdb, $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
		$season = $leaguemanager->getSeason($league);

		$matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => '', "limit" => false) );
		foreach ( $matches AS $match ) {
			if ( isset($match->home_partner) && isset($match->guest_partner) ) {
				if ( $match->home_partner == $team_id && $match->winner_id == $match->away_team )
					$num_lost++;
				elseif ( $match->guest_partner == $team_id && $match->winner_id == $match->home_team )
					$num_lost++;
					
			}
		}
		return $num_lost;
	}


	/**
	 * save custom standings
	 *
	 * @param int $team_id
	 * @return void
	 */
	function saveStandings( $team_id, $league_id )
	{
		global $wpdb, $leaguemanager;

		$team = $wpdb->get_results( "SELECT `custom` FROM {$wpdb->leaguemanager_table} WHERE `team_id` = {$team_id} AND `league_id` = {$league_id}" );
		$team = $team[0];
		$custom = isset($team->custom) ? maybe_unserialize($team->custom) : '';
		$custom = $this->getStandingsData($team_id, $custom);

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `custom` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d'", maybe_serialize($custom), $team_id, $league_id ) );
	}


	/**
	 * get standings table data
	 *
	 * @param object $team
	 * @param array $matches
	 */
	function getStandingsFilter( $team, $matches, $point_rule )
	{
		/*
		 * analogue to leaguemanager_save_standings_$sport filter
		 */
		$data = $this->getStandingsData( $team->id, maybe_unserialize($team->custom), $matches );
        $team->sets_allowed = $data['sets_allowed'];
        $team->sets_won = $data['sets_won'];
		$team->games_allowed = $data['games_allowed'];
        $team->games_won = $data['games_won'];
		
		return $team;
	}
	
	
	/**
	 * get standings data for given team
	 *
	 * @param int $team_id
	 * @param array $data
	 * @return array number of runs for and against as assoziative array
	 */
	function getStandingsData( $team_id, $data = array(), $matches = false )
	{
		global $leaguemanager;

        $data['straight_set'] = $data['split_set'] = array( 'win' => 0, 'lost' => 0 );
		$data['games_allowed'] = 0;
        $data['games_won'] = 0;
        $data['sets_won'] = 0;
        $data['sets_allowed'] = 0;
		$data['sets_shared'] = 0;

		$league = $leaguemanager->getCurrentLeague();
		$season = $leaguemanager->getSeason($league);

        if ( !$matches ) {
            $matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => '', "limit" => false, "cache" => false, "home_points" => 'not null', "away points" => 'not null') );
        }
        
		foreach ( $matches AS $match ) {
			if ( $match->home_team == $team_id || $match->away_team == $team_id ) {
                
                $index = ( $team_id == $match->home_team ) ? 'player2' : 'player1';

                if (isset($league->num_rubbers)) {
                    $rubbers = $leaguemanager->getRubbers( array("match_id" => $match->id));
                    foreach ( $rubbers as $rubber) {

                        if ($rubber->winner_id == $team_id) {               //home winner
                            if ($match->home_team == $team_id)  {           //home team

                                for ( $j = 1; $j <= $league->num_sets; $j++  ) {
                                    $data['games_allowed'] += $rubber->sets[$j]['player2'];
                                    $data['games_won'] += $rubber->sets[$j]['player1'];
									if ( $rubber->sets[$j]['player1'] > $rubber->sets[$j]['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] < $rubber->sets[$j]['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
                                }
								if ($rubber->away_points > "0" ) {          //away team got a set
									$data['split_set']['win'] +=1;
								} else {                                    //away team got no set
									$data['straight_set']['win'] +=1;
								}
								
                            } else {                                        //away team
                                for ( $j = 1; $j <= $league->num_sets; $j++  ) {
                                    $data['games_allowed'] += $rubber->sets[$j]['player1'];
                                    $data['games_won'] += $rubber->sets[$j]['player2'];
									if ( $rubber->sets[$j]['player2'] > $rubber->sets[$j]['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player2'] < $rubber->sets[$j]['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
                                }
								if ($rubber->home_points > "0" ) {          //home team got a set
									$data['split_set']['win'] +=1;
								} else {                                    //home team got no set
									$data['straight_set']['win'] +=1;
								}
                            }
							
                        } elseif ($rubber->loser_id == $team_id) {          //away winner
                            if ($match->home_team == $team_id) {            //home team
                                if ($rubber->home_points > "0") {           //home team got a set
                                    $data['split_set']['lost'] +=1;
                                } else {                                    //home team got no set
                                    $data['straight_set']['lost'] +=1;
                                }
                                for ( $j = 1; $j <= $league->num_sets; $j++  ) {
                                    $data['games_allowed'] += $rubber->sets[$j]['player2'];
                                    $data['games_won'] += $rubber->sets[$j]['player1'];
									if ( $rubber->sets[$j]['player1'] > $rubber->sets[$j]['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] < $rubber->sets[$j]['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
                                }
                            } else {                                        //away team
                                if ($rubber->away_points > "0") {           //away team got a set
                                    $data['split_set']['lost'] +=1;
                                } else {                                    //away team got no set
                                    $data['straight_set']['lost'] +=1;
                                }
                                for ( $j = 1; $j <= $league->num_sets; $j++  ) {
                                    $data['games_allowed'] += $rubber->sets[$j]['player1'];
                                    $data['games_won'] += $rubber->sets[$j]['player2'];
									if ( $rubber->sets[$j]['player2'] > $rubber->sets[$j]['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player2'] < $rubber->sets[$j]['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
                                }
                            }
                        
						} elseif ( $rubber->winner_id == -1 ) {										//drawn rubber
							if ($match->home_team == $team_id)  {           //home team
								
								for ( $j = 1; $j <= $league->num_sets; $j++  ) {
									$data['games_allowed'] += $rubber->sets[$j]['player2'];
									$data['games_won'] += $rubber->sets[$j]['player1'];
									if ( $rubber->sets[$j]['player1'] > $rubber->sets[$j]['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] < $rubber->sets[$j]['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
								if ($rubber->away_points > "0" ) {          //away team got a set
									$data['split_set']['win'] +=1;
								} else {                                    //away team got no set
									$data['straight_set']['win'] +=1;
								}
								
							} else {                                        //away team
								for ( $j = 1; $j <= $league->num_sets; $j++  ) {
									$data['games_allowed'] += $rubber->sets[$j]['player1'];
									$data['games_won'] += $rubber->sets[$j]['player2'];
									if ( $rubber->sets[$j]['player2'] > $rubber->sets[$j]['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[$j]['player2'] < $rubber->sets[$j]['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( $rubber->sets[$j]['player1'] == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
								if ($rubber->home_points > "0" ) {          //home team got a set
									$data['split_set']['win'] +=1;
								} else {                                    //home team got no set
									$data['straight_set']['win'] +=1;
								}
							}
							
						}
						
                    }
                } else {
                    // First check for Split Set, else it's straight set
                    if ( $match->sets[$league->num_sets]['player1'] != '' && $match->sets[$league->num_sets]['player2'] != '' ) {
                        if ( $match->winner_id == $team_id ) {
                            $data['split_set']['win'] += 1;
                            for ( $j = 1; $j <= $league->num_sets-1; $j++  ) {
                                $data['games_allowed'] += $match->sets[$j][$index];
                            }
                        } elseif ( $match->loser_id == $team_id) {
                            $data['split_set']['lost'] += 1;
                            for ( $j = 1; $j <= $league->num_sets-1; $j++  ) {
                                $data['games_allowed'] += $match->sets[$j][$index];
                            }
                            $data['games_allowed'] += 1;
                        }
                    } else {
                        if ( $match->winner_id == $team_id ) {
                            $data['straight_set']['win'] += 1;
                            for ( $j = 1; $j <= $league->num_sets-1; $j++  ) {
                                $data['games_allowed'] += $match->sets[$j][$index];
                            }
                        } elseif ( $match->loser_id == $team_id) {
                            $data['straight_set']['lost'] += 1;
                            for ( $j = 1; $j <= $league->num_sets-1; $j++  ) {
                                $data['games_allowed'] += $match->sets[$j][$index];
                            }
                        }
                    }
                }
			}
		}

		return $data;
	}


	/**
	 * Filter match title for double matches
	 *
	 * @param object $match
	 * @param array $teams
	 * @param string $title
	 * @return string
	 */
	function matchTitle( $title, $match, $teams )
	{
		$homeTeam = $teams[$match->home_team]['title'] ;
		$awayTeam = $teams[$match->away_team]['title'];

		$title = sprintf("%s - %s", $homeTeam, $awayTeam);
		
		return $title;

	}


	/**
	 * extend header for Standings Table in Backend
	 *
	 * @param none
	 * @return void
	 */
	function displayStandingsHeader()
	{
		echo '<th class="tennishdr">'.__( 'Sets Won', 'leaguemanager' ).'</th><th class="tennishdr">'.__( 'Sets Against', 'leaguemanager' ).'</th><th class="tennishdr">'.__( 'Games Won', 'leaguemanager' ).'</th><th class="tennishdr">'.__( 'Games Against', 'leaguemanager' ).'</th>';
	}


	/**
	 * extend columns for Standings Table in Backend
	 *
	 * @param object $team
	 * @param string $rule
	 * @return void
	 */
	function displayStandingsColumns( $team, $rule )
	{
		global $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();

        if (!isset($team->sets_won)) $team->sets_won = '';
        if (!isset($team->sets_allowed)) $team->sets_allowed = '';
        if (!isset($team->games_won)) $team->games_won = '';
		if (!isset($team->games_allowed)) $team->games_allowed = '';
		if ( is_admin() && $rule == 'manual' )
			echo '<td><input type="text" size="2" name="custom['.$team->id.'][sets_won]" value="'.$team->sets_won.'" /></td><td><input type="text" size="2" name="custom['.$team->id.'][sets_allowed]" value="'.$team->sets_allowed.'" /></td><td><input type="text" size="2" name="custom['.$team->id.'][games_won]" value="'.$team->games_won.'" /></td><td><input type="text" size="2" name="custom['.$team->id.'][games_allowed]" value="'.$team->games_allowed.'" /></td>';
		else
			echo '<td class="num">'.$team->sets_won.'</td><td class="num">'.$team->sets_allowed.'</td><td class="num">'.$team->games_won.'</td><td class="num">'.$team->games_allowed.'</td>';
	}


	/**
	 * display hidden fields in team edit form
	 *
	 * @param object $team
	 * @return void
	 */
	function editTeam( $team )
	{
        if (!isset($team->sets_won)) $team->sets_won = '';
        if (!isset($team->sets_allowed)) $team->sets_allowed = '';
        if (!isset($team->games_won)) $team->games_won = '';
		if (!isset($team->games_allowed)) $team->games_allowed = '';
		
		echo '<input type="hidden" name="custom[sets_won]" value="'.$team->sets_won.'" /><input type="hidden" name="custom[sets_allowed]" value="'.$team->sets_allowed.'" /><input type="hidden" name="custom[games_won]" value="'.$team->games_won.'" /><input type="hidden" name="custom[games_allowed]" value="'.$team->games_allowed.'" />';
	}


	/**
	 * Add custom fields to match form
	 *
	 * @param none
	 */
	function editMatchesHeader() {

	}
	
	function editMatchesColumns( $match, $league, $season, $teams, $i ) {
		
	}


	/**
	 * display Table Header for Match Administration
	 *
	 * @param none
	 * @return void
	 */
	function displayMatchesHeader()
	{
		global $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
        if ( !isset($league->num_rubbers)) {
            echo '<th colspan="'.$league->num_sets.'" style="text-align: center;">'.__( 'Sets', 'leaguemanager' ).'</th>';
        } else {
            echo '<th>'.__( 'Rubbers', 'leaguemanager' ).'</th>';
        }
	}


	/**
	 * display Table columns for Match Administration
	 *
	 * @param object $match
	 * @return void
	 */
	function displayMatchesColumns( $match )
	{
		global $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();

		if ( !isset($league->num_sets) || empty($league->num_sets) ) {
			$leaguemanager->setMessage(__('You have to define the number of sets', 'leaguemanager'), true);
			$leaguemanager->printMessage();
			echo '<td></td>';
        } elseif ( isset($league->num_rubbers)) {
			$base_height = 155;
			$rubber_height = $league->num_rubbers * 145;
			$height = $base_height + $rubber_height;
            $link = '#TB_inline?&inlineId=showMatchRubbers&width=650&height='.$height;
            echo '<td><a href="'.$link.'" class="thickbox button button-primary" id="'.$match->id.'" onclick="Leaguemanager.showRubbers(this)">View Rubbers</a></td>';
		} else {
			for ( $i = 1; $i <= $league->num_sets; $i++ ) {
				if (!isset($match->sets[$i])) {
					$match->sets[$i] = array('player1' => '', 'player2' => '');
				}
				echo '<td><input class="points" type="text" size="2" id="set_'.$match->id.'_'.$i.'_player1" name="custom['.$match->id.'][sets]['.$i.'][player1]" value="'.$match->sets[$i]['player1'].'" /> : <input class="points" type="text" size="2" id="set_'.$match->id.'_'.$i.'_player2" name="custom['.$match->id.'][sets]['.$i.'][player2]" value="'.$match->sets[$i]['player2'].'" /></td>';
			}
		}
	}

	/**
	 * export matches header
	 *
	 * @param string $content
	 * @return the content
	 */
	function exportMatchesHeader( $content )
	{
		global $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
        if ( isset($league->num_rubbers)) {
            for ($ix = 0; $ix < $league->num_rubbers; $ix++) {
                $content .= "\t".__('RubberID','leaguemanager');
                $content .= "\t".__('Home Player 1','leaguemanager');
                $content .= "\t".__('Home Player 2','leaguemanager');
                if ( isset($league->num_sets)) {
                    for ($ix2 = 0; $ix2 < $league->num_sets; $ix2++) {
                        $setnum = $ix2 + 1;
                        $content .= "\t".__('Set','leaguemanager').$setnum;
                    }
                }
                $content .= "\t".__('Away Player 1','leaguemanager');
                $content .= "\t".__('Away Player 2','leaguemanager');
            }
        } else {
            $content .= "\t".utf8_decode(__( 'Sets', 'leaguemanager' )).str_repeat("\t", $league->num_sets);
        }
		
        return $content;
	}


	/**
	 * export matches data
	 *
	 * @param string $content
	 * @param object $match
	 * @return the content
	 */
	function exportMatchesData( $content, $match )
	{
		global $leaguemanager;
		$league = $leaguemanager->getCurrentLeague();
        if ( isset($league->num_rubbers)) {
            $rubbers = $leaguemanager->getRubbers(array("match_id" => $match->id));
            foreach ($rubbers as $rubber) {
                $content .= "\t".$rubber->id;
                $content .= "\t".$rubber->homePlayer1;
                $content .= "\t".$rubber->homePlayer2;
                $content .= "\t".$rubber->awayPlayer1;
                $content .= "\t".$rubber->awayPlayer2;
                if ( isset($rubber->sets) ) {
                    foreach ( $rubber->sets AS $j => $set ) {
                        $content .= "\t".implode(":", $set);
                    }
                } else {
                    $content .= str_repeat("\t", $league->num_sets);
                }

            }
        } elseif ( isset($match->sets) ) {
			foreach ( $match->sets AS $j => $set ) {
				$content .= "\t".implode(":", $set);
			}
		} else {
			$content .= str_repeat("\t", $league->num_sets);
		}
		return $content;
	}

	
	/**
	 * import matches
	 *
	 * @param array $custom
	 * @param array $line elements start at index 10
	 * @param int $match_id
	 * @return array
	 */
	function importMatches( $custom, $line, $match_id )
	{
		$match_id = intval($match_id);
		for( $x = 10; $x <= 11; $x++ ) {
			$set = isset($line[$x]) ? explode(":",$line[$x]) : array('','');
			$custom[$match_id]['sets'][] = array( 'player1' => $set[0], 'player2' => $set[1] );
		}

		return $custom;
	}
    
    /**
     * import fixtures
     *
     * @param array $custom
     * @param int $match_id
     * @param varchar $group
     * @param int $rubbers - number of rubbers
     * @param date $date
     * @return array
     */
    function importFixtures( $custom, $match_id )
    {
        return $custom;

    }

	/**
	 * import results
	 *
	 * @param array $custom
	 * @param array $line elements start at index 10
	 * @param int $match_id
	 * @return array
	 */
	function importResults( $custom, $line, $match_id )	{
        $match = $leaguemanager->getMatch( $match_id, false);
        $league = $leaguemanager->getLeague( $match->league_id );
        $num_rubbers = $league->num_rubbers;
        $num_sets = $league->num_sets;
		$match_id = intval($match_id);
        $customRubber = array();
        
        if (isset($num_rubbers)) {
            $rx = 10;
            for ($ix = 0; $ix < $num_rubbers; $ix++) {
                
                $rubber_id = $line[$rx];
                $rubber = $leaguemanager->getRubber( $rubber_id, false );
                $rx++;
                $home_player_1 = $line[$rx];
                $rx++;
                $home_player_2 = $line[$rx];
                $rx = $rx + 1;
                for ($ix2 = 0 ; $ix2 < $num_sets; $ix2++) {
                    $set = isset($line[$rx]) ? explode(":",$line[$rx]) : array('','');
                    $customRubber[$rubber_id]['sets'][] = array( 'player1' => $set[0], 'player2' => $set[1] );
                    $rx++;
                }
                $away_player_1 = $line[$rx];
                $rx++;
                $away_player_2 = $line[$rx];
                $rx++;
                $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_rubbers} SET `home_player_1` = '%s', `home_player_2` = '%s', `away_player_1` = '%s', `away_player_2` = '%s', `custom` = '%s' WHERE `id` = '%d'", $home_player_1, $home_player_2, $away_player_1, $away_player_2, $customrubber, $rubber_id) );
            }
        } else {
                
            for( $x = 10; $x <= 11; $x++ ) {
                $set = isset($line[$x]) ? explode(":",$line[$x]) : array('','');
                $custom[$match_id]['sets'][] = array( 'player1' => $set[0], 'player2' => $set[1] );
            }
        }


		return $custom;
	}



	/**
	 * export teams header
	 *
	 * @param string $content
	 * @return the content
	 */
	function exportTeamsHeader( $content )
	{
		$content .= "\t".utf8_decode(__( 'Sets Won', 'leaguemanager' ))."\t".utf8_decode(__('Sets Against', 'leaguemanager'))."\t".utf8_decode(__('Games Won', 'leaguemanager'))."\t".utf8_decode(__('Games Against', 'leaguemanager'));
		return $content;
	}


	/**
	 * export teams data
	 *
	 * @param string $content
	 * @param object $team
	 * @return the content
	 */
	function exportTeamsData( $content, $team )
	{
		if ( isset($team->straight_set) )
			$content .= "\t".$team->sets_won."\t".$team->sets_allowed."\t".$team->split_set['lost']."\t".$team->games_won."\t".$team->games_allowed;
		else
			$content .= "\t\t\t\t\t\t";

		return $content;
	}

	
	/**
	 * import teams
	 *
	 * @param array $custom
	 * @param array $line elements start at index 8
	 * @return array
	 */
	function importTeams( $custom, $line )
	{
        $custom['sets_won'] = isset($line[8]) ? $line[8] : '';
        $custom['sets_allowed'] = isset($line[9]) ? $line[9] : '';
		$custom['games_won'] = isset($line[10])? $line[10] : '';
        $custom['games_allowed'] = isset($line[11])? $line[11] : '';

		return $custom;
	}
	
	/**
	 * update match results and automatically calculate score
	 *
	 * @param int $match_id
	 * @return none
	 */
	function updateResults( $matchId )
	{
		global $wpdb, $leaguemanager;
		
		$match = $leaguemanager->getMatch( $matchId, false );
		if ( $match->home_points == "" && $match->away_points == "" ) {
            
            $league = $leaguemanager->getCurrentLeague();

            $score = array( 'home' => '', 'guest' => '' );
            if (isset($league->num_rubbers)) {
                $rubbers = $leaguemanager->getRubbers( array("match_id" => $matchId));
                
                foreach ( $rubbers as $rubber) {
                    $score['home'] += $rubber->home_points;
                    $score['guest'] += $rubber->away_points;
                }
                
            } else {
                foreach ( $match->sets AS $set ) {
                    if ( $set['player1'] != '' && $set['player2'] != '' ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            $score['home'] += 1;
                        } else {
                            $score['guest'] += 1;
                        }
                    }
                }
                
            }
			
			if ($score['home'] != 0 && $score['away'] != 0) {
				$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_matches} SET `home_points` = '%s', `away_points` = '%s' WHERE `id` = '%d'", $score['home'], $score['guest'], $matchId) );
			}
		}
	}
}

$tennis = new LeagueManagerTennis();
?>
