<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

/**
 * A division (league) to schedule, with its active teams.
 */
readonly class Schedule_Division_DTO {
    /**
     * @param int                 $league_id   League id.
     * @param string              $league_name League title.
     * @param Schedule_Team_DTO[] $teams       Teams in the division.
     */
    public function __construct(
        public int $league_id,
        public string $league_name,
        public array $teams = [],
    ) {
    }

    /**
     * Finds a schedule team by its identifier.
     *
     * @param int $team_id Team identifier to search for.
     *
     * @return Schedule_Team_DTO|null Matching team DTO, or null if no team is found.
     */
    public function find_team( int $team_id ): ?Schedule_Team_DTO {
        return array_find( $this->teams, fn( $team ) => $team->id === $team_id );
    }
}
