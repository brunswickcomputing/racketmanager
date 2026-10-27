<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Club;

use Racketmanager\Domain\DTO\Club\Club_Player_Hydration_DTO;

/**
 * Factory for creating Club_Player entities from hydration DTOs, database rows, or arrays.
 */
final class Club_Player_Factory {
	/**
	 * Create a Club_Player entity from a hydration DTO.
	 *
	 * @param Club_Player_Hydration_DTO $dto
	 * @return Club_Player
	 */
	public static function create_from_dto( Club_Player_Hydration_DTO $dto ): Club_Player {
		return new Club_Player( $dto );
	}

	/**
	 * Create a Club_Player entity from a generic object (e.g. a club player registration row).
	 *
	 * @param object $row
	 * @return Club_Player
	 */
	public static function from_object( object $row ): Club_Player {
		return self::create_from_dto( Club_Player_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Club_Player entity from an associative array.
	 *
	 * @param array $data
	 * @return Club_Player
	 */
	public static function from_array( array $data ): Club_Player {
		return self::create_from_dto( Club_Player_Hydration_DTO::from_array( $data ) );
	}
}
