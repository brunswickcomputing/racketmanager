<?php
/**
 * Finances invoice item view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Invoice_Item_View_Model {

    /**
     * @param int                   $id
     * @param string|int            $invoice_number
     * @param string                $charge_name
     * @param string                $billable_name
     * @param float|int|string|null $amount
     * @param string                $amount_formatted
     * @param string                $status
     * @param string                $date_due
     * @param string                $date_due_formatted
     * @param string                $edit_url
     */
    public function __construct(
        public int $id,
        public string|int $invoice_number,
        public string $charge_name,
        public string $billable_name,
        public float|int|string|null $amount,
        public string $amount_formatted,
        public string $status,
        public string $date_due,
        public string $date_due_formatted,
        public string $edit_url,
    ) {
    }
}
