<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Data Transfer Object for hydrating a Club entity from database rows or input data.
 */
readonly class Club_Hydration_DTO {
    /**
     * Creates a new instance with club, team, player, match, result, entry, competition, and match secretary data.
     *
     * @param int|null    $id Unique identifier.
     * @param string      $name Name.
     * @param string|null $shortcode Short code.
     * @param string|null $type Type.
     * @param string|null $website Website URL.
     * @param string|null $contactno Contact number.
     * @param int|null    $founded Founded year.
     * @param string|null $facilities Facilities description.
     * @param string|null $address Address.
     * @param int         $team_count Number of teams.
     * @param int         $player_count Number of players.
     * @param array       $players Player data.
     * @param array       $teams Team data.
     * @param array       $matches Match data.
     * @param array       $results Result data.
     * @param string|null $created_date Creation date.
     * @param string|null $removed_date Removal date.
     * @param bool        $single Whether the instance represents a single record.
     * @param array       $entry Entry data.
     * @param array       $competitions Competition data.
     * @param object|null $match_secretary Match secretary data.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public ?string $shortcode = null,
        public ?string $type = null,
        public ?string $website = null,
        public ?string $contactno = null,
        public ?int $founded = null,
        public ?string $facilities = null,
        public ?string $address = null,
        public int $team_count = 0,
        public int $player_count = 0,
        public array $players = [],
        public array $teams = [],
        public array $matches = [],
        public array $results = [],
        public ?string $created_date = null,
        public ?string $removed_date = null,
        public bool $single = false,
        public array $entry = [],
        public array $competitions = [],
        public ?object $match_secretary = null
    ) {
    }

    /**
     * Creates an instance from an object, normalising available club-related data and applying defaults for missing values.
     *
     * @param object $data Source object containing club, team, player, match, result, entry, competition, and match secretary data.
     *
     * @return self New instance populated from the source object.
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->id ?? null ),
            name: (string) ( $data->name ?? '' ),
            shortcode: self::nullable_string( $data->shortcode ?? null ),
            type: self::nullable_string( $data->type ?? null ),
            website: self::nullable_string( $data->website ?? null ),
            contactno: self::nullable_string( $data->contactno ?? null ),
            founded: self::nullable_int( $data->founded ?? null ),
            facilities: self::nullable_string( $data->facilities ?? null ),
            address: self::nullable_string( $data->address ?? null ),
            team_count: self::int( $data->team_count ?? null ),
            player_count: self::int( $data->player_count ?? null ),
            players: self::array( $data->players ?? null ),
            teams: self::array( $data->teams ?? null ),
            matches: self::array( $data->matches ?? null ),
            results: self::array( $data->results ?? null ),
            created_date: self::nullable_string( $data->created_date ?? null ),
            removed_date: self::nullable_string( $data->removed_date ?? null ),
            single: ! empty( $data->single ),
            entry: self::array( $data->entry ?? null ),
            competitions: self::array( $data->competitions ?? null ),
            match_secretary: self::object( $data->match_secretary ?? null ),
        );
    }

    /**
     * Creates an instance from an array of data.
     *
     * @param array $data Source data used to create the instance.
     *
     * @return self Created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a numeric value to an integer, or returns null when the value is not numeric.
     *
     * @param mixed $value Value to convert.
     *
     * @return int|null Converted integer, or null for non-numeric values.
     */
    private static function nullable_int( mixed $value ): ?int {
        return is_numeric( $value ) ? (int) $value : null;
    }

    /**
     * Converts a numeric value to an integer, or returns the default value when conversion is not possible.
     *
     * @param mixed $value Value to convert.
     * @param int   $default_value Default value returned when the value is not numeric.
     *
     * @return int Converted integer value or the default value.
     */
    private static function int( mixed $value, int $default_value = 0 ): int {
        return is_numeric( $value ) ? (int) $value : $default_value;
    }

    /**
     * Converts a non-null value to a string while preserving null values.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null String representation of the value, or null when the value is null.
     */
    private static function nullable_string( mixed $value ): ?string {
        return null !== $value ? (string) $value : null;
    }

    /**
     * Converts the given value to an array when possible.
     *
     * @param mixed $value Value to validate as an array.
     *
     * @return array The original array value, or an empty array when the value is not an array.
     */
    private static function array( mixed $value ): array {
        return is_array( $value ) ? $value : [];
    }

    /**
     * Returns the value when it is an object, or null otherwise.
     *
     * @param mixed $value Value to inspect.
     *
     * @return object|null Object value, or null when the value is not an object.
     */
    private static function object( mixed $value ): ?object {
        return is_object( $value ) ? $value : null;
    }
}
