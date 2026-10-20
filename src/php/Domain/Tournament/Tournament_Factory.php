<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Tournament;

use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;

/**
 * Factory for creating Tournament entities from hydration DTOs or database objects.
 */
final class Tournament_Factory {
	/**
	 * Create a Tournament entity from a hydration DTO.
	 *
	 * @param Tournament_Hydration_DTO $dto
	 * @return Tournament
	 */
	public static function create_from_dto( Tournament_Hydration_DTO $dto ): Tournament {
		return new Tournament( $dto );
	}

	/**
	 * Create a Tournament entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return Tournament
	 */
	public static function from_object( object $row ): Tournament {
		return self::create_from_dto( Tournament_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Tournament entity from an array.
	 *
	 * @param array $data
	 * @return Tournament
	 */
	public static function from_array( array $data ): Tournament {
		return self::create_from_dto( Tournament_Hydration_DTO::from_array( $data ) );
	}
}
