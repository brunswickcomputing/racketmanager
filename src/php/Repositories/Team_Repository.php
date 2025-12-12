<?php
/**
 * Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use QM_DB;
use Racketmanager\Domain\Team;
use wpdb;

/**
 * Class to implement the Team repository
 */
class Team_Repository {
    private QM_DB|wpdb $wpdb;
    private string $table_name;

    /**
     * Create a new Team_Repository instance
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_teams';
    }

    /**
     * Find a team by ID
     *
     * @param int|string|null $team_id
     *
     * @return Team|null
     */
    public function find_by_id( int|string|null $team_id ): ?Team {
        if ( is_numeric( $team_id ) ) {
            $search = $this->wpdb->prepare(
                '`id` = %d',
                $team_id
            );
        } else {
            $search = $this->wpdb->prepare(
                '`title` = %s',
                $team_id
            );
        }
        if ( ! $team_id ) {
            return null;
        }
        $team = wp_cache_get( $team_id, 'teams' );

        if ( ! $team ) {
            if ( -1 === $team_id ) {
                $team = (object) array(
                    'id'     => $team_id,
                    'title'  => __( 'Bye', 'racketmanager' ),
                );
            } else {
                $team = $this->wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `club_id`, `type`, `team_type` FROM $this->table_name WHERE " . $search . ' LIMIT 1',
                );
            }
            if ( ! $team ) {
                return null;
            }
            $team = new Team( $team );
            wp_cache_set( $team->id, $team, 'teams' );
        }
        return $team;
    }

    /**
     * Find all teams for a club
     *
     * @param int $club_id
     * @param string|null $type
     *
     * @return array
     */
    public function find_by_club( int $club_id, ?string $type ): array {
        $query    = "SELECT * FROM $this->table_name WHERE club_id = %d";
        $params[] = $club_id;
        if ( $type ) {
            $query   .= " AND type = '%s'";
            $params[] = $type;
        }
        $query   .= " AND team_type IS NULL ORDER BY title";
        $results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) );
        return array_map( function( $row ) { return new Team( $row ); }, $results );
    }

    /**
     * Find all teams that are used for players
     *
     * @return array
     */
    public function find_for_players(): array {
        $results = $this->wpdb->get_results( "SELECT * FROM $this->table_name WHERE team_type = 'P' ORDER BY title" );
        return array_map( function( $row ) { return new Team( $row ); }, $results );
    }

    /**
     * Save a team.
     *
     * @param Team $team
     *
     * @return void
     */
    public function save( Team $team ): void {
        if ( $team->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'title'       => $team->get_name(),
                    'stadium'     => $team->get_stadium(),
                    'club_id'     => $team->get_club_id(),
                    'type'        => $team->get_type(),
                    'team_type'   => $team->get_team_type(),
                ),
                array(
                    '%s', // Format for name (string)
                    '%s', // Format for stadium (string)
                    '%d', // Format for club_id (int)
                    '%s', // Format for type (string)
                    '%s', // Format for team_type (string)
                )
            );
            $team->set_id( $this->wpdb->insert_id );
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in
            $this->wpdb->update(
                $this->table_name,
                array(
                    'title'       => $team->get_name(),
                    'stadium'     => $team->get_stadium(),
                    'club_id'     => $team->get_club_id(),
                    'type'        => $team->get_type(),
                    'team_type'   => $team->get_team_type(),
                ), // Data to update
                array('id' => $team->get_id() ),            // Where clause
                array( '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ),                                          // Data format
                array('%d')                                 // Where format
            );
        }
        wp_cache_flush_group( 'teams' );
    }
    /**
     * Checks if a club has teams.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function has_teams( int $club_id ): bool {
        $count = $this->wpdb->query(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE `club_id` = %d",
                $club_id
            )
        );
        return $count > 0;
    }

    /**
     * Finds the highest existing sequence number for teams belonging to a club shortcode prefix.
     * Assumes team names follow the format 'SHORTCODE 1', 'SHORTCODE 2', etc.
     *
     * @param string $club_shortcode
     * @param string $type
     *
     * @return int The next available sequence number (1 if no teams exist yet).
     */
    public function find_next_sequence_number( string $club_shortcode, string $type ): int {
        // We look for names starting with the shortcode followed by a space and digits
        $prefix_like = $this->wpdb->esc_like( $club_shortcode ) . ' ' . $this->wpdb->esc_like( $type ) . ' %';
        $query = $this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED))
             FROM $this->table_name
             WHERE `title` LIKE %s",
            $prefix_like
        );

        $max_sequence = (int) $this->wpdb->get_var($query);
        // If no teams found, MAX returns NULL/0, so the next number is 1. Otherwise, increment the max.
        return $max_sequence + 1;
    }

    /**
     * Checks if a player is a captain of a team
     *
     * @param int $club_id
     * @param int $player
     *
     * @return bool
     */
    public function find_captain( int $club_id, int $player ): bool {
        $tables_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
               "SELECT count(*) FROM $this->table_name t, $tables_table t1 WHERE t.`club_id` = %d AND t.`team_type` IS NULL AND t.`id` = t1.`team_id` AND t1.`captain` = %d",
                $club_id,
                $player
            )
        );
        return $count > 0;
    }

}
