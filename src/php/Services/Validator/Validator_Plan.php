<?php
/**
 * Entry Form Validation API: Plan validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\Services\Validator;

/**
 * Class to implement the PlanValidator object
 */
final class Validator_Plan extends Validator {
    /**
     * Validate start time
     *
     * @param string|null $start_time start time.
     *
     * @return object $validation updated validation object.
     */
    public function start_time( ?string $start_time ): object {
        if ( ! $start_time ) {
            $error_field   = 'start_time';
            $error_message = __( 'Start time must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
    /**
     * Validate time increment
     *
     * @param string|null $time_increment start time.
     *
     * @return object $validation updated validation object.
     */
    public function time_increment( ?string $time_increment ): object {
        if ( ! $time_increment ) {
            $error_field   = 'timeIncrement';
            $error_message = __( 'Time increment must be specified', 'racketmanager' );
            $this->set_errors( $error_field, $error_message );
        }
        return $this;
    }
}
