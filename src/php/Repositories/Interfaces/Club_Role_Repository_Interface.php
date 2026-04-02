<?php
/**
 * Club_Role_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Club_Role;

/**
 * Interface to implement the Club_Role repository
 */
interface Club_Role_Repository_Interface extends Repository_Interface {
    /**
     * Save a club role.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int;

    /**
     * Find a club role by its ID.
     *
     * @param int|string|null $id
     *
     * @return Club_Role|null
     */
    public function find_by_id( int|string|null $id ): ?Club_Role;

    /**
     * Find a club role by its ID.
     *
     * @param int $id
     *
     * @return Club_Role|null
     */
    public function find( int $id ): ?Club_Role;

    /**
     * Delete a club role by its ID.
     *
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;

    /**
     * Search for club roles.
     *
     * @param array $args
     *
     * @return array|null
     */
    public function search( array $args ): ?array;

    /**
     * Build club roles from SQL and group.
     *
     * @param string $sql
     * @param string $group
     *
     * @return array
     */
    public function build_club_roles( string $sql, string $group ): array;

    /**
     * Delete club roles for a role.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete_for_role( int $id ): bool;

    /**
     * Delete club roles for a club.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function delete_for_club( int $club_id ): bool;

    /**
     * Get roles for a club.
     *
     * @param int $club_id
     *
     * @return array
     */
    public function get_roles_for_club( int $club_id ): array;

    /**
     * Find clubs by player and role.
     *
     * @param int $user_id
     * @param string $role_name
     * @param int|null $club_id
     *
     * @return array
     */
    public function find_clubs_by_player_and_role( int $user_id, string $role_name, ?int $club_id ): array;
}
