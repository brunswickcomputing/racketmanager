<?php
/**
 * Invoice_Full_Details_DTO API: Invoice_Full_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO\Finance;

use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Domain\Value_Objects\Money;
use stdClass;

/**
 * Class to implement the Invoice Full Details Data Transfer Object
 */
class Invoice_Full_Details_DTO {
    public int $id;
    public int $charge_id;
    public int $billable_id;
    public string $billable_type;
    public string $billable_name;
    public int $invoice_number;
    public string $status;
    public ?string $amount;
    public string $date;
    public string $date_due;
    public ?string $payment_reference;
    public ?string $purchase_order;
    public stdClass $details;
    public ?string $billable_address;
    public Invoice $invoice;
    public ?Player $contact;
    public int $season;
    public string $charge_name;
    public string $competition_type;

    /**
     * Invoice_Details_DTO constructor.
     *
     * @param Invoice $invoice
     * @param string|null $billable_name
     * @param string|null $billable_address
     * @param Player|null $contact
     * @param Charge_Details_DTO $charge
     */
    public function __construct( Invoice $invoice, ?string $billable_name, ?string $billable_address, ?Player $contact, Charge_Details_DTO $charge ) {
        $this->invoice           = $invoice;
        $this->contact           = $contact;
        $this->season            = (int) $charge->season;
        $this->id                = (int) $invoice->id;
        $this->charge_name       = $charge->charge_name;
        $this->charge_id         = $invoice->charge_id;
        $this->billable_id       = $invoice->billable_id;
        $this->billable_type     = $invoice->billable_type;
        $this->billable_name     = $billable_name;
        $this->billable_address  = $billable_address;
        $this->invoice_number    = $invoice->invoice_number;
        $this->status            = $invoice->status;
        $this->amount            = isset( $invoice->amount ) ? (string) $invoice->amount : null;
        $this->date              = $invoice->date;
        $this->date_due          = $invoice->date_due;
        $this->payment_reference = $invoice->payment_reference;
        $this->purchase_order    = $invoice->purchase_order;
        $this->details           = $invoice->details;
        $this->competition_type  = $charge->competition_type;
    }

    public function get_money( ?Currency $currency = null ): ?Money {
        if ( null === $this->amount || '' === $this->amount ) {
            return null;
        }
        return Money::from_string( $this->amount, $currency );
    }

    public function get_status_enum(): ?Invoice_Status {
        return Invoice_Status::try_from_value( $this->status );
    }

}
