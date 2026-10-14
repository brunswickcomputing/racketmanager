<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Query for the [match-detail] shortcode.
 */
final readonly class Get_Match_Detail_Query {
    public function __construct(
        public int $match_id = 0,
        public ?int $player_id = null,
        public string $template = ''
    ) {}
}
