<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Standings_Handler.
 */
final readonly class League_Standings_Result {
    /**
     * @param object|null $league
     * @param array<int, object> $teams
     */
    public function __construct(
        public ?object $league = null,
        public array $teams = [],
        public string $template = '',
        public bool $widget = false,
        public ?string $season = null,
        public ?string $group = null,
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
