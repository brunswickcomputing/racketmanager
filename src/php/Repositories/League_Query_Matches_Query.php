<?php
/**
 * League match query builder and executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Util\Util;

/**
 * Query builder and executor for league matches queries.
 */
class League_Query_Matches_Query {
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
     * Execute league match query.
     *
     * Note: Legacy raw concatenation of $query_args['match_date'] is currently interpolated without parameterised prepare placeholders.
     *
     * @param int   $league_id  The league ID.
     * @param array $query_args Query filter arguments.
     * @param bool  $count      Whether to return a count instead of results.
     * @return array|int Array of match records or total count if count is true.
     */
    public function execute( int $league_id, array $query_args, bool $count = false ): array|int {
        if ( ! $this->wpdb ) {
            return $count ? 0 : array();
        }

        $matches_table        = $this->wpdb->prefix . 'racketmanager_matches';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $league_teams_table   = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table          = $this->wpdb->prefix . 'racketmanager_teams';

        $args     = array( $league_id );
        $sql_from = " FROM $matches_table m";
        $sql      = ' WHERE m.`league_id` = %d';

        $sql .= $this->build_season_filter( $query_args, $args );
        $sql .= $this->build_final_filter( $query_args, $args );
        $sql .= $this->build_team_filters( $query_args, $args );
        $sql .= $this->build_match_day_filter( $query_args, $args );
        $sql .= $this->build_points_filters( $query_args );
        $sql .= $this->build_time_and_outcome_filters( $query_args, $args );
        $sql .= $this->build_extra_filters(
            $query_args,
            $teams_table,
            $args,
            $sql_from,
            $rubbers_table,
            $rubber_players_table,
            $league_teams_table
        );

        $sql_select   = $count ? 'SELECT COUNT(distinct m.id)' : 'SELECT distinct m.*';
        $bypass_cache = false === ( $query_args['cache'] ?? true );

        if ( $count ) {
            $full_sql = $sql_select . $sql_from . $sql;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic query containing pre-prepared parameters; legacy query has raw match_date concatenation.
            $prepared = $this->wpdb->prepare( $full_sql, $args );
            return $this->execute_count( $prepared, $bypass_cache );
        }

        $sql     .= $this->build_order_and_limit( $query_args );
        $full_sql = $sql_select . $sql_from . $sql;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic query containing pre-prepared parameters; legacy query has raw match_date concatenation.
        $prepared = $this->wpdb->prepare( $full_sql, $args );
        return $this->execute_select( $prepared, $bypass_cache );
    }

