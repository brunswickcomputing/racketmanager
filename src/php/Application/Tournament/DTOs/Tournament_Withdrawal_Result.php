<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Withdrawal_Result {
    /**
     * @param array<int|string, mixed> $events_entered
     */
    public function __construct(
        public mixed $tournament_id = null,
        public mixed $player_id = null,
        public mixed $modal = null,
        public ?string $msg = null,
        public array $events_entered = [],
        public ?string $error_message = null,
        public string $error_type = 'modal'
    ) {}

    public static function error( string $message, string $error_type = 'modal' ): self {
        return new self( error_message: $message, error_type: $error_type );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
