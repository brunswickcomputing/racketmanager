<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

/**
 * Result DTO returned by Get_Club_Roles_Handler.
 */
final readonly class Club_Roles_Result {
    /**
     * @param array<int, mixed> $roles
     */
    public function __construct(
        public ?object $club = null,
        public array $roles = [],
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
