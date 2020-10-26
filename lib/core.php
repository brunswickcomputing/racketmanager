<?php
/**
 * Core class for the WordPress plugin LeagueManager
 * 
 * @author 	Kolja Schleich
 * @package	LeagueManager
 * @copyright Copyright 2008
*/
class LeagueManager {
	/**
	 * array of leagues
	 *
	 * @var array
	 */
	var $leagues = array();
	

	/**
	 * data of certain league
	 *
	 * @var array
	 */
	var $league = array();


	/**
	 * ID of current league
	 *
	 * @var int
	 */
	var $league_id = null;

	
	/**
	 * current season
	 *
	 * @var mixed
	 */
	var $season;


	/**
	 * error handling
	 *
	 * @var boolean
	 */
	var $error = false;
	
	
	/**
	 * message
	 *
	 * @var string
	 */
	var $message;
		
	/**
	 * number of matches
	 *
	 * @var int
	 */
	var $num_matches = null;
	
	
	/**
	 * number of matches per page
	 *
	 * @var int
	 */
	var $num_matches_per_page = 0;
	
	
	/**
	 * number of pages for matches
	 *
	 * @var int
	 */
	var $num_max_pages = 0;
	

	/**
	 * match day
	 *
	 * @var int
	 */
	var $match_day = null;
	
	
    //SELECT c.`name`, t.`title`, tc.`match_day`, tc.`match_time`, display_name, user_email, meta_value
    //FROM `wp_leaguemanager_teams` t, `wp_leaguemanager_team_competition` tc, `wp_users` u, `wp_usermeta` um, `wp_leaguemanager_competitions` c
    //WHERE t.`id` = tc.`team_id`
    //AND tc.`captain` = u.`id`
    //AND u.`id` = um.`user_id`
    //AND tc.`competition_id` = c.`id`
    //AND c.id BETWEEN 1 and 6
    //AND um.`meta_key` = 'contactno'
    //AND t.`affiliatedclub` = 133
    //order by 1,2

}
?>
