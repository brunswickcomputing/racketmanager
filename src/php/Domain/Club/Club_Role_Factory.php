<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Club;

use Racketmanager\Domain\DTO\Club\Club_Role_Hydration_DTO;

/**
 * Factory for creating Club_Role entities from hydration DTOs, database rows, or arrays.
 */
final class Club_Role_Factory {
	/**
	 * Create a Club_Role entity from a hydration DTO.
	 *
	 * @param Club_Role_Hydration_DTO $dto
	 * @return Club_Role
	 */
	public static function create_from_dto( Club_Role_Hydration_DTO $dto ): Club_Role {
		return new Club_Role( $dto );
	}

	/**
	 * Create a Club_Role entity from a generic object (e.g. a club role row).
	 *
	 * @param object $row
	 * @return Club_Role
	 */
	public static function from_object( object $row ): Club_Role {
		return self::create_from_dto( Club_Role_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Club_Role entity from an associative array.
	 *
	 * @param array $data
	 * @return Club_Role
	 */
	public static function from_array( array $data ): Club_Role {
		return self::create_from_dto( Club_Role_Hydration_DTO::from_array( $data ) );
	}
}
