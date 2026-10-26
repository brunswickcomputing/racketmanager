<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

/**
 * A team taking part in the competition schedule.
 *
 * `preferred_slot` is the court slot the team plays its home matches in
 * (normally `match_day . ' ' . match_time`); teams of one club sharing the same
 * slot compete for the same physical courts.
 */
readonly class Schedule_Team_DTO {
	/**
	 * @param int         $id             Team id.
	 * @param string      $name           Team title.
	 * @param int         $club_id        Club id.
	 * @param string      $match_day      Home match day name (e.g. "Monday").
	 * @param string      $match_time     Home match time (e.g. "18:30").
	 * @param string      $location       Home location (club shortcode).
	 * @param string      $preferred_slot Court slot key, defaults to `match_day match_time`.
	 * @param int         $event_id       Event the team's division belongs to.
	 * @param string|null $match_day_date Optional fixed match date overriding the week calculation.
	 */
	public function __construct(
		public int $id,
		public string $name,
		public int $club_id,
		public string $match_day = '',
		public string $match_time = '',
		public string $location = '',
		public string $preferred_slot = '',
		public int $event_id = 0,
		public ?string $match_day_date = null,
	) {
	}

	/**
	 * Resolve the slot key, falling back to `match_day match_time`.
	 *
	 * @return string
	 */
	public function slot(): string {
		if ( '' !== $this->preferred_slot ) {
			return $this->preferred_slot;
		}
		return trim( $this->match_day . ' ' . $this->match_time );
	}
}
