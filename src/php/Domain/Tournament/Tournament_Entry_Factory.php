<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Tournament;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;

/**
 * Factory for creating Tournament_Entry entities from hydration DTOs, database objects, or arrays.
 */
final class Tournament_Entry_Factory {
	/**
	 * Create a Tournament_Entry entity from a hydration DTO.
	 *
	 * @param Tournament_Entry_Hydration_DTO $dto
	 * @return Tournament_Entry
	 */
	public static function create_from_dto( Tournament_Entry_Hydration_DTO $dto ): Tournament_Entry {
		return new Tournament_Entry( $dto );
	}

	/**
	 * Create a Tournament_Entry entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Tournament_Entry
	 */
	public static function from_object( object $row ): Tournament_Entry {
		return self::create_from_dto( Tournament_Entry_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Tournament_Entry entity from an array.
	 *
	 * @param array $data
	 * @return Tournament_Entry
	 */
	public static function from_array( array $data ): Tournament_Entry {
		return self::create_from_dto( Tournament_Entry_Hydration_DTO::from_array( $data ) );
	}
}
