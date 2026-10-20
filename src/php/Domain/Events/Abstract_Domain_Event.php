<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Events;

use DateTimeImmutable;

/**
 * Base abstract class for domain events providing timestamp handling.
 */
abstract readonly class Abstract_Domain_Event implements Domain_Event_Interface {
    protected DateTimeImmutable $occurred_on;

    public function __construct( ?DateTimeImmutable $occurred_on = null ) {
        $this->occurred_on = $occurred_on ?? new DateTimeImmutable();
    }

    public function get_occurred_on(): DateTimeImmutable {
        return $this->occurred_on;
    }

    public function get_event_name(): string {
        return static::class;
    }
}
