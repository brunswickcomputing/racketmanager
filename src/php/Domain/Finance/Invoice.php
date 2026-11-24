<?php
declare( strict_types=1 );
/**
 * Invoice API: invoice class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Invoice
 */

namespace Racketmanager\Domain\Finance;

use Racketmanager\Domain\DTO\Finance\Invoice_Hydration_DTO;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Json_Encoder;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Class to implement the invoice object
 *
 * @Entity
 */
class Invoice {
    /**
     * Id
     *
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * Invoice number
     *
     * @var int|null
     */
    public private(set) ?int $invoice_number = null;

    /**
     * Club
     *
     * @var object|null
     */
    public private(set) ?object $club = null;

    /**
     * Billable id
     *
     * @var int|null
     */
    public private(set) ?int $billable_id = null;

    /**
     * Billable type
     *
     * @var string|null
     */
    public private(set) ?string $billable_type = null;

    /**
     * Charge
     *
     * @var object|null
     */
    public private(set) ?object $charge = null;

    /**
     * Charge id
     *
     * @var int|null
     */
    public private(set) ?int $charge_id = null;

    /**
     * Status
     *
     * @var string
     */
    public private(set) string $status = 'draft';

    /**
     * Invoice date
     *
     * @var string|null
     */
    public private(set) ?string $date = null;

    /**
     * Date due
     *
     * @var string|null
     */
    public private(set) ?string $date_due = null;

    /**
     * Amount
     *
     * @var string|null
     */
    public private(set) ?string $amount = null;

    /**
     * Payment ref
     *
     * @var string|null
     */
    public private(set) ?string $payment_reference = null;

    /**
     * Purchase order
     *
     * @var string|null
     */
    public private(set) ?string $purchase_order = null;

    /**
     * Details
     *
     * @var object|string|null
     */
    public private(set) object|string|null $details = null;

    /**
     * Construct class instance
     *
     * @param Invoice_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Invoice_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->set_id( $dto->id );
        $this->set_invoice_number( $dto->invoice_number );
        $this->set_charge_id( $dto->charge_id );
        $this->set_billable_id( $dto->billable_id );
        $this->set_billable_type( $dto->billable_type );
        $this->set_status( $dto->status ?? 'draft' );
        $this->set_date( $dto->date );
        $this->set_date_due( $dto->date_due );
        $this->set_amount( $dto->amount );
        $this->set_payment_reference( $dto->payment_reference );
        $this->set_purchase_order( $dto->purchase_order );
        $this->set_details( $dto->details );
    }

    /**
     * Create an invoice instance from an object.
     *
     * @param object $row Source object containing invoice data.
     * @return self Invoice instance created from the object.
     */
    public static function from_object( object $row ): self {
        return Invoice_Factory::from_object( $row );
    }

    /**
     * Create an invoice instance from array data.
     *
     * @param array<string, mixed> $data Invoice data.
     * @return self Created invoice instance.
     */
    public static function from_array( array $data ): self {
        return Invoice_Factory::from_array( $data );
    }

    /**
     * Set the identifier.
     *
     * @param int|null $id Identifier value.
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * Gets the identifier.
     *
     * @return int|null The identifier, or null if none is set.
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set the invoice number.
     *
     * @param mixed $invoice_number Invoice number value.
     * @return void
     */
    public function set_invoice_number( mixed $invoice_number ): void {
        $this->invoice_number = ( null === $invoice_number || '' === (string) $invoice_number ) ? null : (int) $invoice_number;
    }

    /**
     * Gets the invoice number associated with the invoice.
     *
     * @return int|null The invoice number, or null if not set.
     */
    public function get_invoice_number(): ?int {
        return $this->invoice_number;
    }

    /**
     * Set the club entity.
     *
     * @param object|null $club Club entity.
     * @return void
     */
    public function set_club( ?object $club ): void {
        $this->club = $club;
    }

