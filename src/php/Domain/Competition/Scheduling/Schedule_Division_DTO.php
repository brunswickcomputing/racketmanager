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
	 * Find a team by id.
	 *
	 * @param int $team_id
	 * @return Schedule_Team_DTO|null
	 */
	public function find_team( int $team_id ): ?Schedule_Team_DTO {
		foreach ( $this->teams as $team ) {
			if ( $team->id === $team_id ) {
				return $team;
			}
		}
		return null;
	}
}
