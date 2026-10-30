<?php
declare( strict_types=1 );
/**
 * Fixture_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Application\Export\DTO\Export_Criteria;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Util\Util;
use RuntimeException;
use wpdb;


/**
 * Class to implement the fixture repository
 */
class Fixture_Repository implements Fixture_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private ?Fixture_Relation_Loader $relation_loader;
    private ?Rubber_Repository_Interface $rubber_repository;
    private ?League_Repository_Interface $league_repository;
    private ?Team_Repository_Interface $team_repository;

    public function __construct(
        ?wpdb $db = null,
        ?Fixture_Relation_Loader $relation_loader = null,
        ?Rubber_Repository_Interface $rubber_repository = null,
        ?League_Repository_Interface $league_repository = null,
        ?Team_Repository_Interface $team_repository = null
    ) {
        global $wpdb;
        $this->wpdb              = $db ?? $wpdb;
        $this->table_name        = $this->wpdb->prefix . 'racketmanager_matches';
        $this->relation_loader   = $relation_loader;
        $this->rubber_repository = $rubber_repository;
        $this->league_repository = $league_repository;
        $this->team_repository   = $team_repository;
    }

    public function save( object $entity ): bool|int {
        /** @var Fixture $entity */
        $data = array(
            'group'               => $entity->get_group(),
            'date'                => Fixture_Time_Parser::to_mysql_datetime( $entity->get_date() ),
            'date_original'       => Fixture_Time_Parser::to_mysql_datetime( $entity->get_date_original() ),
            'home_team'           => $entity->get_home_team(),
            'away_team'           => $entity->get_away_team(),
            'match_day'           => $entity->get_match_day(),
            'location'            => $entity->get_location(),
            'host'                => $entity->get_host(),
            'league_id'           => $entity->get_league_id(),
            'season'              => $entity->get_season(),
            'home_points'         => $entity->get_home_points(),
            'away_points'         => $entity->get_away_points(),
            'winner_id'           => $entity->get_winner_id(),
            'loser_id'            => $entity->get_loser_id(),
            'status'              => $entity->get_status(),
            'linked_match'        => $entity->get_linked_fixture(),
            'leg'                 => $entity->get_leg(),
            'winner_id_tie'       => $entity->get_winner_id_tie(),
            'loser_id_tie'        => $entity->get_loser_id_tie(),
            'home_points_tie'     => $entity->get_home_points_tie(),
            'away_points_tie'     => $entity->get_away_points_tie(),
            'post_id'             => $entity->get_post_id(),
            'final'               => $entity->get_final(),
            'custom'              => maybe_serialize( $entity->get_custom() ),
            'confirmed'           => $entity->get_confirmed(),
            'home_captain'        => $entity->get_home_approver(),
            'away_captain'        => $entity->get_away_approver(),
            'comments'            => maybe_serialize( $entity->get_comments() ),
            'updated'             => current_time( 'mysql' ),
            'updated_user'        => get_current_user_id(),
        );

        if ( ! empty( $entity->get_home_points() ) || ! empty( $entity->get_away_points() ) ) {
            $data['date_result_entered'] = current_time( 'mysql' );
        }

        $format = array(
            '%s', // group
            '%s', // date
            '%s', // date_original
            '%s', // home_team
            '%s', // away_team
            '%d', // match_day
            '%s', // location
            '%s', // host
            '%d', // league_id
            '%s', // season
            '%s', // home_points
            '%s', // away_points
            '%d', // winner_id
            '%d', // loser_id
            '%d', // status
            '%d', // linked_match
            '%d', // leg
            '%d', // winner_id_tie
            '%d', // loser_id_tie
            '%f', // home_points_tie
            '%f', // away_points_tie
            '%d', // post_id
            '%s', // final
            '%s', // custom
            '%s', // confirmed
            '%d', // home_captain
            '%d', // away_captain
            '%s', // comments
            '%s', // updated
            '%d', // updated_user
        );

        if ( isset( $data['date_result_entered'] ) ) {
            $format[] = '%s';
        }

        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $format
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            return (bool) $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $entity->get_id()
                ),
                $format,
                array( '%d' )
            );
        }
    }

    /**
     * Delete a fixture by ID, including its associated rubbers.
     *
     * @param int $id
     * @return bool
     */
    public function delete( int $id ): bool {
        // Delete associated rubbers first
        $rubbers_table   = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubbers_deleted = $this->wpdb->delete(
            $rubbers_table,
            array( 'match_id' => $id ),
            array( '%d' )
        );
        if ( false === $rubbers_deleted ) {
            return false;
        }

        $deleted = $this->wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            wp_cache_delete( $id, 'fixtures' );
            wp_cache_delete( $id . '_legacy', 'fixtures' );
            wp_cache_delete( (string) $id, 'matches' );
            wp_cache_delete( (string) $id, 'match' );
        }

        return (bool) $deleted;
    }

    /**
     * Delete fixture alias.
     *
     * @param int $id
     * @return bool
     */
    public function delete_fixture( int $id ): bool {
        return $this->delete( $id );
    }

    public function find_by_id( $id ): ?Fixture {
        $row = $this->find_raw_by_id( $id );

        if ( ! $row ) {
            return null;
        }

        return $this->hydrate_fixture( $row, false );
    }

    /**
     * Find a fixture by ID and attach league and team relations.
     *
     * @param int $id
     * @return Fixture|null
     */
    public function find_by_id_with_relations( int $id ): ?Fixture {
        $row = $this->find_raw_by_id( $id );

        if ( ! $row ) {
            return null;
        }

        return $this->hydrate_fixture( $row, true );
    }

    /**
     * Find raw fixture row by ID.
     *
     * @param int $fixture_id
     * @param bool $legacy Whether to include legacy date formatting fields.
     * @return object|null
     */
    public function find_raw_by_id( int $fixture_id, bool $legacy = false ): ?object {
        if ( empty( $fixture_id ) ) {
            return null;
        }

        $cache_key = $legacy ? $fixture_id . '_legacy' : $fixture_id;
        $fixture = wp_cache_get( $cache_key, 'fixtures' );

        if ( ! $fixture ) {
            $select = "*";
            if ( $legacy ) {
                $select = "`final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date_original`, '%Y-%m-%d %H:%i') AS date_original, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `updated`, `updated_user`, `confirmed`, `home_captain`, `away_captain`, `comments`, `status`, `host`, `linked_match`, `leg`, `winner_id_tie`, `loser_id_tie`, `home_points_tie`, `away_points_tie`, `updated`, `date_result_entered`";
            }

            $fixture = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT $select FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $fixture_id
                )
            );

            if ( $fixture ) {
                wp_cache_set( $cache_key, $fixture, 'fixtures' );
            }
        }

        return $fixture ? (object) $fixture : null;
    }

    /**
     * @param int $tournament_id
     *
     * @return Fixture[]
     */
    public function find_finals_fixtures_for_tournament( int $tournament_id ): array {
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';
        $query             = $this->wpdb->prepare(
            "
                SELECT f.*
                FROM `$this->table_name` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                WHERE f.season = t.season
                  and f.final = 'final'
                  and t.id = %d
                ",
            $tournament_id,
        );
        $results           = $this->wpdb->get_results( $query );

        return array_map(
            fn( $row ) => $this->hydrate_fixture( $row, true ),
            $results
        );
    }

    public function find_fixtures_for_player_by_tournament( int $player_id, int $tournament_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';

        $query   = $this->wpdb->prepare(
            "
                SELECT DISTINCT f.*
                FROM `$this->table_name` f
                    JOIN $leagues_table l on f.league_id = l.id
                    JOIN $events_table e on l.event_id = e.id
                    JOIN $tournaments_table t on t.competition_id = e.competition_id
                INNER JOIN `$team_players_table` tp ON (tp.team_id = f.home_team OR tp.team_id = f.away_team)
                WHERE f.season = t.season
                  AND t.id = %d
                  AND tp.player_id = %d
                ORDER BY f.date DESC,
                         e.name,
                         l.title DESC
                      ",
            $tournament_id,
            $player_id,
        );
        $results = $this->wpdb->get_results( $query );
        foreach ( $results as &$row ) {
            $row = $this->hydrate_fixture( $row, true );
        }

        return $results;
    }

    public function delete_by_league_and_season( int $league_id, string $season ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array(
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%d', '%s' )
        ) !== false;
    }

    /**
     * Find fixtures by league, season and final key.
     *
     * @param int $league_id
     * @param string $season
     * @param string $final_key
     * @param int|null $leg
     * @return Fixture[]
     */
    public function find_by_league_and_final( int $league_id, string $season, string $final_key, ?int $leg = null ): array {
        $query = "SELECT * FROM $this->table_name WHERE `league_id` = %d AND `season` = %s AND `final` = %s";
        $args  = [ $league_id, $season, $final_key ];

        if ( null !== $leg ) {
            $query .= " AND `leg` = %d";
            $args[] = $leg;
        }

        $query .= " ORDER BY `id` ASC";

        $sql = $this->wpdb->prepare( $query, ...$args );
        $results = $this->wpdb->get_results( $sql );

        return array_map(
            fn( $row ) => $this->hydrate_fixture( $row ),
            $results
        );
    }
    /**
     * Find fixtures by league, season, and team ID (can be numeric or placeholder).
     *
     * @param int $league_id
     * @param string $season
     * @param string $team_id
     * @return Fixture[]
     */
    public function find_by_league_and_team( int $league_id, string $season, string $team_id ): array {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE `league_id` = %d AND `season` = %s AND (`home_team` = %s OR `away_team` = %s)",
            $league_id,
            $season,
            $team_id,
            $team_id
        );
        $results = $this->wpdb->get_results( $query );

        return array_map(
            fn( $row ) => $this->hydrate_fixture( $row, true ),
            $results
        );
    }

    /**
     * Find fixtures by team ID (either home or away).
     *
     * @param int $team_id
     * @return Fixture[]
     */
    public function find_by_team( int $team_id ): array {
        if ( ! $this->wpdb ) {
            return array();
        }
        $query   = $this->wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE `home_team` = %s OR `away_team` = %s",
            (string) $team_id,
            (string) $team_id
        );
        $results = $this->wpdb->get_results( $query );

        return array_map(
            fn( $row ) => $this->hydrate_fixture( $row ),
            $results ?: array()
        );
    }

    /**
     * Delete all fixtures and their rubbers for a given team ID.
     *
     * @param int $team_id
     * @return bool
     */
    public function delete_by_team( int $team_id ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $fixtures    = $this->find_by_team( $team_id );
        $all_deleted = true;
        foreach ( $fixtures as $fixture ) {
            if ( ! $this->delete( (int) $fixture->get_id() ) ) {
                $all_deleted = false;
            }
        }
        return $all_deleted;
    }

    /**
     * Count how many other matches a player has played on the same match day.
     *
     * @param string $season
     * @param int $match_day
     * @param int $league_id
     * @param int $club_player_id
     * @return int
     */
    public function count_player_matches_on_same_day( string $season, int $match_day, int $league_id, int $club_player_id ): int {
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';

        return (int) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT count(*)
             FROM $this->table_name m
             JOIN $rubbers_table r ON m.`id` = r.`match_id`
             JOIN $rubber_players_table rp ON r.`id` = rp.`rubber_id`
             WHERE m.`season` = %s
               AND m.`match_day` = %d
               AND m.`league_id` != %d
               AND m.`league_id` IN (
                   SELECT l.`id`
                   FROM $leagues_table l
                   WHERE l.`event_id` =  %d
               )
               AND rp.`club_player_id` = %d",
            $season,
            $match_day,
            $league_id,
            $league_id,
            $club_player_id
        ) );
    }

    public function update_status( int $id, int $status ): bool {
        $updated = $this->wpdb->update(
            $this->table_name,
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_cache_delete( $id, 'fixtures' );
            wp_cache_delete( $id . '_legacy', 'fixtures' );
            return true;
        }

        return false;
    }

    public function update_teams( int $id, string $home_team, string $away_team ): bool {
        $updated = $this->wpdb->update(
            $this->table_name,
            array(
                'home_team' => $home_team,
                'away_team' => $away_team,
            ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_cache_delete( $id, 'fixtures' );
            wp_cache_delete( $id . '_legacy', 'fixtures' );
            return true;
        }

        return false;
    }

    public function update_date( int $id, string $date, ?string $original_date = null ): bool {
        $canonical_date = Fixture_Time_Parser::to_mysql_datetime( $date );
        $data = array( 'date' => $canonical_date );
        $format = array( '%s' );

        if ( null !== $original_date ) {
            $data['date_original'] = Fixture_Time_Parser::to_mysql_datetime( $original_date );
            $format[] = '%s';
        }

        $updated = $this->wpdb->update(
            $this->table_name,
            $data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        );

        if ( false !== $updated ) {
            wp_cache_delete( $id, 'fixtures' );
            wp_cache_delete( $id . '_legacy', 'fixtures' );
            return true;
        }

        return false;
    }

    public function find_by_criteria( Export_Criteria $criteria ): array {
        $sql = "SELECT id FROM $this->table_name AS m";
        $where = array( '1=1' );

        if ( $criteria->league_id ) {
            $where[] = $this->wpdb->prepare( 'm.league_id = %d', $criteria->league_id );
        }

        if ( $criteria->season ) {
            $where[] = $this->wpdb->prepare( 'm.season = %s', $criteria->season );
        }

        if ( $criteria->competition_id ) {
            $league_table = $this->wpdb->prefix . 'racketmanager_leagues';
            $event_table  = $this->wpdb->prefix . 'racketmanager_events';
            $where[] = $this->wpdb->prepare(
                "m.league_id IN (SELECT id FROM $league_table WHERE event_id IN (SELECT id FROM $event_table WHERE competition_id = %d))",
                $criteria->competition_id
            );
        }

        if ( $criteria->club_id ) {
            $team_table = $this->wpdb->prefix . 'racketmanager_teams';
            $where[] = $this->wpdb->prepare(
                " (m.home_team IN (SELECT id FROM $team_table WHERE club_id = %d) OR m.away_team IN (SELECT id FROM $team_table WHERE club_id = %d))",
                $criteria->club_id,
                $criteria->club_id
            );
        }

        if ( $criteria->team_id ) {
            $where[] = $this->wpdb->prepare(
                '(m.home_team = %d OR m.away_team = %d)',
                $criteria->team_id,
                $criteria->team_id
            );
        }

        if ( $criteria->date_from ) {
            $where[] = $this->wpdb->prepare( 'm.date >= %s', $criteria->date_from );
        }

        if ( $criteria->date_to ) {
            $where[] = $this->wpdb->prepare( 'm.date <= %s', $criteria->date_to );
        }

        $sql .= ' WHERE ' . implode( ' AND ', $where );
        $sql .= ' ORDER BY m.match_day ASC, m.date ASC';

        $results = $this->wpdb->get_results( $sql );

        $fixtures = array();
        if ( ! empty( $results ) ) {
            foreach ( $results as $row ) {
                $fixture = $this->find_by_id( (int) $row->id );
                if ( $fixture ) {
                    $fixtures[] = $fixture;
                }
            }
        }

        return $fixtures;
    }

    /**
     * @param array $criteria
     * @return Fixture|null
     */
    public function find_one_by_slug_criteria( array $criteria ): ?Fixture {
        if ( ! $this->league_repository ) {
            return null;
        }

        $league_slug    = $criteria['league_slug'] ?? $criteria['league_id'] ?? null;
        $home_team_name = $criteria['home_team_slug'] ?? null;
        $away_team_name = $criteria['away_team_slug'] ?? null;
        $season         = $criteria['season'] ?? null;
        $match_day      = $criteria['match_day'] ?? null;
        $round          = $criteria['round'] ?? null;
        $leg            = $criteria['leg'] ?? null;

        if ( empty( $league_slug ) ) {
            return null;
        }

        $league = null;
        if ( is_numeric( $league_slug ) ) {
            $league = $this->league_repository->find_by_id( (int) $league_slug );
        } elseif ( is_string( $league_slug ) ) {
            $league = $this->league_repository->find_by_name( $league_slug );
        }

        if ( ! $league || empty( $league->id ) ) {
            return null;
        }

        $home_team_id = null;
        if ( $home_team_name ) {
            $home_team_id = $this->get_team_id_by_title( $home_team_name );
            if ( ! $home_team_id ) {
                return null;
            }
        }

        $away_team_id = null;
        if ( $away_team_name ) {
            $away_team_id = $this->get_team_id_by_title( $away_team_name );
            if ( ! $away_team_id ) {
                return null;
            }
        }

        $query_args = array(
            'season'          => $season,
            'home_team'       => $home_team_id,
            'away_team'       => $away_team_id,
            'match_day'       => $match_day,
            'final'           => $round,
            'leg'             => $leg,
            'is_championship' => ! empty( $league->is_championship ),
            'current_season'  => $league->current_season['name'] ?? null,
        );

        $rows = $this->league_repository->query_matches( (int) $league->id, $query_args );

        if ( empty( $rows ) || ! is_array( $rows ) || count( $rows ) !== 1 ) {
            return null;
        }

        return $this->hydrate_fixture( $rows[0], true );
    }

    /**
     * Find all confirmed fixtures played by a specific player with optional filters.
     *
     * @param int $player_id
     * @param array $args
     * @return Fixture[]
     */
    public function find_player_matches( int $player_id, array $args = [] ): array {
        if ( ! $this->rubber_repository ) {
            throw new RuntimeException( 'Fixture_Repository::find_player_matches requires a Rubber_Repository_Interface.' );
        }

        $defaults = [
            'season'  => null,
            'time'    => null,
            'type'    => null,
            'status'  => null,
        ];
        $args = array_merge( $defaults, $args );

        $matches_table        = $this->table_name;
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';

        $sql_from = " FROM `$matches_table` AS m, `$leagues_table` AS l, `$rubbers_table` AS r, `$rubber_players_table` AS rp";
        $where    = [
            'm.`league_id` = l.`id`',
            'm.`id` = r.`match_id`',
            'r.`id` = rp.`rubber_id`',
            $this->wpdb->prepare( 'rp.`player_id` = %d', $player_id ),
        ];

        if ( ! empty( $args['season'] ) ) {
            $where[] = $this->wpdb->prepare( 'm.`season` = %s', $args['season'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where[] = $this->wpdb->prepare( 'm.`confirmed` = %s', $args['status'] );
        }
        if ( ! empty( $args['time'] ) ) {
            if ( 'latest' === $args['time'] ) {
                $where[] = "(m.`home_points` != '' OR m.`away_points` != '')";
            } elseif ( is_numeric( $args['time'] ) ) {
                $where[] = $this->wpdb->prepare( 'm.`date` > NOW() - INTERVAL %d DAY', (int) $args['time'] );
            }
        }
        if ( ! empty( $args['type'] ) ) {
            $escaped_type = method_exists( $this->wpdb, 'esc_like' ) ? $this->wpdb->esc_like( $args['type'] ) : addcslashes( $args['type'], '_%\\' );
            $type_like    = '%' . $escaped_type . '%';
            $where[]      = $this->wpdb->prepare(
                "m.`league_id` IN (SELECT id FROM `$leagues_table` WHERE `event_id` IN (SELECT e.`id` FROM `$events_table` e WHERE e.`type` LIKE %s))",
                $type_like
            );
        }

        $sql = "SELECT DISTINCT m.*" . $sql_from . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY m.`date` ASC';
        $results = $this->wpdb->get_results( $sql );
        if ( ! is_array( $results ) ) {
            return [];
        }

        return array_map( function( $row ) use ( $player_id ) {
            $fixture = $this->hydrate_fixture( $row, true );
            $rubbers = $this->rubber_repository->find_by_fixture_id( (int) $fixture->id, $player_id );
            $fixture->set_rubbers( $rubbers );
            return $fixture;
        }, $results );
    }

    /**
     * Hydrate a fixture aggregate from a row, optionally attaching league and team relations.
     *
     * Relations are loaded outside the entity by the relation loader so that the
     * aggregate itself stays free of repository and service collaborators.
     */
    private function hydrate_fixture( object $row, bool $with_relations = false ): Fixture {
        $fixture = Fixture_Factory::from_object( $row );
        if ( $with_relations && $this->relation_loader ) {
            $this->relation_loader->load( $fixture );
        }

        return $fixture;
    }

    /**
     * Get matches without using a league object.
     *
     * @param array $match_args query arguments.
     *
     * @return array|int $matches
     */
    public function find_all( array $match_args = array() ): array|int {
        $defaults = array(
            'leagueId'            => false,
            'season'              => false,
            'final'               => false,
            'competition_type'    => false,
            'orderby'             => array(
                'league_id' => 'ASC',
                'id'        => 'ASC',
            ),
            'competition_id'      => false,
            'event_id'            => false,
            'confirmed'           => false,
            'match_date'          => false,
            'time'                => false,
            'timeOffset'          => false,
            'history'             => false,
            'club'                => false,
            'league_name'         => false,
            'team'                => false,
            'team_name'           => false,
            'home_team'           => false,
            'home_club'           => false,
            'away_team'           => false,
            'match_day'           => false,
            'competition_name'    => false,
            'count'               => false,
            'confirmationPending' => false,
            'resultPending'       => false,
            'status'              => false,
            'tournament_id'       => false,
            'player'              => false,
            'type'                => false,
            'complete'            => false,
            'age_group'           => false,
        );

        $match_args           = array_merge( $defaults, $match_args );
        $league_id            = $match_args['leagueId'];
        $season               = $match_args['season'];
        $final                = $match_args['final'];
        $competition_type     = $match_args['competition_type'];
        $orderby              = $match_args['orderby'];
        $competition_id       = $match_args['competition_id'];
        $event_id             = $match_args['event_id'];
        $confirmed            = $match_args['confirmed'];
        $match_date           = $match_args['match_date'];
        $time                 = $match_args['time'];
        $time_offset          = $match_args['timeOffset'];
        $history              = $match_args['history'];
        $club                 = $match_args['club'];
        $league_name          = $match_args['league_name'];
        $team                 = $match_args['team'];
        $team_name            = $match_args['team_name'];
        $home_team            = $match_args['home_team'];
        $home_club            = $match_args['home_club'];
        $away_team            = $match_args['away_team'];
        $match_day            = $match_args['match_day'];
        $competition_name     = $match_args['competition_name'];
        $count                = $match_args['count'];
        $confirmation_pending = $match_args['confirmationPending'];
        $result_pending       = $match_args['resultPending'];
        $status               = $match_args['status'];
        $tournament_id        = $match_args['tournament_id'];
        $player               = $match_args['player'];
        $type                 = $match_args['type'];
        $complete             = $match_args['complete'];
        $age_group            = $match_args['age_group'];

        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $competitions_table   = $this->wpdb->prefix . 'racketmanager_competitions';
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $tournaments_table    = $this->wpdb->prefix . 'racketmanager_tournaments';
        $team_players_table   = $this->wpdb->prefix . 'racketmanager_team_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';

        $sql_from = " FROM {$this->table_name} AS m, {$leagues_table} AS l";
        if ( $count ) {
            $sql        = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1 = 1";
            $sql_fields = '';
        } else {
            $sql_fields = "SELECT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, m.`confirmed`, m.`home_captain`, m.`away_captain`, m.`comments`, m.`updated`, `event_id`, m.`status`, `leg`, `winner_id_tie`, `loser_id_tie`";
            $sql        = ' WHERE m.`league_id` = l.`id`';
        }

        if ( $match_date ) {
            $sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', `date`) = 0";
        }
        if ( $competition_name ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` in (SELECT e.`id` FROM {$events_table} e, {$competitions_table} c WHERE c.`name` = '" . $competition_name . "' AND e.`competition_id` = c.`id`))";
        }
        if ( $competition_id ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` IN (select `id` from {$events_table} WHERE `competition_id` = '" . $competition_id . "') )";
        }
        if ( $event_id ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` = '" . $event_id . "')";
        }
        if ( $league_id ) {
            $sql .= " AND `league_id`  = '" . $league_id . "'";
        }
        if ( $league_name ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `title` = '" . $league_name . "')";
        }
        if ( $season ) {
            $sql .= " AND `season`  = '" . $season . "'";
        }
        if ( $final ) {
            if ( 'all' === $final ) {
                $sql .= " AND `final` != ''";
            } else {
                $sql .= " AND `final`  = '" . $final . "'";
            }
        }
        if ( $competition_type ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` in (select e.`id` from {$events_table} e, {$competitions_table} c WHERE e.`competition_id` = c.`id` AND c.`type` = '" . $competition_type . "'))";
        }
        if ( $tournament_id ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` in (SELECT e.`id` FROM {$events_table} e, {$competitions_table} c, {$tournaments_table} t WHERE e.`competition_id` = c.`id` AND c.`id` = t.`competition_id` AND t.`id` = " . $tournament_id . '))';
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . ':00:00';
        } else {
            $time_offset = '00:00:00';
        }
        if ( $status ) {
            $sql .= " AND `confirmed` = '" . $status . "'";
        }
        if ( $confirmed ) {
            $sql .= " AND `confirmed` in ('P','A','C')";
            if ( $time_offset ) {
                $sql .= " AND ADDTIME(`updated`,'" . $time_offset . "') <= NOW()";
            }
        }
        if ( $confirmation_pending ) {
            $confirmation_pending = intval( $confirmation_pending ) . ':00:00';
            $sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $result_pending = intval( $result_pending ) . ':00:00';
            $sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $time ) {
            if ( 'latest' === $time ) {
                $sql .= " AND (`home_points` != '' OR `away_points` != '')";
            } elseif ( 'outstanding' === $time ) {
                $sql .= " AND ADDTIME(`date`,'" . $time_offset . "') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
            } elseif ( is_numeric( $time ) ) {
                $sql .= ' AND m.`date` > now() - INTERVAL ' . $time . ' DAY';
            }
        }
        if ( $history ) {
            $sql .= ' AND `updated` >= NOW() - INTERVAL ' . $history . ' DAY';
        }

        if ( $club ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM {$teams_table} WHERE `club_id` = " . $club . ") OR `away_team` IN (SELECT `id` FROM {$teams_table} WHERE `club_id` = " . $club . '))';
        }
        if ( $team ) {
            $sql .= ' AND (`home_team` = ' . $team . ' OR `away_team` = ' . $team . ')';
        }
        if ( $home_club ) {
            $sql .= " AND `home_team` IN (SELECT `id` FROM {$teams_table} WHERE `club_id` = " . $home_club . ')';
        }
        if ( ! empty( $home_team ) ) {
            $sql .= ' AND `home_team` = ' . $home_team . ' ';
        }
        if ( ! empty( $away_team ) ) {
            $sql .= ' AND `away_team` = ' . $away_team . ' ';
        }
        if ( ! empty( $team_name ) ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM {$teams_table} WHERE `title` LIKE '%" . $team_name . "%') OR `away_team` IN (SELECT `id` FROM {$teams_table} WHERE `title` LIKE '%" . $team_name . "%'))";
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            $sql .= ' AND `match_day` = ' . $match_day . ' ';
        }
        if ( $player ) {
            if ( $tournament_id ) {
                $sql_from .= " ,{$team_players_table} tp";
                $sql      .= " AND ((m.`home_team` = tp.`team_id` AND tp.`player_id` = '$player') OR (m.`away_team` = tp.`team_id` AND tp.`player_id` = '$player'))";
            } else {
                $sql_from .= " ,{$rubbers_table} r, {$rubber_players_table} rp";
                $sql      .= " AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
            }
        }
        if ( $type ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` in (select e.`id` from {$events_table} e WHERE e.`type` like '%%" . $type . "%%'))";
        }
        if ( $complete ) {
            $sql .= ' AND m.`winner_id` != 0';
        }
        if ( $age_group ) {
            $sql .= " AND `league_id` in (select `id` from {$leagues_table} WHERE `event_id` in (SELECT e.`id` FROM {$events_table} e, {$competitions_table} c WHERE e.`competition_id` = c.`id` and `age_group` = '" . $age_group . "'))";
        }
        if ( $count ) {
            return intval(
                $this->wpdb->get_var( $sql )
            );
        }
        $order   = Util::order_by_string( $orderby );
        $sql     = $sql_fields . $sql_from . $sql . $order;
        $matches = wp_cache_get( md5( $sql ), 'matches' );
        if ( ! $matches ) {
            $matches = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $matches, 'matches' );
        }
        if ( ! is_array( $matches ) ) {
            return array();
        }
        $hydrated_matches = array();
        foreach ( $matches as $i => $match ) {
            $match_id = (int) ( is_object( $match ) ? $match->id : $match );
            $fixture  = $this->find_by_id( $match_id );
            if ( $player && $fixture ) {
                $rubber_repo = $this->rubber_repository ?? new Rubber_Repository();
                $fixture->set_rubbers( $rubber_repo->find_by_fixture_id( (int) $fixture->get_id(), (int) $player ) );
            }
            if ( $fixture ) {
                $hydrated_matches[ $i ] = $fixture;
            }
        }
        return $hydrated_matches;
    }

    /**
     * Helper to get team ID by title.
     *
     * @param string $title
     * @return int
     */
    private function get_team_id_by_title( string $title ): int {
        if ( $this->team_repository ) {
            return $this->team_repository->find_id_by_title( $title );
        }

        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        return (int) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM $teams_table WHERE title = %s",
            $title
        ) );
    }
}
