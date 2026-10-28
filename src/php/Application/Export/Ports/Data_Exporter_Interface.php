<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Export\Ports;

use Racketmanager\Services\Export\DTO\Export_Criteria;

/**
 * Port contract for data export delivery (calendar, fixtures, results, and tournament reports).
 */
interface Data_Exporter_Interface {
    /**
     * Export calendar events in iCalendar format.
     */
    public function calendar( Export_Criteria $criteria ): string;

    /**
     * Export match results.
     */
    public function results( Export_Criteria $criteria ): string;

    /**
     * Export fixture schedule.
     */
    public function fixtures( Export_Criteria $criteria ): string;

    /**
     * Export full tournament report results in CSV format.
     */
    public function report_results( Export_Criteria $criteria ): string;
}
