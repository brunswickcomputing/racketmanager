<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture\Events;

use DateTimeImmutable;
use Racketmanager\Domain\Events\Abstract_Domain_Event;

/**
 * Dispatched when a fixture result has been finalized.
 */
final readonly class Fixture_Result_Finalized_Event extends Abstract_Domain_Event {
    public function __construct(
        public int $fixture_id,
        public ?int $league_id,
        public string $season,
        public ?string $competition_type = null,
        ?DateTimeImmutable $occurred_on = null,
    ) {
        parent::__construct( $occurred_on );
    }
}
