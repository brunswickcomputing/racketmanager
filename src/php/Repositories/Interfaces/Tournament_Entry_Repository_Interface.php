<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Tournament_Entry;

/**
 * Interface for Tournament_Entry_Repository.
 */
interface Tournament_Entry_Repository_Interface extends Repository_Interface {
    /**
     * Find a tournament entry by ID.
     *
     * @param int|string|null $id
     *
     * @return Tournament_Entry|null
     */
    public function find_by_id( int|string|null $id ): ?Tournament_Entry;

    /**
     * Find a tournament entry by tournament and player.
     *
     * @param int $tournament_id
     * @param int $player_id
     *
     * @return Tournament_Entry|null
     */
    public function find_by_tournament_and_player( int $tournament_id, int $player_id ): ?Tournament_Entry;

    /**
     * Save a tournament entry.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): int|bool;

    /**
     * Find entries by tournament and status.
     *
     * @param int $tournament_id
     * @param string|null $status
     * @return array
     */
    public function find_by_tournament( int $tournament_id, ?string $status = null ): array;
}
