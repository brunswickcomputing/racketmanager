<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;

/**
 * Factory for creating Event entities.
 */
final class Event_Factory {
	/**
	 * Create an Event entity from a hydration DTO.
	 *
	 * @param Event_Hydration_DTO $dto
	 * @return Event
	 */
	public static function create_from_dto( Event_Hydration_DTO $dto ): Event {
		return new Event( $dto->to_std_class() );
	}

	/**
	 * Create an Event entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @return Event
	 */
	public static function from_object( object $row ): Event {
		$dto = Event_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}

	/**
	 * Create an Event entity from an associative array.
	 *
	 * @param array $data
	 * @return Event
	 */
	public static function from_array( array $data ): Event {
		$dto = Event_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto );
	}
}
