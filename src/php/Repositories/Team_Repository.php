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

use Closure;
use Racketmanager\Domain\DTO\Team\Team_Competition_DTO;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Domain\Team\Team_Placeholder_Ref;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Repositories\Concerns\Wpdb_Transactions;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;
use Throwable;

/**
 * Class to implement the Team repository
 */
class Team_Repository implements Team_Repository_Interface {
    use Wpdb_Transactions;

    private ?object $wpdb;
    private ?string $table_name         = null;
    private ?string $team_players_table = null;
    private ?Player_Repository_Interface $player_repository;
    private Fixture_Repository_Interface|Closure|null $fixture_repository;
    private ?Fixture_Repository_Interface $resolved_fixture_repository = null;

    /**
     * @var array<int|string, bool>
     */
    private array $resolving_teams = array();

    /**
     * Initialises the team repository with optional database and related repository dependencies.
     *
     * @param object|null                               $wpdb WordPress database object. Falls back to the global database object when null.
     * @param Player_Repository_Interface|null          $player_repository Optional player repository dependency.
     * @param Fixture_Repository_Interface|Closure|null $fixture_repository Optional fixture repository dependency or factory callback.
     *
     * @return void
     */
    public function __construct(
        ?object $wpdb = null,
        ?Player_Repository_Interface $player_repository = null,
        Fixture_Repository_Interface|Closure|null $fixture_repository = null
    ) {
        if ( null === $wpdb ) {
            global $wpdb;
        }
        $this->wpdb = $wpdb;
        if ( $this->wpdb ) {
            $prefix                   = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
            $this->table_name         = $prefix . 'racketmanager_teams';
            $this->team_players_table = $prefix . 'racketmanager_team_players';
        }
        $this->player_repository  = $player_repository;
        $this->fixture_repository = $fixture_repository;
    }

    /**
     * Retrieves the resolved fixture repository, resolving a configured factory callback when necessary.
     *
     * @return Fixture_Repository_Interface|null Resolved fixture repository instance, or null when no repository is configured.
     */
    private function get_fixture_repository(): ?Fixture_Repository_Interface {
        if ( null === $this->resolved_fixture_repository && null !== $this->fixture_repository ) {
            $this->resolved_fixture_repository = $this->fixture_repository instanceof Closure
                ? ( $this->fixture_repository )()
                : $this->fixture_repository;
        }

        return $this->resolved_fixture_repository;
    }

    /**
     * Finds a team by its numeric ID or title.
     *
     * @param int|string|null $id Team ID, team title, or null.
     *
     * @return Team|null Team instance when found, or null when no matching team exists.
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
                $team = $this->wpdb->get_row(
                    "SELECT `id`, `title`, `stadium`, `home`, `profile`, `status`, `club_id`, `type`, `team_type` FROM $this->table_name WHERE " . $search . ' LIMIT 1' // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                );
            }
            if ( ! $team ) {
                return null;
            }
            $team = Team_Factory::from_object( $team );
            $this->enrich( $team );
            wp_cache_set( $team->id, $team, 'teams' );
        }

        return $team;
    }

    /**
     * Enriches a team with related player data and title information while preventing recursive resolution.
     *
     * @param Team $team Team instance to enrich.
     *
     * @return void
     */
    private function enrich( Team $team ): void {
        $id = $team->get_id();
        if ( null !== $id && isset( $this->resolving_teams[ $id ] ) ) {
            return;
        }
        if ( null !== $id ) {
            $this->resolving_teams[ $id ] = true;
        }
        try {
            $this->enrich_players( $team );
            $this->enrich_title( $team );
        } finally {
            if ( null !== $id ) {
                unset( $this->resolving_teams[ $id ] );
            }
        }
    }

    /**
     * Enriches a team with associated player objects and player identifiers for player-entry teams.
     *
     * @param Team $team Team instance to populate with related players and player IDs.
     *
     * @return void
     */
    private function enrich_players( Team $team ): void {
        if ( $team->id && 'P' === $team->team_type && $this->wpdb ) {
            $players = $this->wpdb->get_results(
                $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `player_id` FROM $this->team_players_table WHERE `team_id` = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $team->id // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                )
            );
            if ( ! empty( $players ) ) {
                $player_objects = [];
                $player_ids     = [];
                $i              = 1;
                foreach ( $players as $team_player ) {
                    $player = $this->player_repository?->find( (int) $team_player->player_id );
                    if ( $player ) {
                        $player_objects[] = $player;
                    }
                    $player_ids[ $i ] = (int) $team_player->player_id;
                    ++$i;
                }
                $team->set_players( $player_objects );
                $team->set_player_ids( $player_ids );
            }
        }
    }

