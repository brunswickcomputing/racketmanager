<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

/**
 * Result DTO returned by Get_Club_Players_Handler.
 */
final readonly class Club_Players_Result {
    /**
     * @param array<int, mixed> $raw_players
     * @param array<string, mixed> $display_opt
     */
    public function __construct(
        public ?object $club = null,
        public ?object $player = null,
        public ?object $club_player = null,
        public array $raw_players = [],
        public bool $user_can_update = false,
        public array $display_opt = [],
        public bool $use_wtn = false,
        public ?string $referrer = null,
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
