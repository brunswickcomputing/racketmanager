<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for the [club-competitions] shortcode.
 */
final readonly class Get_Club_Competitions_Query {
    public function __construct(
        public string $club_name = '',
        public ?string $competition_name = null,
        public string $template = ''
    ) {}
}