    /**
     * Enriches a team's title from its reference or placeholder-based title.
     *
     * @param Team $team Team instance whose title may be updated when a valid placeholder reference is found.
     *
     * @return void
     */
    private function enrich_title( Team $team ): void {
        if ( ! empty( $team->team_ref ) ) {
            $ref = $team->team_ref;
        } elseif ( ! empty( $team->title ) && str_contains( $team->title, '_' ) ) {
            $ref = $team->title;
        } else {
            return;
        }

        $placeholder = Team_Placeholder_Ref::parse( $ref );
        if ( null === $placeholder ) {
            return;
        }

        $fixture_repo = $this->get_fixture_repository();
        if ( null !== $fixture_repo ) {
            $expanded = Util::generate_team_name( $ref, $fixture_repo );
            if ( ! empty( $expanded ) ) {
                $team->set_title( $expanded );
                return;
            }
        }

        $team->set_title( Util::format_placeholder_label( $placeholder ) );
    }

    /**
     * Finds a team ID by its title.
     *
     * Trims the provided title, returns 0 for an empty title, checks the team cache,
     * and queries the database when no cached ID is available.
     *
     * @param string $title Team title to search for.
     *
     * @return int Matching team ID, or 0 when no team is found.
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
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `id` FROM $this->table_name WHERE `title` = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $title // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        if ( $id > 0 ) {
            wp_cache_set( 'team_id_' . md5( $title ), $id, 'teams' );
        }

        return $id;
    }

    /**
     * Finds an entity ID by its name.
     *
     * @param string $name Name to search for.
     *
     * @return int|null Matching entity ID, or null when no matching entity is found.
     */
    public function find_id_by_name( string $name ): ?int {
        $id = $this->find_id_by_title( $name );
        return $id > 0 ? $id : null;
    }

    /**
     * Finds the title for a record by its unique identifier.
     *
     * @param int $id Record identifier used to locate the title.
     *
     * @return string|null Title when found, or null when no matching record exists.
     */
    private function find_title_by_id( int $id ): ?string {
        $title = $this->wpdb->get_var(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `title` FROM $this->table_name WHERE `id` = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $id // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        return ( null === $title || false === $title ) ? null : (string) $title;
    }

    /**
     * Invalidates the cached team ID associated with a non-empty team title.
     *
     * @param string|null $title Team title used to build the cache key. Null or empty values are ignored.
     *
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
     * Finds teams for a club, optionally filtered by team type, ordered by title.
     *
     * @param int         $club_id Club identifier used to retrieve matching teams.
     * @param string|null $type Optional team type filter.
     *
     * @return array List of matching team objects enriched with related data.
     */
    public function find_by_club( int $club_id, ?string $type = null ): array {
        $query    = "SELECT * FROM $this->table_name WHERE club_id = %d";
        $params[] = $club_id;
        if ( $type ) {
            $query   .= " AND type = '%s'";
            $params[] = $type;
        }
        $query  .= ' AND team_type IS NULL ORDER BY title';
        $results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return array_map(
            function ( $row ) {
                $team = Team_Factory::from_object( $row );
                $this->enrich( $team );
                return $team;
            },
            $results
        );
    }

    /**
     * Finds player teams matching the specified competition or team type.
     *
     * @param string $type Type identifier used to filter player teams.
     *
     * @return Team[] List of enriched teams ordered by title.
     */
    public function find_for_players( string $type ): array {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT * FROM $this->table_name WHERE team_type = 'P' AND type = %s ORDER BY title", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $type // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        return array_map(
            function ( $row ) {
                $team = Team_Factory::from_object( $row );
                $this->enrich( $team );
                return $team;
            },
            $results
        );
    }

