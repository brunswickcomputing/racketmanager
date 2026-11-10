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
use Racketmanager\Exceptions\Rubber_Repository_Missing_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use wpdb;


/**
 * Class to implement the fixture repository
 */
class Fixture_Repository implements Fixture_Repository_Interface {
    /**
     * SQL WHERE clause keyword with surrounding whitespace.
     */
    private const string SQL_WHERE = ' WHERE ';

    /**
     * WordPress database object.
     *
     * @var wpdb
     */
    private wpdb $wpdb;

    /**
     * Matches database table name.
     *
     * @var string
     */
    private string $table_name;

    /**
     * Fixture relation loader service.
     *
     * @var Fixture_Relation_Loader|null
     */
    private ?Fixture_Relation_Loader $relation_loader;

    /**
     * Rubber repository.
     *
     * @var Rubber_Repository_Interface|null
     */
    private ?Rubber_Repository_Interface $rubber_repository;

    /**
     * League repository.
     *
     * @var League_Repository_Interface|null
     */
    private ?League_Repository_Interface $league_repository;

    /**
     * Team repository.
     *
     * @var Team_Repository_Interface|null
     */
    private ?Team_Repository_Interface $team_repository;

    /**
     * Constructor.
     *
     * @param wpdb|null                        $db                Database object.
     * @param Fixture_Relation_Loader|null     $relation_loader   Relation loader.
     * @param Rubber_Repository_Interface|null $rubber_repository Rubber repository.
     * @param League_Repository_Interface|null $league_repository League repository.
     * @param Team_Repository_Interface|null   $team_repository   Team repository.
     */
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

