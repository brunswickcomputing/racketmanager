<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Ports;

use DateTimeImmutable;

/**
 * Domain Port for the current time.
 *
 * Entities receive the timestamp explicitly (e.g. `Club_Player::approve( $user_id, $clock->now() )`)
 * so they stay deterministic; Infrastructure supplies the WordPress site-local time.
 */
interface Clock_Interface {
    /**
     * Current time in the site's configured timezone.
     *
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable;
}
