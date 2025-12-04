<?php
/**
 * Club_Role Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club_Role;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Club_Role repository
 */
class Club_Role_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_club_roles';
    }

    /**
     * Inserts a new club role into the database.
     * The save action is explicit, not in the Club Role constructor.
     * @param Club_Role $club_role The club role object to save.
     */
    public function save( Club_Role $club_role ): void {
        if ( $club_role->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'club_id' => $club_role->get_club_id(),
                    'role_id' => $club_role->get_role_id(),
                    'user_id' => $club_role->get_user_id(),
                ),
                array(
                    '%d', // Format for club_id (integer)
                    '%d', // Format for role_id (integer)
                    '%d', // Format for user_id (integer)
                )
            );
            $club_role->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                array('user_id' => $club_role->get_user_id() ), // Data to update
                array('id' => $club_role->get_id() ),            // Where clause
                array('%d'),                                  // Data format
                array('%d')                                   // Where format
            );
        }
        wp_cache_flush_group( 'club-roles' );

        // Optional: Update the user object with the new ID
        // if ($user->getId() === null && $this->wpdb->insert_id) {
        //     // This requires the ID property to be mutable or passed back
        // }
    }

    /**
     * Retrieves an existing club role from the database by ID.
     *
     * @param int $id The user ID.
     *
     * @return Club_Role|null The user object or null if not found.
     */
    public function find( int $id ): ?Club_Role {
        $club_role_data = wp_cache_get( $id, 'club-roles' );
        if ( ! $club_role_data ) {

            // Prepare the query safely using prepare() to prevent SQL injection
            $query = $this->wpdb->prepare(
                "SELECT `id`, `user_id`, `club_id`, `role_id` FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            );

            $club_role_data = $this->wpdb->get_row( $query ); // Get a single row as an object
            if ( $club_role_data ) {
                wp_cache_set( $id, $club_role_data, 'club-roles' );
            }
        }

        if ( $club_role_data ) {
            // Instantiate and return a new User object with the fetched data
            return new Club_Role( $club_role_data );
        }

        return null; // Club role not found
    }

    /**
     * Retrieves existing club roles from the database by parameters.
     *
     * @param array $args search arguments.
     *
     * @return Club_Role|null The user object or null if not found.
     */
    public function search( array $args = array() ): ?array {
        $defaults   = array(
            'role'  => false,
            'user'  => false,
            'club'  => false,
            'group' => false
        );
        $args  = array_merge( $defaults, $args );
        $role  = $args['role'];
        $user  = $args['user'];
        $club  = $args['club'];
        $group = $args['group'];

        $search_terms = array();
        if ( $role ) {
            $search_terms[] = $this->wpdb->prepare( '`role_id` = %d', intval( $role ) );
        }
        if ( $user ) {
            $search_terms[] = $this->wpdb->prepare( '`user_id` = %d', intval( $user ) );
        }
        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( '`club_id` = %d', intval( $club ) );
        }
        if ($group ) {
            $search_terms[] = "'group' = 'group'";
        }
        $search     = Util::search_string( $search_terms, true );
        $sql        = "SELECT `id` FROM $this->table_name " . $search;
        $club_roles = wp_cache_get( md5( $sql ), 'club-roles' );
        if ( ! $club_roles ) {
            $club_roles = array();
            $roles      = $this->wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call OK.
            foreach ( $roles as $club_role_ref ) {
                $club_role = $this->find( $club_role_ref->id );
                if ( $club_role ) {
                    if ( $group ) {
                        if ( ! isset( $club_roles[ $club_role->role_id ] ) ) {
                            $club_roles[ $club_role->role_id ] = array();
                        }
                        $club_roles[ $club_role->role_id ][] = $club_role;
                    } else {
                        $club_roles[] = $club_role;
                    }
                }
            }
            wp_cache_set( md5( $sql ), $club_roles, 'club-roles' );
        }
        return $club_roles;
    }

    /**
     * Delete a club role from the database.
     *
     * @param $id
     *
     * @return void
     */
    public function delete_for_role( $id ): void {
        $this->wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );
        wp_cache_flush_group( 'club-roles' );
    }

    /**
     * Delete all club roles for a club.
     *
     * @param int $club_id
     *
     * @return void
     */
    public function delete_for_club( int $club_id ): void {
        $this->wpdb->delete(
            $this->table_name,
            array( 'club_id' => $club_id ),
            array( '%d' )
        );
        wp_cache_flush_group( 'club-roles' );
    }

    /**
     * Get all club roles for a club.
     *
     * @param int $club_id
     *
     * @return array
     */
    public function get_roles_for_club( int $club_id ): array {
        return $this->search( array( 'club' => $club_id ) );
    }
}
