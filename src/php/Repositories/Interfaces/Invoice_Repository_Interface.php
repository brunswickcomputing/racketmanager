<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Invoice;

/**
 * Interface for Invoice_Repository.
 */
interface Invoice_Repository_Interface extends Repository_Interface {
    /**
     * Save an invoice.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): int|bool;

    /**
     * Find an invoice by ID.
     *
     * @param int|string|null $id
     *
     * @return Invoice|null
     */
    public function find_by_id( int|string|null $id ): ?Invoice;

    /**
     * Find invoices by criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function find_by( array $criteria ): array;

    /**
     * Find an invoice by charge and billable.
     *
     * @param int|null $charge_id
     * @param int|null $billable_id
     * @param string $billable_type
     *
     * @return Invoice|null
     */
    public function find_by_charge_and_billable( ?int $charge_id, ?int $billable_id, string $billable_type ): ?Invoice;

    /**
     * Check if a charge has any invoices.
     *
     * @param int $charge_id
     * @return bool
     */
    public function has_invoices( int $charge_id ): bool;

    /**
     * Find the total amount paid for a tournament by a player.
     *
     * @param int $player_id
     * @param int $tournament_id
     * @return float
     */
    public function find_tournament_paid_total_by_player( int $player_id, int $tournament_id ): float;
}
