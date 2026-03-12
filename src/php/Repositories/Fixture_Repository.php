<?php
/**
 * Fixture_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Fixture;
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
            'updated_user'        => $fixture->get_updated_user(),
            'updated'             => $fixture->get_updated(),
            'date_result_entered' => $fixture->get_date_result_entered(),
            'confirmed'           => $fixture->get_confirmed(),
            'home_captain'        => $fixture->get_home_captain(),
            'away_captain'        => $fixture->get_away_captain(),
            'comments'            => $fixture->get_comments(),
        );

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
            '%d', // updated_user
            '%s', // updated
            '%s', // date_result_entered
            '%s', // confirmed
            '%d', // home_captain
            '%d', // away_captain
            '%s', // comments
        );

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

    public function find_by_id( $fixture_id ): ?Fixture {
        if ( empty( $fixture_id ) ) {
            return null;
        }
        $fixture = wp_cache_get( $fixture_id, 'fixtures' );

        if ( ! $fixture ) {
            $fixture = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $fixture_id
                )
            );

            if ( ! $fixture ) {
                return null;
            }
            $fixture = new Fixture( $fixture );

            wp_cache_set( $fixture->id, $fixture, 'fixtures' );
        }

        return $fixture;
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

    public function find_fixtures_by_round_by_league_by_tournament( int $tournament_id, int $league_id, string $round ): array {
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';

        $query   = $this->wpdb->prepare(
            "
                SELECT DISTINCT f.*
                FROM `$this->table_name` f
                    JOIN $leagues_table l on f.league_id = l.id
                    JOIN $events_table e on l.event_id = e.id
                    JOIN $tournaments_table t on t.competition_id = e.competition_id
                WHERE f.season = t.season
                  AND t.id = %d
                  AND f.league_id = %d
                  AND f.final = %s
                ORDER BY f.id
                      ",
            $tournament_id,
            $league_id,
            $round
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
}
