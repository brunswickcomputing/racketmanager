<?php
/**
 * Upgrade API: Upgrade class
 *
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Util\Util;

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
    private Club_Management_Service $club_service;

    /**
     * Initialise the upgrade class
     */
    public function __construct() {
        global $racketmanager;
        $this->options          = $racketmanager->options;
        $this->installed        = $this->options['dbversion'] ?? null;
        $club_repository        = new Club_Repository();
        $club_role_repository   = new Club_Role_Repository();
        $this->club_service     = new Club_Management_Service( $club_repository, $club_role_repository );
    }

    /**
     * Run the upgrade
     *
     * @return void
     */
    public function run(): void {
        global $wpdb;
        $wpdb->show_errors();
        $this->v9_7_0();
        $this->v10_0_0();
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
        global $wpdb;
        $version = '9.7.0';
        if ( version_compare( $this->installed, $version, '<' ) ) {
            $this->show_upgrade_step( $version );
            $charset_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                if ( ! empty($wpdb->charset) ) {
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                }
                if ( ! empty($wpdb->collate) ) {
                    $charset_collate .= " COLLATE $wpdb->collate";
                }
            }
            $wpdb->query( "CREATE TABLE $wpdb->racketmanager_club_roles ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `club_id` int( 11 ) NOT NULL, `role_id` int( 11 ) NOT NULL, `user_id` int( 11 ) NOT NULL, PRIMARY KEY ( `id` )) $charset_collate;" );
            $clubs                = $wpdb->get_results( "SELECT `id`, `matchsecretary` FROM $wpdb->racketmanager_clubs");
            foreach ( $clubs as $club ) {
                $club_role      = $this->club_service->set_club_role( $club->id, 1, $club->matchsecretary );
                if ( $club_role ) {
                    $msg = sprintf(esc_html__( 'Club %s match secretary role set to %s', 'racketmanager' ), $club->id, $club_role->user_id );
                } else {
                    $msg = sprintf(esc_html__( 'Club %s match secretary role not set', 'racketmanager' ), $club->id );
                }
                echo '<p>' . esc_html( $msg ) . '</p>';
            }
            $wpdb->query( "ALTER TABLE $wpdb->racketmanager_clubs DROP `matchsecretary`" );
        }
    }

    /**
     * Upgrade to 10.0.0
     * Fake entry to trigger upgrade
     *
     * @return void
     */
    private function v10_0_0 ():void {
        global $wpdb;
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
            $wpdb->query( "ALTER TABLE $wpdb->racketmanager_clubs DROP `latitude`" );
            $wpdb->query( "ALTER TABLE $wpdb->racketmanager_clubs DROP `longitude`" );
        }
    }

    private function show_upgrade_step( string $version ): void {
        echo '<p>' . sprintf(esc_html__( 'starting %s upgrade', 'racketmanager' ), $version ) . '</p>';
    }
}
