<?php
/**
 * Competition API: Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Competition
 */

namespace Racketmanager\Domain;

use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_tournament;

/**
 * Class to implement the Competition object
 */
class Competition {
    /**
     * Competition ID
     *
     * @var ?int
     */
    public ?int $id = null;

    /**
     * Competition name
     *
     * @var string
     */
    public string $name;

    /**
     * Seasons data
     *
     * @var array|string|null
     */
    public array|string|null $seasons = array();

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
    public string $sport = 'tennis';

    /**
     * Point rule
     *
     * @var string
     */
    public string $point_rule = 'tennis';

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
     * Team ranking mode
     *
     * @var string
     */
    public string $team_ranking = 'auto';

    /**
     * League mode
     *
     * @var string
     */
    public string $mode = 'default';

    /**
     * Default match starting time
     *
     * @var array
     */
    public array $default_match_start_time = array(
        'hour'    => 19,
        'minutes' => 30,
    );
    /**
     * Finals
     *
     * @var array
     */
    public array $finals = array();

    /**
     * Standings table layout settings
     *
     * @var array
     */
    public array $standings = array(
        'status'     => 1,
        'pld'        => 1,
        'won'        => 1,
        'tie'        => 1,
        'lost'       => 1,
        'winPercent' => 0,
        'last5'      => 1,
        'sets'       => 1,
        'games'      => 1,
    );
    /**
     * Number of teams per page in list
     *
     * @var int
     */
    public int $num_matches_per_page = 10;

    /**
     * Event offsets indexed by ID
     *
     * @var array
     */
    public array $event_index = array();
    /**
     * Championship flag
     *
     * @var boolean
     */
    public bool $is_championship = false;

    /**
     * Type
     *
     * @var string
     */
    public string $type = '';

    /**
     * Current season
     *
     * @var array|false
     */
    public array|false $current_season = array();

    /**
     * Number of match days
     *
     * @var int
     */
    public int $num_match_days = 0;

