<?php
/**
 * League_Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\League_Team;
use wpdb;

/**
 * Class to implement the League team repository
 */
class League_Team_Repository {
    private wpdb $wpdb;
    private string $table_name;
    private string $season_compare;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->season_compare = ' AND `season` = %d';
        $this->table_name = $this->wpdb->prefix . 'racketmanager_league_teams';
    }

    public function save( League_Team $league_team ): int|bool {
        $data = array(
            'team_id'        => $league_team->get_team_id(),
            'league_id'      => $league_team->get_league_id(),
            'season'         => $league_team->get_season(),
            'captain'        => $league_team->get_captain(),
            'match_day'      => $league_team->get_match_day(),
            'match_time'     => $league_team->get_match_time(),
            'points_plus'    => $league_team->get_points_plus(),
            'points_minus'   => $league_team->get_points_minus(),
            'points_2_plus'  => $league_team->get_points_2_plus(),
            'points_2_minus' => $league_team->get_points_2_minus(),
            'add_points'     => $league_team->get_add_points(),
            'done_matches'   => $league_team->get_done_matches(),
            'won_matches'    => $league_team->get_won_matches(),
            'draw_matches'   => $league_team->get_drawn_matches(),
            'lost_matches'   => $league_team->get_lost_matches(),
            'diff'           => $league_team->get_diff(),
            'group'          => $league_team->get_group(),
            'rank'           => $league_team->get_rank(),
            'profile'        => $league_team->get_profile(),
            'status'         => $league_team->get_status(),
            'rating'         => $league_team->get_rating(),
            'custom'         => maybe_serialize( $league_team->get_custom() ),
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
        if ( empty( $league_team->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format
            );
            $league_team->set_id( $this->wpdb->insert_id );
            wp_cache_set( $league_team->get_id(), $league_team, 'league-teams' );
            return $result !== false;
        } else {
            wp_cache_set( $league_team->get_id(), $league_team, 'league-teams' );
            return $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $league_team->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( $league_team_id ): ?League_Team {
        if ( empty( $league_team_id ) ) {
            return null;
        }
        $league_team = wp_cache_get( $league_team_id, 'league-teams' );

        if ( ! $league_team ) {
            $league_team = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $league_team_id
                )
            );

            if ( ! $league_team ) {
                return null;
            }
            $league_team = new League_Team( $league_team );

            wp_cache_set( $league_team->id, $league_team, 'league-teams' );
        }

        return $league_team;
    }

    /**
     * Finds all Club IDs where the given player is assigned as a team captain.
     *
     * @param int $player_id
     * @param int|null $club_id
     *
     * @return int[] Array of Club IDs.
     */
    public function find_club_ids_where_player_is_captain( int $player_id, ?int $club_id ):array {
        // Need to join the league team entry table with the main teams table to get the club_id
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $sql = "SELECT DISTINCT t.club_id FROM $this->table_name lte INNER JOIN $teams_table t ON lte.team_id = t.id WHERE lte.captain = %d";
        $params = array( $player_id );
        if ( $club_id ) {
            $sql .= " AND club_id = %d";
            $params[] = $club_id;
        }
        $query = $this->wpdb->prepare(
            $sql,
            $params
        );
        return $this->wpdb->get_col($query);
    }

    /**
     * Get league teams for an event
     *
     * @param int|null $event_id
     * @param int|null $season
     * @param int|null $team_id
     * @param int|null $club_id
     *
     * @return array
     */
    public function get_by_event_id( ?int $event_id, ?int $season = null, ?int $team_id = null, ?int $club_id = null ): array {
        $cache_key = 'event' . $event_id . '_' . $season . '_' . $team_id . '_' . $club_id;
        $teams = wp_cache_get( md5( $cache_key ), 'league_teams' );
        if ( $teams ) {
            return $teams;
        }
        $search = '';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        if ( $season ) {
            $search .= $this->wpdb->prepare( $this->season_compare, $season );
        }
        if ( $team_id ) {
            $search .= $this->wpdb->prepare( " AND `team_id`  = %d", $team_id );
        }
        if ( $club_id ) {
            $search .= $this->wpdb->prepare( " AND `team_id` IN ( SELECT `id` FROM $teams_table WHERE `club_id` = %d)", $club_id );
        }
        $teams = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT t.* FROM $this->table_name t, $leagues_table l WHERE l.`event_id` = %d AND l.`id` = t.`league_id` $search ORDER BY `title`",
                $event_id
            )
        );
        $results = array_map(function($row) {
            return new League_Team( $row );
        }, $teams);
        wp_cache_set( md5( $cache_key ), $results, 'league_teams' );
        return $results;

    }

    /**
     * Get clubs for an event and season
     *
     * @param int|null $event_id
     * @param int|null $season
     *
     * @return array
     */
    public function get_clubs_by_event_id( ?int $event_id, ?int $season ): array {
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $clubs_table   = $this->wpdb->prefix . 'racketmanager_clubs';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $cache_key = 'event_clubs_' . $event_id . '_' . $season;
        $clubs = wp_cache_get( md5( $cache_key ), 'league_teams' );
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
     * Get clubs for a competition and season
     *
     * @param int|null $competition_id
     * @param int|null $season
     *
     * @return array
     */
    public function get_clubs_by_competition_id( ?int $competition_id, ?int $season ): array {
        $events_table = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        $clubs_table   = $this->wpdb->prefix . 'racketmanager_clubs';
        $teams_table   = $this->wpdb->prefix . 'racketmanager_teams';
        $cache_key = 'competition_clubs_' . $competition_id . '_' . $season;
        $clubs = wp_cache_get( md5( $cache_key ), 'league_teams' );
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
     * Marks specific teams as 'withdrawn' from their league registrations.
     *
     * @param int $club_id The ID of the club performing the withdrawal.
     * @param string $season
     * @param int $event_id
     * @param array $team_ids
     *
     * @return League_Team[].
     */
    public function find_teams_to_withdraw_from_league( int $club_id, string $season, int $event_id, array $team_ids = array() ): array {
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';

        $results = [];
        $query = "
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
            $query .= " AND t.id IN ( $placeholders )";
            $params = array_merge( $params, $team_ids );
        }
        $sql  = $this->wpdb->prepare( $query, $params );
        $rows = $this->wpdb->get_results( $sql );

        foreach ( $rows as $row ) {
            // Map to the Domain Model
            $results[] = new League_Team( $row );
        }
        return $results;
    }

}
