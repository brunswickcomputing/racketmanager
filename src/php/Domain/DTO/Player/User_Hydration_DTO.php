<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for hydrating a User entity.
 *
 * Accepts both the WordPress shape (`ID`, `user_email`, `display_name`, optionally nested under `data`
 * as `WP_User` exposes it) and the plugin shape (`id`, `email`, `fullname`, `favourites`, `club`, …).
 * A non-empty WordPress key wins when both are present; the entity mirrors the resolved value onto its
 * alias (`ID`/`id`, `user_email`/`email`, `display_name`/`fullname`).
 *
 * Strict allowlist of the User entity's declared properties: WordPress capability bags (`caps`,
 * `roles`, `allcaps`, …) and any other unknown key are dropped. `null` means "not present".
 */
readonly class User_Hydration_DTO {
    /**
     * Creates a user data object with identity, profile, account, status, statistics, and validation details.
     *
     * @param int             $id User identifier.
     * @param string          $email User email address.
     * @param string          $fullname User full name.
     * @param string|null     $created_date Date the record was created.
     * @param string|null     $user_registered Date the user was registered.
     * @param mixed           $firstname User first name.
     * @param mixed           $surname User surname.
     * @param mixed           $gender User gender.
     * @param mixed           $type User type.
     * @param mixed           $btm User BTM value.
     * @param mixed           $year_of_birth User year of birth.
     * @param string|int|null $age User age.
     * @param mixed           $contactno User contact number.
     * @param mixed           $removed_date Date the user was removed.
     * @param mixed           $removed_user User responsible for removal.
     * @param mixed           $locked Lock status.
     * @param mixed           $locked_date Date the user was locked.
     * @param mixed           $locked_user User responsible for locking.
     * @param string|null     $locked_user_name Name of the user responsible for locking.
     * @param mixed           $system_record System record flag or value.
     * @param array|null      $matches Match data associated with the user.
     * @param array|null      $statistics Statistics associated with the user.
     * @param string|null     $user_pass User password hash.
     * @param string|null     $user_nicename User nicename.
     * @param string|null     $user_url User URL.
     * @param string|null     $user_activation_key User activation key.
     * @param string|null     $user_login User login name.
     * @param string|null     $user_status User status value.
     * @param mixed           $opt_ins User opt-in data.
     * @param array|null      $wtn WTN data associated with the user.
     * @param string|null     $message Message associated with the user operation.
     * @param string|null     $update_result Result of the update operation.
     * @param array|null      $err_flds Validation error fields.
     * @param array|null      $err_msgs Validation error messages.
     * @param string|null     $password Plain password value.
     * @param string|null     $re_password Re-entered password value.
     * @param array|null      $favourites User favourites.
     * @param string|null     $status Current status.
     * @param int|null        $entry_id Entry identifier.
     * @param object|null     $club Club associated with the user.
     * @param string|null     $index Index value.
     *
     * @return void
     */
    public function __construct(
        public int $id = 0,
        public string $email = '',
        public string $fullname = '',
        public ?string $created_date = null,
        public ?string $user_registered = null,
        public mixed $firstname = null,
        public mixed $surname = null,
        public mixed $gender = null,
        public mixed $type = null,
        public mixed $btm = null,
        public mixed $year_of_birth = null,
        public string|int|null $age = null,
        public mixed $contactno = null,
        public mixed $removed_date = null,
        public mixed $removed_user = null,
        public mixed $locked = null,
        public mixed $locked_date = null,
        public mixed $locked_user = null,
        public ?string $locked_user_name = null,
        public mixed $system_record = null,
        public ?array $matches = null,
        public ?array $statistics = null,
        public ?string $user_pass = null,
        public ?string $user_nicename = null,
        public ?string $user_url = null,
        public ?string $user_activation_key = null,
        public ?string $user_login = null,
        public ?string $user_status = null,
        public mixed $opt_ins = null,
        public ?array $wtn = null,
        public ?string $message = null,
        public ?string $update_result = null,
        public ?array $err_flds = null,
        public ?array $err_msgs = null,
        public ?string $password = null,
        public ?string $re_password = null,
        public ?array $favourites = null,
        public ?string $status = null,
        public ?int $entry_id = null,
        public ?object $club = null,
        public ?string $index = null,
    ) {
    }

    /**
     * Creates an instance from an object by normalising its public values and supported user data aliases.
     *
     * @param object $data Source object containing user data.
     *
     * @return self Normalized user data instance.
     */
    public static function from_object( object $data ): self {
        $row = get_object_vars( $data );
        if ( isset( $row['data'] ) && is_object( $row['data'] ) ) {
            // WP_User keeps the user table columns under ->data; top-level keys take precedence.
            $row = array_merge( get_object_vars( $row['data'] ), $row );
        }

        return new self(
            id: (int) self::alias( $row, 'ID', 'id', 0 ),
            email: self::string( self::alias( $row, 'user_email', 'email', '' ) ) ?? '',
            fullname: self::string( self::alias( $row, 'display_name', 'fullname', '' ) ) ?? '',
            created_date: self::string( $row['created_date'] ?? null ),
            user_registered: self::string( $row['user_registered'] ?? null ),
            firstname: $row['firstname'] ?? null,
            surname: $row['surname'] ?? null,
            gender: $row['gender'] ?? null,
            type: $row['type'] ?? null,
            btm: $row['btm'] ?? null,
            year_of_birth: $row['year_of_birth'] ?? null,
            age: self::age( $row['age'] ?? null ),
            contactno: $row['contactno'] ?? null,
            removed_date: $row['removed_date'] ?? null,
            removed_user: $row['removed_user'] ?? null,
            locked: $row['locked'] ?? null,
            locked_date: $row['locked_date'] ?? null,
            locked_user: $row['locked_user'] ?? null,
            locked_user_name: self::string( $row['locked_user_name'] ?? null ),
            system_record: $row['system_record'] ?? null,
            matches: self::array( $row['matches'] ?? null ),
            statistics: self::array( $row['statistics'] ?? null ),
            user_pass: self::string( $row['user_pass'] ?? null ),
            user_nicename: self::string( $row['user_nicename'] ?? null ),
            user_url: self::string( $row['user_url'] ?? null ),
            user_activation_key: self::string( $row['user_activation_key'] ?? null ),
            user_login: self::string( $row['user_login'] ?? null ),
            user_status: self::string( $row['user_status'] ?? null ),
            opt_ins: $row['opt_ins'] ?? null,
            wtn: self::array( $row['wtn'] ?? null ),
            message: self::string( $row['message'] ?? null ),
            update_result: self::string( $row['update_result'] ?? null ),
            err_flds: self::array( $row['err_flds'] ?? null ),
            err_msgs: self::array( $row['err_msgs'] ?? null ),
            password: self::string( $row['password'] ?? null ),
            re_password: self::string( $row['re_password'] ?? null ),
            favourites: self::array( $row['favourites'] ?? null ),
            status: self::string( $row['status'] ?? null ),
            entry_id: isset( $row['entry_id'] ) && is_numeric( $row['entry_id'] ) ? (int) $row['entry_id'] : null,
            club: isset( $row['club'] ) && is_object( $row['club'] ) ? $row['club'] : null,
            index: self::string( $row['index'] ?? null ),
        );
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data Source data used to populate the instance.
     *
     * @return self Created instance populated from the provided data.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Returns a value from a row using a preferred WordPress key, falling back to a plugin key or default value.
     *
     * @param array  $row Source row containing possible keyed values.
     * @param string $wordpress_key Preferred WordPress field key.
     * @param string $plugin_key Fallback plugin field key.
     * @param mixed  $default_value Default value returned when neither key contains a usable value.
     *
     * @return mixed Resolved value from the row or the default value.
     */
    private static function alias( array $row, string $wordpress_key, string $plugin_key, mixed $default_value ): mixed {
        if ( ! empty( $row[ $wordpress_key ] ) ) {
            return $row[ $wordpress_key ];
        }
        return $row[ $plugin_key ] ?? $row[ $wordpress_key ] ?? $default_value;
    }

    /**
     * Converts a scalar value to a string, or returns null for non-scalar values.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null String representation of the value, or null when the value is not scalar.
     */
    private static function string( mixed $value ): ?string {
        return is_scalar( $value ) ? (string) $value : null;
    }

    /**
     * Converts a value to an array when it is already an array, otherwise returns null.
     *
     * @param mixed $value Value to validate as an array.
     *
     * @return array|null The original array value, or null when the value is not an array.
     */
    private static function array( mixed $value ): ?array {
        return is_array( $value ) ? $value : null;
    }

    /**
     * Normalises an age value to an integer, string, or null.
     *
     * @param mixed $value Age value to normalize.
     *
     * @return string|int|null Normalized age value, or null when the value cannot be represented as a scalar.
     */
    private static function age( mixed $value ): string|int|null {
        if ( is_int( $value ) || is_string( $value ) ) {
            return $value;
        }
        return is_scalar( $value ) ? (string) $value : null;
    }
}
