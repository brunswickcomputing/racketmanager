<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Event_Dropdown_Handler.
 */
final readonly class Event_Dropdown_Result {
    public function __construct(
        public array $leagues = [],
        public string $template = 'dropdown',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