    /**
     * Events
     *
     * @var array
     */
    public array $events = array();
    /**
     * Settings
     *
     * @var array
     */
    public array $settings = array();
    /**
     * Entry type
     *
     * @var string
     */
    public string $entry_type;
    /**
     * Cup flag
     *
     * @var boolean
     */
    public bool $is_cup = false;
    /**
     * Tournament flag
     *
     * @var boolean
     */
    public bool $is_tournament = false;
    /**
     * League flag
     *
     * @var boolean
     */
    public bool $is_league = false;
    /**
     * Team entry flag
     *
     * @var boolean
     */
    public bool $is_team_entry = false;
    /**
     * Teams
     *
     * @var array
     */
    public array $teams = array();
    /**
     * Current phase string
     *
     * @var string|null
     */
    public ?string $current_phase = null;
    /**
     * Player entry flag
     *
     * @var boolean
     */
    public bool $is_player_entry = false;
    /**
     * Players array
     *
     * @var array
     */
    public array $players = array();
    /**
     * Clubs array
     *
     * @var array
     */
    public array $clubs = array();
    /**
     * Date Open
     *
     * @var string|null
     */
    public mixed $date_open;
    /**
     * Date Start
     *
     * @var string|null
     */
    public mixed $date_start;
    /**
     * Date End
     *
     * @var string|null
     */
    public mixed $date_end;
    /**
     * Venue
     *
     * @var string|null
     */
    public mixed $venue;
    /**
     * Is complete
     *
     * @var boolean
     */
    public bool $is_complete = false;
    /**
     * Is started
     *
     * @var boolean
     */
    public bool $is_started = false;
    /**
     * Is closed
     *
     * @var boolean
     */
    public bool $is_closed = false;
    /**
     * Is pending
     *
     * @var boolean
     */
    public bool $is_pending = false;
    /**
     * Is open
     *
     * @var boolean
     */
    public bool $is_open = false;
    /**
     * Competition code
     *
     * @var string|null
     */
    public ?string $competition_code = null;
    /**
     * Is competition active
     *
     * @var boolean
     */
    public bool $is_active = false;
    /**
     * Grade
     *
     * @var string|null
     */
    public ?string $grade;
    /**
     * Max teams per league
     *
     * @var int|null
     */
    public ?int $max_teams;
    /**
     * Max teams per club in a league
     *
     * @var int|null
     */
    public ?int $teams_per_club;
    /**
     * Number of teams promoted and relegated
     *
     * @var int|null
     */
    public ?int $teams_prom_relg;
    /**
     * Lowest team to be promoted
     *
     * @var int|null
     */
    public ?int $lowest_promotion;
    /**
     * Default round length
     *
     * @var int|null
     */
    public ?int $round_length;
    /**
     * Are there match day restrictions
     *
     * @var boolean
     */
    public bool $match_day_restriction;
    /**
     * Are weekend matches allowed
     *
     * @var boolean
     */
    public bool $match_day_weekends;
    /**
     * Are match dates fixed
     *
     * @var boolean
     */
    public bool $fixed_match_dates;
    /**
     * Are fixtures home and away
     *
     * @var boolean
     */
    public bool $home_away;
    /**
     * Number of courts available by club
     *
     * @var array
     */
    public array $num_courts_available;
    /**
     * Scoring default format
     *
     * @var string|null
     */
    public ?string $scoring;
    /**
     * Number of sets default
     *
     * @var int
     */
    public int $num_sets;
    /**
     * Number of rubbers default
     *
     * @var int|null
     */
    public ?int $num_rubbers;
    /**
     * Age group
     *
     * @var string|null
     */
    public ?string $age_group;
    /**
     * Reverse rubbers
     *
     * @var boolean|null
     */
    public ?bool $reverse_rubbers;
    /**
     * Home away difference
     *
     * @var int|null
     */
    public ?int $home_away_diff;
    /**
     * Filler weeks
     *
     * @var int|null
     */
    public ?int $filler_weeks;
    /**
     * Match days allowed array
     *
     * @var array
     */
    public array $match_days_allowed;
    /**
     * Start times - weekday/weekend/min/max
     *
     * @var array|null
     */
    public array|null $start_time;
    /**
     * Rules
     *
     * @var array
     */
    public array $rules;
    /**
     * Entries
     *
     * @var int
     */
    public int $entries;
    /**
     * Number of players
     *
     * @var int
     */
    public int $num_players;
    /**
     * Winners
     *
     * @var array
     */
    public array $winners;
    /**
     * Season
     *
     * @var int
     */
    public int $season;
    /**
     * Number of entries
     *
     * @var int
     */
    public int $num_entries;
    /**
     * Primary league
     *
     * @var int|null
     */
    public ?int $primary_league;
    /**
     * Offset
     *
     * @var int
     */
    public int $offset;
    /**
     * Competition type
     *
     * @var string
     */
    public string $competition_type;
    /**
     * Player
     *
     * @var object
     */
    public object $player;
    /**
     * Entry link
     *
     * @var string
     */
    public string $entry_link;
    /**
     * Config
     *
     * @var object
     */
    public object $config;
    /**
     * Match query arguments
     *
     * @var array
     */
    private array $match_query_args = array(
        'leagueId'            => false,
        'season'              => false,
        'final'               => false,
        'orderby'             => array(
            'league_id' => 'ASC',
            'id'        => 'ASC',
        ),
        'confirmed'           => false,
        'player'              => false,
        'match_date'          => false,
        'time'                => false,
        'timeOffset'          => false,
        'history'             => false,
        'club'                => false,
        'league_name'         => false,
        'team'                => false,
        'team_name'           => false,
        'home_team'           => false,
        'away_team'           => false,
        'match_day'           => false,
        'home_club'           => false,
        'count'               => false,
        'confirmationPending' => false,
        'resultPending'       => false,
        'status'              => false,
    );
    private string $select_count = 'SELECT COUNT(*)';
    private string $time_zero = ':00:00';
    public static function create( string $name, string $type, string $age_group ): self {
        $settings = array();
        if ( 'league' === $type ) {
            $mode       = 'default';
            $entry_type = 'team';
        } elseif ( 'cup' === $type ) {
            $mode       = 'championship';
            $entry_type = 'team';
        } elseif ( 'tournament' === $type ) {
            $mode       = 'championship';
            $entry_type = 'player';
        }
        if ( ! empty( $mode ) && ! empty( $entry_type ) ) {
            $settings['mode'] = $mode;
            $settings['entry_type'] = $entry_type;
        }
        $settings['sport'] = 'Tennis'; // Default

        $seasons = array();

        return new self( $name, $type, $age_group, $seasons, $settings );
    }

