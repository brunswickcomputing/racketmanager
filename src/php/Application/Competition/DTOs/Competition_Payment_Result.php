<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Services\Stripe_Settings;

/**
 * Result DTO for Get_Competition_Payment_Handler.
 */
final readonly class Competition_Payment_Result {
    public function __construct(
        public ?Tournament $tournament = null,
        public ?Player $player = null,
        public ?object $tournament_entry = null,
        public ?float $total_due = null,
        public ?int $invoice_id = null,
        public ?Stripe_Settings $stripe = null,
        public bool $complete = false,
        public string $template = 'tournament-payment',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
