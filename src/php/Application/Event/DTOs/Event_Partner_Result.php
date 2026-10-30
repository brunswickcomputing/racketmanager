<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Event_Partner_Handler.
 */
final readonly class Event_Partner_Result {
    public function __construct(
        public ?object $event = null,
        public ?int $player_id = null,
        public ?string $partner_name = null,
        public ?string $partner_btm = null,
        public ?int $partner_id = null,
        public string $partner_gender = 'M',
        public ?string $date_end = null,
        public ?string $season = null,
        public ?string $modal = null,
        public ?int $tournament_id = null,
        public string $template = 'partner-modal',
        public ?string $error_message = null,
        public string $template_type = 'event'
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
