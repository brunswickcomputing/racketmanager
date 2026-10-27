<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use Racketmanager\Domain\Shared\Serialized_Column;

/**
 * Data Transfer Object for hydrating a League_Team entity from a league-team row.
 *
 * Strict allowlist of the `league_teams` table columns, the team columns joined by the repository
 * (`title`, `club_id`, `stadium`, `home`, `roster`, `team_type`, `table_id`) and the standings keys
 * persisted inside the serialized `custom` column. `custom` is decoded and unslashed, and its keys
 * take precedence over the table columns (as the legacy row merge did). Unknown row keys are dropped.
 *
 * `null` means "not present in the row": the entity keeps its default for that property.
 */
readonly class League_Team_Hydration_DTO {
	public function __construct(
		public ?int $id = null,
		public ?int $table_id = null,
		public ?int $team_id = null,
		public ?int $league_id = null,
		public ?string $season = null,
		public ?string $title = null,
		public ?string $stadium = null,
		public ?bool $home = null,
		public ?int $club_id = null,
		public ?int $profile = null,
		public ?string $status = null,
		public ?float $points_plus = null,
		public ?float $points_minus = null,
		public ?int $points_2_plus = null,
		public ?int $points_2_minus = null,
		public ?float $add_points = null,
		public ?int $done_matches = null,
		public ?int $won_matches = null,
		public ?int $draw_matches = null,
		public ?int $lost_matches = null,
		public int|string|null $diff = null,
		public ?string $group = null,
		public ?int $rank = null,
		public ?int $old_rank = null,
		public ?float $rating = null,
		public ?int $captain = null,
		public ?int $captain_id = null,
		public ?string $captain_name = null,
		public ?string $contactno = null,
		public ?string $contactemail = null,
		public ?string $match_day = null,
		public ?string $match_time = null,
		public ?string $team_type = null,
		public ?string $league_status = null,
		public array $custom = [],
		public mixed $roster = null,
		public ?array $players = null,
		public ?int $sets_won = null,
		public ?int $sets_allowed = null,
		public ?int $games_won = null,
		public ?int $games_allowed = null,
		public ?string $sets_shared = null,
		public ?string $no_team = null,
		public ?string $no_player = null,
		public ?int $rubbers_won = null,
		public ?int $rubbers_shared = null,
		public ?int $matches_won = null,
		public ?int $matches_shared = null,
		public ?array $straight_set = null,
		public ?array $split_set = null
	) {
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$row    = (array) $data;
		$custom = empty( $row['custom'] ) ? array() : Serialized_Column::unslash( Serialized_Column::decode_array( $row['custom'] ) );
		$row    = array_merge( $row, $custom );
		$row    = array_combine( array_map( 'trim', array_keys( $row ) ), array_values( $row ) );

		return new self(
			id: self::nullable_int( $row['id'] ?? null ),
			table_id: self::int( $row['table_id'] ?? null ),
			team_id: self::nullable_int( $row['team_id'] ?? null ),
			league_id: self::int( $row['league_id'] ?? null ),
			season: self::string( $row['season'] ?? null ),
			title: self::string( $row['title'] ?? null ),
			stadium: self::string( $row['stadium'] ?? null ),
			home: self::bool( $row['home'] ?? null ),
			club_id: self::int( $row['club_id'] ?? null ),
			// Legacy parity: a present column (even '' or NULL) was coerced with (int) => Team_Profile::PENDING (0).
			profile: array_key_exists( 'profile', $row ) ? (int) $row['profile'] : null,
			status: self::string( $row['status'] ?? null ),
			points_plus: self::float( $row['points_plus'] ?? null ),
			points_minus: self::float( $row['points_minus'] ?? null ),
			points_2_plus: self::int( $row['points_2_plus'] ?? null ),
			points_2_minus: self::int( $row['points_2_minus'] ?? null ),
			add_points: self::float( $row['add_points'] ?? null ),
			done_matches: self::int( $row['done_matches'] ?? null ),
			won_matches: self::int( $row['won_matches'] ?? null ),
			draw_matches: self::int( $row['draw_matches'] ?? null ),
			lost_matches: self::int( $row['lost_matches'] ?? null ),
			diff: self::diff( $row['diff'] ?? null ),
			group: self::string( $row['group'] ?? null ),
			rank: self::nullable_int( $row['rank'] ?? null ),
			old_rank: self::nullable_int( $row['old_rank'] ?? null ),
			rating: self::nullable_float( $row['rating'] ?? null ),
			captain: self::nullable_int( $row['captain'] ?? null ),
			captain_id: self::nullable_int( $row['captain_id'] ?? null ),
			captain_name: self::string( $row['captain_name'] ?? null ),
			contactno: self::string( $row['contactno'] ?? null ),
			contactemail: self::string( $row['contactemail'] ?? null ),
			match_day: self::string( $row['match_day'] ?? null ),
			match_time: self::string( $row['match_time'] ?? null ),
			team_type: self::string( $row['team_type'] ?? null ),
			league_status: self::string( $row['league_status'] ?? null ),
			custom: $custom,
			roster: array_key_exists( 'roster', $row ) ? Serialized_Column::decode_legacy( $row['roster'] ) : null,
			players: isset( $row['players'] ) && is_array( $row['players'] ) ? $row['players'] : null,
			sets_won: self::int( $row['sets_won'] ?? null ),
			sets_allowed: self::int( $row['sets_allowed'] ?? null ),
			games_won: self::int( $row['games_won'] ?? null ),
			games_allowed: self::int( $row['games_allowed'] ?? null ),
			sets_shared: self::string( $row['sets_shared'] ?? null ),
			no_team: self::string( $row['no_team'] ?? null ),
			no_player: self::string( $row['no_player'] ?? null ),
			rubbers_won: self::int( $row['rubbers_won'] ?? null ),
			rubbers_shared: self::int( $row['rubbers_shared'] ?? null ),
			matches_won: self::int( $row['matches_won'] ?? null ),
			matches_shared: self::int( $row['matches_shared'] ?? null ),
			straight_set: isset( $row['straight_set'] ) ? (array) $row['straight_set'] : null,
			split_set: isset( $row['split_set'] ) ? (array) $row['split_set'] : null
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

	/** Integer column backing a non-nullable entity property: `''` becomes 0, absent/non-numeric becomes null. */
	private static function int( mixed $value ): ?int {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0;
		}
		return is_numeric( $value ) ? (int) $value : null;
	}

	/** Integer column backing a nullable entity property: `''` becomes 0; null and absent remain null. */
	private static function nullable_int( mixed $value ): ?int {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0;
		}
		return is_numeric( $value ) ? (int) $value : null;
	}

	/** Float column backing a non-nullable entity property: `''` becomes 0.0. */
	private static function float( mixed $value ): ?float {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0.0;
		}
		return is_numeric( $value ) ? (float) $value : null;
	}

	/** Float column backing a nullable entity property: `''` becomes 0.0; null and absent remain null. */
	private static function nullable_float( mixed $value ): ?float {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return 0.0;
		}
		return is_numeric( $value ) ? (float) $value : null;
	}

	/** String column: scalars are cast, absent/non-scalar becomes null. */
	private static function string( mixed $value ): ?string {
		return is_scalar( $value ) ? (string) $value : null;
	}

	/** Boolean column: `''` becomes false, numeric/bool values are cast, absent becomes null. */
	private static function bool( mixed $value ): ?bool {
		if ( null === $value ) {
			return null;
		}
		if ( '' === $value ) {
			return false;
		}
		return is_bool( $value ) || is_numeric( $value ) ? (bool) $value : null;
	}

	/** `diff` keeps the legacy `string|int` shape (the entity prefixes positive values with `+`). */
	private static function diff( mixed $value ): int|string|null {
		if ( null === $value ) {
			return null;
		}
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_float( $value ) ) {
			return (int) $value;
		}
		return is_scalar( $value ) ? (string) $value : null;
	}
}
