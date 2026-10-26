<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

/**
 * Input to the competition scheduler.
 */
readonly class Schedule_Request_DTO {
	/**
	 * @param Schedule_Division_DTO[] $divisions          Divisions to schedule together.
	 * @param int                     $teams_per_division Maximum teams per division (number of rounds + 1).
	 * @param bool                    $is_home_away       Whether every pairing is played twice (home and away).
	 * @param string[]                $match_dates        Week start dates (`Y-m-d`) indexed from week 1 at offset 0.
	 */
	public function __construct(
		public array $divisions,
		public int $teams_per_division = 6,
		public bool $is_home_away = false,
		public array $match_dates = [],
	) {
	}

	/**
	 * Total number of weeks in the schedule.
	 *
	 * @return int
	 */
	public function total_weeks(): int {
		return $this->is_home_away ? ( $this->teams_per_division - 1 ) * 2 : $this->teams_per_division - 1;
	}
}