    /**
     * Persists a team entity to the database and updates related cache entries.
     *
     * Creates a new record when the entity has no ID, or updates the existing record when an ID is present.
     * Returns the inserted record ID for new entities, true for successful updates, or false on failure.
     *
     * @param object $entity Team entity to save.
     *
     * @return bool|int Inserted team ID when created, true when updated successfully, or false on failure.
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
            '%s', // Format for name (string).
            '%s', // Format for stadium (string).
            '%d', // Format for club_id (int).
            '%s', // Format for type (string).
            '%s', // Format for team_type (string).
        );

        if ( $entity->get_id() === null ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $data_types );
            if ( $inserted ) {
                $insert_id = $this->wpdb->insert_id;
                $entity->set_id( $insert_id );
                wp_cache_set( $insert_id, $entity, 'teams' );
                $this->invalidate_title_cache( $entity->get_name() );
                $response = $insert_id;
            } else {
                $response = false;
            }
        } else {
            $old_title = $this->find_title_by_id( (int) $entity->get_id() );

            // UPDATE: Use wpdb->update with the prepare logic built-in.
            $updated = $this->wpdb->update(
                $this->table_name,
                $data,
                array( 'id' => $entity->get_id() ),            // Where clause.
                $data_types,                                   // Data format.
                array( '%d' )                                  // Where format.
            );
            if ( false === $updated ) {
                $response = false;
            } else {
                wp_cache_set( $entity->get_id(), $entity, 'teams' );
                $this->invalidate_title_cache( $old_title );
                $this->invalidate_title_cache( $entity->get_name() );
                $response = true;
            }
        }

        return $response;
    }

    /**
     * Determines whether the specified club has any teams.
     *
     * @param int $club_id Club ID to check for associated teams.
     *
     * @return bool True if the club has one or more teams, false otherwise.
     */
    public function has_teams( int $club_id ): bool {
        $count = $this->wpdb->query(
            $this->wpdb->prepare(                                                     // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT COUNT(*) FROM $this->table_name WHERE `club_id` = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $club_id                                                             // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        return $count > 0;
    }

    /**
     * Finds the next available sequence number for a title matching the club shortcode and type.
     *
     * @param string $club_shortcode Club shortcode used as the leading title prefix.
     * @param string $type Type value used after the club shortcode in the title.
     *
     * @return int Next sequence number for the matching title pattern.
     */
    public function find_next_sequence_number( string $club_shortcode, string $type ): int {
        // We look for names starting with the shortcode followed by a space and digits.
        $prefix_like = $this->wpdb->esc_like( $club_shortcode ) . ' ' . $this->wpdb->esc_like( $type ) . ' %';
        $query       = $this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED)) FROM $this->table_name WHERE `title` LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $prefix_like
        );

        $max_sequence = (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        // If no teams found, MAX returns NULL/0, so the next number is 1. Otherwise, increment the max.
        return $max_sequence + 1;
    }

    /**
     * Determines whether the specified player is a captain for any standard team in the given club.
     *
     * @param int $club_id Club identifier to check.
     * @param int $player Player identifier to check as captain.
     *
     * @return bool True if the player is a captain for a matching club team, false otherwise.
     */
    public function find_captain( int $club_id, int $player ): bool {
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT count(*) FROM $this->table_name t, $league_teams_table t1 WHERE t.`club_id` = %d AND t.`team_type` IS NULL AND t.`id` = t1.`team_id` AND t1.`captain` = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $club_id,        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $player          // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        return $count > 0;
    }

