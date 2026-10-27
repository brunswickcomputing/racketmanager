<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;

/**
 * Factory for creating Rubber entities from hydration DTOs, database rows, or arrays.
 */
final class Rubber_Factory {
	/**
	 * Create a Rubber entity from a hydration DTO.
	 *
	 * @param Rubber_Hydration_DTO $dto
	 * @return Rubber
	 */
	public static function create_from_dto( Rubber_Hydration_DTO $dto ): Rubber {
		return new Rubber( $dto );
	}

	/**
	 * Create a Rubber entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return Rubber
	 */
	public static function from_object( object $row ): Rubber {
		return self::create_from_dto( Rubber_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Rubber entity from an associative array.
	 *
	 * @param array $data
	 * @return Rubber
	 */
	public static function from_array( array $data ): Rubber {
		return self::create_from_dto( Rubber_Hydration_DTO::from_array( $data ) );
	}
}
