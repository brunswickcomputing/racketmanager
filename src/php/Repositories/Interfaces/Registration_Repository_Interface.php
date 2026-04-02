<?php
/**
 * Registration_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Club_Player;

/**
 * Interface to implement the Registration repository
 */
interface Registration_Repository_Interface extends Repository_Interface {
    /**
     * Save a club player.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int;

    /**
     * Find a club player by its ID.
     *
     * @param int|string|null $id
     *
     * @return Club_Player|null
     */
    public function find_by_id( int|string|null $id ): ?Club_Player;

    /**
     * Find registrations by player ID and status.
     *
     * @param int $player_id
     * @param string $status
     *
     * @return array
     */
    public function find_by_player( int $player_id, string $status ): array;

    /**
     * Find a registration by club and player IDs.
     *
     * @param int $club_id
     * @param int $player_id
     *
     * @return Club_Player|null
     */
    public function find_by_club_and_player( int $club_id, int $player_id ): ?Club_Player;

    /**
     * Find a club player by its ID.
     *
     * @param int $club_player_id
     *
     * @return Club_Player|null
     */
    public function find( int $club_player_id ): ?Club_Player;

    /**
     * Delete a registration by its ID.
     *
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;

    /**
     * Delete registrations for a club.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function delete_for_club( int $club_id ): bool;

    /**
     * Find club IDs where a player is registered.
     *
     * @param int $player_id
     * @param int|null $club_id
     *
     * @return array
     */
    public function find_club_ids_where_player_is_registered( int $player_id, ?int $club_id ): array;
}
