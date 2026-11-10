<?php
declare( strict_types=1 );
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
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Util\Util;

/**
 * Class to implement the League repository
 */
class League_Repository implements League_Repository_Interface {
    /**
     * WordPress database object.
     *
     * @var object|null
     */
    private ?object $wpdb;

    /**
     * Leagues table name.
     *
     * @var string|null
     */
    private ?string $table_name = null;

    /**
     * Event repository instance or resolver closure.
     *
     * @var Event_Repository_Interface|Closure|null
     */
    private mixed $event_repository;

    /**
     * Lazy resolver returning the League_Query_Service (needed by Championship_Factory); kept lazy to avoid a container cycle.
     *
     * @var Closure|null
     */
    private ?Closure $league_query_service_resolver;

    /**
     * League cache invalidator collaborator.
     *
     * @var League_Cache_Invalidator
     */
    private League_Cache_Invalidator $cache_invalidator;

    /**
     * League_Repository constructor.
     *
     * @param object|null                             $db                            WordPress database object.
     * @param Event_Repository_Interface|Closure|null $event_repository              Event repository instance or resolver.
     * @param Closure|null                            $league_query_service_resolver League query service resolver closure.
     * @param League_Cache_Invalidator|null           $cache_invalidator             League cache invalidator collaborator.
     */
    public function __construct(
        ?object $db = null,
        Event_Repository_Interface|Closure|null $event_repository = null,
        ?Closure $league_query_service_resolver = null,
        ?League_Cache_Invalidator $cache_invalidator = null
    ) {
        $this->wpdb = $db ?? ( $GLOBALS['wpdb'] ?? null );
        if ( $this->wpdb ) {
            $prefix           = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
            $this->table_name = $prefix . 'racketmanager_leagues';
        }
        $this->event_repository              = $event_repository;
        $this->league_query_service_resolver = $league_query_service_resolver;
        $this->cache_invalidator             = $cache_invalidator ?? new League_Cache_Invalidator( $this->wpdb, $this->table_name );
    }

