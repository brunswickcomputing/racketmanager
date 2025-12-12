<?php
/**
 * Upgrade API: Upgrade class
 *
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\RacketManager;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Upgrade
 */
class Upgrade {
    /**
     * @var mixed|null current db version
     */
    private mixed $installed;
    /**
     * @var array racketmanager options
     */
    private array $options;
    private Club_Service $club_service;
    private wpdb $wpdb;
    private RacketManager $racketmanager;

    /**
     * Initialise the upgrade class
     */
    public function __construct( $plugin_instance ) {
        global $wpdb;
        $this->wpdb             = $wpdb;
        $this->racketmanager    = $plugin_instance;
        $this->options          = $this->racketmanager->options;
        $this->installed        = $this->options['dbversion'] ?? null;
        $c                      = $this->racketmanager->container;
        $this->club_service     = $c->get( 'club_service' );
    }

    /**
     * Run the upgrade
     *
     * @return void
     */
    public function run(): void {
        $this->wpdb->show_errors();
        $this->v9_7_0();
        $this->v10_0_0();
        $this->v10_0_1();
        $this->v10_0_2();
        $this->v10_0_3();
        $this->v10_0_4();
        $this->v10_0_5();
        $this->v10_0_6();
        /*
        * Update version and dbversion
        */
        $this->options['dbversion'] = RACKETMANAGER_DBVERSION;
        global $racketmanager;
        $racketmanager->update_plugin_options( $this->options );
    }

