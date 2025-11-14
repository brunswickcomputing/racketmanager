<?php

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club;
use Racketmanager\Util\Util;
use wpdb;
use function Racketmanager\get_team;

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
            // Instantiate and return a new Club object with the fetched data
            return new Club( $club_data );
        }

        return null; // Club not found
    }

    /**
     * Get teams for a club
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'club'    => false,
            'count'   => false,
            'players' => false,
            'type'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $club     = $args['club'];
        $count    = $args['count'];
        $players  = $args['players'];
        $type     = $args['type'];

        $search_terms = array();
        $sql    = " FROM $wpdb->racketmanager_teams WHERE `club_id` = '%d'";
        $search_terms[] = $club;
        if ( ! $players ) {
            $sql .= " AND (`team_type` is null OR `team_type` != 'P')";
        } else {
            $sql .= " AND `team_type` = 'P'";
        }
        if ( $type ) {
            if ( 'OS' === $type ) {
                $sql   .= " AND `type` like '%%%s%%'";
                $search_terms[] = 'S';
            } else {
                $sql   .= " AND `type` = '%s'";
                $search_terms[] = $type;
            }
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*) ' . $sql;
            return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    $search_terms
                )
            );
        }
        $sql  = 'SELECT `id` ' . $sql . ' ORDER BY `title`';
        $sql  = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_terms
        );

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

        foreach ( $teams as $i => $team ) {
            $team        = get_team( $team->id );
            $teams[ $i ] = $team;
        }

        return $teams;
    }

    /**
     * Retrieves existing clubs from the database by parameters
     * replaces the $racketmanager->get_clubs function.
     *
     * @param array $args search arguments.
     *
     * @return array array of clubs.
     */
    public function find_all( array $args = array() ): array {
        $defaults = array(
            'type'    => false,
            'orderby' => 'asc',
        );
        $args     = array_merge( $defaults, $args );
        $type     = $args['type'];
        $orderby  = $args['orderby'];

        $search_terms = array();
        if ( $type && 'all' !== $type ) {
            if ( 'current' === $type ) {
                $search_terms[] = "`type` != 'past'";
            } else {
                $search_terms[] = $this->wpdb->prepare( '`type` = %s', $type );
            }
        }
        $search = Util::search_string( $search_terms, true );
        switch ( $orderby ) {
            case 'asc':
                $order = '`name` ASC';
                break;
            case 'desc':
                $order = '`name` DESC';
                break;
            case 'rand':
                $order = 'RAND()';
                break;
            case 'menu_order':
                $order = '`id` ASC';
                break;
            default:
                break;
        }
        $order  = empty( $order ) ? null : 'ORDER BY ' . $order;
        $sql    = "SELECT `id`, `name`, `website`, `type`, `address`, `contactno`, `founded`, `facilities`, `shortcode` FROM $this->table_name $search $order";
        $clubs  = wp_cache_get( md5( $sql ), 'clubs' );
        if ( ! $clubs ) {
            $results = $this->wpdb->get_results( $sql );
            $clubs   = array_map(
                function( $row ) {
                    return new Club( $row );
                    },
                $results
            );
            wp_cache_set( md5( $sql ), $clubs, 'clubs' );
        }
        return $clubs;
    }

    /**
     * Delete club from the database.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function delete( int $club_id ): bool {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $this->table_name WHERE `id` = %d",
                $club_id
            )
        );
        wp_cache_flush_group( 'clubs' );
        return $result !== false;
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
