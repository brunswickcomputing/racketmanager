<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\League_Repository;

/**
 * Internal DTO for applying confirmation to a fixture.
 */
readonly class Fixture_Confirmation_Context {
    public function __construct(
        public string $status,
        public string $actioned_by,
        public ?string $confirm_comments,
        public Result $result,
        public bool $update_standings = false,
        public bool $run_checks = false,
        public ?League_Repository $league_repository = null
    ) {
    }
}
