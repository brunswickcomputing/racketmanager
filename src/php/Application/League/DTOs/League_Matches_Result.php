<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Matches_Handler.
 */
final readonly class League_Matches_Result {
    /**
     * @param object|null $league
     * @param array<int|string, mixed> $raw_matches
     * @param array<int|string, mixed> $grouped_matches
     * @param array<int, object> $teams
     */
    public function __construct(
        public ?object $league = null,
        public array $raw_matches = [],
        public bool $is_team_entry = true,
        public bool $is_grouped = false,
        public array $grouped_matches = [],
        public array $teams = [],
        public mixed $season = null,
        public int $post_id = 0,
        public string $form_action = '',
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