    /**
     * Gets the club entity.
     *
     * @return object|null The club entity, or null if not set.
     */
    public function get_club(): ?object {
        return $this->club;
    }

    /**
     * Set the billable identifier.
     *
     * @param int|null $billable_id Billable identifier.
     * @return void
     */
    public function set_billable_id( ?int $billable_id ): void {
        $this->billable_id = $billable_id;
    }

    /**
     * Gets the billable identifier associated with the entity.
     *
     * @return int|null The billable identifier, or null if none is set.
     */
    public function get_billable_id(): ?int {
        return $this->billable_id;
    }

    /**
     * Set the billable type.
     *
     * @param string|null $billable_type Billable type value.
     * @return void
     */
    public function set_billable_type( ?string $billable_type ): void {
        $this->billable_type = $billable_type;
    }

    /**
     * Gets the billable type.
     *
     * @return string|null The billable type, or null if none is set.
     */
    public function get_billable_type(): ?string {
        return $this->billable_type;
    }

    /**
     * Set the charge entity.
     *
     * @param object|null $charge Charge entity.
     * @return void
     */
    public function set_charge( ?object $charge ): void {
        $this->charge = $charge;
    }

    /**
     * Gets the charge entity.
     *
     * @return object|null The charge entity, or null if not set.
     */
    public function get_charge(): ?object {
        return $this->charge;
    }

    /**
     * Set the charge ID.
     *
     * @param int|null $charge_id Charge ID value.
     * @return void
     */
    public function set_charge_id( ?int $charge_id ): void {
        $this->charge_id = $charge_id;
    }

    /**
     * Gets the charge ID associated with the payment.
     *
     * @return int|null The charge ID, or null if none is set.
     */
    public function get_charge_id(): ?int {
        return $this->charge_id;
    }

    /**
     * Set invoice status.
     *
     * @param string|Invoice_Status $status Status value.
     * @return void
     */
    public function set_status( string|Invoice_Status $status ): void {
        if ( $status instanceof Invoice_Status ) {
            $this->status = $status->value;
            return;
        }
        $enum         = Invoice_Status::try_from_value( $status );
        $this->status = $enum ? $enum->value : $status;
    }

    /**
     * Gets the current status.
     *
     * @return string The current status.
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Sets the invoice status using the provided status enum.
     *
     * @param Invoice_Status $status The invoice status to set.
     * @return void
     */
    public function set_status_enum( Invoice_Status $status ): void {
        $this->status = $status->value;
    }

    /**
     * Gets the invoice status as an enum value.
     *
     * @return Invoice_Status|null The invoice status enum, or null if the status value is invalid.
     */
    public function get_status_enum(): ?Invoice_Status {
        return Invoice_Status::try_from_value( $this->status );
    }

    /**
     * Determines whether the current status indicates that the payment is paid.
     *
     * @return bool True if the payment is paid, false otherwise.
     */
    public function is_paid(): bool {
        return $this->get_status_enum()?->is_paid() ?? false;
    }

    /**
     * Determines whether the current status represents a draft state.
     *
     * @return bool True if the current status is draft, false otherwise.
     */
    public function is_draft(): bool {
        return $this->get_status_enum()?->is_draft() ?? false;
    }

    /**
     * Determines whether the current status is final.
     *
     * @return bool True if the current status is final, false otherwise.
     */
    public function is_final(): bool {
        return $this->get_status_enum()?->is_final() ?? false;
    }

    /**
     * Determines whether the current status indicates that the item has been sent.
     *
     * @return bool True if the item has been sent, false otherwise.
     */
    public function is_sent(): bool {
        return $this->get_status_enum()?->is_sent() ?? false;
    }

    /**
     * Determines whether the current status is cancelled.
     *
     * @return bool True if the current status is cancelled, false otherwise.
     */
    public function is_cancelled(): bool {
        return $this->get_status_enum()?->is_cancelled() ?? false;
    }

