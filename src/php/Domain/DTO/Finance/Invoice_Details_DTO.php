<?php
/**
 * Invoice_Details_DTO API: Invoice_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\DTO
 */

namespace Racketmanager\Domain\DTO\Finance;

use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Domain\Value_Objects\Money;

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
    public ?string $amount;
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
        $this->amount            = isset( $data->amount ) ? (string) $data->amount : null;
        $this->date              = $data->date;
        $this->date_due          = $data->date_due;
        $this->payment_reference = $data->payment_reference;
        $this->purchase_order    = $data->purchase_order;
        $this->details           = $data->details;
    }

    public function get_money( ?Currency $currency = null ): ?Money {
        return null === $this->amount ? null : Money::from_string( $this->amount, $currency );
    }

    public function get_status_enum(): ?Invoice_Status {
        return Invoice_Status::try_from_value( $this->status );
    }

}
