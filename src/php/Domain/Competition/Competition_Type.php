<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

/**
 * Value object representing competition types.
 */
enum Competition_Type: string {
	case LEAGUE = 'league';
	case CUP = 'cup';
	case TOURNAMENT = 'tournament';

	/**
	 * Is this a league competition?
	 *
	 * @return bool
	 */
	public function is_league(): bool {
		return $this === self::LEAGUE;
	}

	/**
	 * Is this a cup competition?
	 *
	 * @return bool
	 */
	public function is_cup(): bool {
		return $this === self::CUP;
	}

	/**
	 * Is this a tournament competition?
	 *
	 * @return bool
	 */
	public function is_tournament(): bool {
		return $this === self::TOURNAMENT;
	}

	/**
	 * Does this type require team entry?
	 *
	 * @return bool
	 */
	public function is_team_entry(): bool {
		return $this === self::LEAGUE || $this === self::CUP;
	}

	/**
	 * Does this type require player entry?
	 *
	 * @return bool
	 */
	public function is_player_entry(): bool {
		return $this === self::TOURNAMENT;
	}
}
