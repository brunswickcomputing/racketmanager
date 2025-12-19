<?php
/**
 * League API: League class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage League
 */

namespace Racketmanager\Domain;

use Racketmanager\Services\Championship;
use Racketmanager\Services\Schedule_Round_Robin;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function get_query_var;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_team;
use function Racketmanager\show_score;
use function Racketmanager\show_standings;
use function Racketmanager\withdrawn_team_email;

/**
 * Class to implement the League object
 */
class League {

    /**
     * League ID
     *
     * @var ?int
     */
    public ?int $id = null;

    /**
     * League title
     *
     * @var string
     */
    public string $title;
    /**
     * League name
     *
     * @var string
     */
    public string $name;

    /**
     * Seasons data
     *
     * @var array|string
     */
    public string|array $seasons = array();
    /**
     * Sequence
     *
     * @var string|null
     */
    public ?string $sequence = null;
    /**
     * Number of seasons
     *
     * @var int
     */
    public int $num_seasons = 0;

    /**
     * Sport type
     *
     * @var string
     */
    public string $sport = 'default';

    /**
     * Point rule
     *
     * @var string
     */
    public string $point_rule = 'three';

    /**
     * Primary points format
     *
     * @var string
     */
    public string $point_format = '%d-%d';

    /**
     * Secondary points format
     *
     * @var string
     */
    public string $point_2_format = '%d-%d';

    /**
     * League mode
     *
     * @var string
     */
    public string $mode = 'default';

    /**
     * Standings table layout settings
     *
     * @var array
     */
    public array $standings;
    /**
     * Number of teams per page in list
     *
     * @var int
     */
    public int $num_teams_per_page = 10;

    /**
     * Number of pages for teams
     *
     * @var int
     */
    public int $num_pages_teams = 0;

    /**
     * Current page for teams
     *
     * @var int
     */
    public int $current_page_teams = 1;

    /**
     * Teams pagination
     *
     * @var string|null
     */
    public ?string $pagination_teams = '';

    /**
     * Number of matches per page
     *
     * @var int
     */
    public int $num_matches_per_page = 30;

    /**
     * Default display filter for matches
     *
     * @var string
     */
    public string $match_display = 'current_match_day';

    /**
     * Number of pages for matches
     *
     * @var int
     */
    public int $num_pages_matches = 0;

    /**
     * Current page for matches
     *
     * @var int
     */
    public int $current_page_matches = 1;

    /**
     * Matches pagination
     *
     * @var string|null
     */
    public ?string $pagination_matches = '';

    /**
     * Slideshow options
     *
     * @var array
     */
    public array $slideshow = array(
        'season'      => 'latest',
        'num_matches' => 0,
    );

    /**
     * Team groups
     *
     * @var string
     */
    public string $groups;

    /**
     * Current team group
     *
     * @var string
     */
    public string $current_group = '';

    /**
     * Teams
     *
     * @var array
     */
    public array $teams = array();
    /**
     * Number of teams
     *
     * @var int
     */
    public int $num_teams = 0;
    /**
     * Total number of teams
     *
     * @var int
     */
    public int $num_teams_total = 0;
    /**
     * Matches
     *
     * @var array
     */
    public array $matches = array();

    /**
     * Total number of matches
     *
     * @var int
     */
    public int $num_matches_total = 0;

    /**
     * Number of matches
     *
     * @var int
     */
    public int $num_matches = 0;

    /**
     * Current season
     *
     * @var array
     */
    public array $current_season = array();

    /**
     * Number of match days
     *
     * @var int
     */
    public int $num_match_days = 0;

    /**
     * Current match day
     *
     * @var int
     */
    public int $match_day = 0;

    /**
     * Query arguments
     *
     * @var array
     */
    private array $query_args = array();

    /**
     * Team database query args
     *
     * @var array
     */
    private array $team_query_args = array(
        'limit'            => false,
        'group'            => '',
        'season'           => '',
        'rank'             => 0,
        'orderby'          => array( 'rank' => 'ASC' ),
        'home'             => false,
        'ids'              => array(),
        'cache'            => true,
        'reset_query_args' => false,
        'get_details'      => false,
        'status'           => false,
        'club'             => false,
        'team_name'        => '',
        'team_id'          => '',
        'count'            => false,
        'active'           => false,
    );

    /**
     * Team query argument types
     *
     * @var array
     */
    private array $team_query_args_types = array(
        'limit'            => 'numeric',
        'group'            => 'string',
        'season'           => 'string',
        'rank'             => 'numeric',
        'orderby'          => 'array',
        'home'             => 'boolean',
        'ids'              => 'array_numeric',
        'cache'            => 'boolean',
        'reset_query_args' => 'boolean',
        'get_details'      => 'boolean',
        'status'           => 'string',
        'club'             => 'numeric',
        'team_name'        => 'string',
        'team_id'          => 'numeric',
        'count'            => 'boolean',
        'active'           => 'boolean',
    );

    /**
     * Match query arguments
     *
     * @var array
     */
    private array $match_query_args = array(
        'limit'            => true,
        'group'            => '',
        'season'           => '',
        'final'            => '',
        'match_day'        => -1,
        'match_date'       => false,
        'time'             => '',
        'home_only'        => false,
        'count'            => false,
        'orderby'          => array(
            'date' => 'ASC',
            'id'   => 'ASC',
        ),
        'standingstable'   => false,
        'cache'            => true,
        'team_id'          => 0,
        'home_team'        => '',
        'away_team'        => '',
        'team_pair'        => array(),
        'winner_id'        => false,
        'loser_id'         => false,
        'home_points'      => false,
        'away_points'      => false,
        'mode'             => '',
        'reset_limit'      => true,
        'reset_query_args' => false,
        'update_results'   => false,
        'confirmed'        => false,
        'leg'              => false,
        'player'           => false,
        'withdrawn'        => true,
        'club'             => false,
        'pending'          => false,
        'status'           => array(),
        'days'             => false,
        'history'          => false,
    );

    /**
     * Match query argument types
     *
     * @var array
     */
    private array $match_query_args_types = array(
        'limit'            => 'numeric',
        'group'            => 'string',
        'season'           => 'string',
        'final'            => 'string',
        'match_day'        => 'numeric',
        'match_date'       => 'string',
        'time'             => 'string',
        'home_only'        => 'boolean',
        'count'            => 'boolean',
        'orderby'          => 'array',
        'standingstable'   => 'boolean',
        'cache'            => 'boolean',
        'team_id'          => 'numeric',
        'home_team'        => 'string',
        'away_team'        => 'string',
        'team_pair'        => 'array',
        'winner_id'        => 'numeric',
        'loser_id'         => 'numeric',
        'home_points'      => 'string',
        'away_points'      => 'string',
        'mode'             => 'string',
        'reset_limit'      => 'boolean',
        'reset_query_args' => 'boolean',
        'update_results'   => 'boolean',
        'confirmed'        => 'boolean',
        'leg'              => 'numeric',
        'player'           => 'numeric',
        'withdrawn'        => 'boolean',
        'club'             => 'numeric',
        'pending'          => 'boolean',
        'status'           => 'array',
        'days'             => 'numeric',
        'history'          => 'boolean',
    );
    /**
     * Settings
     *
     * @var array|string
     */
    public string|array $settings;
    /**
     * Team offsets indexed by ID
     *
     * @var array
     */
    public array $team_index = array();
    /**
     * Current team
     *
     * @var int
     */
    public int $current_team = -1;

    /**
     * Team is selected?
     *
     * @var boolean
     */
    public bool $is_selected_team = false;
    /**
     * Current match
     *
     * @var int
     */
    public int $current_match = -1;

    /**
     * Match is selected
     *
     * @var boolean
     */
    public bool $is_selected_match = false;

    /**
     * Toggle match day selection menu display
     *
     * @var boolean
     */
    public bool $show_match_day_selection = true;
    /**
     * Is this an archive
     *
     * @var boolean
     */
    public bool $is_archive = false;

    /**
     * Set archive tab
     *
     * @var int
     */
    public int $archive_tab = 0;

    /**
     * Save templates for whole league or archive display
     *
     * @var array
     */
    public array $templates = array();
    /**
     *
     * Championship flag
     *
     * @var boolean
     */
    public bool $is_championship = false;

    /**
     * Championship object
     *
     * @var Championship|null
     */
    public ?Championship $championship = null;

    /**
     * Event id
     *
     * @var int
     */
    public int $event_id;

    /**
     * Number of sets
     *
     * @var int
     */
    public int $num_sets = 0;

    /**
     * Number of rubbers
     *
     * @var int|null
     */
    public ?int $num_rubbers = null;

    /**
     * Number of sets required to win a match
     *
     * @var int
     */
    public int $num_sets_to_win = 1;

    /**
     * Competition type
     *
     * @var string
     */
    public string $competition_type = '';

    /**
     * Type
     *
     * @var string
     */
    public string $type = '';

    /**
     * Event
     *
     * @var Event
     */
    public Event $event;

    /**
     * Scoring
     *
     * @var string|null
     */
    public ?string $scoring = '';

    /**
     * Custom team input field keys and translated labels
     *
     * @var array
     */
    public array $fields_team = array();

