<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Communication;

/**
 * Data Transfer Object for hydrating a Message entity from database rows or input data.
 *
 * Strict allowlist of the messages table columns. Unknown row keys are dropped.
 * Coercion mirrors Typed_Property_Hydrator: `''` becomes `0` for integer columns, `null` is kept as
 * "absent" so the entity property stays at its default.
 */
readonly class Message_Hydration_DTO {
	/**
	 * @param int|null    $id
	 * @param string|null $subject
	 * @param int|null    $userid
	 * @param string|null $status
	 * @param string|null $date
	 * @param string|null $sender
	 * @param string|null $message_object
	 */
	public function __construct(
		public ?int $id = null,
		public ?string $subject = null,
		public ?int $userid = null,
		public ?string $status = null,
		public ?string $date = null,
		public ?string $sender = null,
		public ?string $message_object = null,
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
			subject: self::nullable_string( $data->subject ?? null ),
			userid: self::nullable_int( $data->userid ?? null ),
			status: self::nullable_string( $data->status ?? null ),
			date: self::nullable_string( $data->date ?? null ),
			sender: self::nullable_string( $data->sender ?? null ),
			message_object: self::nullable_string( $data->message_object ?? null ),
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

	private static function nullable_string( mixed $value ): ?string {
		if ( null === $value ) {
			return null;
		}
		return is_scalar( $value ) ? (string) $value : null;
	}
}
