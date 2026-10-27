<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Finance;

/**
 * Data Transfer Object for hydrating an Invoice entity from database rows or input data.
 *
 * Strict allowlist of the invoice columns the entity declares. Unknown row keys are dropped.
 * Legacy coercion is preserved: identifier columns become `null` for `null`/`''`, `amount` is always a
 * string when the column is present, and a JSON `details` string is decoded into an object.
 */
readonly class Invoice_Hydration_DTO {
	/**
	 * @param int|null           $id
	 * @param int|null           $invoice_number
	 * @param int|null           $charge_id
	 * @param int|null           $billable_id
	 * @param string|null        $billable_type
	 * @param string|null        $status
	 * @param string|null        $date
	 * @param string|null        $date_due
	 * @param string|null        $amount
	 * @param string|null        $payment_reference
	 * @param string|null        $purchase_order
	 * @param object|string|null $details
	 */
	public function __construct(
		public ?int $id = null,
		public ?int $invoice_number = null,
		public ?int $charge_id = null,
		public ?int $billable_id = null,
		public ?string $billable_type = null,
		public ?string $status = null,
		public ?string $date = null,
		public ?string $date_due = null,
		public ?string $amount = null,
		public ?string $payment_reference = null,
		public ?string $purchase_order = null,
		public object|string|null $details = null,
	) {
	}

	/**
	 * Create from a generic object (e.g., database row).
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$row = get_object_vars( $data );

		$details = $row['details'] ?? null;
		if ( is_string( $details ) ) {
			$details = json_decode( $details );
		}

		return new self(
			id: self::nullable_int( $row['id'] ?? null ),
			invoice_number: self::nullable_int( $row['invoice_number'] ?? null ),
			charge_id: self::nullable_int( $row['charge_id'] ?? null ),
			billable_id: self::nullable_int( $row['billable_id'] ?? null ),
			billable_type: self::nullable_string( $row['billable_type'] ?? null ),
			status: self::nullable_string( $row['status'] ?? null ),
			date: self::nullable_string( $row['date'] ?? null ),
			date_due: self::nullable_string( $row['date_due'] ?? null ),
			amount: array_key_exists( 'amount', $row ) ? (string) $row['amount'] : null,
			payment_reference: self::nullable_string( $row['payment_reference'] ?? null ),
			purchase_order: self::nullable_string( $row['purchase_order'] ?? null ),
			details: is_object( $details ) || is_string( $details ) ? $details : null,
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

	private static function nullable_string( mixed $value ): ?string {
		if ( null === $value ) {
			return null;
		}
		return is_scalar( $value ) ? (string) $value : null;
	}
}
