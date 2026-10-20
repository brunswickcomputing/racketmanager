<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Events;

use DateTimeImmutable;

/**
 * Contract for domain events across all bounded contexts.
 */
interface Domain_Event_Interface {
    /**
     * Get the exact timestamp when this domain event occurred.
     *
     * @return DateTimeImmutable
     */
    public function get_occurred_on(): DateTimeImmutable;

    /**
     * Get the unique event identifier or name.
     *
     * @return string
     */
    public function get_event_name(): string;
}
