<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Data Transfer Object for hydrating a Club_Role entity from a row.
 *
 * Strict allowlist of the `club_roles` table columns plus the `role`, `club` and `user` objects
 * callers may attach. Unknown row keys are dropped. `null` means "not present in the row".
 */
readonly class Club_Role_Hydration_DTO {
    /**
     * Creates a new instance with optional user, club, role, and related object data.
     *
     * @param int|null    $id The identifier.
     * @param int|null    $user_id The user identifier.
     * @param int|null    $club_id The club identifier.
     * @param int|null    $role_id The role identifier.
     * @param object|null $role The related role object.
     * @param object|null $club The related club object.
     * @param object|null $user The related user object.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public ?int $user_id = null,
        public ?int $club_id = null,
        public ?int $role_id = null,
        public ?object $role = null,
        public ?object $club = null,
        public ?object $user = null,
    ) {
    }

    /**
     * Creates an instance from an object containing identifier and related entity data.
     *
     * @param object $data The source object containing id, user_id, club_id, role_id, role, club, and user values.
     *
     * @return self The created instance.
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->id ?? null ),
            user_id: self::int( $data->user_id ?? null ),
            club_id: self::int( $data->club_id ?? null ),
            role_id: self::int( $data->role_id ?? null ),
            role: self::object( $data->role ?? null ),
            club: self::object( $data->club ?? null ),
            user: self::object( $data->user ?? null ),
        );
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data The data used to create the instance.
     *
     * @return self The created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a mixed value to an integer when possible.
     *
     * Returns null for null or non-numeric values, returns 0 for an empty string,
     * and casts numeric values to an integer.
     *
     * @param mixed $value The value to convert.
     *
     * @return int|null The converted integer, or null when the value cannot be converted.
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
     * Converts a mixed value to a nullable integer.
     *
     * @param mixed $value The value to convert.
     *
     * @return int|null The converted integer value, or null when the value cannot be converted.
     */
    private static function nullable_int( mixed $value ): ?int {
        return self::int( $value );
    }

    /**
     * Returns the given value when it is an object, or null otherwise.
     *
     * @param mixed $value The value to check.
     *
     * @return object|null The object value, or null if the value is not an object.
     */
    private static function object( mixed $value ): ?object {
        return is_object( $value ) ? $value : null;
    }
}
