<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Data Transfer Object for hydrating a Club_Role entity from a row.
 *
 * Strict allowlist of the `club_roles` table columns plus the `role`, `club` and `user` objects
 * callers may attach. Unknown row keys are dropped. `null` means "not present in the row".
 */
readonly class Club_Role_Hydration_DTO {
	public function __construct(
		public ?int $id = null,
		public ?int $user_id = null,
		public ?int $club_id = null,
		public ?int $role_id = null,
		public ?object $role = null,
		public ?object $club = null,
		public ?object $user = null,
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
			user_id: self::int( $data->user_id ?? null ),
			club_id: self::int( $data->club_id ?? null ),
			role_id: self::int( $data->role_id ?? null ),
			role: self::object( $data->role ?? null ),
			club: self::object( $data->club ?? null ),
			user: self::object( $data->user ?? null ),
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

	/** Attached object: non-objects are dropped. */
	private static function object( mixed $value ): ?object {
		return is_object( $value ) ? $value : null;
	}
}
