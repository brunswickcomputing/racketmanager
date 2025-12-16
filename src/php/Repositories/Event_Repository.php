<?php
/**
 * Event_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Event;
use Racketmanager\Domain\League;
use wpdb;

/**
 * Class to implement the Event repository
 */
class Event_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_events';
    }

    public function save( Event $event ): void {
        if ( empty( $event->get_id() ) ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'name'           => $event->get_name(),
                    'settings'       => maybe_serialize( $event->get_settings() ),
                    'seasons'        => maybe_serialize( $event->get_seasons() ),
                    'type'           => $event->get_type(),
                    'num_sets'       => $event->get_num_sets(),
                    'num_rubbers'    => $event->get_num_rubbers(),
                    'competition_id' => $event->get_competition_id(),
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
            $event->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                array(
                    'name'           => $event->get_name(),
                    'settings'       => maybe_serialize( $event->get_settings() ),
                    'seasons'        => maybe_serialize( $event->get_seasons() ),
                    'type'           => $event->get_type(),
                    'num_sets'       => $event->get_num_sets(),
                    'num_rubbers'    => $event->get_num_rubbers(),
                    'competition_id' => $event->get_competition_id(),
                ), // Data to update
                array(
                    'id' => $event->get_id()
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
            );
        }
    }

    public function find_by_id( $event_id ): ?Event {
        if ( empty( $event_id ) ) {
            return null;
        }
        $event = wp_cache_get( $event_id, 'events' );

        if ( ! $event ) {
            $event = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $event_id
                )
            );

            if ( ! $event ) {
                return null;
            }
            $event = new Event( $event );

            wp_cache_set( $event->get_id(), $event, 'events' );
        }

        return $event;
    }

}
