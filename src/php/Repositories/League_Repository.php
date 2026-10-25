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
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Util\Util;

/**
 * Class to implement the League repository
 */
class League_Repository implements League_Repository_Interface {
    private ?object $wpdb = null;
    private ?string $table_name = null;
    /** @var Event_Repository_Interface|Closure|null */
    private mixed $event_repository = null;

    public function __construct(
        ?object $db = null,
        Event_Repository_Interface|Closure|null $event_repository = null
    ) {
        $this->wpdb = $db ?? ( $GLOBALS['wpdb'] ?? null );
        if ( $this->wpdb ) {
            $prefix = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
            $this->table_name = $prefix . 'racketmanager_leagues';
        }
        $this->event_repository = $event_repository;
    }

    private function get_event_repository(): ?Event_Repository_Interface {
        if ( $this->event_repository instanceof Closure ) {
            $this->event_repository = ( $this->event_repository )();
        }
        return $this->event_repository instanceof Event_Repository_Interface ? $this->event_repository : null;
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
                if ( $entity->get_event_id() ) {
                    $this->invalidate_event_leagues_cache( (int) $entity->get_event_id() );
                }
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            $updated = $this->wpdb->update(
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
            if ( $updated ) {
                wp_cache_delete( $entity->get_id(), 'leagues' );
                if ( $entity->get_name() ) {
                    wp_cache_delete( $entity->get_name(), 'leagues' );
                }
                if ( $entity->get_event_id() ) {
                    $this->invalidate_event_leagues_cache( (int) $entity->get_event_id() );
                }
            }
            return $updated;
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
        }

        return $league;
    }

