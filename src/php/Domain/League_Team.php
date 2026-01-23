<?php
/**
 * League_Team API: League Team class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage League_Team
 */

namespace Racketmanager\Domain;

use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * Class to implement the League_Team object
 */
final class League_Team {
    /**
     * Number of done matches
     *
     * @var int
     */
    public int $done_matches = 0;

    /**
     * Number of won matches
     *
     * @var int
     */
    public int $won_matches = 0;

    /**
     * Number of draw matches
     *
     * @var int
     */
    public int $draw_matches = 0;

    /**
     * Number of lost matches
     *
     * @var int
     */
    public int $lost_matches = 0;

    /**
     * Percentage of won matches
     *
     * @var int|float
     */
    public int|float $win_percent = 0;

    /**
     * Retrieve team instance
     *
     * @param int $team_id
     */
    private int $team_id;
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
    public bool $home;
    /**
     * Team id variable
     *
     * @var ?int
     */
    public ?int $id = null;
    /**
     * Table id variable
     *
     * @var int
     */
    public int $table_id;
    /**
     * Title variable
     *
     * @var ?string
     */
    public ?string $title = null;
    /**
     * Stadium variable
     *
     * @var string
     */
    public string $stadium;
    /**
     * Profile variable
     *
     * @var string
     */
    public string $profile = '1';
    /**
     * Status variable
     *
     * @var ?string
     */
    public ?string $status = null;
    /**
     * Status text variable
     *
     * @var string
     */
    public string $status_text;
    /**
     * Status icon variable
     *
     * @var string
     */
    public string $status_icon;
    /**
     * Club variable
     *
     * @var int
     */
    public int $club_id;
    /**
     * Club object variable
     *
     * @var Club|null
     */
    public Club|null $club;
    /**
     * Positive points variable
     *
     * @var float
     */
    public float $points_plus = 0;
    /**
     * Additional points variable
     *
     * @var int
     */
    public int $add_points = 0;
    /**
     * Points variable
     *
     * @var array
     */
    public array $points;
    /**
     * Sets won variable
     *
     * @var int
     */
    public int $sets_won = 0;
    /**
     * Sets lost variable
     *
     * @var int
     */
    public int $sets_allowed = 0;
    /**
     * Games won variable
     *
     * @var int
     */
    public int $games_won = 0;
    /**
     * Games lost variable
     *
     * @var int
     */
    public int $games_allowed = 0;
    /**
     * Points minus variable
     *
     * @var float
     */
    public float $points_minus = 0;
    /**
     * Secondary points variable
     *
     * @var array
     */
    public array $points_2 = array();
    /**
     * Secondary points plus variable
     *
     * @var int
     */
    public int $points_2_plus = 0;
    /**
     * Secondary points minus variable
     *
     * @var int
     */
    public int $points_2_minus = 0;
    /**
     * Points difference variable
     *
     * @var string|int
     */
    public string|int $diff = 0;
    /**
     * Roster variable
     *
     * @var array
     */
    public mixed $roster = null;
    /**
     * Player variable
     *
     * @var int
     */
    public int $player;
    /**
     * Player id variable
     *
     * @var int
     */
    public int $player_id;
    /**
     * League id variable
     *
     * @var int
     */
    public int $league_id;
    /**
     * Next match variable
     *
     * @var object
     */
    public object $next_match;
    /**
     * Previous match variable
     *
     * @var object
     */
    public object $prev_match;
    /**
     * Season variable
     *
     * @var string
     */
    public string $season;
    /**
     * Custom variable
     *
     * @var string|array|null
     */
    public string|array|null $custom = null;
    /**
     * Display class variable
     *
     * @var string
     */
    public string $class;
    /**
     * Captain name variable
     *
     * @var ?int
     */
    public ?int $captain;
    /**
     * Formatted points variable
     *
     * @var array
     */
    public array $points_formatted;
    /**
     * Captain id variable
     *
     * @var ?int
     */
    public ?int $captain_id = null;
    /**
     * Contact number variable
     *
     * @var string
     */
    public string $contactno;
    /**
     * Content email variable
     *
     * @var string
     */
    public string $contactemail;
    /**
     * Match day variable
     *
     * @var ?string
     */
    public ?string $match_day = null;
    /**
     * Match time variable
     *
     * @var ?string
     */
    public ?string $match_time = null;
    /**
     * Old rank
     *
     * @var int
     */
    public int $old_rank;
    /**
     * Team ref variable
     *
     * @var string
     */
    public string $team_ref;
    /**
     * Team type variable
     *
     * @var string|null
     */
    public ?string $team_type = null;
    /**
     * Is withdrawn variable
     *
     * @var boolean
     */
    public bool $is_withdrawn;
    /**
     * Players variable
     *
     * @var array
     */
    public array $players = array();
    /**
     * Group variable
     *
     * @var string
     */
    public ?string $group = null;
    /**
     * Rank variable
     *
     * @var string
     */
    public ?string $rank = null;
    /**
     * Rating variable
     *
     * @var string|null
     */
    public ?string $rating = null;
    /**
     * Straight set variable
     *
     * @var array
     */
    public array $straight_set;
    /**
     * Split set variable
     *
     * @var array
     */
    public array $split_set;
    /**
     * Sets shared variable
     *
     * @var string
     */
    public string $sets_shared;
    /**
     * No team variable
     *
     * @var string
     */
    public string $no_team;
    /**
     * No player variable
     *
     * @var string
     */
    public string $no_player;
    /**
     * Rubbers won variable
     *
     * @var int
     */
    public int $rubbers_won;
    /**
     * Rubbers shared variable
     *
     * @var int
     */
    public int $rubbers_shared;
    /**
     * Matches won variable
     *
     * @var int
     */
    public int $matches_won;
    /**
     * Matches shared variable
     *
     * @var int
     */
    public int $matches_shared;
    /**
     * Info variable
     *
     * @var object
     */
    public object $info;
    /**
     * League variable
     *
     * @var string
     */
    public string $league;
    /**
     * League status variable
     *
     * @var string
     */
    public string $league_status;
    /**
     * Get instance function
     *
     * @param int $league_team_id league team id.
     *
     * @return boolean|object
     */
    public static function get_instance( int $league_team_id ): bool|League_Team {
        global $wpdb;
        if ( ! $league_team_id ) {
            return false;
        }

        $league_team = wp_cache_get( $league_team_id, 'league-teams' );

        if ( ! $league_team ) {
            $league_team = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT B.`id` AS `id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating` FROM $wpdb->racketmanager_teams B INNER JOIN $wpdb->racketmanager_league_teams A ON B.id = A.team_id WHERE A.`id` = %d LIMIT 1",
                    $league_team_id
                )
            ); // db call ok.

