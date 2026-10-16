<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

/**
 * Data Transfer Object for hydrating an Event entity from raw data.
 */
readonly class Event_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 * @param string $type
	 * @param int $num_sets
	 * @param int $num_rubbers
	 * @param int|null $competition_id
	 * @param array $settings
	 * @param array|string $seasons
	 */
	public function __construct(
		public ?int $id,
		public string $name,
		public string $type = '',
		public int $num_sets = 3,
		public int $num_rubbers = 0,
		public ?int $competition_id = null,
		public array $settings = [],
		public array|string $seasons = []
	) {
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
			$unserialized = maybe_unserialize( $settings );
			if ( is_array( $unserialized ) ) {
				$settings = $unserialized;
			} else {
				$settings = json_decode( $settings, true ) ?: [];
			}
		}

		$seasons = $data->seasons ?? [];
		if ( is_string( $seasons ) ) {
			$unserialized = maybe_unserialize( $seasons );
			if ( is_array( $unserialized ) ) {
				$seasons = $unserialized;
			}
		}

		return new self(
			id: isset( $data->id ) ? (int) $data->id : null,
			name: (string) ( $data->name ?? '' ),
			type: (string) ( $data->type ?? '' ),
			num_sets: isset( $data->num_sets ) ? (int) $data->num_sets : 3,
			num_rubbers: isset( $data->num_rubbers ) ? (int) $data->num_rubbers : 0,
			competition_id: isset( $data->competition_id ) ? (int) $data->competition_id : null,
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

	/**
	 * Convert DTO to a standard object compatible with legacy entity hydration.
	 *
	 * @return \stdClass
	 */
	public function to_std_class(): \stdClass {
		$obj = new \stdClass();
		$obj->id = $this->id;
		$obj->name = $this->name;
		$obj->type = $this->type;
		$obj->num_sets = $this->num_sets;
		$obj->num_rubbers = $this->num_rubbers;
		$obj->competition_id = $this->competition_id;
		$obj->settings = $this->settings;
		$obj->seasons = is_array( $this->seasons ) ? wp_json_encode( $this->seasons ) : $this->seasons;

		foreach ( $this->settings as $key => $val ) {
			if ( ! isset( $obj->{$key} ) ) {
				$obj->{$key} = $val;
			}
		}

		return $obj;
	}
}
