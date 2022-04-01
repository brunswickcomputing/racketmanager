<?php

/**
* RacketManagerShortcodes API: RacketManagerShortcodes class
*
* @author Kolja Schleich
* @author Paul Moffat
* @package RacketManager
* @subpackage RacketManagerShortcodes
*/

/**
* Class to implement shortcode functions
*
*/
class RacketManagerShortcodes extends RacketManager {

	/**
	* initialize shortcodes
	*
	*/
	public function __construct() {
		add_shortcode( 'standings', array(&$this, 'showStandings') );
		add_shortcode( 'dailymatches', array(&$this, 'showDailyMatches') );
		add_shortcode( 'latestresults', array(&$this, 'showLatestResults') );
		add_shortcode( 'matches', array(&$this, 'showMatches') );
		add_shortcode( 'match', array(&$this, 'showMatch') );
		add_shortcode( 'championship', array(&$this, 'showChampionship') );
		add_shortcode( 'crosstable', array(&$this, 'showCrosstable') );
		add_shortcode( 'teams', array(&$this, 'showTeams') );
		add_shortcode( 'team', array(&$this, 'showTeam') );
		add_shortcode( 'leaguearchive', array(&$this, 'showArchive') );
		add_shortcode( 'competition', array(&$this, 'showCompetition') );
		add_shortcode( 'players', array(&$this, 'showPlayers') );
		add_shortcode( 'clubs', array(&$this, 'showClubs') );
		add_shortcode( 'club', array(&$this, 'showClub') );
		add_shortcode( 'tournament-entry', array(&$this, 'showTournamentEntry') );
		add_shortcode( 'winners', array(&$this, 'showWinners') );
		add_shortcode( 'matchnotification', array(&$this, 'showMatchNotification') );
		add_shortcode( 'resultnotification', array(&$this, 'showResultNotification') );
		add_shortcode( 'rosternotification', array(&$this, 'showRosterNotification') );
		add_shortcode( 'cupentry', array(&$this, 'showCupEntry') );
		add_shortcode( 'leagueentry', array(&$this, 'showLeagueEntry') );
	}

	/**
	* Display League Standings
	*
	*    [standings league_id="1" template="name"]
	*
	* - league_id is the ID of league
	* - season: display specific season (optional). default is current season
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "standings-template.php" (optional)
	* - group: optional group
	*
	* @param array $atts shortcode attributes
	* @return string
	*/
	public function showStandings( $atts, $widget = false ) {
		global $league;

		extract(shortcode_atts(array(
			'league_id' => 0,
			'league_name' => '',
			'template' => '',
			'season' => false,
			'group' => false,
			'home' => 0,
		), $atts ));

		$league = $this->getleague($league_id);

		if ( !$league ) return;

		$league->setTemplate('standingstable', $template);
		$league->setSeason($season);
		$league->setGroup($group);

		$team_args = array( "orderby" => array('rank' => 'ASC') );
		if ( $group ) $team_args["group"] = $group;
		$teams = $league->getLeagueTeams( $team_args );

		if (empty($template))
		$filename = 'standings';
		elseif ( !$widget && $this->checkTemplate('standings-'.$league->sport) )
		$filename = 'standings-'.$league->sport;
		else
		$filename = 'standings-'.$template;

		$out = $this->loadTemplate( $filename, array('league' => $league, 'teams' => $teams, 'widget' => $widget, 'season' => $season ) );

		return $out;
	}

