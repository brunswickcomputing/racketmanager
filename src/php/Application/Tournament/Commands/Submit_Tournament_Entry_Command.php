<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;

/**
 * Command DTO for submitting a tournament entry.
 */
readonly class Submit_Tournament_Entry_Command {
    /**
     * @param int|null $tournament_id
     * @param int|null $player_id
     * @param int|null $club_id
     * @param string|null $season
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $lta_number
     * @param float $competition_fee
     * @param float $paid_amt
     * @param float $total_cost
     * @param string|null $comments
     * @param bool $acceptance
     * @param array<int, mixed> $entries
     * @param int[] $missed_event_ids
     */
    public function __construct(
        public ?int $tournament_id,
        public ?int $player_id,
        public ?int $club_id = null,
        public ?string $season = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $lta_number = null,
        public float $competition_fee = 0.0,
        public float $paid_amt = 0.0,
        public float $total_cost = 0.0,
        public ?string $comments = null,
        public bool $acceptance = false,
        public array $entries = [],
        public array $missed_event_ids = []
    ) {
    }

    /**
     * Build command from POST / request array.
     *
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $dto = new Tournament_Entry_Request_DTO( $post );

        return new self(
            tournament_id: $dto->tournament_id > 0 ? $dto->tournament_id : null,
            player_id: $dto->player_id > 0 ? $dto->player_id : null,
            club_id: $dto->club_id > 0 ? $dto->club_id : null,
            season: $dto->season,
            email: $dto->email,
            phone: $dto->phone,
            lta_number: $dto->lta_number,
            competition_fee: $dto->competition_fee,
            paid_amt: $dto->paid_amt,
            total_cost: $dto->total_cost,
            comments: $dto->comments,
            acceptance: $dto->acceptance,
            entries: $dto->entries,
            missed_event_ids: $dto->missed_event_ids
        );
    }

    /**
     * Convert to Tournament_Entry_Request_DTO for downstream service consumption.
     *
     * @return Tournament_Entry_Request_DTO
     */
    public function to_request_dto(): Tournament_Entry_Request_DTO {
        return new Tournament_Entry_Request_DTO(
            tournament_id: (int) $this->tournament_id,
            player_id: (int) $this->player_id,
            club_id: (int) $this->club_id,
            season: (string) ( $this->season ?? '' ),
            email: (string) ( $this->email ?? '' ),
            phone: (string) ( $this->phone ?? '' ),
            lta_number: (string) ( $this->lta_number ?? '' ),
            competition_fee: $this->competition_fee,
            paid_amt: $this->paid_amt,
            total_cost: $this->total_cost,
            comments: (string) ( $this->comments ?? '' ),
            acceptance: $this->acceptance,
            entries: $this->entries,
            missed_event_ids: $this->missed_event_ids
        );
    }
}
