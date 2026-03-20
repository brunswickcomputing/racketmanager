<?php

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Domain\Enums\Fixture_Reset_Status;

readonly class Fixture_Reset_Response {
    public function __construct(
        public int $fixture_id,
        public Fixture_Reset_Status $status
    ) {}
}