    /**
     * Custom match input field keys and translated labels
     *
     * @var array
     */
    protected array $fields_match = array();
    /**
     * Is final flag
     *
     * @var boolean
     */
    public bool $is_final = false;
    /**
     * Entry type
     *
     * @var string
     */
    public string $entry_type;
    /**
     * Match template type
     *
     * @var string
     */
    public string $matches_template_type;
    /**
     * Season
     *
     * @var string
     */
    public string $season;
    /**
     * Players
     *
     * @var array
     */
    public array $players;
    /**
     * Team
     *
     * @var object
     */
    public object $team;
    /**
     * Player
     *
     * @var object
     */
    public object $player;
    /**
     * Finals
     *
     * @var array
     */
    public array $finals;
    /**
     * Retrieve league instance
     *
     * @param int|string $league_id league id.
     */
    public static function get_instance( int|string $league_id ) {
        global $wpdb;

        if ( is_numeric( $league_id ) ) {
            $search = $wpdb->prepare(
                '`id` = %d',
                intval( $league_id )
            );
        } else {
            $search = $wpdb->prepare(
                '`title` = %s',
                $league_id
            );
        }
        if ( ! $league_id ) {
            return false;
        }
        $league = wp_cache_get( $league_id, 'leagues' );
        if ( ! $league ) {
            $league = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `title`, `id`, `settings`, `event_id`, `seasons`, `sequence` FROM $wpdb->racketmanager WHERE " . $search . ' LIMIT 1'
            );
            if ( $league ) {
                $event = get_event( $league->event_id );
            }
            if ( empty( $league ) || empty( $event ) ) {
                return false;
            }
            $league->settings = (array) maybe_unserialize( $league->settings );
            $league           = (object) array_merge( (array) $league, $league->settings );
            // check if specific sports class exists.
            if ( ! isset( $event->competition->sport ) ) {
                $league->sport = '';
            }
            $instance = 'Racketmanager\sports\League_' . ucfirst( $event->competition->sport );

            if ( class_exists( $instance ) ) {
                $league = new $instance( $league );
            } else {
                $league = new League( $league );
            }
            wp_cache_set( $league->id, $league, 'leagues' );
        }
        return $league;
    }

    /**
     * Constructor
     *
     * @param object $league League object.
     */
    public function __construct( object $league ) {
        if ( isset( $league->settings ) ) {
            $league->settings = (array) maybe_unserialize( $league->settings );
            $league           = (object) array_merge( (array) $league, $league->settings );
        } else {
            $league->settings = array();
        }

        foreach ( get_object_vars( $league ) as $key => $value ) {
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key, $value );
            } else {
                $this->$key = $value;
            }
        }

        $this->title = stripslashes( $this->title );
        $this->name  = $this->title;
        $event       = get_event( $this->event_id );
        $this->event = $event;
        // set seasons.
        if ( empty( $this->seasons ) ) {
            $this->seasons = array();
        }
        $this->seasons     = (array) maybe_unserialize( $this->seasons );
        $event_seasons     = $this->event->get_seasons();
        $this->num_seasons = count( $event_seasons );
        // set season to latest.
        $this->set_season();
        $this->groups          = trim( $this->groups ?? '' );
        $this->mode            = $event->competition->mode;
        $this->num_sets        = $event->num_sets;
        $this->num_sets_to_win = floor( $this->num_sets / 2 ) + 1;
        $this->num_rubbers     = $event->num_rubbers;
        $this->type            = $event->type;
        $this->point_rule      = $event->competition->point_rule;
        $this->sport           = $event->competition->sport;
        $this->scoring         = $event->scoring ?? null;
        $this->set_match_query_args();
        $this->set_num_matches(); // for pagination.
        $this->set_num_teams( true ); // get total number of teams.
        $this->standings      = $event->standings;
        $this->point_format   = $event->competition->point_format;
        $this->point_2_format = $event->competition->point_2_format;
        // set default standings display options for additional team fields.
        if ( count( $this->fields_team ) > 0 ) {
            foreach ( $this->fields_team as $key => $data ) {
                if ( ! isset( $this->standings[ $key ] ) ) {
                    $this->standings[ $key ] = 1;
                }
            }
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // set selected team marker.
        if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
            $this->current_team     = intval( $_GET[ 'team_' . $this->id ] );
            $this->is_selected_team = true;
        }

        // set selected match marker.
        if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
            $this->current_match     = intval( $_GET[ 'match_' . $this->id ] );
            $this->is_selected_match = true;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        // Championship.
        if ( 'championship' === $this->mode ) {
            $this->is_championship = true;
            $this->championship    = new Championship( $this, $this->settings );
        }

        // add actions & filter.
        add_filter( 'racketmanager_import_matches_' . $this->sport, array( &$this, 'import_matches' ), 10, 4 );
        add_filter( 'racketmanager_import_teams_' . $this->sport, array( &$this, 'import_teams' ), 10, 3 );
    }

    /**
     * Get the id
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function get_name(): string {
        return $this->title;
    }

    /**
     * Get the settings
     *
     * @return array|string
     */
    public function get_settings(): array|string {
        return $this->settings;
    }

    /**
     * Get the seasons
     *
     * @return array|string
     */
    public function get_seasons(): array|string {
        return $this->seasons;
    }

    /**
     * Get the sequence
     *
     * @return string|null
     */
    public function get_sequence(): ?string {
        return $this->sequence;
    }

    /**
     * Get the event id
     *
     * @return int
     */
    public function get_event_id(): int {
        return $this->event_id;
    }

    /**
     * Set the id
     *
     * @param int $id
     *
     * @return void
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    /**
     * Edit League
     *
     * @param string $title title.
     * @param string|null $sequence sequence.
     */
    public function update( string $title, ?string $sequence = null ): void {
        global $wpdb;
        $this->title = $title;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager SET `title` = %s WHERE `id` = %d",
                $this->title,
                $this->id
            )
        );
        if ( ! empty( $sequence ) ) {
            $this->sequence = $sequence;
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager SET `sequence` = %s WHERE `id` = %d",
                    $this->sequence,
                    $this->id,
                )
            );
        }
        wp_cache_set( $this->id, $this, 'leagues' );
    }

    /**
     * Delete League
     */
    public function delete(): void {
        global $wpdb;
        $matches = $this->get_matches( array() );
        if ( $matches ) {
            $this->delete_matches( $matches );
        }
        // remove tables.
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_league_teams WHERE `league_id` = %d",
                $this->id
            )
        );
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager WHERE `id` = %d",
                $this->id
            )
        );
    }
    /**
     * Delete matches from League for a season
     *
     * @param string $season season.
     */
    public function delete_season_matches( string $season ): void {
        $matches = $this->get_matches(
            array(
                'season' => $season,
                'final'  => 'all',
            )
        );
        if ( $matches ) {
            $this->delete_matches( $matches );
        }
    }
    /**
     * Delete matches from League
     *
     * @param array $matches array of matches.
     */
    public function delete_matches( array $matches ): void {
        foreach ( $matches as $match ) {
            $match = get_match( $match->id );
            $match->delete();
        }
    }
    /**
     * Delete team from League
     *
     * @param integer $team team id.
     * @param string $season season.
     */
    public function delete_team( int $team, string $season ): void {
        global $wpdb;
        $matches = $this->get_matches(
            array(
                'team_id' => $team,
                'season'  => $season,
            )
        );
        if ( $matches ) {
            $this->delete_matches( $matches );
        }
        // remove tables.
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_league_teams WHERE `team_id` = %d AND `league_id` = %d and `season` = %s",
                $team,
                $this->id,
                $season
            )
        );
    }
    /**
     * Withdraw team from League
     *
     * @param integer $team_id team id.
     * @param string $season season.
     */
    public function withdraw_team( int $team_id, string $season ): void {
        global $wpdb;
        $match_args                     = array();
        $match_args['team_id']          = $team_id;
        $match_args['season']           = $season;
        $match_args['reset_query_args'] = true;
        if ( $this->is_championship ) {
            $match_args['pending'] = true;
        }
        // update matches.
        $matches = $this->get_matches( $match_args );
        foreach ( $matches as $match ) {
            $match = get_match( $match );
            if ( $match ) {
                $status = empty( $match->status ) ? null : Util_Lookup::get_match_status( $match->status );
                if ( 'Withdrawn' !== $status ) {
                    if ( $this->is_championship ) {
                        if ( intval( $match->home_team ) === $team_id ) {
                            $match_status = 'walkover_player2';
                        } else {
                            $match_status = 'walkover_player1';
                        }
                        if ( empty( $match->leg ) || 2 === $match->leg ) {
                            $match->notify_team_withdrawal( $team_id );
                        }
                        $match->handle_result_update( array(), $match_status );
                    } else {
                        $match_confirmed = 'Y';
                        $home_team_score = 0;
                        $away_team_score = 0;
                        $status          = Util_Lookup::get_match_status_code( 'cancelled' );
                        $match->update_result( $home_team_score, $away_team_score, $match->custom, $match_confirmed, $status );
                        $match->update_league_with_result();
                    }
                }
            }
        }
        // update table.
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_league_teams SET `status` = 'W' WHERE `team_id` = %d AND `league_id` = %d and `season` = %s",
                $team_id,
                $this->id,
                $season
            )
        );
        if ( ! $this->is_championship ) {
            $this->update_standings( $season );
        }
        if ( ! $this->is_championship ) {
            $this->notify_league_team_withdrawn( $team_id, $season );
        }
    }

    /**
     * Function to notify all teams in league of withdrawal
     * @param int $team_id
     * @param string $season
     *
     * @return void
     */
    private function notify_league_team_withdrawn( int $team_id, string $season ): void {
        global $racketmanager;
        $team          = get_team( $team_id );
        $message_send  = false;
        $teams         = $this->get_league_teams( array( 'season' => $season ) );
        $headers       = array();
        $email_from    = $racketmanager->get_confirmation_email( $this->event->competition->type );
        $headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $this->event->competition->type ) . ' Secretary <' . $email_from . '>';
        $headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $this->event->competition->type ) . ' Secretary <' . $email_from . '>';
        $email_subject = $this->title . ' ' . $season . ' - ' . __( 'Withdrawn team', 'racketmanager' ) . ' - ' . $team->title;
        $email_to      = array();
        foreach ( $teams as $team ) {
            $team_dtls = $this->get_team_dtls( $team->id );
            if ( ! empty( $team_dtls->contactemail ) ) {
                $email_to[]   = ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
                $message_send = true;
            }
            if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
                $headers[]    = RACKETMANAGER_CC_EMAIL . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
                $message_send = true;
            }
        }
        $message_args            = array();
        $message_args['team']    = $team_id;
        $message_args['season']  = $season;
        $message_args['league']  = $this->id;
        $message_args['subject'] = $email_subject;
        $message_args['from']    = $email_from;

        if ( $message_send ) {
            $email_message = withdrawn_team_email( $message_args );
            wp_mail( $email_to, $email_subject, $email_message, $headers );
        }
    }
    /**
     * Set default dataset query arguments
     */
    private function set_match_query_args(): void {
        // set to latest match day by default.
        $this->set_match_query_arg( 'match_day', 'current' );

        // set number of matches per page.
        $this->set_match_query_arg( 'limit', $this->num_matches_per_page );
    }

    /**
     * Set match query argument
     *
     * @param string $key key.
     * @param mixed   $value value.
     * @param boolean $replace - used for arrays to add arguments or replace with values.
     */
    public function set_match_query_arg( string $key, mixed $value, bool $replace = true ): void {
        if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
            $value = $this->num_matches_per_page;
        }
        // sanitize query arg types.
        $v = $value;
        if ( 'numeric' === $this->match_query_args_types[ $key ] ) {
            $v = intval( $value );
        }
        if ( 'boolean' === $this->match_query_args_types[ $key ] ) {
            $v = intval( $value ) === 1;
        }
        if ( is_array( $this->match_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $v ) ) {
                $v = array( $v );
            }
            $this->match_query_args[ $key ] = array_merge( $this->match_query_args[ $key ], $v );
        } else {
            $this->match_query_args[ $key ] = $v;
        }
    }
    /**
     * Set team query argument
     *
     * @param string $key key.
     * @param mixed   $value value.
     * @param boolean $replace - used for arrays to add arguments or replace with values.
     */
    public function set_team_query_arg( string $key, mixed $value, bool $replace = true ): void {
        if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
            $value = $this->num_teams_per_page;
        }
        // sanitize query arg types.
        if ( 'numeric' === $this->team_query_args_types[ $key ] ) {
            $value = intval( $value );
        }
        if ( 'boolean' === $this->team_query_args_types[ $key ] ) {
            $value = intval( $value ) === 1;
        }

        if ( is_array( $this->team_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }
            $this->team_query_args[ $key ] = array_merge( $this->team_query_args[ $key ], $value );
        } else {
            $this->team_query_args[ $key ] = $value;
        }
    }

    /**
     * Set current season
     *
     * @param false|string $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_season( false|string $season = false, bool $force_overwrite = false ): void {
        global $wp;
        // Build a safe seasons array from Event (JSON-backed) and an index by name
        $seasons_list = $this->event->get_seasons();
        $by_name = array();
        if ( is_array( $seasons_list ) ) {
            foreach ( $seasons_list as $s ) {
                if ( is_array( $s ) && isset( $s['name'] ) ) {
                    $by_name[ $s['name'] ] = $s;
                }
            }
        } else {
            $seasons_list = array();
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( ! empty( $season ) && true === $force_overwrite ) {
            $data = $by_name[ $season ] ?? null;
        } elseif ( ! empty( $_POST['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['season'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $data = $by_name[ $key ] ?? false;
        } elseif ( ! empty( $_GET['season'] ) ) {
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            $data = $by_name[ $key ] ?? false;
        } elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) {
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            $data = $by_name[ $key ] ?? false;
        } elseif ( isset( $wp->query_vars['season'] ) ) {
            $key  = $wp->query_vars['season'];
            $data = $by_name[ $key ] ?? false;
        } elseif ( ! empty( $season ) ) {
            $data = $by_name[ $season ] ?? null;
        } else {
            $tmp  = $seasons_list;
            $data = ! empty( $tmp ) ? end( $tmp ) : null;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( empty( $data ) ) {
            $tmp  = $seasons_list;
            $data = ! empty( $tmp ) ? end( $tmp ) : null;
        }
        if ( ! $data ) {
            $data                   = array();
            $data['name']           = '';
            $data['num_match_days'] = 0;
        }
        $this->current_season = $data;
        $this->num_match_days = $data['num_match_days'];

        $this->set_team_query_arg( 'season', $this->current_season['name'] );
        $this->set_match_query_arg( 'season', $this->current_season['name'] );
    }

    /**
     * Get current season name
     *
     * @return string
     */
    public function get_season(): string {
        return stripslashes( $this->current_season['name'] );
    }
    /**
     * Set group
     *
     * @param string $group group.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_group( string $group = '', bool $force_overwrite = false ): void {
        if ( '' === $group || true !== $force_overwrite ) {
            if ( isset( $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $group = wp_strip_all_tags( wp_unslash( $_GET['group'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( is_admin() && isset( $_POST['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $group = wp_strip_all_tags( wp_unslash( $_POST['group'] ) );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
            } else {
                // set to first group in league by default.
                $groups = $this->get_groups();
                if ( isset( $groups[0] ) ) {
                    $group = $groups[0];
                }
            }
        }

        if ( is_array( $group ) ) {
            $group = $group[0];
        }
        $group = htmlspecialchars( wp_strip_all_tags( $group ) );
        if ( $this->group_exists( $group ) ) {
            $this->set_team_query_arg( 'group', $group );
            $this->set_match_query_arg( 'group', $group );
            $this->current_group = $group;
        }
    }

    /**
     * Get current group
     */
    public function get_group(): string {
        return $this->current_group;
    }

    /**
     * Get groups
     *
     * @return array|false
     */
    public function get_groups(): false|array {
        $this->groups = trim( $this->groups );
        $groups       = explode( ';', $this->groups );
        if ( ! is_array( $groups ) ) {
            return false;
        }
        return $groups;
    }

    /**
     * Retrieve match day
     *
     * @param false|string $_match_day match day.
     */
    public function set_match_day( false|string $_match_day = false ): void {
        global $wpdb, $wp;
        if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $match_day = intval( $_GET['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $_GET[ 'match_day_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $match_day = intval( $_GET[ 'match_day_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $match_day = intval( $_POST['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        } elseif ( isset( $wp->query_vars['match_day'] ) ) {
            $match_day = get_query_var( 'match_day' );
        } elseif ( is_numeric( $_match_day ) && 0 !== $_match_day ) {
            $match_day = intval( $_match_day );
        } elseif ( 'last' === $_match_day ) {
            $match_day = wp_cache_get( 'last_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $wpdb->racketmanager_matches WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) > 0 ORDER BY datediff LIMIT 1",
                        $this->id,
                        $this->current_season['name']
                    )
                ); // db call ok.
                if ( $match ) {
                    $match_day = $match->match_day;
                    wp_cache_set( 'last_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } elseif ( 'next' === $_match_day ) {
            $match_day = wp_cache_get( 'next_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `match_day`, DATEDIFF(NOW(), `date`) AS datediff FROM $wpdb->racketmanager_matches WHERE `league_id` = %d AND `season` = %s AND DATEDIFF(NOW(), `date`) < 0 ORDER BY datediff DESC LIMIT 1",
                        $this->id,
                        $this->current_season['name']
                    )
                ); // db call ok.
                if ( $match ) {
                    $match_day = $match->match_day;
                    wp_cache_set( 'next_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } elseif ( 'current' === $_match_day || 'latest' === $_match_day ) {
            $match_day = wp_cache_get( 'current_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `id`, `match_day`, ABS(DATEDIFF(NOW(), `date`)) AS datediff FROM $wpdb->racketmanager_matches WHERE `league_id` = %d AND `season` = %s ORDER BY datediff LIMIT 1",
                        $this->id,
                        $this->current_season['name']
                    )
                ); // db call ok.
                if ( $match ) {
                    $match_day = $match->match_day;
                    wp_cache_set( 'current_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } else {
            $match_day = 1;
        }
        if ( empty( $match_day ) || ! is_numeric( $match_day ) ) {
            $match_day = 1;
        }
        $this->match_day                     = intval( $match_day );
        $this->match_query_args['match_day'] = $match_day;
    }

    /**
     * Get pagination
     *
     * @param string $which type of pagination.
     *
     * @return string|null
     */
    public function get_page_links( string $which = 'matches' ): ?string {
        $this->get_current_page( $which );

        if ( 'matches' === $which ) {
            $base         = is_admin() ? 'match_paged' : 'match_paged_' . $this->id;
            $num_pages    = $this->num_pages_matches;
            $current_page = $this->current_page_matches;
            $num_items    = $this->num_matches;
        } elseif ( 'teams' === $which ) {
            $base         = is_admin() ? 'team_paged' : 'team_paged_' . $this->id;
            $num_pages    = $this->num_pages_teams;
            $current_page = $this->current_page_teams;
            $num_items    = $this->num_matches;
        } else {
            return '';
        }

        $query_args = $this->query_args;

        if ( 'matches' === $which && isset( $_POST['match_day'] ) && is_string( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $query_args['match_day'] = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['match_day'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        $page_links = paginate_links(
            array(
                'base'      => add_query_arg( $base, '%#%' ),
                'format'    => '',
                'prev_text' => '&#9668;',
                'next_text' => '&#9658;',
                'total'     => $num_pages,
                'current'   => $current_page,
                'add_args'  => $query_args,
            )
        );

        if ( $page_links && is_admin() ) {
            /* translators: %s: number of matches  */
            $page_links = sprintf( '<span class="displaying-num">' . __( '%s Matches', 'racketmanager' ) . '</span>%s', number_format_i18n( $num_items ), $page_links );
        }

        return $page_links;
    }

    /**
     * Set number of pages for matches
     *
     * @param string $which type of pagination.
     */
    public function set_num_pages( string $which = 'matches' ): void {
        if ( 'matches' === $which ) {
            $this->num_pages_matches = ( 0 === $this->num_matches_per_page ) ? 1 : ceil( $this->num_matches / $this->num_matches_per_page );
        }
        if ( 'teams' === $which ) {
            $this->num_pages_teams = ( 0 === $this->num_teams_per_page ) ? 1 : ceil( $this->num_teams / $this->num_teams_per_page );
        }
    }

    /**
     * Retrieve current page
     *
     * @param string $which type of pagination.
     * @return int
     */
    public function get_current_page( string $which = 'matches' ): int {
        global $wp;

        $this->set_num_pages( $which );

        if ( 'matches' === $which ) {
            $key = 'match_paged';
        } elseif ( 'teams' === $which ) {
            $key = 'team_paged';
        } else {
            $key = null;
        }
        if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = intval( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $wp->query_vars[ $key ] ) ) {
            $current_page = max( 1, intval( $wp->query_vars[ $key ] ) );
        } elseif ( isset( $_GET[ $key . '_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = intval( $_GET[ $key . '_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $wp->query_vars[ $key . '_' . $this->id ] ) ) {
            $current_page = max( 1, intval( $wp->query_vars[ $key . '_' . $this->id ] ) );
        } else {
            $current_page = 1;
        }

        if ( 'matches' === $which && $current_page > $this->num_pages_matches ) {
            $current_page = $this->num_pages_matches;
        }
        if ( 'teams' === $which && $current_page > $this->num_pages_teams ) {
            $current_page = $this->num_pages_teams;
        }
        // Prevent negative offsets.
        if ( 0 === intval( $current_page ) ) {
            $current_page = 1;
        }
        if ( 'matches' === $which ) {
            $this->current_page_matches = $current_page;
        }
        if ( 'teams' === $which ) {
            $this->current_page_teams = $current_page;
        }
        return $current_page;
    }

    /**
     * Get teams from league from database
     *
     * @param array $query_args query_arguments.
     *
     * @return array|int database results
     */
    public function get_league_teams( array $query_args = array() ): array|int {
        global $wpdb;
        $old_query_args = $this->team_query_args;

        // set query args.
        foreach ( $query_args as $key => $value ) {
            $this->set_team_query_arg( $key, $value );
        }
        $club             = $this->team_query_args['club'];
        $season           = $this->team_query_args['season'];
        $rank             = $this->team_query_args['rank'];
        $orderby          = $this->team_query_args['orderby'];
        $home             = $this->team_query_args['home'];
        $cache            = $this->team_query_args['cache'];
        $reset_query_args = $this->team_query_args['reset_query_args'];
        $get_details      = $this->team_query_args['get_details'];
        $status           = $this->team_query_args['status'];
        $team_name        = $this->team_query_args['team_name'];
        $team_id          = $this->team_query_args['team_id'];
        $count            = $this->team_query_args['count'];
        $active           = $this->team_query_args['active'];

        $args = array( $this->id );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT B.`id` AS `id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`group`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating`';
        }
        $sql .= " FROM $wpdb->racketmanager_teams B INNER JOIN $wpdb->racketmanager_league_teams A ON B.id = A.team_id WHERE `league_id` = %d";

        if ( '' === $season ) {
            $sql   .= ' AND A.`season` = %s';
            $args[] = $this->current_season['name'];
        } elseif ( 'any' === $season ) {
            $sql .= " AND A.`season` != ''";
        } elseif ( $this->season_exists( htmlspecialchars( $season ) ) ) {
            $sql   .= ' AND A.`season` = %s';
            $args[] = htmlspecialchars( $season );
        }

        if ( $rank ) {
            $sql   .= ' AND A.`rank` = %s';
            $args[] = $rank;
        }
        if ( $home ) {
            $sql .= ' AND B.`home` = 1';
        }
        if ( $status ) {
            if ( 'active' === $status ) {
                $sql .= ' AND A.`profile` != 3';
            } elseif ( 1 === $status ) {
                $sql   .= ' AND A.`profile` = %d';
                $args[] = $status;
            }
        }
        if ( $club ) {
            $sql   .= ' AND B.`club_id` = %d';
            $args[] = $club;
        }
        if ( $team_name ) {
            $sql   .= ' AND B.`title` = %s';
            $args[] = $team_name;
        }
        if ( $team_id ) {
            $sql   .= ' AND B.`id` = %d';
            $args[] = $team_id;
        }
        if ( $active ) {
            $sql .= " AND A.`status` != 'W'";
        }
        if ( ! $cache ) {
            $sql .= " AND 'nocache' = 'nocache'";
        }
        if ( $count ) {
            $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $args,
            );
            $teams = wp_cache_get( md5( $sql ), 'leaguetable' );
            if ( ! $teams || ! $cache ) {
                $teams = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $teams, 'leaguetable' );
            }
        } else {
            $sql .= Util::order_by_string( $orderby );
            $sql  = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $args
            );
            $teams = wp_cache_get( $sql, 'leaguetable' );
            if ( ! $teams || ! $cache ) {
                $teams = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( $sql, $teams, 'leaguetable' );
            }
            $class      = array();
            $team_index = array();
            foreach ( $teams as $i => $team ) {
                $team    = get_league_team( $team->table_id );
                $class[] = ( 'alternate' === $class ) ? '' : 'alternate';
                $team->custom  = stripslashes_deep( maybe_unserialize( $team->custom ) );
                $team->roster  = maybe_unserialize( $team->roster );
                $team->title   = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
                $team->stadium = stripslashes( $team->stadium );
                $team->class   = implode( ' ', $class );
                $team->points_formatted = array(
                    'primary'   => sprintf( $this->point_format, $team->points_plus, $team->points_minus ),
                    'secondary' => sprintf( $this->point_2_format, $team->points_2_plus, $team->points_2_minus ),
                );
                if ( ! empty( $team->players ) ) {
                    $type        = substr( $this->event->type, 1, 1 );
                    $team_rating = 0;
                    foreach ( $team->players as $player ) {
                        $rating = $player->wtn[ $type ];
                        if ( is_numeric( $rating ) ) {
                            $team_rating += $rating;
                        }
                    }
                    $team->profile = $team_rating;
                }
                if ( $get_details ) {
                    $team_dtls           = $this->get_team_dtls( $team->id );
                    $team->match_day     = $team_dtls->match_day;
                    $team->match_time    = $team_dtls->match_time;
                    $team->captain_id    = $team_dtls->captain_id;
                    $team->captain       = $team_dtls->captain;
                    $team->contactno     = $team_dtls->contactno;
                    $team->contactemail  = $team_dtls->contactemail;
                    $team->league_status = $team_dtls->league_status;
                }

                $team_index[ $team->id ] = $i;
                $teams[ $i ]             = $team;
            }

            $this->teams      = $teams;
            $this->team_index = $team_index;

            $this->set_num_teams();
        }

        // reset team query args.
        if ( true === $reset_query_args ) {
            foreach ( $old_query_args as $key => $query_arg ) {
                $this->set_team_query_arg( $key, $query_arg );
            }

            $this->set_team_query_arg( 'reset_query_args', false );
        }

        return $teams;
    }

    /**
     * Add team to league
     *
     * @param int|string $team_id team identifier.
     * @param string $season season.
     * @param string|null $rank rank.
     * @param string|null $status status.
     * @param int|string $profile profile.
     *
     * @return int|boolean $table_id
     */
    public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int {
        global $wpdb, $racketmanager;
        $valid = true;
        if ( ! is_numeric( $team_id ) ) {
            $team = get_team( $team_id );
            if ( ! $team ) {
                $team        = new stdClass();
                $team->title = $team_id;
                $team->type  = $this->type;
                if ( $this->event->competition->is_tournament ) {
                    $team->team_type = 'S';
                }
                $team = new Team( $team );
            }
            $team_id = $team->id;
        }
        $table_id = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT `id` FROM $wpdb->racketmanager_league_teams WHERE `team_id` = %d AND `season` = %s AND `league_id` = %d",
                $team_id,
                $season,
                $this->id
            )
        );
        if ( $table_id ) {
            $message_text = __( 'Team already in table', 'racketmanager' );
            $valid        = false;
        } else {
            if ( ! $rank ) {
                $result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                        "INSERT INTO $wpdb->racketmanager_league_teams (`team_id`, `season`, `league_id`, `profile`) VALUES (%d, %s, %d, %d)",
                        $team_id,
                        $season,
                        $this->id,
                        $profile
                    )
                );
            } else {
                $result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "INSERT INTO $wpdb->racketmanager_league_teams (`team_id`, `season`, `league_id`, `rank`, `status`, `profile`) VALUES (%d, %s, %d, %d, %s, %d)",
                        $team_id,
                        $season,
                        $this->id,
                        $rank,
                        $status,
                        $profile
                    )
                );
            }
            if ( $result ) {
                $table_id     = $wpdb->insert_id;
                $message_text = __( 'Table entry added', 'racketmanager' );
            } else {
                $message_text = __( 'Error adding team to table', 'racketmanager' );
                $valid        = false;
                error_log( $message_text ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( $wpdb->last_error ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        if ( $valid ) {
            $error = false;
        } else {
            $error = true;
        }
        $racketmanager->set_message( $message_text, $error );

        if ( $valid ) {
            return $table_id;
        } else {
            return false;
        }
    }

    /**
     * Get single team from League cache
     *
     * @param int $team_id team id.
     *
     * @return object|false
     */
    public function get_league_team( int $team_id ): object|false {
        if ( isset( $this->team_index[ $team_id ] ) ) {
            return $this->teams[ $this->team_index[ $team_id ] ];
        } else {
            return $this->get_team_dtls( $team_id );
        }
    }

    /**
     * Get single team
     *
     * @param int $team_id team id.
     * @param string|null $season season name (optional).
     *
     * @return object|false
     */
    public function get_team_dtls( int $team_id, ?string $season = null ): object|false {
        global $wpdb;
        if ( empty( $season ) ) {
            $season = $this->current_season['name'];
        }
        if ( -1 === $team_id ) {
            $team               = (object) array(
                'id'     => -1,
                'title'  => 'Bye',
                'player' => array(),
            );
            $team->captain      = '';
            $team->contactno    = '';
            $team->contactemail = '';
            $team->club_id      = '';
            $team->stadium      = '';
            $team->roster       = '';
            return $team;
        }

        $sql = $wpdb->prepare(
            "SELECT A.`title`, C.`captain`, A.`club_id`, C.`match_day`, C.`match_time`, A.`stadium`, A.`home`, A.`roster`, A.`profile`, A.`id`, A.`status`, A.`type`, A.`team_type`, C.`status` as `league_status`, C.`rating`, C.`rank`, C.`points_plus`, C.`points_minus`, C.`points_2_plus`, C.`points_2_minus`, C.`add_points`, C.`done_matches`, C.`won_matches`, C.`draw_matches`, C.`lost_matches`, C.`diff` FROM $wpdb->racketmanager_league_teams C INNER JOIN  $wpdb->racketmanager_teams A ON A.`id` = C.`team_id` AND C.`league_id` = %d WHERE A.`id` = %d AND C.`season` = %s",
            $this->id,
            $team_id,
            $season
        );

        $team = wp_cache_get( md5( $sql ), 'teamdetails' );
        if ( ! $team ) {
            $team = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $team, 'teamdetails' );
        }

        if ( ! isset( $team ) ) {
            return false;
        }
        if ( str_contains( $team->title, '_' ) ) {
            $team_name = Util::generate_team_name( $team->title );
            if ( ! empty( $team_name ) ) {
                $team->title = $team_name;
            }
        } else {
            $team->title = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
        }
        $captain = get_userdata( $team->captain );
        if ( $captain ) {
            $team->captain_id   = $team->captain;
            $team->captain      = $captain->display_name;
            $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
            $team->contactemail = $captain->user_email;
        } else {
            $team->captain_id   = 0;
            $team->captain      = '';
            $team->contactno    = '';
            $team->contactemail = '';
        }
        if ( ! empty( $team->club_id ) ) {
            $team->club_id = stripslashes( $team->club_id );
            $team->club    = get_club( $team->club_id );
        } else {
            $team->club_id = null;
            $team->club    = null;
        }
        $team->stadium          = stripslashes( $team->stadium );
        $team->roster           = maybe_unserialize( $team->roster );
        $team->points_formatted = array(
            'primary'   => sprintf( $this->point_format, $team->points_plus, $team->points_minus ),
            'secondary' => sprintf( $this->point_2_format, $team->points_2_plus, $team->points_2_minus ),
        );
        if ( 'P' === $team->team_type && null !== $team->roster ) {
            $team->players = array();
            $i             = 1;
            foreach ( $team->roster as $player ) {
                $team_player           = get_player( $player );
                $team->players [ $i ]  = $team_player;
                $team->player[ $i ]    = $team_player->get_fullname() ?? '';
                $team->player_id[ $i ] = $player;
                ++$i;
            }
        }
        $team->is_withdrawn = false;
        if ( 'W' === $team->league_status ) {
            $team->is_withdrawn = true;
        }
        return $team;
    }

    /**
     * Gets matches from database
     *
     * @param array $query_args query arguments.
     *
     * @return array|int|object of matches
     */
    public function get_matches( array $query_args ): array|int|object {
        global $wpdb;
        $old_query_args = $this->match_query_args;
        // set query args.
        foreach ( $query_args as $key => $value ) {
            $this->set_match_query_arg( $key, $value );
        }
        $limit            = $this->match_query_args['limit'];
        $season           = $this->match_query_args['season'];
        $final            = $this->match_query_args['final'];
        $match_day        = $this->match_query_args['match_day'];
        $time             = $this->match_query_args['time'];
        $match_date       = $this->match_query_args['match_date'];
        $count            = $this->match_query_args['count'];
        $orderby          = $this->match_query_args['orderby'];
        $standingstable   = $this->match_query_args['standingstable'];
        $cache            = $this->match_query_args['cache'];
        $team_id          = $this->match_query_args['team_id'];
        $home_team        = $this->match_query_args['home_team'];
        $away_team        = $this->match_query_args['away_team'];
        $team_pair        = $this->match_query_args['team_pair'];
        $winner_id        = $this->match_query_args['winner_id'];
        $loser_id         = $this->match_query_args['loser_id'];
        $home_points      = $this->match_query_args['home_points'];
        $away_points      = $this->match_query_args['away_points'];
        $reset_limit      = $this->match_query_args['reset_limit'];
        $reset_query_args = $this->match_query_args['reset_query_args'];
        $confirmed        = $this->match_query_args['confirmed'];
        $leg              = $this->match_query_args['leg'];
        $player           = $this->match_query_args['player'];
        $withdrawn        = $this->match_query_args['withdrawn'];
        $club             = $this->match_query_args['club'];
        $pending          = $this->match_query_args['pending'];
        $status           = $this->match_query_args['status'];

        $matches = array();
        $args    = array( $this->id );
        if ( $count ) {
            $sql_start = "SELECT COUNT(*) FROM $wpdb->racketmanager_matches m";
        } else {
            $sql_start = "SELECT  DISTINCT m.`id`, m.`date` FROM $wpdb->racketmanager_matches m";
        }
        $sql = ' WHERE m.`league_id` = %d';

        // disable limit for championship mode.
        if ( $this->is_championship ) {
            $limit = false;
        }
        if ( empty( $season ) ) {
            $sql   .= ' AND m.`season` = %s';
            $args[] = $this->current_season['name'];
        } elseif ( 'any' === $season ) {
            $sql .= " AND m.`season` != ''";
        } elseif ( $this->season_exists( $season ) ) {
            $sql   .= ' AND m.`season` = %s';
            $args[] = htmlspecialchars( $season );
        } else {
            return $matches;
        }
        if ( $final ) {
            if ( 'all' !== $final ) {
                $sql      .= ' AND m.`final` = %s';
                $args[]    = htmlspecialchars( wp_strip_all_tags( $final ) );
                $match_day = -1;
                $limit     = 0;
            }
        } elseif ( $this->is_championship ) {
            $sql .= " AND m.`final` IS NOT NULL";
        } else {
            $sql .= " AND ( m.`final` = '' OR m.`final` IS NULL )";
        }

        if ( $team_id ) {
            $sql   .= ' AND (`home_team` = %d OR `away_team` = %d)';
            $args[] = $team_id;
            $args[] = $team_id;
        } elseif ( count( $team_pair ) === 2 ) {
            $sql   .= " AND ( (`home_team` = %d AND `away_team` = %d' OR (`home_team` = %d AND `away_team` = %d ) )";
            $args[] = intval( $team_pair[0] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[1] );
            $args[] = intval( $team_pair[0] );
        } else {
            if ( ! empty( $home_team ) ) {
                $sql   .= ' AND `home_team` = %d';
                $args[] = $home_team;
            }
            if ( ! empty( $away_team ) ) {
                $sql   .= ' AND `away_team` = %d';
                $args[] = $away_team;
            }
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            if ( $standingstable ) {
                $sql .= ' AND `match_day` <=%d';
            } else {
                $sql .= ' AND `match_day` = %d';
            }
            $args[] = $match_day;
        }

        // get only finished matches with score for time 'latest'.
        if ( 'latest' === $time ) {
            $home_points = false;
            $away_points = false;
            $sql        .= " AND (m.`home_points` != '' OR m.`away_points` != '')";
        }

        if ( '' !== $home_points ) {
            if ( 'null' === $home_points ) {
                $sql .= ' AND m.`home_points` IS NULL';
            } elseif ( 'not_null' === $home_points ) {
                $sql .= ' AND m.`home_points` IS NOT NULL';
            } elseif ( 'not_empty' === $home_points ) {
                $sql .= " AND m.`home_points` != ''";
            }
        }
        if ( $away_points ) {
            if ( 'null' === $away_points ) {
                $sql .= ' AND m.`away_points` IS NULL';
            } elseif ( 'not_null' === $away_points ) {
                $sql .= ' AND m.`away_points` IS NOT NULL';
            } elseif ( 'not_empty' === $away_points ) {
                $sql .= " AND m.`away_points` != ''";
            }
        }

        if ( $winner_id ) {
            $sql   .= ' AND m.`winner_id` = %d';
            $args[] = $winner_id;
        }
        if ( $loser_id ) {
            $sql   .= ' AND m.`loser_id` = %d';
            $args[] = $loser_id;
        }

        if ( 'next' === $time ) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0';
        } elseif ( 'prev' === $time || 'latest' === $time ) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0';
        } elseif ( 'prev1' === $time ) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0) AND (m.`winner_id` != 0) ';
        } elseif ( 'today' === $time ) {
            $sql .= ' AND DATEDIFF(NOW(), m.`date`) = 0';
        } elseif ( 'day' === $time ) {
            $sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', m.`date`) = 0";
        }
        if ( $confirmed ) {
            $sql .= " AND m.`confirmed` = 'Y'";
        }
        if ( $leg ) {
            $sql   .= ' AND m.`leg` = %s';
            $args[] = $leg;
        }
        if ( $player ) {
            $sql_start .= " ,$wpdb->racketmanager_rubbers r, $wpdb->racketmanager_rubber_players rp";
            $sql       .= " AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
        }
        // Force ordering by date ascending if next matches are queried.
        if ( 'next' === $time ) {
            $orderby['date'] = 'ASC';
        }
        // Force ordering by date descending if previous/latest matches are queried.
        if ( 'prev' === $time || 'latest' === $time ) {
            $orderby['date'] = 'DESC';
        }
        if ( ! $withdrawn ) {
            $sql_start .= " ,$wpdb->racketmanager_league_teams t1, $wpdb->racketmanager_league_teams t2";
            $sql       .= " AND `home_team` = t1.`team_id` AND t1.`league_id` = m.`league_id` and t1.`season` = m.`season` AND t1.`status` != 'W'";
            $sql       .= " AND `away_team` = t2.`team_id` AND t2.`league_id` = m.`league_id` and t2.`season` = m.`season` AND t2.`status` != 'W'";
        }
        if ( $club ) {
            $sql .= " AND (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . ") OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = " . $club . '))';
        }
        if ( $pending ) {
            $sql .= ' AND m.winner_id = 0';
        }
        if ( $status ) {
            $status_code = $status['status_code'] ?? null;
            $compare     = $status['compare'] ?? null;
            if ( $status_code ) {
                $status_value = Util_Lookup::get_match_status_code( $status_code );
                if ( $status_value ) {
                    if ( 'not' === $compare ) {
                        $sql   .= ' AND m.`status` != %d';
                        $args[] = $status_value;
                    } elseif ( '=' === $compare ) {
                        $sql   .= ' AND m.`status` = %d';
                        $args[] = $status_value;
                    }
                }
            }
        }
        // get number of matches.
        if ( $count ) {
            $this->set_match_query_arg( 'count', false );
            $sql = $sql_start . $sql;
            $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $args
            );
            // Use WordPress cache for counting matches.
            $matches = wp_cache_get( md5( $sql ), 'num_matches' );
            if ( ! $matches || false === $cache ) {
                $matches = intval(
                    $wpdb->get_var(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                        $sql
                    )
                ); // db call ok.
                wp_cache_set( md5( $sql ), $matches, 'num_matches' );
            }
        } else {
            $sql   .= Util::order_by_string( $orderby );
            $offset = intval( $limit > 0 ) ? ( $this->get_current_page() - 1 ) * $limit : 0;
            if ( intval( $limit > 0 ) ) {
                $sql .= ' LIMIT ' . intval( $offset ) . ',' . intval( $limit );
            }
            $sql = $sql_start . $sql;
            $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $args
            );
            $matches = wp_cache_get( md5( $sql ), 'matches' );
            if ( ! $matches || false === $cache ) {
                $matches = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $matches, 'matches' );
            }
            $class = '';
            foreach ( $matches as $i => $match ) {
                $match        = get_match( $match->id );
                $class        = ( 'alternate' === $class ) ? '' : 'alternate';
                $match->class = $class;
                if ( $player ) {
                    $match->rubbers = $match->get_rubbers( $player );
                }

                $matches[ $i ] = $match;
            }
            if ( 1 === $limit && $matches ) {
                $matches = $matches[0];
            }
        }

        // reset match limit.
        if ( true === $reset_limit ) {
            $this->set_match_query_arg( 'limit', $old_query_args['limit'] );
            $this->set_match_query_arg( 'reset_limit', false );
        }

        // reset match query args.
        if ( true === $reset_query_args ) {
            foreach ( $old_query_args as $key => $query_arg ) {
                $this->set_match_query_arg( $key, $query_arg );
            }
            $this->set_match_query_arg( 'reset_query_args', false );
        }

        if ( true !== $count ) {
            $this->matches = $matches;
        } else {
            $this->num_matches = $matches;
        }

        return $matches;
    }

    /**
     * Get standings table
     *
     * @param array $teams teams.
     * @param int $match_day match day.
     * @param string $mode mode.
     *
     * @return array the ranked teams
     */
    public function get_standings( array $teams, int $match_day, string $mode = 'all' ): array {
        // hide status as it's meaningless.
        $this->standings['status'] = 0;

        // set basic match query args.
        $this->set_match_query_arg( 'standingstable', true );
        $this->set_match_query_arg( 'match_day', $match_day );
        $this->set_match_query_arg( 'final', '' );
        $this->set_match_query_arg( 'limit', false );

        $this->set_match_query_arg( 'mode', $mode );

        foreach ( $teams as $i => $team ) {
            $team       = get_league_team( $team );
            $match_args = array();

            // get only home matches.
            if ( 'home' === $mode ) {
                $match_args['home_team'] = $team->id;
            }
            // get only away matches.
            if ( 'away' === $mode ) {
                $match_args['away_team'] = $team->id;
            }
            // get all matches for given team.
            if ( 'all' === $mode ) {
                $match_args['team_id'] = $team->id;
            }
            // get matches up to given match day.
            if ( $match_day ) {
                $match_args['match_day'] = $match_day;
            }
            $match_args['confirmed'] = true;
            $match_args['status']    = array(
                'status_code' => 'Cancelled',
                'compare'     => 'not',
            );

            // initialize team standings data.
            $team->done_matches  = 0;
            $team->won_matches   = 0;
            $team->draw_matches  = 0;
            $team->lost_matches  = 0;
            $team->points_plus   = 0;
            $team->points_minus  = 0;
            $team->points_2_plus  = 0;
            $team->points_2_minus = 0;

            // get matches.
            $matches = $this->get_matches( $match_args );
            foreach ( $matches as $match ) {
                if ( '' !== $match->home_points && '' !== $match->away_points ) {
                    $team->done_matches += 1;
                }
                if ( $match->winner_id === $team->id ) {
                    $team->won_matches += 1;
                }
                if ( $match->loser_id === $team->id ) {
                    $team->lost_matches += 1;
                }
                if ( -1 === $match->winner_id && -1 === $match->loser_id ) {
                    $team->draw_matches += 1;
                }
            }
            $team->points         = $this->calculate_points( $team, $matches );
            $team->points_plus    = $team->points['plus'];
            $team->points_minus   = $team->points['minus'];
            $team->points_2_plus   = 0;
            $team->points_2_minus  = 0;
            $team->diff           = $team->points_2_plus - $team->points_2_minus;
            $team->custom['diff'] = $team->diff;
            $team->win_percent();
            $custom = $this->get_standings_data( $team->id, $team->custom, $matches );
            foreach ( $custom as $key => $value ) {
                $team->{$key} = $value;
            }
            $teams[ $i ] = $team;
        }

        /*
        * rank teams.
        */
        $teams = $this->rank_teams( $teams );
        return $this->get_ranking( $teams );
    }
    /**
     * Set finals flag for championship mode
     *
     * @param boolean $is_final final indicator.
     */
    public function set_finals( bool $is_final = true ): void {
        $this->is_final = $is_final;
    }

    /**
     * Get point rule depending on selection.
     *
     * @param string|false $rule rule.
     *
     * @return array of points
     */
    public function get_point_rule( false|string $rule = false ): array {
        if ( ! $rule ) {
            $rule = $this->point_rule;
        }
        $rule = maybe_unserialize( $rule );

        // Manual point rule.
        if ( is_array( $rule ) ) {
            return $rule;
        } else {
            $point_rules = array();
            // One point rule.
            $point_rules['one'] = array(
                'forwin'  => 1,
                'fordraw' => 0,
                'forloss' => 0,
            );
            // Two-point rule.
            $point_rules['two'] = array(
                'forwin'  => 2,
                'fordraw' => 1,
                'forloss' => 0,
            );
            // Three-point rule.
            $point_rules['three'] = array(
                'forwin'  => 3,
                'fordraw' => 1,
                'forloss' => 0,
            );
            $point_rules['tennis']                = array(
                'forwin'             => 1,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 0.5,
                'rubber_win'         => 0,
                'rubber_draw'        => 0,
                'shared_match'       => 0.5,
                'match_result'       => null,
                'forwalkover_rubber' => 1,
                'forwalkover_match'  => 1,
                'result_late'        => 1,
                'confirmation_late'  => 1,
            );
            $point_rules['tennisNoPenalty']       = array(
                'forwin'             => 1,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 0.5,
                'rubber_win'         => 0,
                'rubber_draw'        => 0,
                'shared_match'       => 0.5,
                'match_result'       => null,
                'forwalkover_rubber' => 0,
                'forwalkover_match'  => 0,
            );
            $point_rules['tennisRubber']          = array(
                'forwin'             => 0,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 0.5,
                'rubber_win'         => 2,
                'rubber_draw'        => 1,
                'shared_match'       => 0.5,
                'match_result'       => 'rubber_count',
                'forwalkover_rubber' => 2,
                'forwalkover_match'  => 0,
            );
            $point_rules['tennisSummer']          = array(
                'forwin'             => 1,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 0.5,
                'matches_win'        => 3,
                'matches_draw'       => 1.5,
                'forwalkover_rubber' => 1,
                'forwalkover_match'  => 1,
                'result_late'        => 1,
                'confirmation_late'  => 1,
            );
            $point_rules['tennisSummerNoPenalty'] = array(
                'forwin'             => 1,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 0.5,
                'matches_win'        => 3,
                'matches_draw'       => 1.5,
                'forwalkover_rubber' => 0,
                'forwalkover_match'  => 0,
            );
            $point_rules['score']                 = array(
                'forwin'             => 0,
                'fordraw'            => 0,
                'forloss'            => 0,
                'forwin_split'       => 0,
                'forloss_split'      => 0,
                'forshare'           => 1,
                'matches_win'        => 0,
                'matches_draw'       => 0,
                'match_result'       => 'games',
                'forwalkover_rubber' => 1,
                'forwalkover_match'  => 1,
            );

            return $point_rules[ $rule ];
        }
    }

    /**
     * Get number of teams for specific league
     *
     * @param boolean $total total teams or teams per page.
     */
    public function set_num_teams( bool $total = false ): void {
        if ( true === $total ) {
            $this->num_teams_total = $this->get_num_teams();
        } else {
            $this->num_teams        = $this->num_teams_total;
            $this->pagination_teams = $this->get_page_links( 'teams' );
        }
    }
    /**
     * Get number of teams for specific league
     *
     * @param string|null $status status.
     * @param boolean $latest latest indicator.
     *
     * @return int
     */
    public function get_num_teams( ?string $status = null, bool $latest = false ): int {
        $args['count']            = true;
        $args['season']           = $this->current_season['name'];
        $args['reset_query_args'] = true;
        $args['club']             = false;
        if ( $status ) {
            $args['active'] = true;
        }
        if ( $latest ) {
            $args['cache'] = false;
        }
        return $this->get_league_teams( $args );
    }
    /**
     * Gets number of matches
     *
     * @param boolean $total total matches or matches per page.
     */
    public function set_num_matches( bool $total = false ): void {
        $match_args                     = array();
        $match_args['count']            = true;
        $match_args['season']           = null;
        $match_args['reset_query_args'] = true;
        if ( true !== $total ) {
            $match_args['limit'] = 0;
        }
        $num_matches = $this->get_matches( $match_args );
        if ( $total ) {
            $this->num_matches_total = $num_matches;
        } else {
            $this->pagination_matches = $this->get_page_links();
        }
    }

    /**
     * Get specific field for crosstable
     *
     * @param int $team_id team.
     * @param int $opponent_id opponent.
     *
     * @return string
     */
    public function get_crosstable_field( int $team_id, int $opponent_id ): string {
        if ( $team_id === $opponent_id ) {
            $score = '&nbsp;';
        } else {
            $matches   = $this->get_matches(
                array(
                    'home_team'        => $team_id,
                    'away_team'        => $opponent_id,
                    'match_day'        => -1,
                    'limit'            => false,
                    'reset_query_args' => true,
                )
            );
            $home_away = empty( $this->event->current_season['home_away'] ) ? false : $this->event->current_season['home_away'];
            if ( $home_away ) {
                if ( $matches ) {
                    $score = '';
                    foreach ( $matches as $match ) {
                        $score .= show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) ) . '<br>';
                    }
                } else {
                    $score = '&nbsp;';
                }
            } elseif ( $matches ) {
                $match = $matches[0];
                $score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
            } else {
                $matches = $this->get_matches(
                    array(
                        'home_team'        => $opponent_id,
                        'away_team'        => $team_id,
                        'match_day'        => -1,
                        'limit'            => false,
                        'reset_query_args' => true,
                    )
                );
                if ( $matches ) {
                    $match = $matches[0];
                    $score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
                } else {
                    $score = '&nbsp;';
                }
            }
        }

        return $score;
    }
    /**
     * Default ranking function. Re-defined in sports-specific class
     * 1) Primary points DESC
     * 2) Games Allowed ASC
     * 3) Done Matches ASC
     *
     * @param array $teams team.
     *
     * @return array
     */
    protected function rank_teams( array $teams ): array {
        foreach ( $teams as $key => $team ) {
            $team_sets_won     = $team->sets_won ?? 0;
            $team_sets_allowed = $team->sets_allowed ?? 0;
            if ( ! is_numeric( $team_sets_won ) ) {
                $team_sets_won = 0;
            }
            if ( ! is_numeric( $team_sets_allowed ) ) {
                $team_sets_allowed = 0;
            }
            $team_games_won     = $team->games_won ?? 0;
            $team_games_allowed = $team->games_allowed ?? 0;
            if ( ! is_numeric( $team_games_won ) ) {
                $team_games_won = 0;
            }
            if ( ! is_numeric( $team_games_allowed ) ) {
                $team_games_allowed = 0;
            }
            $points[ $key ]        = $team->points['plus'];
            $sets_diff[ $key ]     = $team_sets_won - $team_sets_allowed;
            $sets_won[ $key ]      = $team_sets_won;
            $sets_allowed[ $key ]  = $team_sets_allowed;
            $games_diff[ $key ]    = $team_games_won - $team_games_allowed;
            $games_won[ $key ]     = $team_games_won;
            $games_allowed[ $key ] = $team_games_allowed;
            if ( 'W' === $team->status ) {
                $status[ $key ] = $team->status;
            } else {
                $status[ $key ] = null;
            }
            $title[ $key ] = $team->title;
        }
        array_multisort( $status, SORT_ASC, $points, SORT_DESC, $sets_diff, SORT_DESC, $games_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $title, SORT_ASC, $teams );

        return $teams;
    }
    /**
     * Set tab in league/archive shortcodes
     *
     * @param boolean $is_archive is this an archive.
     */
    public function set_tab( bool $is_archive = false ): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET[ 'match_day_' . $this->id ] ) || isset( $_GET[ 'team_id_' . $this->id ] ) || isset( $_GET[ 'match_paged_' . $this->id ] ) ) {
            $this->archive_tab = 2;
        }
        if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
            $this->archive_tab = 3;
        }
        if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
            $this->archive_tab = 2;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $this->is_archive = $is_archive;
    }

    /**
     * Set template in league/archive shortcodes
     *
     * @param string $key key.
     * @param string $template template.
     */
    public function set_template( string $key, string $template ): void {
        $this->templates[ $key ] = $template;
    }

    /**
     * Set all templates in league/archive shortcodes
     *
     * @param array $templates An associative array of template key => template associations.
     */
    public function set_templates( array $templates ): void {
        foreach ( $templates as $key => $template ) {
            $this->set_template( $key, $template );
        }
    }

    /**
     * Check if season exists
     *
     * @param string $season season.
     *
     * @return boolean
     */
    private function season_exists( string $season ): bool {
        $seasons_list = method_exists( $this->event, 'get_seasons' )
            ? $this->event->get_seasons()
            : ( is_array( $this->event->seasons ) ? $this->event->seasons : array() );
        if ( ! is_array( $seasons_list ) ) {
            return false;
        }
        // Check associative by key first
        if ( isset( $seasons_list[ $season ] ) ) {
            return true;
        }
        // Then scan list entries by ['name']
        foreach ( $seasons_list as $s ) {
            if ( is_array( $s ) && isset( $s['name'] ) && (string) $s['name'] === (string) $season ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if group exists
     *
     * @param string $group group.
     *
     * @return boolean
     */
    private function group_exists( string $group ): bool {
        if ( isset( $this->groups ) ) {
            $groups = explode( ';', $this->groups );
            if ( in_array( $group, $groups, true ) ) {
                return true;
            }
        }
        return false;
    }
    /**
     * Default ranking function. Re-defined in sports-specific class
     * 1) Primary points DESC
     * 2) Done Matches ASC
     *
     * @param false|string $season season.
     */
    public function set_teams_rank( false|string $season = false ): void {
        if ( ! isset( $season ) ) {
            $season = $this->current_season;
        }
        $season = is_array( $season ) ? $season['name'] : $season;

        // rank Teams in groups.
        $groups = ! empty( $this->groups ) ? explode( ';', $this->groups ) : array( '0' );
        foreach ( $groups as $group ) {
            $team_args = array( 'season' => $season );
            if ( ! empty( $group ) ) {
                $team_args['group'] = $group;
            }
            $teams = $this->get_league_teams( $team_args );

            if ( ! empty( $teams ) && 'auto' === $this->event->competition->team_ranking ) {
                $teams = $this->rank_teams( $teams );
                $teams = $this->get_ranking( $teams );
                $this->update_ranking( $teams );
            }
        }
    }

    /**
     * Get team rank function
     *
     * @param int $team_id team id.
     * @param string $season season.
     *
     * @return int|null team ranking.
     */
    public function get_rank( int $team_id, string $season ): ?int {
        global $wpdb;
        $sql       = $wpdb->prepare(
            "SELECT `rank` FROM $wpdb->racketmanager_league_teams WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $this->id,
            $season,
            $team_id,
        );
        $team_rank = wp_cache_get( md5( $sql ), 'team_rank' );
        if ( ! $team_rank ) {
            $team_rank = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
            );
            wp_cache_set( md5( $sql ), $team_rank, 'team_rank' );
        }
        if ( $team_rank ) {
            return $team_rank->rank;
        } else {
            return null;
        }
    }

    /**
     * Get team league table function
     *
     * @param int $team_id team id.
     * @param string $season season.
     *
     * @return string|null team status.
     */
    public function get_status( int $team_id, string $season ): ?string {
        global $wpdb;
        $sql         = $wpdb->prepare(
            "SELECT `status` FROM $wpdb->racketmanager_league_teams WHERE `league_id` = %d AND `season` = %s AND `team_id` = %d",
            $this->id,
            $season,
            $team_id,
        );
        $team_status = wp_cache_get( md5( $sql ), 'team_status' );
        if ( ! $team_status ) {
            $team_status = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
            );
            wp_cache_set( md5( $sql ), $team_status, 'team_status' );
        }
        if ( $team_status ) {
            return $team_status->status;
        } else {
            return null;
        }
    }
    /**
     * Gets ranking of teams
     *
     * @param array $teams teams.
     *
     * @return array of parameters
     */
    public function get_ranking( array $teams ): array {
        $rank      = 1;
        $incr      = 1;
        $new_teams = array();
        foreach ( $teams as $key => $team ) {
            $team->old_rank = $team->rank;

            if ( $key > 0 ) {
                if ( ! $this->is_championship && ( isset( $team->points ) && $this->is_tie( $team, $teams[ $key - 1 ] ) ) ) {
                    ++$incr;
                } else {
                    $rank += $incr;
                    $incr  = 1;
                }
            }

            $team->rank = $rank;
            if ( 'W' !== $team->status ) {
                $team->status = $this->get_team_status( $team, $rank );
            }

            $new_teams[ $key ] = $team;
        }

        return $this->tiebreak( $new_teams );
    }

    /**
     * Get team status depending on previous rank
     *
     * @param object $team team.
     * @param int $rank rank.
     *
     * @return string
     */
    private function get_team_status( object $team, int $rank ): string {
        if ( 0 !== $team->old_rank && $team->done_matches > 1 ) {
            if ( intval( $team->old_rank ) === $rank ) {
                $status = '=';
            } elseif ( $rank < $team->old_rank ) {
                $status = '+';
            } else {
                $status = '-';
            }
        } else {
            $status = '';
        }
        return $status;
    }

    /**
     * Update Team Rank and status
     *
     * @param array $teams teams to be ranked.
     */
    public function update_ranking( array $teams ): void {
        global $wpdb;
        foreach ( $teams as $team ) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_league_teams SET `rank` = %d, `status` = %s WHERE `id` = %d",
                    $team->rank,
                    $team->status,
                    $team->table_id
                )
            ); // db call ok.
            wp_cache_delete( $team->table_id, 'league-teams' );
            wp_cache_delete( $team->league_id, 'leaguetable' );
        }
    }
    /**
     * =======================
     * Administration section
     * =======================
     */

    /**
     * Update standings manually
     *
     * @param array $teams teams.
     * @param array $points points.
     * @param array $matches matches.
     * @param array $custom custom.
     */
    public function save_standings_manually( array $teams, array $points, array $matches, array $custom ): void {
        global $wpdb;

        $season = $this->current_season['name'];

        foreach ( array_keys( $teams ) as $id ) {
            $points_2_plus  = 0;
            $points_2_minus = 0;
            $diff           = 0;

            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_league_teams SET `points_plus` = %d, `points_minus` = %d, `points_2_plus` = %d, `points_2_minus` = %d, `done_matches` = %d, `won_matches` = %d, `draw_matches` = %d, `lost_matches` = %d, `diff` = %d, `add_points` = %d WHERE `team_id` = %d and `league_id` = %d AND `season` = %s",
                    $points['points_plus'][ $id ],
                    $points['points_minus'][ $id ],
                    $points_2_plus,
                    $points_2_minus,
                    $matches['num_done_matches'][ $id ],
                    $matches['num_won_matches'][ $id ],
                    $matches['num_draw_matches'][ $id ],
                    $matches['num_lost_matches'][ $id ],
                    $diff,
                    $points['add_points'][ $id ],
                    $id,
                    $this->id,
                    $season
                )
            );
            wp_cache_flush();
        }

        // Update Teams Rank and Status if not set to manual ranking.
        if ( 'manual' !== $this->event->competition->team_ranking ) {
            $this->set_teams_rank( $season );
        }
    }

    /**
     * Update match results
     *
     * @param array $matches matches.
     * @param array $home_points home points.
     * @param array $away_points away points.
     * @param array $custom custom.
     * @param string $season season.
     * @param false|string $final_round final indicator.
     * @param string $confirmed confirmed status.
     *
     * @return int
     */
    public function update_match_results( array $matches, array $home_points, array $away_points, array $custom, string $season, false|string $final_round = false, string $confirmed = 'Y' ): int {
        $num_matches = 0;
        if ( ! empty( $matches ) ) {
            foreach ( $matches as $match_id ) {
                $match         = get_match( $match_id );
                $c             = $custom[$match_id] ?? array();
                $points_home   = isset( $home_points[$match_id] ) ? floatval( $home_points[$match_id] ) : null;
                $points_away   = isset( $away_points[$match_id] ) ? floatval( $away_points[$match_id] ) : null;
                $match_updated = $match->update_result( $points_home, $points_away, $c, $confirmed, $match->status );
                if ( $match_updated ) {
                    ++$num_matches;
                }
            }
        }

        if ( $num_matches > 0 && ! $final_round ) {
            $this->update_standings( $season );
        }
        return $num_matches;
    }
    /**
     * Update standings function
     *
     * @param string $season season.
     */
    public function update_standings( string $season ): void {
        // update Standings for each team.
        $league_teams = $this->get_league_teams(
            array(
                'season' => $season,
                'cache'  => false,
            )
        );
        foreach ( $league_teams as $league_team ) {
            $this->save_standings( $league_team );
        }
        // Update Teams Rank and Status.
        $this->set_teams_rank( $season );
    }
    /**
     * Update points for given team
     *
     * @param object $league_team team.
     */
    private function save_standings( object $league_team ): void {
        global $wpdb;

        if ( 'manual' !== $this->point_rule ) {
            $league_team = get_league_team( $league_team );
            $league_team->get_num_done_matches();
            $league_team->get_num_won_matches();
            $league_team->get_num_draw_matches();
            $league_team->get_num_lost_matches();

            $league_team->points  = $this->calculate_points( $league_team );
            $league_team->diff    = 0;
            $league_team->points_2 = array(
                'plus'  => 0,
                'minus' => 0,
            );
            // get custom team standings data.
            $league_team->custom = $this->get_standings_data( $league_team->id, $league_team->custom );
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_league_teams SET `points_plus` = %f, `points_minus` = %f, `points_2_plus` = %d, `points_2_minus` = %d, `done_matches` = %d, `won_matches` = %d, `draw_matches` = %d, `lost_matches` = %d, `diff` = %d, `custom` = %s WHERE `team_id` = %d AND `league_id` = %d AND `season` = %s",
                    $league_team->points['plus'],
                    $league_team->points['minus'],
                    $league_team->points_2['plus'],
                    $league_team->points_2['minus'],
                    $league_team->done_matches,
                    $league_team->won_matches,
                    $league_team->draw_matches,
                    $league_team->lost_matches,
                    $league_team->diff,
                    maybe_serialize( $league_team->custom ),
                    $league_team->id,
                    $league_team->league_id,
                    $league_team->season
                )
            ); // db call ok.
            wp_cache_delete( $league_team->id, '$league_team' );
            wp_cache_delete( $league_team->league_id, 'leaguetable' );
        }

    }

    /**
     * Calculate points for given team depending on point rule
     *
     * @param object $team team.
     * @param false|array $matches match.
     *
     * @return array
     */
    private function calculate_points( object $team, false|array $matches = false ): array {
        $team_id     = $team->id;
        $season      = $team->season;
        $point_rule  = $this->get_point_rule( $this->point_rule );
        $points      = array(
            'plus'  => 0,
            'minus' => 0,
        );
        if ( ! $matches ) {
            $matches = $this->get_matches(
                array(
                    'team_id'          => $team->id,
                    'match_day'        => -1,
                    'limit'            => false,
                    'season'           => $season,
                    'cache'            => false,
                    'reset_query_args' => true,
                )
            );
        }

        if ( $matches ) {
            $points_for     = 0;
            $points_against = 0;
            foreach ( $matches as $match ) {
                if ( $match->home_team === strval( $team_id ) ) {
                    $points_for     += $match->home_points;
                    $points_against += $match->away_points;
                } elseif ( $match->away_team === strval( $team_id ) ) {
                    $points_for     += $match->away_points;
                    $points_against += $match->home_points;
                }
            }
            $points['plus']  = $points_for;
            $points['minus'] = $points_against;
        } else {
            $forwin             = empty( $point_rule['forwin'] ) ? 0 : $point_rule['forwin'];
            $forwin_split       = empty( $point_rule['forwin_split'] ) ? 0 : $point_rule['forwin_split'];
            $forloss_split      = empty( $point_rule['forloss_split'] ) ? 0 : $point_rule['forloss_split'];
            $forshare           = empty( $point_rule['forshare'] ) ? 0 : $point_rule['forshare'];
            $forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
            $walkover_penalty   = empty( $point_rule['forwalkover_match'] ) ? 0 : $point_rule['forwalkover_match'];
            $rubber_win         = empty( $point_rule['rubber_win'] ) ? 0 : $point_rule['rubber_win'];
            $rubber_draw        = empty( $point_rule['rubber_draw'] ) ? 0 : $point_rule['rubber_draw'];
            $matches_win        = empty( $point_rule['matches_win'] ) ? 0 : $point_rule['matches_win'];
            $matches_draw       = empty( $point_rule['matches_draw'] ) ? 0 : $point_rule['matches_draw'];
            $shared_match       = empty( $point_rule['shared_match'] ) ? 0 : $point_rule['shared_match'];
            $data               = $this->get_standings_data( $team_id, array(), $matches );
            if ( ! empty( $point_rule['match_result'] ) ) {
                if ( 'rubber_count' === $point_rule['match_result'] ) {
                    $points['plus'] = $data['rubbers_won'] * $rubber_win + $data['rubbers_shared'] * $rubber_draw - ( $data['no_player'] * $forwalkover_rubber ) - $data['no_team'] * $walkover_penalty + $data['matches_shared'] * $shared_match;
                } elseif ( 'games' === $point_rule['match_result'] ) {
                    $points['plus'] = $data['games_won'];
                }
            } else {
                $points['plus']  = $data['sets_won'] + ( $data['straight_set']['win'] * $forwin ) + ( $data['split_set']['win'] * $forwin_split ) + ( $data['split_set']['lost'] * $forloss_split ) + ( $data['sets_shared'] * $forshare ) - ( $data['no_player'] * $forwalkover_rubber ) - ( $data['no_team'] * $walkover_penalty ) + ( $data['matches_won'] * $matches_win ) + ( $data['matches_shared'] * $matches_draw );
                $points['minus'] = $data['sets_allowed'] + ( $data['straight_set']['lost'] * $forwin ) + ( $data['split_set']['win'] * $forloss_split ) + ( $data['split_set']['lost'] * $forwin_split ) + ( $data['sets_shared'] * $forshare );
            }
        }
        return $points;
    }

    /**
     * Break ties
     *
     * @param array $teams teams.
     *
     * @return array
     */
    private function tiebreak( array $teams ): array {
        // re-order teams by rank.
        foreach ( $teams as $key => $row ) {
            $rank[ $key ] = $row->rank;
        }
        array_multisort( $rank, SORT_ASC, $teams );

        return $teams;
    }

    /**
     * ========================
     * Sports customization section. The following functions can be overridden by sports class
     * ========================
     */

    /**
     * Determine if two teams are tied based on
     *
     * 1) Primary points
     * 2) Secondary point difference
     * 3) Secondary points
     * 4) Win Percentage
     *
     * @param object $team1 team1.
     * @param object $team2 team2.
     *
     * @return boolean
     */
    protected function is_tie( object $team1, object $team2 ): bool {
        // initialize results array.

        $res = array(
            'primary'    => false,
            'sets_diff'  => false,
            'games_diff' => false,
            'sets_won'   => false,
        );

        if ( $team1->points['plus'] === $team2->points['plus'] ) {
            $res['primary'] = true;
        }
        if ( ( $team1->sets_won - $team1->sets_allowed ) === ( $team2->sets_won - $team2->sets_allowed ) ) {
            $res['sets_diff'] = true;
        }
        if ( ( $team1->games_won - $team1->games_allowed ) === ( $team2->games_won - $team2->games_allowed ) ) {
            $res['sets_diff'] = true;
        }
        if ( $team1->sets_won === $team2->sets_won ) {
            $res['sets_won'] = true;
        }

        // get unique results.
        $res = array_values( array_unique( $res ) );

        // more than one results, i.e. not tied.
        if ( count( $res ) > 1 ) {
            return false;
        }

        return $res[0];
    }

    /**
     * Custom update results method
     *
     * @param object $match match.
     *
     * @return Racketmanager_Match
     */
    protected function update_results( object $match ): object {
        $match = get_match( $match->id );

        // exit if only one team is set.
        if ( '-1' === $match->home_team || '-1' === $match->away_team ) {
            return $match;
        }

        if ( empty( $match->home_points ) && empty( $match->away_points ) ) {
            $score = array(
                'home' => '0',
                'away' => '0',
            );
            if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
                $rubbers = $match->get_rubbers();

                foreach ( $rubbers as $rubber ) {
                    if ( is_numeric( $rubber->home_points ) ) {
                        $score['home'] += intval( $rubber->home_points );
                    }
                    if ( is_numeric( $rubber->away_points ) ) {
                        $score['away'] += intval( $rubber->away_points );
                    }
                }
            } else {
                foreach ( $match->sets as $set ) {
                    if ( isset( $set['player1'] ) && isset( $set['player2'] ) ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            $score['home'] += 1;
                        } else {
                            $score['away'] += 1;
                        }
                    }
                }
            }
            $match->home_points = $score['home'];
            $match->away_points = $score['away'];
            $match->get_result( $match->home_points, $match->away_points, $match->custom );
        }
        return $match;
    }
    /**
     * Get custom standings data
     *
     * @param int $team_id team reference.
     * @param array $data data used to get standings.
     * @param array $matches array of matches.
     *
     * @return array
     */
    protected function get_standings_data( int $team_id, array $data, array $matches = array() ): array {
        $data['straight_set']   = array(
            'win'  => 0,
            'lost' => 0,
        );
        $data['split_set']      = $data['straight_set'];
        $data['games_allowed']  = 0;
        $data['games_won']      = 0;
        $data['sets_won']       = 0;
        $data['sets_allowed']   = 0;
        $data['sets_shared']    = 0;
        $data['no_player']      = 0;
        $data['no_team']        = 0;
        $data['rubbers_won']    = 0;
        $data['rubbers_shared'] = 0;
        $data['matches_won']    = 0;
        $data['matches_shared'] = 0;

        $walkover_sets  = $this->num_sets_to_win;
        $set_type       = Util::get_set_type( $this->scoring );
        $set_info       = Util::get_set_info( $set_type );
        $games_to_win   = $set_info->min_win;
        $walkover_games = $walkover_sets * $games_to_win;

        $season = $this->get_season();

        if ( ! $matches ) {
            $matches = $this->get_matches_for_standings( $season, $team_id );
        }
        foreach ( $matches as $match ) {
            $team_ref       = strval( $team_id ) === $match->home_team ? 'home' : 'away';
            $team_ref_alt   = 'home' === $team_ref ? 'away' : 'home';
            $player_ref     = strval( $team_id ) === $match->home_team ? 'player1' : 'player2';
            $player_ref_alt = 'player1' === $player_ref ? 'player2' : 'player1';
            $match          = get_match( $match );
            if ( ! empty( $match->winner_id ) && ! empty( $match->loser_id ) && 'W' !== $match->teams['home']->status && 'W' !== $match->teams['away']->status && ! $match->is_cancelled ) {
                if ( ! empty( $match->status ) && 3 === $match->status ) {
                    ++$data['matches_shared'];
                }
                if ( ! empty( $this->num_rubbers ) ) {
                    $rubbers_won    = 0;
                    $rubbers_lost   = 0;
                    $rubbers_shared = 0;
                    $rubbers        = $match->get_rubbers();
                    $walkovers      = 0;
                    foreach ( $rubbers as $rubber ) {
                        if ( ! $rubber->is_walkover && ! $rubber->is_shared ) {
                            $num_sets    = count( $rubber->sets );
                            $set_retired = null;
                            if ( $rubber->is_retired || $rubber->is_abandoned ) {
                                for ( $s1 = $num_sets; $s1 > 0; $s1-- ) {
                                    if ( '' !== $rubber->sets[ $s1 ]['player1'] || '' !== $rubber->sets[ $s1 ]['player2'] ) {
                                        $set_retired = $s1;
                                        break;
                                    }
                                }
                            }
                            for ( $j = 1; $j <= $this->num_sets; $j++ ) {
                                $set_type = Util::get_set_type( $this->scoring, null, $this->num_sets, $j );
                                if ( isset( $rubber->sets[ $j ]['player1'] ) && null !== $rubber->sets[ $j ]['player1'] ) {
                                    $set        = $rubber->sets[ $j ];
                                    $set_winner = null;
                                    if ( $rubber->is_retired && $set_retired === $j ) {
                                        if ( $team_ref === $rubber->custom['retired'] ) {
                                            $set_winner = $team_ref_alt;
                                        } else {
                                            $set_winner = $team_ref;
                                        }
                                    }
                                    if ( $rubber->is_abandoned && $set_retired === $j ) {
                                        $set_winner = 'abandoned';
                                    }
                                    if ( is_numeric( trim( $set[ $player_ref_alt ] ) ) ) {
                                        if ( 'MTB' === $set_type ) {
                                            if ( $rubber->loser_id === strval( $team_id ) ) {
                                                ++$data['games_allowed'];
                                            }
                                        } else {
                                            $data['games_allowed'] += intval( $set[ $player_ref_alt ] );
                                        }
                                    }
                                    if ( is_numeric( trim( $set[ $player_ref ] ) ) ) {
                                        if ( 'MTB' === $set_type ) {
                                            if ( $rubber->winner_id === strval( $team_id ) ) {
                                                ++$data['games_won'];
                                            }
                                        } else {
                                            $data['games_won'] += intval( $set[ $player_ref ] );
                                        }
                                    }
                                    if ( ( $set[ $player_ref ] > $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref === $set_winner ) {
                                        $data['sets_won'] += 1;
                                    } elseif ( ( $set[ $player_ref ] < $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
                                        $data['sets_allowed'] += 1;
                                    } elseif ( 'S' === strtoupper( $set[ $player_ref ] ) || $rubber->is_abandoned ) {
                                        $data['sets_shared'] += 1;
                                    }
                                }
                            }
                        } elseif ( $rubber->is_shared ) {
                            $data['sets_shared'] += $this->num_sets;
                            ++$data['rubbers_shared'];
                            ++$rubbers_shared;
                        }
                        if ( $rubber->winner_id === strval( $team_id ) || '-1' === $rubber->winner_id ) { // winning team.
                            if ( $rubber->winner_id === strval( $team_id ) ) {
                                ++$data['rubbers_won'];
                                ++$rubbers_won;
                            }
                            if ( $rubber->is_walkover ) {
                                $data['sets_won']            += $walkover_sets;
                                $data['games_won']           += $walkover_games;
                                $data['straight_set']['win'] += 1;
                            } elseif ( $match->home_team === strval( $team_id ) ) {   // home team.
                                if ( $data['sets_won'] > '0' ) {
                                    if ( $rubber->away_points > '0' ) {
                                        $data['split_set']['win'] += 1;
                                    } else {
                                        $data['straight_set']['win'] += 1;
                                    }
                                }
                            } elseif ( $rubber->home_points > '0' ) { // away team split set win.
                                if ( $data['sets_won'] > '0' ) {
                                    $data['split_set']['win'] += 1;       // home team got a set.
                                }
                            } elseif ( $data['sets_won'] > '0' ) {                                  // home team straight set win.
                                $data['straight_set']['win'] += 1;
                            }
                        } elseif ( $rubber->loser_id === strval( $team_id ) ) { // losing team.
                            ++$rubbers_lost;
                            if ( $rubber->is_walkover ) {
                                $data['sets_allowed']         += $walkover_sets;
                                $data['games_allowed']        += $walkover_games;
                                $data['no_player']            += 1;
                                $data['straight_set']['lost'] += 1;
                                ++$walkovers;
                            } elseif ( $match->home_team === strval( $team_id ) ) {   // team loss.
                                if ( $rubber->home_points > '0' ) {
                                    $data['split_set']['lost'] += 1;
                                } else {
                                    $data['straight_set']['lost'] += 1;
                                }
                            } elseif ( $rubber->away_points > '0' ) { // team split set loss.
                                $data['split_set']['lost'] += 1;
                            } else {                                 // team straight set loss.
                                $data['straight_set']['lost'] += 1;
                            }
                        }
                    }
                    if ( intval( $match->league->num_rubbers ) === $walkovers ) {
                        $data['no_team'] += $walkovers;
                    }
                    if ( $rubbers_shared ) {
                        if ( $rubbers_won === $rubbers_lost ) {
                            ++$data['matches_shared'];
                        } elseif ( $rubbers_won > $rubbers_lost ) {
                            ++$data['matches_won'];
                        }
                    } elseif ( $rubbers_won > $rubbers_lost ) {
                        ++$data['matches_won'];
                    }
                } else {
                    $set_winner = null;
                    for ( $j = 1; $j <= $this->num_sets; $j++ ) {
                        $set = $match->sets[ $j ];
                        if ( ( $set[ $player_ref ] > $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref === $set_winner ) {
                            $data['sets_won'] += 1;
                        } elseif ( ( $set[ $player_ref ] < $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
                            $data['sets_allowed'] += 1;
                        } elseif ( 'S' === strtoupper( $set[ $player_ref ] ) ) {
                            $data['sets_shared'] += 1;
                        }
                        $data['games_allowed'] += $match->sets[ $j ][ $player_ref_alt ];
                        $data['games_won']     += $match->sets[ $j ][ $player_ref ];
                    }
                    if ( $this->num_sets > 1 && '' !== $match->sets[ $this->num_sets ]['player1'] && '' !== $match->sets[ $this->num_sets ]['player2'] ) {
                        if ( $match->winner_id === strval( $team_id ) ) {
                            $data['split_set']['win'] += 1;
                        } elseif ( $match->loser_id === strval( $team_id ) ) {
                            $data['split_set']['lost'] += 1;
                        }
                    } elseif ( $match->winner_id === strval( $team_id ) ) {
                        $data['straight_set']['win'] += 1;
                    } elseif ( $match->loser_id === strval( $team_id ) ) {
                        $data['straight_set']['lost'] += 1;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get matches for standings function
     *
     * @param string $season season.
     * @param int $team team id.
     *
     * @return array of matches.
     */
    private function get_matches_for_standings( string $season, int $team ): array {
        return $this->get_matches(
            array(
                'season'           => $season,
                'team_id'          => $team,
                'final'            => '',
                'limit'            => false,
                'cache'            => false,
                'home_points'      => 'not null',
                'away_points'      => 'not null',
                'reset_query_args' => true,
                'confirmed'        => true,
                'withdrawn'        => false,
            )
        );
    }

    /**
     * Import matches
     *
     * @param array $custom custom.
     * @param array $line line.
     * @param int $match_id match id.
     * @param int $col the starting column index.
     *
     * @return array
     */
    public function import_matches( array $custom, array $line, int $match_id, int $col ): array {
        if ( count( $this->fields_match ) > 0 ) {
            foreach ( $this->fields_match as $key => $data ) {
                if ( isset( $data['keys'] ) && is_array( $data['keys'][ array_keys( $data['keys'] )[0] ] ) ) {
                    foreach ( $data['keys'] as $k => $v ) {
                        $p          = ! empty( $line[ $col ] ) ? explode( '-', $line[ $col ] ) : array( '', '' );
                        $x          = array();
                        $x[ $v[0] ] = $p[0];
                        $x[ $v[1] ] = $p[1];

                        $custom[ $match_id ][ $key ][ $k ] = $x;

                        ++$col;
                    }
                } else {
                    if ( isset( $data['keys'] ) ) {
                        $p                     = ! empty( $line[ $col ] ) ? explode( '-', $line[ $col ] ) : array( '', '' );
                        $x                     = array();
                        $x[ $data['keys'][0] ] = $p[0];
                        $x[ $data['keys'][1] ] = $p[1];
                    } else {
                        $x = ! empty( $line[ $col ] ) ? $line[ $col ] : '';
                    }

                    $custom[ $match_id ][ $key ] = $x;

                    ++$col;
                }
            }
        }

        return $custom;
    }

    /**
     * Import teams
     *
     * @param array $custom custom.
     * @param array $line line.
     * @param int $col the starting column index.
     *
     * @return array
     */
    public function import_teams( array $custom, array $line, int $col ): array {
        if ( count( $this->fields_team ) > 0 ) {
            foreach ( $this->fields_team as $key => $data ) {
                if ( isset( $data['keys'] ) ) {
                    $p                     = ! empty( $line[ $col ] ) ? explode( '-', $line[ $col ] ) : array( '', '' );
                    $x                     = array();
                    $x[ $data['keys'][0] ] = $p[0];
                    $x[ $data['keys'][1] ] = $p[1];
                    $custom[ $key ]        = $x;
                } else {
                    $custom[ $key ] = $line[$col] ?? 0;
                }

                ++$col;
            }
        }

        return $custom;
    }

    /**
     * Schedule matches
     */
    public function schedule_matches(): void {
        $season         = $this->get_season();
        $schedule_teams = $this->get_league_teams(
            array(
                'season'  => $season,
                'status'  => 'active',
                'orderby' => array(
                    'group' => 'ASC',
                    'title' => 'ASC',
                ),
            )
        );
        if ( $this->event->is_box ) {
            $num_teams = $this->num_teams;
            if ( 0 !== $this->num_teams % 2 ) {
                ++$num_teams;
            }
            $num_rounds    = $num_teams - 1;
            $num_teams_max = $num_teams;
            $home_away     = false;
        } else {
            $num_rounds = $this->current_season['num_match_days'];
            $home_away  = empty( $this->event->current_season['home_away'] ) ? false : $this->event->current_season['home_away'];
            if ( $home_away ) {
                $num_rounds = $num_rounds / 2;
            }
            $num_teams_max = $num_rounds + 1;
        }
        $refs = array();
        for ( $i = 1; $i <= $num_teams_max; $i++ ) {
            $refs[] = $i;
        }
        foreach ( $schedule_teams as $team ) {
            if ( $team->group ) {
                $ref = array_search( intval( $team->group ), $refs, true );
                array_splice( $refs, $ref, 1 );
            }
        }
        foreach ( $schedule_teams as $team ) {
            if ( ! $team->group ) {
                $group = $refs[0];
                Util::set_table_group( $group, $team->table_id );
                array_splice( $refs, 0, 1 );
            }
        }

        $schedule_teams = $this->get_league_teams(
            array(
                'season'      => $season,
                'status'      => 'active',
                'get_details' => true,
                'cache'       => false,
                'orderby'     => array(
                    'group' => 'ASC',
                    'title' => 'ASC',
                ),
            )
        );
        if ( $schedule_teams ) {
            if ( $refs ) {
                foreach ( $refs as $ref ) {
                    $team             = array(
                        'id'     => -1,
                        'title'  => __( 'Bye', 'racketmanager' ),
                        'player' => array(),
                        'group'  => $ref,
                    );
                    $schedule_teams[] = (object) $team;
                }
                usort( $schedule_teams, fn ( $a, $b ) => $a->group <=> $b->group );
            }
            $this->create_schedule( $schedule_teams, $num_rounds, $home_away );
        }
    }

    /**
     * Schedule matches
     *
     * @param array   $teams teams to create schedule for.
     * @param string  $num_rounds number of rounds.
     * @param boolean $home_away home and away indicator.
     */
    public function create_schedule( array $teams, string $num_rounds, bool $home_away ): void {
        $season      = $this->current_season['name'];
        $match_dates = $this->current_season['match_dates'];
        $num_teams   = count( $teams );
        if ( ! $num_rounds ) {
            $num_rounds = $this->current_season['num_match_days'];
        }
        if ( $num_teams & 1 ) {
            ++$num_teams;
        }
        $schedule_round_robin = new Schedule_Round_Robin();
        $rounds               = $schedule_round_robin->generate( $num_teams, $num_rounds, $home_away );
        $this->create_match_schedule( $rounds, $teams, $match_dates, $season, $this->event->is_box );
    }
    /**
     * Create match schedule with teams
     *
     * @param array $rounds rounds.
     * @param array $teams array of teams.
     * @param array $match_dates array of match dates.
     * @param string $season season.
     * @param boolean $is_box is this a box league.
     */
    public function create_match_schedule( array $rounds, array $teams, array $match_dates, string $season, bool $is_box ): void {
        $num_rounds = count( $rounds );
        for ( $i = 0; $i < $num_rounds; $i++ ) {
            if ( ! $is_box ) {
                $round_number              = $i + 1;
                $start_date                = $match_dates[ $i ];
                $rounds[ $i ]['startDate'] = $start_date;
            }
            $fixtures = $rounds[ $i ]['fixtures'];
            foreach ( $fixtures as $fixture ) {
                $home_team_dtls = $teams[ $fixture['home'] - 1 ];
                $away_team_dtls = $teams[ $fixture['away'] - 1 ];
                if ( -1 !== $home_team_dtls->id && -1 !== $away_team_dtls->id ) {
                    $match            = new stdClass();
                    $match->season    = $season;
                    $match->league_id = $this->id;
                    $match->home_team = $home_team_dtls->id;
                    $match->away_team = $away_team_dtls->id;
                    if ( $is_box ) {
                        $match->date      = null;
                        $match->match_day = 1;
                        $match->location  = '';
                    } else {
                        $match_day        = $home_team_dtls->match_day;
                        $match_time       = $home_team_dtls->match_time;
                        $day              = Util_Lookup::get_match_day_number( $match_day );
                        $match_date       = Util::amend_date( $start_date, $day );
                        $match->date      = $match_date . ' ' . $match_time;
                        $match->match_day = $round_number;
                        $match->location  = $home_team_dtls->club->shortcode;
                    }
                    new Racketmanager_Match( $match );
                }
            }
        }
    }
    /**
     * Add match to league function
     *
     * @param object $match match object.
     *
     * @return void
     */
    public function add_match( object $match ): void {
        $match = new Racketmanager_Match( $match );
        if ( $this->is_championship && $this->event->current_season['home_away'] && 'final' !== $match->final_round ) {
            $match->leg              = 1;
            $new_match               = clone $match;
            $weeks_diff              = empty( $this->event->competition->seasons[ $match->season ]['home_away_diff'] ) ? 2 : $this->event->competition->seasons[ $match->season ]['home_away_diff'];
            $new_match->date         = Util::amend_date( $match->date, $weeks_diff, '+', 'weeks' );
            $new_match->linked_match = $match->id;
            $new_match->leg          = $match->leg + 1;
            if ( ! empty( $match->host ) ) {
                $new_match->host = 'home' === $match->host ? 'away' : 'home';
            }
            unset( $new_match->id );
            $new_match = new Racketmanager_Match( $new_match );
            $new_match->update_legs( $new_match->leg, $match->id );
            $match->update_legs( $match->leg, $new_match->id );
        }
    }
    /**
     * Update match within league function
     *
     * @param object $match match object.
     */
    public function update_match( object $match ): void {
        $match->update();
        if ( ! empty( $match->linked_match ) ) {
            $linked_match            = get_match( $match->linked_match );
            $linked_match->home_team = $match->home_team;
            $linked_match->away_team = $match->away_team;
            $linked_match->date      = gmdate( 'Y-m-d H:i:s', strtotime( $match->date . ' +14 day' ) );
            if ( ! empty( $match->host ) ) {
                $linked_match->host = 'home' === $match->host ? 'away' : 'home';
            }
            $linked_match->update();
        }
    }
    /**
     * Get players for league
     *
     * @param array $args search arguments.
     *
     * @return array
     */
    public function get_players( array $args = array() ): array {
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(),
            'club'    => false,
            'team'    => false,
            'count'   => false,
            'group'   => false,
            'stats'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];
        $club     = $args['club'];
        $team     = $args['team'];
        $count    = $args['count'];
        $group    = $args['group'];
        $stats    = $args['stats'];

        $search_terms  = array();
        $search_args   = array();
        $search_args[] = $this->id;
        if ( ! $season ) {
            $season = $this->current_season['name'];
        }
        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        if ( $team ) {
            $search_terms[] .= '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
            $search_args[]   = $team;
            $search_args[]   = 'home';
            $search_args[]   = $team;
            $search_args[]   = 'away';
        }
        if ( $club ) {
            $search_terms[] .= "(( `home_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s))";
            $search_args[]   = $club;
            $search_args[]   = 'home';
            $search_args[]   = $club;
            $search_args[]   = 'away';
        }
        $search = Util::search_string( $search_terms );
        $order  = Util::order_by_string( $orderby );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT DISTINCT `player_id`, `club_player_id`';
        }
        $sql .= " FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = %d" . $search;
        if ( $count ) {
            $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $search_args,
            );
            $num_players = wp_cache_get( md5( $sql ), 'league_rubber_players' );
            if ( ! $num_players ) {
                $num_players = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $num_players, 'league_rubber_players' );
            }
            return $num_players;
        }
        $sql .= $order;
        if ( intval( $limit > 0 ) ) {
            $sql          .= ' LIMIT %d, %d';
            $search_args[] = $offset;
            $search_args[] = $limit;
        }
        $sql = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_args,
        );
        $players = wp_cache_get( md5( $sql ), 'league_rubber_players' );
        if ( ! $players ) {
            $players = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $players, 'league_rubber_players' );
        }
        $league_players = array();
        foreach ( $players as $player ) {
            $player = get_player( $player->player_id );
            if ( $player->system_record ) {
                continue;
            }
            if ( ! $stats ) {
                $league_players[] = $player->get_fullname();
            } else {
                $player->matches = $player->get_matches( $this, $this->current_season['name'], 'league' );
                $player->stats   = $player->get_stats();
                if ( ! $team ) {
                    $player->team = $this->get_player_team( array( 'player' => $player->get_id() ) );
                }
                $player->win_pct      = $player->stats['total']->win_pct;
                $player->matches_won  = $player->stats['total']->matches_won;
                $player->matches_lost = $player->stats['total']->matches_lost;
                $player->played       = $player->stats['total']->played;
                $league_players[]     = $player;
            }
        }
        if ( ! $stats ) {
            asort( $league_players );
        } else {
            $won    = array_column( $league_players, 'matches_won' );
            $played = array_column( $league_players, 'played' );
            array_multisort( $won, SORT_DESC, $played, SORT_ASC, $league_players );
        }
        if ( $group ) {
            $this->players = array();
            foreach ( $league_players as $player ) {
                $key = strtoupper( substr( $player, 0, 1 ) );
                if ( false === array_key_exists( $key, $this->players ) ) {
                    $this->players[ $key ] = array();
                }
                // now just add the row data.
                $this->players[ $key ][] = $player;
            }
        } else {
            $this->players = $league_players;
        }

        return $this->players;
    }
    /**
     * Get Player team function
     *
     * @param array $args array of parameters.
     *
     * @return object team
     */
    private function get_player_team( array $args = array() ): object {
        global $wpdb;
        $defaults = array(
            'season' => false,
            'team'   => false,
            'player' => false,
        );
        $args     = array_merge( $defaults, $args );
        $season   = $args['season'];
        $team     = $args['team'];
        $player   = $args['player'];

        $search_terms  = array();
        $search_args   = array();
        $search_args[] = $this->id;
        if ( ! $season ) {
            $season = $this->current_season['name'];
        }
        if ( $season ) {
            $search_terms[] = '`season` = %s';
            $search_args[]  = $season;
        }
        if ( $player ) {
            $search_terms[] = 'ro.`player_id` = %d';
            $search_args[]  = intval( $player );
        }
        $search = Util::search_string( $search_terms );
        $sql    = "SELECT distinct t.`id`, t.`title` FROM $wpdb->racketmanager_teams AS t, $wpdb->racketmanager_rubbers AS r, $wpdb->racketmanager_rubber_players AS rp, $wpdb->racketmanager_matches AS m, $wpdb->racketmanager_club_players AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND rp.`club_player_id` = ro.`id` AND ((rp.`player_team` = 'home' AND m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND m.`away_team` = t.`id`)) AND ro.`club_id` = t.`club_id` AND r.`match_id` = m.`id` AND m.`league_id` = %d " . $search;
        $sql    = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_args,
        );
        $teams = wp_cache_get( md5( $sql ), 'player_team' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'player_team' );
        }
        if ( $teams ) {
            $team = $teams[0];
        }
        return $team;
    }
    /**
     * Set rounds function
     *
     * @param string $season season name.
     * @param array $rounds round details.
     *
     * @return void
     */
    public function set_rounds( string $season, array $rounds ): void {
        global $wpdb;
        if ( empty( $this->seasons[ $season ] ) ) {
            $this->seasons[ $season ] = array();
        }
        $this->seasons[ $season ]['rounds'] = $rounds;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager SET `seasons` = %s WHERE `id` = %d",
                maybe_serialize( $this->seasons ),
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'leagues' );
    }
    /**
     * Contact League Teams
     *
     * @param string $season season.
     * @param string $email_message message.
     *
     * @return boolean
     */
    public function contact_teams( string $season, string $email_message ): bool {
        global $racketmanager;
        $message_sent  = false;
        $teams         = $this->get_league_teams( array( 'season' => $season ) );
        $email_message = str_replace( '\"', '"', $email_message );
        $headers       = array();
        $email_from    = $racketmanager->get_confirmation_email( $this->event->competition->type );
        $headers[]     = 'From: ' . ucfirst( $this->event->competition->type ) . ' Secretary <' . $email_from . '>';
        $headers[]     = 'cc: ' . ucfirst( $this->event->competition->type ) . ' Secretary <' . $email_from . '>';
        $email_subject = $racketmanager->site_name . ' - ' . $this->title . ' ' . $season . ' - Important Message';
        $email_to      = array();
        foreach ( $teams as $team ) {
            $team_dtls = $this->get_team_dtls( $team->id );
            if ( ! empty( $team_dtls->contactemail ) ) {
                $email_to[]   = ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
                $message_sent = true;
            }
            if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
                $headers[]    = 'cc: ' . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
                $message_sent = true;
            }
        }
        if ( $message_sent ) {
            wp_mail( $email_to, $email_subject, $email_message, $headers );
        }
        return $message_sent;
    }
}

