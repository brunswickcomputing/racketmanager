<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

/**
 * Result DTO for Get_Competitions_Handler.
 */
final readonly class Competitions_List_Result {
    public function __construct(
        public array $competitions = [],
        public array $user_competitions = [],
        public string $type = '',
        public string $competition_type = '',
        public string $template = 'competitions',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
