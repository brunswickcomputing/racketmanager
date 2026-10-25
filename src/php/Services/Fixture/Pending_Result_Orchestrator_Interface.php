<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

/**
 * Interface for orchestrating scheduled workflows for pending results and approvals.
 */
interface Pending_Result_Orchestrator_Interface {

    /**
     * Chase pending results for a competition type.
     *
     * @param string $competition Competition type (e.g., 'league').
     */
    public function chase_pending_results( string $competition = 'league' ): void;

    /**
     * Chase pending approvals for a competition type.
     *
     * @param string $competition Competition type (e.g., 'league').
     */
    public function chase_pending_approvals( string $competition = 'league' ): void;
}
