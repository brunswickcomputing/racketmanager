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
use Racketmanager\Domain\Competition_Overview_DTO;
use wpdb;

/**
 * Class to implement the Competition repository
 */
class Competition_Repository {
    private wpdb $wpdb;
    private string $table_name;
    private string $events_table;
    private string $leagues_table;
    private string $league_teams_table;
    private string $teams_table;
    private string $rubber_players_table;
    private string $rubbers_table;
    private string $fixtures_table;

    /**
     * Create a new Competition_Repository instance
     *
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb                 = $wpdb;
        $this->table_name           = $this->wpdb->prefix . 'racketmanager_competitions';
        $this->events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $this->fixtures_table       = $this->wpdb->prefix . 'racketmanager_matches';
        $this->leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $this->league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $this->rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $this->rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $this->teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
    }

    /**
     * Save a competition.
     *
     * @param Competition $competition
     *
     * @return int|bool
     */
    public function save( Competition $competition ): int|bool {
        $data = array(
            'name'      => $competition->get_name(),
            'settings'  => json_encode( $competition->get_settings() ), // Store settings as JSON
            'seasons'   => json_encode( $competition->get_seasons() ), // Store seasons as JSON in DB
            'type'      => $competition->get_type(),
            'age_group' => $competition->get_age_group(),
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

    /**
     * Find a competition by its ID.
     *
     * @param int|string|null $competition_id
     *
     * @return Competition|null
     */
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

    /**
     * Find all competitions.
     *
     * @return array
     */
    public function find_all(): array {
        $competitions = wp_cache_get( 'competitions', 'competitions' );
        if ( ! $competitions ) {
            $competitions = $this->wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY `name`" );
            $competitions = array_map( [ Competition::class, 'from_database' ], $competitions );
            wp_cache_set( 'competitions', $competitions, 'competitions' );
        }
        return $competitions;
    }

    /**
     * Find competitions by criteria.
     *
     * @param array $criteria
     *
     * @return array
     */
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

    /**
     * Find competitions with summary data.
     *
     * @param $age_group
     * @param $type
     *
     * @return array
     */
    public function find_competitions_with_summary( $age_group = null, $type = null ): array {
        $params     = [];
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

    /**
     * Delete a competition from the database.
     *
     * @param int $competition_id
     *
     * @return void
     */
    public function delete( int $competition_id ): void {
        $this->wpdb->delete( $this->table_name, array( 'id' => $competition_id ), array( '%d' ) );
    }

    /**
     * Retrieves an overview of all competitions with aggregated counts for events, teams, and active players.
     *
     * @param int $competition_id
     * @param int $season
     * @param int|null $min_fixtures
     *
     * @return Competition_Overview_DTO|null
     */
    public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = 1 ): ?Competition_Overview_DTO {
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';

        $player_activity_subquery = $this->wpdb->prepare(
            "SELECT l.event_id, rp.player_id FROM $this->rubber_players_table rp INNER JOIN $this->rubbers_table r ON rp.rubber_id = r.id INNER JOIN $this->fixtures_table f ON r.match_id = f.id AND f.season = %d INNER JOIN $this->leagues_table l ON f.league_id = l.id GROUP BY l.event_id, rp.player_id HAVING COUNT(rp.id) >= %d",
            $season,
            $min_fixtures
        );

        $query = $this->wpdb->prepare(
            "SELECT c.id as id, c.name as name, c.settings as settings, COUNT(DISTINCT e.id) as num_events, COUNT(DISTINCT lte.team_id) as num_teams, COUNT(DISTINCT t.club_id) as num_clubs, COUNT(DISTINCT active_players.player_id) as num_players FROM `$this->table_name` c LEFT JOIN `$events_table` e ON c.`id` = e.`competition_id` LEFT JOIN `$leagues_table` l ON e.id = l.event_id LEFT JOIN `$league_teams_table` lte ON l.id = lte.league_id AND lte.season = %d LEFT JOIN `$teams_table` t ON lte.team_id = t.id LEFT JOIN ($player_activity_subquery) AS active_players ON e.id = active_players.event_id WHERE c.id = %d GROUP BY c.id, c.name, c.settings ORDER BY c.name",
            $season,
            $competition_id
        );

        $row = $this->wpdb->get_row( $query );

        return $row ? new Competition_Overview_DTO( $row ) : null;
    }

}
