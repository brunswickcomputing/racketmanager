<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

/**
 * Result DTO for Get_Competition_Dropdown_Handler.
 */
final readonly class Competition_Dropdown_Result {
    public function __construct(
        public array $events = [],
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
