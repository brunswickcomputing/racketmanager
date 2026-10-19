<?php
/**
 * Invoice View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Invoice_View_Model {
    /**
     * @param int|null                   $id
     * @param string|int                 $invoice_number
     * @param string                     $title
     * @param string                     $status
     * @param string|null                $status_display
     * @param bool                       $is_paid
     * @param string|null                $date
     * @param string                     $date_formatted
     * @param string|null                $date_due
     * @param string                     $date_due_formatted
     * @param string                     $due_text
     * @param string|null                $purchase_order
     * @param string|null                $purchase_order_display
     * @param string                     $billable_name
     * @param string|null                $billable_address
     * @param string|null                $billable_address_formatted
     * @param string                     $charge_name
     * @param string                     $organisation_name
     * @param string                     $billing_address
     * @param string                     $billing_email
     * @param string                     $billing_url
     * @param string                     $billing_telephone
     * @param bool                       $has_breakdown
     * @param string|null                $fee_competition_formatted
     * @param string|null                $fee_events_unit_formatted
     * @param Invoice_Item_View_Model[]  $items
     * @param string                     $fee_total_formatted
     * @param bool                       $has_paid
     * @param string|null                $paid_formatted
     * @param string|null                $amount_due_formatted
     * @param string|null                $bank_name
     * @param string|null                $sort_code
     * @param string|null                $account_number
     * @param string|null                $payment_reference
     * @param bool                       $is_user_logged_in
     * @param string                     $login_url
     * @param string|null                $return_url
     * @param string                     $details
     */
    public function __construct(
        public ?int $id = null,
        public string|int $invoice_number = '',
        public string $title = '',
        public string $status = '',
        public ?string $status_display = null,
        public bool $is_paid = false,
        public ?string $date = null,
        public string $date_formatted = '',
        public ?string $date_due = null,
        public string $date_due_formatted = '',
        public string $due_text = '',
        public ?string $purchase_order = null,
        public ?string $purchase_order_display = null,
        public string $billable_name = '',
        public ?string $billable_address = null,
        public ?string $billable_address_formatted = null,
        public string $charge_name = '',
        public string $organisation_name = '',
        public string $billing_address = '',
        public string $billing_email = '',
        public string $billing_url = '',
        public string $billing_telephone = '',
        public bool $has_breakdown = false,
        public ?string $fee_competition_formatted = null,
        public ?string $fee_events_unit_formatted = null,
        public array $items = [],
        public string $fee_total_formatted = '',
        public bool $has_paid = false,
        public ?string $paid_formatted = null,
        public ?string $amount_due_formatted = null,
        public ?string $bank_name = null,
        public ?string $sort_code = null,
        public ?string $account_number = null,
        public ?string $payment_reference = null,
        public bool $is_user_logged_in = false,
        public string $login_url = '',
        public ?string $return_url = null,
        public string $details = '',
    ) {}
}
