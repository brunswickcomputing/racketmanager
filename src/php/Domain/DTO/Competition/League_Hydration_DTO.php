<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

/**
 * Data Transfer Object for hydrating a League entity from raw data.
 */
readonly class League_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $title
	 * @param int|null $event_id
	 * @param string|null $sequence
	 * @param array $settings
	 * @param array $seasons
	 */
	public function __construct(
		public ?int $id,
		public string $title,
		public ?int $event_id = null,
		public ?string $sequence = null,
		public array $settings = [],
		public array $seasons = []
	) {
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
			$unserialized = maybe_unserialize( $seasons );
			if ( is_array( $unserialized ) ) {
				$seasons = $unserialized;
			} else {
				$seasons = json_decode( $seasons, true ) ?: [];
			}
		}

		$settings = $data->settings ?? [];
		if ( is_string( $settings ) ) {
			$unserialized = maybe_unserialize( $settings );
			if ( is_array( $unserialized ) ) {
				$settings = $unserialized;
			} else {
				$settings = json_decode( $settings, true ) ?: [];
			}
		}

		$title = $data->title ?? ( $data->name ?? '' );

		return new self(
			id: isset( $data->id ) ? (int) $data->id : null,
			title: (string) $title,
			event_id: isset( $data->event_id ) ? (int) $data->event_id : null,
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

	/**
	 * Convert DTO to a standard object compatible with legacy entity hydration.
	 *
	 * @return \stdClass
	 */
	public function to_std_class(): \stdClass {
		$obj = new \stdClass();
		$obj->id = $this->id;
		$obj->title = $this->title;
		$obj->name = $this->title;
		$obj->event_id = $this->event_id;
		$obj->sequence = $this->sequence;
		$obj->settings = $this->settings;
		$obj->seasons = $this->seasons;

		foreach ( $this->settings as $key => $val ) {
			if ( ! isset( $obj->{$key} ) ) {
				$obj->{$key} = $val;
			}
		}

		return $obj;
	}
}
