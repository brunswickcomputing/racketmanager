<?php
/**
 * Plugin Name: Racketmanager
 * Plugin URI: http://wordpress.org/extend/plugins/leaguemanager/
 * Description: Manage and present racket sports league and tournament results.
 * Version: 8.45.0
 * Author: Paul Moffat
 * Text Domain: racketmanager
 *
 * @package plugin Racketmanager
 *
 * Copyright 2025  Paul Moffat (email: paul@brunswickcomputing.co,uk)
 * Based initially on racketmanager plugin.
 */

namespace Racketmanager;

/**
 * RacketManager is a feature-rich racket management plugin supporting various different sport types including
 * - tennis
 *
 * @author Paul Moffat
 * @package RacketManager
 * @version 8.45.0
 * @copyright 2025
 */
if ( ! defined( 'ABSPATH' ) ) {
	//Exit if this file is accessed directly.
	exit;
}
$site_url  = get_option( 'siteurl' );
$site_url .=  '/';
define( 'RACKETMANAGER', 'racketmanager' );
define( 'RACKETMANAGER_VERSION', '8.45.0' );
define( 'RACKETMANAGER_DBVERSION', '8.47.5' );
define( 'RACKETMANAGER_SITE', $site_url );
define( 'RACKETMANAGER_URL', esc_url( plugin_dir_url( __FILE__ ) ) );
define( 'RACKETMANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'RACKETMANAGER_PLUGIN_FILE', __FILE__ );
define( 'RACKETMANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RACKETMANAGER_CHECKED', 'checked' );
define( 'RACKETMANAGER_IS_INVALID', 'is-invalid' );

class RacketmanagerMain {
	public function __construct() {
		global $racketmanager;
		$this->define_tables();

		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-util.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-activator.php';

		load_plugin_textdomain( 'racketmanager', false, 'racketmanager/languages' );

		register_activation_hook( __FILE__, array( 'RacketManager\Racketmanager_Activator', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'RacketManager\Racketmanager_Activator', 'deactivate' ) );
		add_action( 'plugins_loaded', array( 'RacketManager\RacketManager', 'get_instance' ) );

		if ( is_admin() ) {
			require_once RACKETMANAGER_PATH . 'include/class-racketmanager-admin.php';
			add_action( 'plugins_loaded', array( 'RacketManager\RacketManager_Admin', 'get_instance' ) );
			$racketmanager = new RacketManager_Admin();
		} else {
			$racketmanager = new RacketManager();
		}

		// suppress output.
		if ( isset( $_POST['racketmanager_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			ob_start();
		}
	}
	private function define_tables(): void {
		global $wpdb;
		$wpdb->racketmanager                      = $wpdb->prefix . 'racketmanager_leagues';
		$wpdb->racketmanager_table                = $wpdb->prefix . 'racketmanager_table';
		$wpdb->racketmanager_teams                = $wpdb->prefix . 'racketmanager_teams';
		$wpdb->racketmanager_matches              = $wpdb->prefix . 'racketmanager_matches';
		$wpdb->racketmanager_rubbers              = $wpdb->prefix . 'racketmanager_rubbers';
		$wpdb->racketmanager_club_players         = $wpdb->prefix . 'racketmanager_club_players';
		$wpdb->racketmanager_competitions         = $wpdb->prefix . 'racketmanager_competitions';
		$wpdb->racketmanager_team_events          = $wpdb->prefix . 'racketmanager_team_events';
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
		$wpdb->racketmanager_team_players         = $wpdb->prefix . 'racketmanager_team_players';
		$wpdb->racketmanager_tournament_entries   = $wpdb->prefix . 'racketmanager_tournament_entries';
		$wpdb->racketmanager_player_errors        = $wpdb->prefix . 'racketmanager_player_errors';
	}
}
new RacketmanagerMain();
