<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\DTOs;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;

/**
 * Read model returned by the [score] query handler.
 */
final readonly class Match_Score_Result {
    /**
     * @param Fixture_Details_DTO|null $details       Resolved fixture details; null on error.
     * @param string|null              $error_message Error to show instead of a score.
     * @param bool                     $played        Whether any points have been recorded.
     * @param string|null              $score_class   'winner', 'loser', 'tie' or null.
     * @param mixed                    $score_team_1  Points for the requested team.
     * @param mixed                    $score_team_2  Points for the opponent.
     * @param bool                     $home_away     Whether the home/away (match day) decoration was requested.
     * @param string                   $template      Template suffix.
     */
    public function __construct(
        public ?Fixture_Details_DTO $details = null,
        public ?string $error_message = null,
        public bool $played = false,
        public ?string $score_class = null,
        public mixed $score_team_1 = null,
        public mixed $score_team_2 = null,
        public bool $home_away = false,
        public string $template = ''
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }
}
