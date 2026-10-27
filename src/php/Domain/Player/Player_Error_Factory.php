<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Player;

use Racketmanager\Domain\DTO\Player\Player_Error_Hydration_DTO;

/**
 * Factory for creating Player_Error entities from hydration DTOs, database objects, or arrays.
 */
final class Player_Error_Factory {
	/**
	 * Create a Player_Error entity from a hydration DTO.
	 *
	 * @param Player_Error_Hydration_DTO $dto
	 * @return Player_Error
	 */
	public static function create_from_dto( Player_Error_Hydration_DTO $dto ): Player_Error {
		return new Player_Error( $dto );
	}

	/**
	 * Create a Player_Error entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Player_Error
	 */
	public static function from_object( object $row ): Player_Error {
		return self::create_from_dto( Player_Error_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Player_Error entity from an array.
	 *
	 * @param array $data
	 * @return Player_Error
	 */
	public static function from_array( array $data ): Player_Error {
		return self::create_from_dto( Player_Error_Hydration_DTO::from_array( $data ) );
	}
}
