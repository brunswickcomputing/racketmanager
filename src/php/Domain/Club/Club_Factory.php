<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Club;

use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;

/**
 * Factory for creating Club entities from hydration DTOs, database objects, or arrays.
 */
final class Club_Factory {
	/**
	 * Create a Club entity from a hydration DTO.
	 *
	 * @param Club_Hydration_DTO $dto
	 * @return Club
	 */
	public static function create_from_dto( Club_Hydration_DTO $dto ): Club {
		return new Club( $dto );
	}

	/**
	 * Create a Club entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Club
	 */
	public static function from_object( object $row ): Club {
		$dto = Club_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}

	/**
	 * Create a Club entity from an array.
	 *
	 * @param array $data
	 * @return Club
	 */
	public static function from_array( array $data ): Club {
		$dto = Club_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto );
	}
}
