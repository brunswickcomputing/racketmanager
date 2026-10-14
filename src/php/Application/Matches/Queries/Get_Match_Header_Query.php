<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Query for the [match-header] shortcode.
 */
final readonly class Get_Match_Header_Query {
    public function __construct(
        public int $match_id = 0,
        public mixed $edit = false,
        public string $template = ''
    ) {}
}