            if ( ! $league_team ) {
                return false;
            }
            $league_team = new League_Team( $league_team );

            wp_cache_set( $league_team->id, $league_team, 'league-teams' );
        }

        return $league_team;
    }

    /**
     * Constructor
     *
     * @param object|null $league_team League_Team object.
     */
    public function __construct( ?object $league_team = null ) {
        if ( ! is_null( $league_team ) ) {
            if ( empty( $league_team->custom ) ) {
                $league_team->custom = array();
            } else {
                $league_team->custom = stripslashes_deep( (array) maybe_unserialize( $league_team->custom ) );
                $league_team         = (object) array_merge( (array) $league_team, (array) $league_team->custom );
            }
            foreach ( get_object_vars( $league_team ) as $key => $value ) {
                $key        = trim( $key );
                $this->$key = $value;
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
            if ( ! empty( $this->club_id ) ) {
                $this->club = get_club( $this->club_id );
            } else {
                $this->club = null;
            }
            $this->roster = maybe_unserialize( $this->roster );
            if ( 'P' === $this->team_type && null !== $this->roster ) {
                $team          = get_team( $this->id );
                $this->players = $team->players;
            }
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
                $team_name = Util::generate_team_name( $this->title );
                if ( ! empty( $team_name ) ) {
                    $this->team_ref = $this->title;
                    $this->title    = $team_name;
                }
            }
        }
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
        return $this->team_id;
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
        return $this->diff;
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
     * Get profile id
     *
     * @return int|null Profile identifier
     */
    public function get_profile(): ?int {
        return $this->profile;
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
     * @param int|string $id Identifier
     *
     * @return void
     */
    public function set_id( $id ): void {
        $this->id = $id;
    }
    public function set_captain( int $captain ): void {
        $this->captain = $captain;
    }
    public function set_match_day( string $match_day ): void {
        $this->match_day = $match_day;
    }
    public function set_match_time( string $match_time ): void {
        $this->match_time = $match_time;
    }
    public function set_entered_state( string $entered_state ): void {
        $this->profile = $entered_state;
    }
    public function set_status( ?string $status = null ): void {
        $this->status = $status;
    }

    public function add_details( int $captain, string $match_day, string $match_time ): void {
        global $wpdb;
        $this->captain = $captain;
        $this->match_day  = $match_day;
        $this->match_time = $match_time;

        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `captain` = %d, `match_day` = %s, `match_time` = %s WHERE `id` = %d",
                $this->captain,
                $this->match_day,
                $this->match_time,
                $this->table_id
            )
        );
    }
    /**
     * Compute win percentage
     */
    public function win_percent(): void {
        $this->win_percent = $this->done_matches > 0 ? round( ( $this->won_matches + $this->draw_matches / 2 ) / $this->done_matches, 3 ) * $this->pct_mult : 0;
    }

    /**
     * Get next match
     *
     * @return object next Match object
     */
    public function get_next_match(): object {
        $league           = get_league( $this->league_id );
        $this->next_match = $league->get_matches(
            array(
                'team_id'     => $this->id,
                'time'        => 'next',
                'limit'       => 1,
                'reset_limit' => true,
            )
        );

        return $this->next_match;
    }


    /**
     * Get previous match
     *
     * @return object previous Match object
     */
    public function get_prev_match(): object {
        $league           = get_league( $this->league_id );
        $this->prev_match = $league->get_matches(
            array(
                'team_id'     => $this->id,
                'time'        => 'prev',
                'limit'       => 1,
                'reset_limit' => true,
            )
        );
        if ( $this->prev_match && '' === $this->prev_match->score ) {
            $this->prev_match->score = 'N/A';
        }
        return $this->prev_match;
    }
    /**
     * Get number of finished matches for team
     *
     * @return int
     */
    public function get_num_done_matches(): int {
        $league = get_league( $this->league_id );
        $num_matches = $league->get_matches(
            array(
                'count'            => true,
                'team_id'          => $this->id,
                'home_points'      => 'not_empty',
                'away_points'      => 'not_empty',
                'limit'            => false,
                'cache'            => false,
                'match_day'        => -1,
                'reset_query_args' => true,
                'withdrawn'        => false,
                'status'           => array(
                    'status_code' => 'Cancelled',
                    'compare'     => 'not',
                ),
            )
        );
        $num_matches = apply_filters( 'racketmanager_done_matches_' . $league->sport, $num_matches, $this->id, $league->id );

        $this->done_matches = $num_matches;

        // re-compute win percentage.
        $this->win_percent();

        return $num_matches;
    }

    /**
     * Get number of won matches
     *
     * @return int
     */
    public function get_num_won_matches(): int {
        $league = get_league( $this->league_id );
        $num_won = $league->get_matches(
            array(
                'count'            => true,
                'winner_id'        => $this->id,
                'limit'            => false,
                'cache'            => false,
                'match_day'        => -1,
                'reset_query_args' => true,
                'withdrawn'        => false,
                'status'           => array(
                    'status_code' => 'Cancelled',
                    'compare'     => 'not',
                ),
            )
        );
        $num_won = apply_filters( 'racketmanager_won_matches_' . $league->sport, $num_won, $this->id, $league->id );

        $this->won_matches = $num_won;

        // re-compute win percentage.
        $this->win_percent();

        return $num_won;
    }

    /**
     * Get number of draw matches
     *
     * @return int
     */
    public function get_num_draw_matches(): int {
        $league = get_league( $this->league_id );
        $num_draw = $league->get_matches(
            array(
                'count'            => true,
                'team_id'          => $this->id,
                'winner_id'        => -1,
                'loser_id'         => -1,
                'limit'            => false,
                'cache'            => false,
                'match_day'        => -1,
                'reset_query_args' => true,
                'withdrawn'        => false,
                'status'           => array(
                    'status_code' => 'Cancelled',
                    'compare'     => 'not',
                ),
            )
        );
        $num_draw = apply_filters( 'racketmanager_tie_matches_' . $league->sport, $num_draw, $this->id, $league->id );

        $this->draw_matches = $num_draw;

        // re-compute win percentage.
        $this->win_percent();

        return $num_draw;
    }

    /**
     * Get number of lost matches
     *
     * @return int
     */
    public function get_num_lost_matches(): int {
        $league = get_league( $this->league_id );
        $num_lost = $league->get_matches(
            array(
                'count'            => true,
                'loser_id'         => $this->id,
                'limit'            => false,
                'cache'            => false,
                'match_day'        => -1,
                'reset_query_args' => true,
                'withdrawn'        => false,
                'status'           => array(
                    'status_code' => 'Cancelled',
                    'compare'     => 'not',
                ),
            )
        );
        $num_lost = apply_filters( 'racketmanager_lost_matches_' . $league->sport, $num_lost, $this->id, $league->id );

        $this->lost_matches = $num_lost;

        // re-compute win percentage.
        $this->win_percent();

        return $num_lost;
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
     * @param float $rating rating.
     */
    public function set_rating( float $rating ): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `rating` = %f WHERE `id` = %d",
                $rating,
                $this->table_id
            )
        );
    }
    /**
     * Update constitution settings
     *
     * @param int $league_id league id.
     * @param string $status status.
     */
    public function update_constitution( int $league_id, string $status ): void {
        global $wpdb;
        $this->league_id = $league_id;
        $this->status    = $status;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `league_id` = %d, `status` = %s WHERE `id` = %d",
                $league_id,
                $this->status,
                $this->table_id
            )
        );
        wp_cache_set( $this->id, $this, 'league-teams' );
    }
    /**
     * Add/remove points
     *
     * @param float $points points to add/remove.
     */
    public function amend_points( float $points ): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `add_points` = %s WHERE `id` = %d",
                $points,
                $this->table_id,
            )
        );
        $this->add_points = $points;
        $league = get_league( $this->league_id );
        $league?->set_teams_rank( $this->season );
        wp_cache_set( $this->id, $this, 'league-teams' );
    }
    /**
     * Persist current computed/edited stats to database
     *
     * Updates aggregate fields such as matches played/won/lost/drawn and
     * points (plus/minus/secondary) and custom data for this league team.
     *
     * @return void
     */
    public function update(): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `done_matches` = %d, `won_matches` = %d, `lost_matches` = %d, `draw_matches` = %d, `points_plus` = %f, `points_minus` = %f, `points_2_plus` = %d, `points_2_minus` = %d, `add_points` = %d, `custom` = %s WHERE `id` = %d",
                $this->done_matches,
                $this->won_matches,
                $this->lost_matches,
                $this->draw_matches,
                $this->points_plus,
                $this->points_minus,
                $this->points_2_plus,
                $this->points_2_minus,
                $this->add_points,
                maybe_serialize( $this->custom ),
                $this->table_id,
            )
        );
    }
    /**
     * Update Table
     *
     * @param int $league_id league id.
     * @param int $rank rank.
     * @param string $status status.
     * @param string $profile profile.
     */
    public function set_constitution_rank( int $league_id, int $rank, string $status, string $profile ): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `league_id` = %d, `rank` = %d, `status` = %s, `profile` = %d WHERE `id` = %d",
                $league_id,
                $rank,
                $status,
                $profile,
                $this->table_id
            )
        );
    }

    /**
     * Set team details for given player
     *
     * @param int|string $player_id Player identifier
     *
     * @return void
     */
    public function set_team_details( $player_id ) {

    }
}
