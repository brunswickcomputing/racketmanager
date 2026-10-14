<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for the [club-roles] shortcode.
 */
final readonly class Get_Club_Roles_Query {
    public function __construct(
        public string $club_name = '',
        public string $template = ''
    ) {}
}
