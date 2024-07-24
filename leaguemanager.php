<?php
/**
 * Plugin Name: Racketmanager
 * Plugin URI: http://wordpress.org/extend/plugins/leaguemanager/
 * Description: Manage and present racketsports league and tournament results.
 * Version: 8.11.0
 * Author: Paul Moffat
 * Text Domain: racketmanager
 *
 * @package plugin Racketmanager
 *
 * Copyright 2024  Paul Moffat (email: paul@paarcs.com)
 * Based initially on leaguemanager plugin.
 */

namespace Racketmanager;

/**
 * RacketManager is a feature-rich racket management plugin supporting various different sport types including
 * - tennis
 *
 * @author Paul Moffat
 * @package RacketManager
 * @version 8.11.0
 * @copyright 2024
 */
racketmanager_setup_plugin();

/**
 * Setup plugin
 */
function racketmanager_setup_plugin() {
	global $racketmanager;

	define( 'RACKETMANAGER', 'racketmanager' );
	define( 'RACKETMANAGER_VERSION', '8.11.0' );
	define( 'RACKETMANAGER_DBVERSION', '8.10.0' );
	define( 'RACKETMANAGER_URL', esc_url( plugin_dir_url( __FILE__ ) ) );
	define( 'RACKETMANAGER_PATH', plugin_dir_path( __FILE__ ) );
	define( 'RACKETMANAGER_PLUGIN_FILE', __FILE__ );
	define( 'RACKETMANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	define( 'RACKETMANAGER_CHECKED', 'checked' );
	define( 'RACKETMANAGER_IS_INVALID', 'is_invalid' );
	racketmanager_define_tables();

	require_once RACKETMANAGER_PATH . 'include/class-racketmanager-util.php';
	require_once RACKETMANAGER_PATH . 'include/class-racketmanager.php';

	load_plugin_textdomain( 'racketmanager', false, 'racketmanager/languages' );

	if ( is_admin() ) {
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-activator.php';
		$racketmanager_activator = new Racketmanager_Activator();
		$racketmanager_activator->setup();
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin.php';
		$racketmanager = new RacketManager_Admin();
	} else {
		$racketmanager = new RacketManager();
	}

	// suppress output.
	if ( isset( $_POST['racketmanager_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		ob_start();
	}
}
/**
 * Define database tables
 */
function racketmanager_define_tables() {
	global $wpdb;
	$wpdb->racketmanager                      = $wpdb->prefix . 'racketmanager_leagues';
	$wpdb->racketmanager_table                = $wpdb->prefix . 'racketmanager_table';
	$wpdb->racketmanager_teams                = $wpdb->prefix . 'racketmanager_teams';
	$wpdb->racketmanager_matches              = $wpdb->prefix . 'racketmanager_matches';
	$wpdb->racketmanager_rubbers              = $wpdb->prefix . 'racketmanager_rubbers';
	$wpdb->racketmanager_club_players         = $wpdb->prefix . 'racketmanager_club_players';
	$wpdb->racketmanager_competitions         = $wpdb->prefix . 'racketmanager_competitions';
	$wpdb->racketmanager_team_events          = $wpdb->prefix . 'racketmanager_team_events';
	$wpdb->racketmanager_club_player_requests = $wpdb->prefix . 'racketmanager_club_player_requests';
	$wpdb->racketmanager_clubs                = $wpdb->prefix . 'racketmanager_clubs';
	$wpdb->racketmanager_seasons              = $wpdb->prefix . 'racketmanager_seasons';
	$wpdb->racketmanager_competitions_seasons = $wpdb->prefix . 'racketmanager_competitions_seasons';
	$wpdb->racketmanager_results_checker      = $wpdb->prefix . 'racketmanager_results_checker';
	$wpdb->racketmanager_tournaments          = $wpdb->prefix . 'racketmanager_tournaments';
	$wpdb->racketmanager_charges              = $wpdb->prefix . 'racketmanager_charges';
	$wpdb->racketmanager_invoices             = $wpdb->prefix . 'racketmanager_invoices';
	$wpdb->racketmanager_events               = $wpdb->prefix . 'racketmanager_events';
	$wpdb->racketmanager_rubber_players       = $wpdb->prefix . 'racketmanager_rubber_players';
	$wpdb->racketmanager_results_report       = $wpdb->prefix . 'racketmanager_results_report';
	$wpdb->racketmanager_messages             = $wpdb->prefix . 'racketmanager_messages';
}
