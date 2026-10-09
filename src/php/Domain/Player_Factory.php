<?php
declare( strict_types=1 );

namespace Racketmanager\Domain;

use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;

/**
 * Factory for creating Player entities from hydration DTOs or database objects / WP_User.
 */
final class Player_Factory {
	/**
	 * Create a Player entity from a hydration DTO.
	 *
	 * @param Player_Hydration_DTO $dto
	 * @return Player
	 */
	public static function create_from_dto( Player_Hydration_DTO $dto ): Player {
		return new Player( $dto );
	}

	/**
	 * Create a Player entity from a generic object (e.g. database row or WP_User).
	 *
	 * @param object $row
	 * @return Player
	 */
	public static function from_object( object $row ): Player {
		$dto = Player_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}
}
