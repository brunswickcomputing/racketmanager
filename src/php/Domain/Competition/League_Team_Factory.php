<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\League_Team_Hydration_DTO;

/**
 * Factory for creating and hydrating League_Team entities.
 */
final class League_Team_Factory {
	/**
	 * Create a League_Team entity from a hydration DTO.
	 *
	 * @param League_Team_Hydration_DTO $dto
	 * @return League_Team
	 */
	public static function create_from_dto( League_Team_Hydration_DTO $dto ): League_Team {
		return new League_Team( $dto );
	}

	/**
	 * Create a League_Team entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return League_Team
	 */
	public static function from_object( object $row ): League_Team {
		return self::create_from_dto( League_Team_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a League_Team entity from an associative array.
	 *
	 * @param array $data
	 * @return League_Team
	 */
	public static function from_array( array $data ): League_Team {
		return self::create_from_dto( League_Team_Hydration_DTO::from_array( $data ) );
	}
}
