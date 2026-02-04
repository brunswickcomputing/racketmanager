<?php
/**
 * Tournament_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Tournament;
use wpdb;

/**
 * Class to implement the Tournament repository
 */
class Tournament_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_tournaments';
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
            'information'      => $tournament->get_information(),
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
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $tournament->set_id( $this->wpdb->insert_id );
            wp_cache_set( $tournament->get_id(), $tournament, 'tournaments' );

            return $result !== false;
        } else {
            wp_cache_set( $tournament->get_id(), $tournament, 'tournaments' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $tournament->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }

    }

    public function find_by_id( $tournament_id, $search_term = 'id' ): ?Tournament {
        if ( ! $tournament_id ) {
            return null;
        }
        switch ( $search_term ) {
            case 'name':
                $search = $this->wpdb->prepare(
                    '`name` = %s',
                    $tournament_id
                );
                break;
            case 'shortcode':
                $search_terms   = explode( ',', $tournament_id );
                $competition_id = $search_terms[0];
                $season         = $search_terms[1];
                $search         = $this->wpdb->prepare(
                    '`competition_id` = %d AND `season` = %s',
                    intval( $competition_id ),
                    $season,
                );
                break;
            case 'id':
            default:
                $tournament_id = (int) $tournament_id;
                $search        = $this->wpdb->prepare(
                    '`id` = %d',
                    $tournament_id
                );
                break;
        }
        $tournament = wp_cache_get( $tournament_id, 'tournaments' );

        if ( ! $tournament ) {
            $tournament = $this->wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT `id`, `name`, `competition_id`, `season`, `venue`, date, `date_closing`, `date_start`, `date_open`, `date_withdrawal`, `grade`, `num_entries`, `numcourts` AS `num_courts`, `starttime` as `start_time`, `timeincrement` AS `time_increment`, `orderofplay` as `order_of_play`, `competition_code`, `information` FROM `$this->table_name` WHERE $search"
            );
            if ( ! $tournament ) {
                return null;
            }
            $tournament = new Tournament( $tournament );
            wp_cache_set( $tournament_id, $tournament, 'tournaments' );
        }

        return $tournament;

    }

    public function get_previous_tournament_players_with_optin( int $tournament_id, int $limit = 1, bool $entered = false ): array {
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
            )",
                $tournament_id
            );
        }
        $sql .= " ORDER BY u.display_name";

        $query   = $this->wpdb->prepare(
            $sql,
            $tournament_id,
            $tournament_id,
            $limit
        );
        $players = wp_cache_get( md5( $sql ), 'tournament_players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $query );
            wp_cache_set( md5( $sql ), $players, 'tournament_players' );
        }

        return $players;
    }

    public function delete( int $tournament_id ): int|false {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $tournament_id ), array( '%d' ) );
    }

}
