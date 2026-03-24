<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

/**
 * Data Transfer Object for fixture result update response.
 */
readonly class Fixture_Update_Response {
    /**
     * @param Fixture_Update_Status[] $outcomes
     */
    public function __construct(
        public array $outcomes
    ) {
    }

    public function has_outcome( Fixture_Update_Status $status ): bool {
        return in_array( $status, $this->outcomes, true );
    }
}
