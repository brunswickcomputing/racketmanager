<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Player_Search_Handler.
 */
final readonly class Player_Search_Result {
    /**
     * @param array<int, mixed> $players
     */
    public function __construct(
        public array $players = [],
        public ?string $search = null,
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
