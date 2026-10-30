<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Teams_Handler.
 */
final readonly class League_Teams_Result {
    /**
     * @param object|null $league
     * @param object|null $team
     * @param object|null $team_details
     * @param array<int|string, mixed> $raw_matches
     * @param array<int, object> $league_teams
     */
    public function __construct(
        public ?object $league = null,
        public bool $is_team_details = false,
        public ?object $team = null,
        public ?object $team_details = null,
        public array $raw_matches = [],
        public bool $user_can_edit_team = false,
        public array $league_teams = [],
        public string $item_link = '',
        public ?int $team_id = null,
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
