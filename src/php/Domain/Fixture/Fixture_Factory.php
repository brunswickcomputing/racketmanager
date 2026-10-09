<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;

/**
 * Factory for creating Fixture entities from hydration DTOs or database objects.
 */
final class Fixture_Factory {
	/**
	 * Create a Fixture entity from a hydration DTO.
	 *
	 * @param Fixture_Hydration_DTO $dto
	 * @return Fixture
	 */
	public static function create_from_dto( Fixture_Hydration_DTO $dto ): Fixture {
		return new Fixture( $dto );
	}

	/**
	 * Create a Fixture entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return Fixture
	 */
	public static function from_object( object $row ): Fixture {
		$dto = Fixture_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}
}
