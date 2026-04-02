<?php
/**
 * Player_Error_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repository
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Player_Error;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use wpdb;

/**
 * Class to implement the Player_Error repository
 */
class Player_Error_Repository implements Player_Error_Repository_Interface {

    private wpdb $wpdb;
    private string $table_name;

    /**
     * Create a new Player_Error_Repository instance.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_player_errors';
    }

    /**
     * Save a player error.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int {
        /** @var Player_Error $player_error */
        $player_error = $entity;
        if ( empty( $player_error->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                array(
                    'player_id'    => $player_error->get_player_id(),
                    'message'      => $player_error->get_message(),
                    'created_date' => $player_error->get_created_date()
                ),
                array(
                    '%d',
                    '%s',
                    '%s'
                )
            );
            if ( $inserted ) {
                $player_error->set_id($this->wpdb->insert_id);
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            return $this->wpdb->update(
                $this->table_name,
                array(
                    'player_id'    => $player_error->get_player_id(),
                    'message'      => $player_error->get_message(),
                    'created_date' => $player_error->get_created_date(),
                ),
                array(
                    'id' => $player_error->get_id()
                ),
                array(
                    '%d',
                    '%s',
                    '%s'
                ),
                array( '%d' )
            ) !== false;
        }
    }

    /**
     * Find a player error by its ID.
     *
     * @param int|string|null $id
     *
     * @return Player_Error|null
     */
    public function find_by_id( int|string|null $id ): ?Player_Error {
        return $this->find( $id );
    }

    /**
     * Find a player error by its ID.
     *
     * @param int $player_error_id
     *
     * @return Player_Error|null
     */
    public function find( int $player_error_id ): ?Player_Error {
        $query = $this->wpdb->prepare(
            "SELECT id, player_id, message, created_date FROM $this->table_name WHERE id = %d",
            $player_error_id
        );
        $row   = $this->wpdb->get_row($query);
        return $row ? new Player_Error( $row ) : null;
    }

    /**
     * Find all player errors with details.
     *
     * @param string|null $message
     *
     * @return array
     */
    public function find_all_with_details( string $message = null ): array {
        $search = null;
        $code = match( $message ) {
            'no_player' => 'Player not found',
            'no_wtn'    => 'WTN not found',
            default     => null,
        };
        if ( $code ) {
            $search = $this->wpdb->prepare( 'WHERE `message` = %s', $code );
        }
        $user_table = $this->wpdb->users;
        $user_meta_table = $this->wpdb->usermeta;
        $btm_meta_key = Player_Repository::META_KEY_BTM; // Reuse the constant from PlayerRepository

        $query = "SELECT pe.id, pe.player_id, pe.message, pe.created_date, u.display_name AS player_name, MAX( IF( um_btm.meta_key = %s, um_btm.meta_value, NULL ) ) AS btm FROM  $this->table_name pe INNER JOIN $user_table u ON pe.player_id = u.ID LEFT JOIN $user_meta_table um_btm ON u.ID = um_btm.user_id AND um_btm.meta_key = %s $search GROUP BY  pe.id, pe.player_id, u.display_name ORDER BY pe.created_date DESC";

        $params = [$btm_meta_key, $btm_meta_key];

        return $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) );
    }

    /**
     * Delete a player error.
     *
     * @param $player_error_id
     *
     * @return bool
     */
    public function delete( $player_error_id ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array(
                'id' => $player_error_id
            ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Delete player errors for a player.
     *
     * @param int $player_id
     *
     * @return bool
     */
    public function delete_for_player( int $player_id ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array(
                'player_id' => $player_id
            ),
            array( '%d' )
        ) !== false;
    }

}