    /**
     * Build season filter clause.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_season_filter( array $query_args, array &$args ): string {
        $season = $query_args['season'] ?? null;
        if ( 'all' !== $season && 'any' !== $season && empty( $query_args['skip_season_filter'] ) ) {
            $args[] = empty( $season ) ? ( $query_args['current_season'] ?? '' ) : $season;
            return ' AND m.`season` = %s';
        }
        return '';
    }

    /**
     * Build final / championship filter clause.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_final_filter( array $query_args, array &$args ): string {
        $final           = $query_args['final'] ?? null;
        $is_championship = $query_args['is_championship'] ?? false;
        $sql             = " AND ( m.`final` = '' OR m.`final` IS NULL )";

        if ( $final ) {
            $sql = '';
            if ( 'all' !== $final ) {
                $args[] = htmlspecialchars( wp_strip_all_tags( $final ) );
                $sql    = ' AND m.`final` = %s';
            }
        } elseif ( $is_championship ) {
            $sql = ' AND m.`final` IS NOT NULL';
        }

        return $sql;
    }

    /**
     * Build team filter clauses.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_team_filters( array $query_args, array &$args ): string {
        $team_id   = $query_args['team_id'] ?? null;
        $team_pair = $query_args['team_pair'] ?? null;
        $home_team = $query_args['home_team'] ?? null;
        $away_team = $query_args['away_team'] ?? null;

        if ( $team_id ) {
            $args[] = $team_id;
            $args[] = $team_id;
            return ' AND (m.`home_team` = %d OR m.`away_team` = %d)';
        }

        if ( is_array( $team_pair ) && count( $team_pair ) === 2 ) {
            $args[] = intval( $team_pair[0] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[0] );
            return ' AND ( (`home_team` = %d AND `away_team` = %d) OR (`home_team` = %d AND `away_team` = %d) )';
        }

        $sql = '';
        if ( ! empty( $home_team ) ) {
            $sql   .= ' AND m.`home_team` = %d';
            $args[] = (int) $home_team;
        }
        if ( ! empty( $away_team ) ) {
            $sql   .= ' AND m.`away_team` = %d';
            $args[] = (int) $away_team;
        }
        return $sql;
    }

    /**
     * Build match day filter clause.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_match_day_filter( array $query_args, array &$args ): string {
        $match_day      = $query_args['match_day'] ?? null;
        $standingstable = $query_args['standingstable'] ?? false;
        if ( $match_day && intval( $match_day ) > 0 ) {
            $args[] = intval( $match_day );
            if ( $standingstable ) {
                return ' AND m.`match_day` <= %d';
            }
            return ' AND m.`match_day` = %d';
        }
        return '';
    }

    /**
     * Build home/away points filter clauses.
     *
     * @param array $query_args Query arguments.
     * @return string SQL clause.
     */
    private function build_points_filters( array $query_args ): string {
        $time        = $query_args['time'] ?? null;
        $home_points = $query_args['home_points'] ?? '';
        $away_points = $query_args['away_points'] ?? '';
        $sql         = '';

        if ( 'latest' === $time ) {
            $home_points = false;
            $away_points = false;
            $sql        .= " AND (m.`home_points` != '' OR m.`away_points` != '')";
        }

        if ( '' !== $home_points && false !== $home_points ) {
            $sql .= $this->build_single_points_filter( 'home_points', $home_points );
        }

        if ( $away_points ) {
            $sql .= $this->build_single_points_filter( 'away_points', $away_points );
        }

        return $sql;
    }

    /**
     * Build a single column points filter clause.
     *
     * @param string $column Column name.
     * @param mixed  $points Filter points value.
     * @return string SQL clause.
     */
    private function build_single_points_filter( string $column, mixed $points ): string {
        $sql = '';
        if ( 'null' === $points ) {
            $sql = " AND m.`$column` IS NULL";
        } elseif ( 'not_null' === $points ) {
            $sql = " AND m.`$column` IS NOT NULL";
        } elseif ( 'not_empty' === $points ) {
            $sql = " AND m.`$column` != ''";
        }
        return $sql;
    }

