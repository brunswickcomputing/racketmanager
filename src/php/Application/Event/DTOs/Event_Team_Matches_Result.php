<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Event_Team_Matches_Handler.
 */
final readonly class Event_Team_Matches_Result {
    public function __construct(
        public array $matches = [],
        public string $template = 'team-matches',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
