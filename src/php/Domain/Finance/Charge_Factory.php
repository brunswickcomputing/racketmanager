<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Finance;

use Racketmanager\Domain\DTO\Finance\Charge_Hydration_DTO;

/**
 * Factory for creating Charge entities from hydration DTOs, database objects, or arrays.
 */
final class Charge_Factory {
	/**
	 * Create a Charge entity from a hydration DTO.
	 *
	 * @param Charge_Hydration_DTO $dto
	 * @return Charge
	 */
	public static function create_from_dto( Charge_Hydration_DTO $dto ): Charge {
		return new Charge( $dto );
	}

	/**
	 * Create a Charge entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Charge
	 */
	public static function from_object( object $row ): Charge {
		return self::create_from_dto( Charge_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Charge entity from an array.
	 *
	 * @param array $data
	 * @return Charge
	 */
	public static function from_array( array $data ): Charge {
		return self::create_from_dto( Charge_Hydration_DTO::from_array( $data ) );
	}
}
