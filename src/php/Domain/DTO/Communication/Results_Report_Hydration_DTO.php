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
     * Creates a new instance with the provided match result data.
     *
     * @param int|null          $id The identifier of the record.
     * @param int|null          $match_id The related match identifier.
     * @param string|false|null $result_object The serialized or encoded result object, false on failure, or null when unavailable.
     * @param mixed             $data Additional data associated with the result.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public ?int $match_id = null,
        public string|false|null $result_object = null,
        public mixed $data = null,
    ) {
    }

    /**
     * Creates an instance from an object containing match result values.
     *
     * @param object $data Source object containing the identifier, match identifier, result object, and payload data.
     *
     * @return self The created instance populated from the provided object.
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
     * Creates an instance from the provided associative array data.
     *
     * @param array $data The data used to populate the instance.
     *
     * @return self The created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a nullable mixed value to an integer.
     *
     * @param mixed $value The value to convert, or null to preserve as null.
     *
     * @return int|null The converted integer value, or null when the input is null.
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
}
