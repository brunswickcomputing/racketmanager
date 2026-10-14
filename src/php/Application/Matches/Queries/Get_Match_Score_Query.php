<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Query for the [score] shortcode.
 */
final readonly class Get_Match_Score_Query {
    public function __construct(
        public int $match_id = 0,
        public ?string $team_id = null,
        public ?string $opponent_id = null,
        public bool $home_away = false,
        public string $template = ''
    ) {}
}