    /**
     * Save a league entity.
     *
     * @param object $entity The league entity to save.
     * @return bool|int The league ID on insert, true on successful update (or no changes), or false on failure.
     */
    public function save( object $entity ): bool|int {
        /** @var League $entity */
        $data        = array(
            'title'    => $entity->get_name(),
            'settings' => maybe_serialize( $entity->get_settings() ),
            'seasons'  => maybe_serialize( $entity->get_seasons() ),
            'sequence' => $entity->sequence,
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
                    $this->cache_invalidator->invalidate_event_leagues_cache( (int) $entity->get_event_id() );
                }
                return $this->wpdb->insert_id;
            }
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table_name,
            $data, // Data to update.
            array(
                'id' => $entity->get_id(),
            ), // Where clause.
            $data_format,
            array(
                '%d',
            ) // Where format.
        ) !== false;
        if ( $updated ) {
            wp_cache_delete( $entity->get_id(), 'leagues' );
            if ( $entity->get_name() ) {
                wp_cache_delete( $entity->get_name(), 'leagues' );
            }
            if ( $entity->get_event_id() ) {
                $this->cache_invalidator->invalidate_event_leagues_cache( (int) $entity->get_event_id() );
            }
        }
        return $updated;
    }

    /**
     * Find a league by its ID.
     *
     * @param int|string $id The league ID.
     * @return ?League The league object or null if not found.
     */
    public function find_by_id( $id ): ?League {
        if ( empty( $id ) ) {
            return null;
        }

        $league = wp_cache_get( $id, 'leagues' );

        if ( ! $league && $this->wpdb ) {
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            );
            $row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

            if ( $row ) {
                $league = $this->hydrate_league( $row );
                if ( $league ) {
                    wp_cache_set( $league->id, $league, 'leagues' );
                }
            }
        }

        return $league instanceof League ? $league : null;
    }

    /**
     * Find a league by its name or title.
     *
     * @param string $name The league title.
     * @return ?League The league object or null if not found.
     */
    public function find_by_name( string $name ): ?League {
        if ( empty( $name ) ) {
            return null;
        }

        $league = wp_cache_get( $name, 'leagues' );

        if ( ! $league && $this->wpdb ) {
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "SELECT * FROM $this->table_name WHERE `title` = %s LIMIT 1",
                $name
            );
            $row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

            if ( $row ) {
                $league = $this->hydrate_league( $row );
                if ( $league ) {
                    wp_cache_set( $name, $league, 'leagues' );
                    wp_cache_set( $league->id, $league, 'leagues' );
                }
            }
        }

        return $league instanceof League ? $league : null;
    }

    /**
     * Hydrate a League entity from a database row.
     *
     * @param object $row Database row object.
     * @return ?League Hydrated League entity or null.
     */
    private function hydrate_league( object $row ): ?League {
        $event = null;
        if ( ! empty( $row->event_id ) ) {
            if ( $this->event_repository instanceof Closure ) {
                $this->event_repository = ( $this->event_repository )();
            }
            $event = $this->event_repository instanceof Event_Repository_Interface ? $this->event_repository->find_by_id( (int) $row->event_id ) : null;
        }
        $league = League_Factory::from_object( $row, $event );
        if ( $league ) {
            $league->set_season();
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
                $factory              = new Championship_Factory( $this, $this->league_query_service_resolver );
                $league->championship = $factory->create( $league, $settings );
            }
        }
        return $league;
    }

    /**
     * Find the next sequence number for a league within an event.
     *
     * @param string $event_name The event name.
     * @return int The next sequence number.
     */
    public function find_next_sequence_number( string $event_name ): int {
        // We look for names starting with the shortcode followed by a space and digits.
        $prefix_like = $this->wpdb->esc_like( $event_name ) . ' %';
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED))
             FROM $this->table_name
             WHERE `title` LIKE %s",
            $prefix_like
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $max_sequence = (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        // If no leagues found, MAX returns NULL/0, so the next number is 1. Otherwise, increment the max.
        return $max_sequence + 1;
    }

    /**
     * Get leagues for an event
     *
     * @param int|null $event_id The event ID.
     * @param int|null $season   Optional season identifier.
     *
     * @return array Array of league database rows.
     */
    public function get_by_event_id( ?int $event_id, ?int $season = null ): array {
        if ( empty( $event_id ) ) {
            return array();
        }
        $version   = $this->cache_invalidator->get_event_leagues_version( $event_id );
        $cache_key = 'event_leagues_' . $event_id . '_' . ( $season ?? 'all' ) . '_v' . $version;
        $leagues   = wp_cache_get( md5( $cache_key ), 'leagues' );
        if ( ! is_array( $leagues ) ) {
            $search = '';
            if ( $season ) {
                $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $search = $this->wpdb->prepare( " AND `id` IN (SELECT DISTINCT `league_id` FROM $league_teams_table t, $this->table_name l WHERE t.`league_id` = l.`id` AND `season` = %d AND `event_id` = %d)", $season, $event_id );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and pre-prepared subquery interpolation.
                "SELECT * FROM $this->table_name WHERE `event_id` = %d $search ORDER BY `title`",
                $event_id
            );
            $leagues = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
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
     * @param int $event_id The event ID.
     * @return ?int The lowest league ID or null if none found.
     */
    public function get_lowest_league_id_by_event( int $event_id ): ?int {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $this->wpdb->prepare(
            "SELECT id
             FROM `$this->table_name`
             WHERE event_id = %d
             ORDER BY rank DESC, id DESC
             LIMIT 1",
            $event_id
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $result = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.

        return $result ? (int) $result : null;
    }

    /**
     * Delete a league by its ID.
     *
     * @param int $id The league ID.
     *
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $snapshot = $this->cache_invalidator->capture_snapshot( $id );
        $deleted  = $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
        if ( $deleted ) {
            $this->cache_invalidator->invalidate_after_delete( $id, $snapshot );
        }
        return $deleted;
    }

    /**
     * Update the title and optional sequence for a league.
     *
     * @param int         $id       The league ID.
     * @param string      $title    The new title.
     * @param string|null $sequence Optional new sequence number.
     * @return bool True on success, false on failure.
     */
    public function update_title_and_sequence( int $id, string $title, ?string $sequence = null ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $snapshot = $this->cache_invalidator->capture_snapshot( $id, '`title`, `event_id`' );
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
            $this->cache_invalidator->invalidate_after_update_title( $id, $title, $snapshot );
        }
        return $result;
    }

    /**
     * Delete all teams assigned to a league.
     *
     * @param int $league_id The league ID.
     * @return bool True on success, false on failure.
     */
    public function delete_league_teams( int $league_id ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        return $this->wpdb->delete( $table, array( 'league_id' => $league_id ), array( '%d' ) ) !== false;
    }

    /**
     * Delete a team from a league for a specific season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Mark a team as withdrawn from a league for a specific season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Get the match day number for a league and season based on query type.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param string $type      The match day query type ('last', 'next', 'current', 'latest').
     * @return ?int The match day number or null if none found.
     */
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
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) > 0 ORDER BY datediff LIMIT 1",
                $league_id,
                $season
            );
        } elseif ( 'next' === $type ) {
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) < 0 ORDER BY datediff DESC LIMIT 1",
                $league_id,
                $season
            );
        } else {
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
                "SELECT `id`, `match_day`, ABS(DATEDIFF(NOW(), `date`)) AS datediff FROM $matches_table WHERE `league_id` = %d AND `season` = %s ORDER BY datediff LIMIT 1",
                $league_id,
                $season
            );
        }
        $match     = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        $match_day = null;
        if ( $match ) {
            $match_day = (int) $match->match_day;
            wp_cache_set( $cache_key, $match_day, 'leagues_match_days' );
        }
        return $match_day;
    }

    /**
     * Query teams assigned to a league with filters or return the count.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Query filter arguments.
     * @param bool  $count     Whether to return a count instead of results.
     * @return array|int Array of team rows or total count if count is true.
     */
    public function query_league_teams( int $league_id, array $args, bool $count = false ): array|int {
        return new League_Query_Teams_Query( $this->wpdb )->execute( $league_id, $args, $count );
    }

    /**
     * Add a team to a league for a season.
     *
     * @param int         $league_id The league ID.
     * @param int         $team_id   The team ID.
     * @param string      $season    The season identifier.
     * @param string|null $rank      Optional ranking.
     * @param string|null $status    Optional status string.
     * @param int         $profile   Team profile type.
     * @return bool|int Insert ID on success, or false on failure/existing.
     */
    public function add_team_to_league( int $league_id, int $team_id, string $season, ?string $rank = null, ?string $status = null, int $profile = 1 ): bool|int {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $query = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT `id` FROM $table WHERE `team_id` = %d AND `season` = %s AND `league_id` = %d",
            $team_id,
            $season,
            $league_id
        );
        $existing = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        if ( $existing ) {
            return false;
        }
        $data   = array(
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

    /**
     * Get team details and league standings information for a team in a season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return ?object Team details object or null if not found.
     */
    public function get_team_details( int $league_id, int $team_id, string $season ): ?object {
        if ( ! $this->wpdb ) {
            return null;
        }
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare(
            "SELECT A.`title`, C.`captain`, A.`club_id`, C.`match_day`, C.`match_time`, A.`stadium`, A.`home`, A.`roster`, A.`profile`, A.`id`, A.`status`, A.`type`, A.`team_type`, C.`status` as `league_status`, C.`rating`, C.`rank`, C.`points_plus`, C.`points_minus`, C.`points_2_plus`, C.`points_2_minus`, C.`add_points`, C.`done_matches`, C.`won_matches`, C.`draw_matches`, C.`lost_matches`, C.`diff` FROM $league_teams_table C INNER JOIN $teams_table A ON A.`id` = C.`team_id` AND C.`league_id` = %d WHERE A.`id` = %d AND C.`season` = %s",
            $league_id,
            $team_id,
            $season
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $team = wp_cache_get( md5( $sql ), 'teamdetails' );
        if ( ! $team ) {
            $team = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            if ( $team ) {
                wp_cache_set( md5( $sql ), $team, 'teamdetails' );
            }
        }
        return $team ?: null;
    }

    /**
     * Get the rank of a team in a league for a season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @return ?int The team rank or null if not found.
     */
    public function get_rank( int $league_id, string $season, int $team_id ): ?int {
        if ( ! $this->wpdb ) {
            return null;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $sql   = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT `rank` FROM $table WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $league_id,
            $season,
            $team_id
        );
        $team_rank = wp_cache_get( md5( $sql ), 'team_rank' );
        if ( ! $team_rank ) {
            $team_rank = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( md5( $sql ), $team_rank, 'team_rank' );
        }
        return $team_rank && isset( $team_rank->rank ) ? (int) $team_rank->rank : null;
    }

    /**
     * Get the status of a team in a league for a season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @return ?string The team status or null if not found.
     */
    public function get_status( int $league_id, string $season, int $team_id ): ?string {
        if ( ! $this->wpdb ) {
            return null;
        }
        $table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $sql   = $this->wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT `status` FROM $table WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $league_id,
            $season,
            $team_id
        );
        $team_status = wp_cache_get( md5( $sql ), 'team_status' );
        if ( ! $team_status ) {
            $team_status = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( md5( $sql ), $team_status, 'team_status' );
        }
        return $team_status && isset( $team_status->status ) ? (string) $team_status->status : null;
    }

    /**
     * Update team standings manually with given points and match figures.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @param array  $points    Array containing points_plus, points_minus, and add_points.
     * @param array  $matches   Array containing match breakdown counts.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Update team standings data for a team in a league.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @param array  $data      Associative array of standings columns to update.
     * @return bool True on success, false on failure.
     */
    public function update_team_standings( int $league_id, string $season, int $team_id, array $data ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $table       = $this->wpdb->prefix . 'racketmanager_league_teams';
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

    /**
     * Query players that participated in league matches or return count.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Query filter arguments.
     * @param bool  $count     Whether to return a count instead of results.
     * @return array|int Array of player records or total count if count is true.
     */
    public function query_players( int $league_id, array $args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';

        $search_terms = array();
        $search_args  = array( $league_id );

        $season = $args['season'] ?? false;
        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        $team = $args['team'] ?? false;
        if ( $team ) {
            $search_terms[] = '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
            $search_args[]  = $team;
            $search_args[]  = 'home';
            $search_args[]  = $team;
            $search_args[]  = 'away';
        }
        $club = $args['club'] ?? false;
        if ( $club ) {
            $search_terms[] = "(( `home_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s))";
            $search_args[]  = $club;
            $search_args[]  = 'home';
            $search_args[]  = $club;
            $search_args[]  = 'away';
        }
        $search  = Util::search_string( $search_terms );
        $orderby = $args['orderby'] ?? array();
        $order   = Util::order_by_string( $orderby );

        if ( $count ) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT COUNT(*) FROM $rubber_players_table rp, $rubbers_table r, $matches_table m WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search;
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic SQL with placeholders.
            $prepared    = $this->wpdb->prepare( $sql, $search_args );
            $num_players = wp_cache_get( md5( $prepared ), 'league_rubber_players' );
            if ( ! $num_players ) {
                $num_players = (int) $this->wpdb->get_var( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
                wp_cache_set( md5( $prepared ), $num_players, 'league_rubber_players' );
            }
            return (int) $num_players;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT DISTINCT `player_id`, `club_player_id` FROM $rubber_players_table rp, $rubbers_table r, $matches_table m WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search . $order;
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $limit  = $args['limit'] ?? 0;
        $offset = $args['offset'] ?? 0;
        if ( intval( $limit > 0 ) ) {
            $sql          .= ' LIMIT %d, %d';
            $search_args[] = (int) $offset;
            $search_args[] = (int) $limit;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic SQL with placeholders.
        $prepared = $this->wpdb->prepare( $sql, $search_args );
        $players  = wp_cache_get( md5( $prepared ), 'league_rubber_players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( md5( $prepared ), $players, 'league_rubber_players' );
        }
        return is_array( $players ) ? $players : array();
    }

    /**
     * Get team information for a player in a league.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Filter arguments including player ID and optional season.
     * @return ?object Team object or null if not found.
     */
    public function get_player_team( int $league_id, array $args ): ?object {
        if ( ! $this->wpdb ) {
            return null;
        }
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';
        $club_players_table   = $this->wpdb->prefix . 'racketmanager_club_players';

        $search_terms = array();
        $search_args  = array( $league_id );

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
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT distinct t.`id`, t.`title` FROM $teams_table AS t, $rubbers_table AS r, $rubber_players_table AS rp, $matches_table AS m, $club_players_table AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND rp.`club_player_id` = ro.`id` AND ((rp.`player_team` = 'home' AND m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND m.`away_team` = t.`id`)) AND ro.`club_id` = t.`club_id` AND r.`match_id` = m.`id` AND m.`league_id` = %d " . $search;
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic SQL with placeholders.
        $prepared = $this->wpdb->prepare( $sql, $search_args );

        $teams = wp_cache_get( md5( $prepared ), 'player_team' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( md5( $prepared ), $teams, 'player_team' );
        }
        return ( ! empty( $teams ) && is_array( $teams ) ) ? $teams[0] : null;
    }

    /**
     * Update serialized seasons configuration for a league.
     *
     * @param int   $league_id The league ID.
     * @param array $seasons   Array of season data.
     * @return bool True on success, false on failure.
     */
    public function update_seasons( int $league_id, array $seasons ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $snapshot = $this->cache_invalidator->capture_snapshot( $league_id );
        $result   = $this->wpdb->update(
            $this->table_name,
            array( 'seasons' => maybe_serialize( $seasons ) ),
            array( 'id' => $league_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
        if ( $result ) {
            $this->cache_invalidator->invalidate_after_update_seasons( $league_id, $seasons, $snapshot );
        }
        return $result;
    }

    /**
     * Query matches in a league with filters or return the count.
     *
     * Note: Legacy raw concatenation of $query_args['match_date'] is currently interpolated without parameterised prepare placeholders.
     *
     * @param int   $league_id   The league ID.
     * @param array $query_args  Query filter arguments.
     * @param bool  $count       Whether to return a count instead of results.
     * @return array|int Array of match records or total count if count is true.
     */
    public function query_matches( int $league_id, array $query_args, bool $count = false ): array|int {
        return new League_Query_Matches_Query( $this->wpdb )->execute( $league_id, $query_args, $count );
    }

    /**
     * Retrieve league names projected as id and name.
     *
     * @return array Array of objects containing id and name.
     */
    public function find_names(): array {
        if ( ! $this->wpdb ) {
            return array();
        }
        $results = $this->wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name interpolation.
            "SELECT `id`, `title` AS `name` FROM $this->table_name ORDER BY `id`"
        );
        return is_array( $results ) ? $results : array();
    }

    /**
     * Get leagues from the database matching criteria.
     *
     * @param array $args Query arguments for filtering and pagination.
     * @return array Array of League objects.
     */
    public function find_all( array $args = array() ): array {
        $defaults         = array(
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
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $search_terms[] = $this->wpdb->prepare( "`event_id` in (select e.`id` from $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND c.`type` = %s)", $competition_type );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare(
            "SELECT `id` FROM $this->table_name $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $leagues = wp_cache_get( md5( $sql ), 'leagues' );
        if ( ! $leagues ) {
            $leagues = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
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
            if ( ! $season || ( isset( $league->event ) && method_exists( $league->event, 'get_season_by_name' ) && $league->event->get_season_by_name( $season ) ) ) {
                $hydrated_leagues[] = $league;
            }
        }
        return $hydrated_leagues;
    }

    /**
     * Get league standings summary rows.
     *
     * @param array $args Filter arguments (season, team, age_group).
     * @return array Array of standings summary rows.
     */
    public function get_league_standings( array $args = array() ): array {
        $defaults  = array(
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
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql .= $this->wpdb->prepare(
                " AND l.`id` IN (SELECT `id` FROM $this->table_name WHERE `event_id` IN (SELECT e.`id` FROM $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND `age_group` = %s))",
                $age_group
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        $sql             .= ' ORDER BY l.`id` ASC';
        $league_standings = wp_cache_get( md5( $sql ), 'league_standings' );
        if ( ! $league_standings ) {
            $league_standings = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic query.
            wp_cache_set( md5( $sql ), $league_standings, 'league_standings' );
        }
        return is_array( $league_standings ) ? $league_standings : array();
    }
}
