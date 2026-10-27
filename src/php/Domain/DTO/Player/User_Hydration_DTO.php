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
	 * Create from a WordPress user, a database row, a plugin-shaped object or a User entity.
	 *
	 * @param object $data
	 * @return self
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
	 * Create from an associative array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return self::from_object( (object) $data );
	}

	/** Resolve an aliased column: the WordPress key when non-empty, otherwise the plugin key, otherwise the default. */
	private static function alias( array $row, string $wordpress_key, string $plugin_key, mixed $default ): mixed {
		if ( ! empty( $row[ $wordpress_key ] ) ) {
			return $row[ $wordpress_key ];
		}
		return $row[ $plugin_key ] ?? $row[ $wordpress_key ] ?? $default;
	}

	/** String column: scalars are cast, absent/non-scalar becomes null. */
	private static function string( mixed $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}

	/** Array column: arrays pass through, absent/non-array becomes null. */
	private static function array( mixed $value ): ?array {
		return is_array( $value ) ? $value : null;
	}

	/** `age` keeps the legacy `string|int` shape. */
	private static function age( mixed $value ): string|int|null {
		if ( is_int( $value ) || is_string( $value ) ) {
			return $value;
		}
		return is_scalar( $value ) ? (string) $value : null;
	}
}
