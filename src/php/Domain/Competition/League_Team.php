<?php
declare( strict_types=1 );
/**
 * League_Team API: League Team class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage League_Team
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Competition\League_Team_Hydration_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Shared\Serialized_Column;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Domain\Team\Team_Placeholder_Ref;
use Racketmanager\Util\Util_Lookup;

/**
 * Class to implement the League_Team object
 */
class League_Team {
    /**
     * Number of done matches
     *
     * @var int
     */
    public private(set) int $done_matches = 0;

    /**
     * Number of won matches
     *
     * @var int
     */
    public private(set) int $won_matches = 0;

    /**
     * Number of draw matches
     *
     * @var int
     */
    public private(set) int $draw_matches = 0;

    /**
     * Number of lost matches
     *
     * @var int
     */
    public private(set) int $lost_matches = 0;

    /**
     * Percentage of won matches
     *
     * @var int|float
     */
    public private(set) int|float $win_percent = 0;

    /**
     * Retrieve team instance
     *
     * @var int|null $team_id
     */
    private ?int $team_id = null;
    /**
     * Win percent multiplication factor
     *
     * @var int
     */
    private int $pct_mult = 1;
    /**
     * Home variable
     *
     * @var boolean
     */
    public private(set) bool $home = false;
    /**
     * Team id variable
     *
     * @var ?int
     */
    public private(set) ?int $id = null;
    /**
     * Table id variable
     *
     * @var int|null
     */
    public private(set) ?int $table_id = null;
    /**
     * Title variable
     *
     * @var ?string
     */
    public private(set) ?string $title = null;
    /**
     * Stadium variable
     *
     * @var string
     */
    public private(set) string $stadium = '';
    /**
     * Profile variable
     *
     * @var Team_Profile
     */
    private Team_Profile $profile; // Type-hinted to the Enum.
    /**
     * Status variable
     *
     * @var ?string
     */
    public private(set) ?string $status = null;
    /**
     * Status text variable
     *
     * @var string
     */
    public private(set) string $status_text = '';
    /**
     * Status icon variable
     *
     * @var string
     */
    public private(set) string $status_icon = '';
    /**
     * Club variable
     *
     * @var ?int
     */
    public private(set) ?int $club_id = null;
    /**
     * Club object variable
     *
     * @var Club|null
     */
    public private(set) Club|null $club = null;
    /**
     * Positive points variable
     *
     * @var float
     */
    public private(set) float $points_plus = 0.0;
    /**
     * Additional points variable
     *
     * @var float
     */
    public private(set) float $add_points = 0.0;
    /**
     * Points variable
     *
     * @var array
     */
    public private(set) array $points = array();
    /**
     * Sets won variable
     *
     * @var int
     */
    public private(set) int $sets_won = 0;
    /**
     * Sets lost variable
     *
     * @var int
     */
    public private(set) int $sets_allowed = 0;
    /**
     * Games won variable
     *
     * @var int
     */
    public private(set) int $games_won = 0;
    /**
     * Games lost variable
     *
     * @var int
     */
    public private(set) int $games_allowed = 0;
    /**
     * Points minus variable
     *
     * @var float
     */
    public private(set) float $points_minus = 0.0;
    /**
     * Secondary points variable
     *
     * @var array
     */
    public private(set) array $points_2 = array();
    /**
     * Secondary points plus variable
     *
     * @var int
     */
    public private(set) int $points_2_plus = 0;
    /**
     * Secondary points minus variable
     *
     * @var int
     */
    public private(set) int $points_2_minus = 0;
    /**
     * Points difference variable
     *
     * @var string|int
     */
    public private(set) string|int $diff = 0;
    /**
     * Roster variable
     *
     * @var array
     */
    public private(set) mixed $roster = null;
    /**
     * Player variable
     *
     * @var int
     */
    public private(set) int $player = 0;
    /**
     * Player id variable
     *
     * @var int
     */
    public private(set) int $player_id = 0;
    /**
     * League id variable
     *
     * @var int
     */
    public private(set) int $league_id = 0;
    /**
     * Next match variable
     *
     * @var ?object
     */
    public private(set) ?object $next_match = null;
    /**
     * Previous match variable
     *
     * @var ?object
     */
    public private(set) ?object $prev_match = null;
    /**
     * Season variable
     *
     * @var string
     */
    public private(set) string $season = '';
    /**
     * Custom variable
     *
     * @var string|array|null
     */
    public private(set) string|array|null $custom = null;
    /**
     * Display class variable
     *
     * @var string
     */
    public private(set) string $class = '';
    /**
     * Captain (user id) variable - persisted in the league teams `captain` column.
     *
     * @var ?int
     */
    public private(set) ?int $captain = null;
    /**
     * Captain display name variable (populated when team details are loaded)
     *
     * @var ?string
     */
    public private(set) ?string $captain_name = null;
    /**
     * Formatted points variable
     *
     * @var array
     */
    public private(set) array $points_formatted = array();
    /**
     * Captain id variable
     *
     * @var ?int
     */
    public private(set) ?int $captain_id = null;
    /**
     * Contact number variable
     *
     * @var ?string
     */
    public private(set) ?string $contactno = null;
    /**
     * Content email variable
     *
     * @var ?string
     */
    public private(set) ?string $contactemail = null;
    /**
     * Match day variable
     *
     * @var ?string
     */
    public private(set) ?string $match_day = null;
    /**
     * Match time variable
     *
     * @var ?string
     */
    public private(set) ?string $match_time = null;
    /**
     * Old rank
     *
     * @var ?int
     */
    public private(set) ?int $old_rank = null;
    /**
     * Team ref variable
     *
     * @var string
     */
    public private(set) string $team_ref = '';
    /**
     * Team type variable
     *
     * @var string|null
     */
    public private(set) ?string $team_type = null;
    /**
     * Is withdrawn variable
     *
     * @var boolean
     */
    public private(set) bool $is_withdrawn = false;
    /**
     * Players variable
     *
     * @var array
     */
    public private(set) array $players = array();
    /**
     * Group variable
     *
     * @var string|null
     */
    public private(set) ?string $group = null;
    /**
     * Rank variable
     *
     * @var int|null
     */
    public private(set) ?int $rank = null;
    /**
     * Rating variable
     *
     * @var float|null
     */
    public private(set) ?float $rating = null;
    /**
     * Straight set variable
     *
     * @var array
     */
    public private(set) array $straight_set = array();
    /**
     * Split set variable
     *
     * @var array
     */
    public private(set) array $split_set = array();
    /**
     * Sets shared variable
     *
     * @var string
     */
    public private(set) string $sets_shared = '';
    /**
     * No team variable
     *
     * @var string
     */
    public private(set) string $no_team = '';
    /**
     * No player variable
     *
     * @var string
     */
    public private(set) string $no_player = '';
    /**
     * Rubbers won variable
     *
     * @var int
     */
    public private(set) int $rubbers_won = 0;
    /**
     * Rubbers shared variable
     *
     * @var int
     */
    public private(set) int $rubbers_shared = 0;
    /**
     * Matches won variable
     *
     * @var int
     */
    public private(set) int $matches_won = 0;
    /**
     * Matches shared variable
     *
     * @var int
     */
    public private(set) int $matches_shared = 0;
    /**
     * Info variable
     *
     * @var object|null
     */
    public private(set) ?object $info = null;
    /**
     * League variable
     *
     * @var string
     */
    public private(set) string $league = '';
    /**
     * League status variable
     *
     * @var ?string
     */
    public private(set) ?string $league_status = null;
    /**
     * Constructor
     *
     * Hydrates from the typed DTO only (`custom` keys are already merged over the row by the DTO).
     * `new League_Team()` yields an empty entity.
     *
     * @param League_Team_Hydration_DTO|null $dto League_Team hydration DTO.
     */
    public function __construct(
        ?League_Team_Hydration_DTO $dto = null
    ) {
        if ( is_null( $dto ) ) {
            return;
        }

        foreach ( get_object_vars( $dto ) as $key => $value ) {
            if ( null === $value ) {
                continue; // Column absent from the row: keep the property default.
            }
            if ( 'profile' === $key ) {
                $this->profile = Team_Profile::from_value( (int) $value );
            } else {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
        }

        $this->points_plus += $this->add_points; // add or subtract extra points.
        $this->points       = array(
            'plus'  => $this->points_plus,
            'minus' => $this->points_minus,
        );
        $this->points_2     = array(
            'plus'  => $this->points_2_plus,
            'minus' => $this->points_2_minus,
        );
        $this->diff         = ( $this->diff > 0 ) ? '+' . $this->diff : $this->diff;
        $this->win_percent();
        if ( empty( $this->status ) ) {
            $this->status_text = '';
        } else {
            $this->status_text = Util_Lookup::get_standing_status( $this->status );
        }
        if ( ! isset( $this->club ) ) {
            $this->club = null;
        }
        $this->roster = Serialized_Column::decode_legacy( $this->roster );
        if ( 'W' === $this->status ) {
            $this->is_withdrawn = true;
        } else {
            $this->is_withdrawn = false;
        }
        $this->status_icon = '';
        if ( '+' === $this->status ) {
            $this->status_icon = 'arrow-up';
        } elseif ( '-' === $this->status ) {
            $this->status_icon = 'arrow-down';
        } elseif ( '=' === $this->status ) {
            $this->status_icon = 'dot';
        }
        if ( ! empty( $this->title ) && str_contains( $this->title, '_' ) ) {
            $placeholder = Team_Placeholder_Ref::parse( $this->title );
            if ( null !== $placeholder ) {
                $this->team_ref = $this->title;
                $this->title    = $placeholder->default_label();
            }
        }
    }

    /**
     * Create an instance from an object row.
     *
     * @param object $row Source object containing team data.
     *
     * @return self Created instance.
     */
    public static function from_object( object $row ): self {
        return League_Team_Factory::from_object( $row );
    }

    /**
     * Create an instance from array data.
     *
     * @param array $data Data used to create the instance.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return League_Team_Factory::from_array( $data );
    }

    /**
     * Get id
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get team id
     *
     * @return int Team identifier
     */
    public function get_team_id(): int {
        return $this->team_id ?? (int) ( $this->id ?? 0 );
    }

    /**
     * Get league id
     *
     * @return int League identifier
     */
    public function get_league_id(): int {
        return $this->league_id;
    }

    /**
     * Get season code/name
     *
     * @return string Season
     */
    public function get_season(): string {
        return $this->season;
    }

    /**
     * Get captain id
     *
     * @return int|null Player id or null
     */
    public function get_captain(): ?int {
        return $this->captain;
    }

    /**
     * Get match day
     *
     * @return string|null Match day label or null
     */
    public function get_match_day(): ?string {
        return $this->match_day;
    }

    /**
     * Get match time
     *
     * @return string|null Match time or null
     */
    public function get_match_time(): ?string {
        return $this->match_time;
    }

    /**
     * Get primary points scored (plus)
     *
     * @return float
     */
    public function get_points_plus(): float {
        return $this->points_plus;
    }

    /**
     * Get primary points conceded (minus)
     *
     * @return float
     */
    public function get_points_minus(): float {
        return $this->points_minus;
    }

    /**
     * Get secondary points scored (plus)
     *
     * @return int
     */
    public function get_points_2_plus(): int {
        return $this->points_2_plus;
    }

    /**
     * Get secondary points conceded (minus)
     *
     * @return int
     */
    public function get_points_2_minus(): int {
        return $this->points_2_minus;
    }

    /**
     * Get additional points adjustment
     *
     * @return float
     */
    public function get_add_points(): float {
        return $this->add_points;
    }

    /**
     * Get number of matches played (cached on object)
     *
     * @return int
     */
    public function get_done_matches(): int {
        return $this->done_matches;
    }

    /**
     * Get number of matches won (cached on object)
     *
     * @return int
     */
    public function get_won_matches(): int {
        return $this->won_matches;
    }

    /**
     * Get number of matches lost (cached on object)
     *
     * @return int
     */
    public function get_lost_matches(): int {
        return $this->lost_matches;
    }

    /**
     * Get number of matches drawn (cached on object)
     *
     * @return int
     */
    public function get_drawn_matches(): int {
        return $this->draw_matches;
    }

    /**
     * Get points difference
     *
     * @return int
     */
    public function get_diff(): int {
        return (int) $this->diff;
    }

    /**
     * Get group
     *
     * @return string|null Group name/code
     */
    public function get_group(): ?string {
        return $this->group;
    }

    /**
     * Get rank
     *
     * @return int|null Rank position
     */
    public function get_rank(): ?int {
        return $this->rank;
    }

    /**
     * Get profile enum
     *
     * @return Team_Profile|null
     */
    public function get_profile_enum(): ?Team_Profile {
        return $this->profile ?? null;
    }

    /**
     * Get profile id
     *
     * @return int|null Profile identifier
     */
    public function get_profile(): ?int {
        return isset( $this->profile ) ? $this->profile->value : null;
    }

    /**
     * Get status code
     *
     * @return string|null Status
     */
    public function get_status(): ?string {
        return $this->status;
    }

    /**
     * Get rating value
     *
     * @return float|null Rating
     */
    public function get_rating(): ?float {
        return $this->rating;
    }

    /**
     * Get custom data
     *
     * @return string|array|null Custom metadata
     */
    public function get_custom(): string|array|null {
        return $this->custom;
    }

    /**
     * Set id
     *
     * @param int|null $id Identifier.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }
    /**
     * Set captain user id
     *
     * @param int|null $captain Captain user id.
     *
     * @return void
     */
    public function set_captain( ?int $captain ): void {
        $this->captain = $captain;
    }

    /**
     * Set captain id
     *
     * @param int|null $captain_id Captain id.
     *
     * @return void
     */
    public function set_captain_id( ?int $captain_id ): void {
        $this->captain_id = $captain_id;
    }

    /**
     * Set captain name
     *
     * @param string|null $captain_name Captain display name.
     *
     * @return void
     */
    public function set_captain_name( ?string $captain_name ): void {
        $this->captain_name = $captain_name;
    }

    /**
     * Set contact number
     *
     * @param string|null $contactno Contact number.
     *
     * @return void
     */
    public function set_contactno( ?string $contactno ): void {
        $this->contactno = $contactno;
    }

    /**
     * Set contact email
     *
     * @param string|null $contactemail Contact email.
     *
     * @return void
     */
    public function set_contactemail( ?string $contactemail ): void {
        $this->contactemail = $contactemail;
    }

    /**
     * Set match day
     *
     * @param string|null $match_day Match day.
     *
     * @return void
     */
    public function set_match_day( ?string $match_day ): void {
        $this->match_day = $match_day;
    }

    /**
     * Set match time
     *
     * @param string|null $match_time Match time.
     *
     * @return void
     */
    public function set_match_time( ?string $match_time ): void {
        $this->match_time = $match_time;
    }

    /**
     * Set league status
     *
     * @param string|null $league_status League status.
     *
     * @return void
     */
    public function set_league_status( ?string $league_status ): void {
        $this->league_status = $league_status;
    }

    /**
     * Set status
     *
     * @param string|null $status Status.
     *
     * @return void
     */
    public function set_status( ?string $status = null ): void {
        $this->status = $status;
    }

    /**
     * Add team details
     *
     * @param int|null    $captain Captain id.
     * @param string|null $match_day Match day.
     * @param string|null $match_time Match time.
     *
     * @return void
     */
    public function add_details( ?int $captain, ?string $match_day, ?string $match_time ): void {
        $this->captain    = $captain;
        $this->match_day  = $match_day;
        $this->match_time = $match_time;
    }
    /**
     * Compute win percentage
     */
    public function win_percent(): void {
        $this->win_percent = $this->done_matches > 0 ? round( ( $this->won_matches + $this->draw_matches / 2 ) / $this->done_matches, 3 ) * $this->pct_mult : 0;
    }

    /**
     * Set player rating
     *
     * @param object $team team object.
     * @param object $event event object.
     */
    public function set_player_rating( object $team, object $event ): void {
        if ( ! empty( $team->players ) ) {
            $type        = substr( $event->type, 1, 1 );
            $team_rating = 0;
            foreach ( $team->players as $player ) {
                $rating       = floatval( $player->wtn[ $type ] );
                $team_rating += $rating;
            }
            $this->set_rating( $team_rating );
        }
    }
    /**
     * Set rating
     *
     * @param float|null $rating rating.
     */
    public function set_rating( ?float $rating ): void {
        $this->rating = $rating;
    }
    /**
     * Update constitution settings
     *
     * @param int    $league_id league id.
     * @param string $status status.
     */
    public function update_constitution( int $league_id, string $status ): void {
        $this->league_id = $league_id;
        $this->status    = $status;
    }
    /**
     * Add/remove points
     *
     * @param float $points points to add/remove.
     */
    public function amend_points( float $points ): void {
        $this->add_points = $points;
    }
    /**
     * Update Table
     *
     * @param int        $league_id league id.
     * @param int        $rank rank.
     * @param string     $status status.
     * @param string|int $profile profile.
     */
    public function set_constitution_rank( int $league_id, int $rank, string $status, string|int $profile ): void {
        $this->league_id = $league_id;
        $this->rank      = $rank;
        $this->status    = $status;
        $this->profile   = Team_Profile::from_value( (int) $profile );
    }

    /**
     * Get table id
     *
     * @return int|null Table id.
     */
    public function get_table_id(): ?int {
        return $this->table_id ?? null;
    }

    /**
     * Set table id
     *
     * @param int|null $table_id Table id.
     *
     * @return void
     */
    public function set_table_id( ?int $table_id ): void {
        $this->table_id = $table_id;
    }

    /**
     * Set team id
     *
     * @param int|null $team_id Team id.
     *
     * @return void
     */
    public function set_team_id( ?int $team_id ): void {
        $this->team_id = $team_id;
    }

    /**
     * Set season
     *
     * @param int|null $season Season year.
     *
     * @return void
     */
    public function set_season( ?int $season ): void {
        $this->season = (string) $season;
    }

    /**
     * Set league id
     *
     * @param int $league_id League id.
     *
     * @return void
     */
    public function set_league_id( int $league_id ): void {
        $this->league_id = $league_id;
    }

    /**
     * Set entered state profile
     *
     * @param Team_Profile $entered_state Entered state profile.
     *
     * @return void
     */
    public function set_entered_state( Team_Profile $entered_state ): void {
        $this->profile = $entered_state;
    }

    /**
     * Set rank
     *
     * @param int|null $rank Rank.
     *
     * @return void
     */
    public function set_rank( ?int $rank ): void {
        $this->rank = $rank;
    }

    /**
     * Set club
     *
     * @param Club|null $club Club entity.
     *
     * @return void
     */
    public function set_club( ?Club $club ): void {
        $this->club = $club;
    }

    /**
     * Set club id
     *
     * @param int|null $club_id Club id.
     *
     * @return void
     */
    public function set_club_id( ?int $club_id ): void {
        $this->club_id = $club_id;
    }

    /**
     * Set players
     *
     * @param array $players Players array.
     *
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Set title
     *
     * @param string $title Title.
     *
     * @return void
     */
    public function set_title( string $title ): void {
        $this->title = $title;
    }

    /**
     * Set display class
     *
     * @param string $display_class Display class.
     *
     * @return void
     */
    public function set_class( string $display_class ): void {
        $this->class = $display_class;
    }

    /**
     * Set formatted points
     *
     * @param array $points_formatted Formatted points.
     *
     * @return void
     */
    public function set_points_formatted( array $points_formatted ): void {
        $this->points_formatted = $points_formatted;
    }

    /**
     * Set is withdrawn
     *
     * @param bool $is_withdrawn Withdrawn status.
     *
     * @return void
     */
    public function set_withdrawn( bool $is_withdrawn ): void {
        $this->is_withdrawn = $is_withdrawn;
    }

    /**
     * Set match counts
     *
     * @param int $done Done matches.
     * @param int $won Won matches.
     * @param int $draw Draw matches.
     * @param int $lost Lost matches.
     *
     * @return void
     */
    public function set_match_counts( int $done, int $won, int $draw, int $lost ): void {
        $this->done_matches = $done;
        $this->won_matches  = $won;
        $this->draw_matches = $draw;
        $this->lost_matches = $lost;
        $this->win_percent();
    }

    /**
     * Update standings statistics
     *
     * @param array            $points Points breakdown.
     * @param int|float|string $diff Points difference.
     * @param array            $points_2 Secondary points breakdown.
     * @param array|null       $custom Custom stats.
     *
     * @return void
     */
    public function update_standings_stats( array $points, int|float|string $diff, array $points_2, ?array $custom = null ): void {
        $this->points   = $points;
        $this->diff     = is_float( $diff ) ? (int) $diff : $diff;
        $this->points_2 = $points_2;
        $this->custom   = $custom;
    }

    /**
     * Set points
     *
     * @param array $points Points breakdown.
     *
     * @return void
     */
    public function set_points( array $points ): void {
        $this->points = $points;
    }

    /**
     * Set points plus
     *
     * @param int|float $points_plus Points plus.
     *
     * @return void
     */
    public function set_points_plus( int|float $points_plus ): void {
        $this->points_plus = (float) $points_plus;
    }

    /**
     * Set points minus
     *
     * @param int|float $points_minus Points minus.
     *
     * @return void
     */
    public function set_points_minus( int|float $points_minus ): void {
        $this->points_minus = (float) $points_minus;
    }

    /**
     * Set points diff
     *
     * @param int|float|string $diff Difference.
     *
     * @return void
     */
    public function set_diff( int|float|string $diff ): void {
        $this->diff = is_float( $diff ) ? (int) $diff : $diff;
    }

    /**
     * Set points 2 plus
     *
     * @param int $points_plus Secondary points plus.
     *
     * @return void
     */
    public function set_points_2_plus( int $points_plus ): void {
        $this->points_2_plus = $points_plus;
    }

    /**
     * Set points 2 minus
     *
     * @param int $points_minus Secondary points minus.
     *
     * @return void
     */
    public function set_points_2_minus( int $points_minus ): void {
        $this->points_2_minus = $points_minus;
    }

    /**
     * Set custom data
     *
     * @param string|array|null $custom Custom metadata.
     *
     * @return void
     */
    public function set_custom( string|array|null $custom ): void {
        $this->custom = $custom;
    }

    /**
     * Set previous/old rank
     *
     * @param int|null $old_rank Previous rank.
     *
     * @return void
     */
    public function set_old_rank( ?int $old_rank ): void {
        $this->old_rank = $old_rank;
    }

    /**
     * Record previous rank
     *
     * @param int|null $old_rank Previous rank.
     *
     * @return void
     */
    public function record_previous_rank( ?int $old_rank = null ): void {
        $this->old_rank = $old_rank ?? $this->rank;
    }

    /**
     * Set league title / display name
     *
     * @param string $league League title.
     *
     * @return void
     */
    public function set_league( string $league ): void {
        $this->league = $league;
    }

    /**
     * Set info details object
     *
     * @param object|null $info Team details object.
     *
     * @return void
     */
    public function set_info( ?object $info ): void {
        $this->info = $info;
    }

    /**
     * Set roster
     *
     * @param mixed $roster Roster data.
     *
     * @return void
     */
    public function set_roster( mixed $roster ): void {
        $this->roster = $roster;
    }

    /**
     * Apply standings calculation metrics to the team.
     *
     * @param array $metrics Associative array of standings metrics.
     *
     * @return void
     */
    public function apply_standings_metrics( array $metrics ): void {
        if ( array_key_exists( 'sets_won', $metrics ) ) {
            $this->sets_won = (int) $metrics['sets_won'];
        }
        if ( array_key_exists( 'sets_allowed', $metrics ) ) {
            $this->sets_allowed = (int) $metrics['sets_allowed'];
        }
        if ( array_key_exists( 'sets_shared', $metrics ) ) {
            $this->sets_shared = (string) $metrics['sets_shared'];
        }
        if ( array_key_exists( 'games_won', $metrics ) ) {
            $this->games_won = (int) $metrics['games_won'];
        }
        if ( array_key_exists( 'games_allowed', $metrics ) ) {
            $this->games_allowed = (int) $metrics['games_allowed'];
        }
        if ( array_key_exists( 'straight_set', $metrics ) ) {
            $this->straight_set = (array) $metrics['straight_set'];
        }
        if ( array_key_exists( 'split_set', $metrics ) ) {
            $this->split_set = (array) $metrics['split_set'];
        }
        if ( array_key_exists( 'no_team', $metrics ) ) {
            $this->no_team = (string) $metrics['no_team'];
        }
        if ( array_key_exists( 'no_player', $metrics ) ) {
            $this->no_player = (string) $metrics['no_player'];
        }
        if ( array_key_exists( 'rubbers_won', $metrics ) ) {
            $this->rubbers_won = (int) $metrics['rubbers_won'];
        }
        if ( array_key_exists( 'rubbers_shared', $metrics ) ) {
            $this->rubbers_shared = (int) $metrics['rubbers_shared'];
        }
        if ( array_key_exists( 'matches_won', $metrics ) ) {
            $this->matches_won = (int) $metrics['matches_won'];
        }
        if ( array_key_exists( 'matches_shared', $metrics ) ) {
            $this->matches_shared = (int) $metrics['matches_shared'];
        }
    }
}
