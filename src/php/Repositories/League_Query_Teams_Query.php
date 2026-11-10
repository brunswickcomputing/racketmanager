<?php
/**
 * League teams query builder and executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Util\Util;

/**
 * Query builder and executor for league teams queries.
 */
class League_Query_Teams_Query {
    /**
     * WordPress database object.
     *
     * @var object|null
     */
    private ?object $wpdb;

    /**
     * Constructor.
     *
     * @param object|null $wpdb Database object.
     */
    public function __construct( ?object $wpdb ) {
        $this->wpdb = $wpdb;
    }

    /**
     * Execute query for league teams or count.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Query filter arguments.
     * @param bool  $count     Whether to return a count instead of results.
     * @return array|int Array of team rows or total count if count is true.
     */
    public function execute( int $league_id, array $args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }

        $sql          = $this->build_base_sql( $count );
        $query_params = array( $league_id );

        $sql .= $this->build_season_filter( $args, $query_params );
        $sql .= $this->build_team_filters( $args, $query_params );

        $cache = $args['cache'] ?? true;
        if ( $count ) {
            return $this->execute_count( $sql, $query_params, (bool) $cache );
        }

        $sql .= $this->build_order( $args );

        return $this->execute_select( $sql, $query_params, (bool) $cache );
    }

    /**
     * Build base SELECT and FROM SQL clauses.
     *
     * @param bool $count Whether to build count query projection.
     * @return string SQL clause.
     */
    private function build_base_sql( bool $count ): string {
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';

        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT B.`id` AS `id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating`';
        }

        return $sql . " FROM $teams_table B INNER JOIN $league_teams_table A ON B.id = A.team_id WHERE `league_id` = %d";
    }

    /**
     * Build season filter clause.
     *
     * @param array $args         Query arguments.
     * @param array $query_params Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_season_filter( array $args, array &$query_params ): string {
        $sql = '';
        if ( empty( $args['skip_season_filter'] ) ) {
            $season = $args['season'] ?? null;
            if ( empty( $season ) ) {
                $sql            = ' AND A.`season` = %s';
                $query_params[] = $args['current_season'] ?? '';
            } elseif ( 'any' === $season ) {
                $sql = " AND A.`season` != ''";
            } else {
                $sql            = ' AND A.`season` = %s';
                $query_params[] = $season;
            }
        }

        return $sql;
    }

    /**
     * Build team profile / status filter clause.
     *
     * @param array $args         Query arguments.
     * @param array $query_params Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_status_filter( array $args, array &$query_params ): string {
        $sql    = '';
        $status = $args['status'] ?? null;
        if ( ! empty( $status ) ) {
            if ( 'active' === $status ) {
                $sql = ' AND A.`profile` != 3';
            } elseif ( 1 === $status ) {
                $sql            = ' AND A.`profile` = %d';
                $query_params[] = $status;
            }
        }

        return $sql;
    }

    /**
     * Build team-specific filter clauses.
     *
     * @param array $args         Query arguments.
     * @param array $query_params Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_team_filters( array $args, array &$query_params ): string {
        $sql = '';

        if ( ! empty( $args['rank'] ) ) {
            $sql           .= ' AND A.`rank` = %s';
            $query_params[] = $args['rank'];
        }
        if ( ! empty( $args['home'] ) ) {
            $sql .= ' AND B.`home` = 1';
        }

        $sql .= $this->build_status_filter( $args, $query_params );

        if ( ! empty( $args['club'] ) ) {
            $sql           .= ' AND B.`club_id` = %d';
            $query_params[] = $args['club'];
        }
        if ( ! empty( $args['team_name'] ) ) {
            $sql           .= ' AND B.`title` = %s';
            $query_params[] = $args['team_name'];
        }
        if ( ! empty( $args['team_id'] ) ) {
            $sql           .= ' AND B.`id` = %d';
            $query_params[] = $args['team_id'];
        }
        if ( ! empty( $args['active'] ) ) {
            $sql .= " AND A.`status` != 'W'";
        }

        return $sql;
    }

    /**
     * Build order by clause.
     *
     * @param array $args Query arguments.
     * @return string SQL clause.
     */
    private function build_order( array $args ): string {
        $orderby = $args['orderby'] ?? array();
        return Util::order_by_string( $orderby );
    }

    /**
     * Execute count query with caching under leaguetable group.
     *
     * @param string $sql          SQL query.
     * @param array  $query_params Prepared parameters.
     * @param bool   $cache        Whether to use cache.
     * @return int Total count.
     */
    private function execute_count( string $sql, array $query_params, bool $cache ): int {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic SQL with placeholders.
        $prepared  = $this->wpdb->prepare( $sql, $query_params );
        $cache_key = md5( $prepared );
        $result    = wp_cache_get( $cache_key, 'leaguetable' );
        if ( ! $result || ! $cache ) {
            $result = (int) $this->wpdb->get_var( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( $cache_key, $result, 'leaguetable' );
        }

        return (int) $result;
    }

    /**
     * Execute select query with caching under leaguetable group.
     *
     * @param string $sql          SQL query.
     * @param array  $query_params Prepared parameters.
     * @param bool   $cache        Whether to use cache.
     * @return array Array of team rows.
     */
    private function execute_select( string $sql, array $query_params, bool $cache ): array {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared dynamic SQL with placeholders.
        $prepared = $this->wpdb->prepare( $sql, $query_params );
        $result   = wp_cache_get( $prepared, 'leaguetable' );
        if ( ! $result || ! $cache ) {
            $result = $this->wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( $prepared, $result, 'leaguetable' );
        }

        return is_array( $result ) ? $result : array();
    }
}
