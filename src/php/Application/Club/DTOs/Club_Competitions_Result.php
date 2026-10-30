<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

/**
 * Result DTO returned by Get_Club_Competitions_Handler.
 */
final readonly class Club_Competitions_Result {
    /**
     * @param array<int, array{competition: object, events: array<int, array{event: object, teams: array<int, mixed>}>}> $competitions_data
     */
    public function __construct(
        public ?object $club = null,
        public array $competitions_data = [],
        public bool $user_can_update = false,
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
