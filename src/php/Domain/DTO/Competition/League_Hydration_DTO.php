<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use InvalidArgumentException;

/**
 * Data Transfer Object for hydrating a League entity from raw data.
 */
class League_Hydration_DTO {
	/**
	 * Backward-compatible property hook for legacy title access.
	 */
	public string $title {
		final get => $this->name;
	}

	public readonly string $name;

	/**
	 * @param int|null $id
	 * @param string $name
	 * @param int|null $event_id
	 * @param string|null $sequence
	 * @param array $settings
	 * @param array $seasons
	 * @param string|null $title Legacy alias for $name.
	 */
	public function __construct(
		public readonly ?int $id = null,
		string $name = '',
		public readonly ?int $event_id = null,
		public readonly ?string $sequence = null,
		public readonly array $settings = [],
		public readonly array $seasons = [],
		?string $title = null
	) {
		$this->name = '' !== $name ? $name : ( $title ?? '' );

		if ( null !== $this->id && $this->id < 0 ) {
			throw new InvalidArgumentException( 'League id must be non-negative.' );
		}
		if ( null !== $this->event_id && $this->event_id <= 0 ) {
			throw new InvalidArgumentException( 'League event_id must be a positive integer.' );
		}
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$seasons = $data->seasons ?? [];
		if ( is_string( $seasons ) ) {
			$unserialized = self::decode_serialized_array( $seasons );
			if ( is_array( $unserialized ) ) {
				$seasons = $unserialized;
			} else {
				$seasons = json_decode( $seasons, true ) ?: [];
			}
		}

		$settings = $data->settings ?? [];
		if ( is_string( $settings ) ) {
			$unserialized = self::decode_serialized_array( $settings );
			if ( is_array( $unserialized ) ) {
				$settings = $unserialized;
			} else {
				$settings = json_decode( $settings, true ) ?: [];
			}
		}

		$name = (string) ( $data->name ?? ( $data->title ?? '' ) );

		return new self(
			id: isset( $data->id ) && '' !== (string) $data->id ? (int) $data->id : null,
			name: $name,
			event_id: isset( $data->event_id ) && '' !== (string) $data->event_id && (int) $data->event_id > 0 ? (int) $data->event_id : null,
			sequence: isset( $data->sequence ) ? (string) $data->sequence : null,
			settings: (array) $settings,
			seasons: (array) $seasons
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
