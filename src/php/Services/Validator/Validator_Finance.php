<?php
/**
 * Entry Form Validation API: Finance validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

use function Racketmanager\get_charge;
use function Racketmanager\get_invoice;

/**
 * Class to implement the Finance Validator object
 */
final class Validator_Finance extends Validator_Config {
    /**
     * Validate charge
     *
     * @param string|null $charge_id charge id.
     *
     * @return object $validation updated validation object.
     */
    public function charge( ?string $charge_id ): object {
        if ( empty( $charge_id ) ) {
            $this->error      = true;
            $this->err_flds[] = 'charge';
            $this->err_msgs[] = __( 'Charge id not found', 'racketmanager' );
        } else {
            if ( is_numeric( $charge_id ) ) {
                $charge = get_charge( $charge_id );
            } else {
                $this->error      = true;
                $this->err_flds[] = 'charge';
                $this->err_msgs[] = __( 'Charge id must be numeric', 'racketmanager' );
            }
            if ( ! $charge ) {
                $this->error      = true;
                $this->err_flds[] = 'charge';
                $this->err_msgs[] = __( 'Charge not valid', 'racketmanager' );
            }
        }
        return $this;
    }
    /**
     * Validate status
     *
     * @param string|null $status status.
     *
     * @return object $validation updated validation object.
     */
    public function status( ?string $status ): object {
        if ( ! $status ) {
            $error_field   = 'status';
            $error_message = __( 'Status must be set', 'racketmanager' );
            $status        = 400;
            $this->set_errors( $error_field, $error_message, $status );
        }

        return $this;
    }
    /**
     * Validate invoice
     *
     * @param int|null $invoice_id invoice.
     * @param string $error_field error field.
     *
     * @return object $validation updated validation object.
     */
    public function invoice( ?int $invoice_id, string $error_field = 'invoice' ): object {
        if ( empty( $invoice_id ) ) {
            $this->error      = true;
            $this->err_flds[] = $error_field;
            $this->err_msgs[] = __( 'Invoice id not found', 'racketmanager' );
            $this->status     = 404;
        } else {
            $invoice = get_invoice( $invoice_id );
            if ( ! $invoice ) {
                $this->error      = true;
                $this->err_flds[] = $error_field;
                $this->err_msgs[] = __( 'Invoice not found', 'racketmanager' );
                $this->status     = 404;
            }
        }
        return $this;
    }
    /**
     * Validate purchase order
     *
     * @param ?string $purchase_order new purchase order.
     * @param ?string $original_purchase_order original match date.
     * @return object $validation updated validation object.
     */
    public function purchase_order( ?string $purchase_order, ?string $original_purchase_order ): object {
        if ( $purchase_order === $original_purchase_order ) {
            $this->error      = true;
            $this->err_flds[] = 'purchaseOrder';
            $this->err_msgs[] = __( 'PO not changed', 'racketmanager' );
        }
        return $this;
    }
}
