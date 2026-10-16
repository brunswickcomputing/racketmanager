<?php
/**
 * Event_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\DTO\Competition\Event_Details_DTO;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Util\Util;

/**
 * Class to implement the Event repository
 */
class Event_Repository implements Event_Repository_Interface {
    private ?object $wpdb = null;
    private ?string $table_name = null;

    public function __construct( ?object $db = null ) {
        $this->wpdb = $db ?? ( $GLOBALS['wpdb'] ?? null );
        if ( $this->wpdb ) {
            $this->table_name = $this->table( 'racketmanager_events' );
        }
    }

    private function table( string $suffix ): string {
        $prefix = isset( $this->wpdb->prefix ) ? (string) $this->wpdb->prefix : 'wp_';
        return $prefix . $suffix;
    }

    private function get_leagues_table(): string {
        if ( isset( $this->wpdb->racketmanager ) && is_string( $this->wpdb->racketmanager ) && '' !== $this->wpdb->racketmanager ) {
            return $this->wpdb->racketmanager;
        }
        return $this->table( 'racketmanager_leagues' );
    }

    public function save( object $entity ): bool|int {
        if ( ! $this->wpdb ) {
            return false;
        }
        /** @var Event $entity */
        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'           => $entity->get_name(),
                    'settings'       => maybe_serialize( $entity->get_settings() ),
                    // Persist seasons as JSON
                    'seasons'        => $entity->get_seasons_json(),
                    'type'           => $entity->get_type(),
                    'num_sets'       => $entity->get_num_sets(),
                    'num_rubbers'    => $entity->get_num_rubbers(),
                    'competition_id' => $entity->get_competition_id(),
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                )
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            return $this->wpdb->update(
                $this->table_name,
                array(
                    'name'           => $entity->get_name(),
                    'settings'       => maybe_serialize( $entity->get_settings() ),
                    // Persist seasons as JSON
                    'seasons'        => $entity->get_seasons_json(),
                    'type'           => $entity->get_type(),
                    'num_sets'       => $entity->get_num_sets(),
                    'num_rubbers'    => $entity->get_num_rubbers(),
                    'competition_id' => $entity->get_competition_id(),
                ), // Data to update
                array(
                    'id' => $entity->get_id()
                ), // Where clause
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                ),
                array(
                    '%d'
                ) // Where format
            ) !== false;
        }
    }

    public function find_by_id( $id ): ?Event {
        if ( empty( $id ) ) {
            return null;
        }
        $event = wp_cache_get( $id, 'events' );

        if ( ! $event && $this->wpdb ) {
            if ( is_numeric( $id ) ) {
                $search = "`id` = %d";
            } else {
                $search = "`name` = %s";
            }
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competition_id` FROM $this->table_name WHERE $search LIMIT 1",
                    $id
                )
            );

            if ( ! $row ) {
                return null;
            }
            $event = Event_Factory::from_object( $row );

            wp_cache_set( $event->id, $event, 'events' );
        }

        return $event ?: null;
    }

    public function find_by_name( string $name ): ?Event {
        if ( '' === $name ) {
            return null;
        }
        $event = wp_cache_get( $name, 'events' );
        if ( $event instanceof Event ) {
            return $event;
        }
        if ( ! $this->wpdb ) {
            return null;
        }
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competition_id` FROM $this->table_name WHERE `name` = %s LIMIT 1",
                $name
            )
        );
        if ( ! $row ) {
            return null;
        }
        $event = Event_Factory::from_object( $row );
        wp_cache_set( $event->id, $event, 'events' );
        return $event;
    }

    /**
     * Find all events for a competition.
     *
     * @param int $competition_id
     *
     * @return Event[]
     */
    public function find_by_competition_id( int $competition_id ): array {
        $events = wp_cache_get( md5( $competition_id ), 'events' );
        if ( ! $events && $this->wpdb ) {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `competition_id` = %d ORDER BY `name`",
                    $competition_id
                )
            );
            $events = array_map(
                function( $row ) {
                    return Event_Factory::from_object( $row );
                },
                $results ?: []
            );
            wp_cache_set( md5( $competition_id ), $events, 'events' );
        }
        return $events ?: [];
    }

    public function delete( int $id ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        if ( method_exists( $this->wpdb, 'delete' ) ) {
            return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
        }
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $this->table_name WHERE `id` = %d",
                $id
            )
        ) !== false;
    }

    /**
     * Retrieves events for a competition with counts of associated leagues, teams, and clubs.
     *
     * @param int $competition_id
     * @param int $season
     * @param int|null $min_fixtures
     *
     * @return Event_Details_DTO[]
     */
    public function find_events_by_competition_with_counts( int $competition_id, int $season, ?int $min_fixtures = 1): array {
        if ( ! $this->wpdb ) {
            return [];
        }
        $events_table = $this->table_name;
        $leagues_table = $this->get_leagues_table();
        $league_teams_table = $this->table( 'racketmanager_league_teams' );
        $teams_table = $this->table( 'racketmanager_teams' );
        $rubber_players_table = $this->table( 'racketmanager_rubber_players' );
        $rubbers_table = $this->table( 'racketmanager_rubbers' );
        $matches_table = $this->table( 'racketmanager_matches' );

        $player_activity_subquery = $this->wpdb->prepare(
            "SELECT l.event_id, rp.player_id FROM $rubber_players_table rp INNER JOIN $rubbers_table r ON rp.rubber_id = r.id INNER JOIN $matches_table f ON r.match_id = f.id AND f.season = %d INNER JOIN $leagues_table l ON f.league_id = l.id GROUP BY l.event_id, rp.player_id HAVING COUNT(rp.id) >= %d",
            $season,
            $min_fixtures
        );

        $query = $this->wpdb->prepare(
            "SELECT e.id as event_id, e.name as event_name, e.type as format, e.settings as settings, COUNT(DISTINCT l.id) as num_leagues, COUNT(DISTINCT lte.team_id) as num_teams, COUNT(DISTINCT t.club_id) as num_clubs, COUNT(DISTINCT active_players.player_id) as num_players FROM `$events_table` e LEFT JOIN `$leagues_table` l ON e.id = l.event_id LEFT JOIN `$league_teams_table` lte ON l.id = lte.league_id AND lte.season = %d LEFT JOIN `$teams_table` t ON lte.team_id = t.id LEFT JOIN ($player_activity_subquery) AS active_players ON e.id = active_players.event_id WHERE e.competition_id = %d GROUP BY e.id, e.name, e.type ORDER BY e.name",
            $season,
            $competition_id
        );

        $results = $this->wpdb->get_results($query);

        return array_map(function($row) {
            return new Event_Details_DTO( $row );
        }, $results ?: []);
    }

    public function insert_event( object $event ): int {
        if ( ! $this->wpdb ) {
            return 0;
        }
        $settings = array();
        $this->wpdb->query(
            $this->wpdb->prepare(
                "INSERT INTO $this->table_name (`name`, `competition_id`, `num_rubbers`, `num_sets`, `type`, `settings`) VALUES (%s, %d, %d, %d, %s, %s)",
                $event->name,
                $event->competition_id,
                $event->num_rubbers,
                $event->num_sets,
                $event->type,
                maybe_serialize( $settings )
            )
        );
        return (int) $this->wpdb->insert_id;
    }

    public function update_name( int $id, string $name ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $this->table_name SET `name` = %s WHERE `id` = %d",
                $name,
                $id
            )
        );
        return $result !== false;
    }

    public function update_settings_and_rules( int $id, array $settings, ?int $num_rubbers, ?int $num_sets, string $type ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $this->table_name SET `settings` = %s, `num_rubbers` = %d, `num_sets` = %d, `type` = %s WHERE `id` = %d",
                maybe_serialize( $settings ),
                $num_rubbers,
                $num_sets,
                $type,
                $id
            )
        );
        return $result !== false;
    }

    public function count_leagues( int $event_id ): int {
        if ( ! $this->wpdb ) {
            return 0;
        }
        $leagues_table = $this->get_leagues_table();
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(ID) FROM $leagues_table WHERE `event_id` = %d",
                $event_id
            )
        );
    }

    public function query_leagues( int $event_id, array $args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $args['count'] ?? false ? 0 : [];
        }
        $defaults = array(
            'offset'      => 0,
            'limit'       => 99999999,
            'orderby'     => array( 'title' => 'ASC' ),
            'consolation' => false,
            'count'       => false,
            'season'      => false,
        );
        $args        = array_merge( $defaults, $args );
        $offset      = $args['offset'];
        $limit       = $args['limit'];
        $orderby     = $args['orderby'];
        $consolation = $args['consolation'];
        $count       = $args['count'];
        $season      = $args['season'];

        $leagues_table      = $this->get_leagues_table();
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $search_terms   = array();
        $search_terms[] = $this->wpdb->prepare( '`event_id` = %d', $event_id );
        if ( $consolation ) {
            $search_terms[] = "`settings` LIKE '%\"is_consolation\";b:1;%'";
        }
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( "`id` IN (SELECT DISTINCT `league_id` FROM $league_teams_table t, $leagues_table l WHERE t.`league_id` = l.`id` AND `season` = %d AND `event_id` = %d)", intval( $season ), $event_id );
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql = "SELECT COUNT(*) FROM $leagues_table $search ";
            return (int) $this->wpdb->get_var( $sql );
        }
        $order = Util::order_by_string( $orderby );
        $sql   = $this->wpdb->prepare(
            "SELECT `title`, `id`, `settings`, `event_id` FROM $leagues_table $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $leagues = wp_cache_get( md5( $sql ), 'leagues' );
        if ( ! $leagues ) {
            $leagues = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $leagues, 'leagues' );
        }

        return $leagues ?: [];
    }

    public function get_player_stats( int $event_id, array $args ): array {
        if ( ! $this->wpdb ) {
            return [];
        }
        $defaults  = array(
            'season'    => false,
            'club'      => false,
            'league_id' => false,
            'system'    => false,
            'player'    => false,
        );
        $args      = array_merge( $defaults, $args );
        $season    = $args['season'];
        $club      = $args['club'];
        $league_id = $args['league_id'];
        $system    = $args['system'];
        $player    = $args['player'];

        $leagues_table        = $this->get_leagues_table();
        $teams_table          = $this->table( 'racketmanager_teams' );
        $rubbers_table        = $this->table( 'racketmanager_rubbers' );
        $rubber_players_table = $this->table( 'racketmanager_rubber_players' );
        $matches_table        = $this->table( 'racketmanager_matches' );
        $club_players_table   = $this->table( 'racketmanager_club_players' );
        $users_table          = isset( $this->wpdb->users ) ? $this->wpdb->users : $this->table( 'users' );

        $sql1 = "SELECT p.ID AS `player_id`, p.`display_name` AS `fullname`, ro.`id` AS `roster_id`, ro.`club_id` FROM $club_players_table AS ro, $users_table AS p WHERE ro.`player_id` = p.`ID`";
        $sql2 = "FROM $teams_table AS t, $rubbers_table AS r, $rubber_players_table AS rp, $matches_table AS m, $club_players_table AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND ((rp.`player_team` = 'home' AND rp.`club_player_id` = ro.`id` AND m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND rp.`club_player_id` = ro.`id` AND m.`away_team` = t.`id`)) AND ro.`club_id` = t.`club_id` AND r.`match_id` = m.`id` ";
        $search_terms2   = array();
        $search_terms2[] = $this->wpdb->prepare( "m.`league_id` IN (SELECT `id` FROM $leagues_table WHERE `event_id` = '%d')", $event_id );

        if ( $season ) {
            $search_terms2[] = $this->wpdb->prepare( "m.`season` = %s", wp_strip_all_tags( $season ) );
        }
        if ( $league_id ) {
            $search_terms2[] = $this->wpdb->prepare( "m.`league_id` = %d", intval( $league_id ) );
        }
        if ( $club ) {
            $search_terms2[] = $this->wpdb->prepare( "ro.`club_id` = %d", intval( $club ) );
        }
        if ( $player ) {
            $search_terms2[] = $this->wpdb->prepare( "ro.`id` = %d", $player );
        }
        if ( ! $system ) {
            $search_terms2[] = "ro.`system_record` IS NULL";
        }

        $order       = '`club_id`, `fullname` ';
        $search_2    = Util::search_string( $search_terms2 );
        $sql2        = $sql2 . $search_2;
        $sql         = $sql1 . ' AND ro.`id` in (SELECT ro.id ' . $sql2 . ')';
        $sql        .= " ORDER BY $order";
        $playerstats = wp_cache_get( md5( $sql ), 'playerstats' );
        if ( ! $playerstats ) {
            $playerstats = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $playerstats, 'playerstats' );
        }

        if ( ! is_array( $playerstats ) ) {
            return [];
        }

        foreach ( $playerstats as $i => $playerstat ) {
            $sql3  = 'SELECT t.`id` AS team_id, t.`title` AS team_title, m.`season`, m.`match_day`, m.`home_team`, m.`away_team`, m.`winner_id` AS match_winner, m.`home_points`, m.`away_points`, m.`loser_id` AS match_loser, r.`rubber_number`, r.`winner_id` AS rubber_winner, r.`loser_id` AS rubber_loser, r.`custom`, m.`final` as `final_round`';
            $sql4  = $this->wpdb->prepare( "ro.`ID` = %d", $playerstat->roster_id );
            $sql3 .= $sql2 . ' AND ' . $sql4;
            $sql3 .= ' ORDER BY m.`season`, m.`match_day`';

            $sql = $sql3;
            $stats = wp_cache_get( md5( $sql ), 'playerstats' );
            if ( ! $stats ) {
                $stats = $this->wpdb->get_results( $sql );
                wp_cache_set( md5( $sql ), $stats, 'playerstats' );
            }

            if ( is_array( $stats ) ) {
                foreach ( $stats as $s => $stat ) {
                    $stat->custom = stripslashes_deep( maybe_unserialize( $stat->custom ) );
                    $stats[ $s ]  = $stat;
                }
            } else {
                $stats = [];
            }

            $playerstat->matchdays = $stats;
            $playerstats[ $i ]     = (object) (array) $playerstat;
        }

        return $playerstats;
    }

    public function get_teams_info( int $event_id, ?string $season, array $args = [] ): array {
        if ( ! $this->wpdb || empty( $season ) ) {
            return [];
        }
        $defaults  = array(
            'league_id' => false,
            'rank'      => false,
            'home'      => false,
            'club'      => false,
            'orderby'   => array(
                'rank'  => 'ASC',
                'title' => 'ASC',
            ),
        );
        $args      = array_merge( $defaults, $args );
        $league_id = $args['league_id'];
        $rank      = $args['rank'];
        $orderby   = $args['orderby'];
        $home      = $args['home'];
        $club      = $args['club'];

        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $search_terms = array();
        if ( $league_id ) {
            if ( 'any' === $league_id ) {
                $search_terms[] = "A.`league_id` != ''";
            } else {
                $search_terms[] = $this->wpdb->prepare( 'A.`league_id` = %d', intval( $league_id ) );
            }
        }
        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( '`club_id` = %d', intval( $club ) );
        }
        if ( $rank ) {
            $search_terms[] = $this->wpdb->prepare( 'A.`rank` = %s', $rank );
        }
        if ( $home ) {
            $search_terms[] = 'B.`home` = 1';
        }

        $search = Util::search_string( $search_terms );
        $order  = Util::order_by_string( $orderby );
        $sql = "SELECT DISTINCT B.`id`, B.`title`, A.`captain`, B.`club_id`, B.`stadium`, B.`home`, B.`roster`, B.`profile`, A.`group`, A.`match_day`, A.`match_time` FROM $teams_table B, $league_teams_table A WHERE B.id = A.team_id AND A.league_id in (select `id` from $leagues_table WHERE `event_id` = " . intval( $event_id ) . ') AND A.season = ' . $this->wpdb->prepare( '%s', $season ) . " $search $order";

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

        return $teams ?: [];
    }

    public function get_team_info( int $event_id, int $team_id, ?string $season ): ?object {
        if ( ! $this->wpdb || empty( $season ) ) {
            return null;
        }
        $leagues_table      = $this->get_leagues_table();
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $sql = $this->wpdb->prepare(
            "SELECT TBL.`captain`, TBL.`match_day`, TBL.`match_time` FROM $league_teams_table TBL WHERE TBL.`team_id` = %d AND TBL.`season` = %s AND TBL.`league_id` IN (SELECT `id` FROM $leagues_table WHERE `event_id` = %d) LIMIT 1",
            $team_id,
            $season,
            $event_id
        );

        $team = wp_cache_get( md5( $sql ), 'team' );
        if ( ! $team ) {
            $team = $this->wpdb->get_row( $sql );
            wp_cache_set( md5( $sql ), $team, 'team' );
        }

        return $team ?: null;
    }

    public function get_settings( int $id ): array {
        if ( ! $this->wpdb ) {
            return [];
        }
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT `settings` FROM $this->table_name WHERE `id` = %d",
                $id
            )
        );
        return $result ? (array) maybe_unserialize( $result->settings ) : [];
    }

    public function query_constitution( int $event_id, array $args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $args['count'] ?? false ? 0 : [];
        }
        $defaults  = array(
            'offset'    => 0,
            'limit'     => 99999999,
            'season'    => false,
            'oldseason' => false,
            'club'      => false,
            'count'     => false,
        );
        $args      = array_merge( $defaults, $args );
        $offset    = $args['offset'];
        $limit     = $args['limit'];
        $season    = $args['season'];
        $oldseason = $args['oldseason'];
        $club      = $args['club'];
        $count     = $args['count'];

        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $search_terms   = array();
        $search_terms[] = $this->wpdb->prepare( '`event_id` = %d', $event_id );

        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( 't1.`season` = %s', $season );
        }

        if ( ! $oldseason ) {
            $oldseason = $season;
        }

        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, ot.`league_id` AS old_league_id, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank`,`ot`.`rank` AS old_rank, l.`id`, ot.`points_plus`, ot.`add_points`, t1.`status`, t1.`profile`, t1.`rating`';
        }
        $sql .= " FROM $leagues_table l, $teams_table t2, $league_teams_table t1 LEFT OUTER JOIN $league_teams_table ot ON `ot`.`season` = %s and `ot`.`team_id` = `t1`.`team_id` and ot.league_id in (select id from $leagues_table ol where ol.`event_id` = %d) WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ";
        $sql  = $this->wpdb->prepare(
            $sql,
            $oldseason,
            $event_id
        );

        if ( $count ) {
            return (int) $this->wpdb->get_var( $sql );
        }
        $sql = $this->wpdb->prepare(
            $sql . ' ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );

        $constitutions = wp_cache_get( md5( $sql ), 'constitution' );
        if ( ! $constitutions ) {
            $constitutions = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
        }

        return $constitutions ?: [];
    }

    public function build_constitution( int $event_id, array $args = [] ): array {
        if ( ! $this->wpdb ) {
            return [];
        }
        $defaults = array(
            'offset' => 0,
            'limit'  => 99999999,
            'season' => false,
            'club'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $club     = $args['club'];

        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $search_terms   = array();
        $search_terms[] = $this->wpdb->prepare( '`event_id` = %d', $event_id );

        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( '`season` = %s', $season );
        }

        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }

        $search = Util::search_string( $search_terms );
        $sql    = $this->wpdb->prepare(
            "SELECT `l`.`title` AS `old_league_title`, l.`id` AS `old_league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank` AS old_rank, l.`id`, t1.`points_plus`, t1.`add_points`, t1.`status`, t1.`profile`, t1.captain, t1.match_day, t1.match_time FROM $leagues_table l, $league_teams_table t1, $teams_table t2 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $constitutions = wp_cache_get( md5( $sql ), 'constitution' );
        if ( ! $constitutions ) {
            $constitutions = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
        }

        return $constitutions ?: [];
    }

    public function query_clubs( int $event_id, array $args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $args['count'] ?? false ? 0 : [];
        }
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];

        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );
        $clubs_table        = $this->table( 'racketmanager_clubs' );

        $search_terms   = array();
        $search_terms[] = $this->wpdb->prepare( '`event_id` = %d', $event_id );
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( 't1.`season` = %s', $season );
        }
        if ( $status ) {
            $search_terms[] = $this->wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
        }
        if ( $name ) {
            $search_terms[] = $this->wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
        }

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(DISTINCT(c.`id`))';
        } else {
            $sql = 'SELECT t2.`club_id`, count(t2.`id`) as `team_count`';
        }
        $sql .= " FROM $leagues_table l, $teams_table t2, $league_teams_table t1, $clubs_table c WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`club_id` = c.`id`" . $search;

        if ( $count ) {
            return (int) $this->wpdb->get_var( $sql );
        } else {
            $sql .= ' GROUP BY t2.`club_id`';
        }
        $sql = $this->wpdb->prepare(
            $sql . ' ORDER BY c.`name` ASC LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );

        $event_clubs = wp_cache_get( md5( $sql ), 'event_clubs' );
        if ( ! $event_clubs ) {
            $event_clubs = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $event_clubs, 'event_clubs' );
        }

        return $event_clubs ?: [];
    }

    public function query_teams( int $event_id, array $args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $args['count'] ?? false ? 0 : [];
        }
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(
                'league_title' => 'ASC',
                'name'         => 'ASC',
            ),
            'club'    => false,
            'league'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
            'player'  => false,
            'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];
        $club     = $args['club'];
        $league   = $args['league'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];
        $player   = $args['player'];
        $partner  = $args['partner'];

        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );
        $team_players_table = $this->table( 'racketmanager_team_players' );

        $search_terms   = array();
        $search_terms[] = $this->wpdb->prepare( '`event_id` = %d', $event_id );

        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( 't1.`season` = %s', $season );
        }
        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }
        if ( $league ) {
            $search_terms[] = $this->wpdb->prepare( 'l.`id` = %d', intval( $league ) );
        }
        if ( $status ) {
            $search_terms[] = $this->wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
        }
        if ( $name ) {
            $search_terms[] = $this->wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
        }
        if ( $player ) {
            $search_terms[] = $this->wpdb->prepare( "t2.`id` IN (SELECT `team_id` FROM $team_players_table WHERE `player_id` = %d )", $player );
        }
        if ( $partner ) {
            $search_terms[] = $this->wpdb->prepare( "t2.`id` IN (SELECT `team_id` FROM $team_players_table WHERE `player_id` = %d )", $partner );
        }
        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`team_id`))';
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`team_type` AS `team_type`, t1.`rating`';
        }
        $sql .= " FROM $leagues_table l, $teams_table t2, $league_teams_table t1 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

        if ( $count ) {
            $event_teams = wp_cache_get( md5( $sql ), 'event_teams' );
            if ( ! $event_teams ) {
                $event_teams = $this->wpdb->get_var( $sql );
                wp_cache_set( md5( $sql ), $event_teams, 'event_teams' );
            }
            return (int) $event_teams;
        }
        $sql .= Util::order_by_string( $orderby );
        $sql  = $this->wpdb->prepare(
            $sql . ' LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );
        $event_teams = wp_cache_get( md5( $sql ), 'event_teams' );
        if ( ! $event_teams ) {
            $event_teams = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $event_teams, 'event_teams' );
        }

        return $event_teams ?: [];
    }

    public function query_players( int $event_id, bool $is_player_entry, array $args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $args['count'] ?? false ? 0 : [];
        }
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(),
            'club'    => false,
            'team'    => false,
            'count'   => false,
            'stats'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];
        $club     = $args['club'];
        $team     = $args['team'];
        $count    = $args['count'];

        $leagues_table        = $this->get_leagues_table();
        $teams_table          = $this->table( 'racketmanager_teams' );
        $league_teams_table   = $this->table( 'racketmanager_league_teams' );
        $team_players_table   = $this->table( 'racketmanager_team_players' );
        $rubber_players_table = $this->table( 'racketmanager_rubber_players' );
        $rubbers_table        = $this->table( 'racketmanager_rubbers' );
        $matches_table        = $this->table( 'racketmanager_matches' );

        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`player_id`))';
        } else {
            $sql = 'SELECT DISTINCT `player_id`';
        }
        if ( $is_player_entry ) {
            $sql .= " FROM $team_players_table tp, $league_teams_table t, $leagues_table l WHERE tp.`team_id` = t.`team_id` AND t.`league_id` = l.`id` AND l.`event_id` = %d";
        } else {
            $sql .= " FROM $rubber_players_table rp, $rubbers_table r, $matches_table m WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM $leagues_table WHERE `event_id` = %d)";
        }
        $search_terms  = array();
        $search_args   = array();
        $search_args[] = $event_id;

        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        if ( $team ) {
            if ( $is_player_entry ) {
                $search_terms[] = 'tp.`team_id` = %d';
                $search_args[]  = $team;
            } else {
                $search_terms[] = '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
                $search_args[]  = $team;
                $search_args[]  = 'home';
                $search_args[]  = $team;
                $search_args[]  = 'away';
            }
        }
        if ( $club ) {
            $search_terms[] = "(( `home_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $teams_table WHERE `club_id` = %d) AND `player_team` = %s))";
            $search_args[]  = $club;
            $search_args[]  = 'home';
            $search_args[]  = $club;
            $search_args[]  = 'away';
        }
        $search = Util::search_string( $search_terms );
        $order  = Util::order_by_string( $orderby );
        $sql   .= $search;
        if ( $count ) {
            $sql = $this->wpdb->prepare(
                $sql,
                $search_args
            );
            $num_players = wp_cache_get( md5( $sql ), 'event_rubber_players' );
            if ( ! $num_players ) {
                $num_players = $this->wpdb->get_var( $sql );
                wp_cache_set( md5( $sql ), $num_players, 'event_rubber_players' );
            }
            return (int) $num_players;
        }
        $sql .= $order;
        if ( intval( $limit > 0 ) ) {
            $sql          .= ' LIMIT %d, %d';
            $search_args[] = $offset;
            $search_args[] = $limit;
        }
        $sql = $this->wpdb->prepare(
            $sql,
            $search_args
        );
        $players = wp_cache_get( md5( $sql ), 'event_rubber_players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $sql );
            wp_cache_set( md5( $sql ), $players, 'event_rubber_players' );
        }

        return $players ?: [];
    }

    public function mark_teams_withdrawn( int $event_id, string $season, int $club, false|int $team = false ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $leagues_table      = $this->get_leagues_table();
        $teams_table        = $this->table( 'racketmanager_teams' );
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $search_terms = array();
        if ( $team ) {
            $search_terms[] = $this->wpdb->prepare( '`team_id` = %d', $team );
        }
        $search = Util::search_string( $search_terms );
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $league_teams_table SET `profile` = 3, `status` = 'W' WHERE `league_id` IN (select `id` FROM $leagues_table WHERE `event_id` = %d) AND `season` = %s AND `team_id` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d) $search ",
                $event_id,
                $season,
                $club
            )
        );
        return $result !== false;
    }

    public function mark_teams_entered( int $event_id, int $team, string $season ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $leagues_table      = $this->get_leagues_table();
        $league_teams_table = $this->table( 'racketmanager_league_teams' );

        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $league_teams_table SET `profile` = 1 WHERE `league_id` IN (select `id` FROM $leagues_table WHERE `event_id` = %d) AND `season` = %s AND `team_id` = %d",
                $event_id,
                $season,
                $team
            )
        );
        return $result !== false;
    }

    public function query_matches( int $event_id, array $match_args = [] ): array|int {
        if ( ! $this->wpdb ) {
            return $match_args['count'] ?? false ? 0 : [];
        }
        $defaults = array(
            'leagueId'            => false,
            'season'              => false,
            'final'               => false,
            'orderby'             => array(
                'league_id' => 'ASC',
                'id'        => 'ASC',
            ),
            'confirmed'           => false,
            'player'              => false,
            'match_date'          => false,
            'time'                => false,
            'timeOffset'          => false,
            'history'             => false,
            'club'                => false,
            'league_name'         => false,
            'team_id'             => false,
            'team_name'           => false,
            'home_team'           => false,
            'away_team'           => false,
            'match_day'           => false,
            'home_club'           => false,
            'count'               => false,
            'confirmationPending' => false,
            'resultPending'       => false,
            'status'              => false,
            'pending'             => false,
        );
        $match_args           = array_merge( $defaults, $match_args );
        $league_id            = $match_args['leagueId'];
        $season               = $match_args['season'];
        $final                = $match_args['final'];
        $orderby              = $match_args['orderby'];
        $confirmed            = $match_args['confirmed'];
        $player               = $match_args['player'];
        $match_date           = $match_args['match_date'];
        $time                 = $match_args['time'];
        $time_offset          = $match_args['timeOffset'];
        $history              = $match_args['history'];
        $club                 = $match_args['club'];
        $league_name          = $match_args['league_name'];
        $team_id              = $match_args['team_id'];
        $team_name            = $match_args['team_name'];
        $home_team            = $match_args['home_team'];
        $home_club            = $match_args['home_club'];
        $away_team            = $match_args['away_team'];
        $match_day            = $match_args['match_day'];
        $count                = $match_args['count'];
        $confirmation_pending = $match_args['confirmationPending'];
        $result_pending       = $match_args['resultPending'];
        $status               = $match_args['status'];
        $pending              = $match_args['pending'];

        $leagues_table        = $this->get_leagues_table();
        $teams_table          = $this->table( 'racketmanager_teams' );
        $rubbers_table        = $this->table( 'racketmanager_rubbers' );
        $rubber_players_table = $this->table( 'racketmanager_rubber_players' );
        $matches_table        = $this->table( 'racketmanager_matches' );

        $search_terms   = array();
        $sql_from       = " FROM $matches_table AS m, $leagues_table AS l, $rubbers_table AS r ";
        $sql            = " WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id`";
        $search_terms[] = $this->wpdb->prepare( " l.`event_id` = %d ", $event_id );
        if ( $count ) {
            $sql_fields = 'SELECT COUNT( distinct m.id ) AS count';
        } else {
            $sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, m.`status`";
        }

        if ( $match_date ) {
            $search_terms[] = $this->wpdb->prepare( "DATEDIFF( %s, `date`) = 0", htmlspecialchars( wp_strip_all_tags( $match_date ) ) );
        }
        if ( $league_id ) {
            $search_terms[] = $this->wpdb->prepare( "`league_id`  = %d", $league_id );
        }
        if ( $league_name ) {
            $search_terms[] = $this->wpdb->prepare( "`league_id` in ( select `id` from $leagues_table WHERE `title` = %s ) ", $league_name );
        }
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( "`season`  = %s", $season );
        }
        if ( $final ) {
            $search_terms[] = $this->wpdb->prepare( "`final`  = %s", $final );
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . ':00:00';
        } else {
            $time_offset = '00:00:00';
        }
        if ( $status ) {
            $search_terms[] = $this->wpdb->prepare( "`confirmed` = %s", $status );
        }
        if ( $confirmed ) {
            $search_terms[] = "`confirmed` in ('P','A','C') ";
            if ( $time_offset ) {
                $search_terms[] = $this->wpdb->prepare( "ADDTIME(`updated`, %s) <= NOW()", $time_offset );
            }
        }
        if ( $player ) {
            $sql_from .= ", $rubber_players_table AS rp";
            $search_terms[] = "r.`id` = rp.`rubber_id`";
            $search_terms[] = $this->wpdb->prepare( "rp.`player_id` = %d", $player );
        }
        if ( $confirmation_pending ) {
            $confirmation_pending = intval( $confirmation_pending ) . ':00:00';
            $sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $result_pending = intval( $result_pending ) . ':00:00';
            $sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
        }

        // get only finished matches with score for time 'latest'.
        if ( 'latest' === $time ) {
            $search_terms[] = "(m.`home_points` != '' OR m.`away_points` != '')";
        } elseif ( 'outstanding' === $time ) {
            $search_terms[] = $this->wpdb->prepare( "ADDTIME(m.`date`, %s) <= NOW() AND m.`winner_id` = 0 AND `confirmed` IS NULL", $time_offset );
        } elseif ( 'next' === $time ) {
            $search_terms[] = "TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0";
        }
        // get only updated matches in specified period for history.
        if ( $history ) {
            $search_terms[] = $this->wpdb->prepare( "`updated` >= NOW() - INTERVAL %s DAY", $history );
        }

        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( "(`home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d) OR `away_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d))", $club, $club );
        }
        if ( $home_club ) {
            $search_terms[] = $this->wpdb->prepare( "`home_team` IN (SELECT `id` FROM $teams_table WHERE `club_id` = %d)", $home_club );
        }
        if ( ! empty( $home_team ) ) {
            $search_terms[] = $this->wpdb->prepare( "`home_team` = %s", $home_team );
        }
        if ( ! empty( $away_team ) ) {
            $search_terms[] = $this->wpdb->prepare( "`away_team` = %s", $away_team );
        }
        if ( ! empty( $team_id ) ) {
            $search_terms[] = $this->wpdb->prepare( "(`home_team` = %d OR `away_team` = %d)", $team_id, $team_id );
        }
        if ( ! empty( $team_name ) ) {
            $team_name_search = '%' . $this->wpdb->esc_like( $team_name ) . '%';
            $search_terms[] = $this->wpdb->prepare( "(`home_team` IN (SELECT `id` FROM $teams_table WHERE `title` LIKE %s) OR `away_team` IN (SELECT `id` FROM $teams_table WHERE `title` LIKE %s))", $team_name_search, $team_name_search );
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            $search_terms[] = $this->wpdb->prepare( "`match_day` = %s", $match_day );
        }
        if ( $pending ) {
            $search_terms[] = "m.`winner_id` = 0";
        }
        $search = Util::search_string( $search_terms );
        $sql    = $sql_fields . $sql_from . $sql . $search;
        if ( $count ) {
            return (int) $this->wpdb->get_var( $sql );
        } else {
            $sql .= Util::order_by_string( $orderby );
            $matches = $this->wpdb->get_results( $sql );
            return $matches ?: [];
        }
    }

    public function update_seasons( int $id, array $seasons ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $seasons_json = wp_json_encode( $seasons );
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $this->table_name SET `seasons` = %s WHERE `id` = %d",
                $seasons_json,
                $id
            )
        );
        return $result !== false;
    }

    public function delete_league_teams_for_season( int $league_id, string $season ): bool {
        if ( ! $this->wpdb ) {
            return false;
        }
        $league_teams_table = $this->table( 'racketmanager_league_teams' );
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM $league_teams_table WHERE `league_id` = %d AND `season` = %s",
                $league_id,
                $season
            )
        );
        return $result !== false;
    }

}