    /**
     * Finds teams in a competition for a season with club, league, and player participation details.
     *
     * @param int $competition_id Competition identifier used to filter associated events and leagues.
     * @param int $season Season identifier used to filter league teams and fixtures.
     * @param int $min_fixtures Minimum number of rubber player records required for the team's player count calculation.
     *
     * @return Team_Competition_DTO[] List of team competition detail data transfer objects.
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

        $query   = $this->wpdb->prepare(
            " SELECT t.id as team_id, t.title as team_name, c.id as club_id, c.shortcode as club_shortcode, l.title as league_name, (SELECT COUNT(DISTINCT rp.player_id) FROM `$rubber_players_table` rp INNER JOIN `$rubbers_table` r ON rp.rubber_id = r.id INNER JOIN `$matches_table` f ON r.match_id = f.id INNER JOIN `$leagues_table` l ON f.league_id = l.id INNER JOIN `$events_table` e_sub ON l.event_id = e_sub.id WHERE e_sub.competition_id = %d AND f.season = %d AND ( (rp.player_team = 'home' AND f.home_team = t.id) OR (rp.player_team = 'away' AND f.away_team = t.id) ) HAVING COUNT(rp.id) >= %d ) as num_players FROM  `$teams_table` t INNER JOIN `$clubs_table` c ON t.club_id = c.id INNER JOIN `$league_teams_table` lte ON t.id = lte.team_id INNER JOIN `$leagues_table` l ON lte.league_id = l.id INNER JOIN `$events_table` e ON l.event_id = e.id WHERE e.competition_id = %d AND lte.season = %d ORDER BY c.shortcode, t.title", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $competition_id,
            $season,
            $min_fixtures,
            $competition_id,
            $season
        );
        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return array_map( fn( $row ) => new Team_Competition_DTO( $row ), $results );
    }

    /**
     * Retrieves fixture settings for a team within a specific event.
     *
     * @param int $team_id Team identifier used to locate the league team settings.
     * @param int $event_id Event identifier used to filter the associated league.
     *
     * @return Team_Fixture_Settings_DTO|null Fixture settings for the team and event, or null when no matching settings are found.
     */
    public function find_team_settings_for_event( int $team_id, int $event_id ): ?Team_Fixture_Settings_DTO {
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $users_table        = $this->wpdb->base_prefix . 'users';
        $usermeta_table     = $this->wpdb->base_prefix . 'usermeta';

        $phone_meta_key = 'contactno';

        $query = $this->wpdb->prepare(
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "
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
    ",
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $phone_meta_key,
            $team_id,
            $event_id
        );

        $row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return $row ? new Team_Fixture_Settings_DTO( $row ) : null;
    }

