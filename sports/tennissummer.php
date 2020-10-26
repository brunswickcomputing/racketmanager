<?php
/**
 * Tennis Summer Class
 * 
 * @author 	Paul Moffat
 * @package	LeagueManager
 * @copyright Copyright 2017
*/
class LeagueManagerTennisSummer extends LeagueManager
{

	/**
	 * sports key
	 *
	 * @var string
	 */
	var $key = 'tennissumer';


	/**
	 * load specific settings
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

	}
	function LeagueManagerTennisSummer()
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
		$sports[$this->key] = __( 'Tennis Summer', 'leaguemanager' );
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
		$rules[$this->key] = __('Tennis Summer', 'leaguemanager');

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
		$rules[$this->key] = array( 'forwin' => 0, 'fordraw' => 0, 'forloss' => 0, 'forwin_split' => 0, 'forloss_split' => 0, 'forshare' => 0.5 );

		return $rules;
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
			$sets_diff[$key] = intval($team->sets_won) - intval($team->sets_allowed);
			$sets_won[$key] = $team->sets_won;
            $sets_allowed[$key] = $team->sets_allowed;
			$games_diff[$key] = intval($team->games_won) - intval($team->games_allowed);
            $games_won[$key] = $team->games_won;
			$games_allowed[$key] = $team->games_allowed;
		}
		array_multisort( $points, SORT_DESC, $games_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $teams );
		return $teams;
	}

}

$tennissummer = new LeagueManagerTennisSummer();
?>
