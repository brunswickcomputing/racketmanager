<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Result;

/**
 * DTO for Result Checker data
 */
readonly class Results_Checker_Data {
    /**
     * @param int|null    $id           ID.
     * @param int|null    $league_id    League ID.
     * @param int|null    $match_id     Match ID.
     * @param int|null    $team_id      Team ID.
     * @param int|null    $player_id    Player ID.
     * @param int|null    $rubber_id    Rubber ID.
     * @param string|null $description  Description.
     * @param int|null    $status       Status.
     * @param int|null    $updated_user Updated user.
     * @param string|null $updated_date Updated date.
     */
    public function __construct(
        public ?int $id = null,
        public ?int $league_id = null,
        public ?int $match_id = null,
        public ?int $team_id = null,
        public ?int $player_id = null,
        public ?int $rubber_id = null,
        public ?string $description = null,
        public ?int $status = null,
        public ?int $updated_user = null,
        public ?string $updated_date = null,
    ) {}

    /**
     * Create from a generic object (e.g., database row).
     *
     * Database rows deliver every column as a string; `''`/`null` become `null` for nullable
     * identifiers, mirroring the legacy Results_Checker constructor.
     *
     * @param object $data
     * @return self
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->id ?? null ),
            league_id: self::nullable_int( $data->league_id ?? null ),
            match_id: self::nullable_int( $data->match_id ?? null ),
            team_id: self::nullable_int( $data->team_id ?? null ),
            player_id: self::nullable_int( $data->player_id ?? null ),
            rubber_id: self::nullable_int( $data->rubber_id ?? null ),
            description: isset( $data->description ) ? (string) $data->description : null,
            status: self::nullable_int( $data->status ?? null ),
            updated_user: self::nullable_int( $data->updated_user ?? null ),
            updated_date: isset( $data->updated_date ) ? (string) $data->updated_date : null,
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
}