	/**
	* Display Daily Matches
	*
	*    [dailymatches league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	*
	* - league_id is the ID of league (optional)
	* - competition_id is the ID of the competition (optional)
	* - season: display specific season (optional)
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showDailyMatches( $atts ) {
		global $racketmanager, $wpdb;

		extract(shortcode_atts(array(
			'league_id' => 0,
			'competition_id' => 0,
			'competition_type' => 'league',
			'template' => 'daily',
			'match_date' => false,
		), $atts ));

		$matches = false;

		$match_date = get_query_var('match_date');
		if ( $match_date == '' ) {
			if (isset($_GET['match_date'])) {
				$match_date = $_GET['match_date'];
			}
		}
		if ( $match_date == '' ) {
			$match_date = date("Y-m-d");
		}

		$matches = $racketmanager->getMatches( array('match_date' => $match_date, 'competition_type' => $competition_type) );

		$filename = ( !empty($template) ) ? 'matches-'.$template : 'matches';

		$out = $this->loadTemplate( $filename, array('matches' => $matches, 'match_date' => $match_date ) );
		return $out;
	}

	/**
	* Display Latest Match results
	*
	*    [latestresults league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	*
	* - league_id is the ID of league (optional)
	* - competition_id is the ID of the competition (optional)
	* - season: display specific season (optional)
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showLatestResults( $atts ) {
		global $racketmanager, $wpdb;

		extract(shortcode_atts(array(
			'league_id' => 0,
			'competition_id' => 0,
			'competition_type' => 'league',
			'template' => 'results',
			'days' => 7,
			'affiliatedclub' => '',
		), $atts ));

		$matches = false;

		$time = 'latest';
		$matches = $racketmanager->getMatches( array('days' => $days, 'competition_type' => $competition_type, 'time' => 'latest', 'history' => $days, 'affiliatedClub' => $affiliatedclub) );

		foreach ( $matches AS $i => $match ) {
				$matches[$i]->score = $this->getMatchScore($match);
				$i++;
		}
		if (empty($template))
		$filename = 'matches';
		elseif ( isset($league) && $this->checkTemplate('matches-'.$league->sport) )
		$filename = 'matches-'.$league->sport;
		else
		$filename = 'matches-'.$template;

		$out = $this->loadTemplate( $filename, array('matches' => $matches ) );
		return $out;
	}

	/**
	* Display League Matches
	*
	*    [matches league_id="1" mode="all|home|racing" template="name" roster=ID]
	*
	* - league_id is the ID of league
	* - league_name: get league by name and not ID (optional)
	* - mode can be either "all" or "home". For racing it must be "racing". If it is not specified the matches are displayed on a weekly basis
	* - season: display specific season (optional)
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	* - roster is the ID of individual team member (currently only works with racing)
	* - match_day: specific match day (integer)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showMatches( $atts ) {
		global $league, $racketmanager;

		extract(shortcode_atts(array(
			'league_id' => 0,
			'league_name' => '',
			'team' => 0,
			'template' => 'daily',
			'template_type' => 'tabs',
			'mode' => '',
			'season' => '',
			'limit' => 'true',
			'roster' => false,
			'match_day' => -1,
			'home_only' => 'false',
			'match_date' => false,
			'group' => false,
			'time' => '',
			'dateformat' => '',
			'timeformat' => '',
			'show_team_selection' => '',
			'show_match_day_selection' => ''
		), $atts ));

		$league = $this->getleague($league_id);

		if ( !$league ) return;

		$league->setTemplate('matches', $template);

		// Always disable match day in template to show matches by matchday
		if ( in_array($template, array("by_matchday")) || !empty($time) )
		$match_day = -1;

		$league->setMatchesSelection($show_match_day_selection, $match_day, $show_team_selection, $team);

		$league->setSeason($season);
		$league->setMatchDay($match_day);
		$league->matches_template_type = $template_type;

		$matches = false;
		$match_args = array("final" => '');

		// get matches of specific team
		$team_name = str_replace('-', ' ', get_query_var('team'));
		if ( !$team_name == null ) {
			$team_id = $racketmanager->getTeamID($team_name);
		} elseif ( !empty($team) || (isset($_GET['team_id']) && !empty($_GET['team_id'])) ) {
			$team_id = !empty($team) ? $team : (int)$_GET['team_id'];
		}
		if ( !empty($team_id) ) {
			$match_args['team_id'] = $team_id;
		} elseif ( !empty($group) ) {
			$match_args['group'] = $group;
		}

		if ( $limit === 'false' )  {
			$match_args['limit'] = false;
		} elseif ( $limit && is_numeric($limit) ) {
			$match_args['limit'] = intval($limit);
		}

		$match_args['time'] = $time;
		$match_args['home_only'] = ( $home_only == 1 || $home_only == 'true' ) ? true : false;

		// get matches
		$matches = $league->getMatches( $match_args );
		$league->setNumMatches();

		if ( !$matches ) return;

		foreach ( $matches AS $i => $match ) {

			$match = get_match($match);
			$matches[$i] = $match;
			$matches[$i]->num_rubbers = ( isset($league->num_rubbers) ? $league->num_rubbers : NULL );
			$matches[$i]->num_sets = ( isset($league->num_sets) ? $league->num_sets : NULL );

			if ( isset($matches[$i]->num_rubbers) ) {
				$rubbers = $match->getRubbers();
				$r=1;
				foreach ($rubbers as $rubber) {
					$rubber->home_player_1_name = $rubber->home_player_2_name = $rubber->away_player_1_name = $rubber->away_player_2_name = '';
					if ( isset($rubber->home_player_1) ) $rubber->home_player_1_name = $this->getPlayerNamefromRoster($rubber->home_player_1);
					if ( isset($rubber->home_player_2) ) $rubber->home_player_2_name = $this->getPlayerNamefromRoster($rubber->home_player_2);
					if ( isset($rubber->away_player_1) ) $rubber->away_player_1_name = $this->getPlayerNamefromRoster($rubber->away_player_1);
					if ( isset($rubber->away_player_2) ) $rubber->away_player_2_name = $this->getPlayerNamefromRoster($rubber->away_player_2);
					$matches[$i]->rubbers[$r] = $rubber;
					$r ++;
				}
			}

		}

		/*
		* get teams
		*/
		$teams = $league->getLeagueTeams( array("season" => $season, "orderby" => array("title" => "ASC")), 'ARRAY' );

		if ( empty($template) && $this->checkTemplate('matches-'.$league->sport) )
		$filename = 'matches-'.$league->sport;
		elseif ($this->checkTemplate('matches-'.$template.'-'.$league->sport) )
		$filename = 'matches-'.$template.'-'.$league->sport;
		else
		$filename = ( !empty($template) ) ? 'matches-'.$template : 'matches';

