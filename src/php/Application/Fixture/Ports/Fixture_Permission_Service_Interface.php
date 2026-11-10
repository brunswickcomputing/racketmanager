<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for fixture permission and authorization checks.
 */
interface Fixture_Permission_Service_Interface {
    /**
     * Check whether fixture update is allowed.
     *
     * @param int|Fixture $fixture_id
     *
     * @return object
     */
    public function is_update_allowed( int|Fixture $fixture_id ): object;
}
