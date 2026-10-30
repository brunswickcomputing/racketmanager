<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

/**
 * Result DTO returned by Get_Club_Handler.
 */
final readonly class Club_Overview_Result {
    public function __construct(
        public ?object $club = null,
        public object|array|null $club_details = null,
        public string $google_maps_key = '',
        public bool $user_can_update_club = false,
        public string $template = 'club',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
