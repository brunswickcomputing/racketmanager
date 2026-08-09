<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use Racketmanager\Domain\Competition\Competition_Type;

/**
 * Data Transfer Object for hydrating a Competition entity from raw data.
 */
readonly class Competition_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 * @param Competition_Type $type
	 * @param string|null $age_group
	 * @param array $seasons
	 * @param array $settings
	 */
	public function __construct(
		public ?int $id,
		public string $name,
		public Competition_Type $type,
		public ?string $age_group = null,
		public array $seasons = [],
		public array $settings = []
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
			$seasons = json_decode( $seasons, true ) ?: [];
		}

		$settings = $data->settings ?? [];
		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true ) ?: [];
		}

		return new self(
			id: isset( $data->id ) ? (int) $data->id : null,
			name: $data->name ?? '',
			type: Competition_Type::tryFrom( $data->type ?? '' ) ?: Competition_Type::LEAGUE,
			age_group: $data->age_group ?? null,
			seasons: (array) $seasons,
			settings: (array) $settings
		);
	}
}
