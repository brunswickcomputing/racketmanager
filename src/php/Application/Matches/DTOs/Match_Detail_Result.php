<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\DTOs;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;

/**
 * Read model returned by the [match-detail] query handler.
 */
final readonly class Match_Detail_Result {
    /**
     * @param Fixture_Details_DTO|null $details       Resolved fixture details; null on error.
     * @param string|null              $error_message Error to show instead of the detail.
     * @param object|null              $match_player  Player the rubbers were filtered for, if any.
     * @param string                   $round         Final round name, or '' for league fixtures.
     * @param string                   $type          'league' or 'tournament'.
     * @param string                   $template      Template suffix.
     */
    public function __construct(
        public ?Fixture_Details_DTO $details = null,
        public ?string $error_message = null,
        public ?object $match_player = null,
        public string $round = '',
        public string $type = 'league',
        public string $template = ''
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }
}
