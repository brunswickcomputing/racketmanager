<?php
declare( strict_types=1 );
/**
 * RacketManager-Rubber API: RacketManager-rubber class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Rubber
 */

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;
use Racketmanager\Domain\Enums\Fixture_Status;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use stdClass;

/**
 * Class to implement the Rubber object
 */
class Rubber {
    /**
     * Rubber id variable
     *
     * @var int|false
     */
    public private(set) int|false $id = 0;
    /**
     * Match id variable
     *
     * @var int
     */
    public private(set) int $match_id = 0;
    /**
     * Status variable
     *
     * @var int|null
     */
    public private(set) ?int $status = null;
    /**
     * Custom variable
     *
     * @var array
     */
    public private(set) mixed $custom = array();
    /**
     * Sets variable
     *
     * @var array
     */
    public private(set) mixed $sets = array();
    /**
     * Rubber start time variable
     *
     * @var string|int|false
     */
    public private(set) string|int|false $start_time = '';
    /**
     * Rubber hour variable
     *
     * @var string
     */
    public private(set) string $hour = '';
    /**
     * Rubber minutes variable
     *
     * @var string
     */
    public private(set) string $minutes = '';
    /**
     * Date variable
     *
     * @var string
     */
    public private(set) string $date = '';
    /**
     * Rubber date variable
     *
     * @var string|int|false
     */
    public private(set) string|int|false $rubber_date = '';
    /**
     * Home points variable
     *
     * @var float|null
     */
    public private(set) ?float $home_points = null;
    /**
     * Away points variable
     *
     * @var float|null
     */
    public private(set) ?float $away_points = null;
    /**
     * Score variable
     *
     * @var string
     */
    public private(set) string $score = '-:-';
    /**
     * Is walkover variable
     *
     * @var boolean
     */
    public private(set) bool $is_walkover = false;
    /**
     * Is retired variable
     *
     * @var boolean
     */
    public private(set) bool $is_retired = false;
    /**
     * Is shared variable
     *
     * @var boolean
     */
    public private(set) bool $is_shared = false;
    /**
     * Is abandoned variable
     *
     * @var boolean
     */
    public private(set) bool $is_abandoned = false;
    /**
     * Is invalid variable
     *
     * @var boolean
     */
    public private(set) bool $is_invalid = false;
    /**
     * Players variable
     *
     * @var array
     */
    public private(set) array $players = array();
    /**
     * Rubber type variable
     *
     * @var string
     */
    public private(set) string $type = '';
    /**
     * Rubber title variable
     *
     * @var string
     */
    public private(set) string $title = '';
    /**
     * Rubber number variable
     *
     * @var int
     */
    public private(set) int $rubber_number = 0;
    /**
     * Winner id variable
     *
     * @var int
     */
    public private(set) int $winner_id = 0;
    /**
     * Loser id variable
     *
     * @var int
     */
    public private(set) int $loser_id = 0;
    /**
     * Reverse rubbers variable
     *
     * @var boolean
     */
    public private(set) bool $reverse_rubbers = false;
    /**
     * Reverse rubber variable
     *
     * @var boolean
     */
    public private(set) bool $reverse_rubber = false;
    /**
     * Day
     *
     * @var int
     */
    public private(set) int $day = 0;
    /**
     * Month
     *
     * @var int
     */
    public private(set) int $month = 0;
    /**
     * Year
     *
     * @var int
     */
    public private(set) int $year = 0;
    /**
     * Stats
     *
     * @var array
     */
    public private(set) array $stats = array();
    /**
     * Class
     *
     * @var string
     */
    public private(set) string $class = '';
    /**
     * Group
     *
     * @var string
     */
    public private(set) string $group = '';
    /**
     * Post id
     *
     * @var int
     */
    public private(set) int $post_id = 0;
    /**
     * Walkover
     *
     * @var string
     */
    public private(set) string $walkover = '';
    /**
     * Invalid
     *
     * @var string
     */
    public private(set) string $invalid = '';
    /**
     * Abandoned
     *
     * @var string
     */
    public private(set) string $abandoned = '';
    /**
     * Share
     *
     * @var string
     */
    public private(set) string $share = '';
    /**
     * Cancelled
     *
     * @var string
     */
    public private(set) string $cancelled = '';
    /**
     * Retired
     *
     * @var string
     */
    public private(set) string $retired = '';

