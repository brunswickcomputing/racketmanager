<?php
/**
 * Invoice API: invoice class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Invoice
 */

namespace Racketmanager\Domain;

/**
 * Class to implement the invoice object
 *
 * @Entity
 */
final class Invoice {
    /**
     * Id
     *
     * @var ?int
     */
    public ?int $id = null;
    /**
     * Invoice number
     *
     * @var int
     */
    public int $invoice_number;
    /**
     * Club
     *
     * @var object
     */
    public object $club;
    /**
     * Billable id
     *
     * @var int|null
     */
    public ?int $billable_id;
    /**
     * Billable type
     *
     * @var string|null
     */
    public ?string $billable_type;
    /**
     * Charge
     *
     * @var object
     */
    public object $charge;
    /**
     * Charge id
     *
     * @var int
     */
    public int $charge_id;
    /**
     * Status
     *
     * @var string
     */
    public string $status = 'draft';
    /**
     * Invoice date
     *
     * @var string
     */
    public string $date;
    /**
     * Date due
     *
     * @var string
     */
    public string $date_due;
    /**
     * Amount
     *
     * @var string
     */
    public string $amount;
    /**
     * Payment ref
     *
     * @var string|null
     */
    public string|null $payment_reference = null;
    /**
     * Purchase order
     *
     * @var string|null
     */
    public string|null $purchase_order = null;
    /**
     * Details
     *
     * @var object|string|null
     */
    public object|string|null $details;
    /**
     * Racketmanager
     *
     * @var object
     */
    public object $racketmanager;

    /**
     * Construct class instance
     *
     * @param object|null $invoice invoice object.
     */
    public function __construct( ?object $invoice = null ) {
        if ( ! is_null( $invoice ) ) {
            if ( isset( $invoice->details ) ) {
                $invoice->details = json_decode( $invoice->details );
            }
            foreach ( get_object_vars( $invoice ) as $key => $value ) {
                $this->$key = $value;
            }
        }
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    public function set_charge_id( ?int $charge_id ): void {
        $this->charge_id = $charge_id;
    }

    public function set_billable_id( int $billable_id ): void {
        $this->billable_id = $billable_id;
    }

    public function set_billable_type( string $billable_type ): void {
        $this->billable_type = $billable_type;
    }

    public function set_date( string $date ): void {
        $this->date = $date;
    }

    public function set_date_due( ?string $date_due ): void {
        $this->date_due = $date_due;
    }

    public function set_invoice_number( mixed $invoice_number ):void {
        $this->invoice_number = $invoice_number;
    }

    /**
     * Set the invoice amount
     *
     * @param string $amount amount value.
     */
    public function set_amount(string $amount ):  void {
        $this->amount = $amount;
    }
    /**
     * Set invoice status
     *
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        if ( 'resend' === $status ) {
            $status = 'sent';
        }
        $this->status = $status;
    }
    /**
     * Set payment reference status
     *
     * @param string $reference reference value.
     */
    public function set_payment_reference(string $reference ): void {
        $this->payment_reference = $reference;
    }
    /**
     * Set purchase order
     *
     * @param string $purchase_order purchase order.
     */
    public function set_purchase_order(string $purchase_order ): void {
        $this->purchase_order = $purchase_order;
    }
    /**
     * Set details
     *
     * @param object $details invoice details.
     */
    public function set_details( object $details ): void {
        $this->details = $details;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_charge_id(): int {
        return $this->charge_id;
    }

    public function get_billable_id(): ?int {
        return $this->billable_id;
    }

    public function get_billable_type(): ?string {
        return $this->billable_type;
    }

    public function get_invoice_number(): int {
        return $this->invoice_number;
    }

    public function get_date(): null|string {
        return $this->date;
    }

    public function get_date_due(): null|string {
        return $this->date_due;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_amount(): null|int {
        return $this->amount;
    }

    public function get_payment_reference(): null|string {
        return $this->payment_reference;
    }

    public function get_purchase_order(): null|string {
        return $this->purchase_order;
    }

    public function get_details(): null|string {
        return json_encode( $this->details );
    }

}
