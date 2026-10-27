<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Result;

use Racketmanager\Domain\DTO\Result\Results_Checker_Data;

/**
 * Factory for creating Results_Checker entities from hydration DTOs, database objects, or arrays.
 */
final class Results_Checker_Factory {
	/**
	 * Create a Results_Checker entity from a hydration DTO.
	 *
	 * @param Results_Checker_Data $dto
	 * @return Results_Checker
	 */
	public static function create_from_dto( Results_Checker_Data $dto ): Results_Checker {
		return new Results_Checker( $dto );
	}

	/**
	 * Create a Results_Checker entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Results_Checker
	 */
	public static function from_object( object $row ): Results_Checker {
		return self::create_from_dto( Results_Checker_Data::from_object( $row ) );
	}

	/**
	 * Create a Results_Checker entity from an array.
	 *
	 * @param array $data
	 * @return Results_Checker
	 */
	public static function from_array( array $data ): Results_Checker {
		return self::create_from_dto( Results_Checker_Data::from_array( $data ) );
	}
}