		$out = $this->loadTemplate( $filename, array('league' => $league, 'matches' => $matches, 'teams' => $teams, 'season' => $season ) );
		return $out;
	}

	/**
	* Display single match
	*
	* [match id="1" template="name"]
	*
	* - id is the ID of the match to display
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "match-template.php" (optional)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showMatch( $atts ) {
		global $match;
		extract(shortcode_atts(array(
			'match_id' => 0,
			'template' => '',
		), $atts ));

		// Get Match ID from shortcode or $_GET
		if ( !$match_id ) {
			$match_id = ( isset($_GET['match_id']) && !empty($_GET['match_id']) ) ? $_GET['match_id'] : false;
		}
		$match = get_match($match_id);

		$filename = '';
		if ( $match ) {
			$league = get_league($match->league_id);
			$match->num_rubbers = ( isset($league->num_rubbers) ? $league->num_rubbers : NULL );
			$match->num_sets = ( isset($league->num_sets) ? $league->num_sets : NULL );

			if ( isset($match->num_rubbers) ) {
				$rubbers = $match->getRubbers();
				$r=1;
				foreach ($rubbers as $rubber) {
					$rubber->home_player_1_name = $rubber->home_player_2_name = $rubber->away_player_1_name = $rubber->away_player_2_name = '';
					if ( isset($rubber->home_player_1) ) $rubber->home_player_1_name = $this->getPlayerNamefromRoster($rubber->home_player_1);
					if ( isset($rubber->home_player_2) ) $rubber->home_player_2_name = $this->getPlayerNamefromRoster($rubber->home_player_2);
					if ( isset($rubber->away_player_1) ) $rubber->away_player_1_name = $this->getPlayerNamefromRoster($rubber->away_player_1);
					if ( isset($rubber->away_player_2) ) $rubber->away_player_2_name = $this->getPlayerNamefromRoster($rubber->away_player_2);
					$match->rubbers[$r] = $rubber;
					$r ++;
				}
			}

		}

		if ( empty($template) && $this->checkTemplate('match-'.$league->sport) )
		$filename = 'match-'.$league->sport;
		elseif ($this->checkTemplate('match-'.$template.'-'.$league->sport) )
		$filename = 'match-'.$template.'-'.$league->sport;
		else
		$filename = ( !empty($template) ) ? 'matches-'.$template : 'matches';
		$out = $this->loadTemplate( $filename, array('match' => $match) );

		return $out;
	}

	/**
	* get Player name
	*
	* @param int $roster_id
	* @return string $playerName
	*/
	function getPlayerNamefromRoster( $roster_id ) {
		global $racketmanager;

		$roster_dtls = $racketmanager->getRosterEntry( intval($roster_id));
		$playerName = $roster_dtls->fullname;
		return $playerName;
	}

	/**
	* Display Championship
	*
	*    [championship league_id="1" template="name"]
	*
	* - league_id is the ID of league
	* - season: display specific season (optional)
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showChampionship( $atts ) {
		global $league;

		extract(shortcode_atts(array(
			'league_id' => 0,
			'league_name' => '',
			'template' => '',
			'season' => false,
		), $atts ));


		$league = $this->getleague($league_id);
		if ( !$league ) return;

		$competition = get_competition($league->competition_id);
		$league->setTemplate('championship', $template);
		if ( !$season ) {
			$season = $league->getSeason();
			$season = $season['name'];
		}
		$league->season = $season;
		$finals = array();
		foreach ( array_reverse($league->championship->getFinals()) AS $final ) {
			$class = 'alternate';
			$data['key'] = $final['key'];
			$data['name'] = $final['name'];
			$data['num_matches'] = $final['num_matches'];
			$data['rowspan'] = ( $league->championship->num_teams_first_round/2 >= 4 ) ? ceil(4/$final['num_matches']) : ceil(($league->championship->num_teams_first_round/2)/$final['num_matches']);

			$matches_raw = $league->getMatches( array("final" => $final['key'], "orderby" => array("id" => "ASC")) );

			$matches = array();
			for ( $i = 1; $i <= $final['num_matches']; $i++ ) {
				$match = isset($matches_raw[$i-1]) ? $matches_raw[$i-1] : NULL;

				if ( $match ) {
					$match = get_match($match);
					$class = ( !isset($class) || 'alternate' == $class ) ? '' : 'alternate';
					$match->class = $class;
					$home_title = $match->teams['home']->title;
					$away_title = $match->teams['away']->title;
					$match->title = sprintf("%s - %s", $home_title, $away_title);
					$match->home_title = $home_title;
					$match->away_title = $away_title;

					$match->num_rubbers = ( isset($league->num_rubbers) ? $league->num_rubbers : NULL );
					$match->num_sets = ( isset($league->num_sets) ? $league->num_sets : NULL );

					if ( isset($match->num_rubbers) && $match->num_rubbers > 0 ) {
						$rubbers = $match->getRubbers();
						$r=1;
						foreach ($rubbers as $rubber) {
							$rubber->home_player_1_name = $rubber->home_player_2_name = $rubber->away_player_1_name = $rubber->away_player_2_name = '';
							if ( isset($rubber->home_player_1) && $rubber->home_player_1 > 0 ) $rubber->home_player_1_name = $this->getPlayerNamefromRoster($rubber->home_player_1);
							if ( isset($rubber->home_player_2) && $rubber->home_player_2 > 0) $rubber->home_player_2_name = $this->getPlayerNamefromRoster($rubber->home_player_2);
							if ( isset($rubber->away_player_1) && $rubber->away_player_1 > 0) $rubber->away_player_1_name = $this->getPlayerNamefromRoster($rubber->away_player_1);
							if ( isset($rubber->away_player_2) && $rubber->away_player_2 > 0) $rubber->away_player_2_name = $this->getPlayerNamefromRoster($rubber->away_player_2);
							$match->rubbers[$r] = $rubber;
							$r ++;
						}
					}

					$match->score = $this->getMatchScore($match);

					if ( $final['key'] == 'final' ) {
						$data['isFinal'] = true;
						$data['field_id'] = ( $match->winner_id == $match->home_team ) ? "final_home" : "final_away";
					} else {
						$data['isFinal'] = false;
					}

					if ( empty($match->location) ) {
						$match->location = '';
					}
					$matches[$i] = $match;
				}
			}

			$data['matches'] = $matches;
			$finals[] = (object)$data;
		}

		if ( empty($template) && $this->checkTemplate('championship-'.$league->sport) ) {
			$filename = 'championship-'.$league->sport;
		} else {
			$filename = ( !empty($template) ) ? 'championship-'.$template : 'championship';
		}

		$out = $this->loadTemplate( $filename, array('league' => $league, 'finals' => $finals) );

		return $out;
	}


	/**
	* Function to display Clubs Info Page
	*
	*    [clubs template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showClubs( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'type' => '',
			'template' => '',
			'echo' => 0,
		), $atts ));

		$clubs = $racketmanager->getClubs();

		$filename = ( !empty($template) ) ? 'clubs-'.$template : 'clubs';

		$out = $this->loadTemplate( $filename, array( 'clubs' => $clubs) );

		if ( $echo )
		echo $out;
		else
		return $out;
	}

	/**
	* Function to display Club Info Page
	*
	*    [club id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showClub( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'id' => 0,
			'template' => '',
			'echo' => 0,
		), $atts ));

		// Get League by Name
		$club_name = get_query_var('club_name');
		$club_name = str_replace('-',' ',$club_name);

		$club = get_Club( $club_name, 'shortcode' );

		if ( !$club ) return;

		$rosters = $club->getRoster( array( 'inactive' => "Y", 'type' => 'real', 'cache' => false ) );
		$rosterRequests = $club->getRosterRequests();

		$club->single = true;

		$filename = ( !empty($template) ) ? 'club-'.$template : 'club';

		$out = $this->loadTemplate( $filename, array( 'club' => $club, 'rosters' => $rosters, 'rosterRequests' => $rosterRequests ) );

		if ( $echo )
		echo $out;
		else
		return $out;
	}

	/**
	* Display Team list
	*
	*    [teams league_id=ID template=X season=x]
	*
	* - league_id is the ID of league
	* - season: use specific season (optional)
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "teams-template.php" (optional)
	* - group: show teams only from specific group
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showTeams( $atts ) {
		global $league;
		extract(shortcode_atts(array(
			'league_id' => 0,
			'template' => '',
			'season' => false,
			'group' => false
		), $atts ));

		$league = $this->getleague($league_id);

		$competition = get_competition($league->competition_id);

		$league->setTemplate('teams', $template);
		$league->setGroup($group);

		$team_args = array( "orderby" => array('rank' => 'ASC') );
		if ( $group ) $team_args["group"] = $group;
		$teams = $league->getLeagueTeams( $team_args );

		if ( empty($template) && $this->checkTemplate('teams-'.$league->sport) )
		$filename = 'teams-'.$league->sport;
		else
		$filename = ( !empty($template) ) ? 'teams-'.$template : 'teams-list';

		$out = $this->loadTemplate( $filename, array('league' => $league, 'teams' => $teams) );

		return $out;
	}

	/**
	* Display Team Info Page
	*
	*    [team id=ID template=X]
	*
	* - id: the team ID
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "team-template.php" (optional)
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showTeam( $atts ) {
		global $league, $leagueTeam;
		extract(shortcode_atts(array(
			'id' => 0,
			'template' => '',
			'echo' => 0,
		), $atts ));

		$league = get_league();
		if ( !is_null($league) )
		$team = $league->getLeagueTeam(intval($id));
		else
		$team = get_leagueTeam(intval($id));

		if ( empty($template) && $this->checkTemplate('team-'.$league->sport) )
		$filename = 'team-'.$league->sport;
		else
		$filename = ( !empty($template) ) ? 'team-'.$template : 'team';

		$out = $this->loadTemplate( $filename, array('league' => $league, 'team' => $team) );

		return $out;
	}

	/**
	* Display Crosstable
	*
	* [crosstable league_id="1" mode="popup" template="name"]
	*
	* - league_id is the ID of league to display
	* - mode set to "popup" makes the crosstable be displayed in a thickbox popup window.
	* - template is the template used for displaying. Replace name appropriately. Templates must be named "crosstable-template.php" (optional)
	* - season: display crosstable of given season (optional)
	* - group: show crosstable for specific group
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showCrosstable( $atts ) {
		global $league;
		extract(shortcode_atts(array(
			'league_id' => 0,
			'league_name' => '',
			'group' => '',
			'template' => '',
			'mode' => '',
			'season' => false
		), $atts ));

		$league = $this->getleague($league_id);

		if ( !$league ) return;

		$league->setTemplate('crosstable', $template);
		$league->setSeason($season);
		$league->setGroup($group);

		$teams = $league->getLeagueTeams( array('orderby' => array('rank' => 'ASC')) );

		if ( empty($template) && $this->checkTemplate('crosstable-'.$league->sport) )
		$filename = 'crosstable-'.$league->sport;
		else
		$filename = ( !empty($template) ) ? 'crosstable-'.$template : 'crosstable';

		$out = $this->loadTemplate( $filename, array('league' => $league, 'teams' => $teams, 'mode' => $mode, 'season' => $season ) );

		return $out;
	}

	/**
	* show Competition
	*
	* [competition_id=ID season=X template=X]
	*
	* id: ID of competition
	* season: season to show
	* template: teamplate to use
	*/
	public function showCompetition( $atts ) {
		global $racketmanager, $competition;

		extract(shortcode_atts(array(
			'id' => 0,
			'season' => false,
			'template' => '',
			'standingstable' => '',
			'crosstable' => '',
			'matches' => '',
			'teams' => 'list'
		), $atts ));
		$competition = get_competition( $id );

		if ( !$competition ) return;

		$leagues = $competition->getLeagues( array('competition' => $id, 'orderby' => array("title" => "ASC")));

		if ( isset($_GET['season']) && !empty($_GET['season']) ) {
			$season = htmlspecialchars(strip_tags($_GET['season']));
		} elseif ( isset($_GET['season']) ) {
			$season = htmlspecialchars(strip_tags($_GET['season']));
		} else {
			$season = null !== get_query_var('season') ? get_query_var('season') : false;
		}

		if ( empty($template) && $this->checkTemplate('competition-'.$competition->sport) ) {
			$filename = 'competition-'.$competition->sport;
		} else {
			$filename = ( !empty($template) ) ? 'competition-'.$template : 'competition';
		}

		$seasons = $competition->seasons;
		if ( !$season ) {
			if ( $seasons ) {
				$season = end($competition->seasons);
				$season = $season['name'];
			} else {
				$season = '';
			}
		}

		$out = $this->loadTemplate( $filename, array('competition' => $competition, 'leagues' => $leagues, 'seasons' => $seasons, 'curr_season' => $season, 'standingsTemplate' => $standingstable) );
		return $out;
	}

	/**
	* Display Archive
	*
	*    [leaguearchive template=X]
	*
	* - template: teamplate to use
	* - standingstable: template for standings table
	* - crosstable: template for crosstable
	* - matches: template for matches
	* - teams: template for teams
	* - matches_template_type: type of match template
	*
	* @param array $atts shorcode attributes
	* @return string
	*/
	public function showArchive( $atts ) {
		global $league;

		extract(shortcode_atts(array(
			'league_id' => false,
			'competition_id' => false,
			'league_name' => '',
			'standingstable' => 'last5',
			'crosstable' => '',
			'matches' => '',
			'teams' => 'list',
			'template' => '',
			'matches_template_type' => ''
		), $atts ));

		// get all leagues, needed for dropdown
		$competition = get_competition( $competition_id);

		if ( !$competition ) return;

		$seasons = $competition->seasons;
		$leagues = $competition->getLeagues( array('competition' => $competition_id, 'orderby' => array("title" => "ASC")));

		// Get League by Name
		$league_name = get_query_var('league_name');
		$league_name = str_replace('-',' ',$league_name);

		if (!empty($league_name)) {
			$league_id = $competition->getLeagueId($league_name);
		}

		// Get League ID from shortcode or $_GET
		if ( $league_id ) {
			$league = get_league($league_id);
		}

		if ($league) {
			$league->setTab(true);
			$league->setTemplates(array( 'standingstable' => $standingstable, 'crosstable' => $crosstable, 'matches' => $matches, 'teams' => $teams ));
			$league->matches_template_type = $matches_template_type;

			if ( empty($template) && $this->checkTemplate('archive-'.$league->sport) )
			$filename = 'archive-'.$league->sport;
			else
			$filename = ( !empty($template) ) ? 'archive-'.$template : 'archive';

			$out = $this->loadTemplate( $filename, array('leagues' => $leagues, 'league' => $league, 'seasons' => $seasons) );
			return $out;
		}
	}

	/**
	* Function to display Players
	*
	*	[teams league_id=ID template=X season=x]
	*
	* @param array $atts
	* @return the content
	*/
	public function showPlayers( $atts ) {
		global $league;
		extract(shortcode_atts(array(
			'league_id' => 0,
			'template' => '',
			'season' => false,
			'group' => false
		), $atts ));

		$league = $this->getleague($league_id);

		if ( !$league ) return;

		$competition = get_competition($league->competition_id);
		$league->setSeason($season);

		$player_args = array("league_id" => $league->id, "season" => $season, "orderby" => array("title" => "ASC"));
		$players = $competition->getPlayerStats( $player_args );

		$playerstats	= array();

		foreach ( $players AS $p => $player ) {

			$matches = $player->matchdays;
			$played = $won = $lost = $drawn = $setsWon = $setsConceded = $gamesWon = $gamesConceded = $setsDiff = $gamesDiff = 0;
			$playername = $player->fullname;

			foreach ( $matches AS $match ) {

				$played ++;
				$player->team_title	= $match->team_title;
				$team				= $match->team_title;

				if ( $match->rubber_winner == $match->team_id ) {
					$won ++;
				} elseif ( $match->rubber_loser == $match->team_id ) {
					$lost ++;
				} else {
					$drawn ++;
				}

				if ( $player->roster_id == $match->home_player_1 || $player->roster_id == $match->home_player_2 ) {
					$setPlayer		= 'player1';
					$setOpponent	= 'player2';
				} else {
					$setPlayer		= 'player2';
					$setOpponent	= 'player1';
				}

				$sets				= $match->custom['sets'];
				$setCount			= count($sets);
				for ( $s = 1; $s <= $setCount; $s++  ) {

					if ( is_numeric($sets[$s][$setPlayer]) ) $gamesWon		+= $sets[$s][$setPlayer];
					if ( is_numeric($sets[$s][$setOpponent]) ) $gamesConceded	+= $sets[$s][$setOpponent];
					if ( $sets[$s][$setPlayer] > $sets[$s][$setOpponent] ) {
						$setsWon ++;
					} elseif ( $sets[$s][$setPlayer] < $sets[$s][$setOpponent] ) {
						$setsConceded ++;
					}
				}
			}

			$winpct = $won*100/$played;
			$setsDiff = $setsWon - $setsConceded;
			$gamesDiff = $gamesWon - $gamesConceded;
			$playerstats[$p]		= array('playername' => $playername, 'team' => $team, 'played' => $played, 'won' => $won, 'lost' => $lost, 'drawn' => $drawn, 'setsWon' => $setsWon, 'setsConceded' => $setsConceded, 'gamesWon' => $gamesWon, 'gamesConceded' => $gamesConceded, 'winpct' => $winpct, 'setsDiff' => $setsDiff, 'gamesDiff' => $gamesDiff );

		}

		$playerstats = array_msort($playerstats, array('won'=>SORT_DESC, 'winpct'=>SORT_DESC, 'setsDiff'=>SORT_DESC, 'gamesDiff'=>SORT_DESC,  'playername'=>SORT_ASC));
		$playerstats = (object)$playerstats;

		if ( empty($template) && $this->checkTemplate('players-'.$league->sport) ) {
			$filename = 'players-'.$league->sport;
		} else {
			$filename = ( !empty($template) ) ? 'players-'.$template : 'players';
		}

		$out = $this->loadTemplate( $filename, array('league' => $league, 'playerstats' => $playerstats) );
		return $out;
	}

	/**
	* Function to display Tournament Entry Page
	*
	*    [tournament-entry id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showTournamentEntry( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'type' => '',
			'template' => '',
		), $atts ));

		// get competition list
		if ($type != "") {
			$type = $type;
		} elseif ( isset($_GET['type']) && !empty($_GET['type']) ) {
			$type = htmlspecialchars(strip_tags($_GET['type']));
		} elseif ( isset($wp->query_vars['type']) ) {
			$type = get_query_var('type');
		}
		if ( !$type ) return _e('No tournament open for entries', 'racketmanager');

		$tournaments = $racketmanager->getTournaments( array( 'type' => $type, 'entryopen' => true ) );
		if ( !$tournaments ) return _e('No tournament open for entries', 'racketmanager');
		$tournament = $tournaments[0];

		$competitions = $racketmanager->getCompetitions( array('type' => 'tournament', 'name' => $type, 'season' => $tournament->season, 'orderby' => array("id" => "ASC")) );

		$player = wp_get_current_user();
		$player->contactno = get_user_meta( $player->ID, 'contactno', true);
		$player->gender = get_user_meta( $player->ID, 'gender', true);
		$rosters = $racketmanager->getRoster( array('player' => $player->ID, 'inactive' => true) );
		$malePartners = $racketmanager->getRoster( array('gender' => 'M', 'inactive' => true, 'type' => true) );
		$femalePartners = $racketmanager->getRoster( array('gender' => 'F', 'inactive' => true, 'type' => true) );

		$filename = ( !empty($template) ) ? 'entry-tournament-'.$template : 'entry-tournament';

		$out = $this->loadTemplate( $filename, array( 'tournament' => $tournament, 'competitions' => $competitions, 'player' => $player, 'rosters' => $rosters, 'season' => $tournament->season, 'type' => $type, 'malePartners' => $malePartners, 'femalePartners' => $femalePartners ) );

		return $out;
	}

	/**
	* Function to display Tournament winner
	*
	*    [winners id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showWinners( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'type' => '',
			'tournament' => false,
			'template' => '',
		), $atts ));

		// get competition list
		if ($type != "") {
			$type = $type;
		} elseif ( isset($_GET['type']) && !empty($_GET['type']) ) {
			$type = htmlspecialchars(strip_tags($_GET['type']));
		} elseif ( isset($wp->query_vars['type']) ) {
			$type = get_query_var('type');
		}
		if ( !$type ) return _e('No tournament winners', 'racketmanager');
		$tournaments = $racketmanager->getTournaments( array( 'type' => $type ) );

		if ($tournament != "") {
			$tournament = $tournament;
		} elseif ( isset($_GET['tournament']) && !empty($_GET['tournament']) ) {
			$tournament = htmlspecialchars(strip_tags($_GET['tournament']));
		} elseif ( isset($wp->query_vars['tournament']) ) {
			$tournament = get_query_var('tournament');
		}

		if (!$tournament) {
			$tournament = $tournaments[0];
		} else {
			$tournament = $racketmanager->getTournament($tournament);
		}

		$winners = $racketmanager->getWinners( $tournament->season, $type );

		$filename = ( !empty($template) ) ? 'winners-'.$template : 'winners';

		$out = $this->loadTemplate( $filename, array( 'winners' => $winners, 'tournaments' => $tournaments, 'curr_tournament' => $tournament->name) );

		return $out;
	}

	/**
	* Get match score
	*
	* @param object $match match details
	* @return the score
	*/
	public function getMatchScore($match) {
		if ( $match->home_points != NULL && $match->away_points != NULL ) {
			if ( isset($match->league->num_rubbers) && $match->league->num_rubbers > 0 ) {
				$score = sprintf("%s - %s", $match->home_points, $match->away_points);
			} else {
				$score = '';
				$sets = $match->custom['sets'];
				foreach ( $sets AS $set ) {
					if ( $set['player1'] != null && $set['player2'] != null )  {
						$score .= $set['player1'].'-'.$set['player2'].' ';
					}
				}
				if ( $score == '' ) {
					$score = __('Walkover', 'racketmanager');
				}
			}
		} elseif ( $match->winner_id != 0 ) {
			if ( $match->home_team == -1 || $match->away_team == -1 ) {
				$score = '';
			} else {
				$score = __('Walkover', 'racketmanager');
			}
		} else {
			$score = "";
		}
		return $score;
	}

	/**
	* Function to show match notification
	*
	*    [matchnotification id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showMatchNotification( $atts ) {
		global $racketmanager;

		extract(shortcode_atts(array(
			'match' => '',
			'template' => '',
			'tournament' => false,
			'competition' => '',
			'emailfrom' => '',
			'round' => '',
			'competitiontype' => ''
		), $atts ));

		$organisationname = $racketmanager->site_name;
		$match = get_match($match);

		$homeDtls = array();
		$awayDtls = array();
		if ( $competitiontype == 'tournament') {
			$tournament = $racketmanager->getTournament( $tournament );
			if ( substr($match->league->competition_type,1,1) == 'D' ) {
				$homeDtls['title'] = "Home Players";
				$awayDtls['title'] = "Away Players";
			} else {
				$homeDtls['title'] = "Home Player";
				$awayDtls['title'] = "Away Player";
			}
		} elseif ( $competitiontype == 'cup' ) {
			$template = 'cup';
			$homeDtls['title'] = "Home Team";
			$awayDtls['title'] = "Away Team";
		}
		if ( $match->custom['host'] == 'home') {
			$homeDtls['name'] = $match->teams['home']->title;
			$homeDtls['club'] = $match->teams['home']->affiliatedclubname;
			$homeDtls['captain'] = $match->teams['home']->captain;
			$homeDtls['captainEmail'] = $match->teams['home']->contactemail;
			$homeDtls['captainTel'] = $match->teams['home']->contactno;
			$homeDtls['matchDay'] = $match->teams['home']->match_day;
			$homeDtls['matchTime'] = $match->teams['home']->match_time;
			$awayDtls['name'] = $match->teams['away']->title;
			$awayDtls['club'] = $match->teams['away']->affiliatedclubname;
			$awayDtls['captain'] = $match->teams['away']->captain;
			$awayDtls['captainEmail'] = $match->teams['away']->contactemail;
			$awayDtls['captainTel'] = $match->teams['away']->contactno;
		} else {
			$awayDtls['name'] = $match->teams['home']->title;
			$awayDtls['club'] = $match->teams['home']->affiliatedclubname;
			$awayDtls['captain'] = $match->teams['home']->captain;
			$awayDtls['captainEmail'] = $match->teams['home']->contactemail;
			$awayDtls['captainTel'] = $match->teams['home']->contactno;
			$homeDtls['name'] = $match->teams['away']->title;
			$homeDtls['club'] = $match->teams['away']->affiliatedclubname;
			$homeDtls['captain'] = $match->teams['away']->captain;
			$homeDtls['captainEmail'] = $match->teams['away']->contactemail;
			$homeDtls['captainTel'] = $match->teams['away']->contactno;
			$homeDtls['matchDay'] = $match->teams['away']->match_day;
			$homeDtls['matchTime'] = $match->teams['away']->match_time;
		}

		$filename = ( !empty($template) ) ? 'match-notification-'.$template : 'match-notification';

		$out = $this->loadTemplate( $filename, array( 'tournament' => $tournament, 'competition' => $competition, 'match' => $match, 'homeDtls' => $homeDtls, 'awayDtls' => $awayDtls, 'round' => $round, 'organisationName' => $organisationname, 'emailFrom' => $emailfrom ), 'email' );

		return $out;
	}

	/**
	* Function to show result notification
	*
	*    [resultnotification id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showResultNotification( $atts ) {
		global $racketmanager;

		extract(shortcode_atts(array(
			'match' => '',
			'template' => '',
			'league' => false,
			'round' => false,
			'matchday' => false,
			'organisationname' => false,
		), $atts ));

		$match = get_match($match);

		$actionurl = admin_url().'?page=racketmanager&view=results';
		if ( $league ) {
			$actionurl .= '&subpage=show-league&league_id='.$league;
		}
		if ( $matchday ) {
			$actionurl .= '&match_day='.$matchday;
		}
		if ( $round ) {
			$actionurl .= '&final='.$round.'&league-tab=matches';
		}

		if ( !$organisationname ) {
			$organisationname = $racketmanager->site_name;
		}

		$filename = ( !empty($template) ) ? 'result-notification-'.$template : 'result-notification';

		$out = $this->loadTemplate( $filename, array( 'match' => $match, 'organisationName' => $organisationname, 'actionurl' => $actionurl ), 'email' );

		return $out;
	}
	/**
	* Function to show roster notification
	*
	*    [rostertnotification club=club template=X]
	*
	* @param array $atts
	* @return the content
	*/

	public function showRosterNotification( $atts ) {
		global $racketmanager;

		extract(shortcode_atts(array(
			'club' => '',
			'action' => false,
			'template' => ''
		), $atts ));

		$actionurl = admin_url().'?page=racketmanager&view=rosterRequest';

		$organisationname = $racketmanager->site_name;

		$filename = ( !empty($template) ) ? 'roster-notification-'.$template : 'roster-notification';

		$out = $this->loadTemplate( $filename, array( 'action' => $action, 'club' => $club, 'organisationName' => $organisationname, 'actionurl' => $actionurl ), 'email' );

		return $out;
	}

	/**
	* Function to display Cup Entry Page
	*
	*    [cupentry id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showCupEntry( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'type' => false,
			'season' => false,
			'template' => '',
		), $atts ));

		if ( !is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="'.wp_login_url().'">login</a> to enter cups</p>';
		}
		$club_name = get_query_var('club_name');
		$club_name = str_replace('-',' ',$club_name);

		$club = get_Club( $club_name, 'shortcode' );

		if ( !$club ) {
			return _e('No club selected', 'racketmanager');
		}
		$user = wp_get_current_user();
		$userid = $user->ID;
		$userCanUpdateClub = false;
		if ( current_user_can( 'manage_racketmanager' ) ) {
		    $userCanUpdateClub = true;
		} else {
		    if ( $club->matchsecretary !=null && $club->matchsecretary == $userid ) {
		        $userCanUpdateClub = true;
		    }
		}
		if ( !$userCanUpdateClub ) {
			return _e('User not authorised for club', 'racketmanager');
		}

		// get competition list
		$type = get_query_var('type');
		if ( !$type ) {
			return _e('No cups open for entries', 'racketmanager');
		}
		$season = get_query_var('season');
		if ( !$season ) {
			return _e('No season specified', 'racketmanager');
		}
		$competitions = $racketmanager->getCompetitions( array('type' => 'cup', 'name' => $type, 'season' => $season, 'orderby' => array("name" => "ASC")) );
		$ladiesTeams = $club->getTeams(false, 'WD');
		$mensTeams = $club->getTeams(false, 'MD');
		$mixedTeams = $club->getTeams(false, 'XD');

		$filename = ( !empty($template) ) ? 'entry-cup-'.$template : 'entry-cup';

		$out = $this->loadTemplate( $filename, array( 'club' => $club, 'competitions' => $competitions, 'ladiesTeams' => $ladiesTeams, 'mensTeams' => $mensTeams, 'mixedTeams' => $mixedTeams, 'season' => $season, 'type' => $type ) );

		return $out;
	}

	/**
	* Function to display league Entry Page
	*
	*    [leagueentry id=ID template=X]
	*
	* @param array $atts
	* @return the content
	*/
	public function showLeagueEntry( $atts ) {
		global $racketmanager;
		extract(shortcode_atts(array(
			'type' => false,
			'season' => false,
			'template' => '',
		), $atts ));

		if ( !is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="'.wp_login_url().'">login</a> to enter leagues</p>';
		}
		$club_name = get_query_var('club_name');
		$club_name = str_replace('-',' ',$club_name);

		$club = get_Club( $club_name, 'shortcode' );

		if ( !$club ) {
			return _e('No club selected', 'racketmanager');
		}
		$user = wp_get_current_user();
		$userid = $user->ID;
		$userCanUpdateClub = false;
		if ( current_user_can( 'manage_racketmanager' ) ) {
		    $userCanUpdateClub = true;
		} else {
		    if ( $club->matchsecretary !=null && $club->matchsecretary == $userid ) {
		        $userCanUpdateClub = true;
		    }
		}
		if ( !$userCanUpdateClub ) {
			return _e('User not authorised for club', 'racketmanager');
		}

		// get competition list
		$type = get_query_var('type');
		if ( !$type ) {
			return _e('No leagues open for entries', 'racketmanager');
		}
		$season = get_query_var('season');
		if ( !$season ) {
			return _e('No season specified', 'racketmanager');
		}
		$competitions = $racketmanager->getCompetitions( array('type' => 'league', 'name' => $type, 'orderby' => array("name" => "ASC")) );
		foreach ($competitions AS $i => $competition) {
			$competition = get_competition($competition->id);
			$competitionTeams = $competition->getTeams(array('season' => $season, 'club' => $club->id));
			foreach ($competitionTeams as $c => $competitionTeam) {
				$competitionTeam->teamInfo = $competition->getTeamInfo($competitionTeam->teamId);
				$competitionTeams[$c] = $competitionTeam;
			}
			$competition->competitionTeams = $competitionTeams;
			if ( $competition->type == 'WD' ) {
				$competition->teams = $club->getTeams(false, 'WD');
			} elseif ( $competition->type == 'MD' ) {
				$competition->teams = $club->getTeams(false, 'MD');
			} elseif ( $competition->type == 'XD' or $competition->type == 'LD' ) {
				$competition->teams = $club->getTeams(false, 'XD');
			}
			$competitions[$i] = $competition;
		}

		$filename = ( !empty($template) ) ? 'entry-league-'.$template : 'entry-league';

		$out = $this->loadTemplate( $filename, array( 'club' => $club, 'competitions' => $competitions, 'season' => $season, 'type' => $type ) );

		return $out;
	}

	/**
	* Load template for user display. First the current theme directory is checked for a template
	* before defaulting to the plugin
	*
	* @param string $template Name of the template file (without extension)
	* @param array $vars Array of variables name=>value available to display code (optional)
	* @param string $type Type of content template (email, page)
	* @return the content
	*/
	public function loadTemplate( $template, $vars = array(), $type = false ) {
		global $league, $team, $match, $racketmanager;

		extract($vars);

		if ( $type ) {
			switch ( $type ) {
				case 'email':
				$templateDir = 'templates/email';
				break;
				case 'page':
				$templateDir = 'templates/page';
				break;
				default:
				$templateDir = 'templates';
				break;
			}
		} else {
			$templateDir = 'templates';
		}
		ob_start();

		if ( file_exists( get_stylesheet_directory() . "/racketmanager/$template.php")) {
			include(get_stylesheet_directory() . "/racketmanager/$template.php");
		} elseif ( file_exists( get_template_directory() . "/racketmanager/$template.php")) {
			include(get_template_directory() . "/racketmanager/$template.php");
		} elseif ( file_exists(RACKETMANAGER_PATH . "/".$templateDir."/".$template.".php") ) {
			include(RACKETMANAGER_PATH . "/".$templateDir."/".$template.".php");
		} else {
			$this->setMessage( sprintf(__('Could not load template %s.php', 'racketmanager'), $template), true );
			$this->printMessage();
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}


	/**
	* check if template exists
	*
	* @param string $template
	* @return boolean
	*/
	private function checkTemplate( $template ) {
		if ( file_exists( get_stylesheet_directory() . "/racketmanager/$template.php")) {
			return true;
		} elseif  ( file_exists( get_template_directory() . "/racketmanager/$template.php")) {
			return true;
		} elseif ( file_exists(RACKETMANAGER_PATH . "/templates/".$template.".php") ) {
			return true;
		}

		return false;
	}

	/**
	* get league
	*
	* @param int $league_id
	* @return null|League
	*/
	private function getLeague( $league_id ) {
		global $league;

		if ($league_id == 0) $league = get_league();
		else $league = get_league(intval($league_id));
		return $league;
	}
}
?>
