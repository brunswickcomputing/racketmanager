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
use wpdb;

/**
 * Class to implement the fixture repository
 */
class Fixture_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_matches';
    }

    public function save( Fixture $fixture ): bool {
        $data = array(
            'group'               => $fixture->get_group(),
            'date'                => $fixture->get_date(),
            'date_original'       => $fixture->get_date_original(),
            'home_team'           => $fixture->get_home_team(),
            'away_team'           => $fixture->get_away_team(),
            'match_day'           => $fixture->get_match_day(),
            'location'            => $fixture->get_location(),
            'host'                => $fixture->get_host(),
            'league_id'           => $fixture->get_league_id(),
            'season'              => $fixture->get_season(),
            'home_points'         => $fixture->get_home_points(),
            'away_points'         => $fixture->get_away_points(),
            'winner_id'           => $fixture->get_winner_id(),
            'loser_id'            => $fixture->get_loser_id(),
            'status'              => $fixture->get_status(),
            'linked_match'        => $fixture->get_linked_match(),
            'leg'                 => $fixture->get_leg(),
            'winner_id_tie'       => $fixture->get_winner_id_tie(),
            'loser_id_tie'        => $fixture->get_loser_id_tie(),
            'home_points_tie'     => $fixture->get_home_points_tie(),
            'away_points_tie'     => $fixture->get_away_points_tie(),
            'post_id'             => $fixture->get_post_id(),
            'final'               => $fixture->get_final(),
            'custom'              => maybe_serialize( $fixture->get_custom() ),
            'confirmed'           => $fixture->get_confirmed(),
            'home_captain'        => $fixture->get_home_captain(),
            'away_captain'        => $fixture->get_away_captain(),
            'comments'            => $fixture->get_comments(),
            'updated_by'          => $fixture->get_updated_by(),
            'updated'             => current_time( 'mysql' ),
            'updated_user'        => get_current_user_id(),
        );

        if ( ! empty( $fixture->get_home_points() ) || ! empty( $fixture->get_away_points() ) ) {
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
            '%s', // updated_by
            '%s', // updated
            '%d', // updated_user
        );

        if ( isset( $data['date_result_entered'] ) ) {
            $format[] = '%s';
        }

        if ( empty( $fixture->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $format
            );
            if ( $inserted ) {
                $fixture->set_id( $this->wpdb->insert_id );
            }

            return (bool) $inserted;
        } else {
            $updated = $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $fixture->get_id()
                ),
                $format,
                array( '%d' )
            );

            return $updated !== false && $updated > 0;
        }
    }

    /**
     * Delete a fixture by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete( int $id ): bool {
        $deleted = $this->wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            wp_cache_delete( $id, 'fixtures' );
            wp_cache_delete( $id . '_legacy', 'fixtures' );
        }

        return $deleted !== false;
    }

    /**
     * Insert a new fixture.
     *
     * @param array $data
     * @param array|null $format
     * @return int|bool The insert ID or false on failure.
     */
    public function insert( array $data, ?array $format = null ) {
        $inserted = $this->wpdb->insert( $this->table_name, $data, $format );

        if ( $inserted ) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    public function find_by_id( $fixture_id ): ?Fixture {
        $row = $this->find_raw_by_id( $fixture_id );

        if ( ! $row ) {
            return null;
        }

        return new Fixture( $row );
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
            fn( $row ) => new Fixture( $row ),
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
            $row = new Fixture( $row );
        }

        return $results;
    }

    public function delete_by_league_and_season( int $league_id, string $season ): void {
        $this->wpdb->delete(
            $this->table_name,
            array(
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%d', '%s' )
        );
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
            fn( $row ) => new Fixture( $row ),
            $results
        );
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
}
