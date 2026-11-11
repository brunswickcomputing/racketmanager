<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Ports;

/**
 * Port interface for creating competition config validator instances.
 */
interface Competition_Config_Validator_Factory_Interface {
    /**
     * Create a competition config validator instance.
     *
     * @return object
     */
    public function create(): object;
}
