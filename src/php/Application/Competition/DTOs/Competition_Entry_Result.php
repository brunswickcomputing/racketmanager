<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;

/**
 * Result DTO for Get_Competition_Entry_Handler.
 */
final readonly class Competition_Entry_Result {
    public function __construct(
        public string $type = '', // 'tournament', 'cup', 'league', 'clubs_list'
        public ?Tournament $tournament = null,
        public ?Player $player = null,
        public ?object $competition = null,
        public ?object $club = null,
        public array $clubs = [],
        public string $season = '',
        public array $competition_season = [],
        public array $events = [],
        public array $ladies_teams = [],
        public array $mens_teams = [],
        public array $mixed_teams = [],
        public array $match_days = [],
        public ?string $date_closing = null,
        public array $player_entries = [],
        public array $club_memberships = [],
        public ?object $tournament_entry = null,
        public int $user_id = 0,
        public bool $can_manage = false,
        public string $template = '',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