    /**
     * Build winner, loser, and time filter clauses.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_time_and_outcome_filters( array $query_args, array &$args ): string {
        $sql       = '';
        $winner_id = $query_args['winner_id'] ?? null;
        $loser_id  = $query_args['loser_id'] ?? null;

        if ( $winner_id ) {
            $sql   .= ' AND m.`winner_id` = %d';
            $args[] = (int) $winner_id;
        }
        if ( $loser_id ) {
            $sql   .= ' AND m.`loser_id` = %d';
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

        return $sql;
    }

    /**
     * Build additional filters (confirmed, leg, player, withdrawn, club, pending, status).
     *
     * @param array  $query_args           Query arguments.
     * @param string $teams_table          Teams table name.
     * @param array  $args                 Prepared arguments list.
     * @param string $sql_from             FROM clause to append joins to.
     * @param string $rubbers_table        Rubbers table name.
     * @param string $rubber_players_table Rubber players table name.
     * @param string $league_teams_table   League teams table name.
     * @return string SQL clause.
     */
    private function build_extra_filters(
        array $query_args,
        string $teams_table,
        array &$args,
        string &$sql_from,
        string $rubbers_table,
        string $rubber_players_table,
        string $league_teams_table
    ): string {
        $sql = '';

        if ( ! empty( $query_args['confirmed'] ) ) {
            $sql .= " AND m.`confirmed` = 'Y'";
        }
        if ( ! empty( $query_args['leg'] ) ) {
            $sql   .= ' AND m.`leg` = %s';
            $args[] = $query_args['leg'];
        }
        if ( ! empty( $query_args['player'] ) ) {
            $sql_from .= ", $rubbers_table r, $rubber_players_table rp";
            $sql      .= ' AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = %s';
            $args[]    = $query_args['player'];
        }
        if ( empty( $query_args['withdrawn'] ) ) {
            $sql_from .= ", $league_teams_table t1, $league_teams_table t2";
            $sql      .= " AND m.`home_team` = t1.`team_id` AND t1.`league_id` = m.`league_id` AND t1.`season` = m.`season` AND t1.`status` != 'W'";
            $sql      .= " AND m.`away_team` = t2.`team_id` AND t2.`league_id` = m.`league_id` AND t2.`season` = m.`season` AND t2.`status` != 'W'";
        }
        if ( ! empty( $query_args['club'] ) ) {
            $sql   .= " AND (m.`home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d) OR m.`away_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d))";
            $args[] = (int) $query_args['club'];
            $args[] = (int) $query_args['club'];
        }
        if ( ! empty( $query_args['pending'] ) ) {
            $sql .= ' AND m.winner_id = 0';
        }
        $sql .= $this->build_status_filter( $query_args, $args );

        return $sql;
    }

    /**
     * Build match status filter clause.
     *
     * @param array $query_args Query arguments.
     * @param array $args       Prepared arguments list.
     * @return string SQL clause.
     */
    private function build_status_filter( array $query_args, array &$args ): string {
        if ( empty( $query_args['status_value'] ) ) {
            return '';
        }

        $args[] = $query_args['status_value'];
        if ( 'not' === ( $query_args['status_compare'] ?? '' ) ) {
            return ' AND m.`status` != %d';
        }
        return ' AND m.`status` = %d';
    }

    /**
     * Build order by and limit clauses for match query.
     *
     * @param array $query_args Query arguments.
     * @return string SQL clause.
     */
    private function build_order_and_limit( array $query_args ): string {
        $time    = $query_args['time'] ?? null;
        $orderby = $query_args['orderby'] ?? array();
        if ( 'next' === $time ) {
            $orderby['date'] = 'ASC';
        } elseif ( 'prev' === $time || 'latest' === $time ) {
            $orderby['date'] = 'DESC';
        }

        $sql = Util::order_by_string( $orderby );

        $limit  = $query_args['limit'] ?? 0;
        $offset = $query_args['offset'] ?? 0;
        if ( intval( $limit > 0 ) ) {
            $sql .= ' LIMIT ' . intval( $offset ) . ',' . intval( $limit );
        }

        return $sql;
    }

    /**
     * Execute count query with caching under num_matches group.
     *
     * @param string $prepared     Pre-prepared SQL string.
     * @param bool   $bypass_cache Whether to bypass cache.
     * @return int Total count of matches.
     */
    private function execute_count( string $prepared, bool $bypass_cache ): int {
        $cache_key = md5( $prepared );
        $result    = wp_cache_get( $cache_key, 'num_matches' );
        if ( ! $result || $bypass_cache ) {
            $result = (int) $this->wpdb->get_var( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( $cache_key, $result, 'num_matches' );
        }
        return (int) $result;
    }

    /**
     * Execute select query with caching under matches group.
     *
     * @param string $prepared     Pre-prepared SQL string.
     * @param bool   $bypass_cache Whether to bypass cache.
     * @return array Array of match records.
     */
    private function execute_select( string $prepared, bool $bypass_cache ): array {
        $cache_key = md5( $prepared );
        $result    = wp_cache_get( $cache_key, 'matches' );
        if ( ! $result || $bypass_cache ) {
            $result = $this->wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
            wp_cache_set( $cache_key, $result, 'matches' );
        }
        return is_array( $result ) ? $result : array();
    }
}
