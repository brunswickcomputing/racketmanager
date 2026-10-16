<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;

/**
 * Factory for creating League entities.
 */
final class League_Factory {
	/**
	 * Create a League entity from a hydration DTO.
	 *
	 * Missing events return null so callers (including League::get_instance) can
	 * preserve the original false/null behaviour instead of fatalling.
	 *
	 * @param League_Hydration_DTO $dto
	 * @param mixed                $event Optional event; when omitted, resolved from $dto->event_id.
	 * @return League|null
	 */
	public static function create_from_dto( League_Hydration_DTO $dto, mixed $event = null ): ?League {
		$std_object = $dto->to_std_class();

		if ( null === $event ) {
			if ( empty( $dto->event_id ) ) {
				return null;
			}
			$event = \Racketmanager\get_event( $dto->event_id );
		}
		if ( empty( $event ) ) {
			return null;
		}

		if ( ! empty( $event->competition->sport ) ) {
			$sport_class = 'Racketmanager\\sports\\League_' . ucfirst( $event->competition->sport );
			if ( class_exists( $sport_class ) ) {
				return new $sport_class( $std_object );
			}
		}

		return new League( $std_object );
	}

	/**
	 * Create a League entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @param mixed  $event Optional event; when omitted, resolved from the row.
	 * @return League|null
	 */
	public static function from_object( object $row, mixed $event = null ): ?League {
		$dto = League_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto, $event );
	}

	/**
	 * Create a League entity from an associative array.
	 *
	 * @param array $data
	 * @param mixed $event Optional event; when omitted, resolved from the data.
	 * @return League|null
	 */
	public static function from_array( array $data, mixed $event = null ): ?League {
		$dto = League_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto, $event );
	}
}
