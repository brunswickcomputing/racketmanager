<?php
/**
 * RacketManager-Rubber API: RacketManager-rubber class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Rubber
 */

namespace Racketmanager\Domain\Fixture;

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
    public int|false $id = 0;
    /**
     * Match id variable
     *
     * @var int
     */
    public int $match_id = 0;
    /**
     * Status variable
     *
     * @var int|null
     */
    public ?int $status = null;
    /**
     * Custom variable
     *
     * @var array
     */
    public mixed $custom = array();
    /**
     * Sets variable
     *
     * @var array
     */
    public mixed $sets = array();
    /**
     * Rubber start time variable
     *
     * @var string|int|false
     */
    public string|int|false $start_time = '';
    /**
     * Rubber hour variable
     *
     * @var string
     */
    public string $hour = '';
    /**
     * Rubber minutes variable
     *
     * @var string
     */
    public string $minutes = '';
    /**
     * Date variable
     *
     * @var string
     */
    public string $date = '';
    /**
     * Rubber date variable
     *
     * @var string|int|false
     */
    public string|int|false $rubber_date = '';
    /**
     * Home points variable
     *
     * @var float|null
     */
    public ?float $home_points = null;
    /**
     * Away points variable
     *
     * @var float|null
     */
    public ?float $away_points = null;
    /**
     * Score variable
     *
     * @var string
     */
    public string $score = '-:-';
    /**
     * Is walkover variable
     *
     * @var boolean
     */
    public bool $is_walkover = false;
    /**
     * Is retired variable
     *
     * @var boolean
     */
    public bool $is_retired = false;
    /**
     * Is shared variable
     *
     * @var boolean
     */
    public bool $is_shared = false;
    /**
     * Is abandoned variable
     *
     * @var boolean
     */
    public bool $is_abandoned = false;
    /**
     * Is invalid variable
     *
     * @var boolean
     */
    public bool $is_invalid = false;
    /**
     * Players variable
     *
     * @var array
     */
    public array $players = array();
    /**
     * Rubber type variable
     *
     * @var string
     */
    public string $type = '';
    /**
     * Rubber title variable
     *
     * @var string
     */
    public string $title = '';
    /**
     * Rubber number variable
     *
     * @var int
     */
    public int $rubber_number = 0;
    /**
     * Winner id variable
     *
     * @var int
     */
    public int $winner_id = 0;
    /**
     * Loser id variable
     *
     * @var int
     */
    public int $loser_id = 0;
    /**
     * Reverse rubbers variable
     *
     * @var boolean
     */
    public bool $reverse_rubbers = false;
    /**
     * Reverse rubber variable
     *
     * @var boolean
     */
    public bool $reverse_rubber = false;
    /**
     * Day
     *
     * @var int
     */
    public int $day = 0;
    /**
     * Month
     *
     * @var int
     */
    public int $month = 0;
    /**
     * Year
     *
     * @var int
     */
    public int $year = 0;
    /**
     * Stats
     *
     * @var array
     */
    public array $stats = array();
    /**
     * Class
     *
     * @var string
     */
    public string $class = '';
    /**
     * Group
     *
     * @var string
     */
    public string $group = '';
    /**
     * Post id
     *
     * @var int
     */
    public int $post_id = 0;
    /**
     * Walkover
     *
     * @var string
     */
    public string $walkover = '';
    /**
     * Invalid
     *
     * @var string
     */
    public string $invalid = '';
    /**
     * Abandoned
     *
     * @var string
     */
    public string $abandoned = '';
    /**
     * Share
     *
     * @var string
     */
    public string $share = '';
    /**
     * Cancelled
     *
     * @var string
     */
    public string $cancelled = '';
    /**
     * Retired
     *
     * @var string
     */
    public string $retired = '';

    /**
     * Rubber construction function
     *
     * @param object|null $rubber rubber object.
     */
    public function __construct( ?object $rubber = null ) {
        $this->players = array();

        if ( ! is_null( $rubber ) ) {
            if ( ! empty( $rubber->custom ) ) {
                $custom = stripslashes_deep( (array) maybe_unserialize( $rubber->custom ) );
                $rubber = (object) array_merge( (array) $rubber, (array) $custom );
            }

            foreach ( get_object_vars( $rubber ) as $key => $value ) {
                $this->$key = $value;
            }

            $this->custom = stripslashes_deep( maybe_unserialize( $this->custom ) );
            $this->sets   = $this->custom['sets'] ?? array();

            if ( ! empty( $this->date ) && ! str_starts_with( $this->date, '0000-00-00' ) ) {
                $this->rubber_date = function_exists( 'mysql2date' ) ? mysql2date( 'Y-m-d', $this->date ) : substr( $this->date, 0, 10 );
                $this->year        = (int) substr( $this->date, 0, 4 );
                $this->month       = (int) substr( $this->date, 5, 2 );
                $this->day         = (int) substr( $this->date, 8, 2 );
                $time              = substr( $this->date, 11, 5 );
                $this->hour        = substr( $time, 0, 2 );
                $this->minutes     = substr( $time, 3, 2 );
                $this->start_time  = ( '00:00' === $time ) ? '' : ( function_exists( 'mysql2date' ) ? mysql2date( 'H:i', $this->date ) : $time );
            } else {
                $this->rubber_date = ( ! empty( $this->date ) && str_starts_with( $this->date, '0000-00-00' ) ) ? 'N/A' : '';
                $this->year        = 0;
                $this->month       = 0;
                $this->day         = 0;
                $this->hour        = '';
                $this->minutes     = '';
                $this->start_time  = '';
            }

            if ( null !== $this->home_points && null !== $this->away_points ) {
                $home_score  = $this->home_points;
                $away_score  = $this->away_points;
                $this->score = sprintf( '%g - %g', $home_score, $away_score );
            } else {
                $this->score = '-:-';
            }

            $this->is_walkover  = ! empty( $this->custom['walkover'] );
            $this->is_shared    = ! empty( $this->custom['share'] );
            $this->is_retired   = ! empty( $this->custom['retired'] );
            $this->is_abandoned = ! empty( $this->custom['abandoned'] );
            $this->is_invalid   = ! empty( $this->custom['invalid'] );

            if ( isset( $rubber->players ) && is_array( $rubber->players ) ) {
                $this->players = $rubber->players;
            }

            $this->title           = ( $this->type ?? '' ) . ( $this->rubber_number ?? '' );
            $this->reverse_rubbers = isset( $rubber->reverse_rubbers ) ? (bool) $rubber->reverse_rubbers : false;
            $this->reverse_rubber  = isset( $rubber->reverse_rubber ) ? (bool) $rubber->reverse_rubber : false;
        }
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
        $this->date = $date;
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
        return \Racketmanager\Domain\Enums\Fixture_Status::WALKOVER->value === $this->status;
    }

    public function is_shared(): bool {
        return \Racketmanager\Domain\Enums\Fixture_Status::SHARED->value === $this->status;
    }

    /**
     * Reset rubber result function
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
    }
}
