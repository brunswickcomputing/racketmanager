<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Domain\Shared\Serialized_Column;

/**
 * Data Transfer Object for hydrating a Rubber entity from a rubber row.
 *
 * Strict allowlist of the `rubbers` table columns, the attachments services add to a row
 * (`players`, `reverse_rubbers`, `reverse_rubber`, `class`, `stats`) and the result keys persisted
 * inside the serialized `custom` column (`sets`, `stats`, `walkover`, `share`, `retired`, `abandoned`,
 * `cancelled`, `invalid`). `custom` is decoded and unslashed and its keys take precedence over the
 * table columns (as the legacy row merge did). Unknown row keys are dropped.
 *
 * `null` means "not present in the row": the entity keeps its default for that property.
 */
readonly class Rubber_Hydration_DTO {
	public function __construct(
		public int|false|null $id = null,
		public ?int $match_id = null,
		public ?int $status = null,
		public mixed $custom = null,
		public mixed $sets = null,
		public ?string $date = null,
		public ?float $home_points = null,
		public ?float $away_points = null,
		public ?array $players = null,
		public ?string $type = null,
		public ?int $rubber_number = null,
		public ?int $winner_id = null,
		public ?int $loser_id = null,
		public ?bool $reverse_rubbers = null,
		public ?bool $reverse_rubber = null,
		public ?array $stats = null,
		public ?string $class = null,
		public ?string $group = null,
		public ?int $post_id = null,
		public ?string $walkover = null,
		public ?string $invalid = null,
		public ?string $abandoned = null,
		public ?string $share = null,
		public ?string $cancelled = null,
		public ?string $retired = null,
	) {
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$row = (array) $data;
		if ( ! empty( $row['custom'] ) ) {
			$row = array_merge( $row, Serialized_Column::unslash( Serialized_Column::decode_array( $row['custom'] ) ) );
		}
		$custom = array_key_exists( 'custom', $row ) ? Serialized_Column::decode_unslashed( $row['custom'] ) : null;

		return new self(
			id: self::id( $row['id'] ?? null ),
			match_id: self::int( $row['match_id'] ?? null ),
			status: self::nullable_int( $row['status'] ?? null ),
			custom: $custom,
			sets: is_array( $custom ) ? ( $custom['sets'] ?? null ) : null,
			date: self::string( $row['date'] ?? null ),
			home_points: self::nullable_float( $row['home_points'] ?? null ),
			away_points: self::nullable_float( $row['away_points'] ?? null ),
			players: isset( $row['players'] ) && is_array( $row['players'] ) ? $row['players'] : null,
			type: self::string( $row['type'] ?? null ),
			rubber_number: self::int( $row['rubber_number'] ?? null ),
			winner_id: self::int( $row['winner_id'] ?? null ),
			loser_id: self::int( $row['loser_id'] ?? null ),
			reverse_rubbers: isset( $row['reverse_rubbers'] ) ? (bool) $row['reverse_rubbers'] : null,
			reverse_rubber: isset( $row['reverse_rubber'] ) ? (bool) $row['reverse_rubber'] : null,
			stats: isset( $row['stats'] ) && is_array( $row['stats'] ) ? $row['stats'] : null,
			class: self::string( $row['class'] ?? null ),
			group: self::string( $row['group'] ?? null ),
			post_id: self::int( $row['post_id'] ?? null ),
			walkover: self::string( $row['walkover'] ?? null ),
			invalid: self::string( $row['invalid'] ?? null ),
			abandoned: self::string( $row['abandoned'] ?? null ),
			share: self::string( $row['share'] ?? null ),
			cancelled: self::string( $row['cancelled'] ?? null ),
			retired: self::string( $row['retired'] ?? null ),
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

	/** `id` keeps the legacy `int|false` shape: `false` is passed through, `''` becomes 0. */
	private static function id( mixed $value ): int|false|null {
		if ( false === $value ) {
			return false;
		}
		return self::int( $value );
	}

	/** Integer column backing a non-nullable entity property: `''` becomes 0, absent/non-numeric becomes null. */
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

	/** Float column backing a nullable entity property: `''` becomes 0.0 (legacy coercion); null and absent remain null. */
	private static function nullable_float( mixed $value ): ?float {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0.0;
		}
		return is_numeric( $value ) ? (float) $value : null;
	}

	/** String column: scalars are cast, absent/non-scalar becomes null. */
	private static function string( mixed $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}
}