    /**
     * Rubber construction function
     *
     * @param Rubber_Hydration_DTO|null $dto hydration DTO (custom column already decoded and merged).
     */
    public function __construct( ?Rubber_Hydration_DTO $dto = null ) {
        $this->players = array();

        if ( ! is_null( $dto ) ) {
            foreach ( get_object_vars( $dto ) as $key => $value ) {
                if ( null === $value ) {
                    continue; // Column absent from the row: keep the property default.
                }
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }

            $this->sets = $this->custom['sets'] ?? array();
            if ( isset( $this->custom['stats'] ) ) {
                $this->stats = $this->custom['stats'];
            }

            $this->parse_date( $this->date );
            $this->sync_derived_state();

            $this->title           = ( $this->type ?? '' ) . ( $this->rubber_number ?? '' );
            $this->reverse_rubbers = $dto->reverse_rubbers ?? false;
            $this->reverse_rubber  = $dto->reverse_rubber ?? false;
        }
    }

    /**
     * Create a Rubber from a generic object (e.g. database row) via Rubber_Factory.
     *
     * @param object $row rubber row.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Rubber_Factory::from_object( $row );
    }

    /**
     * Create a Rubber from an associative array via Rubber_Factory.
     *
     * @param array $data rubber data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Rubber_Factory::from_array( $data );
    }

    /**
     * Get rubber id.
     *
     * @return int|false
     */
    public function get_id(): int|false {
        return $this->id;
    }

    /**
     * Set rubber id.
     *
     * @param int|false $id
     * @return void
     */
    public function set_id( int|false $id ): void {
        $this->id = $id;
    }

    /**
     * Get match id.
     *
     * @return int
     */
    public function get_match_id(): int {
        return $this->match_id;
    }

    /**
     * Set match id.
     *
     * @param int $match_id
     * @return void
     */
    public function set_match_id( int $match_id ): void {
        $this->match_id = $match_id;
    }

    /**
     * Get rubber date (DB format string).
     *
     * @return string
     */
    public function get_date(): string {
        return $this->date;
    }

    /**
     * Set rubber date (DB format string).
     *
     * @param string $date
     * @return void
     */
    public function set_date( string $date ): void {
        $this->reschedule( $date );
    }

    /**
     * Get rubber group.
     *
     * @return string
     */
    public function get_group(): string {
        return $this->group;
    }

    /**
     * Set rubber group.
     *
     * @param string $group
     * @return void
     */
    public function set_group( string $group ): void {
        $this->group = $group;
    }

    /**
     * Get rubber type.
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Set rubber type.
     *
     * @param string $type
     * @return void
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Get rubber number.
     *
     * @return int
     */
    public function get_rubber_number(): int {
        return $this->rubber_number;
    }

