<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Season_Dropdown_Handler.
 */
final readonly class League_Season_Dropdown_Result {
    public function __construct(
        public ?object $league = null,
        public ?string $season = null,
        public string $template = '',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
