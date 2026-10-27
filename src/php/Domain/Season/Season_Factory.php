<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Season;

use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;

/**
 * Factory for creating Season entities from hydration DTOs, database objects, or arrays.
 */
final class Season_Factory {
	/**
	 * Create a Season entity from a hydration DTO.
	 *
	 * @param Season_Hydration_DTO $dto
	 * @return Season
	 */
	public static function create_from_dto( Season_Hydration_DTO $dto ): Season {
		return new Season( $dto );
	}

	/**
	 * Create a Season entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Season
	 */
	public static function from_object( object $row ): Season {
		return self::create_from_dto( Season_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Season entity from an array.
	 *
	 * @param array $data
	 * @return Season
	 */
	public static function from_array( array $data ): Season {
		return self::create_from_dto( Season_Hydration_DTO::from_array( $data ) );
	}
}