    /**
     * Finds a team whose player membership exactly matches the provided player IDs.
     *
     * @param array $player_ids Player IDs to match against a team's assigned players.
     *
     * @return int|null Matching team ID, or null when no exact match is found.
     */
    public function find_team_by_players( array $player_ids ): ?int {
        // Ensure IDs are unique and sorted for consistent matching.
        sort( $player_ids );
        $count        = count( $player_ids );
        $placeholders = implode( ',', array_fill( 0, $count, '%d' ) );

        $query = "
        SELECT team_id
        FROM `$this->team_players_table`
        GROUP BY team_id
        HAVING COUNT(DISTINCT player_id) = %d
           AND COUNT(CASE WHEN player_id IN ($placeholders) THEN 1 END) = %d
           AND COUNT(player_id) = %d
        LIMIT 1";
        $args  = array_merge( [ $count ], $player_ids, [ $count, $count ] );
        $id    = $this->wpdb->get_var(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $query,           // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                ...$args          // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        return $id ? (int) $id : null;
    }

    /**
     * Saves player assignments for a team and clears the team's cache entry.
     *
     * @param int   $team_id Team identifier to associate players with.
     * @param array $player_ids Player identifiers to save for the team.
     *
     * @return bool True when all player assignments are inserted successfully, false if any insert fails.
     */
    public function save_team_players( int $team_id, array $player_ids ): bool {
        $success = true;
        foreach ( $player_ids as $player_id ) {
            $inserted = $this->wpdb->insert(
                $this->team_players_table,
                [
                    'team_id'   => $team_id,
                    'player_id' => absint( $player_id ),
                ],
                [ '%d', '%d' ]
            );
            if ( false === $inserted ) {
                $success = false;
            }
        }
        wp_cache_delete( $team_id, 'teams' );
        return $success;
    }

    /**
     * Saves a team and its associated players within a database transaction.
     *
     * @param Team  $team Team entity to save.
     * @param array $player_ids Player identifiers to associate with the saved team.
     *
     * @return int|false Saved team identifier on success, or false when the team or player associations cannot be saved.
     * @throws Database_Operation_Exception Database operation exception.
     * @throws Throwable Database operation exception.
     */
    public function save_with_players( Team $team, array $player_ids ): int|false {
        $this->begin_transaction();
        try {
            $saved = $this->save( $team );
            if ( ! $saved ) {
                throw new Database_Operation_Exception( __( 'Unable to save team', 'racketmanager' ) );
            }
            // save() returns the new id on insert but bool true on update: always take the id from the entity.
            $team_id = $team->get_id() ?? 0;
            if ( $team_id <= 0 ) {
                throw new Database_Operation_Exception( __( 'Unable to save team', 'racketmanager' ) );
            }
            $saved_players = $this->save_team_players( $team_id, $player_ids );
            if ( ! $saved_players ) {
                throw new Database_Operation_Exception( __( 'Unable to save team players', 'racketmanager' ) );
            }
            $this->commit();

            return $team_id;
        } catch ( Database_Operation_Exception ) {
            $this->rollback();

            return false;
        } catch ( Throwable $e ) {
            $this->rollback();

            throw $e;
        }
    }

    /**
     * Deletes a team and its associated fixtures within a database transaction.
     *
     * @param int                          $team_id ID of the team to delete.
     * @param Fixture_Repository_Interface $fixture_repository Repository used to delete fixtures associated with the team.
     *
     * @return bool True when the associated fixtures and team are deleted successfully, false otherwise.
     */
    public function delete_with_fixtures( int $team_id, Fixture_Repository_Interface $fixture_repository ): bool {
        $this->begin_transaction();
        try {
            $fixtures_deleted = $fixture_repository->delete_by_team( $team_id );
            if ( ! $fixtures_deleted ) {
                $this->rollback();
                return false;
            }

            $deleted = $this->delete( $team_id );
            if ( $deleted ) {
                $this->commit();
            } else {
                $this->rollback();
            }
            return $deleted;
        } catch ( Throwable ) {
            $this->rollback();
            return false;
        }
    }

    /**
     * Updates a team's event-specific league settings for the specified season.
     *
     * @param int         $team_id Team identifier.
     * @param int         $event_id Event identifier used to match related leagues.
     * @param string      $season Season identifier.
     * @param string      $captain Captain identifier to store for the team.
     * @param string      $match_day Preferred match day.
     * @param string|null $match_time Preferred match time, or null to store an empty value.
     *
     * @return bool True when the update query completes successfully, false otherwise.
     */
    public function update_event_settings( int $team_id, int $event_id, string $season, string $captain, string $match_day, ?string $match_time ): bool {
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $query = $this->wpdb->prepare(
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "UPDATE `$league_teams_table`
             SET `captain` = %d, `match_day` = %s, `match_time` = %s
             WHERE `team_id` = %d
               AND `season` = %s
               AND `league_id` IN (
                   SELECT `id` FROM `$leagues_table` WHERE `event_id` = %d
               )",
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            (int) $captain,
            $match_day,
            $match_time ?? '',
            $team_id,
            $season,
            $event_id
        );

        $result = $this->wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return false !== $result;
    }

    /**
     * Deletes a team and its related player and league team records.
     *
     * @param int $id Team ID to delete.
     *
     * @return bool True when all delete operations complete successfully, false on database deletion failure.
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
            $response = false;
        } else {
            wp_cache_delete( $id, 'teams' );
            $this->invalidate_title_cache( $old_title );
            $response = true;
        }
        return $response;
    }

    /**
     * Finds teams matching the provided query arguments or returns the matching team count.
     *
     * @param array $args Optional query arguments. Supports offset, limit, orderby, count, player, and partner filters.
     *
     * @return array|int Array of hydrated team objects when count is false, or the total number of matching teams when count is true.
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
            $search_terms[] = $this->wpdb->prepare(
                "`id` IN (SELECT `team_id` FROM $this->team_players_table WHERE `player_id` = %d )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $player
            );
        }
        if ( $partner ) {
            $search_terms[] = $this->wpdb->prepare(
                "`id` IN (SELECT `team_id` FROM $this->team_players_table WHERE `player_id` = %d )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $partner
            );
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
                $teams = $this->wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                wp_cache_set( md5( $sql ), $teams, 'teams' );
            }
            return (int) $teams;
        }
        $sql  .= Util::order_by_string( $orderby );
        $sql   = $this->wpdb->prepare(
            $sql . ' LIMIT %d, %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            intval( $offset ),
            intval( $limit )
        );
        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
