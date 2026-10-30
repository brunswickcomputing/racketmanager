<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Competition\Competition;

/**
 * Result DTO for Get_Competition_Teams_Handler.
 */
final readonly class Competition_Teams_Result {
    public function __construct(
        public ?Competition $competition = null,
        public array $teams = [],
        public string $template = 'teams',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
