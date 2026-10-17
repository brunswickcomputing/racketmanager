<?php
declare( strict_types=1 );

namespace Racketmanager\Domain;

use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Services\Competition_Service;

/**
 * Factory for creating Tournament entities from hydration DTOs or database objects.
 */
final class Tournament_Factory {
	/**
	 * Create a Tournament entity from a hydration DTO.
	 *
	 * @param Tournament_Hydration_DTO $dto
	 * @param Competition_Service|null $competition_service
	 * @param Option_Gateway_Interface|null $option_gateway
	 * @param string|null $site_url
	 * @param string|null $date_format
	 * @return Tournament
	 */
	public static function create_from_dto(
		Tournament_Hydration_DTO $dto,
		?Competition_Service $competition_service = null,
		?Option_Gateway_Interface $option_gateway = null,
		?string $site_url = null,
		?string $date_format = null
	): Tournament {
		return new Tournament(
			$dto->to_std_class(),
			$competition_service,
			$option_gateway,
			$site_url,
			$date_format
		);
	}

	/**
	 * Create a Tournament entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @param Competition_Service|null $competition_service
	 * @param Option_Gateway_Interface|null $option_gateway
	 * @param string|null $site_url
	 * @param string|null $date_format
	 * @return Tournament
	 */
	public static function from_object(
		object $row,
		?Competition_Service $competition_service = null,
		?Option_Gateway_Interface $option_gateway = null,
		?string $site_url = null,
		?string $date_format = null
	): Tournament {
		$dto = Tournament_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto, $competition_service, $option_gateway, $site_url, $date_format );
	}

	/**
	 * Create a Tournament entity from an array.
	 *
	 * @param array $data
	 * @param Competition_Service|null $competition_service
	 * @param Option_Gateway_Interface|null $option_gateway
	 * @param string|null $site_url
	 * @param string|null $date_format
	 * @return Tournament
	 */
	public static function from_array(
		array $data,
		?Competition_Service $competition_service = null,
		?Option_Gateway_Interface $option_gateway = null,
		?string $site_url = null,
		?string $date_format = null
	): Tournament {
		$dto = Tournament_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto, $competition_service, $option_gateway, $site_url, $date_format );
	}
}