    /**
     * Set rubber number.
     *
     * @param int $rubber_number
     * @return void
     */
    public function set_rubber_number( int $rubber_number ): void {
        $this->rubber_number = $rubber_number;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     * @return void
     */
    public function set_status( ?int $status ): void {
        $this->status = $status;
        $this->sync_derived_state();
    }

    /**
     * Get home points.
     *
     * @return float|null
     */
    public function get_home_points(): ?float {
        return $this->home_points;
    }

    /**
     * Set home points.
     *
     * @param float|null $home_points
     * @return void
     */
    public function set_home_points( ?float $home_points ): void {
        $this->home_points = $home_points;
        $this->sync_derived_state();
    }

    /**
     * Get away points.
     *
     * @return float|null
     */
    public function get_away_points(): ?float {
        return $this->away_points;
    }

    /**
     * Set away points.
     *
     * @param float|null $away_points
     * @return void
     */
    public function set_away_points( ?float $away_points ): void {
        $this->away_points = $away_points;
        $this->sync_derived_state();
    }

    /**
     * Get winner id.
     *
     * @return int
     */
    public function get_winner_id(): int {
        return (int) $this->winner_id;
    }

    /**
     * Set winner id.
     *
     * @param int $winner_id
     * @return void
     */
    public function set_winner_id( int $winner_id ): void {
        $this->winner_id = $winner_id;
    }

    /**
     * Get loser id.
     *
     * @return int
     */
    public function get_loser_id(): int {
        return (int) $this->loser_id;
    }

    /**
     * Set loser id.
     *
     * @param int $loser_id
     * @return void
     */
    public function set_loser_id( int $loser_id ): void {
        $this->loser_id = $loser_id;
    }

    /**
     * Get post id.
     *
     * @return int
     */
    public function get_post_id(): int {
        return $this->post_id;
    }

    /**
     * Set post id.
     *
     * @param int $post_id
     * @return void
     */
    public function set_post_id( int $post_id ): void {
        $this->post_id = $post_id;
    }

    /**
     * Get custom data.
     *
     * @return array
     */
    public function get_custom(): array {
        $custom = $this->custom;
        return is_array( $custom ) ? $custom : array();
    }

    /**
     * Set custom data.
     *
     * @param array $custom
     * @return void
     */
    public function set_custom( array $custom ): void {
        $this->custom = $custom;
        $this->sync_derived_state();
    }

    /**
     * Get players for rubber.
     *
     * @return array
     */
    public function get_players(): array {
        return $this->players ?? array();
    }

    /**
     * Set players for rubber.
     *
     * @param array $players
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Calculate result function
     *
     * @param array $points array of data used to calculate points.
     * @param array $point_rule
     * @param int $num_sets
     * @return object points data for home and away.
     */
    public function calculate_result( array $points, array $point_rule, int $num_sets ): object {
        $home_team          = $points['home']['team'];
        $home_sets          = $points['home']['sets'];
        $home_walkover      = isset( $points['home']['walkover'] ) ? 1 : 0;
        $home_retired       = isset( $points['home']['retired'] ) ? 1 : 0;
        $home_invalid       = isset( $points['home']['invalid'] ) ? 1 : 0;
        $away_team          = $points['away']['team'];
        $away_sets          = $points['away']['sets'];
        $away_walkover      = isset( $points['away']['walkover'] ) ? 1 : 0;
        $away_retired       = isset( $points['away']['retired'] ) ? 1 : 0;
        $away_invalid       = isset( $points['away']['invalid'] ) ? 1 : 0;
        $both_invalid       = isset( $points['both']['invalid'] ) ? 1 : 0;
        $shared_sets        = $points['shared']['sets'] ?? 0;

        $forwin             = $point_rule['forwin'] ?? 0;
        $forwin_split       = $point_rule['forwin_split'] ?? 0;
        $forshare           = $point_rule['forshare'] ?? 0;
        $forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
        if ( $home_invalid ) {
            $invalid_points_home = $forwalkover_rubber;
            $invalid_points_away = 0;
        } elseif ( $away_invalid ) {
            $invalid_points_away = $forwalkover_rubber;
            $invalid_points_home = 0;
        } elseif ( $both_invalid ) {
            $invalid_points_home = $forwalkover_rubber;
            $invalid_points_away = $forwalkover_rubber;
        } else {
            $invalid_points_home = 0;
            $invalid_points_away = 0;
        }
        if ( $shared_sets === $num_sets ) {
            $straight_sets_home = 0;
            $straight_sets_away = 0;
            $split_sets_home    = 0;
            $split_sets_away    = 0;
        } elseif ( ( empty( $home_sets ) || empty( $away_sets ) ) && empty( $shared_sets ) ) {
            if ( empty( $home_sets ) && empty( $away_sets ) ) {
                $straight_sets_home = 0;
                $straight_sets_away = 0;
            } elseif ( empty( $home_sets ) ) {
                $straight_sets_home = 0;
                $straight_sets_away = 1;
            } else {
                $straight_sets_home = 1;
                $straight_sets_away = 0;
            }
            $split_sets_home    = 0;
            $split_sets_away    = 0;
        } elseif ( empty( $home_sets ) ) {
            $straight_sets_home = 0;
            $straight_sets_away = 0;
            $split_sets_home    = 0;
            $split_sets_away    = 1;
        } else {
            $straight_sets_home = 0;
            $straight_sets_away = 0;
            $split_sets_home    = 1;
            $split_sets_away    = 0;
        }
        $home_points = (float) ( $home_sets + ( $straight_sets_home * $forwin ) + ( $split_sets_home * $forwin_split ) + ( $shared_sets * $forshare ) - ( $home_walkover * $forwalkover_rubber ) - $invalid_points_home );
        $away_points = (float) ( $away_sets + ( $straight_sets_away * $forwin ) + ( $split_sets_away * $forwin_split ) + ( $shared_sets * $forshare ) - ( $away_walkover * $forwalkover_rubber ) - $invalid_points_away );
        if ( $home_walkover || $away_walkover ) {
            if ( $home_walkover && $away_walkover ) {
                $winner = -1;
                $loser  = -1;
            } elseif ( $home_walkover ) {
                $winner = $away_team;
                $loser  = $home_team;
            } else {
                $winner = $home_team;
                $loser  = $away_team;
            }
        } elseif ( $home_retired || $away_retired ) {
            if ( $home_retired ) {
                $winner = $away_team;
                $loser  = $home_team;
            } else {
                $winner = $home_team;
                $loser  = $away_team;
            }
        } elseif ( $both_invalid ) {
            $winner = -1;
            $loser  = -1;
        } elseif ( $home_invalid || $away_invalid ) {
            if ( $home_invalid ) {
                $winner = $away_team;
                $loser  = $home_team;
            } else {
                $winner = $home_team;
                $loser  = $away_team;
            }
        } elseif ( (float) $home_points > (float) $away_points ) {
            $winner = $home_team;
            $loser  = $away_team;
        } elseif ( (float) $home_points < (float) $away_points ) {
            $winner = $away_team;
            $loser  = $home_team;
        } else {
            $winner = -1;
            $loser  = -1;
        }
        $return         = new stdClass();
        $return->home   = (float) $home_points;
        $return->away   = (float) $away_points;
        $return->winner = $winner;
        $return->loser  = $loser;
        return $return;
    }

    public function is_walkover(): bool {
        return Fixture_Status::WALKOVER->value === $this->status;
    }

    public function is_shared(): bool {
        return Fixture_Status::SHARED->value === $this->status;
    }

    /**
     * Record the rubber result and synchronize score, sets, stats, and status flags.
     *
     * @param float|int|null $home_points Home points.
     * @param float|int|null $away_points Away points.
     * @param int|null       $winner_id   Winner ID.
     * @param int|null       $loser_id    Loser ID.
     * @param int|null       $status      Rubber status.
     * @param array          $custom      Custom metadata (sets, stats, walkover, etc.).
     * @return void
     */
    public function record_result(
        float|int|null $home_points,
        float|int|null $away_points,
        ?int $winner_id = null,
        ?int $loser_id = null,
        ?int $status = null,
        array $custom = array()
    ): void {
        $this->home_points = null !== $home_points ? (float) $home_points : null;
        $this->away_points = null !== $away_points ? (float) $away_points : null;
        $this->winner_id   = $winner_id ?? 0;
        $this->loser_id    = $loser_id ?? 0;
        $this->status      = $status;
        $this->custom      = $custom;

        $this->sets  = $custom['sets'] ?? array();
        $this->stats = $custom['stats'] ?? array();

        $this->sync_derived_state();
    }

    /**
     * Assign players to this rubber.
     *
     * @param array $players Array of players assigned to home and away.
     * @return void
     */
    public function assign_players( array $players ): void {
        $this->set_players( $players );
    }

    /**
     * Reschedule rubber to a new date and time.
     *
     * @param string $date Date string (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS).
     * @return void
     */
    public function reschedule( string $date ): void {
        $this->parse_date( $date );
    }

    /**
     * Parse date and populate derived calendar and time components.
     *
     * @param string|null $date Date string (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS).
     * @return void
     */
    private function parse_date( ?string $date ): void {
        $this->date = $date ?? '';

        if ( ! empty( $this->date ) && ! str_starts_with( $this->date, '0000-00-00' ) ) {
            // `date` is a MySQL DATETIME in site-local time; slicing it is equivalent to mysql2date( 'Y-m-d' | 'H:i' ).
            $this->rubber_date = substr( $this->date, 0, 10 );
            $this->year        = (int) substr( $this->date, 0, 4 );
            $this->month       = (int) substr( $this->date, 5, 2 );
            $this->day         = (int) substr( $this->date, 8, 2 );
            $time              = substr( $this->date, 11, 5 );
            $this->hour        = substr( $time, 0, 2 );
            $this->minutes     = substr( $time, 3, 2 );
            $this->start_time  = ( '00:00' === $time ) ? '' : $time;
        } else {
            $this->rubber_date = ( ! empty( $this->date ) && str_starts_with( $this->date, '0000-00-00' ) ) ? 'N/A' : '';
            $this->year        = 0;
            $this->month       = 0;
            $this->day         = 0;
            $this->hour        = '';
            $this->minutes     = '';
            $this->start_time  = '';
        }
    }

    /**
     * Synchronize derived score string and status flags from points, status enum, and custom metadata.
     *
     * @return void
     */
    private function sync_derived_state(): void {
        if ( null !== $this->home_points && null !== $this->away_points ) {
            $this->score = sprintf( '%g - %g', $this->home_points, $this->away_points );
        } else {
            $this->score = '-:-';
        }

        $custom = is_array( $this->custom ) ? $this->custom : array();

        $this->is_walkover  = ! empty( $custom['walkover'] ) || Fixture_Status::WALKOVER->value === $this->status;
        $this->is_shared    = ! empty( $custom['share'] ) || Fixture_Status::SHARED->value === $this->status;
        $this->is_retired   = ! empty( $custom['retired'] ) || Fixture_Status::RETIRED->value === $this->status;
        $this->is_abandoned = ! empty( $custom['abandoned'] ) || Fixture_Status::ABANDONED->value === $this->status;
        $this->is_invalid   = ! empty( $custom['invalid'] ) || Fixture_Status::INVALID->value === $this->status;
    }

    /**
     * Reset rubber result function.
     *
     * @return void
     */
    public function reset_result(): void {
        $this->players     = array();
        $this->home_points = null;
        $this->away_points = null;
        $this->winner_id   = 0;
        $this->loser_id    = 0;
        $this->custom      = null;
        $this->status      = null;
        $this->sets        = array();
        $this->stats       = array();
        $this->sync_derived_state();
    }

    /**
     * Set CSS class.
     *
     * @param string $class_name CSS class name.
     * @return void
     */
    public function set_class( string $class_name ): void {
        $this->class = $class_name;
    }

    /**
     * Set rubber title.
     *
     * @param string $title Rubber title.
     * @return void
     */
    public function set_title( string $title ): void {
        $this->title = $title;
    }
}
