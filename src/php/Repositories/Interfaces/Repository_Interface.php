<?php
/**
 * Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

/**
 * Interface to implement a base repository
 */
interface Repository_Interface {
    /**
     * Find an entity by its ID.
     *
     * @param int|string $id
     * @return object|null
     */
    public function find_by_id( $id ): ?object;

    /**
     * Save an entity.
     *
     * @param object $entity
     * @return int|bool The insert ID (int) for new entities, or success (bool) for updates.
     */
    public function save( object $entity );

    /**
     * Delete an entity by its ID.
     *
     * @param int $id
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;
}
