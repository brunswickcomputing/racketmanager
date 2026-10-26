<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Time;

use DateTimeImmutable;
use DateTimeZone;
use Racketmanager\Domain\Ports\Clock_Interface;

/**
 * Clock backed by WordPress site-local time.
 *
 * `now()->format( 'Y-m-d H:i:s' )` is byte-identical to `current_time( 'mysql' )`, which is what the
 * legacy entities persisted, because both read the site timezone from `wp_timezone()`.
 */
final class Wordpress_Clock implements Clock_Interface {

    public function now(): DateTimeImmutable {
        return new DateTimeImmutable( current_time( 'mysql' ), $this->timezone() );
    }

    private function timezone(): ?DateTimeZone {
        if ( function_exists( 'wp_timezone' ) ) {
            return wp_timezone();
        }
        return null;
    }
}
