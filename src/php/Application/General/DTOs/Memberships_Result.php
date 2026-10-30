<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Memberships_Handler.
 */
final readonly class Memberships_Result {
    /**
     * @param array<int, mixed> $clubs
     * @param array<int, mixed> $clubs_archive
     */
    public function __construct(
        public array $clubs = [],
        public array $clubs_archive = [],
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
