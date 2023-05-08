<?php
/*
Plugin Name: Racketmanager
Plugin URI: http://wordpress.org/extend/plugins/leaguemanager/
Description: Manage and present sports league results.
Version: 7.4.§
Author: Paul Moffat

Copyright 2008-2022  Paul Moffat (email: paul@paarcs.com)
Kolja Schleich  (email : kolja.schleich@googlemail.com)
LaMonte Forthun (email : lamontef@collegefundsoftware.com, lamontef@yahoo.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* RacketManager is a feature-rich racket management plugin supporting various different sport types including
* - tennis
*
* @author Paul Moffat
* @package RacketManager
* @version 7.4.§
* @copyright 2008-2022
* @license GPL-3
*/
setup_racketmanager_plugin();

function setup_racketmanager_plugin() {
	global $racketmanager;

	define( 'RACKETMANAGER', 'racketmanager');
	define( 'RACKETMANAGER_VERSION', '7.4.§' );
	define( 'RACKETMANAGER_DBVERSION', '7.4.0' );
	define( 'RACKETMANAGER_URL', esc_url(plugin_dir_url(__FILE__)) );
	define( 'RACKETMANAGER_PATH', plugin_dir_path(__FILE__) );
	define( 'RACKETMANAGER_PLUGIN_FILE',  __FILE__ );
	define( 'RACKETMANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	racketmanager_defineTables();

	require_once (RACKETMANAGER_PATH . 'include/class-util.php');
	require_once (RACKETMANAGER_PATH . 'include/class-racketmanager.php');

	load_plugin_textdomain( 'racketmanager', false, 'racketmanager/languages' );

	if ( is_admin() ) {
		require_once RACKETMANAGER_PATH . 'include/class-activator.php';
		$racketmanager_activator = new Racketmanager_Activator();
		$racketmanager_activator->setup();
		require_once (RACKETMANAGER_PATH . 'include/class-admin.php');
		add_action( 'plugins_loaded', 'load_racketmanager_admin' );
	} else {
		$racketmanager = new RacketManager();
	}

	// suppress output
	if ( isset($_POST['racketmanager_export']) ) {
		ob_start();
	}
}
function load_racketmanager_admin() {
	global $racketmanager;
	$racketmanager = new RacketManagerAdmin();
}
/**
* define database tables
*
*/
function racketmanager_defineTables() {
	global $wpdb;
	$wpdb->racketmanager = $wpdb->prefix . 'racketmanager_leagues';
	$wpdb->racketmanager_table = $wpdb->prefix . 'racketmanager_table';
	$wpdb->racketmanager_teams = $wpdb->prefix . 'racketmanager_teams';
	$wpdb->racketmanager_matches = $wpdb->prefix . 'racketmanager_matches';
	$wpdb->racketmanager_rubbers = $wpdb->prefix . 'racketmanager_rubbers';
	$wpdb->racketmanager_club_players = $wpdb->prefix . 'racketmanager_club_players';
	$wpdb->racketmanager_competitions = $wpdb->prefix . 'racketmanager_competitions';
	$wpdb->racketmanager_team_competition = $wpdb->prefix . 'racketmanager_team_competition';
	$wpdb->racketmanager_club_player_requests = $wpdb->prefix . 'racketmanager_club_player_requests';
	$wpdb->racketmanager_clubs = $wpdb->prefix . 'racketmanager_clubs';
	$wpdb->racketmanager_seasons = $wpdb->prefix . 'racketmanager_seasons';
	$wpdb->racketmanager_competitions_seasons = $wpdb->prefix . 'racketmanager_competitions_seasons';
	$wpdb->racketmanager_results_checker = $wpdb->prefix . 'racketmanager_results_checker';
	$wpdb->racketmanager_tournaments = $wpdb->prefix . 'racketmanager_tournaments';
	$wpdb->racketmanager_charges = $wpdb->prefix . 'racketmanager_charges';
	$wpdb->racketmanager_invoices = $wpdb->prefix . 'racketmanager_invoices';
}

?>
