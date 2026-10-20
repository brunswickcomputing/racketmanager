<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Club\Events;

use DateTimeImmutable;
use Racketmanager\Domain\Events\Abstract_Domain_Event;

/**
 * Dispatched when a player is registered or added to a club.
 */
final readonly class Club_Player_Registered_Event extends Abstract_Domain_Event {
    public function __construct(
        public int $club_id,
        public int $player_id,
        public ?int $registered_by_user_id = null,
        public string $status = 'pending',
        ?DateTimeImmutable $occurred_on = null,
    ) {
        parent::__construct( $occurred_on );
    }
}
