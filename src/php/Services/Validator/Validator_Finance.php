<?php
/**
 * Entry Form Validation API: Finance validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

/**
 * Class to implement the Finance Validator object
 */
final class Validator_Finance extends Validator_Config {
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

}
