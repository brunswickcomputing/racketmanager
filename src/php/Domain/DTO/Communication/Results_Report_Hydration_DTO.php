<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Communication;

use Racketmanager\Domain\Value_Objects\Json_Encoder;

/**
 * Data Transfer Object for hydrating a Results_Report entity from database rows or input data.
 *
 * Strict allowlist of the results report columns. Unknown row keys are dropped.
 * `result_object` (the JSON string stored in the database) and `data` (the decoded report) are kept in
 * sync: whichever side is missing is derived from the other, as the legacy constructor did.
 */
readonly class Results_Report_Hydration_DTO {
	/**
	 * @param int|null          $id
	 * @param int|null          $match_id
	 * @param string|false|null $result_object JSON string stored in the database.
	 * @param mixed             $data          Decoded report payload.
	 */
	public function __construct(
		public ?int $id = null,
		public ?int $match_id = null,
		public string|false|null $result_object = null,
		public mixed $data = null,
	) {
	}

	/**
	 * Create from a generic object (e.g., database row).
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$result_object = $data->result_object ?? null;
		$payload       = $data->data ?? null;

		if ( false !== $result_object && null !== $result_object ) {
			$result_object = is_scalar( $result_object ) ? (string) $result_object : null;
		}

		if ( null === $payload && is_string( $result_object ) ) {
			$payload = json_decode( $result_object );
		}
		if ( null === $result_object && null !== $payload ) {
			$result_object = Json_Encoder::encode( $payload );
		}

		return new self(
			id: self::nullable_int( $data->id ?? null ),
			match_id: self::nullable_int( $data->match_id ?? null ),
			result_object: $result_object,
			data: $payload,
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
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0;
		}
		return (int) $value;
	}
}
