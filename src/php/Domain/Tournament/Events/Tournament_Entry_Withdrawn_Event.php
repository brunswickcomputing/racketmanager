<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament\Events;

use DateTimeImmutable;
use Racketmanager\Domain\Events\Abstract_Domain_Event;

/**
 * Dispatched when a tournament entry is withdrawn.
 */
final readonly class Tournament_Entry_Withdrawn_Event extends Abstract_Domain_Event {
    /**
     * @param int                    $entry_id      Entry identifier.
     * @param int                    $tournament_id Tournament identifier.
     * @param int                    $player_id     Player identifier.
     * @param string|null            $reason        Reason or status description for withdrawal.
     * @param DateTimeImmutable|null $occurred_on   Timestamp when the event occurred.
     */
    public function __construct(
        public int $entry_id,
        public int $tournament_id,
        public int $player_id,
        public ?string $reason = null,
        ?DateTimeImmutable $occurred_on = null,
    ) {
        parent::__construct( $occurred_on );
    }
}
