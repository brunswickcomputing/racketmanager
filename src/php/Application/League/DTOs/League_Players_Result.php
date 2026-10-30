<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Players_Handler.
 */
final readonly class League_Players_Result {
    /**
     * @param object|null $league
     * @param object|null $player
     * @param array<int, object> $raw_players
     */
    public function __construct(
        public ?object $league = null,
        public ?object $player = null,
        public array $raw_players = [],
        public mixed $season_name = null,
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
