<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

/**
 * Output of the competition scheduler.
 *
 * `schedule` shape: `[ league_id => [ 'league_id' => int, 'league_name' => string,
 * 'weeks' => [ week => Scheduled_Match_DTO[] ] ] ]`.
 * `conflicts[]`: `[ 'week', 'division', 'court', 'home', 'with' ]`.
 * `alerts[]`: `[ 'type' => 'equity_compromise', 'team_name', 'division', 'count', 'ideal' ]`.
 * `resource_pairings[resource_id][]`: `[ 'id', 'name', 'division' ]`.
 */
readonly class Schedule_Result_DTO {
	/**
	 * @param int   $retry             Number of seeding attempts made.
	 * @param bool  $is_valid          True when a schedule without court conflicts was found.
	 * @param int   $total_weeks       Number of weeks in the schedule.
	 * @param array $schedule          Per-league weekly schedule.
	 * @param array $conflicts         Same-court-same-week conflicts of the best schedule.
	 * @param array $alerts            Home/away equity alerts.
	 * @param array $resource_pairings Teams grouped by physical court resource.
	 */
	public function __construct(
		public int $retry,
		public bool $is_valid,
		public int $total_weeks,
		public array $schedule = [],
		public array $conflicts = [],
		public array $alerts = [],
		public array $resource_pairings = [],
	) {
	}

	/**
	 * Matches for a league and week.
	 *
	 * @param int $league_id
	 * @param int $week
	 * @return Scheduled_Match_DTO[]
	 */
	public function matches( int $league_id, int $week ): array {
		return $this->schedule[ $league_id ]['weeks'][ $week ] ?? [];
	}

	/**
	 * Every non-bye match in the schedule, keyed in order of league, week and sorted position.
	 *
	 * @return array<int, array{league_id: int, week: int, match: Scheduled_Match_DTO}>
	 */
	public function fixtures(): array {
		$fixtures = [];
		foreach ( $this->schedule as $league_id => $data ) {
			foreach ( $data['weeks'] ?? [] as $week => $matches ) {
				foreach ( $matches as $match ) {
					if ( $match->is_bye ) {
						continue;
					}
					$fixtures[] = [
						'league_id' => (int) $league_id,
						'week'      => (int) $week,
						'match'     => $match,
					];
				}
			}
		}
		return $fixtures;
	}
}
