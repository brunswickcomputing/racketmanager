<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rubber;

/**
 * Data Transfer Object for a single rubber update request.
 */
readonly class Rubber_Update_Request {
    public function __construct(
        public int $rubber_id,
        public ?string $rubber_type,
        public int $rubber_number,
        public array $players,
        public array $sets,
        public ?string $rubber_status,
        public bool $is_playoff = false,
        public bool $is_withdrawn = false,
        public bool $is_cancelled = false
    ) {
    }
}
