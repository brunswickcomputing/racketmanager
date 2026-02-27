<?php
/**
 * Rubber_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Rubber;
use wpdb;

/**
 * Class to implement the rubber repository
 */
class Rubber_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_rubbers';
    }

    public function save( Rubber $rubber ): void {
        $data = array(
            'date'          => $rubber->get_date(),
            'match_id'      => $rubber->get_match_id(),
            'rubber_number' => $rubber->get_rubber_number(),
            'type'          => $rubber->get_type(),
            'group'         => $rubber->get_group(),
            'home_points'   => $rubber->get_home_points(),
            'away_points'   => $rubber->get_away_points(),
            'winner_id'     => $rubber->get_winner_id(),
            'loser_id'      => $rubber->get_loser_id(),
            'post_id'       => $rubber->get_post_id(),
            'custom'        => maybe_serialize( $rubber->get_custom() ),
            'status'        => $rubber->get_status(),
        );

        $format = array(
            '%s', // date
            '%d', // match_id
            '%d', // rubber_number
            '%s', // type
            '%s', // group
            '%f', // home_points
            '%f', // away_points
            '%s', // winner_id
            '%s', // loser_id
            '%d', // post_id
            '%s', // custom
            '%d', // status
        );

        if ( empty( $rubber->get_id() ) ) {
            $this->wpdb->insert( $this->table_name, $data, $format );
            $rubber->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $rubber->get_id(),
                ),
                $format,
                array(
                    '%d',
                )
            );
        }
    }

    public function find_by_id( $rubber_id ): ?Rubber {
        if ( empty( $rubber_id ) ) {
            return null;
        }
        $rubber = wp_cache_get( $rubber_id, 'rubbers' );

        if ( ! $rubber ) {
            $rubber = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $rubber_id
                )
            );

            if ( ! $rubber ) {
                return null;
            }
            $rubber = new Rubber( $rubber );

            wp_cache_set( $rubber->id, $rubber, 'rubbers' );
        }

        return $rubber;
    }

}
