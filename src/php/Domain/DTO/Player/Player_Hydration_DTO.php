<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for hydrating a Player entity from raw database row, user object or user meta.
 */
readonly class Player_Hydration_DTO {
    /**
     * Creates a user data object with profile, account, match, competition, and entry details.
     *
     * @param int|null    $id User identifier.
     * @param string|null $email User email address.
     * @param string|null $display_name User display name.
     * @param string|null $user_registered User registration date.
     * @param string|null $firstname User first name.
     * @param string|null $surname User surname.
     * @param string|null $gender User gender.
     * @param string|null $type User type.
     * @param string|null $btm User BTM identifier.
     * @param int|null    $year_of_birth User year of birth.
     * @param int|null    $age User age.
     * @param string|null $contactno User contact number.
     * @param string|null $removed_date Date the user was removed.
     * @param int|null    $removed_user Identifier of the user who removed this user.
     * @param mixed       $locked Lock status or lock metadata.
     * @param string|null $locked_date Date the user was locked.
     * @param string|null $locked_user Identifier or name of the user who locked this user.
     * @param string|null $locked_user_name Display name of the user who locked this user.
     * @param string|null $system_record System record flag or identifier.
     * @param array       $matches Match data associated with the user.
     * @param array       $statistics Statistics associated with the user.
     * @param string|null $link User profile or related link.
     * @param array       $opt_ins User opt-in preferences.
     * @param array       $wtn WTN data associated with the user.
     * @param string|null $user_pass User password hash.
     * @param string|null $user_nicename User nicename.
     * @param string|null $user_url User URL.
     * @param string|null $user_activation_key User activation key.
     * @param string|null $user_login User login name.
     * @param string|null $index Index value.
     * @param array       $stats Additional user statistics.
     * @param int         $matches_won Number of matches won.
     * @param int         $matches_lost Number of matches lost.
     * @param float|null  $win_pct Win percentage.
     * @param int         $played Number of matches played.
     * @param array       $competitions Competition data associated with the user.
     * @param array       $cup Cup competition data.
     * @param array       $league League competition data.
     * @param array       $tournament Tournament competition data.
     * @param array       $teams Teams associated with the user.
     * @param object|null $club Club associated with the user.
     * @param object|null $team Team associated with the user.
     * @param string|null $status User status.
     * @param string|null $user_status Account status.
     * @param int|null    $entry_id Entry identifier.
     * @param array       $entry Entry data.
     * @param object|null $tournament_entry Tournament entry data.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?string $display_name = null,
        public ?string $user_registered = null,
        public ?string $firstname = null,
        public ?string $surname = null,
        public ?string $gender = null,
        public ?string $type = null,
        public ?string $btm = null,
        public ?int $year_of_birth = null,
        public ?int $age = null,
        public ?string $contactno = null,
        public ?string $removed_date = null,
        public ?int $removed_user = null,
        public mixed $locked = null,
        public ?string $locked_date = null,
        public ?string $locked_user = null,
        public ?string $locked_user_name = null,
        public ?string $system_record = null,
        public array $matches = [],
        public array $statistics = [],
        public ?string $link = null,
        public array $opt_ins = [],
        public array $wtn = [],
        public ?string $user_pass = null,
        public ?string $user_nicename = null,
        public ?string $user_url = null,
        public ?string $user_activation_key = null,
        public ?string $user_login = null,
        public ?string $index = null,
        public array $stats = [],
        public int $matches_won = 0,
        public int $matches_lost = 0,
        public ?float $win_pct = null,
        public int $played = 0,
        public array $competitions = [],
        public array $cup = [],
        public array $league = [],
        public array $tournament = [],
        public array $teams = [],
        public ?object $club = null,
        public ?object $team = null,
        public ?string $status = null,
        public ?string $user_status = null,
        public ?int $entry_id = null,
        public array $entry = [],
        public ?object $tournament_entry = null
    ) {
    }

    /**
     * Creates an instance from a source object by normalising supported user, profile, match, competition, and entry fields.
     *
     * @param object $data Source object containing user data and related metadata.
     *
     * @return self Normalized user data instance.
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->ID ?? $data->id ?? null ),
            email: self::nullable_string( $data->user_email ?? $data->email ?? null ),
            display_name: self::nullable_string( $data->display_name ?? $data->fullname ?? $data->name ?? null ),
            user_registered: self::nullable_string( $data->user_registered ?? null ),
            firstname: self::nullable_string( $data->firstname ?? $data->first_name ?? null ),
            surname: self::nullable_string( $data->surname ?? $data->last_name ?? null ),
            gender: self::nullable_string( $data->gender ?? null ),
            type: self::nullable_string( $data->type ?? null ),
            btm: self::nullable_string( $data->btm ?? null ),
            year_of_birth: self::resolve_year_of_birth( $data->year_of_birth ?? null ),
            age: self::nullable_int( $data->age ?? null ),
            contactno: self::nullable_string( $data->contactno ?? null ),
            removed_date: self::nullable_string( $data->removed_date ?? null ),
            removed_user: self::nullable_int( $data->removed_user ?? null ),
            locked: $data->locked ?? null,
            locked_date: self::nullable_string( $data->locked_date ?? null ),
            locked_user: self::nullable_string( $data->locked_user ?? null ),
            locked_user_name: self::nullable_string( $data->locked_user_name ?? null ),
            system_record: self::nullable_string( $data->system_record ?? null ),
            matches: self::array( $data->matches ?? null ),
            statistics: self::array( $data->statistics ?? null ),
            link: self::nullable_string( $data->link ?? null ),
            opt_ins: self::resolve_opt_ins( $data->opt_ins ?? null ),
            wtn: self::array( $data->wtn ?? null ),
            user_pass: self::nullable_string( $data->user_pass ?? null ),
            user_nicename: self::nullable_string( $data->user_nicename ?? null ),
            user_url: self::nullable_string( $data->user_url ?? null ),
            user_activation_key: self::nullable_string( $data->user_activation_key ?? null ),
            user_login: self::nullable_string( $data->user_login ?? null ),
            index: self::nullable_string( $data->index ?? null ),
            stats: self::array( $data->stats ?? null ),
            matches_won: self::int( $data->matches_won ?? null ),
            matches_lost: self::int( $data->matches_lost ?? null ),
            win_pct: self::nullable_float( $data->win_pct ?? null ),
            played: self::int( $data->played ?? null ),
            competitions: self::array( $data->competitions ?? null ),
            cup: self::array( $data->cup ?? null ),
            league: self::array( $data->league ?? null ),
            tournament: self::array( $data->tournament ?? null ),
            teams: self::array( $data->teams ?? null ),
            club: self::nullable_object( $data->club ?? null ),
            team: self::nullable_object( $data->team ?? null ),
            status: self::nullable_string( $data->status ?? null ),
            user_status: self::nullable_string( $data->user_status ?? null ),
            entry_id: self::nullable_int( $data->entry_id ?? null ),
            entry: self::array( $data->entry ?? null ),
            tournament_entry: self::nullable_object( $data->tournament_entry ?? null ),
        );
    }

    /**
     * Resolves raw opt-in data into a normalised array of opt-in values.
     *
     * @param mixed $raw Raw opt-in data as an array, non-empty string, or other value.
     *
     * @return array Normalized list of opt-in values, or an empty array when no opt-ins are available.
     */
    private static function resolve_opt_ins( mixed $raw ): array {
        if ( is_array( $raw ) ) {
            return array_values( $raw );
        }

        if ( is_string( $raw ) && '' !== $raw ) {
            return [ $raw ];
        }

        return [];
    }

    /**
     * Resolves a year of birth value to an integer, or null when the value is not set or empty.
     *
     * @param mixed $value Year of birth value to resolve.
     *
     * @return int|null Resolved year of birth, or null when no valid value is provided.
     */
    private static function resolve_year_of_birth( mixed $value ): ?int {
        return isset( $value ) && '' !== $value ? (int) $value : null;
    }

    /**
     * Converts a nullable value to a string while preserving null values.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null String representation of the value, or null when the value is null
     */
    private static function nullable_string( mixed $value ): ?string {
        return null !== $value ? (string) $value : null;
    }

    /**
     * Converts a numeric value to an integer, or returns null when the value is not numeric.
     *
     * @param mixed $value Value to convert.
     *
     * @return int|null Integer value when numeric, otherwise null.
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
     * Converts a numeric value to a float, or returns null when the value is not numeric.
     *
     * @param mixed $value Value to convert.
     *
     * @return float|null Converted float value, or null if the value is not numeric.
     */
    private static function nullable_float( mixed $value ): ?float {
        return is_numeric( $value ) ? (float) $value : null;
    }

    /**
     * Converts a value to an array, returning an empty array when the value is not an array.
     *
     * @param mixed $value Value to validate as an array.
     *
     * @return array The original array value, or an empty array if the value is not an array.
     */
    private static function array( mixed $value ): array {
        return is_array( $value ) ? $value : [];
    }

    /**
     * Returns the value when it is an object, or null otherwise.
     *
     * @param mixed $value Value to evaluate.
     *
     * @return object|null Object value when provided, or null for non-object values.
     */
    private static function nullable_object( mixed $value ): ?object {
        return is_object( $value ) ? $value : null;
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data Data used to populate the instance.
     *
     * @return self Instance created from the provided array data.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }
}