    public static function from_database( object $row ): self {
        return new self(
            $row->name,
            $row->type,
            $row->age_group,
            ( empty( $row->seasons ) ? array() : json_decode( $row->seasons, true ) ) ? : array(),
            json_decode($row->settings, true) ?: array(),
            (int) $row->id
        );
    }

    /**
     * Constructor
     *
     * @param string $name
     * @param string $type
     * @param string $age_group
     * @param array|null $seasons
     * @param array|null $settings
     * @param int|null $id
     */
    public function __construct(
        string $name,
        string $type,
        string $age_group,
        ?array $seasons = null,
        ?array $settings = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->age_group = $age_group;
        // Ensure properties are always objects even if null is passed
        $this->seasons = $seasons ?? array();
        $this->settings = $settings ?? array();
        // Championship.
        if ( 'championship' === $this->mode ) {
            $this->is_championship = true;
        } else {
            $this->is_championship = false;
        }
        $this->num_seasons = is_array( $this->seasons ) ? count( $this->seasons ) : 0;

        // Set current season and date-related derived fields using effective seasons
        if ( $this->num_seasons > 0 ) {
            $this->set_current_season();
        }
        $this->is_league       = false;
        $this->is_cup          = false;
        $this->is_tournament   = false;
        $this->is_team_entry   = false;
        $this->is_player_entry = false;
        switch ( $this->type ) {
            case 'league':
                $this->is_league     = true;
                $this->is_team_entry = true;
                break;
            case 'cup':
                $this->is_cup        = true;
                $this->is_team_entry = true;
                $finals              = array();
                $max_rounds          = 4;
                $r                   = $max_rounds;
                for ( $round = 1; $round <= $max_rounds; ++$round ) {
                    $num_teams      = pow( 2, $round );
                    $num_matches    = $num_teams / 2;
                    $key            = Util::get_final_key( $num_teams );
                    $name           = Util::get_final_name( $key );
                    $finals[ $key ] = array(
                        'key'         => $key,
                        'name'        => $name,
                        'num_matches' => $num_matches,
                        'num_teams'   => $num_teams,
                        'round'       => $r,
                    );
                    --$r;
                }
                $this->finals = $finals;
                break;
            case 'tournament':
                $this->is_tournament   = true;
                $this->is_player_entry = true;
                break;
            default:
                break;
        }
    }

    public function set_name( $name ): void {
        $this->name = $name;
    }

    public function set_age_group( $age_group ): void {
        $this->age_group = $age_group;
    }

    public function set_num_courts_available( int $club_id, int $num_courts_available ): void {
        if ( empty( $this->settings['num_courts_available'] ) ) {
            $this->settings['num_courts_available'] = array();
        }
        $this->settings['num_courts_available'][ $club_id ] = $num_courts_available;
    }

    public function set_seasons( array $seasons ): void {
        $this->seasons = $seasons;
    }

