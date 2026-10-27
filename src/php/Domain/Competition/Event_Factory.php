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

		// The constructor derives competition_id from the Competition when the DTO value is null.
		return new Event(
			self::with_legacy_defaults( $dto ),
			$competition
		);
	}

	/** Default number of sets applied by the validating path when the row column is NULL. */
	public const DEFAULT_NUM_SETS = 3;
	/** Default number of rubbers applied by the validating path when the row column is NULL. */
	public const DEFAULT_NUM_RUBBERS = 0;

	/**
	 * Restore the legacy column defaults (`num_sets` 3, `num_rubbers` 0) for NULL/absent row values.
	 *
	 * The DTO itself keeps these nullable so the lenient `Event::from_object()` path mirrors the raw
	 * constructor; defaults are applied here as column fallbacks, so `settings` still take precedence
	 * inside the Event constructor.
	 *
	 * @param Event_Hydration_DTO $dto
	 * @return Event_Hydration_DTO
	 */
	private static function with_legacy_defaults( Event_Hydration_DTO $dto ): Event_Hydration_DTO {
		if ( null !== $dto->num_sets && null !== $dto->num_rubbers ) {
			return $dto;
		}

		return new Event_Hydration_DTO(
			id: $dto->id,
			name: $dto->name,
			type: $dto->type,
			num_sets: $dto->num_sets ?? self::DEFAULT_NUM_SETS,
			num_rubbers: $dto->num_rubbers ?? self::DEFAULT_NUM_RUBBERS,
			competition_id: $dto->competition_id,
			settings: $dto->settings,
			seasons: $dto->seasons
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
