<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Ports;

/**
 * Port interface for League Schedule operations.
 */
interface League_Schedule_Service_Interface {
    /**
     * Schedule all leagues in a box league event.
     *
     * @param object $event Event object.
     * @return array{scheduled: int, skipped: int}
     */
    public function generate_box_league_matches( object $event ): array;
}
