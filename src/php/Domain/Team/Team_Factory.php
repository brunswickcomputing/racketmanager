<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Team;

use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;

/**
 * Factory for creating Team entities from hydration DTOs, database objects, or arrays.
 */
final class Team_Factory {
	/**
	 * Create a Team entity from a hydration DTO.
	 *
	 * @param Team_Hydration_DTO $dto
	 * @return Team
	 */
	public static function create_from_dto( Team_Hydration_DTO $dto ): Team {
		return new Team( $dto );
	}

	/**
	 * Create a Team entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Team
	 */
	public static function from_object( object $row ): Team {
		$dto = Team_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}

	/**
	 * Create a Team entity from an array.
	 *
	 * @param array $data
	 * @return Team
	 */
	public static function from_array( array $data ): Team {
		$dto = Team_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto );
	}
}
