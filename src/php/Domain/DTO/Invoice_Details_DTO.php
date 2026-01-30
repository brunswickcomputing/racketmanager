<?php
/**
 * Invoice_Details_DTO API: Invoice_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Invoice Details Data Transfer Object
 */
class Invoice_Details_DTO {
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
    public string $details;
    public string $charge_name;

    /**
     * Invoice_Details_DTO constructor.
     *
     * @param object $data
     */
    public function __construct( object $data ) {
        $season                  = $data->season;
        $competition_name        = $data->competition_name;
        $this->charge_name       = $season . ' ' . $competition_name;
        $this->id                = $data->id;
        $this->charge_id         = $data->charge_id;
        $this->billable_id       = $data->billable_id;
        $this->billable_type     = $data->billable_type;
        $this->billable_name     = $data->billable_name;
        $this->invoice_number    = $data->invoice_number;
        $this->status            = $data->status;
        $this->amount            = $data->amount;
        $this->date              = $data->date;
        $this->date_due          = $data->date_due;
        $this->payment_reference = $data->payment_reference;
        $this->purchase_order    = $data->purchase_order;
        $this->details           = $data->details;
    }

}
