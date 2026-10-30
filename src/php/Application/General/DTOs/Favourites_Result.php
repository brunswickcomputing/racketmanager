<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Favourites_Handler.
 */
final readonly class Favourites_Result {
    /**
     * @param array<int, array<string, mixed>> $favourite_types
     */
    public function __construct(
        public array $favourite_types = [],
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
