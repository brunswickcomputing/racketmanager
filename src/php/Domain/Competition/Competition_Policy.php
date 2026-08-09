<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Util\Util;

/**
 * Policy for Competition logic.
 */
class Competition_Policy {
	/**
	 * Generate finals rounds for a cup competition.
	 *
	 * @param int $max_rounds
	 * @return array
	 */
	public static function generate_cup_finals( int $max_rounds = 4 ): array {
		$finals = [];
		$r = $max_rounds;
		for ( $round = 1; $round <= $max_rounds; ++$round ) {
			$num_teams   = pow( 2, $round );
			$num_matches = $num_teams / 2;
			$key         = Util::get_final_key( $num_teams );
			$name        = Util::get_final_name( $key );
			$finals[ $key ] = [
				'key'         => $key,
				'name'        => $name,
				'num_matches' => $num_matches,
				'num_teams'   => $num_teams,
				'round'       => $r,
			];
			--$r;
		}
		return $finals;
	}
}
