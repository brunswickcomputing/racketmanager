<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Team_Order_Handler.
 */
final readonly class Team_Order_Result {
    /**
     * @param array<int, mixed> $clubs
     * @param array<int, mixed> $events
     * @param array<string, string> $event_types
     * @param array<string, string> $age_groups
     */
    public function __construct(
        public array $clubs = [],
        public array $events = [],
        public array $event_types = [],
        public array $age_groups = [],
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
