<?php

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club;
use Racketmanager\Util\Util;
use wpdb;

class Club_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_clubs';
    }

    /**
     * Inserts a new club into the database.
     * The save action is explicit, not in the Club constructor.
     * @param Club $club The club object to save.
     */
    public function save( Club $club ): void {
        //`id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode`
        if ( $club->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'       => $club->get_name(),
                    'website'    => $club->get_website(),
                    'type'       => $club->get_type(),
                    'address'    => $club->get_address(),
                    'contactno'  => $club->get_contact_no(),
                    'founded'    => $club->get_founded(),
                    'facilities' => $club->get_facilities(),
                    'shortcode'  => $club->get_shortcode(),
                ),
                array(
                    '%s', // Format for name (string)
                    '%s', // Format for website (string)
                    '%s', // Format for type (string)
                    '%s', // Format for address (string)
                    '%s', // Format for contactno (string)
                    '%s', // Format for founded (string)
                    '%s', // Format for facilities (string)
                    '%s', // Format for shortcode (string)
                )
            );
            $club->set_id( $this->wpdb->insert_id );
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in
            $this->wpdb->update(
                $this->table_name,
                array( 'name'       => $club->get_name(),
                       'website'    => $club->get_website(),
                       'type'       => $club->get_type(),
                       'address'    => $club->get_address(),
                       'contactno'  => $club->get_contact_no(),
                       'founded'    => $club->get_founded(),
                       'facilities' => $club->get_facilities(),
                       'shortcode'  => $club->get_shortcode()
                    ), // Data to update
                array('id' => $club->get_id() ),            // Where clause
                array( '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ),                                          // Data format
                array('%d')                                 // Where format
            );
        }
        wp_cache_flush_group( 'clubs' );
    }

    /**
     * Retrieves an existing club from the database by ID.
     *
     * @param int|string $id The user ID.
     * @param string $search_term
     *
     * @return Club|null The user object or null if not found.
     */
    public function find( int|string $id, string $search_term = 'id' ): ?Club {
        $search = match ($search_term) {
            'name'      => $this->wpdb->prepare(
                '`name` = %s',
                $id
            ),
            'shortcode' => $this->wpdb->prepare(
                '`shortcode` = %s',
                $id
            ),
            default     => $this->wpdb->prepare(
                '`id` = %d',
                $id
            ),
        };
        $club_data = wp_cache_get( $id, 'clubs' );
        if ( ! $club_data ) {

            // Prepare the query safely using prepare() to prevent SQL injection
            $query = "SELECT `id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode` FROM $this->table_name WHERE " . $search . " LIMIT 1";

            $club_data = $this->wpdb->get_row( $query ); // Get a single row as an object
            if ( $club_data ) {
                wp_cache_set( $id, $club_data, 'clubs' );
            }
        }

        if ( $club_data ) {
            // Instantiate and return a new User object with the fetched data
            return new Club( $club_data );
        }

        return null; // Club not found
    }

    /**
     * Retrieves existing club roles from the database by parameters.
     *
     * @param array $args search arguments.
     *
     * @return Club_Role|null The user object or null if not found.
     */
    /**
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
**/
    /**
     * Delete club from the database.
     *
     * @param array $args
     *
     * @return bool
     */
    public function delete( array $args = array() ): bool {
        $defaults   = array(
            'club'    => false,
        );
        $args    = array_merge( $defaults, $args );
        $club    = $args['club'];

        $search_terms = array();
        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( '`id` = %d', intval( $club ) );
        }
        if ( ! empty( $search_terms ) ) {
            $search = Util::search_string( $search_terms, true );
            $sql    = "DELETE FROM $this->table_name " . $search;
            $result = $this->wpdb->query(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call OK.
            wp_cache_flush_group( 'clubs' );
            return $result !== false && $result > 0;
        } else {
            return false;
        }
    }
    public function has_teams( int $club_id ): bool {
        $count = $this->wpdb->query(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}racketmanager_teams WHERE `club_id` = %d",
                $club_id
            )
        );
        return $count > 0;
    }
}
