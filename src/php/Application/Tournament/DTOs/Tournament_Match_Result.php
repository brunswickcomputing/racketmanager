<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Match_Result {
    public function __construct(
        public ?object $tournament = null,
        public ?object $fixture = null,
        public mixed $is_update_allowed = null,
        public ?string $referrer = null,
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
