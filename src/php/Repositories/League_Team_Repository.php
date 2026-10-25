<?php
/**
 * League_Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\League\League_Standings_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use stdClass;
use wpdb;

/**
 * Class to implement the League team repository
 */
class League_Team_Repository implements League_Team_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private string $season_compare;
    private ?Club_Repository_Interface $club_repository;
    private ?Team_Repository_Interface $team_repository;

    public function __construct(
        ?wpdb $wpdb = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Team_Repository_Interface $team_repository = null
    ) {
        $this->wpdb            = $wpdb ?? $GLOBALS['wpdb'];
        $this->season_compare  = ' AND `season` = %d';
        $this->table_name      = $this->wpdb->prefix . 'racketmanager_league_teams';
        $this->club_repository = $club_repository;
        $this->team_repository = $team_repository;
    }

    public function hydrate( object $row ): League_Team {
        $league_team = League_Team_Factory::from_object( $row );
        $this->enrich( $league_team );
        return $league_team;
    }

    /**
     * Enrich a hydrated league team with club and (for player teams) roster players.
     *
     * Preserves the original entity rules: club comes from club_id; players are loaded
     * when team_type is exactly 'P' and roster is not null (an empty roster still loads).
     */
    private function enrich( League_Team $league_team ): void {
        if ( ! empty( $league_team->club_id ) && null !== $this->club_repository ) {
            $league_team->club = $this->club_repository->find_by_id( (int) $league_team->club_id );
        }
        if ( 'P' === ( $league_team->team_type ?? null ) && null !== $league_team->roster && null !== $this->team_repository ) {
            $team_id = ! empty( $league_team->get_team_id() ) ? $league_team->get_team_id() : (int) ( $league_team->id ?? 0 );
            if ( ! empty( $team_id ) ) {
                $team                 = $this->team_repository->find_by_id( $team_id );
                $league_team->players = $team?->players ?? array();
            }
        }
    }

    public function save( object $entity ): bool|int {
        /** @var League_Team $entity */
        $data = array(
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
        $table_id = $entity->get_table_id() ?? $entity->table_id ?? $entity->get_id();
        if ( empty( $table_id ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format
            );
            if ( $inserted ) {
                $entity->table_id = $this->wpdb->insert_id;
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
                ), // Where clause
                $data_format,
                array(
                    '%d',
                ) // Where format
            ) !== false;
        }
    }

    public function delete( int $id ): bool {
        wp_cache_delete( $id, 'league-teams' );
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }

    public function find( int|string $id ): ?League_Team {
        return $this->find_by_id( $id );
    }

    public function find_by_id( $id ): ?League_Team {
        if ( empty( $id ) ) {
            return null;
        }
        $id = (int) $id;
        $league_team = wp_cache_get( $id, 'league-teams' );

        if ( ! $league_team ) {
            $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
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
     * Finds all Club IDs where the given player is assigned as a team captain.
     *
     * @param int $player_id
     * @param int|null $club_id
     *
     * @return int[] Array of Club IDs.
     */
    public function find_club_ids_where_player_is_captain( int $player_id, ?int $club_id = null ): array {
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
     * @return League_Team[]
     */
    public function find_by_event_id( ?int $event_id, ?int $season = null, ?int $team_id = null, ?int $club_id = null ): array {
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
        $results = array_map(
            fn( $row ) => $this->hydrate( $row ),
            $teams
        );
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
    public function get_clubs_by_event_id( ?int $event_id, ?int $season = null ): array {
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
    public function get_clubs_by_competition_id( ?int $competition_id, ?int $season = null ): array {
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
            $results[] = $this->hydrate( $row );
        }
        return $results;
    }

    public function player_already_entered_league( int $player_id, int $league_id, int $season ): stdClass|null {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';

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

    public function find_player_teams_by_player_for_events( int $player_id, array $event_ids, int $season ): array {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $leagues_table = $this->wpdb->prefix . 'racketmanager_leagues';
        // Prepare placeholders for the IN clause
        $placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
        $results      = array();

        // We update the status for teams where the player is a member
        // and that team is linked to one of the missed events.
        $query = $this->wpdb->prepare(
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
        $rows = $this->wpdb->get_results( $query );
        foreach ( $rows as $row ) {
            // Map to the Domain Model
            $results[] = $this->hydrate( $row );
        }
        return $results;

    }

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

        return array_map( function ( $row ) {
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
                // Convert raw int to Enum safely
                profile: Team_Profile::tryFrom( (int) $row->profile ) ?? Team_Profile::ACTIVE,
                status: (string) $row->status,
                additional_info: maybe_unserialize( $row->custom )
            );
        }, $rows );
    }

    public function get_teams_by_league_and_season( int $league_id, int $season ): array {
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $query = $this->wpdb->prepare(
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

    public function find_by_team_league_and_season( int $team_id, int $league_id, int $season ): ?League_Team {
        $query = $this->wpdb->prepare(
            "SELECT * FROM `$this->table_name` WHERE team_id = %d AND league_id = %d AND season = %d",
            $team_id,
            $league_id,
            $season
        );
        $row = $this->wpdb->get_row( $query );
        if ( ! $row ) {
            return null;
        }
        return $this->hydrate( $row );
    }

}
