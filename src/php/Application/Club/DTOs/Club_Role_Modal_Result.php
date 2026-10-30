<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

/**
 * Result DTO returned by Get_Club_Role_Modal_Handler.
 */
final readonly class Club_Role_Modal_Result {
    /**
     * @param array<string, mixed> $club_roles
     */
    public function __construct(
        public ?object $club_role = null,
        public mixed $modal = null,
        public array $club_roles = [],
        public string $template = '',
        public ?string $error_message = null,
        public ?string $error_variant = null
    ) {}

    public static function error( string $message, ?string $error_variant = 'modal' ): self {
        return new self( error_message: $message, error_variant: $error_variant );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