    private function hydrate_league( object $row, ?Event $event = null ): ?League {
        if ( null === $event && ! empty( $row->event_id ) ) {
            $event_repo = $this->get_event_repository();
            if ( $event_repo ) {
                $event = $event_repo->find_by_id( (int) $row->event_id );
            }
        }
        $league = League_Factory::from_object( $row, $event );
        if ( $league ) {
            $league->set_season( false, false );
            $league->num_teams_total = (int) $this->query_league_teams(
                (int) $league->id,
                array(
                    'count'            => true,
                    'season'           => $league->current_season['name'] ?? '',
                    'reset_query_args' => true,
                    'club'             => false,
                ),
                true
            );

            if ( 'championship' === ( $league->mode ?? '' ) ) {
                $settings_data        = isset( $league->settings ) ? (array) $league->settings : array();
                $settings             = Championship_Settings::from_array( $settings_data );
                $factory              = new Championship_Factory( $this );
                $league->championship = $factory->create( $league, $settings, null );
            }
        }
        return $league;
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

    private function invalidate_event_leagues_cache( ?int $event_id ): void {
        if ( empty( $event_id ) ) {
            return;
        }
        $version = (int) wp_cache_get( 'ver_event_leagues_' . $event_id, 'leagues' );
        wp_cache_set( 'ver_event_leagues_' . $event_id, $version + 1, 'leagues' );
    }

    private function get_event_leagues_version( ?int $event_id ): int {
        if ( empty( $event_id ) ) {
            return 0;
        }
        return (int) wp_cache_get( 'ver_event_leagues_' . $event_id, 'leagues' );
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
        if ( empty( $event_id ) ) {
            return array();
        }
        $version   = $this->get_event_leagues_version( $event_id );
        $cache_key = 'event_leagues_' . $event_id . '_' . ( $season ?? 'all' ) . '_v' . $version;
        $leagues   = wp_cache_get( md5( $cache_key ), 'leagues' );
        if ( ! is_array( $leagues ) ) {
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
            if ( is_array( $leagues ) ) {
                wp_cache_set( md5( $cache_key ), $leagues, 'leagues' );
            } else {
                $leagues = array();
            }
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
        if ( ! $this->wpdb ) {
            return false;
        }
        $existing = wp_cache_get( $id, 'leagues' );
        $db_row   = null;
        if ( ! ( $existing instanceof League ) ) {
            $db_row = $this->wpdb->get_row(
                $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1", $id )
            );
        }

        $deleted = $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
        if ( $deleted ) {
            wp_cache_delete( $id, 'leagues' );

            $title    = null;
            $event_id = null;
            $seasons  = array();

            if ( $existing instanceof League ) {
                $title    = $existing->title ?? null;
                $event_id = isset( $existing->event_id ) ? (int) $existing->event_id : null;
                if ( isset( $existing->seasons ) && is_array( $existing->seasons ) ) {
                    $seasons = array_keys( $existing->seasons );
                }
            } elseif ( $db_row ) {
                $title    = $db_row->title ?? null;
                $event_id = isset( $db_row->event_id ) ? (int) $db_row->event_id : null;
                if ( ! empty( $db_row->seasons ) ) {
                    $unserialized = maybe_unserialize( $db_row->seasons );
                    if ( is_array( $unserialized ) ) {
                        $seasons = array_keys( $unserialized );
                    }
                }
            }

            if ( ! empty( $title ) ) {
                wp_cache_delete( $title, 'leagues' );
            }

            foreach ( $seasons as $season_key ) {
                wp_cache_delete( 'last_' . $id . '_' . $season_key, 'leagues_match_days' );
                wp_cache_delete( 'next_' . $id . '_' . $season_key, 'leagues_match_days' );
                wp_cache_delete( 'current_' . $id . '_' . $season_key, 'leagues_match_days' );
            }

            if ( ! empty( $event_id ) ) {
                $this->invalidate_event_leagues_cache( $event_id );
            }
        }
        return $deleted;
    }

    public function update_title_and_sequence( int $id, string $title, ?string $sequence = null ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $existing = wp_cache_get( $id, 'leagues' );
        $db_row   = null;
        if ( ! ( $existing instanceof League ) ) {
            $db_row = $this->wpdb->get_row(
                $this->wpdb->prepare( "SELECT `title`, `event_id` FROM $this->table_name WHERE `id` = %d LIMIT 1", $id )
            );
        }
        $data     = array( 'title' => $title );
        $format   = array( '%s' );
        if ( ! empty( $sequence ) ) {
            $data['sequence'] = $sequence;
            $format[]         = '%s';
        }
        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        ) !== false;
        if ( $result ) {
            wp_cache_delete( $id, 'leagues' );
            wp_cache_delete( $title, 'leagues' );
            $old_title = null;
            $event_id  = null;
            if ( $existing instanceof League ) {
                $old_title = $existing->title ?? null;
                $event_id  = isset( $existing->event_id ) ? (int) $existing->event_id : null;
            } elseif ( $db_row ) {
                $old_title = $db_row->title ?? null;
                $event_id  = isset( $db_row->event_id ) ? (int) $db_row->event_id : null;
            }
            if ( ! empty( $old_title ) && $old_title !== $title ) {
                wp_cache_delete( $old_title, 'leagues' );
            }
            if ( ! empty( $event_id ) ) {
                $this->invalidate_event_leagues_cache( $event_id );
            }
        }
        return $result;
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
        $cache_type = ( 'latest' === $type ) ? 'current' : $type;
        $cache_key  = $cache_type . '_' . $league_id . '_' . $season;
        $cached     = wp_cache_get( $cache_key, 'leagues_match_days' );
        if ( false !== $cached && null !== $cached ) {
            return (int) $cached;
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
        if ( $match ) {
            $match_day = (int) $match->match_day;
            wp_cache_set( $cache_key, $match_day, 'leagues_match_days' );
            return $match_day;
        }
        return null;
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
        $existing = wp_cache_get( $league_id, 'leagues' );
        $db_row   = null;
        if ( ! ( $existing instanceof League ) ) {
            $db_row = $this->wpdb->get_row(
                $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1", $league_id )
            );
        }
        $result = $this->wpdb->update(
            $this->table_name,
            array( 'seasons' => maybe_serialize( $seasons ) ),
            array( 'id' => $league_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
        if ( $result ) {
            wp_cache_delete( $league_id, 'leagues' );
            $title       = null;
            $event_id    = null;
            $all_seasons = array_keys( $seasons );

            if ( $existing instanceof League ) {
                $title    = $existing->title ?? null;
                $event_id = isset( $existing->event_id ) ? (int) $existing->event_id : null;
                if ( isset( $existing->seasons ) && is_array( $existing->seasons ) ) {
                    $all_seasons = array_unique( array_merge( $all_seasons, array_keys( $existing->seasons ) ) );
                }
            } elseif ( $db_row ) {
                $title    = $db_row->title ?? null;
                $event_id = isset( $db_row->event_id ) ? (int) $db_row->event_id : null;
                if ( ! empty( $db_row->seasons ) ) {
                    $unserialized = maybe_unserialize( $db_row->seasons );
                    if ( is_array( $unserialized ) ) {
                        $all_seasons = array_unique( array_merge( $all_seasons, array_keys( $unserialized ) ) );
                    }
                }
            }
            if ( ! empty( $title ) ) {
                wp_cache_delete( $title, 'leagues' );
            }
            foreach ( $all_seasons as $season_key ) {
                wp_cache_delete( 'last_' . $league_id . '_' . $season_key, 'leagues_match_days' );
                wp_cache_delete( 'next_' . $league_id . '_' . $season_key, 'leagues_match_days' );
                wp_cache_delete( 'current_' . $league_id . '_' . $season_key, 'leagues_match_days' );
            }
            if ( ! empty( $event_id ) ) {
                $this->invalidate_event_leagues_cache( $event_id );
            }
        }
        return $result;
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
        if ( 'all' === $season || 'any' === $season || ! empty( $query_args['skip_season_filter'] ) ) {
            // No season filter: spans all seasons and includes empty/zero-season rows.
        } elseif ( empty( $season ) ) {
            $sql .= ' AND m.`season` = %s';
            $args[] = $query_args['current_season'] ?? '';
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

    /**
     * Get leagues from the database matching criteria.
     *
     * @param array $args
     * @return array
     */
    public function find_all( array $args = array() ): array {
        $defaults = array(
            'offset'           => 0,
            'limit'            => 99999999,
            'competition_type' => false,
            'name'             => false,
            'season'           => false,
            'orderby'          => array( 'title' => 'ASC' ),
        );
        $args             = array_merge( $defaults, $args );
        $offset           = $args['offset'];
        $limit            = $args['limit'];
        $competition_type = $args['competition_type'];
        $name             = $args['name'];
        $season           = $args['season'];
        $orderby          = $args['orderby'];
        $search_terms     = array();
        if ( $name ) {
            $escaped_name   = method_exists( $this->wpdb, 'esc_like' ) ? $this->wpdb->esc_like( stripslashes( $name ) ) : addcslashes( stripslashes( $name ), '_%\\' );
            $search_terms[] = $this->wpdb->prepare( '`title` like %s', $escaped_name . '%' );
        }
        if ( $competition_type ) {
            $events_table       = $this->wpdb->prefix . 'racketmanager_events';
            $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';
            $search_terms[]     = $this->wpdb->prepare( "`event_id` in (select e.`id` from $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND c.`type` = %s)", $competition_type );
        }
        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $this->wpdb->prepare(
            "SELECT `id` FROM $this->table_name $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $leagues = wp_cache_get( md5( $sql ), 'leagues' );
        if ( ! $leagues ) {
            $leagues = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $leagues, 'leagues' );
        }
        if ( ! is_array( $leagues ) ) {
            return array();
        }
        $hydrated_leagues = array();
        foreach ( $leagues as $row ) {
            $league = $this->find_by_id( (int) $row->id );
            if ( ! $league ) {
                continue;
            }
            if ( $season ) {
                if ( isset( $league->event ) && method_exists( $league->event, 'get_season_by_name' ) && $league->event->get_season_by_name( $season ) ) {
                    $hydrated_leagues[] = $league;
                }
            } else {
                $hydrated_leagues[] = $league;
            }
        }
        return $hydrated_leagues;
    }

    /**
     * Get league standings summary rows.
     *
     * @param array $args
     * @return array
     */
    public function get_league_standings( array $args = array() ): array {
        $defaults = array(
            'season'    => false,
            'team'      => false,
            'age_group' => false,
        );
        $args      = array_merge( $defaults, $args );
        $season    = $args['season'];
        $team_id   = $args['team'];
        $age_group = $args['age_group'];

        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';

        $sql = "SELECT l.id, t.`won_matches`,t.`rank` FROM $this->table_name l, $league_teams_table t WHERE l.`id` = t.`league_id` AND l.`id` IN (SELECT `id` FROM $this->table_name WHERE `event_id` IN (SELECT e.`id` FROM $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND c.`type` = 'league'))";
        if ( $season ) {
            $sql .= $this->wpdb->prepare(
                ' AND t.`season` = %d',
                $season
            );
        }
        if ( $team_id ) {
            $sql .= $this->wpdb->prepare(
                ' AND t.`team_id` = %d',
                $team_id
            );
        }
        if ( $age_group ) {
            $sql .= $this->wpdb->prepare(
                " AND l.`id` IN (SELECT `id` FROM $this->table_name WHERE `event_id` IN (SELECT e.`id` FROM $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND `age_group` = %s))",
                $age_group
            );
        }

        $sql             .= ' ORDER BY l.`id` ASC';
        $league_standings = wp_cache_get( md5( $sql ), 'league_standings' );
        if ( ! $league_standings ) {
            $league_standings = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $league_standings, 'league_standings' );
        }
        return is_array( $league_standings ) ? $league_standings : array();
    }
}