    /**
     * Get seasons as JSON string for UI/Shortcodes/API output.
     *
     * Note: Internal logic should continue using get_seasons() which returns array.
     */
    public function get_seasons_json(): string {
        // If seasons is already a JSON string, return as-is; otherwise encode
        if ( is_string( $this->seasons ) ) {
            return $this->seasons;
        }
        return wp_json_encode( $this->get_seasons() );
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_age_group(): ?string {
        return $this->age_group;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function get_settings(): array {
        return $this->settings;
    }

    public function get_seasons(): array {
        return $this->seasons;
    }

    /**
     * Get a season by name
     */
    public function get_season_by_name( string $name ): ?array {
        $seasons = $this->get_seasons();
        return $seasons[ $name ] ?? null;
    }

    /**
     * Has a specific season
     */
    public function has_season( string $name ): bool {
        return $this->get_season_by_name( $name ) !== null;
    }

    /**
     * Update settings
     *
     * @param array $settings settings array.
     */
    public function set_settings( array $settings ): void {
        $this->settings = $settings;
    }

    /**
     * Set current season
     *
     * @param string $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_current_season( string $season = '', bool $force_overwrite = false ): void {
        global $wp;
        // Use a local decoded seasons array (property holds JSON string in Option B)
        $seasons = $this->get_seasons();
        if ( ! empty( $season ) && true === $force_overwrite ) {
            $data = $seasons[ $season ] ?? null;
        } elseif ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            if ( ! isset( $seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $seasons[ $key ];
            }
        } elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            if ( ! isset( $seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $seasons[ $key ];
            }
        } elseif ( isset( $wp->query_vars['season'] ) ) {
            $key = $wp->query_vars['season'];
            if ( ! isset( $seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $seasons[ $key ];
            }
        } elseif ( ! empty( $season ) ) {
            $data = $seasons[ $season ] ?? null;
        } else {
            $data = null;
        }
        $today = gmdate( 'Y-m-d' );
        if ( ! isset( $data ) ) {
            foreach ( array_reverse( $seasons ) as $season_item ) {
                $date_active = empty( $season_item['date_closing'] ) ? null : Util::amend_date( $season_item['date_closing'], 7 );
                if ( ! empty( $date_active ) && $date_active <= $today ) {
                    $data = $season_item;
                    break;
                }
            }
        }
        if ( empty( $data ) ) {
            $tmp = $seasons;
            $data = ! empty( $tmp ) ? end( $tmp ) : null;
        }
        $count_match_dates = isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0;
        $this->is_complete = false;
        if ( empty( $data['date_end'] ) && $count_match_dates >= 2 ) {
            $data['date_end']               = end( $data['match_dates'] );
        }
        if ( empty( $data['date_start'] ) && $count_match_dates >= 2 ) {
            $data['date_start']             = $data['match_dates'][0];
        }
        if ( ! empty( $data['date_end'] ) && $today > $data['date_end'] ) {
            $this->current_phase = 'end';
            $this->is_complete   = true;
        } elseif ( ! empty( $data['date_start'] ) && $today >= $data['date_start'] ) {
            $this->current_phase = 'start';
            $this->is_started    = true;
        } elseif ( ! empty( $data['date_closing'] ) && $today > $data['date_closing'] ) {
            $this->current_phase = 'close';
            $this->is_closed     = true;
        } elseif ( ! empty( $data['date_open'] ) ) {
            if ( $today >= $data['date_open'] ) {
                $this->current_phase = 'open';
                $this->is_open       = true;
            } else {
                $this->current_phase = 'pending';
                $this->is_pending    = true;
            }
        } else {
            $this->current_phase = 'complete';
            $this->is_complete   = true;
        }
        $data['venue_name'] = null;
        if ( ! empty( $data['venue'] ) ) {
            $venue_club = get_club( $data['venue'] );
            if ( $venue_club ) {
                $data['venue_name'] = $venue_club->shortcode;
            }
        }
        $this->num_match_days = $data['num_match_days'];
        $this->current_season = $data;
    }

    /**
     * Get the current season name
     *
     * @return string
     */
    public function get_season(): string {
        return stripslashes( $this->current_season['name'] );
    }

    /**
     * Get events from database
     *
     * @param array $args search arguments.
     *
     * @return array
     */
    public function get_events( array $args = array() ): array {
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => null,
            'orderby' => array( 'name' => 'ASC' ),
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`competition_id` = %d', $this->id );

        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `id` FROM $wpdb->racketmanager_events $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $events = wp_cache_get( md5( $sql ), 'events' );
        if ( ! $events ) {
            $events = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $events, 'events' );
        }

        $event_index = array();
        foreach ( $events as $i => $event ) {
            $event = get_event( $event->id );
            if ( $season && empty( $event->get_season_by_name( $season ) ) ) {
                unset( $events[ $i ] );
            } else {
                $event_index[ $event->id ] = $i;
                $events[ $i ]              = $event;
            }
        }

        $this->events      = $events;
        $this->event_index = $event_index;

        return $events;
    }
    /**
     * Get teams from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(
                'league_title' => 'ASC',
                'name'         => 'ASC',
            ),
            'club'    => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];
        $club     = $args['club'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( 'e.`competition_id` = %d', $this->id );

        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
        }

        if ( $club ) {
            $search_terms[] = $wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }

        if ( $status ) {
            $search_terms[] = $wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
        }
        if ( $name ) {
            $search_terms[] = $wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
        }

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = $this->select_count;
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`status` AS `team_type`, e.`name` AS `event_name`';
        }
        $sql .= " FROM $wpdb->racketmanager_events e, $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_league_teams t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

        if ( $count ) {
            return $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
        }
        $sql .= Util::order_by_string( $orderby );
        $sql  = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql . ' LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );
        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }
        foreach ( $teams as $i => $team ) {
            $team->roster       = maybe_unserialize( $team->roster );
            $team->club         = get_club( $team->club_id );
            $team->title        = $team->name;
            $team->player_count = $this->get_players(
                array(
                    'season' => $season,
                    'count'  => true,
                    'team'   => $team->team_id,
                )
            );
            $teams[ $i ]        = $team;
        }

        $this->teams = $teams;

        return $teams;
    }
    /**
     * Get players for competition
     *
     * @param array $args search arguments.
     *
     * @return array|int
     */
    public function get_players( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(),
            'club'    => false,
            'team'    => false,
            'count'   => false,
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
        $stats    = $args['stats'];

        $competition_players = array();
        $players             = array();
        if ( $this->is_player_entry ) {
            $teams = $this->get_teams(
                array(
                    'season' => $season,
                )
            );
            foreach ( $teams as $team ) {
                foreach ( $team->player as $player ) {
                    $players[] = $player;
                }
            }
            $competition_players = array_unique( $players );
        } else {
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
                $sql = 'SELECT COUNT(distinct(`player_id`))';
            } else {
                $sql = 'SELECT DISTINCT `player_id`, `club_player_id`';
            }
            $sql .= " FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT l.`id` FROM $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE l.`event_id` = e.`id` AND e.`competition_id` = %d)" . $search;
            if ( $count ) {
                $sql = $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    $search_args,
                );
                $num_players = wp_cache_get( md5( $sql ), 'competition_rubber_players' );
                if ( ! $num_players ) {
                    $num_players = $wpdb->get_var(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                        $sql
                    ); // db call ok.
                    wp_cache_set( md5( $sql ), $num_players, 'competition_rubber_players' );
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
            $players = wp_cache_get( md5( $sql ), 'competition_rubber_players' );
            if ( ! $players ) {
                $players = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $players, 'competition_rubber_players' );
            }
            foreach ( $players as $player ) {
                $player = get_player( $player->player_id );
                if ( $player && ! $player->system_record ) {
                    if ( $stats ) {
                        $player->matches      = $player->get_matches( $this, $this->current_season['name'], 'competition' );
                        $player->stats        = $player->get_stats();
                        $player->win_pct      = $player->stats['total']->win_pct;
                        $player->matches_won  = $player->stats['total']->matches_won;
                        $player->matches_lost = $player->stats['total']->matches_lost;
                        $player->played       = $player->stats['total']->played;
                    }
                    $competition_players[] = $player;
                }
            }
        }
        if ( $stats ) {
            $won    = array_column( $competition_players, 'matches_won' );
            $played = array_column( $competition_players, 'played' );
            array_multisort( $won, SORT_DESC, $played, SORT_ASC, $competition_players );
        } else {
            asort( $competition_players );
        }
        $this->players = $competition_players;
        return $this->players;
    }

