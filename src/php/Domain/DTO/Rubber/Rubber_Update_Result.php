<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rubber;

/**
 * Data Transfer Object for a rubber update response.
 */
readonly class Rubber_Update_Result {
    public function __construct(
        public int $rubber_id,
        public float $home_points,
        public float $away_points,
        public ?int $winner_id,
        public array $players,
        public array $sets,
        public ?int $status,
        public array $custom,
        public array $stats
    ) {
    }
}
