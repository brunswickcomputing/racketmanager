<?php
/**
 * Tournament Validation API: Tournament validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\validator;

/**
 * Class to implement the Tournament Validator object
 */
final class Validator_Tournament extends Validator_Config {
    /**
     * Validate name
     *
     * @param string|null $name name.
     *
     * @return object $validation updated validation object.
     */
    public function name( ?string $name ): object {
        if ( ! $name ) {
            $this->error      = true;
            $this->err_flds[] = 'tournamentName';
            $this->err_msgs[] = __( 'Name must be specified', 'racketmanager' );
        }

        return $this;
    }
}
