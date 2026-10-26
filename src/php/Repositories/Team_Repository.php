<?php
declare( strict_types=1 );
/**
 * Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use QM_DB;
use Racketmanager\Domain\DTO\Team\Team_Competition_DTO;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Team repository
 */
class Team_Repository implements Team_Repository_Interface {
    private ?object $wpdb = null;
    private ?string $table_name = null;
    private ?string $team_players_table = null;
    private ?Player_Repository_Interface $player_repository;

    /**
     * Create a new Team_Repository instance
     *
     * @param object|null $wpdb
     * @param Player_Repository_Interface|null $player_repository
     */
    public function __construct( ?object $wpdb = null, ?Player_Repository_Interface $player_repository = null ) {
        if ( null === $wpdb ) {
            global $wpdb;
        }
        $this->wpdb               = $wpdb;
        if ( $this->wpdb ) {
            $prefix                   = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
            $this->table_name         = $prefix . 'racketmanager_teams';
            $this->team_players_table = $prefix . 'racketmanager_team_players';
        }
        $this->player_repository  = $player_repository;
    }

    /**
     * Find a team by ID
     *
     * @param int|string|null $id
     *
     * @return Team|null
     */
    public function find_by_id( int|string|null $id ): ?Team {
        if ( is_numeric( $id ) ) {
            $search = $this->wpdb->prepare( '`id` = %d', $id );
        } else {
            $search = $this->wpdb->prepare( '`title` = %s', $id );
        }
        if ( ! $id ) {
            return null;
        }
        $team = wp_cache_get( $id, 'teams' );

        if ( ! $team ) {
            if ( -1 === $id ) {
                $team = (object) array(
                    'id'    => $id,
                    'title' => __( 'Bye', 'racketmanager' ),
                );
            } else {
                $team = $this->wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `club_id`, `type`, `team_type` FROM $this->table_name WHERE " . $search . ' LIMIT 1' );
            }
            if ( ! $team ) {
                return null;
            }
            $team = Team_Factory::from_object( $team );
            if ( $team->id && ( 'P' === $team->team_type || ! empty( $team->roster ) ) ) {
                $players = $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "SELECT `player_id` FROM $this->team_players_table WHERE `team_id` = %d",
                        $team->id
                    )
                );
                if ( ! empty( $players ) ) {
                    $player_objects = [];
                    $player_ids     = [];
                    $i              = 1;
                    foreach ( $players as $team_player ) {
                        $player = $this->player_repository ? $this->player_repository->find( (int) $team_player->player_id ) : null;
                        if ( $player ) {
                            $player_objects[] = $player;
                        }
                        $player_ids[ $i ] = (int) $team_player->player_id;
                        ++$i;
                    }
                    $team->players    = $player_objects;
                    $team->player_ids = $player_ids;
                }
            }
            wp_cache_set( $team->id, $team, 'teams' );
        }

        return $team;
    }

    /**
     * Find a team ID by title
     *
     * @param string $title
     *
     * @return int
     */
    public function find_id_by_title( string $title ): int {
        $title = trim( $title );
        if ( '' === $title ) {
            return 0;
        }

        $cached_id = wp_cache_get( 'team_id_' . md5( $title ), 'teams' );
        if ( false !== $cached_id && (int) $cached_id > 0 ) {
            return (int) $cached_id;
        }

        $id = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT `id` FROM $this->table_name WHERE `title` = %s LIMIT 1",
                $title
            )
        );

        if ( $id > 0 ) {
            wp_cache_set( 'team_id_' . md5( $title ), $id, 'teams' );
        }

        return $id;
    }

    /**
     * Read the persisted title of a team with a lightweight scalar query (no hydration).
     *
     * @param int $id
     *
     * @return string|null
     */
    private function find_title_by_id( int $id ): ?string {
        $title = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT `title` FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            )
        );

        return ( null === $title || false === $title ) ? null : (string) $title;
    }

    /**
     * Invalidate title cache for a team.
     *
     * @param string|null $title
     * @return void
     */
    private function invalidate_title_cache( ?string $title ): void {
        if ( null !== $title ) {
            $title = trim( $title );
            if ( '' !== $title ) {
                wp_cache_delete( 'team_id_' . md5( $title ), 'teams' );
            }
        }
    }

    /**
     * Find all teams for a club
     *
     * @param int $club_id
     * @param string|null $type
     *
     * @return array
     */
    public function find_by_club( int $club_id, ?string $type = null ): array {
        $query    = "SELECT * FROM $this->table_name WHERE club_id = %d";
        $params[] = $club_id;
        if ( $type ) {
            $query    .= " AND type = '%s'";
            $params[] = $type;
        }
        $query   .= " AND team_type IS NULL ORDER BY title";
        $results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) );

        return array_map( fn ( $row ) => Team_Factory::from_object( $row ), $results );
    }

    /**
     * Find all teams that are used for players
     *
     * @param string $type
     *
     * @return array
     */
    public function find_for_players( string $type ): array {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT *
                        FROM $this->table_name
                        WHERE team_type = 'P'
                          AND type = %s
                        ORDER BY title",
                $type
            )
        );

        return array_map( fn ( $row ) => Team_Factory::from_object( $row ), $results );
    }

    /**
     * Save a team.
     *
     * @param object $entity
     *
     * @return int|false
     */
    public function save( object $entity ): bool|int {
        /** @var Team $entity */
        $data       = array(
            'title'     => $entity->get_name(),
            'stadium'   => $entity->get_stadium(),
            'club_id'   => $entity->get_club_id(),
            'type'      => $entity->get_type(),
            'team_type' => $entity->get_team_type(),
        );
        $data_types = array(
            '%s', // Format for name (string)
            '%s', // Format for stadium (string)
            '%d', // Format for club_id (int)
            '%s', // Format for type (string)
            '%s', // Format for team_type (string)
        );

        if ( $entity->get_id() === null ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $data_types );
            if ( $inserted ) {
                $insert_id = $this->wpdb->insert_id;
                $entity->set_id( $insert_id );
                wp_cache_set( $insert_id, $entity, 'teams' );
                $this->invalidate_title_cache( $entity->get_name() );
                return $insert_id;
            }
            return false;
        } else {
            $old_title = $this->find_title_by_id( (int) $entity->get_id() );

            // UPDATE: Use wpdb->update with the prepare logic built-in
            $updated = $this->wpdb->update( $this->table_name, $data, array( 'id' => $entity->get_id() ),            // Where clause
                $data_types,                                // Data format
                array( '%d' )                                 // Where format
            );
            if ( false === $updated ) {
                return false;
            }
            wp_cache_set( $entity->get_id(), $entity, 'teams' );
            $this->invalidate_title_cache( $old_title );
            $this->invalidate_title_cache( $entity->get_name() );

            return true;
        }
    }

    /**
     * Checks if a club has teams.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function has_teams( int $club_id ): bool {
        $count = $this->wpdb->query( $this->wpdb->prepare( "SELECT COUNT(*) FROM $this->table_name WHERE `club_id` = %d", $club_id ) );

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
        $query       = $this->wpdb->prepare( "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED))
             FROM $this->table_name
             WHERE `title` LIKE %s", $prefix_like );

        $max_sequence = (int) $this->wpdb->get_var( $query );

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

        $count = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count(*) FROM $this->table_name t, $tables_table t1 WHERE t.`club_id` = %d AND t.`team_type` IS NULL AND t.`id` = t1.`team_id` AND t1.`captain` = %d", $club_id, $player ) );

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
    public function find_teams_by_competition_with_details( int $competition_id, int $season, int $min_fixtures = 1 ): array {
        $clubs_table          = $this->wpdb->prefix . 'racketmanager_clubs';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->table_name;
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';

        $query   = $this->wpdb->prepare( " SELECT t.id as team_id, t.title as team_name, c.id as club_id, c.shortcode as club_shortcode, l.title as league_name, (SELECT COUNT(DISTINCT rp.player_id) FROM `$rubber_players_table` rp INNER JOIN `$rubbers_table` r ON rp.rubber_id = r.id INNER JOIN `$matches_table` f ON r.match_id = f.id INNER JOIN `$leagues_table` l ON f.league_id = l.id INNER JOIN `$events_table` e_sub ON l.event_id = e_sub.id WHERE e_sub.competition_id = %d AND f.season = %d AND ( (rp.player_team = 'home' AND f.home_team = t.id) OR (rp.player_team = 'away' AND f.away_team = t.id) ) HAVING COUNT(rp.id) >= %d ) as num_players FROM  `$teams_table` t INNER JOIN `$clubs_table` c ON t.club_id = c.id INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id INNER JOIN `$leagues_table` l ON lte.league_id = l.id INNER JOIN `$events_table` e ON l.event_id = e.id WHERE e.competition_id = %d AND lte.season = %d
            ORDER BY c.shortcode, t.title", $competition_id, $season, $min_fixtures, $competition_id, $season );
        $results = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Team_Competition_DTO( $row ), $results );
    }

    /**
     * Retrieves full captain contact details and match settings.
     */
    public function find_team_settings_for_event( int $teamId, int $eventId ): ?Team_Fixture_Settings_DTO {
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $users_table        = $this->wpdb->base_prefix . 'users';
        $usermeta_table     = $this->wpdb->base_prefix . 'usermeta';

        $phone_meta_key = 'contactno';

        $query = $this->wpdb->prepare( "
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
    ", $phone_meta_key, $teamId, $eventId );

        $row = $this->wpdb->get_row( $query );

        return $row ? new Team_Fixture_Settings_DTO( $row ) : null;
    }

    public function find_team_by_players( array $player_ids ): ?int {
        // Ensure IDs are unique and sorted for consistent matching
        sort( $player_ids );
        $count        = count( $player_ids );
        $placeholders = implode( ',', array_fill( 0, $count, '%d' ) );

        $query          = "
        SELECT team_id
        FROM `$this->team_players_table`
        GROUP BY team_id
        HAVING COUNT(DISTINCT player_id) = %d
           AND COUNT(CASE WHEN player_id IN ($placeholders) THEN 1 END) = %d
           AND COUNT(player_id) = %d
        LIMIT 1";
        $args           = array_merge( [ $count ], $player_ids, [ $count, $count ] );
        $prepared_query = $this->wpdb->prepare( $query, ...$args );
        $id             = $this->wpdb->get_var( $prepared_query );

        return $id ? (int) $id : null;
    }

    /**
     * Saves a team using the existing upsert and handles player assignments.
     */
    public function save_team_players( int $team_id, array $player_ids ): bool {
        $success = true;
        foreach ( $player_ids as $player_id ) {
            $inserted = $this->wpdb->insert( $this->team_players_table, [
                    'team_id'   => $team_id,
                    'player_id' => absint( $player_id )
                ], [ '%d', '%d' ] );
            if ( $inserted === false ) {
                $success = false;
            }
        }
        wp_cache_delete( $team_id, 'teams' );
        return $success;
    }

    /**
     * Update team settings for leagues belonging to an event and season.
     *
     * @param int $team_id
     * @param int $event_id
     * @param string $season
     * @param string $captain
     * @param string $match_day
     * @param string|null $match_time
     * @return bool
     */
    public function update_event_settings( int $team_id, int $event_id, string $season, string $captain, string $match_day, ?string $match_time ): bool {
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $query = $this->wpdb->prepare(
            "UPDATE `$league_teams_table`
             SET `captain` = %d, `match_day` = %s, `match_time` = %s
             WHERE `team_id` = %d
               AND `season` = %s
               AND `league_id` IN (
                   SELECT `id` FROM `$leagues_table` WHERE `event_id` = %d
               )",
            (int) $captain,
            $match_day,
            $match_time ?? '',
            $team_id,
            $season,
            $event_id
        );

        $result = $this->wpdb->query( $query );
        return false !== $result;
    }

    /**
     * Delete a team by its ID.
     *
     * @param int $id
     *
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        $old_title   = $this->find_title_by_id( $id );
        $del_players = $this->wpdb->delete( $this->team_players_table, array( 'team_id' => $id ), array( '%d' ) );
        if ( false === $del_players ) {
            return false;
        }
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $del_league_teams   = $this->wpdb->delete( $league_teams_table, array( 'team_id' => $id ), array( '%d' ) );
        if ( false === $del_league_teams ) {
            return false;
        }
        $del_team = $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
        if ( false === $del_team ) {
            return false;
        }
        wp_cache_delete( $id, 'teams' );
        $this->invalidate_title_cache( $old_title );
        return true;
    }

    /**
     * Get teams from the database matching criteria.
     *
     * @param array $args
     * @return array|int
     */
    public function find_all( array $args = array() ): array|int {
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'orderby' => array(
                'id' => 'ASC',
            ),
            'count'   => false,
            'player'  => false,
            'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $orderby  = $args['orderby'];
        $count    = $args['count'];
        $player   = $args['player'];
        $partner  = $args['partner'];

        $search_terms = array();
        if ( $player ) {
            $search_terms[] = $this->wpdb->prepare( "`id` IN (SELECT `team_id` FROM $this->team_players_table WHERE `player_id` = %d )", $player );
        }
        if ( $partner ) {
            $search_terms[] = $this->wpdb->prepare( "`id` IN (SELECT `team_id` FROM $this->team_players_table WHERE `player_id` = %d )", $partner );
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`id`))';
        } else {
            $sql = 'SELECT `id`';
        }
        $sql .= " FROM $this->table_name " . $search;

        if ( $count ) {
            $teams = wp_cache_get( md5( $sql ), 'teams' );
            if ( false === $teams || null === $teams ) {
                $teams = $this->wpdb->get_var( $sql );
                wp_cache_set( md5( $sql ), $teams, 'teams' );
            }
            return (int) $teams;
        }
        $sql .= Util::order_by_string( $orderby );
        $sql  = $this->wpdb->prepare(
            $sql . ' LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );
        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }
        if ( ! is_array( $teams ) ) {
            return array();
        }
        $hydrated_teams = array();
        foreach ( $teams as $row ) {
            $team = $this->find_by_id( (int) $row->id );
            if ( $team ) {
                $hydrated_teams[] = $team;
            }
        }
        return $hydrated_teams;
    }
}
