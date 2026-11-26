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
    /**
     * Creates a new instance with match rubber details, scoring data, player assignments, and status flags.
     *
     * @param int|false|null $id Unique identifier, false, or null when not set.
     * @param int|null       $match_id Associated match identifier.
     * @param int|null       $status Status identifier for the rubber.
     * @param mixed          $custom Custom data associated with the rubber.
     * @param mixed          $sets Set data associated with the rubber.
     * @param string|null    $date Date of the rubber.
     * @param float|null     $home_points Points awarded to the home side.
     * @param float|null     $away_points Points awarded to the away side.
     * @param array|null     $players Player data assigned to the rubber.
     * @param string|null    $type Rubber type.
     * @param int|null       $rubber_number Rubber number within the match.
     * @param int|null       $winner_id Identifier of the winning participant.
     * @param int|null       $loser_id Identifier of the losing participant.
     * @param bool|null      $reverse_rubbers Whether rubbers should be reversed.
     * @param bool|null      $reverse_rubber Whether this rubber should be reversed.
     * @param array|null     $stats Statistics associated with the rubber.
     * @param string|null    $class CSS or display class value.
     * @param string|null    $group Group identifier or label.
     * @param int|null       $post_id Associated post identifier.
     * @param string|null    $walkover Walkover status or value.
     * @param string|null    $invalid Invalid status or value.
     * @param string|null    $abandoned Abandoned status or value.
     * @param string|null    $share Share status or value.
     * @param string|null    $cancelled Cancelled status or value.
     * @param string|null    $retired Retired status or value.
     *
     * @return void
     */
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
        public ?string $class = null, // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
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
     * Creates an instance from an object containing rubber data, decoding serialized custom values and normalising field types.
     *
     * @param object $data Source object containing rubber properties and optional serialized custom data.
     *
     * @return self New instance populated with normalized values from the source object.
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
     * Creates an instance from an associative array of data.
     *
     * @param array $data Source data used to create the instance.
     *
     * @return self New instance populated from the provided array data.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a mixed identifier value to an integer or null while preserving false values.
     *
     * @param mixed $value Identifier value to normalize.
     *
     * @return int|false|null Normalized integer identifier, false when explicitly provided, or null when not convertible.
     */
    private static function id( mixed $value ): int|false|null {
        if ( false === $value ) {
            return false;
        }
        return self::int( $value );
    }

    /**
     * Converts a mixed value to an integer when possible.
     *
     * @param mixed $value Value to convert.
     *
     * @return int|null Converted integer, zero for an empty string, or null when the value is null or not numeric.
     */
    private static function int( mixed $value ): ?int {
        if ( null === $value ) {
            return null;
        }
        if ( '' === $value ) {
            return 0;
        }
        return is_numeric( $value ) ? (int) $value : null;
    }

    /**
     * Converts the given value to an integer or null.
     *
     * @param mixed $value Value to convert.
     *
     * @return int|null Converted integer value, or null when the value cannot be converted.
     */
    private static function nullable_int( mixed $value ): ?int {
        return self::int( $value );
    }

    /**
     * Converts a nullable mixed value to a float when numeric.
     *
     * @param mixed $value Value to convert, with null returning null and an empty string returning 0.0.
     *
     * @return float|null Converted float value, or null when the value is null or not numeric.
     */
    private static function nullable_float( mixed $value ): ?float {
        if ( null === $value ) {
            return null;
        }
        if ( '' === $value ) {
            return 0.0;
        }
        return is_numeric( $value ) ? (float) $value : null;
    }

    /**
     * Converts a scalar value to a string, or returns null when the value is not scalar.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null String representation of the scalar value, or null for non-scalar values.
     */
    private static function string( mixed $value ): ?string {
        return is_scalar( $value ) ? (string) $value : null;
    }
}
