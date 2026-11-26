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
    /**
     * Creates a team table entry with league, ranking, contact, scheduling, roster, and match statistics data.
     *
     * @param int|null        $id Entry identifier.
     * @param int|null        $table_id Table identifier.
     * @param int|null        $team_id Team identifier.
     * @param int|null        $league_id League identifier.
     * @param string|null     $season Season label or identifier.
     * @param string|null     $title Team title or display name.
     * @param string|null     $stadium Stadium or venue name.
     * @param bool|null       $home Whether the team is marked as a home team.
     * @param int|null        $club_id Club identifier.
     * @param int|null        $profile Profile identifier.
     * @param string|null     $status Entry or team status.
     * @param float|null      $points_plus Positive points value.
     * @param float|null      $points_minus Negative points value.
     * @param int|null        $points_2_plus Secondary positive points value.
     * @param int|null        $points_2_minus Secondary negative points value.
     * @param float|null      $add_points Additional points value.
     * @param int|null        $done_matches Number of completed matches.
     * @param int|null        $won_matches Number of won matches.
     * @param int|null        $draw_matches Number of drawn matches.
     * @param int|null        $lost_matches Number of lost matches.
     * @param int|string|null $diff Difference value, as a numeric or formatted value.
     * @param string|null     $group Group name or identifier.
     * @param int|null        $rank Current rank.
     * @param int|null        $old_rank Previous rank.
     * @param float|null      $rating Rating value.
     * @param int|null        $captain Captain flag or identifier.
     * @param int|null        $captain_id Captain identifier.
     * @param string|null     $captain_name Captain display name.
     * @param string|null     $contactno Contact phone number.
     * @param string|null     $contactemail Contact email address.
     * @param string|null     $match_day Scheduled match day.
     * @param string|null     $match_time Scheduled match time.
     * @param string|null     $team_type Team type.
     * @param string|null     $league_status League status.
     * @param array           $custom Custom field values.
     * @param mixed           $roster Roster data.
     * @param array|null      $players Player data.
     * @param int|null        $sets_won Number of sets won.
     * @param int|null        $sets_allowed Number of sets allowed.
     * @param int|null        $games_won Number of games won.
     * @param int|null        $games_allowed Number of games allowed.
     * @param string|null     $sets_shared Shared sets value.
     * @param string|null     $no_team No-team marker or message.
     * @param string|null     $no_player No-player marker or message.
     * @param int|null        $rubbers_won Number of rubbers won.
     * @param int|null        $rubbers_shared Number of rubbers shared.
     * @param int|null        $matches_won Number of matches won.
     * @param int|null        $matches_shared Number of matches shared.
     * @param array|null      $straight_set Straight-set statistics.
     * @param array|null      $split_set Split-set statistics.
     *
     * @return void
     */
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
     * Creates an instance from an object row, merging decoded custom data, normalising column keys, and coercing values to their expected types.
     *
     * @param object $data Source object containing row data and optional serialized custom fields.
     *
     * @return self Hydrated instance built from the supplied object data.
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
     * Creates an instance from an associative array by converting it to an object.
     *
     * @param array $data Source data used to populate the instance.
     *
     * @return self Created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Integer column: `''` becomes 0, numeric values are cast, absent or non-numeric values become null.
     *
     * @param mixed $value Value to normalize.
     *
     * @return int|null Normalized integer value, or null when absent or not numeric.
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
     * Converts a value to an integer when it can be represented as one.
     *
     * @param mixed $value Value to convert.
     *
     * @return int|null Converted integer value, or null when the value cannot be converted.
     */
    private static function nullable_int( mixed $value ): ?int {
        return self::int( $value );
    }

    /**
     * Converts a value to a float when it can be represented as one.
     *
     * @param mixed $value Value to convert.
     *
     * @return float|null Converted float value, or null when the value cannot be converted.
     */
    private static function float( mixed $value ): ?float {
        if ( null === $value ) {
            return null;
        }
        if ( '' === $value ) {
            return 0.0;
        }
        return is_numeric( $value ) ? (float) $value : null;
    }

    /**
     * Converts a value to a float when it can be represented as one.
     *
     * @param mixed $value Value to convert.
     *
     * @return float|null Converted float value, or null when the value cannot be converted.
     */
    private static function nullable_float( mixed $value ): ?float {
        return self::float( $value );
    }

    /**
     * Converts a scalar value to a string.
     *
     * @param mixed $value Value to convert.
     *
     * @return string|null Converted string value, or null when the value is not scalar.
     */
    private static function string( mixed $value ): ?string {
        return is_scalar( $value ) ? (string) $value : null;
    }

    /**
     * Converts a value to a boolean when it can be represented as one.
     *
     * @param mixed $value Value to convert.
     *
     * @return bool|null Converted boolean value, or null when the value cannot be converted.
     */
    private static function bool( mixed $value ): ?bool {
        if ( null === $value ) {
            return null;
        }
        if ( '' === $value ) {
            return false;
        }
        return is_bool( $value ) || is_numeric( $value ) ? (bool) $value : null;
    }

    /**
     * Normalises a diff value by preserving integers, truncating floats to integers, casting scalar values to strings, and returning null for absent or non-scalar values.
     *
     * @param mixed $value Value to normalize.
     *
     * @return int|string|null Normalised diff value, or null when the value is absent or not scalar.
     */
    private static function diff( mixed $value ): int|string|null {
        if ( null === $value ) {
            return null;
        }
        if ( is_int( $value ) ) {
            $return = $value;
        } elseif ( is_float( $value ) ) {
            $return = (int) $value;
        } elseif ( is_scalar( $value ) ) {
            $return = (string) $value;
        } else {
            $return = null;
        }
        return $return;
    }
}
