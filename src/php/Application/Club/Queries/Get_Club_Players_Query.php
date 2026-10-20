<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for the [club-players] shortcode.
 */
final readonly class Get_Club_Players_Query {
    public function __construct(
        public string $club_name = '',
        public ?string $player_name = null,
        public string $template = '',
        public ?string $referrer = null
    ) {}
}
