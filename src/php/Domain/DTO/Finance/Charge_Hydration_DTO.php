<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Finance;

/**
 * Data Transfer Object for hydrating a Charge entity from database rows or input data.
 *
 * Strict allowlist of the charges table columns. Unknown row keys are dropped.
 * Numeric columns follow the legacy entity coercion: `null`/`''` become `null`.
 */
readonly class Charge_Hydration_DTO {
	/**
	 * @param int|null    $id
	 * @param int|null    $competition_id
	 * @param string|null $season
	 * @param string      $status
	 * @param string|null $date
	 * @param float|null  $fee_competition
	 * @param float|null  $fee_event
	 * @param float|null  $total
	 */
	public function __construct(
		public ?int $id = null,
		public ?int $competition_id = null,
		public ?string $season = null,
		public string $status = 'draft',
		public ?string $date = null,
		public ?float $fee_competition = null,
		public ?float $fee_event = null,
		public ?float $total = null,
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
			competition_id: self::nullable_int( $data->competition_id ?? null ),
			season: isset( $data->season ) ? (string) $data->season : null,
			status: isset( $data->status ) ? (string) $data->status : 'draft',
			date: isset( $data->date ) ? (string) $data->date : null,
			fee_competition: self::nullable_float( $data->fee_competition ?? null ),
			fee_event: self::nullable_float( $data->fee_event ?? null ),
			total: self::nullable_float( $data->total ?? null ),
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
		if ( null === $value || '' === (string) $value ) {
			return null;
		}
		return (int) $value;
	}

	private static function nullable_float( mixed $value ): ?float {
		if ( null === $value || '' === (string) $value ) {
			return null;
		}
		return (float) $value;
	}
}
