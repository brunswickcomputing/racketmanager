<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Team_Order_Players_Handler.
 */
final readonly class Team_Order_Players_Result {
    /**
     * @var array<string, mixed>
     */
    public array $vars;

    public function __construct(
        public ?object $event = null,
        public ?object $club = null,
        public array $teams = [],
        public array $matches = [],
        public array $club_players = [],
        public bool $can_update = false,
        public string $template = 'team-players-list',
        public ?string $error_message = null
    ) {
        $this->vars = [
            'event'        => $this->event,
            'club'         => $this->club,
            'teams'        => $this->teams,
            'matches'      => $this->matches,
            'club_players' => $this->club_players,
            'can_update'   => $this->can_update,
        ];
    }

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
