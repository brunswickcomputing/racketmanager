<?php
declare( strict_types=1 );
/**
 * League_Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Closure;
use Racketmanager\Domain\DTO\League\League_Standings_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Domain\Team\Team_Placeholder_Ref;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;
use stdClass;
use wpdb;

/**
 * Repository for persisting, retrieving, hydrating, and enriching league team entities.
 *
 * Encapsulates access to the league teams database table, including creation,
 * updates, deletion, cache integration, and lookup by identifier or related
 * league/team data. Hydrated entities may be enriched with associated club data,
 * roster players for player-based teams, and expanded placeholder team titles
 * when the required dependent repositories are available.
 *
 * Uses WordPress database and cache APIs for storage operations and keeps
 * fixture repository resolution lazy to support circular dependency scenarios.
 */
class League_Team_Repository implements League_Team_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private string $season_compare;
    private ?Club_Repository_Interface $club_repository;
    private ?Team_Repository_Interface $team_repository;
    private Fixture_Repository_Interface|Closure|null $fixture_repository;
    private ?Fixture_Repository_Interface $resolved_fixture_repository = null;

    /**
     * @var array<int|string, bool>
     */
    private array $resolving_league_teams = array();

    /**
     * Initialises the league team repository with database access and optional related repositories.
     *
     * @param wpdb|null                                 $wpdb WordPress database instance. Defaults to the global database instance when null.
     * @param Club_Repository_Interface|null            $club_repository Optional club repository dependency.
     * @param Team_Repository_Interface|null            $team_repository Optional team repository dependency.
     * @param Fixture_Repository_Interface|Closure|null $fixture_repository Optional fixture repository dependency or factory callback.
     *
     * @return void
     */
    public function __construct(
        ?wpdb $wpdb = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Team_Repository_Interface $team_repository = null,
        Fixture_Repository_Interface|Closure|null $fixture_repository = null
    ) {
        $this->wpdb               = $wpdb ?? $GLOBALS['wpdb'];
        $this->season_compare     = ' AND `season` = %d';
        $this->table_name         = $this->wpdb->prefix . 'racketmanager_league_teams';
        $this->club_repository    = $club_repository;
        $this->team_repository    = $team_repository;
        $this->fixture_repository = $fixture_repository;
    }

    /**
     * Resolves and returns the fixture repository dependency when available.
     *
     * @return Fixture_Repository_Interface|null Resolved fixture repository instance, or null when no fixture repository is configured.
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
     * Hydrates a league team entity from a data row and enriches it with related data.
     *
     * @param object $row Source data object used to create the league team entity.
     *
     * @return League_Team Hydrated and enriched league team entity.
     */
    public function hydrate( object $row ): League_Team {
        $league_team = League_Team_Factory::from_object( $row );
        $this->enrich( $league_team );
        return $league_team;
    }

    /**
     * Enriches a league team with related club data, player roster data, and title information.
     *
     * Prevents recursive enrichment for the same league team while related data is being resolved.
     *
     * @param League_Team $league_team League team instance to enrich.
     *
     * @return void
     */
    private function enrich( League_Team $league_team ): void {
        $id = $league_team->get_id() ?? $league_team->table_id ?? null;
        if ( null !== $id && isset( $this->resolving_league_teams[ $id ] ) ) {
            return;
        }
        if ( null !== $id ) {
            $this->resolving_league_teams[ $id ] = true;
        }
        try {
            if ( ! empty( $league_team->club_id ) && null !== $this->club_repository ) {
                $league_team->set_club( $this->club_repository->find_by_id( $league_team->club_id ) );
            }
            if ( 'P' === ( $league_team->team_type ?? null ) && null !== $league_team->roster && null !== $this->team_repository ) {
                $team_id = ! empty( $league_team->get_team_id() ) ? $league_team->get_team_id() : $league_team->id ?? 0;
                if ( ! empty( $team_id ) ) {
                    $team = $this->team_repository->find_by_id( $team_id );
                    $league_team->set_players( $team?->players ?? array() );
                }
            }
            $this->enrich_title( $league_team );
        } finally {
            if ( null !== $id ) {
                unset( $this->resolving_league_teams[ $id ] );
            }
        }
    }

    /**
     * Enriches a league team's title by expanding or formatting a placeholder team reference.
     *
     * @param League_Team $league_team League team whose title should be enriched when a valid placeholder reference is available.
     *
     * @return void
     */
    private function enrich_title( League_Team $league_team ): void {
        $title_ref = ! empty( $league_team->title ) && str_contains( $league_team->title, '_' )
            ? $league_team->title
            : null;
        $ref       = ! empty( $league_team->team_ref )
            ? $league_team->team_ref
            : $title_ref;
        if ( empty( $ref ) ) {
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
                $league_team->set_title( $expanded );
                return;
            }
        }

        $league_team->set_title( Util::format_placeholder_label( $placeholder ) );
    }

    /**
     * Persists a league team entity to the database, creating a new record when no table ID exists or updating the existing record otherwise.
     *
     * @param object $entity League team entity containing the values to save.
     *
     * @return bool|int Inserted row ID when a new record is created, true when an existing record is updated successfully, or false on failure.
     */
    public function save( object $entity ): bool|int {
        /** @var League_Team $entity */
        $data        = array(
            'team_id'        => $entity->get_team_id(),
            'league_id'      => $entity->get_league_id(),
            'season'         => $entity->get_season(),
            'captain'        => $entity->get_captain(),
            'match_day'      => $entity->get_match_day(),
            'match_time'     => $entity->get_match_time(),
            'points_plus'    => $entity->get_points_plus(),
            'points_minus'   => $entity->get_points_minus(),
            'points_2_plus'  => $entity->get_points_2_plus(),
            'points_2_minus' => $entity->get_points_2_minus(),
            'add_points'     => $entity->get_add_points(),
            'done_matches'   => $entity->get_done_matches(),
            'won_matches'    => $entity->get_won_matches(),
            'draw_matches'   => $entity->get_drawn_matches(),
            'lost_matches'   => $entity->get_lost_matches(),
            'diff'           => $entity->get_diff(),
            'group'          => $entity->get_group(),
            'rank'           => $entity->get_rank(),
            'profile'        => $entity->get_profile(),
            'status'         => $entity->get_status(),
            'rating'         => $entity->get_rating(),
            'custom'         => maybe_serialize( $entity->get_custom() ),
        );
        $data_format = array(
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%f',
            '%f',
            '%d',
            '%d',
            '%f',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
            '%d',
            '%s',
            '%f',
            '%s',
        );
        $table_id    = $entity->get_table_id() ?? $entity->table_id ?? $entity->get_id();
        if ( empty( $table_id ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format
            );
            if ( $inserted ) {
                $entity->set_table_id( $this->wpdb->insert_id );
                $entity->set_id( $this->wpdb->insert_id );
                wp_cache_set( $entity->table_id, $entity, 'league-teams' );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            wp_cache_set( $table_id, $entity, 'league-teams' );
            return $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $table_id,
                ), // Where clause.
                $data_format,
                array(
                    '%d',
                ) // Where format.
            ) !== false;
        }
    }

    /**
     * Deletes a league team record by its identifier and clears its cached entry.
     *
     * @param int $id League team identifier.
     *
     * @return bool True when the delete operation completes without a database error, false otherwise.
     */
    public function delete( int $id ): bool {
        wp_cache_delete( $id, 'league-teams' );
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }

    /**
     * Finds a league team by its identifier.
     *
     * @param int|string $id The league team identifier.
     *
     * @return League_Team|null The matching league team, or null if not found.
     */
    public function find( int|string $id ): ?League_Team {
        return $this->find_by_id( $id );
    }

    /**
     * Finds a league team by its identifier.
     *
     * @param mixed $id The league team identifier.
     *
     * @return League_Team|null The league team instance, or null if not found.
     */
    public function find_by_id( $id ): ?League_Team {
        if ( empty( $id ) ) {
            return null;
        }
        $id          = (int) $id;
        $league_team = wp_cache_get( $id, 'league-teams' );

        if ( ! $league_team ) {
            $teams_table     = $this->wpdb->prefix . 'racketmanager_teams';
            $league_team_row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT B.`id` AS `id`, A.`team_id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, A.`profile`, A.`captain`, A.`match_day`, A.`match_time`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating` FROM $teams_table B INNER JOIN $this->table_name A ON B.id = A.team_id WHERE A.`id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $league_team_row ) {
                $league_team_row = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                        $id
                    )
                );
                if ( $league_team_row ) {
                    // Raw league_teams row: its `id` is the table id and `team_id` is the team.
                    $league_team_row->table_id = (int) $league_team_row->id;
                    $league_team_row->id       = (int) ( $league_team_row->team_id ?? $league_team_row->id );
                }
            }

            if ( ! $league_team_row ) {
                return null;
            }
            $league_team = $this->hydrate( $league_team_row );

            wp_cache_set( $id, $league_team, 'league-teams' );
        }

        return $league_team;
    }

    /**
     * Updates the league constitution data for a table record.
     *
     * @param int    $table_id The table record identifier.
     * @param int    $league_id The league identifier to assign.
     * @param string $status The constitution status to set.
     *
     * @return bool True if the update query succeeds, false otherwise.
     */
    public function update_constitution( int $table_id, int $league_id, string $status ): bool {
        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'league_id' => $league_id,
                'status'    => $status,
            ),
            array( 'id' => $table_id ),
            array( '%d', '%s' ),
            array( '%d' )
        ) !== false;

        wp_cache_delete( $table_id, 'league-teams' );
        return $result;
    }

    /**
     * Updates the league, rank, status, and profile for a constitution table entry.
     *
     * @param int        $table_id The identifier of the constitution table entry to update.
     * @param int        $league_id The league identifier to assign.
     * @param int        $rank The constitution rank to set.
     * @param string     $status The status to set.
     * @param string|int $profile The profile identifier to assign.
     *
     * @return bool True if the update query completed successfully, false otherwise.
     */
    public function set_constitution_rank( int $table_id, int $league_id, int $rank, string $status, string|int $profile ): bool {
        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'league_id' => $league_id,
                'rank'      => $rank,
                'status'    => $status,
                'profile'   => (int) $profile,
            ),
            array( 'id' => $table_id ),
            array( '%d', '%d', '%s', '%d' ),
            array( '%d' )
        ) !== false;

        wp_cache_delete( $table_id, 'league-teams' );
        return $result;
    }

    /**
     * Updates the additional points assigned to a league table entry.
     *
     * @param int   $table_id The league table entry identifier.
     * @param float $points The points value to apply.
     *
     * @return bool True if the points were updated successfully, false otherwise.
     */
    public function amend_points( int $table_id, float $points ): bool {
        $result = $this->wpdb->update(
            $this->table_name,
            array( 'add_points' => $points ),
            array( 'id' => $table_id ),
            array( '%f' ),
            array( '%d' )
        ) !== false;

        wp_cache_delete( $table_id, 'league-teams' );
        return $result;
    }

    /**
     * Updates the captain and match schedule details for a league team.
     *
     * @param int    $table_id The league team table record identifier.
     * @param int    $captain The captain identifier to set.
     * @param string $match_day The match day to set.
     * @param string $match_time The match time to set.
     *
     * @return bool True if the update query completed successfully, false otherwise.
     */
    public function update_details( int $table_id, int $captain, string $match_day, string $match_time ): bool {
        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'captain'    => $captain,
                'match_day'  => $match_day,
                'match_time' => $match_time,
            ),
            array( 'id' => $table_id ),
            array( '%d', '%s', '%s' ),
            array( '%d' )
        ) !== false;

        wp_cache_delete( $table_id, 'league-teams' );
        return $result;
    }

    /**
     * Updates the stored statistics for a league table entry.
     *
     * @param int   $table_id The league table entry identifier.
     * @param int   $done_matches The number of completed matches.
     * @param int   $won_matches The number of won matches.
     * @param int   $draw_matches The number of drawn matches.
     * @param int   $lost_matches The number of lost matches.
     * @param float $points_plus The primary points scored or gained.
     * @param float $points_minus The primary points conceded or lost.
     * @param int   $points_2_plus The secondary points scored or gained.
     * @param int   $points_2_minus The secondary points conceded or lost.
     * @param float $add_points Additional points applied to the table entry.
     * @param array $custom Optional custom statistics or metadata to store.
     *
     * @return bool True if the statistics were updated successfully, false otherwise.
     */
    public function update_stats(
        int $table_id,
        int $done_matches,
        int $won_matches,
        int $draw_matches,
        int $lost_matches,
        float $points_plus,
        float $points_minus,
        int $points_2_plus,
        int $points_2_minus,
        float $add_points,
        array $custom = array()
    ): bool {
        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'done_matches'   => $done_matches,
                'won_matches'    => $won_matches,
                'draw_matches'   => $draw_matches,
                'lost_matches'   => $lost_matches,
                'points_plus'    => $points_plus,
                'points_minus'   => $points_minus,
                'points_2_plus'  => $points_2_plus,
                'points_2_minus' => $points_2_minus,
                'add_points'     => $add_points,
                'custom'         => maybe_serialize( $custom ),
            ),
            array( 'id' => $table_id ),
            array( '%d', '%d', '%d', '%d', '%f', '%f', '%d', '%d', '%f', '%s' ),
            array( '%d' )
        ) !== false;

        wp_cache_delete( $table_id, 'league-teams' );
        return $result;
    }

    /**
     * Finds club identifiers where the specified player is a team captain.
     *
     * @param int      $player_id The player identifier.
     * @param int|null $club_id Optional club identifier to restrict the search.
     *
     * @return array The matching club identifiers.
     */
    public function find_club_ids_where_player_is_captain( int $player_id, ?int $club_id = null ): array {
        // Need to join the league team entry table with the main teams table to get the club_id.
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $sql         = "SELECT DISTINCT t.club_id FROM $this->table_name lte INNER JOIN $teams_table t ON lte.team_id = t.id WHERE lte.captain = %d";
        $params      = array( $player_id );
        if ( $club_id ) {
            $sql     .= ' AND club_id = %d';
            $params[] = $club_id;
        }
        $query = $this->wpdb->prepare(
            $sql,
            $params
        );
        return $this->wpdb->get_col( $query );
    }

    /**
     * Finds league teams associated with an event, optionally filtered by season, team, or club.
     *
     * @param int|null $event_id The event identifier.
     * @param int|null $season The season to filter by, or null to include all seasons.
     * @param int|null $team_id The team identifier to filter by, or null to include all teams.
     * @param int|null $club_id The club identifier to filter by, or null to include all clubs.
     *
     * @return League_Team[] The matching league teams.
     */
    public function find_by_event_id( ?int $event_id, ?int $season = null, ?int $team_id = null, ?int $club_id = null ): array {
        $cache_key = 'event' . $event_id . '_' . $season . '_' . $team_id . '_' . $club_id;
        $teams     = wp_cache_get( md5( $cache_key ), 'league_teams' );
        if ( $teams ) {
            return $teams;
        }
        $search        = '';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        if ( $season ) {
            $search .= $this->wpdb->prepare( $this->season_compare, $season );
        }
        if ( $team_id ) {
            $search .= $this->wpdb->prepare( ' AND `team_id`  = %d', $team_id );
        }
        if ( $club_id ) {
            $search .= $this->wpdb->prepare( " AND `team_id` IN ( SELECT `id` FROM $teams_table WHERE `club_id` = %d)", $club_id );
        }
        $teams   = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT t.* FROM $this->table_name t, $leagues_table l WHERE l.`event_id` = %d AND l.`id` = t.`league_id` $search ORDER BY `title`",
                $event_id
            )
        );
        $results = array_map(
            fn( $row ) => $this->hydrate( $row ),
            $teams
        );
        wp_cache_set( md5( $cache_key ), $results, 'league_teams' );
        return $results;
    }

    /**
     * Finds team names that are missing a match day for the given events and season.
     *
     * @param array  $event_ids The event identifiers to check.
     * @param string $season The season to check.
     *
     * @return array The names of teams missing a match day, or an empty array if none are found.
     */
    public function find_teams_missing_match_day( array $event_ids, string $season ): array {
        $event_ids = array_values( array_filter( array_map( 'intval', $event_ids ) ) );
        if ( empty( $event_ids ) ) {
            return array();
        }
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $placeholders  = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
        $params        = array_merge( $event_ids, array( $season ) );
        $rows          = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT t.`title` FROM $teams_table t, $this->table_name t1, $leagues_table l WHERE t.`id` = t1.`team_id` AND (t1.`match_day` IS NULL OR t1.`match_day` = '') AND t1.`profile` != 3 AND l.`id` = t1.`league_id` AND l.`event_id` IN ($placeholders) AND t1.`season` = %s ORDER BY t.`title`",
                $params
            )
        );
        return is_array( $rows ) ? array_map( 'strval', $rows ) : array();
    }

    /**
     * Retrieves clubs participating in an event, optionally filtered by season.
     *
     * @param int|null $event_id The event identifier.
     * @param int|null $season The season to filter by, or null to include all seasons.
     *
     * @return array The list of club records with team counts for the event.
     */
    public function get_clubs_by_event_id( ?int $event_id, ?int $season = null ): array {
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $clubs_table   = $this->wpdb->prefix . 'racketmanager_clubs';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $cache_key     = 'event_clubs_' . $event_id . '_' . $season;
        $clubs         = wp_cache_get( md5( $cache_key ), 'league_teams' );
        if ( ! $clubs ) {
            $search = '';
            if ( $season ) {
                $search = $this->wpdb->prepare( $this->season_compare, $season );
            }
            $clubs = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT  t1.`club_id`, count(t1.`id`) as `team_count` FROM $this->table_name t, $leagues_table l, $teams_table t1, $clubs_table c WHERE l.`event_id` = %d AND l.`id` = t.`league_id` AND t.`team_id` = t1.`id` AND t1.`club_id` = c.`id` $search GROUP BY t1.`club_id`",
                    $event_id
                )
            );
            wp_cache_set( md5( $cache_key ), $clubs, 'league_teams' );
        }
        return $clubs;
    }

    /**
     * Retrieves clubs participating in a competition, optionally filtered by season.
     *
     * @param int|null $competition_id The competition identifier.
     * @param int|null $season The season to filter by, or null to include all seasons.
     *
     * @return array The matching club records with club identifiers, shortcodes, and team counts.
     */
    public function get_clubs_by_competition_id( ?int $competition_id, ?int $season = null ): array {
        $events_table  = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $clubs_table   = $this->wpdb->prefix . 'racketmanager_clubs';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $cache_key     = 'competition_clubs_' . $competition_id . '_' . $season;
        $clubs         = wp_cache_get( md5( $cache_key ), 'league_teams' );
        if ( ! $clubs ) {
            $search = '';
            if ( $season ) {
                $search = $this->wpdb->prepare( $this->season_compare, $season );
            }
            $clubs = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT  t1.`club_id`, c.shortcode, count(t1.`id`) as `team_count` FROM $this->table_name t, $events_table e, $leagues_table l, $teams_table t1, $clubs_table c WHERE l.`event_id` = e.`id` AND e.`competition_id` = %d AND l.`id` = t.`league_id` AND t.`team_id` = t1.`id` AND t1.`club_id` = c.`id` AND t.profile = 1 $search GROUP BY t1.`club_id`, c.shortcode ORDER BY c.shortcode",
                    $competition_id
                )
            );
            wp_cache_set( md5( $cache_key ), $clubs, 'league_teams' );
        }
        return $clubs;
    }

    /**
     * Finds league teams eligible to be withdrawn from a league for a club, season, and event.
     *
     * @param int    $club_id The club identifier used to match teams.
     * @param string $season The season identifier used to match league team entries.
     * @param int    $event_id The event identifier used to match leagues.
     * @param array  $team_ids Optional list of team identifiers to restrict the search.
     *
     * @return League_Team[] The matching league teams eligible for withdrawal.
     */
    public function find_teams_to_withdraw_from_league( int $club_id, string $season, int $event_id, array $team_ids = array() ): array {
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';

        $results = [];
        $query   = "
                    SELECT lte.*
                    FROM `$this->table_name` lte
                        INNER JOIN `$teams_table` t ON lte.team_id = t.id
                        INNER JOIN `$leagues_table` l ON lte.league_id = l.id
                    WHERE t.club_id = %d
                      AND l.event_id = %d
                      AND lte.season = %s
                    AND lte.profile != 3
                      ";

        $params = [ $club_id, $event_id, $season ];

        if ( ! empty( $team_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $team_ids ), '%d' ) );
            $query       .= " AND t.id IN ( $placeholders )";
            $params       = array_merge( $params, $team_ids );
        }
        $sql  = $this->wpdb->prepare( $query, $params );
        $rows = $this->wpdb->get_results( $sql );

        foreach ( $rows as $row ) {
            // Map to the Domain Model.
            $results[] = $this->hydrate( $row );
        }
        return $results;
    }

    /**
     * Determines whether a player has already been entered in a league for a season.
     *
     * @param int $player_id The player identifier.
     * @param int $league_id The league identifier.
     * @param int $season The season identifier.
     *
     * @return stdClass|null The matching team and league team entry, or null if the player has not been entered.
     */
    public function player_already_entered_league( int $player_id, int $league_id, int $season ): stdClass|null {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';

        $query = $this->wpdb->prepare(
            "SELECT DISTINCT t.id as team_id, lt.id as league_team_id
         FROM `$team_players_table` tp
         JOIN `$teams_table` t ON tp.team_id = t.id
         JOIN `$this->table_name` lt ON t.id = lt.team_id
         WHERE tp.player_id = %d
           AND lt.league_id = %d
           AND lt.season = %d
           AND lt.profile != 3",
            $player_id,
            $league_id,
            $season
        );

        return $this->wpdb->get_row( $query );
    }

    /**
     * Finds teams for a player in the specified season and events.
     *
     * @param int   $player_id The player identifier.
     * @param array $event_ids The event identifiers to match.
     * @param int   $season The season identifier.
     *
     * @return array The matching player teams.
     */
    public function find_player_teams_by_player_for_events( int $player_id, array $event_ids, int $season ): array {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        // Prepare placeholders for the IN clause.
        $placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
        $results      = array();

        // We update the status for teams where the player is a member
        // and that team is linked to one of the missed events.
        $query = $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            "SELECT lt.*
             FROM `$this->table_name` lt
         JOIN `$team_players_table` tp ON lt.team_id = tp.team_id
         JOIN `$leagues_table` l ON lt.league_id = l.id
         WHERE tp.player_id = %d
           and lt.season = %d
           AND l.event_id IN ($placeholders)
           AND lt.profile != 3",
            $player_id,
            $season,
            ...$event_ids
        );
        $rows  = $this->wpdb->get_results( $query );
        foreach ( $rows as $row ) {
            // Map to the Domain Model.
            $results[] = $this->hydrate( $row );
        }
        return $results;
    }

    /**
     * Finds the standings for a league in a given season, ordered by rank.
     *
     * @param int $league_id The league identifier.
     * @param int $season The season identifier.
     *
     * @return League_Standings_DTO[] The league standings entries for the specified league and season.
     */
    public function find_league_standings( int $league_id, int $season ): array {
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $query       = $this->wpdb->prepare(
            "SELECT lt.id,
                lt.league_id,
                lt.season,
                lt.profile,
                lt.points_plus,
                lt.add_points,
                lt.done_matches,
                lt.won_matches,
                lt.lost_matches,
                lt.draw_matches,
                lt.diff,
                lt.rank,
                lt.rating,
                lt.custom,
                lt.status,
                t.id as team_id,
                t.title as team_name
            FROM `$this->table_name` lt
            JOIN `$teams_table` t ON lt.team_id = t.id
            WHERE lt.league_id = %d
            AND lt.season = %d
            ORDER BY lt.rank",
            $league_id,
            $season
        );
        $cache_key   = md5( $league_id . '_' . $season );
        $rows        = wp_cache_get( $cache_key, 'league_standings' );
        if ( false === $rows ) {
            $rows = $this->wpdb->get_results( $query );
            wp_cache_set( $cache_key, $rows, 'league_standings' );
        }

        return array_map(
            function ( $row ) {
                return new League_Standings_DTO(
                    id: (int) $row->id,
                    league_id: (int) $row->league_id,
                    team_id: (int) $row->team_id,
                    team_name: $row->team_name,
                    season: (int) $row->season,
                    points: (float) $row->points_plus + (float) $row->add_points,
                    done_matches: (int) $row->done_matches,
                    won_matches: (int) $row->won_matches,
                    lost_matches: (int) $row->lost_matches,
                    drawn_matches: (int) $row->draw_matches,
                    rank: (int) $row->rank,
                    rating: (float) $row->rating,
                    // Convert raw int to Enum safely.
                    profile: Team_Profile::tryFrom( (int) $row->profile ) ?? Team_Profile::ACTIVE,
                    status: (string) $row->status,
                    additional_info: maybe_unserialize( $row->custom )
                );
            },
            $rows
        );
    }

    /**
     * Retrieves teams assigned to a league for a specific season.
     *
     * @param int $league_id The league identifier.
     * @param int $season The season year.
     *
     * @return array The matching team records for the league and season.
     */
    public function get_teams_by_league_and_season( int $league_id, int $season ): array {
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $query       = $this->wpdb->prepare(
            "SELECT t.id,
            t.title,
            '' as stadium
            FROM `$this->table_name` lt
            JOIN `$teams_table` t ON lt.team_id = t.id
            WHERE lt.league_id = %d
            AND lt.season = %d",
            $league_id,
            $season
        );
        $cache_key   = md5( $league_id . '_' . $season );
        $rows        = wp_cache_get( $cache_key, 'league_teams' );
        if ( false === $rows ) {
            $rows = $this->wpdb->get_results( $query );
            wp_cache_set( $cache_key, $rows, 'league_teams' );
        }
        return $rows;
    }

    /**
     * Finds a league team by team, league, and season.
     *
     * @param int $team_id The team identifier.
     * @param int $league_id The league identifier.
     * @param int $season The season year.
     *
     * @return League_Team|null The matching league team, or null if not found.
     */
    public function find_by_team_league_and_season( int $team_id, int $league_id, int $season ): ?League_Team {
        $query = $this->wpdb->prepare(
            "SELECT * FROM `$this->table_name` WHERE team_id = %d AND league_id = %d AND season = %d",
            $team_id,
            $league_id,
            $season
        );
        $row   = $this->wpdb->get_row( $query );
        if ( ! $row ) {
            return null;
        }
        return $this->hydrate( $row );
    }
}
