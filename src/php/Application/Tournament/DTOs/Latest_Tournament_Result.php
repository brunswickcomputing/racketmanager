<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

use Racketmanager\Domain\Tournament\Tournament;

final readonly class Latest_Tournament_Result {
    public function __construct(
        public ?Tournament $tournament = null,
        public string $redirect_url = '',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
