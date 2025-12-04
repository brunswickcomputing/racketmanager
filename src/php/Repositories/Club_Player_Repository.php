<?php
/**
 * Club_Player Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repository
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club_Player;
use wpdb;

/**
 * Class to implement the Club_Player repository
 */
class Club_Player_Repository {
    private wpdb $wpdb;
    private string $table_name;

    /**
     * Create a new Club_Player_Repository instance.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_club_players';
    }

    /**
     * Save a club player.
     *
     * @param Club_Player $club_player
     *
     * @return void
     */
    public function save( Club_Player $club_player ): void {
        //`id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user`
        if ( $club_player->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'player_id'      => $club_player->get_player_id(),
                    'club_id'        => $club_player->get_club_id(),
                    'requested_date' => $club_player->get_requested_date(),
                    'requested_user' => $club_player->get_requested_user(),
                    'created_date'   => $club_player->get_created_date(),
                    'created_user'   => $club_player->get_created_user(),
                    'removed_date'   => $club_player->get_removed_date(),
                    'removed_user'   => $club_player->get_removed_user(),
                    'system_record'  => $club_player->get_system_record(),
                    'status'         => $club_player->get_status(),
                ),
                array(
                    '%d', // Format for player_id (int)
                    '%d', // Format for club_id (int)
                    '%s', // Format for requested_date (string)
                    '%d', // Format for requested_user (int)
                    '%s', // Format for created_date (string)
                    '%d', // Format for created_user (int)
                    '%s', // Format for removed_date (string)
                    '%d', // Format for removed_user (int)
                    '%s', // Format for system_record (bool)
                    '%s', // Format for status (string)
                )
            );
            $club_player->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                array(
                    'player_id'      => $club_player->get_player_id(),
                    'club_id'        => $club_player->get_club_id(),
                    'requested_date' => $club_player->get_requested_date(),
                    'requested_user' => $club_player->get_requested_user(),
                    'created_date'   => $club_player->get_created_date(),
                    'created_user'   => $club_player->get_created_user(),
                    'removed_date'   => $club_player->get_removed_date(),
                    'removed_user'   => $club_player->get_removed_user(),
                    'system_record'  => $club_player->get_system_record(),
                    'status'         => $club_player->get_status(),
                ), // Data to update
                array( // Where clause
                    'id' => $club_player->get_id()
                ),
                array( // Data format
                    '%d', // Format for player_id (int)
                    '%d', // Format for club_id (int)
                    '%s', // Format for requested_date (string)
                    '%d', // Format for requested_user (int)
                    '%s', // Format for created_date (string)
                    '%d', // Format for created_user (int)
                    '%s', // Format for removed_date (string)
                    '%d', // Format for removed_user (int)
                    '%s', // Format for system_record (bool)
                    '%s', // Format for status (string)
                ),
                array( // Where format
                    '%d'
                )
            );
        }
        wp_cache_flush_group( 'club_players' );
    }

    /**
     * Find a club player by its ID.
     *
     * @param $club_player_id
     *
     * @return Club_Player|null
     */
    public function find_by_id( $club_player_id ): ?Club_Player {
        $row   = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user`, `status` FROM $this->table_name WHERE id = %d",
                $club_player_id
            )
        );
        return $row ? new Club_Player( $row ) : null;
    }

    /**
     * Find club players by player ID.
     *
     * @param int $player_id
     * @param string $status
     *
     * @return array
     */
    public function find_by_player( int $player_id, string $status = 'active' ): array {
        $search = null;
        if ( 'active' === $status ) {
            $search = ' AND `removed_date` IS NULL';
        }
        $query   = $this->wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE player_id = %d $search",
            $player_id
        );
        $results = $this->wpdb->get_results( $query );
        return array_map(
            function( $row ) {
                return new Club_Player( $row );
            },
            $results
        );
    }

    /**
     * Find a club player by club and player IDs.
     *
     * @param int $club_id
     * @param int $player_id
     *
     * @return Club_Player|null
     */
    public function find_by_club_and_player( int $club_id, int $player_id): ?Club_Player {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE club_id = %d AND player_id = %d AND `removed_date` IS NULL",
                $club_id,
                $player_id
            )
        );
        return $row ? new Club_Player( $row ) : null;
    }

    /**
     * Find a club player by its ID.
     *
     * @param $club_player_id
     *
     * @return Club_Player|null
     */
    public function find( $club_player_id ): ?Club_Player {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE id = %d",
                $club_player_id
            )
        );
        return $row ? new Club_Player( $row ) : null;
    }

    /**
     * Delete all club players for a club.
     *
     * @param $club_id
     *
     * @return void
     */
    public function delete_for_club( $club_id ): void {
        $this->wpdb->delete(
            $this->table_name,
            array( 'club_id' => $club_id ),
            array( '%d' )
        );
    }
}
