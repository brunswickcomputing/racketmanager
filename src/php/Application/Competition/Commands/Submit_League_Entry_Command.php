<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Commands;

use Racketmanager\Infrastructure\Wordpress\Request\Competition\League_Entry_Request_DTO;

/**
 * Command DTO for submitting a league competition entry.
 */
readonly class Submit_League_Entry_Command {
    /**
     * @param int|null $competition_id
     * @param string|null $season
     * @param int|null $club_id
     * @param string|null $comments
     * @param bool $acceptance
     * @param int|null $num_courts_available
     * @param array<int, mixed> $events_entered
     * @param int[] $missed_event_ids
     * @param array<int, int[]> $missed_team_ids
     * @param int[] $empty_event_ids
     */
    public function __construct(
        public ?int $competition_id,
        public ?string $season,
        public ?int $club_id,
        public ?string $comments,
        public bool $acceptance,
        public ?int $num_courts_available = null,
        public array $events_entered = [],
        public array $missed_event_ids = [],
        public array $missed_team_ids = [],
        public array $empty_event_ids = []
    ) {
    }

    /**
     * Build command from POST / request array.
     *
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $dto = new League_Entry_Request_DTO( $post );

        return new self(
            competition_id: $dto->competition_id,
            season: $dto->season,
            club_id: $dto->club_id,
            comments: $dto->comments,
            acceptance: $dto->acceptance,
            num_courts_available: $dto->num_courts_available,
            events_entered: $dto->events_entered,
            missed_event_ids: $dto->missed_event_ids,
            missed_team_ids: $dto->missed_team_ids,
            empty_event_ids: $dto->empty_event_ids
        );
    }

    /**
     * Convert to League_Entry_Request_DTO for downstream service consumption.
     *
     * @return League_Entry_Request_DTO
     */
    public function to_request_dto(): League_Entry_Request_DTO {
        return new League_Entry_Request_DTO(
            competition_id: $this->competition_id,
            season: $this->season,
            club_id: $this->club_id,
            comments: $this->comments,
            acceptance: $this->acceptance,
            num_courts_available: $this->num_courts_available,
            events_entered: $this->events_entered,
            missed_event_ids: $this->missed_event_ids,
            missed_team_ids: $this->missed_team_ids,
            empty_event_ids: $this->empty_event_ids
        );
    }
}
