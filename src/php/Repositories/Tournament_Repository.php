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
}
