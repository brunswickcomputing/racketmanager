<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Season;

/**
 * Data Transfer Object for hydrating a Season entity from database rows or input data.
 */
readonly class Season_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 */
	public function __construct(
		public ?int $id = null,
		public string $name = ''
	) {
	}

	/**
	 * Create from a generic object (e.g., database row).
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$id   = isset( $data->id ) ? (int) $data->id : null;
		$name = (string) ( $data->name ?? '' );

		return new self(
			id: $id,
			name: $name
		);
	}

	/**
	 * Create from an array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return self::from_object( (object) $data );
	}
}
