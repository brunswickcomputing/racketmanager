<?php
/**
 * Entry Form Validation API: Finance validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager;

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
            $this->error      = true;
            $this->err_flds[] = 'status';
            $this->err_msgs[] = __( 'Status must be set', 'racketmanager' );
        }

        return $this;
    }
}
