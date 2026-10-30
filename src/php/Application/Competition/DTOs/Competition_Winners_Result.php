<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Competition\Competition;

/**
 * Result DTO for Get_Competition_Winners_Handler.
 */
final readonly class Competition_Winners_Result {
    public function __construct(
        public ?Competition $competition = null,
        public array $raw_winners = [],
        public string $template = 'winners',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
