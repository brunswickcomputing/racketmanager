<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Racketmanager\Domain\Ports\Clock_Interface;

/**
 * Deterministic Clock_Interface double for unit tests.
 */
final class Fixed_Clock implements Clock_Interface {
    private DateTimeImmutable $now;

    public function __construct( string $now = '2026-03-01 12:34:56', string $timezone = 'Europe/London' ) {
        $this->now = new DateTimeImmutable( $now, new DateTimeZone( $timezone ) );
    }

    public function now(): DateTimeImmutable {
        return $this->now;
    }
}
