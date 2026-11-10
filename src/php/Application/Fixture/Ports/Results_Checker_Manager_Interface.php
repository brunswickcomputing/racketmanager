<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Result\Results_Checker;

/**
 * Port interface for results checker approval and error handling workflows.
 */
interface Results_Checker_Manager_Interface {
    /**
     * Approve a result checker entry.
     *
     * @param Results_Checker $checker
     */
    public function approve( Results_Checker $checker ): void;

    /**
     * Handle a result checker entry (apply penalties).
     *
     * @param Results_Checker $checker
     */
    public function handle( Results_Checker $checker ): void;
}