    /**
     * Save a fixture entity.
     *
     * @param object $entity The fixture entity to save.
     * @return bool|int The fixture ID on insert, true if an update changed a row, or false if no rows changed or the operation failed.
     */
    public function save( object $entity ): bool|int {
        /** @var Fixture $entity */
        $data = array(
            'group'           => $entity->get_group(),
            'date'            => Fixture_Time_Parser::to_mysql_datetime( $entity->get_date() ),
            'date_original'   => Fixture_Time_Parser::to_mysql_datetime( $entity->get_date_original() ),
            'home_team'       => $entity->get_home_team(),
            'away_team'       => $entity->get_away_team(),
            'match_day'       => $entity->get_match_day(),
            'location'        => $entity->get_location(),
            'host'            => $entity->get_host(),
            'league_id'       => $entity->get_league_id(),
            'season'          => $entity->get_season(),
            'home_points'     => $entity->get_home_points(),
            'away_points'     => $entity->get_away_points(),
            'winner_id'       => $entity->get_winner_id(),
            'loser_id'        => $entity->get_loser_id(),
            'status'          => $entity->get_status(),
            'linked_match'    => $entity->get_linked_fixture(),
            'leg'             => $entity->get_leg(),
            'winner_id_tie'   => $entity->get_winner_id_tie(),
            'loser_id_tie'    => $entity->get_loser_id_tie(),
            'home_points_tie' => $entity->get_home_points_tie(),
            'away_points_tie' => $entity->get_away_points_tie(),
            'post_id'         => $entity->get_post_id(),
            'final'           => $entity->get_final(),
            'custom'          => maybe_serialize( $entity->get_custom() ),
            'confirmed'       => $entity->get_confirmed(),
            'home_captain'    => $entity->get_home_approver(),
            'away_captain'    => $entity->get_away_approver(),
            'comments'        => maybe_serialize( $entity->get_comments() ),
            'updated'         => current_time( 'mysql' ),
            'updated_user'    => get_current_user_id(),
        );

        if ( ! empty( $entity->get_home_points() ) || ! empty( $entity->get_away_points() ) ) {
            $data['date_result_entered'] = current_time( 'mysql' );
        }

        $format = array(
            '%s', // group.
            '%s', // date.
            '%s', // date_original.
            '%s', // home_team.
            '%s', // away_team.
            '%d', // match_day.
            '%s', // location.
            '%s', // host.
            '%d', // league_id.
            '%s', // season.
            '%s', // home_points.
            '%s', // away_points.
            '%d', // winner_id.
            '%d', // loser_id.
            '%d', // status.
            '%d', // linked_match.
            '%d', // leg.
            '%d', // winner_id_tie.
            '%d', // loser_id_tie.
            '%f', // home_points_tie.
            '%f', // away_points_tie.
            '%d', // post_id.
            '%s', // final.
            '%s', // custom.
            '%s', // confirmed.
            '%d', // home_captain.
            '%d', // away_captain.
            '%s', // comments.
            '%s', // updated.
            '%d', // updated_user.
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
                    'id' => $entity->get_id(),
                ),
                $format,
                array( '%d' )
            );
        }
    }

    /**
     * Delete a fixture by ID, including its associated rubbers.
     *
     * @param int $id The fixture ID.
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        // Delete associated rubbers first.
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
     * @param int $id The fixture ID.
     * @return bool True on success, false on failure.
     */
    public function delete_fixture( int $id ): bool {
        return $this->delete( $id );
    }

    /**
     * Find a fixture by its ID.
     *
     * @param int $id The fixture ID.
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_by_id( $id ): ?Fixture {
        $row = $this->find_raw_by_id( $id );

        if ( ! $row ) {
            return null;
        }

        return $this->hydrate_fixture( $row );
    }

    /**
     * Find a fixture by ID and attach league and team relations.
     *
     * @param int $id The fixture ID.
     * @return Fixture|null The fixture object with relations, or null if not found.
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
     * @param int  $fixture_id The fixture ID.
     * @param bool $legacy     Whether to include legacy date formatting fields.
     * @return object|null The raw fixture database object, or null if not found.
     */
    public function find_raw_by_id( int $fixture_id, bool $legacy = false ): ?object {
        if ( empty( $fixture_id ) ) {
            return null;
        }

        $cache_key = $legacy ? $fixture_id . '_legacy' : $fixture_id;
        $fixture   = wp_cache_get( $cache_key, 'fixtures' );

        if ( ! $fixture ) {
            $select = '*';
            if ( $legacy ) {
                $select = "`final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date_original`, '%Y-%m-%d %H:%i') AS date_original, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `updated`, `updated_user`, `confirmed`, `home_captain`, `away_captain`, `comments`, `status`, `host`, `linked_match`, `leg`, `winner_id_tie`, `loser_id_tie`, `home_points_tie`, `away_points_tie`, `updated`, `date_result_entered`";
            }

            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and field interpolation.
                "SELECT $select FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $fixture_id
            );
            $fixture = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

            if ( $fixture ) {
                wp_cache_set( $cache_key, $fixture, 'fixtures' );
            }
        }

        return $fixture ? (object) $fixture : null;
    }

    /**
     * Find finals fixtures for a tournament.
     *
     * @param int $tournament_id The tournament ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_finals_fixtures_for_tournament( int $tournament_id ): array {
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
        $query = $this->wpdb->prepare(
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
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

        return array_map(
            fn( $row ): ?Fixture => $this->hydrate_fixture( $row, true ),
            $results
        );
    }

    /**
     * Find fixtures for a player in a tournament.
     *
     * @param int $player_id     The player ID.
     * @param int $tournament_id The tournament ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_fixtures_for_player_by_tournament( int $player_id, int $tournament_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
        $query = $this->wpdb->prepare(
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
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        foreach ( $results as &$row ) {
            $row = $this->hydrate_fixture( $row, true );
        }

        return $results;
    }

    /**
     * Delete fixtures by league and season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
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
     * @param int      $league_id  The league ID.
     * @param string   $season     The season identifier.
     * @param string   $final_key  The final round key.
     * @param int|null $leg        Optional leg number.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_league_and_final( int $league_id, string $season, string $final_key, ?int $leg = null ): array {
        $query = "SELECT * FROM $this->table_name WHERE `league_id` = %d AND `season` = %s AND `final` = %s";
        $args  = [ $league_id, $season, $final_key ];

        if ( null !== $leg ) {
            $query .= ' AND `leg` = %d';
            $args[] = $leg;
        }

        $query .= ' ORDER BY `id` ASC';

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query template with table name interpolation.
        $sql     = $this->wpdb->prepare( $query, ...$args );
        $results = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

        return array_map(
            fn( $row ): ?Fixture => $this->hydrate_fixture( $row ),
            $results
        );
    }
    /**
     * Find fixtures by league, season, and team ID (can be numeric or placeholder).
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param string $team_id   The team ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_league_and_team( int $league_id, string $season, string $team_id ): array {
        $query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT * FROM $this->table_name WHERE `league_id` = %d AND `season` = %s AND (`home_team` = %s OR `away_team` = %s)",
            $league_id,
            $season,
            $team_id,
            $team_id
        );
        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

        return array_map(
            fn( $row ): ?Fixture => $this->hydrate_fixture( $row, true ),
            $results
        );
    }

    /**
     * Find fixtures by team ID (either home or away).
     *
     * @param int $team_id The team ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_team( int $team_id ): array {
        $query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT * FROM $this->table_name WHERE `home_team` = %s OR `away_team` = %s",
            (string) $team_id,
            (string) $team_id
        );
        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

        return array_map(
            fn( $row ): ?Fixture => $this->hydrate_fixture( $row ),
            $results ?: array()
        );
    }

    /**
     * Delete all fixtures and their rubbers for a given team ID.
     *
     * @param int $team_id The team ID.
     * @return bool True on success, false on failure.
     */
    public function delete_by_team( int $team_id ): bool {
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
     * @param string $season         The season identifier.
     * @param int    $match_day      The match day number.
     * @param int    $league_id      The league ID.
     * @param int    $club_player_id The club player ID.
     * @return int Total number of matches played on the same day.
     */
    public function count_player_matches_on_same_day( string $season, int $match_day, int $league_id, int $club_player_id ): int {
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
        $query = $this->wpdb->prepare(
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
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
    }

    /**
     * Update status for a fixture.
     *
     * @param int $id     The fixture ID.
     * @param int $status The fixture status integer.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Update home and away team IDs for a fixture.
     *
     * @param int    $id        The fixture ID.
     * @param string $home_team The home team ID.
     * @param string $away_team The away team ID.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Update fixture match date and optional original date.
     *
     * @param int         $id            The fixture ID.
     * @param string      $date          The match date string.
     * @param string|null $original_date Optional original match date string.
     * @return bool True on success, false on failure.
     */
    public function update_date( int $id, string $date, ?string $original_date = null ): bool {
        $canonical_date = Fixture_Time_Parser::to_mysql_datetime( $date );
        $data           = array( 'date' => $canonical_date );
        $format         = array( '%s' );

        if ( null !== $original_date ) {
            $data['date_original'] = Fixture_Time_Parser::to_mysql_datetime( $original_date );
            $format[]              = '%s';
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

    /**
     * Update post ID for a fixture.
     *
     * @param int $id      The fixture ID.
     * @param int $post_id The associated WordPress post ID.
     * @return bool True on success, false on failure.
     */
    public function update_post_id( int $id, int $post_id ): bool {
        if ( $id <= 0 ) {
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table_name,
            array( 'post_id' => $post_id ),
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

    /**
     * Find fixture by associated WordPress post ID.
     *
     * @param int $post_id The associated WordPress post ID.
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_by_post( int $post_id ): ?Fixture {
        if ( $post_id <= 0 ) {
            return null;
        }

        $query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM $this->table_name WHERE `post_id` = %d LIMIT 1",
            $post_id
        );
        $row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if ( ! $row ) {
            return null;
        }

        return $this->hydrate_fixture( $row );
    }

    /**
     * Find fixtures matching export criteria.
     *
     * @param Export_Criteria $criteria The export criteria DTO.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_criteria( Export_Criteria $criteria ): array {
        $sql   = "SELECT id FROM $this->table_name AS m";
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
            $where[]      = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "m.league_id IN (SELECT id FROM $league_table WHERE event_id IN (SELECT id FROM $event_table WHERE competition_id = %d))",
                $criteria->competition_id
            );
        }

        if ( $criteria->club_id ) {
            $team_table = $this->wpdb->prefix . 'racketmanager_teams';
            $where[]    = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
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

        $sql .= self::SQL_WHERE . implode( ' AND ', $where );
        $sql .= ' ORDER BY m.match_day ASC, m.date ASC';

        $results = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

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
     * Find a single fixture matching slug criteria.
     *
     * @param array $criteria The query criteria containing league, team slugs, etc.
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_one_by_slug_criteria( array $criteria ): ?Fixture {
        $league     = $this->resolve_league_by_slug( $criteria['league_slug'] ?? $criteria['league_id'] ?? null );
        $query_args = $league ? $this->build_slug_query_args( $league, $criteria ) : null;
        if ( ! $league || ! $query_args ) {
            return null;
        }

        $rows = $this->league_repository->query_matches( (int) $league->id, $query_args );
        if ( empty( $rows ) || ! is_array( $rows ) || 1 !== count( $rows ) ) {
            return null;
        }

        return $this->hydrate_fixture( $rows[0], true );
    }

    /**
     * Resolve a league entity by slug or numeric ID.
     *
     * @param mixed $league_slug League slug or ID.
     * @return object|null League object or null if unresolved.
     */
    private function resolve_league_by_slug( mixed $league_slug ): ?object {
        if ( ! $this->league_repository || empty( $league_slug ) ) {
            return null;
        }

        $league = null;
        if ( is_numeric( $league_slug ) ) {
            $league = $this->league_repository->find_by_id( (int) $league_slug );
        } elseif ( is_string( $league_slug ) ) {
            $league = $this->league_repository->find_by_name( $league_slug );
        }

        return ( $league && ! empty( $league->id ) ) ? $league : null;
    }

    /**
     * Build match query arguments for slug criteria.
     *
     * @param object $league   League object.
     * @param array  $criteria Query criteria.
     * @return array|null Query arguments array, or null if a specified team could not be resolved.
     */
    private function build_slug_query_args( object $league, array $criteria ): ?array {
        $home_team_name = $criteria['home_team_slug'] ?? null;
        $home_team_id   = null;
        if ( $home_team_name ) {
            $home_team_id = $this->get_team_id_by_title( $home_team_name );
            if ( ! $home_team_id ) {
                return null;
            }
        }

        $away_team_name = $criteria['away_team_slug'] ?? null;
        $away_team_id   = null;
        if ( $away_team_name ) {
            $away_team_id = $this->get_team_id_by_title( $away_team_name );
            if ( ! $away_team_id ) {
                return null;
            }
        }

        return array(
            'season'          => $criteria['season'] ?? null,
            'home_team'       => $home_team_id,
            'away_team'       => $away_team_id,
            'match_day'       => $criteria['match_day'] ?? null,
            'final'           => $criteria['round'] ?? null,
            'leg'             => $criteria['leg'] ?? null,
            'is_championship' => ! empty( $league->is_championship ),
            'current_season'  => $league->current_season['name'] ?? null,
        );
    }

    /**
     * Find all confirmed fixtures played by a specific player with optional filters.
     *
     * @param int   $player_id The player ID.
     * @param array $args      Optional filter arguments (season, time, type, status).
     * @return Fixture[] Array of fixture objects.
     * @throws Rubber_Repository_Missing_Exception If Rubber_Repository_Interface is not set.
     */
    public function find_player_matches( int $player_id, array $args = [] ): array {
        if ( ! $this->rubber_repository ) {
            throw new Rubber_Repository_Missing_Exception( 'Fixture_Repository::find_player_matches requires a Rubber_Repository_Interface.' );
        }

        $defaults = [
            'season' => null,
            'time'   => null,
            'type'   => null,
            'status' => null,
        ];
        $args     = array_merge( $defaults, $args );

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
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "m.`league_id` IN (SELECT id FROM `$leagues_table` WHERE `event_id` IN (SELECT e.`id` FROM `$events_table` e WHERE e.`type` LIKE %s))",
                $type_like
            );
        }

        $sql     = 'SELECT DISTINCT m.*' . $sql_from . self::SQL_WHERE . implode( ' AND ', $where ) . ' ORDER BY m.`date` ASC';
        $results = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        if ( ! is_array( $results ) ) {
            return [];
        }

        return array_map(
            function ( $row ) use ( $player_id ): ?Fixture {
                $fixture = $this->hydrate_fixture( $row, true );
                $rubbers = $this->rubber_repository->find_by_fixture_id( (int) $fixture->id, $player_id );
                $fixture->set_rubbers( $rubbers );
                return $fixture;
            },
            $results
        );
    }

    /**
     * Hydrate a fixture aggregate from a row, optionally attaching league and team relations.
     *
     * Relations are loaded outside the entity by the relation loader so that the
     * aggregate itself stays free of repository and service collaborators.
     *
     * @param object $row            The database row.
     * @param bool   $with_relations Whether to hydrate related entities.
     * @return Fixture
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
     * Note: Legacy filter values are currently interpolated directly into this query without preparation.
     */
    public function find_all( array $match_args = array() ): array|int {
        $query = new Fixture_Find_All_Query(
            $this->wpdb,
            $this->table_name,
            $this,
            $this->rubber_repository
        );

        return $query->execute( $match_args );
    }

    /**
     * Helper to get team ID by title.
     *
     * @param string $title Team title.
     * @return int Team ID.
     */
    private function get_team_id_by_title( string $title ): int {
        if ( $this->team_repository ) {
            return $this->team_repository->find_id_by_title( $title );
        }

        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $query       = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT id FROM $teams_table WHERE title = %s",
            $title
        );
        return (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
    }
}
