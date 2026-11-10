<?php
/**
 * Fixture find all query executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Query builder and executor for find_all matches queries.
 */
class Fixture_Find_All_Query {
    /**
     * SQL WHERE clause keyword with surrounding whitespace.
     */
    private const string SQL_WHERE = ' WHERE ';

    /**
     * Hour time offset format suffix.
     */
    private const string SQL_TIME_OFFSET_SUFFIX = ':00:00';

    /**
     * WordPress database object.
     *
     * @var wpdb|null
     */
    private ?wpdb $wpdb;

    /**
     * Matches database table name.
     *
     * @var string
     */
    private string $table_name;

    /**
     * Fixture repository for hydration.
     *
     * @var Fixture_Repository_Interface
     */
    private Fixture_Repository_Interface $fixture_repository;

    /**
     * Rubber repository for player match rubber loading.
     *
     * @var Rubber_Repository_Interface|null
     */
    private ?Rubber_Repository_Interface $rubber_repository;

    /**
     * Constructor.
     *
     * @param wpdb|null                        $wpdb               Database object.
     * @param string                           $table_name         Matches table name.
     * @param Fixture_Repository_Interface     $fixture_repository Fixture repository.
     * @param Rubber_Repository_Interface|null $rubber_repository  Rubber repository.
     */
    public function __construct(
        ?wpdb $wpdb,
        string $table_name,
        Fixture_Repository_Interface $fixture_repository,
        ?Rubber_Repository_Interface $rubber_repository = null
    ) {
        $this->wpdb               = $wpdb;
        $this->table_name         = $table_name;
        $this->fixture_repository = $fixture_repository;
        $this->rubber_repository  = $rubber_repository;
    }

    /**
     * Execute find_all query.
     *
     * @param array $match_args Query arguments.
     * @return array|int Array of Fixture objects or integer count.
     * Note: Legacy filter values are currently interpolated directly into this query without preparation.
     */
    public function execute( array $match_args = array() ): array|int {
        $defaults = array(
            'leagueId'            => false,
            'season'              => false,
            'final'               => false,
            'competition_type'    => false,
            'orderby'             => array(
                'league_id' => 'ASC',
                'id'        => 'ASC',
            ),
            'competition_id'      => false,
            'event_id'            => false,
            'confirmed'           => false,
            'match_date'          => false,
            'time'                => false,
            'timeOffset'          => false,
            'history'             => false,
            'club'                => false,
            'league_name'         => false,
            'team'                => false,
            'team_name'           => false,
            'home_team'           => false,
            'home_club'           => false,
            'away_team'           => false,
            'match_day'           => false,
            'competition_name'    => false,
            'count'               => false,
            'confirmationPending' => false,
            'resultPending'       => false,
            'status'              => false,
            'tournament_id'       => false,
            'player'              => false,
            'type'                => false,
            'complete'            => false,
            'age_group'           => false,
        );

        $match_args = array_merge( $defaults, $match_args );
        $count      = (bool) $match_args['count'];

        $prefix = $this->wpdb ? $this->wpdb->prefix : 'wp_';

        $leagues_table        = $prefix . 'racketmanager_leagues';
        $events_table         = $prefix . 'racketmanager_events';
        $competitions_table   = $prefix . 'racketmanager_competitions';
        $teams_table          = $prefix . 'racketmanager_teams';
        $tournaments_table    = $prefix . 'racketmanager_tournaments';
        $team_players_table   = $prefix . 'racketmanager_team_players';
        $rubbers_table        = $prefix . 'racketmanager_rubbers';
        $rubber_players_table = $prefix . 'racketmanager_rubber_players';

        $sql_from = " FROM $this->table_name AS m, $leagues_table AS l" . $this->build_find_all_player_from(
            $match_args['player'],
            $match_args['tournament_id'],
            $team_players_table,
            $rubbers_table,
            $rubber_players_table
        );

        $sql_fields = $this->build_find_all_fields(
            $count,
            $match_args['confirmationPending'],
            $match_args['resultPending']
        );

        $filter_sql  = $count ? '' : self::SQL_WHERE . 'm.`league_id` = l.`id`';
        $filter_sql .= $this->build_find_all_competition_filters(
            $match_args,
            $leagues_table,
            $events_table,
            $competitions_table,
            $tournaments_table
        );
        $filter_sql .= $this->build_find_all_time_filters( $match_args );
        $filter_sql .= $this->build_find_all_participant_filters( $match_args, $teams_table );
        $filter_sql .= $this->build_find_all_player_filter( $match_args['player'], $match_args['tournament_id'] );
        $filter_sql .= $this->build_find_all_classification_filters(
            $match_args['type'],
            $match_args['complete'],
            $match_args['age_group'],
            $leagues_table,
            $events_table,
            $competitions_table
        );

        if ( $count ) {
            return $this->execute_find_all_count( $filter_sql );
        }

        return $this->execute_find_all_rows(
            $sql_fields . $sql_from . $filter_sql,
            $match_args['orderby'],
            $match_args['player']
        );
    }

