<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Players_Handler.
 */
final readonly class Players_Result {
    /**
     * @param array<int, mixed> $players
     * @param array<int, mixed> $favourites
     */
    public function __construct(
        public array $players = [],
        public ?string $search_string = null,
        public array $favourites = [],
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
