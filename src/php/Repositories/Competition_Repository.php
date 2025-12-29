<?php
/**
 * Competition_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Competition;
use wpdb;

/**
 * Class to implement the Competition repository
 */
class Competition_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_competitions';
    }

    public function save( Competition $competition ): int|bool {
        $data = array(
            'name'           => $competition->get_name(),
            // Store settings as JSON
            'settings'       => json_encode( $competition->get_settings() ),
            // Store seasons as JSON in DB
            'seasons'        => json_encode( $competition->get_seasons() ),
            'type'           => $competition->get_type(),
            'age_group'      => $competition->get_age_group(),
        );
        $data_format = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        );
        if ( empty( $competition->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $competition->set_id( $this->wpdb->insert_id );
            wp_cache_set( $competition->get_id(), $competition, 'competitions' );
            return $result !== false;
        } else {
            wp_cache_set( $competition->get_id(), $competition, 'competitions' );
            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $competition->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( int|string|null $competition_id ): ?Competition {
        if ( empty( $competition_id ) ) {
            return null;
        }
        if ( is_numeric( $competition_id ) ) {
            $competition_id = (int) $competition_id;
            $search = '`id` = %d';
        } else {
            $search = '`name` = %s';
        }
        $competition = wp_cache_get( $competition_id, 'competitions' );

        if ( ! $competition ) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE $search LIMIT 1",
                    $competition_id
                )
            );

            if ( ! $row ) {
                return null;
            }
            $competition = Competition::from_database( $row );
            wp_cache_set( $competition->get_id(), $competition, 'competitions' );
        }

        return $competition;
    }

    public function find_all(): array {
        $competitions = wp_cache_get( 'competitions', 'competitions' );
        if ( ! $competitions ) {
            $competitions = $this->wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY `name`" );
            $competitions = array_map( [ Competition::class, 'from_database' ], $competitions );
            wp_cache_set( 'competitions', $competitions, 'competitions' );
        }
        return $competitions;
    }

    public function find_by( array $criteria ): array {
        $sql  = "SELECT * FROM $this->table_name";
        if ( ! empty( $criteria ) ) {
            $clauses = array();
            foreach ( $criteria as $key => $value ) {
                // Use prepare statement for values to avoid SQL injection
                $clauses[] = $this->wpdb->prepare( "`$key` = %s", $value );
            }
            $sql .= ' WHERE ' . implode( ' AND ', $clauses );
        }
        $sql .= ' ORDER BY `name`';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $this->wpdb->get_results( $sql );
        return array_map( [ Competition::class, 'from_database' ], $rows );
    }

    public function find_competitions_with_summary( $age_group = null, $type = null ): array {
        $events_table = $this->wpdb->prefix . 'racketmanager_events';
        $params = [];
        $conditions = []; // Use an array instead of a string

        if ( $age_group ) {
            $conditions[] = 'c.age_group = %s';
            $params[]     = $age_group;
        }

        if ( $type ) {
            $conditions[] = 'c.type = %s';
            $params[]     = $type;
        }

        // Build the WHERE clause only if conditions exist
        $where_clause = ! empty( $conditions ) ? ' WHERE ' . implode( ' AND ', $conditions ) : '';

        $query = "SELECT c.id, c.name, c.age_group, c.type, JSON_LENGTH(c.seasons) as season_count, COUNT(DISTINCT e.id) as event_count FROM $this->table_name c LEFT JOIN $events_table e ON c.id = e.competition_id $where_clause GROUP BY c.id ORDER BY c.age_group, c.type, c.name";

        if ( $where_clause ) {
            $query = $this->wpdb->prepare( $query, $params );
        }
        return $this->wpdb->get_results( $query );
    }

    public function delete( int $competition_id ): void {
        $this->wpdb->delete( $this->table_name, array( 'id' => $competition_id ), array( '%d' ) );
    }
}