    /**
     * Build FROM clause additions for player filtering in find_all.
     *
     * @param mixed  $player               Player ID.
     * @param mixed  $tournament_id        Tournament ID.
     * @param string $team_players_table   Team players table name.
     * @param string $rubbers_table        Rubbers table name.
     * @param string $rubber_players_table Rubber players table name.
     * @return string FROM clause fragment.
     */
    private function build_find_all_player_from(
        mixed $player,
        mixed $tournament_id,
        string $team_players_table,
        string $rubbers_table,
        string $rubber_players_table
    ): string {
        if ( ! $player ) {
            return '';
        }

        if ( $tournament_id ) {
            return " ,$team_players_table tp";
        }

        return " ,$rubbers_table r, $rubber_players_table rp";
    }

    /**
     * Build SELECT fields clause for find_all.
     *
     * @param bool  $count                Whether query is a count query.
     * @param mixed $confirmation_pending Confirmation pending offset.
     * @param mixed $result_pending       Result pending offset.
     * @return string SELECT fields clause.
     */
    private function build_find_all_fields( bool $count, mixed $confirmation_pending, mixed $result_pending ): string {
        if ( $count ) {
            return '';
        }

        $sql_fields = "SELECT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, m.`confirmed`, m.`home_captain`, m.`away_captain`, m.`comments`, m.`updated`, `event_id`, m.`status`, `leg`, `winner_id_tie`, `loser_id_tie`";

        if ( $confirmation_pending ) {
            $formatted_confirmation_pending = intval( $confirmation_pending ) . self::SQL_TIME_OFFSET_SUFFIX;
            $sql_fields                    .= ",ADDTIME(`updated`,'" . $formatted_confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $formatted_confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $formatted_result_pending = intval( $result_pending ) . self::SQL_TIME_OFFSET_SUFFIX;
            $sql_fields              .= ",ADDTIME(`date`,'" . $formatted_result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $formatted_result_pending . "')), '%H')/24 as overdue_time";
        }

        return $sql_fields;
    }

