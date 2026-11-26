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
     * Creates a new message data object.
     *
     * @param int|null    $id Message identifier.
     * @param string|null $subject Message subject.
     * @param int|null    $userid User identifier associated with the message.
     * @param string|null $status Message status.
     * @param string|null $date Message date.
     * @param string|null $sender Message sender.
     * @param string|null $message_object Message content or object reference.
     *
     * @return void
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
     * Creates an instance from a generic data object.
     *
     * @param object $data Source object containing message data.
     *
     * @return self New message data object instance.
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
     * Creates an instance from an associative array of data.
     *
     * @param array $data Data used to populate the object.
     *
     * @return self Created object instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a nullable value to an integer.
     *
     * @param mixed $value Value to convert, or null to preserve as null.
     *
     * @return int|null Converted integer value, zero for an empty string, or null when the input is null.
     */
    private static function nullable_int( mixed $value ): ?int {
        if ( null === $value ) {
            return null;
        }
        if ( '' === $value ) {
            return 0;
        }
        return (int) $value;
    }

    /**
     * Converts a nullable scalar value to a string.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null String representation of the value, or null when the value is null or non-scalar.
     */
    private static function nullable_string( mixed $value ): ?string {
        if ( null === $value ) {
            return null;
        }
        return is_scalar( $value ) ? (string) $value : null;
    }
}
