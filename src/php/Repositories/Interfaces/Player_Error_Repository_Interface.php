<?php
/**
 * Player_Error_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Player_Error;

/**
 * Interface to implement the Player_Error repository
 */
interface Player_Error_Repository_Interface extends Repository_Interface {
    /**
     * Save a player error.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int;

    /**
     * Find a player error by its ID.
     *
     * @param int|string|null $id
     *
     * @return Player_Error|null
     */
    public function find_by_id( int|string|null $id ): ?Player_Error;

    /**
     * Find a player error by its ID.
     *
     * @param int $player_error_id
     *
     * @return Player_Error|null
     */
    public function find( int $player_error_id ): ?Player_Error;

    /**
     * Find all player errors with details.
     *
     * @param string|null $message
     *
     * @return array
     */
    public function find_all_with_details( string $message = null ): array;

    /**
     * Delete a player error.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete( int $id ): bool;

    /**
     * Delete player errors for a player.
     *
     * @param int $player_id
     *
     * @return bool
     */
    public function delete_for_player( int $player_id ): bool;
}
