<?php
/**
 * Activator API: Activator class
 *
 * @author Paul Moffat
 * @package Activator
 */

namespace Racketmanager;

defined( 'ABSPATH' ) || die( 'Access denied !' );
/**
 * Class for tasks to do during plugin activation and deactivation phases
 */
class Activator {
    /**
     * Activate plugin
     */
    public static function activate(): void {
        $options = get_option( 'racketmanager' );
        if ( ! $options ) {
            $color_white                  = '#FFFFFF';
            $options                      = array();
            $options['version']           = RACKETMANAGER_VERSION;
            $options['dbversion']         = RACKETMANAGER_DBVERSION;
            $options['textdomain']        = 'racketmanager';
            $options['colors']['headers'] = '#dddddd';
            $options['colors']['rows']    = array(
                'main'      => $color_white,
                'alternate' => '#efefef',
                'ascend'    => $color_white,
                'descend'   => $color_white,
                'relegate'  => $color_white,
            );

            add_option( 'racketmanager', $options, '', 'yes' );
        }

        // create directory.
        wp_mkdir_p( Util::get_file_path() );

        /*
        * Set Capabilities
        */
        $role = get_role( 'administrator' );
        if ( null !== $role ) {
            $role->add_cap( 'view_leagues' );
            $role->add_cap( 'racketmanager_settings' );
            $role->add_cap( 'edit_leagues' );
            $role->add_cap( 'edit_league_settings' );
            $role->add_cap( 'del_leagues' );
            $role->add_cap( 'edit_seasons' );
            $role->add_cap( 'del_seasons' );
            $role->add_cap( 'edit_teams' );
            $role->add_cap( 'del_teams' );
            $role->add_cap( 'edit_matches' );
            $role->add_cap( 'del_matches' );
            $role->add_cap( 'update_results' );
            $role->add_cap( 'export_leagues' );
            $role->add_cap( 'import_leagues' );
            $role->add_cap( 'manage_racketmanager' );

            // old roles.
            $role->add_cap( 'racketmanager' );
            $role->add_cap( 'racket_manager' );
        }

        $role = get_role( 'editor' );
        $role?->add_cap('racket_manager');

        static::create_login_pages();
        static::create_basic_pages();

        static::install();
    }
    /**
     * Create login pages
     */
    public static function create_login_pages(): void {
        // Information needed for creating the plugin's login/account pages.
        $no_title         = 'No title';
        $page_definitions = array(
            'member-login'          => array(
                'title'         => __( 'Sign In', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[custom-login-form]',
            ),
            'member-account'        => array(
                'title'         => __( 'Your Account', 'racketmanager' ),
                'page_template' => 'Member account',
                'content'       => '[account-info]',
            ),
            'member-password-lost'  => array(
                'title'         => __( 'Forgot Your Password?', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[custom-password-lost-form]',
            ),
            'member-password-reset' => array(
                'title'         => __( 'Pick a New Password', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[custom-password-reset-form]',
            ),
        );
        Util::add_racketmanager_page( $page_definitions );
    }

    /**
     * Create basic pages
     */
    public static function create_basic_pages(): void {
        // Information needed for creating the plugin's basic pages.
        $no_title         = 'No title';
        $page_definitions = array(
            'daily-matches-page'  => array(
                'title'         => __( 'Daily Matches', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[dailymatches]',
            ),
            'latest-results-page' => array(
                'title'         => __( 'Latest Results', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[latest_results]',
            ),
            'clubs-page'          => array(
                'title'         => __( 'Clubs', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[clubs]',
            ),
            'club-page'           => array(
                'title'         => __( 'Club', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[club]',
            ),
            'match-page'          => array(
                'title'         => __( 'Match', 'racketmanager' ),
                'page_template' => $no_title,
                'content'       => '[match]',
            ),
        );

        Util::add_racketmanager_page( $page_definitions );
    }

    /**
     * Install plugin
     */
    public static function install(): void {
        global $wpdb;
        include_once ABSPATH . '/wp-admin/includes/upgrade.php';

        $charset_collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty( $wpdb->charset ) ) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if ( ! empty( $wpdb->collate ) ) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }
        }
        $create_charges_sql = "CREATE TABLE $wpdb->racketmanager_charges ( `id` int NOT NULL AUTO_INCREMENT,`competition_id` int DEFAULT NULL, `season` varchar(255) NOT NULL DEFAULT '', `date` date DEFAULT NULL,`status` varchar(50) NOT NULL DEFAULT '', `fee_competition` decimal(10,2) DEFAULT NULL, `fee_event` decimal(10,2) DEFAULT NULL,  PRIMARY KEY (`id`) ) ENGINE=MyISAM $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_charges, $create_charges_sql );
        $create_clubs_sql = "CREATE TABLE $wpdb->racketmanager_clubs ( `id` int NOT NULL AUTO_INCREMENT, `name` varchar(100) NOT NULL DEFAULT '', `website` varchar(100) NOT NULL DEFAULT '', `type` varchar(20) NOT NULL DEFAULT '', `address` varchar(255) NOT NULL DEFAULT '',  `latitude` varchar(20) NOT NULL DEFAULT '', `longitude` varchar(20) NOT NULL DEFAULT '', `contactno` varchar(20) NOT NULL DEFAULT '', `founded` int DEFAULT NULL, `facilities` varchar(255) NOT NULL DEFAULT '', `shortcode` varchar(20) NOT NULL DEFAULT '', `matchsecretary` int DEFAULT NULL,  PRIMARY KEY (`id`) ) ENGINE=MyISAM $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_clubs, $create_clubs_sql );
        $create_club_players_sql = "CREATE TABLE $wpdb->racketmanager_club_players ( `id` int NOT NULL AUTO_INCREMENT, `club_id` int DEFAULT NULL, `player_id` int NOT NULL DEFAULT '0', `removed_date` date DEFAULT NULL, `removed_user` int DEFAULT NULL, `system_record` varchar(1) DEFAULT NULL, `requested_date` date DEFAULT NULL, `requested_user` int DEFAULT NULL, `created_date` datetime DEFAULT NULL, `created_user` int DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_club_players, $create_club_players_sql );
        $create_competitions_sql = "CREATE TABLE $wpdb->racketmanager_competitionss ( `id` int NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL DEFAULT '', `settings` longtext NOT NULL, `seasons` longtext, `type` varchar(255) NOT NULL, `age_group` varchar(10) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_competitions, $create_competitions_sql );
        $create_competitions_seasons_sql = "CREATE TABLE $wpdb->racketmanager_competitions_seasons ( id` int NOT NULL AUTO_INCREMENT, `competition_id` int NOT NULL, `season_id` int NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_competitions_seasons, $create_competitions_seasons_sql );
        $create_events_sql = "CREATE TABLE $wpdb->racketmanager_events ( `id` int NOT NULL AUTO_INCREMENT, `competition_id` int DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `type` varchar(2) DEFAULT NULL, `num_sets` int DEFAULT NULL, `num_rubbers` int DEFAULT NULL, `settings` longtext, `seasons` longtext, PRIMARY KEY (`id`), KEY `competition_id` (`competition_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_events, $create_events_sql );
        $create_invoices_sql = "CREATE TABLE $wpdb->racketmanager_invoices ( `id` int NOT NULL AUTO_INCREMENT, `charge_id` int NOT NULL, `club_id` int DEFAULT NULL, `player_id` int DEFAULT NULL, `invoiceNumber` int NOT NULL, `status` varchar(50) NOT NULL, `amount` decimal(10,2) DEFAULT NULL, `date` date DEFAULT NULL, `date_due` date DEFAULT NULL, `payment_reference` varchar(50) DEFAULT NULL, `purchase_order` varchar(50) DEFAULT NULL, `details` longtext, PRIMARY KEY (`id`))) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_invoices, $create_invoices_sql );
        $create_leagues_sql = "CREATE TABLE $wpdb->racketmanager ( `id` int NOT NULL AUTO_INCREMENT, `title` varchar(100) NOT NULL DEFAULT '', `settings` longtext NOT NULL, `seasons` longtext NOT NULL, `sequence` varchar(3) DEFAULT NULL, `event_id` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `event_id` (`event_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager, $create_leagues_sql );
        $create_matches_sql = "CREATE TABLE $wpdb->racketmanager_matches ( `id` int NOT NULL AUTO_INCREMENT, `group` varchar(30) DEFAULT NULL, `date` datetime DEFAULT NULL, `date_original` datetime DEFAULT NULL, `home_team` varchar(255) NOT NULL DEFAULT '0', `away_team` varchar(255) NOT NULL DEFAULT '0', `match_day` tinyint NOT NULL DEFAULT '0', `location` varchar(100) DEFAULT NULL, `host` varchar(4) DEFAULT NULL, `league_id` int NOT NULL DEFAULT '0', `season` varchar(4) NOT NULL, `home_points` varchar(30) DEFAULT NULL, `away_points` varchar(30) DEFAULT NULL, `winner_id` int NOT NULL DEFAULT '0', `loser_id` int NOT NULL DEFAULT '0', `status` int DEFAULT NULL, `linked_match` int DEFAULT NULL, `leg` int DEFAULT NULL, `winner_id_tie` int DEFAULT NULL, `loser_id_tie` int DEFAULT NULL, `home_points_tie` float DEFAULT NULL, `away_points_tie` float DEFAULT NULL, `post_id` int NOT NULL DEFAULT '0', `final` varchar(150) DEFAULT NULL, `custom` longtext, `updated_user` int DEFAULT NULL, `updated` datetime DEFAULT NULL, `date_result_entered` datetime DEFAULT NULL, `confirmed` varchar(1) DEFAULT NULL, `home_captain` int DEFAULT NULL, `away_captain` int DEFAULT NULL, `comments` longtext, PRIMARY KEY (`id`), KEY `league_id` (`league_id`,`season`), KEY `season` (`season`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_matches, $create_matches_sql );
        $create_messages_sql = "CREATE TABLE $wpdb->racketmanager_messages ( `id` int NOT NULL AUTO_INCREMENT, `subject` varchar(255) NOT NULL, `userid` int NOT NULL, `date` datetime NOT NULL, `sender` varchar(255) NOT NULL, `status` varchar(1) DEFAULT NULL, `message_object` blob NOT NULL, PRIMARY KEY (`id`), KEY `userid` (`userid`) ) ENGINE=InnoDB$charset_collate;";
        maybe_create_table( $wpdb->racketmanager_messages, $create_messages_sql );
        $create_player_errors_sql = "CREATE TABLE $wpdb->racketmanager_player_errors ( `id` int NOT NULL AUTO_INCREMENT, `player_id` int DEFAULT NULL, `message` varchar(255) DEFAULT NULL, `status` int DEFAULT NULL, `created_date` datetime DEFAULT NULL, `updated_user` int DEFAULT NULL, `updated_date` datetime DEFAULT NULL, PRIMARY KEY (`id`)  ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_player_errors, $create_player_errors_sql );
        $create_results_checker_sql = "CREATE TABLE $wpdb->racketmanager_results_checker ( `id` int NOT NULL AUTO_INCREMENT, `league_id` int NOT NULL DEFAULT '0', `match_id` int NOT NULL DEFAULT '0', `team_id` int DEFAULT NULL, `player_id` int DEFAULT NULL, `rubber_id` int DEFAULT NULL, `description` varchar(255) DEFAULT NULL, `status` int DEFAULT NULL, `updated_user` int DEFAULT NULL, `updated_date` datetime DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_results_checker, $create_results_checker_sql );
        $create_results_report_sql = "CREATE TABLE $wpdb->racketmanager_results_report ( `id` int NOT NULL AUTO_INCREMENT, `match_id` int NOT NULL, `result_object` blob NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_results_report, $create_results_report_sql );
        $create_rubbers_sql = "CREATE TABLE $wpdb->racketmanager_rubbers ( `id` int NOT NULL AUTO_INCREMENT, `group` varchar(30) NOT NULL DEFAULT '', `date` datetime NOT NULL, `match_id` int NOT NULL DEFAULT '0', `rubber_number` int NOT NULL DEFAULT '0', `home_points` varchar(30) DEFAULT NULL, `away_points` varchar(30) DEFAULT NULL, `winner_id` int NOT NULL DEFAULT '0', `loser_id` int NOT NULL DEFAULT '0', `status` int DEFAULT NULL, `post_id` int NOT NULL DEFAULT '0', `type` varchar(2) DEFAULT NULL, `custom` longtext, PRIMARY KEY (`id`), KEY `match_id` (`match_id`), KEY `winner_id` (`winner_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_rubbers, $create_rubbers_sql );
        $create_rubber_players_sql = "CREATE TABLE $wpdb->racketmanager_rubber_players ( `id` int NOT NULL AUTO_INCREMENT, `rubber_id` int NOT NULL, `player_ref` int DEFAULT NULL, `player_team` varchar(4) DEFAULT NULL, `player_id` int DEFAULT NULL, `club_player_id` int DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `rubber_id` (`rubber_id`,`player_ref`,`player_team`), KEY `player_id` (`player_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_rubber_players, $create_rubber_players_sql );
        $create_seasons_sql = "CREATE TABLE $wpdb->racketmanager_seasons ( `id` int NOT NULL AUTO_INCREMENT, `name` varchar(100) NOT NULL DEFAULT '', PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_seasons, $create_seasons_sql );
        $create_table_sql = "CREATE TABLE $wpdb->racketmanager_table ( `id` int NOT NULL AUTO_INCREMENT, `team_id` int NOT NULL, `league_id` int NOT NULL, `season` varchar(4) NOT NULL, `points_plus` float NOT NULL DEFAULT '0', `points_minus` float NOT NULL DEFAULT '0', `points_2_plus` int NOT NULL DEFAULT '0', `points_2_minus` int NOT NULL DEFAULT '0', `add_points` float NOT NULL DEFAULT '0', `done_matches` int NOT NULL DEFAULT '0', `won_matches` int NOT NULL DEFAULT '0', `draw_matches` int NOT NULL DEFAULT '0', `lost_matches` int NOT NULL DEFAULT '0', `diff` int NOT NULL DEFAULT '0', `group` varchar(30) NOT NULL DEFAULT '', `rank` int NOT NULL DEFAULT '0', `profile` int NOT NULL DEFAULT '0', `status` varchar(50) NOT NULL DEFAULT '&#8226;', `rating` float DEFAULT NULL, `custom` longtext, PRIMARY KEY (`id`), KEY `team_id` (`team_id`), KEY `season` (`season`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_table, $create_table_sql );
        $create_teams_sql = "CREATE TABLE $wpdb->racketmanager_teams ( `id` int NOT NULL AUTO_INCREMENT, `status` varchar(50) NOT NULL DEFAULT '&#8226;', `title` varchar(100) NOT NULL DEFAULT '', `logo` varchar(150) NOT NULL DEFAULT '', `club_id` int DEFAULT NULL, `stadium` varchar(150) NOT NULL DEFAULT '', `home` tinyint(1) NOT NULL DEFAULT '0', `roster` longtext, `profile` int NOT NULL DEFAULT '0', `custom` longtext, `type` varchar(2) NOT NULL DEFAULT '', `team_type` varchar(1) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_teams, $create_teams_sql );
        $create_team_events_sql = "CREATE TABLE $wpdb->racketmanager_team_events ( `id` int NOT NULL AUTO_INCREMENT, `team_id` int NOT NULL DEFAULT '0', `event_id` int DEFAULT NULL, `captain` varchar(255) NOT NULL DEFAULT '', `match_day` varchar(25) NOT NULL DEFAULT '', `match_time` time DEFAULT NULL, PRIMARY KEY (`id`), KEY `team_id` (`team_id`), KEY `competition_id` (`event_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_team_events, $create_team_events_sql );
        $create_team_players_sql = "CREATE TABLE $wpdb->racketmanager_team_players ( `id` int NOT NULL AUTO_INCREMENT, `team_id` int NOT NULL, `player_id` int NOT NULL, PRIMARY KEY (`id`), KEY `team_id` (`team_id`), KEY `player_id` (`player_id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_team_players, $create_team_players_sql );
        $create_tournaments_sql = "CREATE TABLE $wpdb->racketmanager_tournaments ( `id` int NOT NULL AUTO_INCREMENT, `name` varchar(100) NOT NULL DEFAULT '', `competition_id` int DEFAULT NULL, `season` varchar(255) NOT NULL DEFAULT '', `venue` int DEFAULT NULL, `date` date DEFAULT NULL, `date_closing` date DEFAULT NULL, `date_withdrawal` date DEFAULT NULL, `date_open` date DEFAULT NULL, `date_start` date DEFAULT NULL, `competition_code` varchar(50) DEFAULT NULL, `grade` varchar(1) DEFAULT NULL, `num_entries` int DEFAULT NULL, `numcourts` int DEFAULT NULL, `starttime` time DEFAULT NULL, `timeincrement` time DEFAULT NULL, `orderofplay` longtext, `information` longtext, PRIMARY KEY (`id`) ) ENGINE=InnoDB $charset_collate;";
        maybe_create_table( $wpdb->racketmanager_tournaments, $create_tournaments_sql );
        $create_tournament_entries_sql = "CREATE TABLE $wpdb->racketmanager_tournament_entries ( `id` int NOT NULL AUTO_INCREMENT, `tournament_id` int NOT NULL, `player_id` int NOT NULL, `status` int NOT NULL, `fee` decimal(10,2) DEFAULT NULL, `club_id` int DEFAULT NULL, PRIMARY KEY (`id`),  KEY `tournament_id` (`tournament_id`) ) ENGINE=InnoDB$charset_collate;";
        maybe_create_table( $wpdb->racketmanager_tournament_entries, $create_tournament_entries_sql );
    }

    /**
     * Deactivate plugin
     */
    public static function deactivate() {
    }
}
