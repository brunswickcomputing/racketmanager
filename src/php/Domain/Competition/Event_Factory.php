<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use InvalidArgumentException;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;

/**
 * Factory for creating Event entities.
 */
final class Event_Factory {
	/**
	 * Create an Event entity from a hydration DTO.
	 *
	 * @param Event_Hydration_DTO $dto
	 * @param Competition|null $competition
	 * @return Event
	 * @throws InvalidArgumentException
	 */
	public static function create_from_dto(
		Event_Hydration_DTO $dto,
		?Competition $competition = null
	): Event {
		if ( '' === trim( $dto->name ) ) {
			throw new InvalidArgumentException( 'Event name is required.' );
		}

		if ( '' === trim( $dto->type ) ) {
			throw new InvalidArgumentException( 'Event type is required.' );
		}

		if ( null === $dto->competition_id && ( null === $competition || empty( $competition->id ) ) ) {
			throw new InvalidArgumentException( 'Event competition_id is required.' );
		}

		if ( null !== $dto->competition_id && null !== $competition && ! empty( $competition->id ) && (int) $dto->competition_id !== (int) $competition->id ) {
			throw new InvalidArgumentException( sprintf( 'Event competition_id %d does not match Competition id %d.', $dto->competition_id, (int) $competition->id ) );
		}

		$std_class = $dto->to_std_class();
		if ( null === $std_class->competition_id && null !== $competition && ! empty( $competition->id ) ) {
			$std_class->competition_id = (int) $competition->id;
		}

		return new Event(
			$std_class,
			$competition
		);
	}

	/**
	 * Create an Event entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @param Competition|null $competition
	 * @return Event
	 * @throws InvalidArgumentException
	 */
	public static function from_object(
		object $row,
		?Competition $competition = null
	): Event {
		$dto = Event_Hydration_DTO::from_object( $row );
		return self::create_from_dto(
			$dto,
			$competition
		);
	}

	/**
	 * Create an Event entity from an associative array.
	 *
	 * @param array $data
	 * @param Competition|null $competition
	 * @return Event
	 * @throws InvalidArgumentException
	 */
	public static function from_array(
		array $data,
		?Competition $competition = null
	): Event {
		$dto = Event_Hydration_DTO::from_array( $data );
		return self::create_from_dto(
			$dto,
			$competition
		);
	}
}
