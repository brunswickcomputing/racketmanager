<?php
/**
 * League_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Closure;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Util\Util;

/**
 * Class to implement the League repository
 */
class League_Repository implements League_Repository_Interface {
    private ?object $wpdb = null;
    private ?string $table_name = null;
    private Fixture_Repository_Interface|Closure|null $fixture_repository = null;
    private Standings_Service|Closure|null $standings_service = null;
    private League_Schedule_Service|Closure|null $league_schedule_service = null;
    private Competition_Notification_Service|Closure|null $competition_notification_service = null;
    private Fixture_Service|Closure|null $fixture_service = null;
    private Fixture_Result_Manager|Closure|null $fixture_result_manager = null;
    private Notification_Service|Closure|null $notification_service = null;
    private Rubber_Repository_Interface|Closure|null $rubber_repository = null;

    public function __construct(
        ?object $db = null,
        Fixture_Repository_Interface|Closure|null $fixture_repository = null,
        Standings_Service|Closure|null $standings_service = null,
        League_Schedule_Service|Closure|null $league_schedule_service = null,
        Competition_Notification_Service|Closure|null $competition_notification_service = null,
        Fixture_Service|Closure|null $fixture_service = null,
        Fixture_Result_Manager|Closure|null $fixture_result_manager = null,
        Notification_Service|Closure|null $notification_service = null,
        Rubber_Repository_Interface|Closure|null $rubber_repository = null
    ) {
        $this->wpdb = $db ?? ( $GLOBALS['wpdb'] ?? null );
        if ( $this->wpdb ) {
            $prefix = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
            $this->table_name = $prefix . 'racketmanager_leagues';
        }
        $this->fixture_repository = $fixture_repository;
        $this->standings_service = $standings_service;
        $this->league_schedule_service = $league_schedule_service;
        $this->competition_notification_service = $competition_notification_service;
        $this->fixture_service = $fixture_service;
        $this->fixture_result_manager = $fixture_result_manager;
        $this->notification_service = $notification_service;
        $this->rubber_repository = $rubber_repository;
    }

