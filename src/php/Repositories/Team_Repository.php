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
use Racketmanager\Domain\DTO\Team_Competition_DTO;
use Racketmanager\Domain\DTO\Team_Fixture_Settings_DTO;
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

    /**
     * Gets all teams for a specific competition with associated details and active player counts.
     *
     * @param int $competition_id
     * @param int $season
     * @param int $min_fixtures Minimum fixtures played to be considered 'active'.
     *
     * @return Team_Competition_DTO[]
     */
    public function find_teams_by_competition_with_details( int $competition_id, int $season, int $min_fixtures = 1): array {
        $clubs_table          = $this->wpdb->prefix . 'racketmanager_clubs';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->table_name;
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';

        $query = $this->wpdb->prepare(" SELECT t.id as team_id, t.title as team_name, c.id as club_id, c.shortcode as club_shortcode, l.title as league_name, (SELECT COUNT(DISTINCT rp.player_id) FROM `$rubber_players_table` rp INNER JOIN `$rubbers_table` r ON rp.rubber_id = r.id INNER JOIN `$matches_table` f ON r.match_id = f.id INNER JOIN `$leagues_table` l ON f.league_id = l.id INNER JOIN `$events_table` e_sub ON l.event_id = e_sub.id WHERE e_sub.competition_id = %d AND f.season = %d AND ( (rp.player_team = 'home' AND f.home_team = t.id) OR (rp.player_team = 'away' AND f.away_team = t.id) ) HAVING COUNT(rp.id) >= %d ) as num_players FROM  `$teams_table` t INNER JOIN `$clubs_table` c ON t.club_id = c.id INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id INNER JOIN `$leagues_table` l ON lte.league_id = l.id INNER JOIN `$events_table` e ON l.event_id = e.id WHERE e.competition_id = %d AND lte.season = %d
            ORDER BY c.shortcode, t.title",
            $competition_id,
            $season,
            $min_fixtures,
            $competition_id,
            $season
        );
        $results = $this->wpdb->get_results($query);

        return array_map(fn($row) => new Team_Competition_DTO($row), $results);
    }

    /**
     * Retrieves full captain contact details and match settings.
     */
    public function find_team_settings_for_event( int $teamId, int $eventId): ?Team_Fixture_Settings_DTO {
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $users_table = $this->wpdb->base_prefix . 'users';
        $usermeta_table = $this->wpdb->base_prefix . 'usermeta';

        $phone_meta_key = 'contactno';

        $query = $this->wpdb->prepare("
        SELECT 
            lte.captain as captain_id,
            u.display_name as captain_name,
            u.user_email as captain_email,
            m.meta_value as captain_contact_no,
            lte.match_day as match_day,
            lte.match_time as match_time
        FROM `$league_teams_table` lte
            INNER JOIN `$leagues_table` l ON l.id = lte.league_id
            LEFT JOIN `$users_table` u ON lte.captain = u.ID
            LEFT JOIN `$usermeta_table` m ON u.ID = m.user_id AND m.meta_key = %s
        WHERE lte.team_id = %d AND l.event_id = %d
        ORDER BY lte.season DESC
        LIMIT 1
    ", $phone_meta_key, $teamId, $eventId);

        $row = $this->wpdb->get_row( $query );
        return $row ? new Team_Fixture_Settings_DTO( $row ) : null;
    }
}
