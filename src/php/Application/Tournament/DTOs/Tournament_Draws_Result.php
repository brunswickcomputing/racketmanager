<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Draws_Result {
    /**
     * @param array<int|string, mixed> $draws
     */
    public function __construct(
        public ?object $tournament = null,
        public mixed $draw = null,
        public array $draws = [],
        public string $tab = 'draws',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
