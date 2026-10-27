<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use InvalidArgumentException;

/**
 * Data Transfer Object for hydrating an Event entity from raw data.
 */
readonly class Event_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 * @param string $type
	 * @param int|null $num_sets
	 * @param int|null $num_rubbers
	 * @param int|null $competition_id
	 * @param array $settings
	 * @param array|string $seasons
	 */
	public function __construct(
		public ?int $id = null,
		public string $name = '',
		public string $type = '',
		public ?int $num_sets = null,
		public ?int $num_rubbers = null,
		public ?int $competition_id = null,
		public array $settings = [],
		public array|string $seasons = []
	) {
		if ( null !== $this->id && $this->id < 0 ) {
			throw new InvalidArgumentException( 'Event id must be non-negative.' );
		}
		if ( null !== $this->competition_id && $this->competition_id <= 0 ) {
			throw new InvalidArgumentException( 'Event competition_id must be a positive integer.' );
		}
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$settings = $data->settings ?? [];
		if ( is_string( $settings ) ) {
			$unserialized = self::decode_serialized_array( $settings );
			if ( is_array( $unserialized ) ) {
				$settings = $unserialized;
			} else {
				$settings = json_decode( $settings, true ) ?: [];
			}
		}

		$seasons = $data->seasons ?? [];
		if ( is_string( $seasons ) ) {
			$unserialized = self::decode_serialized_array( $seasons );
			if ( is_array( $unserialized ) ) {
				$seasons = $unserialized;
			}
		}

		$name = (string) ( $data->name ?? ( $data->title ?? '' ) );
		$type = (string) ( $data->type ?? ( $data->event_type ?? '' ) );

		return new self(
			id: isset( $data->id ) && '' !== (string) $data->id ? (int) $data->id : null,
			name: $name,
			type: $type,
			num_sets: isset( $data->num_sets ) && '' !== (string) $data->num_sets ? (int) $data->num_sets : null,
			num_rubbers: isset( $data->num_rubbers ) && '' !== (string) $data->num_rubbers ? (int) $data->num_rubbers : null,
			competition_id: isset( $data->competition_id ) && '' !== (string) $data->competition_id && (int) $data->competition_id > 0 ? (int) $data->competition_id : null,
			settings: (array) $settings,
			seasons: $seasons
		);
	}

	/**
	 * Create from an associative array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return self::from_object( (object) $data );
	}

	/** Decode only legacy PHP-serialized arrays; leave JSON and malformed values unchanged. */
	private static function decode_serialized_array( mixed $value ): mixed {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$serialized = trim( $value );
		if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
			return $value;
		}
		return @unserialize( $serialized );
	}
}
