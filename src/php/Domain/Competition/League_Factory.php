<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use InvalidArgumentException;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;

/**
 * Factory for creating League entities.
 */
final class League_Factory {
	/**
	 * Create a League entity from a hydration DTO.
	 *
	 * Missing events return null so callers can handle the absence instead of fatalling.
	 *
	 * @param League_Hydration_DTO $dto
	 * @param Event|null           $event Optional event; if null, returns null.
	 * @return League|null
	 * @throws InvalidArgumentException
	 */
	public static function create_from_dto(
		League_Hydration_DTO $dto,
		?Event $event = null
	): ?League {
		if ( null === $event ) {
			return null;
		}

		if ( '' === trim( $dto->title ) ) {
			throw new InvalidArgumentException( 'League title is required.' );
		}

		if ( null !== $dto->event_id && isset( $event->id ) && (int) $dto->event_id !== (int) $event->id ) {
			throw new InvalidArgumentException( sprintf( 'League event_id %d does not match Event id %d.', $dto->event_id, (int) $event->id ) );
		}

		$std_object = $dto->to_std_class();
		if ( null === $std_object->event_id && isset( $event->id ) ) {
			$std_object->event_id = (int) $event->id;
		}

		if ( ! empty( $event->competition->sport ) ) {
			$sport_class = 'Racketmanager\\sports\\League_' . ucfirst( $event->competition->sport );
			if ( class_exists( $sport_class ) ) {
				return new $sport_class(
					$std_object,
					$event
				);
			}
		}

		return new League(
			$std_object,
			$event
		);
	}

	/**
	 * Create a League entity from a generic object (e.g. database row).
	 *
	 * @param object     $row
	 * @param Event|null $event
	 * @return League|null
	 * @throws InvalidArgumentException
	 */
	public static function from_object(
		object $row,
		?Event $event = null
	): ?League {
		$dto = League_Hydration_DTO::from_object( $row );
		return self::create_from_dto(
			$dto,
			$event
		);
	}

	/**
	 * Create a League entity from an associative array.
	 *
	 * @param array      $data
	 * @param Event|null $event
	 * @return League|null
	 * @throws InvalidArgumentException
	 */
	public static function from_array(
		array $data,
		?Event $event = null
	): ?League {
		$dto = League_Hydration_DTO::from_array( $data );
		return self::create_from_dto(
			$dto,
			$event
		);
	}
}
