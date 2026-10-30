<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Championship_Handler.
 */
final readonly class League_Championship_Result {
    /**
     * @param object|null $league
     * @param array<int|string, mixed> $finals Finals data array with matches
     */
    public function __construct(
        public ?object $league = null,
        public array $finals = [],
        public ?int $num_rubbers = null,
        public ?int $num_sets = null,
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
