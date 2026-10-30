<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Player\Player;

/**
 * Result DTO for Get_Competition_Players_Handler.
 */
final readonly class Competition_Players_Result {
    public function __construct(
        public ?Competition $competition = null,
        public ?Player $player = null,
        public array $players = [],
        public array $raw_matches = [],
        public string $template = 'players',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
