<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Data Transfer Object for hydrating a Tournament_Entry entity from database rows or input data.
 *
 * Strict allowlist of the tournament entries table columns. Unknown row keys are dropped.
 */
readonly class Tournament_Entry_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param int|null $tournament_id
	 * @param int|null $player_id
	 * @param int|null $status
	 * @param int|null $fee
	 * @param int|null $club_id
	 */
	public function __construct(
		public ?int $id = null,
		public ?int $tournament_id = null,
		public ?int $player_id = null,
		public ?int $status = null,
		public ?int $fee = null,
		public ?int $club_id = null,
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
			tournament_id: self::nullable_int( $data->tournament_id ?? null ),
			player_id: self::nullable_int( $data->player_id ?? null ),
			status: self::nullable_int( $data->status ?? null ),
			fee: self::nullable_int( $data->fee ?? null ),
			club_id: self::nullable_int( $data->club_id ?? null ),
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
