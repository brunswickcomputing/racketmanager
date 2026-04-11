<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTOs;

/**
 * DTO for Result Checker data
 */
readonly class Results_Checker_Data {
    /**
     * @param int|null    $id           ID.
     * @param int|null    $league_id    League ID.
     * @param int|null    $match_id     Match ID.
     * @param int|null    $team_id      Team ID.
     * @param int|null    $player_id    Player ID.
     * @param int|null    $rubber_id    Rubber ID.
     * @param string|null $description  Description.
     * @param int|null    $status       Status.
     * @param int|null    $updated_user Updated user.
     * @param string|null $updated_date Updated date.
     */
    public function __construct(
        public ?int $id = null,
        public ?int $league_id = null,
        public ?int $match_id = null,
        public ?int $team_id = null,
        public ?int $player_id = null,
        public ?int $rubber_id = null,
        public ?string $description = null,
        public ?int $status = null,
        public ?int $updated_user = null,
        public ?string $updated_date = null,
    ) {}
}
