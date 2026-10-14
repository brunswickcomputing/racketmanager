<?php
/**
 * REST matches query DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Rest
 */

declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rest;

/**
 * Typed query DTO for REST fixtures and results endpoints.
 */
final readonly class Rest_Matches_Query {

    public function __construct(
        public ?string $club = null,
        public ?string $competition = null,
        public ?string $event = null,
        public ?string $league = null,
        public ?string $season = null,
        public ?bool $home = null,
        public ?int $days = null,
    ) {
    }
}
