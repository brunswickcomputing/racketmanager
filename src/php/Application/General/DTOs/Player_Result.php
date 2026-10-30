<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Player_Handler.
 */
final readonly class Player_Result {
    /**
     * @param array<int, mixed> $clubs
     * @param array<int, mixed> $titles
     * @param array<string, mixed> $player_competitions
     */
    public function __construct(
        public ?object $player = null,
        public array $clubs = [],
        public array $titles = [],
        public array $player_competitions = [],
        public ?string $error_message = null,
        public ?string $template = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
