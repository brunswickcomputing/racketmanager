<?php
/**
 * Plugin Name: Racketmanager
 * Plugin URI: http://wordpress.org/extend/plugins/racketmanager/
 * Description: Manage and present racket sports league and tournament results.
 * Version: 10.0.0
 * Author: Paul Moffat
 * Text Domain: racketmanager
 *
 * @package plugin Racketmanager
 *
 * Copyright 2025 Paul Moffat (email: paul@brunswickcomputing.co.uk)
 * Based initially on racketmanager plugin.
 */

namespace Racketmanager;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
$site_url  = get_option( 'siteurl' );
$site_url .= '/';

define( 'RACKETMANAGER', 'racketmanager' );
define( 'RACKETMANAGER_VERSION', '10.0.0' );
define( 'RACKETMANAGER_DBVERSION', '10.0.10' );
define( 'RACKETMANAGER_SITE', $site_url );
define( 'RACKETMANAGER_URL', esc_url( plugin_dir_url( __FILE__ ) ) );
define( 'RACKETMANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'RACKETMANAGER_PLUGIN_FILE', __FILE__ );
define( 'RACKETMANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( 'RACKETMANAGER_CHECKED', 'checked' );
define( 'RACKETMANAGER_IS_INVALID', 'is-invalid' );
define( 'RACKETMANAGER_FROM_EMAIL', 'From: ' );
define( 'RACKETMANAGER_CC_EMAIL', 'cc: ' );
define( 'RACKETMANAGER_BCC_EMAIL', 'bcc: ' );

// -----------------------------------------------------------------------------
// Autoloader (Composer PSR-4)
// -----------------------------------------------------------------------------
$autoload_path = RACKETMANAGER_PATH . 'vendor/autoload.php';
if ( file_exists( $autoload_path ) ) {
    require_once $autoload_path;
}

// -----------------------------------------------------------------------------
// I18n
// -----------------------------------------------------------------------------
load_plugin_textdomain( 'racketmanager', false, 'racketmanager/languages' );

// -----------------------------------------------------------------------------
// Activation / Deactivation
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, array( 'Racketmanager\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Racketmanager\\Activator', 'deactivate' ) );

// -----------------------------------------------------------------------------
// DB table name globals (kept for BC with existing code that references $wpdb->racketmanager_*)
// -----------------------------------------------------------------------------
function define_tables(): void {
    global $wpdb;
    $wpdb->racketmanager                      = $wpdb->prefix . 'racketmanager_leagues';
    $wpdb->racketmanager_league_teams         = $wpdb->prefix . 'racketmanager_league_teams';
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
    $wpdb->racketmanager_club_roles           = $wpdb->prefix . 'racketmanager_club_roles';
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\define_tables', 0 );

// -----------------------------------------------------------------------------
// Output buffering for exports (kept behavior)
// -----------------------------------------------------------------------------
function maybe_buffer_export(): void {
    if ( isset( $_POST['racketmanager_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        ob_start();
    }
}
add_action( 'init', __NAMESPACE__ . '\\maybe_buffer_export' );

// -----------------------------------------------------------------------------
// Bootstrap core on plugins_loaded
// -----------------------------------------------------------------------------
add_action( 'plugins_loaded', function () {
    // Core singleton - Admin singleton when in the dashboard
    if ( is_admin() ) {
        $instance = Admin::get_instance();
    } else {
        $instance = RacketManager::get_instance();
    }

    // Global for BC with legacy code that expects $racketmanager to be set.
    global $racketmanager;
    $racketmanager = $instance;
}, 5 );
