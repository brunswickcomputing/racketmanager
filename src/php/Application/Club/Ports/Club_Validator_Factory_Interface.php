<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Ports;

/**
 * Port interface for creating club validators.
 */
interface Club_Validator_Factory_Interface {
    /**
     * Create a new club validator instance.
     *
     * @return Club_Validator_Interface
     */
    public function create(): Club_Validator_Interface;
}
