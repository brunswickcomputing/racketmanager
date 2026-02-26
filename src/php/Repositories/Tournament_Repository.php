<?php
/**
 * Tournament_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Event;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Tournament;
use Racketmanager\Util\Util;
use stdClass;
use wpdb;

/**
 * Class to implement the Tournament repository
 */
class Tournament_Repository {
    private wpdb $wpdb;
    private string $table_name;
    private string $competition_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb              = $wpdb;
        $this->table_name        = $this->wpdb->prefix . 'racketmanager_tournaments';
        $this->competition_table = $this->wpdb->prefix . 'racketmanager_competitions';
    }

    public function save( Tournament $tournament ): bool|int {
        $data        = array(
            'name'             => $tournament->get_name(),
            'competition_id'   => $tournament->get_competition_id(),
            'season'           => $tournament->get_season(),
            'venue'            => $tournament->get_venue(),
            'date'             => $tournament->get_end_date(),
            'date_closing'     => $tournament->get_closing_date(),
            'date_withdrawal'  => $tournament->get_withdrawal_date(),
            'date_open'        => $tournament->get_open_date(),
            'date_start'       => $tournament->get_start_date(),
            'competition_code' => $tournament->get_competition_code(),
            'grade'            => $tournament->get_grade(),
            'num_entries'      => $tournament->get_num_entries(),
            'numcourts'        => $tournament->get_num_courts(),
            'starttime'        => $tournament->get_start_time(),
            'timeincrement'    => $tournament->get_time_increment(),
            'orderofplay'      => maybe_serialize( $tournament->get_order_of_play() ),
            'information'      => json_encode( $tournament->get_information() ),
        );
        $data_format = array(
            '%s',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
        );
        if ( empty( $tournament->get_id() ) ) {
            $result = $this->wpdb->insert( $this->table_name, $data, $data_format );
            $tournament->set_id( $this->wpdb->insert_id );
            wp_cache_set( $tournament->get_id(), $tournament, 'tournaments' );

            return $result !== false;
        } else {
            wp_cache_set( $tournament->get_id(), $tournament, 'tournaments' );

            return $this->wpdb->update( $this->table_name, $data, // Data to update
                array(
                    'id' => $tournament->get_id()
                ), // Where clause
                $data_format, array(
                    '%d'
                ) // Where format
            );
        }

    }

    public function find_by_id( $tournament_id, $search_term = 'id' ): ?Tournament {
        if ( ! $tournament_id ) {
            return null;
        }
        if ( 'shortcode' === $search_term ) {
            $search_terms   = explode( ',', $tournament_id );
            $competition_id = $search_terms[0];
            $season         = $search_terms[1];
            $search         = $this->wpdb->prepare( '`competition_id` = %d AND `season` = %s', intval( $competition_id ), $season );
        } elseif ( is_numeric( $tournament_id ) ) {
            $tournament_id = (int) $tournament_id;
            $search        = $this->wpdb->prepare( '`id` = %d', $tournament_id );
        } else {
            $search = $this->wpdb->prepare( '`name` = %s', $tournament_id );
        }
        $tournament = wp_cache_get( $tournament_id, 'tournaments' );

        if ( ! $tournament ) {
            $tournament = $this->wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `$this->table_name` WHERE $search" );
            if ( ! $tournament ) {
                return null;
            }
            $tournament = new Tournament( $tournament );
            wp_cache_set( $tournament_id, $tournament, 'tournaments' );
        }

        return $tournament;
    }

    public function find_tournament_overview( int $tournament_id ): ?stdclass {
        $clubs_table              = $this->wpdb->prefix . 'racketmanager_clubs';
        $events_table             = $this->wpdb->prefix . 'racketmanager_events';
        $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';

        return $this->wpdb->get_row( $this->wpdb->prepare( "
                    SELECT t.id,
                    t.name as tournament_name,
                    t.season,
                    c2.name as competition_name,
                    c2.age_group,
                    t.competition_code,
                    t.date as date_end,
                    t.date_closing,
                    t.date_withdrawal,
                    t.date_open,
                    t.date_start,
                    c.shortcode as venue_name,
                    CASE
                        WHEN CURDATE() > t.date THEN 'end'
                        WHEN CURDATE() > t.date_start THEN 'in_progress'
                        WHEN CURDATE() > t.date_withdrawal THEN 'withdraw'
                        WHEN CURDATE() > t.date_closing THEN 'closed'
                        WHEN CURDATE() > t.date_open THEN 'open'
                        ELSE 'draft'
                        END AS phase,
                        COUNT(DISTINCT e.id) as num_events,
                        COUNT(DISTINCT te.player_id) as num_entries
                    FROM `$this->table_name` t
                    LEFT JOIN `$clubs_table` c ON t.venue = c.id
                    left join `$this->competition_table` c2 on t.competition_id = c2.id
                    LEFT JOIN `$events_table` e ON e.competition_id = c2.id
                    LEFT JOIN `$tournament_entries_table` te ON te.tournament_id = t.id
                    WHERE t.id = %d
                    GROUP BY t.id", $tournament_id ) );
    }

    public function find_active( ?string $age_group = null ): ?Tournament {
        $search = null;
        if ( $age_group ) {
            $search = $this->wpdb->prepare( " AND `competition_id` in (select `id` from `$this->competition_table` WHERE `age_group` = %s)", $age_group );
        }

        $tournament = $this->wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM `$this->table_name` WHERE `date` >= CURDATE() AND `date_start` <= CURDATE() $search LIMIT 1" );
        if ( ! $tournament ) {
            return null;
        }
        $tournament = new Tournament( $tournament );
        wp_cache_set( $tournament->get_id(), $tournament, 'tournaments' );

        return $tournament;
    }

    public function find_by( array $criteria ): array {
        $defaults       = array(
            'competition_id' => false,
            'season'         => false,
            'name'           => false,
            'entry_open'     => false,
            'open'           => false,
            'active'         => false,
            'age_group'      => false,
            'orderby'        => array( 'name' => 'DESC' ),
        );
        $args           = array_merge( $defaults, $criteria );
        $competition_id = $args['competition_id'];
        $season         = $args['season'];
        $entry_open     = $args['entry_open'];
        $open           = $args['open'];
        $active         = $args['active'];
        $age_group      = $args['age_group'];
        $orderby        = $args['orderby'];

        $search_terms = array();

        if ( $competition_id ) {
            $search_terms[] = $this->wpdb->prepare( '`competition_id` = %s', $competition_id );
        }
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( '`season` = %s', $season );
        }
        if ( $entry_open ) {
            $search_terms[] = '`date_closing` >= CURDATE()';
        }
        if ( $open ) {
            $search_terms[] = "(`date` >= CURDATE() OR `date` = '0000-00-00')";
        }
        if ( $active ) {
            $search_terms[] = '`date` >= CURDATE() AND `date_start` <= CURDATE()';
        }
        if ( $age_group ) {
            $search_terms[] = $this->wpdb->prepare( " `competition_id` in (select `id` from `$this->competition_table` WHERE `age_group` = %s)", $age_group );
        }
        $search      = Util::search_string( $search_terms, true );
        $order       = Util::order_by_string( $orderby );
        $sql         = "SELECT * FROM `$this->table_name` $search $order";
        $cachekey    = md5( $sql );
        $tournaments = wp_cache_get( $cachekey, 'tournaments' );
        if ( ! $tournaments ) {
            $results     = $this->wpdb->get_results( $sql );
            $tournaments = array_map( function ( $row ) {
                return new Tournament( $row );
            }, $results );
            wp_cache_set( $cachekey, $tournaments, 'tournaments', HOUR_IN_SECONDS );
        }

        return $tournaments;
    }

    public function find_previous_tournament_players_with_optin( int $tournament_id, int $limit = 1, bool $entered = false ): array {
        $competitions_table       = $this->wpdb->prefix . 'racketmanager_competitions';
        $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';
        $users_table              = $this->wpdb->prefix . 'users';
        $usermeta_table           = $this->wpdb->prefix . 'usermeta';
        $sql                      = "SELECT DISTINCT u.display_name as fullname, u.user_email as email
         FROM `$users_table` u
         JOIN `$tournament_entries_table` te ON u.ID = te.player_id
         JOIN `$usermeta_table` um_optin ON u.ID = um_optin.user_id
         JOIN `$this->table_name` t ON te.tournament_id = t.id
         JOIN `$competitions_table` c ON t.competition_id = c.id
         JOIN (
               SELECT prev_t.id
               FROM `$this->table_name` prev_t
               JOIN `$competitions_table` prev_c ON prev_t.competition_id = prev_c.id
               WHERE prev_c.age_group = (
                   SELECT age_group
                   FROM `$competitions_table` c2
                   JOIN `$this->table_name` t2 ON c2.id = t2.competition_id
                   WHERE t2.id = %d
               )
               AND prev_t.id < %d
               ORDER BY prev_t.date_start DESC
               LIMIT %d
         ) AS last_two ON te.tournament_id = last_two.id
         WHERE um_optin.meta_key = 'racketmanager_opt_in'
           AND um_optin.meta_value = '1'
        ";
        if ( $entered ) {
            $sql .= $this->wpdb->prepare( "
            AND u.ID NOT IN (
                SELECT player_id
                FROM `$tournament_entries_table`
                WHERE tournament_id = %d
            )", $tournament_id );
        }
        $sql .= " ORDER BY u.display_name";

        $query    = $this->wpdb->prepare( $sql, $tournament_id, $tournament_id, $limit );
        $cachekey = md5( $sql );
        $players  = wp_cache_get( $cachekey, 'tournament_players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $query );
            wp_cache_set( $cachekey, $players, 'tournament_players' );
        }

        return $players;
    }

    public function delete( int $tournament_id ): int|false {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $tournament_id ), array( '%d' ) );
    }

    public function find_events_by_tournament_with_details( int $tournament_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';

        return $this->wpdb->get_results( $this->wpdb->prepare( "
                    SELECT e.id,
                    e.name,
                    COUNT(DISTINCT lt.team_id) as num_teams
                    FROM `$tournaments_table` t
                    LEFT JOIN `$events_table` e ON e.competition_id = t.competition_id
                    LEFT JOIN `$leagues_table` l ON l.event_id = e.id
                    LEFT JOIN `$league_teams_table` lt ON lt.league_id = l.id
                    LEFT JOIN `$teams_table` t2 ON lt.team_id = t2.id
                    WHERE t.id = %d
                    AND lt.season = t.season
                    AND ((t2.team_type IS NULL OR t2.team_type = 'P') OR t2.team_type != 'S')
                    GROUP BY e.id
                    ORDER BY e.name", $tournament_id ) );
    }

    /**
     * Retrieves team and partner info for a player in a specific event.
     *
     * @param int $player_id
     * @param int $event_id
     * @param int $season
     *
     * @return stdClass|null
     */
    public function find_event_details_for_player( int $player_id, int $event_id, int $season ): ?stdClass {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $users_table        = $this->wpdb->prefix . 'users';
        $query              = $this->wpdb->prepare( "SELECT
            lt.id AS team_id,
            u.ID AS partner_id,
            u.display_name AS partner_name,
            u.user_email AS partner_email
         FROM `$team_players_table` tp_primary
         JOIN `$league_teams_table` lt ON tp_primary.team_id = lt.team_id
         JOIN `$leagues_table` l ON lt.league_id = l.id
         LEFT JOIN `$team_players_table` tp_partner
            ON tp_primary.team_id = tp_partner.team_id AND tp_partner.player_id != tp_primary.player_id
         LEFT JOIN `$users_table` u ON tp_partner.player_id = u.ID
         WHERE tp_primary.player_id = %d
           AND l.event_id = %d
           AND lt.season = %d
           AND lt.profile != 3
         LIMIT 1", $player_id, $event_id, $season );

        return $this->wpdb->get_row( $query );
    }

    /**
     * Retrieves team and partner info for a player in a specific event.
     *
     * @param int $player_id
     * @param int $tournament_id
     *
     * @return array
     */
    public function find_event_entry_details_for_player_in_tournament( int $player_id, int $tournament_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $users_table        = $this->wpdb->prefix . 'users';

        $query = $this->wpdb->prepare( "SELECT DISTINCT
            e.id AS event_id,
            e.name AS event_name,
            u.ID AS partner_id,
            u.display_name AS partner_name,
            u.user_email AS partner_email
         FROM `$tournaments_table` t
             JOIN `$events_table` e ON e.competition_id = t.competition_id
             JOIN `$leagues_table` l ON l.event_id = e.id
             JOIN `$league_teams_table` lt ON lt.league_id = l.id
             JOIN `$team_players_table` tp_primary ON tp_primary.team_id = lt.team_id
             LEFT JOIN `$team_players_table` tp_partner ON tp_primary.team_id = tp_partner.team_id AND tp_partner.player_id != tp_primary.player_id
             LEFT JOIN `$users_table` u ON tp_partner.player_id = u.ID
         WHERE tp_primary.player_id = %d
           AND t.id = %d
           AND t.season = lt.season
           AND lt.profile != 3", $player_id, $tournament_id );

        return $this->wpdb->get_results( $query );
    }

    public function find_teams_for_tournament_event( int $tournament_id, int $event_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $teams_table        = $this->wpdb->prefix . 'racketmanager_teams';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $players_table      = $this->wpdb->prefix . 'users';

        $query = $this->wpdb->prepare( "SELECT
            t2.id AS team_id,
            t2.title AS team_name,
            lt.status,
            lt.rating,
            lt.profile,
            lt.rank,
            GROUP_CONCAT(CONCAT(p.ID, ':', p.display_name) ORDER BY p.display_name ASC SEPARATOR ',') AS player_data
         FROM `$tournaments_table` t
             JOIN `$events_table` e ON e.competition_id = t.competition_id
             JOIN `$leagues_table` l ON l.event_id = e.id
             JOIN `$league_teams_table` lt ON lt.league_id = l.id
             JOIN `$teams_table` t2 ON t2.id = lt.team_id
             LEFT JOIN `$team_players_table` tp ON tp.team_id = t2.id
             LEFT JOIN `$players_table` p ON p.ID = tp.player_id
         WHERE t.id = %d
           AND e.id = %d
           AND lt.season = t.season
           AND lt.rating IS NOT NULL
         GROUP BY t2.id,t2.title,lt.status,lt.rating,lt.profile,lt.rank
         ORDER BY lt.rank", $tournament_id, $event_id );

        return $this->wpdb->get_results( $query );
    }

    public function find_event_for_tournament( int $tournament_id, int|string $event_id ): ?Event {
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';

        if ( is_numeric( $event_id ) ) {
            $search = " e.id = %d";
        } else {
            $search = " e.name = %s";
        }
        $query = $this->wpdb->prepare( "SELECT e.*
         FROM `$tournaments_table` t
             JOIN `$events_table` e ON e.competition_id = t.competition_id
         WHERE t.id = %d
           AND $search
         LIMIT 1
            ", $tournament_id, $event_id );
        $row   = $this->wpdb->get_row( $query );
        if ( $row ) {
            return new Event( $row );
        } else {
            return null;
        }
    }

    public function find_finalists_for_tournament( $tournament_id ): array {
        $fixtures_table     = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $players_table      = $this->wpdb->prefix . 'users';
        $query              = $this->wpdb->prepare( "
                SELECT p.ID as player_id,
                       p.display_name,
                       p.user_email as email
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                    join `$team_players_table`  tp on tp.team_id = f.home_team
                    join `$players_table` p on tp.player_id = p.ID
                WHERE f.season = t.season
                  and f.final = 'final'
                  and t.id = %d
                union
                SELECT p.ID as player_id,
                       p.display_name,
                       p.user_email as email
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                    join `$team_players_table`  tp on tp.team_id = f.away_team
                    join `$players_table` p on tp.player_id = p.ID
                WHERE f.season = t.season
                  and f.final = 'final'
                  and t.id = %d
                order by 1
                ", $tournament_id, $tournament_id );
        $results            = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Players_List_DTO( $row ), $results );
    }

    public function find_winners_for_tournament( $tournament_id ): array {
        $fixtures_table           = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table        = $this->wpdb->prefix . 'racketmanager_tournaments';
        $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';
        $events_table             = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table            = $this->wpdb->prefix . 'racketmanager_leagues';
        $team_players_table       = $this->wpdb->prefix . 'racketmanager_team_players';
        $players_table            = $this->wpdb->prefix . 'users';
        $clubs_table              = $this->wpdb->prefix . 'racketmanager_clubs';
        $query                    = $this->wpdb->prepare( "
                SELECT f.id as match_id,
                       e.id as event_id,
                       e.name as event_name,
                       e.type as match_type,
                       l.title as league,
                       -- Winners: Separate Names and Clubs
                       GROUP_CONCAT(DISTINCT pw.display_name ORDER BY pw.display_name ASC SEPARATOR ' / ') as winner_names,
                       GROUP_CONCAT(DISTINCT cw.shortcode ORDER BY pw.display_name ASC SEPARATOR ' / ') as winner_clubs,
                       -- Losers: Separate Names and Clubs
                       GROUP_CONCAT(DISTINCT pl.display_name ORDER BY pl.display_name ASC SEPARATOR ' / ') as loser_names,
                       GROUP_CONCAT(DISTINCT cl.shortcode ORDER BY pl.display_name ASC SEPARATOR ' / ') as loser_clubs
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                    join `$team_players_table`  tpw on tpw.team_id = f.winner_id
                    join `$players_table` pw on tpw.player_id = pw.ID
                    join `$tournament_entries_table` tetw on tetw.player_id = pw.ID AND tetw.tournament_id = t.id
                    left join `$clubs_table` cw on cw.id = tetw.club_id
                    join `$team_players_table`  tpl on tpl.team_id = f.loser_id
                    join `$players_table` pl on tpl.player_id = pl.ID
                    join `$tournament_entries_table` tetl on tetl.player_id = pl.ID AND tetl.tournament_id = t.id
                    left join `$clubs_table` cl on cl.id = tetl.club_id
                WHERE f.season = t.season
                  and f.final = 'final'
                  and t.id = %d
                GROUP BY f.id
                ORDER BY l.title
                ", $tournament_id );

        return $this->wpdb->get_results( $query );
    }

    public function find_match_dates_for_tournament( int $tournament_id ): array {
        $fixtures_table    = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';
        $query             = $this->wpdb->prepare( "
                SELECT DISTINCT DATE_FORMAT(f.`date`, %s) AS `match_date`
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                WHERE f.season = t.season
                  and t.id = %d
                ORDER BY f.`date`
            ", '%Y-%m-%d', $tournament_id );

        return $this->wpdb->get_results( $query );
    }

    /**
     * @param int $tournament_id
     * @param string $match_date
     *
     * @return Fixture[]
     */
    public function find_matches_by_date_for_tournament( int $tournament_id, string $match_date ): array {
        $fixtures_table    = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';
        $query             = $this->wpdb->prepare( "
                SELECT f.*
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                WHERE f.season = t.season
                  AND t.id = %d
                  AND DATEDIFF(%s, f.`date`) = 0
                ORDER BY f.`date`,
                         f.location
            ", $tournament_id, $match_date );
        $results           = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Fixture( $row ), $results );
    }

    /**
     * @param int $tournament_id
     * @param int $event_id
     *
     * @return Fixture[]
     */
    public function find_matches_by_event_for_tournament( int $tournament_id, int $event_id ): array {
        $fixtures_table    = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table      = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table     = $this->wpdb->prefix . 'racketmanager_leagues';
        $query             = $this->wpdb->prepare( "
                SELECT f.*
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                WHERE f.season = t.season
                  AND t.id = %d
                  AND e.id = %d
                ORDER BY f.`date`
            ", $tournament_id, $event_id );
        $results           = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Fixture( $row ), $results );
    }

    /**
     * @param int $tournament_id
     *
     * @return Players_List_DTO[]
     */
    public function find_active_players_for_tournament( int $tournament_id ): array {
        $fixtures_table     = $this->wpdb->prefix . 'racketmanager_matches';
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $players_table      = $this->wpdb->prefix . 'users';
        $query              = $this->wpdb->prepare( "
                SELECT p.ID as player_id,
                       p.display_name,
                       p.user_email as email
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                    join `$team_players_table`  tp on tp.team_id = f.home_team
                    join `$players_table` p on tp.player_id = p.ID
                WHERE f.season = t.season
                  and f.winner_id = 0
                  and t.id = %d
                union
                SELECT p.ID as player_id,
                       p.display_name,
                       p.user_email as email
                FROM `$fixtures_table` f
                    join `$leagues_table` l on f.league_id = l.id
                    join `$events_table` e on l.event_id = e.id
                    join `$tournaments_table` t on t.competition_id = e.competition_id
                    join `$team_players_table`  tp on tp.team_id = f.away_team
                    join `$players_table` p on tp.player_id = p.ID
                WHERE f.season = t.season
                  and f.winner_id = 0
                  and t.id = %d
                order by 1
                ", $tournament_id, $tournament_id );
        $results            = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Players_List_DTO( $row ), $results );
    }

    public function find_leagues_by_event_for_tournament( int $tournament_id ): array {
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $league_teams_table = $this->wpdb->prefix . 'racketmanager_league_teams';
        $query              = $this->wpdb->prepare( "
            SELECT
                e.id as event_id,
                e.type as event_type,
                e.name as event_name,
                l.id as league_id,
                l.title as league_name,
                (
                    SELECT COUNT(*)
                    FROM `$league_teams_table` lt
                    WHERE lt.league_id = l.id
                      AND lt.season = t.season
                      AND lt.profile != 3
                ) as total_entries
            FROM `$events_table` e
            JOIN `$leagues_table` l ON l.event_id = e.id
            JOIN `$tournaments_table` t ON t.competition_id = e.competition_id
            WHERE t.id = %d
            ORDER BY e.type, e.name, l.title
            ", $tournament_id );

        return $this->wpdb->get_results( $query );
    }

    /**
     * @param $player_id
     *
     * @return Tournament[]
     */
    public function find_by_player( $player_id ): array {
        $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';

        $query   = $this->wpdb->prepare( "
            SELECT t.*
            FROM `$this->table_name` t
                JOIN `$tournament_entries_table` te ON t.id = te.tournament_id
            WHERE te.player_id = %d
            ORDER BY t.date DESC
            ",
            $player_id
        );
        $results = $this->wpdb->get_results( $query );

        return array_map( fn( $row ) => new Tournament( $row ), $results );
    }

}
