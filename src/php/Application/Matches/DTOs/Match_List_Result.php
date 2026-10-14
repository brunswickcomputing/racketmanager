<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\DTOs;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Read model returned by match listing query handlers.
 */
final readonly class Match_List_Result {
    /**
     * @param Fixture[]   $fixtures   Fixtures in display order.
     * @param string|null $match_date Effective match date for date-based listings.
     */
    public function __construct(
        public array $fixtures = [],
        public ?string $match_date = null
    ) {}
}
