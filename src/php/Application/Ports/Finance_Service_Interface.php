<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Ports;

use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Tournament\Currency;
use stdClass;
use WP_Error;

/**
 * Port interface for finance operations.
 */
interface Finance_Service_Interface {
    /**
     * Get an invoice by ID.
     *
     * @param int|string|null $invoice_id Invoice ID.
     *
     * @return Invoice|null
     */
    public function get_invoice( int|string|null $invoice_id ): ?Invoice;

    /**
     * Get invoices matching criteria.
     *
     * @param array $criteria Search criteria.
     * @return array
     */
    public function get_invoices_by_criteria( array $criteria ): array;

    /**
     * Get full details for an invoice.
     *
     * @param int|null $invoice_id Invoice ID.
     * @return Invoice_Full_Details_DTO|null
     */
    public function get_full_invoice_details( ?int $invoice_id ): ?Invoice_Full_Details_DTO;

    /**
     * Get current currency.
     *
     * @return Currency
     */
    public function get_currency(): Currency;

    /**
     * Create tournament payment request.
     *
     * @param int|null $tournament_id Tournament ID.
     * @param int|null $player_id     Player ID.
     * @param int|null $invoice_id    Invoice ID.
     * @return string
     */
    public function create_tournament_payment_request( ?int $tournament_id, ?int $player_id, ?int $invoice_id ): string;

    /**
     * Get charge by ID.
     *
     * @param int|string|null $charge_id Charge ID.
     *
     * @return Charge
     */
    public function get_charge( int|string|null $charge_id ): Charge;

    /**
     * Add a charge.
     *
     * @param stdClass $new_charge New charge details.
     * @return Charge|int|WP_Error
     */
    public function add_charge( stdClass $new_charge ): Charge|int|WP_Error;

    /**
     * Perform an action on an invoice.
     *
     * @param int|null    $invoice_id Invoice ID.
     * @param string|null $action     Action name.
     * @return string
     */
    public function action_invoice( ?int $invoice_id, ?string $action ): string;

    /**
     * Action multiple invoices.
     *
     * @param string      $action   Action name.
     * @param array       $invoices Array of invoice IDs.
     * @param string|null $user_id  Optional user ID.
     * @return array
     */
    public function action_invoices( string $action, array $invoices, ?string $user_id = null ): array;

    /**
     * Send invoices for a charge.
     *
     * @param int|null $charge_id Charge ID.
     * @param bool     $clear     Whether to clear.
     * @return bool
     */
    public function send_invoices_for_charge( ?int $charge_id, bool $clear = false ): bool;

    /**
     * Generate invoices for a charge.
     *
     * @param int         $charge_id Charge ID.
     * @param string|null $user_id   Optional user ID.
     * @return int
     */
    public function generate_invoices( int $charge_id, ?string $user_id = null ): int;

    /**
     * Set purchase order for an invoice.
     *
     * @param int|null    $invoice_id     Invoice ID.
     * @param string|null $purchase_order Purchase order reference.
     * @return bool
     */
    public function set_purchase_order_for_invoice( ?int $invoice_id, ?string $purchase_order ): bool;

    /**
     * Set purchase order for an invoice (alias).
     *
     * @param int    $invoice_id     Invoice ID.
     * @param string $purchase_order Purchase order reference.
     * @return bool
     */
    public function set_purchase_order( int $invoice_id, string $purchase_order ): bool;

    /**
     * Get tournament paid total for a player.
     *
     * @param int|null $player_id     Player ID.
     * @param int|null $tournament_id Tournament ID.
     * @return float
     */
    public function get_tournament_paid_total_for_player( ?int $player_id, ?int $tournament_id ): float;

    /**
     * Update payment status.
     *
     * @param string $payment_intent_id Payment intent ID.
     * @param string $status            Payment status.
     * @return void
     */
    public function update_payment( string $payment_intent_id, string $status = 'paid' ): void;
}
