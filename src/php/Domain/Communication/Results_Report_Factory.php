<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Communication;

use Racketmanager\Domain\DTO\Communication\Results_Report_Hydration_DTO;

/**
 * Factory for creating Results_Report entities from hydration DTOs, database objects, or arrays.
 */
final class Results_Report_Factory {
	/**
	 * Create a Results_Report entity from a hydration DTO.
	 *
	 * @param Results_Report_Hydration_DTO $dto
	 * @return Results_Report
	 */
	public static function create_from_dto( Results_Report_Hydration_DTO $dto ): Results_Report {
		return new Results_Report( $dto );
	}

	/**
	 * Create a Results_Report entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Results_Report
	 */
	public static function from_object( object $row ): Results_Report {
		return self::create_from_dto( Results_Report_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Results_Report entity from an array.
	 *
	 * @param array $data
	 * @return Results_Report
	 */
	public static function from_array( array $data ): Results_Report {
		return self::create_from_dto( Results_Report_Hydration_DTO::from_array( $data ) );
	}
}
