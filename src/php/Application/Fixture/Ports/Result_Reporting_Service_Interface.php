<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for external result reporting.
 */
interface Result_Reporting_Service_Interface {
    /**
     * Report result for a fixture.
     *
     * @param Fixture $fixture The fixture to report.
     * @param string|null $competition_code Optional competition code.
     *
     * @return object|null
     */
    public function report_result( Fixture $fixture, ?string $competition_code = null ): ?object;

    /**
     * Report multiple fixture results.
     *
     * @param array $fixtures Fixtures collection.
     * @param string|null $competition_code Optional competition code.
     *
     * @return array
     */
    public function report_fixtures( array $fixtures, ?string $competition_code = null ): array;
}
