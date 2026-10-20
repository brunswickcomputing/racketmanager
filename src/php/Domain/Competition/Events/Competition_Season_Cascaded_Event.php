<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Events;

use DateTimeImmutable;
use Racketmanager\Domain\Events\Abstract_Domain_Event;

/**
 * Dispatched when a competition season has been cascaded to events.
 */
final readonly class Competition_Season_Cascaded_Event extends Abstract_Domain_Event {
    public function __construct(
        public int $competition_id,
        public string $season_name,
        ?DateTimeImmutable $occurred_on = null,
    ) {
        parent::__construct( $occurred_on );
    }
}