    /**
     * Determines whether the current status represents a new state.
     *
     * @return bool True if the current status is new, false otherwise.
     */
    public function is_new(): bool {
        return $this->get_status_enum()?->is_new() ?? false;
    }

    /**
     * Determines whether the current status allows the item to be sent.
     *
     * @return bool True if the item can be sent, false otherwise.
     */
    public function can_be_sent(): bool {
        return $this->get_status_enum()?->can_be_sent() ?? false;
    }

    /**
     * Set the date.
     *
     * @param string|null $date Date value.
     * @return void
     */
    public function set_date( ?string $date ): void {
        $this->date = $date;
    }

    /**
     * Gets the date value.
     *
     * @return string|null The date value, or null if none is set.
     */
    public function get_date(): ?string {
        return $this->date;
    }

    /**
     * Set the due date.
     *
     * @param string|null $date_due Due date value.
     * @return void
     */
    public function set_date_due( ?string $date_due ): void {
        $this->date_due = $date_due;
    }

    /**
     * Gets the due date associated with the payment.
     *
     * @return string|null The due date, or null if none is set.
     */
    public function get_date_due(): ?string {
        return $this->date_due;
    }

    /**
     * Set the invoice amount.
     *
     * @param string|float|int|Money|null $amount Amount value.
     * @return void
     */
    public function set_amount( string|float|int|Money|null $amount ): void {
        if ( $amount instanceof Money ) {
            $this->set_money( $amount );
            return;
        }
        $this->amount = ( null === $amount || '' === (string) $amount ) ? null : (string) $amount;
    }

    /**
     * Gets the amount associated with the payment.
     *
     * @return string|null The amount, or null if none is set.
     */
    public function get_amount(): ?string {
        return $this->amount;
    }

    /**
     * Gets the monetary amount as a Money instance.
     *
     * @param Currency|null $currency The currency to use when creating the Money instance, or null to use the default currency handling.
     * @return Money|null The monetary amount, or null if no amount is set.
     */
    public function get_money( ?Currency $currency = null ): ?Money {
        if ( null === $this->amount || '' === $this->amount ) {
            return null;
        }
        return Money::from_string( $this->amount, $currency );
    }

    /**
     * Sets the monetary amount for this instance.
     *
     * @param Money $money The money object used to set the amount.
     * @return void
     */
    public function set_money( Money $money ): void {
        $this->amount = $money->get_amount_string();
    }

    /**
     * Set the payment reference.
     *
     * @param string|null $reference Reference value.
     * @return void
     */
    public function set_payment_reference( ?string $reference ): void {
        $this->payment_reference = $reference;
    }

    /**
     * Gets the payment reference associated with the payment.
     *
     * @return string|null The payment reference, or null if none is set.
     */
    public function get_payment_reference(): ?string {
        return $this->payment_reference;
    }

    /**
     * Set purchase order.
     *
     * @param string|null $purchase_order Purchase order.
     * @return void
     */
    public function set_purchase_order( ?string $purchase_order ): void {
        $this->purchase_order = $purchase_order;
    }

    /**
     * Get the purchase order.
     *
     * @return string|null Purchase order value, or null if not set.
     */
    public function get_purchase_order(): ?string {
        return $this->purchase_order;
    }

    /**
     * Set details.
     *
     * @param object|string|null $details Invoice details.
     * @return void
     */
    public function set_details( object|string|null $details ): void {
        $this->details = $details;
    }

    /**
     * Get the details as a JSON-encoded string.
     *
     * @return string|null JSON-encoded details, or null if not set.
     */
    public function get_details(): ?string {
        if ( null === $this->details ) {
            return null;
        }
        if ( is_string( $this->details ) ) {
            return $this->details;
        }
        $encoded = Json_Encoder::encode( $this->details );
        return is_string( $encoded ) ? $encoded : null;
    }
}
