<?php
/**
 * Invoice_Full_Details_DTO API: Invoice_Full_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO;

use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
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
    public int $amount;
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
        $this->season            = $charge->season;
        $this->charge_name       = $charge->charge_name;
        $this->charge_id         = $invoice->charge_id;
        $this->billable_id       = $invoice->billable_id;
        $this->billable_type     = $invoice->billable_type;
        $this->billable_name     = $billable_name;
        $this->billable_address  = $billable_address;
        $this->invoice_number    = $invoice->invoice_number;
        $this->status            = $invoice->status;
        $this->amount            = $invoice->amount;
        $this->date              = $invoice->date;
        $this->date_due          = $invoice->date_due;
        $this->payment_reference = $invoice->payment_reference;
        $this->purchase_order    = $invoice->purchase_order;
        $this->details           = $invoice->details;
        $this->competition_type  = $charge->competition_type;
    }

}
