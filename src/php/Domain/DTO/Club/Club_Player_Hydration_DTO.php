<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Data Transfer Object for hydrating a Club_Player (club registration) entity from a row.
 *
 * Strict allowlist of the `club_players` table columns plus the `player` object some callers attach.
 * Unknown row keys are dropped. `null` means "not present in the row": the entity keeps its default.
 */
readonly class Club_Player_Hydration_DTO {
	public function __construct(
		public ?int $id = null,
		public ?int $player_id = null,
		public ?int $club_id = null,
		public ?string $removed_date = null,
		public ?int $removed_user = null,
		public ?string $created_date = null,
		public ?int $created_user = null,
		public ?string $requested_date = null,
		public ?int $requested_user = null,
		public ?object $player = null,
		public ?bool $system_record = null,
		public ?string $status = null,
	) {
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		return new self(
			id: self::nullable_int( $data->id ?? null ),
			player_id: self::int( $data->player_id ?? null ),
			club_id: self::int( $data->club_id ?? null ),
			removed_date: self::string( $data->removed_date ?? null ),
			removed_user: self::nullable_int( $data->removed_user ?? null ),
			created_date: self::string( $data->created_date ?? null ),
			created_user: self::nullable_int( $data->created_user ?? null ),
			requested_date: self::string( $data->requested_date ?? null ),
			requested_user: self::nullable_int( $data->requested_user ?? null ),
			player: isset( $data->player ) && is_object( $data->player ) ? $data->player : null,
			system_record: self::bool( $data->system_record ?? null ),
			status: self::string( $data->status ?? null ),
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

	/** Integer column backing a non-nullable entity property: `''` becomes 0, absent/null/non-numeric becomes null. */
	private static function int( mixed $value ): ?int {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0;
		}
		return is_numeric( $value ) ? (int) $value : null;
	}

	/** Integer column backing a nullable entity property: `''` becomes 0 (legacy coercion); null and absent remain null. */
	private static function nullable_int( mixed $value ): ?int {
		return self::int( $value );
	}

	/** String column: scalars are cast, absent/non-scalar becomes null. */
	private static function string( mixed $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}

	/** Boolean column: `''` becomes false, numeric/bool values are cast, absent becomes null. */
	private static function bool( mixed $value ): ?bool {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return false;
		}
		return is_bool( $value ) || is_numeric( $value ) ? (bool) $value : null;
	}
}
