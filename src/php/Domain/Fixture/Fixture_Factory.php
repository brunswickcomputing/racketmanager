<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

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
	public static function create_from_dto(
		Fixture_Hydration_DTO $dto,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Team_Resolver_Interface $team_service = null,
		?Fixture_Update_Authorizer_Interface $update_authorizer = null
	): Fixture {
		return new Fixture( $dto, $league_repository, $team_service, $update_authorizer );
	}

	/**
	 * Create a Fixture entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return Fixture
	 */
	public static function from_object(
		object $row,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Team_Resolver_Interface $team_service = null,
		?Fixture_Update_Authorizer_Interface $update_authorizer = null
	): Fixture {
		$dto = Fixture_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto, $league_repository, $team_service, $update_authorizer );
	}

	/**
	 * Create a Fixture entity from an array.
	 *
	 * @param array $data
	 * @return Fixture
	 */
	public static function from_array(
		array $data,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Team_Resolver_Interface $team_service = null,
		?Fixture_Update_Authorizer_Interface $update_authorizer = null
	): Fixture {
		$dto = Fixture_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto, $league_repository, $team_service, $update_authorizer );
	}
}
