<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;

/**
 * Command DTO for validating/submitting a tournament partner selection.
 */
readonly class Submit_Tournament_Partner_Command {
    /**
     * @param int|null $player_id
     * @param int|null $partner_id
     * @param int|null $tournament_id
     * @param int|null $event_id
     * @param string|null $modal
     * @param string|null $partner_name
     */
    public function __construct(
        public ?int $player_id,
        public ?int $partner_id,
        public ?int $tournament_id,
        public ?int $event_id,
        public ?string $modal = null,
        public ?string $partner_name = null
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $dto = new Tournament_Partner_Request_DTO( $post );

        return new self(
            player_id: $dto->player_id,
            partner_id: $dto->partner_id,
            tournament_id: $dto->tournament_id,
            event_id: $dto->event_id,
            modal: $dto->modal,
            partner_name: $dto->partner_name
        );
    }

    /**
     * Convert to Tournament_Partner_Request_DTO for downstream service consumption.
     *
     * @return Tournament_Partner_Request_DTO
     */
    public function to_request_dto(): Tournament_Partner_Request_DTO {
        return new Tournament_Partner_Request_DTO(
            player_id: $this->player_id,
            partner_id: $this->partner_id,
            tournament_id: $this->tournament_id,
            event_id: $this->event_id,
            modal: $this->modal,
            partner_name: $this->partner_name
        );
    }
}
