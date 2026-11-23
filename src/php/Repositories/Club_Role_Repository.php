<?php
declare( strict_types=1 );
/**
 * Club_Role Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use wpdb;

/**
 * Class to implement the Club_Role repository
 */
class Club_Role_Repository implements Club_Role_Repository_Interface {
    private ?wpdb $wpdb;
    private string $table_name;

    /**
     * Initialises the club role repository with required dependencies and database table name.
     *
     * @param User_Repository_Interface $user_repository User repository dependency.
     * @param wpdb|null                 $wpdb Optional WordPress database instance.
     *
     * @return void
     */
    public function __construct(
        private readonly User_Repository_Interface $user_repository,
        ?wpdb $wpdb = null
    ) {
        if ( null === $wpdb ) {
            global $wpdb;
        }
        $this->wpdb       = $wpdb;
        $prefix           = ( $this->wpdb && isset( $this->wpdb->prefix ) ) ? $this->wpdb->prefix : 'wp_';
        $this->table_name = $prefix . 'racketmanager_club_roles';
    }

    /**
     * Inserts a new club role into the database.
     * The save action is explicit, not in the Club Role constructor.
     *
     * @param object $entity The club role object to save.
     */
    public function save( object $entity ): bool|int {
        /** @var Club_Role $club_role */
        $club_role = $entity;
        if ( $club_role->get_id() === null ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                array(
                    'club_id' => $club_role->get_club_id(),
                    'role_id' => $club_role->get_role_id(),
                    'user_id' => $club_role->get_user_id(),
                ),
                array(
                    '%d', // Format for club_id (integer).
                    '%d', // Format for role_id (integer).
                    '%d', // Format for user_id (integer).
                )
            );
            if ( $inserted ) {
                $club_role->set_id( $this->wpdb->insert_id );
                wp_cache_flush_group( 'club-roles' );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            $updated = $this->wpdb->update(
                $this->table_name,
                array( 'user_id' => $club_role->get_user_id() ), // Data to update.
                array( 'id' => $club_role->get_id() ),            // Where clause.
                array( '%d' ),                                  // Data format.
                array( '%d' )                                   // Where format.
            );
            wp_cache_flush_group( 'club-roles' );
            return false !== $updated;
        }
    }

    /**
     * Retrieves an existing club role from the database by ID.
     *
     * @param int|string|null $id The user ID.
     *
     * @return Club_Role|null The user object or null if not found.
     */
    public function find_by_id( int|string|null $id ): ?Club_Role {
        return $this->find( (int) $id );
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

            // Prepare the query safely using prepare() to prevent SQL injection.
            $query = $this->wpdb->prepare(
                "SELECT `id`, `user_id`, `club_id`, `role_id` FROM $this->table_name WHERE `id` = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $id
            );
            // Get a single row as an object.
            $club_role_data = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            if ( $club_role_data ) {
                wp_cache_set( $id, $club_role_data, 'club-roles' );
            }
        }

        if ( $club_role_data ) {
            return $this->hydrate_club_role( $club_role_data );
        }

        return null; // Club role not found.
    }

    /**
     * Hydrates a club role from a database row and loads its related user data.
     *
     * @param object $row Database row used to create the club role.
     *
     * @return Club_Role Hydrated club role instance.
     */
    public function hydrate_club_role( object $row ): Club_Role {
        $club_role = Club_Role_Factory::from_object( $row );
        $this->hydrate_user( $club_role );
        return $club_role;
    }

    /**
     * Hydrates the user associated with the club role when a user ID is available.
     *
     * @param Club_Role $club_role The club role to hydrate with its user.
     *
     * @return void
     */
    public function hydrate_user( Club_Role $club_role ): void {
        if ( ! empty( $club_role->user_id ) && null === $club_role->user && null !== $this->user_repository ) {
            $club_role->set_user( $this->user_repository->find( $club_role->user_id ) );
        }
    }

    /**
     * Deletes a record from the database by ID.
     *
     * @param int $id The ID of the record to delete.
     *
     * @return bool True if the delete query completes successfully, false on failure.
     */
    public function delete( int $id ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Retrieves existing club roles from the database by parameters.
     *
     * @param array $args search arguments.
     *
     * @return Club_Role|null The user object or null if not found.
     */
    public function search( array $args = array() ): ?array {
        $defaults = array(
            'role'  => false,
            'user'  => false,
            'club'  => false,
            'group' => false,
        );
        $args     = array_merge( $defaults, $args );
        $role     = $args['role'];
        $user     = $args['user'];
        $club     = $args['club'];
        $group    = $args['group'];

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
        if ( $group ) {
            $search_terms[] = "'group' = 'group'";
        }
        $search     = Util::search_string( $search_terms, true );
        $sql        = "SELECT `id` FROM $this->table_name " . $search;
        $club_roles = wp_cache_get( md5( $sql ), 'club-roles' );
        if ( ! $club_roles ) {
            $club_roles = $this->build_club_roles( $sql, (string) $group );
        }
        return $club_roles;
    }

    /**
     * Builds club role objects from a database query and stores the result in cache.
     *
     * @param string $sql SQL query used to retrieve club role identifiers.
     * @param string $group Whether to group club roles by role ID.
     *
     * @return array List of club roles, or club roles grouped by role ID when grouping is enabled.
     */
    public function build_club_roles( string $sql, string $group ): array {
        $club_roles = array();
        $roles      = $this->wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql
        ); // db call OK.
        foreach ( $roles as $club_role_ref ) {
            $club_role = $this->find( (int) $club_role_ref->id );
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
        return $club_roles;
    }

    /**
     * Deletes a club role from the database by role ID.
     *
     * @param int $id The role ID to delete.
     *
     * @return bool True if the delete operation did not fail, false otherwise.
     */
    public function delete_for_role( int $id ): bool {
        $deleted = $this->wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );
        wp_cache_flush_group( 'club-roles' );
        return false !== $deleted;
    }

    /**
     * Deletes all club roles associated with a club.
     *
     * @param int $club_id Club ID.
     *
     * @return bool True if the delete operation succeeded, false otherwise.
     */
    public function delete_for_club( int $club_id ): bool {
        $deleted = $this->wpdb->delete(
            $this->table_name,
            array( 'club_id' => $club_id ),
            array( '%d' )
        );
        wp_cache_flush_group( 'club-roles' );
        return false !== $deleted;
    }

    /**
     * Retrieves roles associated with a specific club.
     *
     * @param int $club_id Club ID.
     *
     * @return array Array of roles associated with the club.
     */
    public function get_roles_for_club( int $club_id ): array {
        return $this->search( array( 'club' => $club_id ) );
    }

    /**
     * Finds club IDs associated with a player and club role.
     *
     * @param int      $user_id Player user ID.
     * @param string   $role_name Club role name.
     * @param int|null $club_id Optional club ID to restrict the search.
     *
     * @return array List of matching club IDs.
     */
    public function find_clubs_by_player_and_role( int $user_id, string $role_name, ?int $club_id ): array {
        $role_id = Util_Lookup::get_club_role_ref( $role_name );
        if ( $role_id ) {
            $sql    = "SELECT club_id FROM $this->table_name WHERE user_id = %d AND role_id= %d";
            $params = array( $user_id, $role_id );
            if ( $club_id ) {
                $sql     .= ' AND club_id = %d';
                $params[] = $club_id;
            }
            $query = $this->wpdb->prepare(
                $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $params,
            );
            return array_map( 'intval', $this->wpdb->get_col( $query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        } else {
            return array();
        }
    }
}