    /**
     * Upgrade to 9.7.0
     * Create club roles
     * Populate club roles for match secretary from the club table
     * Drop match secretary column
     *
     * @return void
     */
    private function v9_7_0 ():void {
        $version = '9.7.0';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $charset_collate = '';
            if ( $this->wpdb->has_cap( 'collation' ) ) {
                if ( ! empty( $this->wpdb->charset ) ) {
                    $charset_collate = 'DEFAULT CHARACTER SET ' . $this->wpdb->charset;
                }
                if ( ! empty( $this->wpdb->collate ) ) {
                    $charset_collate .= ' COLLATE ' . $this->wpdb->collate;
                }
            }
            $this->wpdb->query( "CREATE TABLE {$this->wpdb->prefix}racketmanager_club_roles ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `club_id` int( 11 ) NOT NULL, `role_id` int( 11 ) NOT NULL, `user_id` int( 11 ) NOT NULL, PRIMARY KEY ( `id` )) $charset_collate;" );
            $clubs                = $this->wpdb->get_results( "SELECT `id`, `matchsecretary` FROM {$this->wpdb->prefix}racketmanager_clubs");
            foreach ( $clubs as $club ) {
                $club_role      = $this->club_service->set_club_role( $club->id, 1, $club->matchsecretary );
                if ( $club_role ) {
                    $msg = sprintf(esc_html__( 'Club %s match secretary role set to %s', 'racketmanager' ), $club->id, $club_role->user_id );
                } else {
                    $msg = sprintf(esc_html__( 'Club %s match secretary role not set', 'racketmanager' ), $club->id );
                }
                echo '<p>' . esc_html( $msg ) . '</p>';
            }
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_clubs DROP `matchsecretary`" );
        }
    }

    /**
     * Upgrade to 10.0.0
     * Fake entry to trigger upgrade
     *
     * @return void
     */
    private function v10_0_0 ():void {
        $version = '10.0.0';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            // Define the parent page details
            $parent_page_slug    = 'clubs/club';
            $parent_page_title   = 'Club';
            $parent_page_content = '[club]';

            // Check if the parent page already exists to prevent duplicates
            $parent_page = get_page_by_path( $parent_page_slug );

            if ( empty( $parent_page ) ) {
                // Create the parent page
                $parent_args = array(
                    'title'    => __( $parent_page_title, 'racketmanager' ),
                    'content'  => $parent_page_content,
                    'status'   => 'publish',
                    'type'     => 'page',
                    'name'     => $parent_page_slug,
                );

                // Insert the parent page into the database and get its ID
                $parent_page_id = Util::add_racketmanager_page( $parent_page_slug, $parent_args );
            } else {
                // Get the existing parent page ID
                $parent_page_id = $parent_page->ID;
            }

            // Define and create child page if parent page exists
            if ( $parent_page_id )  {
                $child_page_slug = 'roles';

                // Check if the child page already exists
                $child_page = get_page_by_path($parent_page_slug . '/' . $child_page_slug );

                if ( empty( $child_page ) ) {
                    // Create the child page and set its parent
                    $child_args = array(
                        'title'    => __('Roles', 'racketmanager' ),
                        'content'  => '[club-roles]',
                        'status'   => 'publish',
                        'type'     => 'page',
                        'name'     => $child_page_slug,
                        'parent'   => $parent_page_id, // Set the parent ID here
                    );

                    // Insert the child page
                    Util::add_racketmanager_page( $child_page_slug, $child_args);
                }
            }
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_clubs DROP `latitude`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_clubs DROP `longitude`" );
        }
    }

    /**
     * Upgrade to 10.0.1
     * Change system `meta_key` to 'racketmanager_type'
     *
     * @return void
     */
    private function v10_0_1 ():void {
        $version = '10.0.1';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "UPDATE {$this->wpdb->prefix}usermeta SET `meta_key` = 'racketmanager_type' WHERE `meta_key` = 'leaguemanager_type'" );
        }
    }

    /**
     * Upgrade to 10.0.2
     * Drop redundant columns
     *
     * @return void
     */
    private function v10_0_2 ():void {
        $version = '10.0.2';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_player_errors DROP `status`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_player_errors DROP `updated_user`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_player_errors DROP `updated_date`" );
        }
    }

    /**
     * Upgrade to 10.0.3
     * Add `status` to `racketmanager_club_player` table and set
     *
     * @return void
     */
    private function v10_0_3 ():void {
        $version = '10.0.3';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_club_players ADD `status` VARCHAR(20) NULL AFTER `player_id`" );
            $this->wpdb->query( "UPDATE {$this->wpdb->prefix}racketmanager_club_players SET `status` = 'pending' WHERE `created_date` IS NULL AND `removed_date` IS NULL" );
            $this->wpdb->query( "UPDATE {$this->wpdb->prefix}racketmanager_club_players SET `status` = 'approved' WHERE `created_date` IS NOT NULL AND `removed_date` IS NULL" );
            $this->wpdb->query( "UPDATE {$this->wpdb->prefix}racketmanager_club_players SET `status` = 'removed' WHERE `removed_date` IS NOT NULL" );
        }
    }

    /**
     * Upgrade to 10.0.4
     * Drop redundant columns
     *
     * @return void
     */
    private function v10_0_4 ():void {
        $version = '10.0.4';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_teams DROP `custom`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_teams DROP `logo`" );
        }
    }

    /**
     * Upgrade to 10.0.5
     * Move the event specific team details (captain/match day/match time) to tables
     *
     * @return void
     */
    private function v10_0_5 ():void {
        $version = '10.0.5';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_table ADD `captain` INT NULL AFTER `season`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_table ADD `match_day` VARCHAR( 25 ) NULL AFTER `captain`" );
            $this->wpdb->query( "ALTER TABLE {$this->wpdb->prefix}racketmanager_table ADD `match_time` TIME NULL AFTER `match_day`" );
            // Migrate captain/match_day/match_time from team_events to table rows if missing
            // captain
            $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
                "UPDATE {$this->wpdb->prefix}racketmanager_table tbl
             JOIN {$this->wpdb->prefix}racketmanager_leagues l ON l.id = tbl.league_id
             JOIN {$this->wpdb->prefix}racketmanager_team_events tc ON tc.team_id = tbl.team_id AND tc.event_id = l.event_id
             SET tbl.captain = IF((tbl.captain IS NULL OR tbl.captain = 0), tc.captain, tbl.captain)"
            );
            // match_day
            $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
                "UPDATE {$this->wpdb->prefix}racketmanager_table tbl
             JOIN {$this->wpdb->prefix}racketmanager_leagues l ON l.id = tbl.league_id
             JOIN {$this->wpdb->prefix}racketmanager_team_events tc ON tc.team_id = tbl.team_id AND tc.event_id = l.event_id
             SET tbl.match_day = IF((tbl.match_day IS NULL OR tbl.match_day = ''), tc.match_day, tbl.match_day)"
            );
            // match_time
            $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
                "UPDATE {$this->wpdb->prefix}racketmanager_table tbl
             JOIN {$this->wpdb->prefix}racketmanager_leagues l ON l.id = tbl.league_id
             JOIN {$this->wpdb->prefix}racketmanager_team_events tc ON tc.team_id = tbl.team_id AND tc.event_id = l.event_id
             SET tbl.match_time = IF((tbl.match_time IS NULL), tc.match_time, tbl.match_time)"
            );
        }
    }

    /**
     * Upgrade to 10.0.6
     * Rename racketmanager_table to racketmanager_league_teams
     *
     * @return void
     */
    private function v10_0_6 ():void {
        $version = '10.0.6';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $this->wpdb->query( "RENAME TABLE {$this->wpdb->prefix}racketmanager_table TO {$this->wpdb->prefix}racketmanager_league_teams" );
        }
    }

    /**
     * Show upgrade step
     *
     * @param string $version
     *
     * @return void
     */
    private function show_upgrade_step( string $version ): void {
        echo '<p>' . sprintf(esc_html__( 'starting %s upgrade', 'racketmanager' ), $version ) . '</p>';
    }
}
