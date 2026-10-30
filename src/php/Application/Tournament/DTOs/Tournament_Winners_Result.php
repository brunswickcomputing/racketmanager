<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Winners_Result {
    /**
     * @param array<string, mixed> $winners
     */
    public function __construct(
        public ?object $tournament = null,
        public array $winners = [],
        public string $tab = 'winners',
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
