<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;

/**
 * Factory for creating Competition entities.
 */
final class Competition_Factory {
	/**
	 * Create a Competition entity from a hydration DTO.
	 *
	 * @param Competition_Hydration_DTO $dto
	 * @return Competition
	 */
	public static function create_from_dto( Competition_Hydration_DTO $dto ): Competition {
		return new Competition(
			name: $dto->name,
			type: $dto->type,
			age_group: $dto->age_group ?: '',
			seasons: $dto->seasons,
			settings: $dto->settings,
			id: $dto->id
		);
	}

	/**
	 * Create a Competition entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Competition
	 */
	public static function from_object( object $row ): Competition {
		$dto = Competition_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}
}
