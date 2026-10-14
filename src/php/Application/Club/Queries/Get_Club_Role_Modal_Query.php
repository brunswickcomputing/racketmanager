<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for the [club-role-modal] shortcode.
 */
final readonly class Get_Club_Role_Modal_Query {
    public function __construct(
        public int $role_id = 0,
        public mixed $modal = null,
        public string $template = ''
    ) {}
}
