<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Match_Dropdown_Handler.
 */
final readonly class League_Match_Dropdown_Result {
    /**
     * @param object|null $league
     * @param array<int, mixed> $matches
     */
    public function __construct(
        public ?object $league = null,
        public array $matches = [],
        public mixed $match_id = null,
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
