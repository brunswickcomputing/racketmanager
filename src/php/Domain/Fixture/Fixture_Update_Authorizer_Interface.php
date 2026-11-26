<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

/**
 * Defines a contract for checking whether fixture updates are permitted.
 *
 * Implementations evaluate a fixture and return a result object that represents
 * the authorisation decision.
 */
interface Fixture_Update_Authorizer_Interface {
    /**
     * Determines whether an update is allowed for the specified fixture.
     *
     * @param Fixture $fixture Fixture to check for update permission.
     *
     * @return object Result object describing whether the update is allowed.
     */
    public function is_update_allowed( Fixture $fixture ): object;
}
