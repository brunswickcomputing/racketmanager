<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Commands;

use Racketmanager\Domain\DTO\Competition\Cup_Entry_Request_DTO;

/**
 * Command DTO for submitting a cup competition entry.
 */
readonly class Submit_Cup_Entry_Command {
    /**
     * @param int|null $competition_id
     * @param string|null $season
     * @param int|null $club_id
     * @param bool $acceptance
     * @param string|null $comments
     * @param array<int, mixed> $events_entered
     */
    public function __construct(
        public ?int $competition_id,
        public ?string $season,
        public ?int $club_id,
        public bool $acceptance,
        public ?string $comments,
        public array $events_entered = []
    ) {
    }

    /**
     * Build command from POST / request array.
     *
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $dto = new Cup_Entry_Request_DTO( $post );

        return new self(
            competition_id: $dto->competition_id,
            season: $dto->season,
            club_id: $dto->club_id,
            acceptance: $dto->acceptance,
            comments: $dto->comments,
            events_entered: $dto->events_entered
        );
    }

    /**
     * Convert to Cup_Entry_Request_DTO for downstream service consumption.
     *
     * @return Cup_Entry_Request_DTO
     */
    public function to_request_dto(): Cup_Entry_Request_DTO {
        return new Cup_Entry_Request_DTO(
            competition_id: $this->competition_id,
            season: $this->season,
            club_id: $this->club_id,
            acceptance: $this->acceptance,
            comments: $this->comments,
            events_entered: $this->events_entered
        );
    }
}
