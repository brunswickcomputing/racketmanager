<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Event_Standings_Handler.
 */
final readonly class Event_Standings_Result {
    public function __construct(
        public ?object $event = null,
        public array $leagues = [],
        public string $template = 'standings',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
