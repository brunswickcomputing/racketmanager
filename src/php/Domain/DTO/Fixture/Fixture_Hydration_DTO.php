<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

use InvalidArgumentException;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;

/**
 * Data Transfer Object for hydrating a Fixture entity from a raw database row or array.
 */
readonly class Fixture_Hydration_DTO {
    public ?string $start_time;

    /**
     * @param int|null $id
     * @param string|null $fixture_title
     * @param string|null $link
     * @param string|null $group
     * @param string|null $date
     * @param string|null $date_original
     * @param string|null $home_team
     * @param string|null $away_team
     * @param int|null $match_day
     * @param string|null $location
     * @param string|null $host
     * @param int|null $league_id
     * @param string|null $season
     * @param string|null $home_points
     * @param string|null $away_points
     * @param int|null $winner_id
     * @param int|null $loser_id
     * @param int|null $status
     * @param int|null $linked_fixture
     * @param int|null $leg
     * @param int|null $winner_id_tie
     * @param int|null $loser_id_tie
     * @param float|null $home_points_tie
     * @param float|null $away_points_tie
     * @param int|null $post_id
     * @param string|null $final
     * @param array|null $custom
     * @param int|null $updated_user
     * @param string|null $updated
     * @param string|null $date_result_entered
     * @param string|null $confirmed
     * @param int|null $home_captain
     * @param int|null $away_captain
     * @param array|null $comments
     * @param string|null $updated_by
     * @param string|null $start_time
     * @param array|null $rubbers
     * @param bool $has_explicit_start_time
     */
    public function __construct(
        public ?int $id = null,
        public ?string $fixture_title = null,
        public ?string $link = null,
        public ?string $group = null,
        public ?string $date = null,
        public ?string $date_original = null,
        public ?string $home_team = null,
        public ?string $away_team = null,
        public ?int $match_day = null,
        public ?string $location = null,
        public ?string $host = null,
        public ?int $league_id = null,
        public ?string $season = null,
        public ?string $home_points = null,
        public ?string $away_points = null,
        public ?int $winner_id = null,
        public ?int $loser_id = null,
        public ?int $status = null,
        public ?int $linked_fixture = null,
        public ?int $leg = null,
        public ?int $winner_id_tie = null,
        public ?int $loser_id_tie = null,
        public ?float $home_points_tie = null,
        public ?float $away_points_tie = null,
        public ?int $post_id = null,
        public ?string $final = null,
        public ?array $custom = null,
        public ?int $updated_user = null,
        public ?string $updated = null,
        public ?string $date_result_entered = null,
        public ?string $confirmed = null,
        public ?int $home_captain = null,
        public ?int $away_captain = null,
        public ?array $comments = null,
        public ?string $updated_by = null,
        ?string $start_time = null,
        public ?array $rubbers = null,
        public bool $has_explicit_start_time = false
    ) {
        if ( ! $this->has_explicit_start_time && null === $start_time && null !== $this->date ) {
            $this->start_time = Fixture_Time_Parser::extract_time_from_datetime( $this->date );
        } elseif ( null !== $start_time ) {
            try {
                $this->start_time = Fixture_Time_Parser::normalize_start_time( $start_time );
            } catch ( InvalidArgumentException ) {
                $this->start_time = Fixture_Time_Parser::extract_time_from_datetime( $this->date );
            }
        } else {
            $this->start_time = null;
        }
    }

    /**
     * Create from a database row or generic object.
     *
     * Comments are unserialized leniently: a handful of live rows have
     * byte-length-mismatched serialized payloads (an emoji replaced by `?`),
     * so a failed unserialize is kept as `['legacy' => <raw string>]` instead
     * of aborting hydration. Custom stays strict because a corrupt settings
     * payload is a data-integrity error, not display text.
     *
     * @param object $data
     * @return self
     */
    public static function from_object( object $data ): self {
        $custom = self::unserialize_value( $data->custom ?? null, 'custom' );
        if ( is_string( $custom ) && '' === trim( $custom ) ) {
            $custom = array();
        } elseif ( null !== $custom && ! is_array( $custom ) ) {
            $custom = (array) $custom;
        }

        try {
            $comments = self::unserialize_value( $data->comments ?? null, 'comments' );
        } catch ( InvalidArgumentException ) {
            $comments = $data->comments ?? null;
        }
        if ( ! is_array( $comments ) && ! is_null( $comments ) ) {
            $comments = array( 'legacy' => $comments );
        }

        $has_explicit_start_time = false;
        $start_time              = self::resolve_start_time( $data, $has_explicit_start_time );

        return new self(
            id: isset( $data->id ) ? (int) $data->id : null,
            fixture_title: isset( $data->fixture_title ) ? (string) $data->fixture_title : ( isset( $data->title ) ? (string) $data->title : null ),
            link: isset( $data->link ) ? (string) $data->link : null,
            group: isset( $data->group ) ? (string) $data->group : null,
            date: isset( $data->date ) ? (string) $data->date : null,
            date_original: isset( $data->date_original ) ? (string) $data->date_original : null,
            home_team: isset( $data->home_team ) ? (string) $data->home_team : null,
            away_team: isset( $data->away_team ) ? (string) $data->away_team : null,
            match_day: isset( $data->match_day ) ? (int) $data->match_day : null,
            location: isset( $data->location ) ? (string) $data->location : null,
            host: isset( $data->host ) ? (string) $data->host : null,
            league_id: isset( $data->league_id ) ? (int) $data->league_id : null,
            season: isset( $data->season ) ? (string) $data->season : null,
            home_points: isset( $data->home_points ) ? (string) $data->home_points : null,
            away_points: isset( $data->away_points ) ? (string) $data->away_points : null,
            winner_id: isset( $data->winner_id ) ? (int) $data->winner_id : null,
            loser_id: isset( $data->loser_id ) ? (int) $data->loser_id : null,
            status: isset( $data->status ) ? (int) $data->status : null,
            linked_fixture: isset( $data->linked_match ) ? (int) $data->linked_match : ( isset( $data->linked_fixture ) ? (int) $data->linked_fixture : null ),
            leg: isset( $data->leg ) ? (int) $data->leg : null,
            winner_id_tie: isset( $data->winner_id_tie ) ? (int) $data->winner_id_tie : null,
            loser_id_tie: isset( $data->loser_id_tie ) ? (int) $data->loser_id_tie : null,
            home_points_tie: isset( $data->home_points_tie ) ? (float) $data->home_points_tie : null,
            away_points_tie: isset( $data->away_points_tie ) ? (float) $data->away_points_tie : null,
            post_id: isset( $data->post_id ) ? (int) $data->post_id : null,
            final: isset( $data->final ) ? (string) $data->final : ( isset( $data->final_round ) ? (string) $data->final_round : null ),
            custom: $custom,
            updated_user: isset( $data->updated_user ) ? (int) $data->updated_user : null,
            updated: isset( $data->updated ) ? (string) $data->updated : null,
            date_result_entered: isset( $data->date_result_entered ) ? (string) $data->date_result_entered : null,
            confirmed: isset( $data->confirmed ) ? (string) $data->confirmed : null,
            home_captain: isset( $data->home_captain ) ? (int) $data->home_captain : null,
            away_captain: isset( $data->away_captain ) ? (int) $data->away_captain : null,
            comments: $comments,
            updated_by: isset( $data->updated_by ) ? (string) $data->updated_by : null,
            start_time: $start_time,
            rubbers: isset( $data->rubbers ) && is_array( $data->rubbers ) ? $data->rubbers : null,
            has_explicit_start_time: $has_explicit_start_time
        );
    }

    /**
     * Resolve the fixture start time from explicit property or persisted datetime string.
     *
     * @param object $data
     * @param bool   $has_explicit_start_time Output parameter indicating if explicit property was supplied.
     * @return string|null
     */
    private static function resolve_start_time( object $data, bool &$has_explicit_start_time = false ): ?string {
        if ( property_exists( $data, 'start_time' ) ) {
            $has_explicit_start_time = true;
            if ( null === $data->start_time ) {
                return null;
            }
            try {
                return Fixture_Time_Parser::normalize_start_time( (string) $data->start_time );
            } catch ( InvalidArgumentException ) {
                // Lenient on hydration: if explicit start_time is malformed (e.g. '7pm'), fall back to date's time.
                $has_explicit_start_time = false;
                $raw_date                = isset( $data->date ) ? (string) $data->date : null;
                return Fixture_Time_Parser::extract_time_from_datetime( $raw_date );
            }
        }

        $has_explicit_start_time = false;
        $raw_date                = isset( $data->date ) ? (string) $data->date : null;
        return Fixture_Time_Parser::extract_time_from_datetime( $raw_date );
    }

    /**
     * Unserialize a persisted field without instantiating PHP objects.
     *
     * WordPress `maybe_unserialize()` is intentionally not used: it constructs
     * objects of any class. Object instantiation is disabled via
     * `allowed_classes => false`.
     *
     * @param mixed  $value
     * @param string $field
     * @return mixed
     */
    private static function unserialize_value( mixed $value, string $field ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        if ( ! self::is_serialized( $value ) ) {
            return $value;
        }
        $unserialized = @unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
        if ( false === $unserialized && 'b:0;' !== $value ) {
            throw new InvalidArgumentException( sprintf( 'Unable to unserialize Fixture hydration field "%s".', $field ) );
        }
        return $unserialized;
    }

    /**
     * Detect a PHP serialized payload without depending on WordPress.
     *
     * @param string $data
     * @return bool
     */
    private static function is_serialized( string $data ): bool {
        $data = trim( $data );
        if ( 'N;' === $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 || ':' !== $data[1] ) {
            return false;
        }
        $last = substr( $data, -1 );
        if ( ';' !== $last && '}' !== $last ) {
            return false;
        }

        return in_array( $data[0], array( 'a', 'O', 's', 'b', 'i', 'd' ), true );
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
}
