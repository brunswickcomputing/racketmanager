<?php
/**
 * Fixture_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Export\DTO\Export_Criteria;
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

    public function __construct(
        ?wpdb $db = null,
        ?Fixture_Relation_Loader $relation_loader = null,
        ?Rubber_Repository_Interface $rubber_repository = null
    ) {
        global $wpdb;
        $this->wpdb       = $db ?? $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_matches';
        $this->relation_loader = $relation_loader;
        $this->rubber_repository = $rubber_repository;
    }

    public function save( object $entity ): bool|int {
        /** @var Fixture $entity */
        $data = array(
            'group'               => $entity->get_group(),
            'date'                => $entity->get_date(),
            'date_original'       => $entity->get_date_original(),
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
        $data = array( 'date' => $date );
        $format = array( '%s' );

        if ( null !== $original_date ) {
            $data['date_original'] = $original_date;
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
        $league_id      = $criteria['league_slug'] ?? null;
        $home_team_name = $criteria['home_team_slug'] ?? null;
        $away_team_name = $criteria['away_team_slug'] ?? null;
        $season         = $criteria['season'] ?? null;
        $match_day      = $criteria['match_day'] ?? null;
        $round          = $criteria['round'] ?? null;
        $leg            = $criteria['leg'] ?? null;

        if ( ! $league_id ) {
            return null;
        }

        $match_args = [
//            'id'     => $league_id, //TODO: not needed for league->get_matches but will be for refactor
            'season' => $season,
        ];

        if ( $home_team_name ) {
            $home_team_id = $this->get_team_id_by_title( $home_team_name );
            if ( $home_team_id ) {
                $match_args['home_team'] = $home_team_id;
            }
        }

        if ( $away_team_name ) {
            $away_team_id = $this->get_team_id_by_title( $away_team_name );
            if ( $away_team_id ) {
                $match_args['away_team'] = $away_team_id;
            }
        }

        if ( $match_day ) {
            $match_args['match_day'] = $match_day;
        }

        if ( $round ) {
            $match_args['final'] = $round;
        }

        if ( $leg ) {
            $match_args['leg'] = $leg;
        }

        $league = \Racketmanager\get_league( $league_id );
        if ( ! $league ) {
            return null;
        }

        $matches = $league->get_matches( $match_args );
        if ( $matches instanceof Fixture ) {
            return $matches;
        }

        if ( is_array( $matches ) && count( $matches ) === 1 ) {
            $row = $matches[0];
            if ( $row instanceof Fixture ) {
                return $row;
            }
            return $this->find_by_id( (int) $row->id );
        }

        if ( is_object( $matches ) && isset( $matches->id ) ) {
            return $this->find_by_id( (int) $matches->id );
        }

        return null;
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
        $leagues_table        = $this->wpdb->prefix . 'racketmanager';
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
     * Helper to get team ID by title.
     *
     * @param string $title
     * @return int
     */
    private function get_team_id_by_title( string $title ): int {
        global $racketmanager;
        if ( isset( $racketmanager ) && method_exists( $racketmanager, 'get_team_id' ) ) {
            return $racketmanager->get_team_id( $title );
        }

        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        return (int) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM $teams_table WHERE title = %s",
            $title
        ) );
    }
}
