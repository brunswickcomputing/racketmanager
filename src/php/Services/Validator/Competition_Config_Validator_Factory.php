<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Application\Ports\Competition_Config_Validator_Factory_Interface;
use Racketmanager\Services\Competition_Service;

/**
 * Factory for creating competition config validator instances.
 */
readonly class Competition_Config_Validator_Factory implements Competition_Config_Validator_Factory_Interface {
    /**
     * Constructor.
     *
     * @param Competition_Service|null $competition_service Competition service.
     */
    public function __construct(
        private ?Competition_Service $competition_service = null
    ) {
    }

    /**
     * Create a competition config validator instance.
     *
     * @return object
     */
    public function create(): object {
        return new Validator_Config( $this->competition_service );
    }
}
