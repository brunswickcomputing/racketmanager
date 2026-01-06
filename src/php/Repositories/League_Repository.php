<?php
/**
 * League_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\League;
use wpdb;

/**
 * Class to implement the League repository
 */
class League_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_leagues';
    }

    public function save( League $league ): void {
        $data = array(
            'title'    => $league->get_name(),
            'settings' => maybe_serialize( $league->get_settings() ),
            'seasons'  => maybe_serialize( $league->get_seasons() ),
            'sequence' => $league->get_sequence(),
            'event_id' => $league->get_event_id(),
        );
        $data_format = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
        );
        if ( empty( $league->get_id() ) ) {
            $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format
            );
            $league->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $league->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( $league_id ): ?League {
        if ( empty( $league_id ) ) {
            return null;
        }
        $league = wp_cache_get( $league_id, 'leagues' );

        if ( ! $league ) {
            $league = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $league_id
                )
            );

            if ( ! $league ) {
                return null;
            }
            $league = new League( $league );

            wp_cache_set( $league->id, $league, 'leagues' );
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

}
