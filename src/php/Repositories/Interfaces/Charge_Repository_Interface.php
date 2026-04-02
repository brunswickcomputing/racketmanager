<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Charge;

/**
 * Interface for Charge_Repository.
 */
interface Charge_Repository_Interface extends Repository_Interface {
    /**
     * Save a charge.
     *
     * @param Charge $charge
     * @return int|bool
     */
    public function save( object $charge ): int|bool;

    /**
     * Find a charge by ID.
     *
     * @param int|string|null $id
     * @return Charge|null
     */
    public function find_by_id( $id ): ?Charge;

    /**
     * Find charges by criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function find_by( array $criteria ): array;
}
