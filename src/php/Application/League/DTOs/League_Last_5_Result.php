<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Last_5_Handler.
 */
final readonly class League_Last_5_Result {
    /**
     * @param object|null $league
     * @param array<int, mixed> $matches
     * @param array<string, string> $team_titles
     */
    public function __construct(
        public ?object $league = null,
        public array $matches = [],
        public ?int $team_id = null,
        public array $team_titles = [],
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