    /**
     * Get matches for competition
     *
     * @param array $match_args query arguments.
     *
     * @return array $matches
     */
    public function get_matches( array $match_args ): array {
        global $wpdb;

        $match_args           = array_merge( $this->match_query_args, $match_args );
        $league_id            = $match_args['leagueId'];
        $season               = $match_args['season'];
        $final                = $match_args['final'];
        $orderby              = $match_args['orderby'];
        $confirmed            = $match_args['confirmed'];
        $player               = $match_args['player'];
        $match_date           = $match_args['match_date'];
        $time                 = $match_args['time'];
        $time_offset          = $match_args['timeOffset'];
        $history              = $match_args['history'];
        $club                 = $match_args['club'];
        $league_name          = $match_args['league_name'];
        $team                 = $match_args['team'];
        $team_name            = $match_args['team_name'];
        $home_team            = $match_args['home_team'];
        $home_club            = $match_args['home_club'];
        $away_team            = $match_args['away_team'];
        $match_day            = $match_args['match_day'];
        $count                = $match_args['count'];
        $confirmation_pending = $match_args['confirmationPending'];
        $result_pending       = $match_args['resultPending'];
        $status               = $match_args['status'];
        $sql_from             = " FROM $wpdb->racketmanager_matches AS m, $wpdb->racketmanager AS l, $wpdb->racketmanager_events AS e, $wpdb->racketmanager_rubbers AS r";
        $search_terms         = array();
        if ( $count ) {
            $sql_fields = $this->select_count;
            $sql        = " WHERE l.`event_id` = e.`id` ";
        } else {
            $sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, m.`leg`";
            $sql        = " WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id` AND l.`event_id` = e.`id` ";
        }
        $search_terms[] = $wpdb->prepare( "e.`competition_id` = %d", $this->id );
        if ( $match_date ) {
            $search_terms[] = $wpdb->prepare( " DATEDIFF( %s, `date`) = 0", htmlspecialchars( wp_strip_all_tags( $match_date ) ) );
        }
        if ( $league_id ) {
            $search_terms[] = $wpdb->prepare( "`league_id` = %d", $league_id );
        }
        if ( $league_name ) {
            $search_terms[] = $wpdb->prepare( "league_id` in (select `id` from $wpdb->racketmanager WHERE `title` = %s ", $league_name);
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( ' `season` = %s ', $season );
        }
        if ( $final && 'all' !== $final ) {
            $search_terms[] = $wpdb->prepare( "`final` = %s", $final );
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . $this->time_zero;
        } else {
            $time_offset = '00:00:00';
        }
        if ( $status ) {
            $search_terms[] = $wpdb->prepare( "`confirmed` = %d", $status );
        }
        if ( $confirmed ) {
            $search_terms[] = "`confirmed` in ('P','A','C')";
            if ( $time_offset ) {
                $search_terms[] = $wpdb->prepare( "ADDTIME( `updated`, %s ) <= NOW()", $time_offset );
            }
        }
        if ( $player ) {
            $sql_from .= ", $wpdb->racketmanager_rubber_players AS rp";
            $search_terms[] = ' r.`id` = rp.`rubber_id`';
            $search_terms[] = $wpdb->prepare( "rp.`player_id` = %d", $player );
        }
        if ( $confirmation_pending ) {
            $confirmation_pending = intval( $confirmation_pending ) . $this->time_zero;
            $sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $result_pending = intval( $result_pending ) . $this->time_zero;
            $sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
        }

        // get only finished matches with score for time 'latest'.
        if ( 'latest' === $time ) {
            $search_terms[] = " (m.`home_points` != '' OR m.`away_points` != '') ";
        } elseif ( 'outstanding' === $time ) {
            $search_terms[] = $wpdb->prepare(" ADDTIME(m.`date`, %s) <= NOW() ", $time_offset );
            $search_terms[] = " m.`winner_id` = 0 ";
            $search_terms[] = " `confirmed` IS NULL ";
        } elseif ( 'next' === $time ) {
            $search_terms[] = ' TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0';
        }
        // get only updated matches in specified period for history.
        if ( $history ) {
            $search_terms[] = $wpdb->prepare( "`updated` >= NOW() - INTERVAL %s DAY", $history );
        }

        if ( $club ) {
            $search_terms[] = $wpdb->prepare( " (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d ) OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d ) ) ",  $club, $club );
        }
        if ( $home_club ) {
            $search_terms[] = $wpdb->prepare( " `home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d )", $home_club );
        }
        if ( ! empty( $home_team ) ) {
            $search_terms[] = $wpdb->prepare( " `home_team` = %s", $home_team );
        }
        if ( ! empty( $away_team ) ) {
            $search_terms[] = $wpdb->prepare( " `away_team` = %s", $away_team );
        }
        if ( ! empty( $team_name ) ) {
            $team_name_search = '%' . $team_name . '%';
            $search_terms[] = $wpdb->prepare( " (`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE %s) OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE %s)) ", $team_name_search, $team_name_search );
        }
        if ( ! empty( $team ) ) {
            $search_terms[] = $wpdb->prepare( " (`home_team` = %d OR `away_team` = %d) ", $team, $team );
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            $search_terms[] = $wpdb->prepare( " `match_day` = %d", $match_day );
        }
        $search = Util::search_string( $search_terms );
        $sql = $sql_fields . $sql_from . $sql . $search;
        if ( $count ) {
            $matches = intval(
                $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                )
            );
        } else {
            $sql .= Util::order_by_string( $orderby );
            // get matches.
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            $class = '';

            foreach ( $matches as $i => $match ) {
                $class        = ( 'alternate' === $class ) ? '' : 'alternate';
                $match        = get_match( $match );
                $match->class = $class;
                if ( $player ) {
                    $match->rubbers = $match->get_rubbers( $player );
                }
                $matches[ $i ] = $match;
            }
        }

