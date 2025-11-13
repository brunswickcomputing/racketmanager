<?php
/**
 * Upgrade API: Upgrade class
 *
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Club_Role;
use stdClass;

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

    /**
     * Initialise the upgrade class
     */
    public function __construct() {
        global $racketmanager;
        $this->options   = $racketmanager->options;
        $this->installed = $this->options['dbversion'] ?? null;
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
            $clubs = $wpdb->get_results( "SELECT `id`, `matchsecretary` FROM $wpdb->racketmanager_clubs");
            foreach ( $clubs as $club ) {
                $club_role = new stdClass();
                $club_role->user_id = $club->matchsecretary;
                $club_role->club_id = $club->id;
                $club_role->role_id = 1;
                new Club_Role( $club_role );
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
            $wpdb->query( "ALTER TABLE $wpdb->racketmanager_clubs DROP `latitude`" );
            $wpdb->query( "ALTER TABLE $wpdb->racketmanager_clubs DROP `longitude`" );
        }
    }

    private function show_upgrade_step( string $version ): void {
        echo '<p>' . sprintf(esc_html__( 'starting %s upgrade', 'racketmanager' ), $version ) . '</p>';
    }
}