    public function save( object $entity ): bool|int {
        /** @var League $entity */
        $data = array(
            'title'    => $entity->get_name(),
            'settings' => maybe_serialize( $entity->get_settings() ),
            'seasons'  => maybe_serialize( $entity->get_seasons() ),
            'sequence' => $entity->get_sequence(),
            'event_id' => $entity->get_event_id(),
        );
        $data_format = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
        );
        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $entity->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            ) !== false;
        }
    }

    public function find_by_id( $id ): ?League {
        if ( empty( $id ) ) {
            return null;
        }

        $league = wp_cache_get( $id, 'leagues' );

        if ( ! $league && $this->wpdb ) {
            $league = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $league ) {
                return null;
            }
            $league = $this->hydrate_league( $league );
            if ( ! $league ) {
                return null;
            }

            wp_cache_set( $league->id, $league, 'leagues' );
        } elseif ( $league instanceof League ) {
            $this->wire_league_collaborators( $league );
        }

        return $league;
    }

    public function find_by_name( string $name ): ?League {
        if ( empty( $name ) ) {
            return null;
        }

        $league = wp_cache_get( $name, 'leagues' );

        if ( ! $league && $this->wpdb ) {
            $league = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `title` = %s LIMIT 1",
                    $name
                )
            );

            if ( ! $league ) {
                return null;
            }
            $league = $this->hydrate_league( $league );
            if ( ! $league ) {
                return null;
            }

            wp_cache_set( $name, $league, 'leagues' );
            wp_cache_set( $league->id, $league, 'leagues' );
        } elseif ( $league instanceof League ) {
            $this->wire_league_collaborators( $league );
        }

        return $league;
    }

    public function wire_league_collaborators( League $league ): void {
        $league->set_league_repository( $this );

        $rubber_repo = $this->resolve_rubber_repository();
        if ( $rubber_repo ) {
            $league->set_rubber_repository( $rubber_repo );
        }

        $fixture_repo = $this->resolve_fixture_repository();
        if ( $fixture_repo ) {
            $league->set_fixture_repository( $fixture_repo );
        }
        $standings_svc = $this->resolve_standings_service();
        if ( $standings_svc ) {
            $league->set_standings_service( $standings_svc );
        }
        $schedule_svc = $this->resolve_league_schedule_service();
        if ( $schedule_svc ) {
            $league->set_league_schedule_service( $schedule_svc );
        }
        $notification_svc = $this->resolve_competition_notification_service();
        if ( $notification_svc ) {
            $league->set_competition_notification_service( $notification_svc );
        }
        $fixture_svc = $this->resolve_fixture_service();
        if ( $fixture_svc ) {
            $league->set_fixture_service( $fixture_svc );
        }
        $result_mgr = $this->resolve_fixture_result_manager();
        if ( $result_mgr ) {
            $league->set_fixture_result_manager( $result_mgr );
        }
        $app_notify_svc = $this->resolve_notification_service();
        if ( $app_notify_svc ) {
            $league->set_notification_service( $app_notify_svc );
        }
    }

    private function hydrate_league( object $row ): ?League {
        $league = League_Factory::from_object(
            $row,
            null,
            $this,
            $this->resolve_fixture_repository(),
            $this->resolve_standings_service(),
            $this->resolve_league_schedule_service(),
            $this->resolve_competition_notification_service(),
            $this->resolve_fixture_service(),
            $this->resolve_fixture_result_manager(),
            $this->resolve_notification_service()
        );
        if ( $league ) {
            $league->set_rubber_repository( $this->resolve_rubber_repository() );
        }
        return $league;
    }

    private function resolve_collaborator( mixed &$collaborator ): mixed {
        if ( $collaborator instanceof Closure ) {
            $collaborator = $collaborator();
        }
        return $collaborator;
    }

    private function resolve_fixture_repository(): ?Fixture_Repository_Interface {
        $resolved = $this->resolve_collaborator( $this->fixture_repository );
        return $resolved instanceof Fixture_Repository_Interface ? $resolved : null;
    }

    private function resolve_rubber_repository(): ?Rubber_Repository_Interface {
        $resolved = $this->resolve_collaborator( $this->rubber_repository );
        return $resolved instanceof Rubber_Repository_Interface ? $resolved : null;
    }

    private function resolve_standings_service(): ?Standings_Service {
        $resolved = $this->resolve_collaborator( $this->standings_service );
        return $resolved instanceof Standings_Service ? $resolved : null;
    }

    private function resolve_league_schedule_service(): ?League_Schedule_Service {
        $resolved = $this->resolve_collaborator( $this->league_schedule_service );
        return $resolved instanceof League_Schedule_Service ? $resolved : null;
    }

    private function resolve_competition_notification_service(): ?Competition_Notification_Service {
        $resolved = $this->resolve_collaborator( $this->competition_notification_service );
        return $resolved instanceof Competition_Notification_Service ? $resolved : null;
    }

    private function resolve_fixture_service(): ?Fixture_Service {
        $resolved = $this->resolve_collaborator( $this->fixture_service );
        return $resolved instanceof Fixture_Service ? $resolved : null;
    }

    private function resolve_fixture_result_manager(): ?Fixture_Result_Manager {
        $resolved = $this->resolve_collaborator( $this->fixture_result_manager );
        return $resolved instanceof Fixture_Result_Manager ? $resolved : null;
    }

    private function resolve_notification_service(): ?Notification_Service {
        $resolved = $this->resolve_collaborator( $this->notification_service );
        return $resolved instanceof Notification_Service ? $resolved : null;
    }

    public function find_next_sequence_number( string $event_name ): int {
        // We look for names starting with the shortcode followed by a space and digits
        $prefix_like = $this->wpdb->esc_like( $event_name ) . ' ' .  '%';
        $query = $this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED))
             FROM $this->table_name
             WHERE `title` LIKE %s",
            $prefix_like
        );
        $max_sequence = (int) $this->wpdb->get_var($query);
        // If no leagues found, MAX returns NULL/0, so the next number is 1. Otherwise, increment the max.
        return $max_sequence + 1;
    }

    /**
     * Get leagues for an event
     *
     * @param int|null $event_id
     * @param int|null $season
     *
     * @return array
     */
    public function get_by_event_id( ?int $event_id, ?int $season = null ): array {
        $cache_key = $event_id . '_' . $season;
        $leagues = wp_cache_get( md5( $cache_key ), 'leagues' );
        if ( ! $leagues ) {
            $search = '';
            if ( $season ) {
                $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
                $search             = $this->wpdb->prepare( " AND `id` IN (SELECT DISTINCT `league_id` FROM $league_teams_table t, $this->table_name l WHERE t.`league_id` = l.`id` AND `season` = %d AND `event_id` = %d)", $season, $event_id);
            }
            $leagues = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `event_id` = %d $search ORDER BY `title`",
                    $event_id
                )
            );
            wp_cache_set( md5( $cache_key ), $leagues, 'events' );
        }
        return $leagues;
    }

    /**
     * Finds the lowest league (the highest rank) for a given event.
     *
     * @param int $event_id
     * @return int|null
     */
    public function get_lowest_league_id_by_event( int $event_id ): ?int {
        $query = $this->wpdb->prepare(
            "SELECT id
             FROM `$this->table_name`
             WHERE event_id = %d
             ORDER BY rank DESC, id DESC
             LIMIT 1",
            $event_id
        );

        $result = $this->wpdb->get_var($query);

        return $result ? (int) $result : null;
    }

    /**
     * Delete a league by its ID.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete( int $id ): bool {
        if ( $this->wpdb ) {
            return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
        }
        return false;
    }

    public function update_title_and_sequence( int $id, string $title, ?string $sequence = null ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $data = array( 'title' => $title );
        $format = array( '%s' );
        if ( ! empty( $sequence ) ) {
            $data['sequence'] = $sequence;
            $format[] = '%s';
        }
        return $this->wpdb->update(
            $this->table_name,
            $data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        ) !== false;
    }

    public function delete_league_teams( int $league_id ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        return $this->wpdb->delete( $table, array( 'league_id' => $league_id ), array( '%d' ) ) !== false;
    }

    public function delete_team( int $league_id, int $team_id, string $season ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        return $this->wpdb->delete(
            $table,
            array(
                'team_id'   => $team_id,
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%d', '%d', '%s' )
        ) !== false;
    }

    public function withdraw_team( int $league_id, int $team_id, string $season ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        return $this->wpdb->update(
            $table,
            array( 'status' => 'W' ),
            array(
                'team_id'   => $team_id,
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%s' ),
            array( '%d', '%d', '%s' )
        ) !== false;
    }

    public function get_match_day( int $league_id, string $season, string $type ): ?int {
        if ( ! $this->wpdb ) {
            return null;
        }
        $matches_table = $this->wpdb->prefix . 'racketmanager_matches';
        if ( 'last' === $type ) {
            $match = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) > 0 ORDER BY datediff LIMIT 1",
                    $league_id,
                    $season
                )
            );
        } elseif ( 'next' === $type ) {
            $match = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) < 0 ORDER BY datediff DESC LIMIT 1",
                    $league_id,
                    $season
                )
            );
        } else {
            $match = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `id`, `match_day`, ABS(DATEDIFF(NOW(), `date`)) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s ORDER BY datediff LIMIT 1",
                    $league_id,
                    $season
                )
            );
        }
        return $match ? (int) $match->match_day : null;
    }

    public function query_league_teams( int $league_id, array $args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $query_params = array( $league_id );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT B.`id` AS `id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating`';
        }
        $sql .= " FROM $teams_table B INNER JOIN $league_teams_table A ON B.id = A.team_id WHERE `league_id` = %d";

        $season = $args['season'] ?? null;
        if ( ! empty( $args['skip_season_filter'] ) ) {
            // Season was supplied but is not a known league season; keep original unfiltered behaviour.
        } elseif ( empty( $season ) ) {
            $sql .= ' AND A.`season` = %s';
            $query_params[] = $args['current_season'] ?? '';
        } elseif ( 'any' === $season ) {
            $sql .= " AND A.`season` != ''";
        } else {
            $sql .= ' AND A.`season` = %s';
            $query_params[] = $season;
        }

        if ( ! empty( $args['rank'] ) ) {
            $sql .= ' AND A.`rank` = %s';
            $query_params[] = $args['rank'];
        }
        if ( ! empty( $args['home'] ) ) {
            $sql .= ' AND B.`home` = 1';
        }
        if ( ! empty( $args['status'] ) ) {
            if ( 'active' === $args['status'] ) {
                $sql .= ' AND A.`profile` != 3';
            } elseif ( 1 === $args['status'] ) {
                $sql .= ' AND A.`profile` = %d';
                $query_params[] = $args['status'];
            }
        }
        if ( ! empty( $args['club'] ) ) {
            $sql .= ' AND B.`club_id` = %d';
            $query_params[] = $args['club'];
        }
        if ( ! empty( $args['team_name'] ) ) {
            $sql .= ' AND B.`title` = %s';
            $query_params[] = $args['team_name'];
        }
        if ( ! empty( $args['team_id'] ) ) {
            $sql .= ' AND B.`id` = %d';
            $query_params[] = $args['team_id'];
        }
        if ( ! empty( $args['active'] ) ) {
            $sql .= " AND A.`status` != 'W'";
        }

        $cache = $args['cache'] ?? true;
        if ( $count ) {
            $prepared = $this->wpdb->prepare( $sql, $query_params );
            $cache_key = md5( $prepared );
            $result = wp_cache_get( $cache_key, 'leaguetable' );
            if ( ! $result || ! $cache ) {
                $result = (int) $this->wpdb->get_var( $prepared );
                wp_cache_set( $cache_key, $result, 'leaguetable' );
            }
            return (int) $result;
        } else {
            $orderby = $args['orderby'] ?? array();
            $sql .= Util::order_by_string( $orderby );
            $prepared = $this->wpdb->prepare( $sql, $query_params );
            $result = wp_cache_get( $prepared, 'leaguetable' );
            if ( ! $result || ! $cache ) {
                $result = $this->wpdb->get_results( $prepared );
                wp_cache_set( $prepared, $result, 'leaguetable' );
            }
            return is_array( $result ) ? $result : array();
        }
    }

    public function add_team_to_league( int $league_id, int $team_id, string $season, ?string $rank = null, ?string $status = null, int $profile = 1 ): bool|int {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT `id` FROM $table WHERE `team_id` = %d AND `season` = %s AND `league_id` = %d",
                $team_id,
                $season,
                $league_id
            )
        );
        if ( $existing ) {
            return false;
        }
        $data = array(
            'team_id'   => $team_id,
            'season'    => $season,
            'league_id' => $league_id,
            'profile'   => $profile,
        );
        $format = array( '%d', '%s', '%d', '%d' );
        if ( $rank ) {
            $data['rank'] = (int) $rank;
            $format[]     = '%d';
            if ( null !== $status ) {
                $data['status'] = $status;
                $format[]       = '%s';
            }
        }
        $inserted = $this->wpdb->insert( $table, $data, $format );
        return $inserted ? $this->wpdb->insert_id : false;
    }

    public function get_team_details( int $league_id, int $team_id, string $season ): ?object {
        if ( ! $this->wpdb ) {
            return null;
        }
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        $sql = $this->wpdb->prepare(
            "SELECT A.`title`, C.`captain`, A.`club_id`, C.`match_day`, C.`match_time`, A.`stadium`, A.`home`, A.`roster`, A.`profile`, A.`id`, A.`status`, A.`type`, A.`team_type`, C.`status` as `league_status`, C.`rating`, C.`rank`, C.`points_plus`, C.`points_minus`, C.`points_2_plus`, C.`points_2_minus`, C.`add_points`, C.`done_matches`, C.`won_matches`, C.`draw_matches`, C.`lost_matches`, C.`diff` FROM $league_teams_table C INNER JOIN $teams_table A ON A.`id` = C.`team_id` AND C.`league_id` = %d WHERE A.`id` = %d AND C.`season` = %s",
            $league_id,
            $team_id,
            $season
        );

        $team = wp_cache_get( md5( $sql ), 'teamdetails' );
        if ( ! $team ) {
            $team = $this->wpdb->get_row( $sql );
            if ( $team ) {
                wp_cache_set( md5( $sql ), $team, 'teamdetails' );
            }
        }
        return $team ?: null;
    }

    public function get_rank( int $league_id, string $season, int $team_id ): ?int {
        if ( ! $this->wpdb ) {
            return null;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $sql = $this->wpdb->prepare(
            "SELECT `rank` FROM $table WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $league_id,
            $season,
            $team_id
        );
        $team_rank = wp_cache_get( md5( $sql ), 'team_rank' );
        if ( ! $team_rank ) {
            $team_rank = $this->wpdb->get_row( $sql );
            wp_cache_set( md5( $sql ), $team_rank, 'team_rank' );
        }
        return $team_rank && isset( $team_rank->rank ) ? (int) $team_rank->rank : null;
    }

    public function get_status( int $league_id, string $season, int $team_id ): ?string {
        if ( ! $this->wpdb ) {
            return null;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $sql = $this->wpdb->prepare(
            "SELECT `status` FROM $table WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $league_id,
            $season,
            $team_id
        );
        $team_status = wp_cache_get( md5( $sql ), 'team_status' );
        if ( ! $team_status ) {
            $team_status = $this->wpdb->get_row( $sql );
            wp_cache_set( md5( $sql ), $team_status, 'team_status' );
        }
        return $team_status && isset( $team_status->status ) ? (string) $team_status->status : null;
    }

    public function update_standings_manually( int $league_id, string $season, int $team_id, array $points, array $matches ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        return $this->wpdb->update(
            $table,
            array(
                'points_plus'    => $points['points_plus'],
                'points_minus'   => $points['points_minus'],
                'points_2_plus'  => 0,
                'points_2_minus' => 0,
                'done_matches'   => $matches['num_done_matches'],
                'won_matches'    => $matches['num_won_matches'],
                'draw_matches'   => $matches['num_draw_matches'],
                'lost_matches'   => $matches['num_lost_matches'],
                'diff'           => 0,
                'add_points'     => $points['add_points'],
            ),
            array(
                'team_id'   => $team_id,
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' ),
            array( '%d', '%d', '%s' )
        ) !== false;
    }

    public function update_team_standings( int $league_id, string $season, int $team_id, array $data ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $update_data = array(
            'points_plus'    => $data['points_plus'] ?? 0,
            'points_minus'   => $data['points_minus'] ?? 0,
            'points_2_plus'  => $data['points_2_plus'] ?? 0,
            'points_2_minus' => $data['points_2_minus'] ?? 0,
            'done_matches'   => $data['done_matches'] ?? 0,
            'won_matches'    => $data['won_matches'] ?? 0,
            'draw_matches'   => $data['draw_matches'] ?? 0,
            'lost_matches'   => $data['lost_matches'] ?? 0,
            'diff'           => $data['diff'] ?? 0,
            'custom'         => maybe_serialize( $data['custom'] ?? [] ),
        );
        return $this->wpdb->update(
            $table,
            $update_data,
            array(
                'team_id'   => $team_id,
                'league_id' => $league_id,
                'season'    => $season,
            ),
            array( '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' ),
            array( '%d', '%d', '%s' )
        ) !== false;
    }

    public function query_players( int $league_id, array $args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';

        $search_terms  = array();
        $search_args   = array( $league_id );

        $season = $args['season'] ?? false;
        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        $team = $args['team'] ?? false;
        if ( $team ) {
            $search_terms[] = '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
            $search_args[]   = $team;
            $search_args[]   = 'home';
            $search_args[]   = $team;
            $search_args[]   = 'away';
        }
        $club = $args['club'] ?? false;
        if ( $club ) {
            $search_terms[] = "(( `home_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s))";
            $search_args[]   = $club;
            $search_args[]   = 'home';
            $search_args[]   = $club;
            $search_args[]   = 'away';
        }
        $search = Util::search_string( $search_terms );
        $orderby = $args['orderby'] ?? array();
        $order  = Util::order_by_string( $orderby );

        if ( $count ) {
            $sql = "SELECT COUNT(*) FROM $rubber_players_table rp, $rubbers_table r, $matches_table m WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search;
            $prepared = $this->wpdb->prepare( $sql, $search_args );
            $num_players = wp_cache_get( md5( $prepared ), 'league_rubber_players' );
            if ( ! $num_players ) {
                $num_players = (int) $this->wpdb->get_var( $prepared );
                wp_cache_set( md5( $prepared ), $num_players, 'league_rubber_players' );
            }
            return (int) $num_players;
        }

        $sql = "SELECT DISTINCT `player_id`, `club_player_id` FROM $rubber_players_table rp, $rubbers_table r, $matches_table m WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search . $order;
        $limit = $args['limit'] ?? 0;
        $offset = $args['offset'] ?? 0;
        if ( intval( $limit > 0 ) ) {
            $sql .= ' LIMIT %d, %d';
            $search_args[] = (int) $offset;
            $search_args[] = (int) $limit;
        }
        $prepared = $this->wpdb->prepare( $sql, $search_args );
        $players = wp_cache_get( md5( $prepared ), 'league_rubber_players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $prepared );
            wp_cache_set( md5( $prepared ), $players, 'league_rubber_players' );
        }
        return is_array( $players ) ? $players : array();
    }

    public function get_player_team( int $league_id, array $args ): ?object {
        if ( ! $this->wpdb ) {
            return null;
        }
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';
        $club_players_table   = $this->wpdb->prefix . 'racketmanager_club_players';

        $search_terms  = array();
        $search_args   = array( $league_id );

        $season = $args['season'] ?? false;
        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        $player = $args['player'] ?? false;
        if ( $player ) {
            $search_terms[] = 'ro.`player_id` = %d';
            $search_args[]  = (int) $player;
        }
        $search = Util::search_string( $search_terms );
        $sql = "SELECT distinct t.`id`, t.`title` FROM $teams_table AS t, $rubbers_table AS r, $rubber_players_table AS rp, $matches_table AS m, $club_players_table AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND rp.`club_player_id` = ro.`id` AND ((rp.`player_team` = 'home' AND m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND m.`away_team` = t.`id`)) AND ro.`club_id` = t.`club_id` AND r.`match_id` = m.`id` AND m.`league_id` = %d " . $search;
        $prepared = $this->wpdb->prepare( $sql, $search_args );

        $teams = wp_cache_get( md5( $prepared ), 'player_team' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $prepared );
            wp_cache_set( md5( $prepared ), $teams, 'player_team' );
        }
        return ( ! empty( $teams ) && is_array( $teams ) ) ? $teams[0] : null;
    }

    public function update_seasons( int $league_id, array $seasons ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        return $this->wpdb->update(
            $this->table_name,
            array( 'seasons' => maybe_serialize( $seasons ) ),
            array( 'id' => $league_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
    }

    public function query_matches( int $league_id, array $query_args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';

        $args = array( $league_id );
        if ( $count ) {
            $sql_start = 'SELECT COUNT(distinct m.id)';
        } else {
            $sql_start = 'SELECT distinct m.*';
        }
        $sql_start .= " FROM $matches_table m";
        $sql = ' WHERE m.`league_id` = %d';

        $season = $query_args['season'] ?? null;
        if ( empty( $season ) ) {
            $sql .= ' AND m.`season` = %s';
            $args[] = $query_args['current_season'] ?? '';
        } elseif ( 'any' === $season ) {
            $sql .= " AND m.`season` != ''";
        } else {
            $sql .= ' AND m.`season` = %s';
            $args[] = $season;
        }

        $final = $query_args['final'] ?? null;
        $is_championship = $query_args['is_championship'] ?? false;
        if ( $final ) {
            if ( 'all' !== $final ) {
                $sql .= ' AND m.`final` = %s';
                $args[] = htmlspecialchars( wp_strip_all_tags( $final ) );
            }
        } elseif ( $is_championship ) {
            $sql .= " AND m.`final` IS NOT NULL";
        } else {
            $sql .= " AND ( m.`final` = '' OR m.`final` IS NULL )";
        }

        $team_id = $query_args['team_id'] ?? null;
        $team_pair = $query_args['team_pair'] ?? null;
        $home_team = $query_args['home_team'] ?? null;
        $away_team = $query_args['away_team'] ?? null;
        if ( $team_id ) {
            $sql .= ' AND (m.`home_team` = %d OR m.`away_team` = %d)';
            $args[] = $team_id;
            $args[] = $team_id;
        } elseif ( is_array( $team_pair ) && count( $team_pair ) === 2 ) {
            $sql .= " AND ( (`home_team` = %d AND `away_team` = %d) OR (`home_team` = %d AND `away_team` = %d) )";
            $args[] = intval( $team_pair[0] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[0] );
        } else {
            if ( ! empty( $home_team ) ) {
                $sql .= ' AND m.`home_team` = %d';
                $args[] = (int) $home_team;
            }
            if ( ! empty( $away_team ) ) {
                $sql .= ' AND m.`away_team` = %d';
                $args[] = (int) $away_team;
            }
        }

        $match_day = $query_args['match_day'] ?? null;
        $standingstable = $query_args['standingstable'] ?? false;
        if ( $match_day && intval( $match_day ) > 0 ) {
            if ( $standingstable ) {
                $sql .= ' AND m.`match_day` <= %d';
            } else {
                $sql .= ' AND m.`match_day` = %d';
            }
            $args[] = intval( $match_day );
        }

        $time = $query_args['time'] ?? null;
        $home_points = $query_args['home_points'] ?? '';
        $away_points = $query_args['away_points'] ?? '';
        if ( 'latest' === $time ) {
            $home_points = false;
            $away_points = false;
            $sql .= " AND (m.`home_points` != '' OR m.`away_points` != '')";
        }

        if ( '' !== $home_points && false !== $home_points ) {
            if ( 'null' === $home_points ) {
                $sql .= ' AND m.`home_points` IS NULL';
            } elseif ( 'not_null' === $home_points ) {
                $sql .= ' AND m.`home_points` IS NOT NULL';
            } elseif ( 'not_empty' === $home_points ) {
                $sql .= " AND m.`home_points` != ''";
            }
        }
        if ( $away_points && false !== $away_points ) {
            if ( 'null' === $away_points ) {
                $sql .= ' AND m.`away_points` IS NULL';
            } elseif ( 'not_null' === $away_points ) {
                $sql .= ' AND m.`away_points` IS NOT NULL';
            } elseif ( 'not_empty' === $away_points ) {
                $sql .= " AND m.`away_points` != ''";
            }
        }

        $winner_id = $query_args['winner_id'] ?? null;
        $loser_id = $query_args['loser_id'] ?? null;
        if ( $winner_id ) {
            $sql .= ' AND m.`winner_id` = %d';
            $args[] = (int) $winner_id;
        }
        if ( $loser_id ) {
            $sql .= ' AND m.`loser_id` = %d';
            $args[] = (int) $loser_id;
        }

        $time = $query_args['time'] ?? null;
        if ( 'next' === $time ) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0';
        } elseif ( 'prev' === $time || 'latest' === $time ) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0';
        } elseif ( 'prev1' === $time ) {
            $sql .= ' AND (TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0 AND m.`winner_id` != 0)';
        } elseif ( 'today' === $time ) {
            $sql .= ' AND DATEDIFF(NOW(), m.`date`) = 0';
        } elseif ( 'day' === $time && ! empty( $query_args['match_date'] ) ) {
            $sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $query_args['match_date'] ) ) . "', m.`date`) = 0";
        }

        if ( ! empty( $query_args['confirmed'] ) ) {
            $sql .= " AND m.`confirmed` = 'Y'";
        }
        if ( ! empty( $query_args['leg'] ) ) {
            $sql .= ' AND m.`leg` = %s';
            $args[] = $query_args['leg'];
        }
        if ( ! empty( $query_args['player'] ) ) {
            $sql_start .= ", $rubbers_table r, $rubber_players_table rp";
            $sql .= ' AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = %s';
            $args[] = $query_args['player'];
        }
        if ( empty( $query_args['withdrawn'] ) ) {
            $sql_start .= ", $league_teams_table t1, $league_teams_table t2";
            $sql .= " AND m.`home_team` = t1.`team_id` AND t1.`league_id` = m.`league_id` AND t1.`season` = m.`season` AND t1.`status` != 'W'";
            $sql .= " AND m.`away_team` = t2.`team_id` AND t2.`league_id` = m.`league_id` AND t2.`season` = m.`season` AND t2.`status` != 'W'";
        }
        if ( ! empty( $query_args['club'] ) ) {
            $sql .= " AND (m.`home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d) OR m.`away_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d))";
            $args[] = (int) $query_args['club'];
            $args[] = (int) $query_args['club'];
        }
        if ( ! empty( $query_args['pending'] ) ) {
            $sql .= ' AND m.winner_id = 0';
        }
        if ( ! empty( $query_args['status_value'] ) ) {
            if ( 'not' === ( $query_args['status_compare'] ?? '' ) ) {
                $sql .= ' AND m.`status` != %d';
                $args[] = $query_args['status_value'];
            } else {
                $sql .= ' AND m.`status` = %d';
                $args[] = $query_args['status_value'];
            }
        }

        $cache = $query_args['cache'] ?? true;
        if ( $count ) {
            $full_sql = $sql_start . $sql;
            $prepared = $this->wpdb->prepare( $full_sql, $args );
            $result = wp_cache_get( md5( $prepared ), 'num_matches' );
            if ( ! $result || false === $cache ) {
                $result = (int) $this->wpdb->get_var( $prepared );
                wp_cache_set( md5( $prepared ), $result, 'num_matches' );
            }
            return (int) $result;
        }

        $orderby = $query_args['orderby'] ?? array();
        if ( 'next' === $time ) {
            $orderby['date'] = 'ASC';
        } elseif ( 'prev' === $time || 'latest' === $time ) {
            $orderby['date'] = 'DESC';
        }
        $sql .= Util::order_by_string( $orderby );

        $limit = $query_args['limit'] ?? 0;
        $offset = $query_args['offset'] ?? 0;
        if ( intval( $limit > 0 ) ) {
            $sql .= ' LIMIT ' . intval( $offset ) . ',' . intval( $limit );
        }
        $full_sql = $sql_start . $sql;
        $prepared = $this->wpdb->prepare( $full_sql, $args );
        $result = wp_cache_get( md5( $prepared ), 'matches' );
        if ( ! $result || false === $cache ) {
            $result = $this->wpdb->get_results( $prepared );
            wp_cache_set( md5( $prepared ), $result, 'matches' );
        }
        return is_array( $result ) ? $result : array();
    }
}
