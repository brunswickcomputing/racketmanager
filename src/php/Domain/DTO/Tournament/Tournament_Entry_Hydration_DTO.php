<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Data Transfer Object for hydrating a Tournament_Entry entity from database rows or input data.
 *
 * Strict allowlist of the tournament entries table columns. Unknown row keys are dropped.
 */
readonly class Tournament_Entry_Hydration_DTO {
    /**
     * Construct hydration DTO.
     *
     * @param int|null              $id            Entry ID.
     * @param int|null              $tournament_id Tournament ID.
     * @param int|null              $player_id     Player ID.
     * @param int|null              $status        Entry status.
     * @param string|int|float|null $fee           Entry fee raw value (string decimal, int, float, or null).
     * @param int|null              $club_id       Club ID.
     */
    public function __construct(
        public ?int $id = null,
        public ?int $tournament_id = null,
        public ?int $player_id = null,
        public ?int $status = null,
        public string|int|float|null $fee = null,
        public ?int $club_id = null,
    ) {
    }

    /**
     * Create from a generic object (e.g., database row).
     *
     * @param object $data Raw database row or object.
     * @return self
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->id ?? null ),
            tournament_id: self::nullable_int( $data->tournament_id ?? null ),
            player_id: self::nullable_int( $data->player_id ?? null ),
            status: self::nullable_int( $data->status ?? null ),
            fee: self::nullable_fee( $data->fee ?? null ),
            club_id: self::nullable_int( $data->club_id ?? null ),
        );
    }

    /**
     * Create from an array.
     *
     * @param array $data Raw array data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Convert mixed value to nullable int.
     *
     * @param mixed $value Input value.
     * @return int|null
     */
    private static function nullable_int( mixed $value ): ?int {
        if ( null === $value || '' === $value ) {
            return null;
        }
        return (int) $value;
    }

    /**
     * Convert mixed fee value to nullable string, int, or float.
     *
     * Preserves raw decimal values like '15.50' or floats/ints without truncation.
     *
     * @param mixed $value Input value.
     * @return string|int|float|null
     */
    private static function nullable_fee( mixed $value ): string|int|float|null {
        if ( null === $value || '' === $value ) {
            return null;
        }
        if ( is_int( $value ) || is_float( $value ) || is_string( $value ) ) {
            return $value;
        }
        return (string) $value;
    }
}