        return $matches;
    }

    /**
     * Update seasons
     *
     * Accepts either an array (preferred) or a JSON string (Option B compatibility).
     * Keeps the internal seasons property as a JSON string and persists JSON to DB.
     *
     * @param array|string $seasons Season data as array or JSON string.
     */
    public function update_seasons( array|string $seasons ): bool {
        global $wpdb;

        // Normalize input to array
        if ( is_string( $seasons ) ) {
            $decoded = json_decode( $seasons, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $seasons = $decoded;
            } else {
                // Invalid JSON string; do not persist
                return false;
            }
        }

        // Compare to current decoded seasons
        $current = $this->get_seasons();
        if ( $current !== $seasons ) {
            // Keep property as JSON string
            $this->seasons = wp_json_encode( $seasons );
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_competitions SET `seasons` = %s WHERE `id` = %d",
                    $this->get_seasons_json(),
                    $this->id
                )
            );
            wp_cache_set( $this->id, $this, 'competitions' );
            return true;
        }
        return false;
    }

    /**
     * Add season
     *
     * @param object $season season data.
     */
    public function add_season( object $season ): bool {
        $seasons                  = $this->seasons;
        $seasons[ $season->name ] = (array) $season;
        $updates                  = $this->update_seasons( $seasons );
        $events                   = $this->get_events();
        if ( $events ) {
            $event_season                 = new stdClass();
            $event_season->name           = $season->name;
            $event_season->home_away      = $season->home_away;
            $event_season->num_match_days = $season->num_match_days;
            $event_season->match_dates    = $season->match_dates;
            $season_event                 = (array) $event_season;
            foreach ( $events as $event ) {
                $event->add_season( $season_event );
            }
        }
        return $updates;
    }
    /**
     * Update season
     *
     * @param array $season season data.
     */
    public function update_season( array $season ): bool {
        $seasons                 = $this->seasons;
        $season_name             = $season['name'];
        $seasons[ $season_name ] = $season;
        ksort( $seasons );
        return $this->update_seasons( $seasons );
    }

    /**
     * Contact Competition Teams
     *
     * @param string $season season.
     * @param string $email_message message.
     *
     * @return boolean
     */
    public function contact_teams( string $season, string $email_message ): bool {
        global $racketmanager;
        $email_message = str_replace( '\"', '"', $email_message );
        $headers       = array();
        $email_from    = $racketmanager->get_confirmation_email( $this->type );
        $headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $this->type ) . ' Secretary <' . $email_from . '>';
        $headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $this->type ) . ' Secretary <' . $email_from . '>';
        $email_subject = $racketmanager->site_name . ' - ' . $this->name . ' ' . $season . ' - Important Message';
        $email_to      = array();
        if ( $this->is_player_entry ) {
            if ( $this->is_tournament ) {
                $tournament_key = $this->id . ',' . $this->current_season['name'];
                $tournament     = get_tournament( $tournament_key, 'shortcode' );
                if ( $tournament ) {
                    $players = $tournament->get_players();
                    foreach ( $players as $player_name ) {
                        $player = get_player( $player_name, 'name' );
                        if ( $player && ! empty( $player->email ) ) {
                            $headers[] = RACKETMANAGER_BCC_EMAIL . $player->display_name . ' <' . $player->email . '>';
                        }
                    }
                }
            }
        } else {
            $teams  = array();
            $events = $this->get_events();
            foreach ( $events as $event ) {
                $event = get_event( $event );
                if ( $event ) {
                    $event_teams = $event->get_teams( array( 'season' => $event->current_season['name'] ) );
                    if ( $event_teams ) {
                        $teams = array_merge( $teams, $event_teams );
                    }
                }
            }
            foreach ( $teams as $team ) {
                $league = get_league( $team->league_id );
                if ( $league ) {
                    $team_dtls = $league->get_team_dtls( $team->team_id );
                    if ( ! empty( $team_dtls->contactemail ) ) {
                        $headers[] = RACKETMANAGER_BCC_EMAIL . ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
                    }
                    if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
                        $headers[] = RACKETMANAGER_BCC_EMAIL . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
                    }
                }
            }
        }
        wp_mail( $email_to, $email_subject, $email_message, $headers );
        return true;
    }

    /**
     * Retrieve competition instance
     *
     * @param int|string $competition_id competition id.
     * @param string|null $search_term search.
     */
    public static function get_instance( int|string $competition_id, ?string $search_term = 'id' ) {
        global $wpdb;
        switch ( $search_term ) {
            case 'name':
                $search = $wpdb->prepare(
                    '`name` = %s',
                    $competition_id
                );
                break;
            case 'id':
            default:
                $competition_id = (int) $competition_id;
                $search         = $wpdb->prepare(
                    '`id` = %d',
                    $competition_id
                );
                break;
        }
        if ( ! $competition_id ) {
            return false;
        }
        $competition = wp_cache_get( $competition_id, 'competitions' );
        if ( ! $competition ) {
            $competition = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `name`, `id`, `type`, `settings`, `seasons`, `age_group` FROM $wpdb->racketmanager_competitions WHERE " . $search . ' LIMIT 1'
            );
            if ( ! $competition ) {
                return false;
            }
            // Do not maybe_unserialize here; the domain constructor will normalize `settings`
            // check if specific sports class exists.
            if ( ! isset( $competition->sport ) ) {
                $competition->sport = '';
            }
            $instance = 'Racketmanager\sports\Competition_' . ucfirst( $competition->sport );
            if ( class_exists( $instance ) ) {
                $competition = new $instance( $competition );
            } else {
                $competition = new Competition( $competition );
            }

            wp_cache_set( $competition->id, $competition, 'competitions' );
        }

        return $competition;
    }

}