    /**
     * Build competition and tournament filter SQL clauses for find_all.
     *
     * @param array  $args               Query arguments.
     * @param string $leagues_table      Leagues table name.
     * @param string $events_table       Events table name.
     * @param string $competitions_table Competitions table name.
     * @param string $tournaments_table  Tournaments table name.
     * @return string Filter SQL clauses.
     */
    private function build_find_all_competition_filters(
        array $args,
        string $leagues_table,
        string $events_table,
        string $competitions_table,
        string $tournaments_table
    ): string {
        $sql = '';

        if ( $args['match_date'] ) {
            $sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $args['match_date'] ) ) . "', `date`) = 0";
        }
        if ( $args['competition_name'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` in (SELECT e.`id` FROM $events_table e, $competitions_table c WHERE c.`name` = '" . $args['competition_name'] . "' AND e.`competition_id` = c.`id`))";
        }
        if ( $args['competition_id'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` IN (select `id` from $events_table WHERE `competition_id` = '" . $args['competition_id'] . "') )";
        }
        if ( $args['event_id'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` = '" . $args['event_id'] . "')";
        }
        if ( $args['leagueId'] ) {
            $sql .= " AND `league_id`  = '" . $args['leagueId'] . "'";
        }
        if ( $args['league_name'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `title` = '" . $args['league_name'] . "')";
        }
        if ( $args['season'] ) {
            $sql .= " AND `season`  = '" . $args['season'] . "'";
        }
        if ( $args['final'] ) {
            $sql .= 'all' === $args['final']
                ? " AND `final` != ''"
                : " AND `final`  = '" . $args['final'] . "'";
        }
        if ( $args['competition_type'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` in (select e.`id` from $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` AND c.`type` = '" . $args['competition_type'] . "'))";
        }
        if ( $args['tournament_id'] ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` in (SELECT e.`id` FROM $events_table e, $competitions_table c, $tournaments_table t WHERE e.`competition_id` = c.`id` AND c.`id` = t.`competition_id` AND t.`id` = " . $args['tournament_id'] . '))';
        }

        return $sql;
    }

    /**
     * Build time and status filter SQL clauses for find_all.
     *
     * @param array $args Query arguments.
     * @return string Filter SQL clauses.
     */
    private function build_find_all_time_filters( array $args ): string {
        $sql         = '';
        $time_offset = $args['timeOffset']
            ? intval( $args['timeOffset'] ) . self::SQL_TIME_OFFSET_SUFFIX
            : '00:00:00';

        if ( $args['status'] ) {
            $sql .= " AND `confirmed` = '" . $args['status'] . "'";
        }
        if ( $args['confirmed'] ) {
            $sql .= " AND `confirmed` in ('P','A','C')";
            if ( $time_offset ) {
                $sql .= " AND ADDTIME(`updated`,'" . $time_offset . "') <= NOW()";
            }
        }
        if ( $args['time'] ) {
            if ( 'latest' === $args['time'] ) {
                $sql .= " AND (`home_points` != '' OR `away_points` != '')";
            } elseif ( 'outstanding' === $args['time'] ) {
                $sql .= " AND ADDTIME(`date`,'" . $time_offset . "') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
            } elseif ( is_numeric( $args['time'] ) ) {
                $sql .= ' AND m.`date` > now() - INTERVAL ' . $args['time'] . ' DAY';
            }
        }
        if ( $args['history'] ) {
            $sql .= ' AND `updated` >= NOW() - INTERVAL ' . $args['history'] . ' DAY';
        }

        return $sql;
    }

    /**
     * Build club, team, and match day filter SQL clauses for find_all.
     *
     * @param array  $args        Query arguments.
     * @param string $teams_table Teams table name.
     * @return string Filter SQL clauses.
     */
    private function build_find_all_participant_filters( array $args, string $teams_table ): string {
        $sql = '';

        if ( $args['club'] ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = " . $args['club'] . ") OR `away_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = " . $args['club'] . '))';
        }
        if ( $args['team'] ) {
            $sql .= ' AND (`home_team` = ' . $args['team'] . ' OR `away_team` = ' . $args['team'] . ')';
        }
        if ( $args['home_club'] ) {
            $sql .= " AND `home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = " . $args['home_club'] . ')';
        }
        if ( ! empty( $args['home_team'] ) ) {
            $sql .= ' AND `home_team` = ' . $args['home_team'] . ' ';
        }
        if ( ! empty( $args['away_team'] ) ) {
            $sql .= ' AND `away_team` = ' . $args['away_team'] . ' ';
        }
        if ( ! empty( $args['team_name'] ) ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM $teams_table WHERE `title` LIKE '%" . $args['team_name'] . "%') OR `away_team` IN (SELECT `id` FROM $teams_table WHERE `title` LIKE '%" . $args['team_name'] . "%'))";
        }
        if ( $args['match_day'] && intval( $args['match_day'] ) > 0 ) {
            $sql .= ' AND `match_day` = ' . $args['match_day'] . ' ';
        }

        return $sql;
    }

    /**
     * Build player filter SQL clauses for find_all.
     *
     * @param mixed $player        Player ID.
     * @param mixed $tournament_id Optional tournament ID.
     * @return string Filter SQL clauses.
     */
    private function build_find_all_player_filter( mixed $player, mixed $tournament_id ): string {
        if ( ! $player ) {
            return '';
        }

        if ( $tournament_id ) {
            return " AND ((m.`home_team` = tp.`team_id` AND tp.`player_id` = '$player') OR (m.`away_team` = tp.`team_id` AND tp.`player_id` = '$player'))";
        }

        return " AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
    }

    /**
     * Build classification filter SQL clauses for find_all.
     *
     * @param mixed  $type               Sport/event type.
     * @param mixed  $complete           Whether match is complete.
     * @param mixed  $age_group          Age group.
     * @param string $leagues_table      Leagues table name.
     * @param string $events_table       Events table name.
     * @param string $competitions_table Competitions table name.
     * @return string Filter SQL clauses.
     */
    private function build_find_all_classification_filters(
        mixed $type,
        mixed $complete,
        mixed $age_group,
        string $leagues_table,
        string $events_table,
        string $competitions_table
    ): string {
        $sql = '';

        if ( $type ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` in (select e.`id` from $events_table e WHERE e.`type` like '%%" . $type . "%%'))";
        }
        if ( $complete ) {
            $sql .= ' AND m.`winner_id` != 0';
        }
        if ( $age_group ) {
            $sql .= " AND `league_id` in (select `id` from $leagues_table WHERE `event_id` in (SELECT e.`id` FROM $events_table e, $competitions_table c WHERE e.`competition_id` = c.`id` and `age_group` = '" . $age_group . "'))";
        }

        return $sql;
    }

    /**
     * Execute count query for find_all.
     *
     * @param string $filter_sql Accumulated filter SQL clauses.
     * @return int Match count.
     */
    private function execute_find_all_count( string $filter_sql ): int {
        if ( ! $this->wpdb ) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM $this->table_name" . ( $filter_sql ? self::SQL_WHERE . substr( $filter_sql, 5 ) : '' );
        return intval(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Legacy dynamic query; filters are interpolated without prepare(), pending remediation.
            $this->wpdb->get_var( $sql )
        );
    }

    /**
     * Execute row query for find_all with caching and hydration.
     *
     * @param string $query_sql Base query SQL.
     * @param mixed  $orderby   Order-by specification.
     * @param mixed  $player    Optional player ID filter.
     * @return Fixture[] Hydrated fixture objects.
     */
    private function execute_find_all_rows( string $query_sql, mixed $orderby, mixed $player ): array {
        if ( ! $this->wpdb ) {
            return array();
        }

        $order     = Util::order_by_string( $orderby );
        $sql       = $query_sql . $order;
        $cache_key = md5( $sql );
        $matches   = wp_cache_get( $cache_key, 'matches' );
        if ( ! $matches ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Legacy dynamic query; filters are interpolated without prepare(), pending remediation.
            $matches = $this->wpdb->get_results( $sql );
            wp_cache_set( $cache_key, $matches, 'matches' );
        }

        if ( ! is_array( $matches ) ) {
            return array();
        }

        return $this->hydrate_find_all_matches( $matches, $player );
    }

    /**
     * Hydrate fixtures and optionally attach rubbers for find_all.
     *
     * @param array $matches Database rows or match IDs.
     * @param mixed $player  Optional player ID filter.
     * @return Fixture[] Hydrated fixture objects.
     */
    private function hydrate_find_all_matches( array $matches, mixed $player ): array {
        $hydrated_matches = array();
        foreach ( $matches as $i => $match ) {
            $match_id = (int) ( is_object( $match ) ? $match->id : $match );
            $fixture  = $this->fixture_repository->find_by_id( $match_id );
            if ( $player && $fixture ) {
                $rubber_repo = $this->rubber_repository ?? new Rubber_Repository();
                $fixture->set_rubbers( $rubber_repo->find_by_fixture_id( (int) $fixture->get_id(), (int) $player ) );
            }
            if ( $fixture ) {
                $hydrated_matches[ $i ] = $fixture;
            }
        }

        return $hydrated_matches;
    }
}
