<?php
/**
 * Finances invoices page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Finances_Invoices_Page_View_Model {

    /**
     * @param array<int, object>    $invoices
     * @param array<int, object>    $charges
     * @param array<int, object>    $clubs
     * @param string                $racketmanager_tab 'club-invoices'|'player-invoices'
     * @param int|string|null       $club_id
     * @param int|string|null       $charge_id
     * @param string                $status
     * @param string                $billable_type
     * @param string                $total_amount_formatted
     * @param float                 $total_amount
     * @param Error_Bag             $errors
     */
    public function __construct(
        public array $invoices,
        public array $charges,
        public string $billable_type,
        public string $total_amount_formatted,
        public array $clubs = array(),
        public string $racketmanager_tab = 'club-invoices',
        public int|string|null $club_id = null,
        public int|string|null $charge_id = null,
        public string $status = 'open',
        public float $total_amount = 0.0,
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }
}
