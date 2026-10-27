<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for hydrating a Player_Error entity from database rows or input data.
 *
 * Strict allowlist of the player errors columns the entity declares. Unknown row keys are dropped.
 */
readonly class Player_Error_Hydration_DTO {
	/**
	 * @param int|null    $id
	 * @param int|null    $player_id
	 * @param string|null $message
	 * @param string|null $created_date
	 */
	public function __construct(
		public ?int $id = null,
		public ?int $player_id = null,
		public ?string $message = null,
		public ?string $created_date = null,
	) {
	}

	/**
	 * Create from a generic object (e.g., database row).
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		return new self(
			id: self::nullable_int( $data->id ?? null ),
			player_id: self::nullable_int( $data->player_id ?? null ),
			message: isset( $data->message ) && is_scalar( $data->message ) ? (string) $data->message : null,
			created_date: isset( $data->created_date ) && is_scalar( $data->created_date ) ? (string) $data->created_date : null,
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

	private static function nullable_int( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}
		return (int) $value;
	}
}
