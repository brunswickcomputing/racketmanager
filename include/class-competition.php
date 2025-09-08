<?php
/**
 * Competition API: Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Competition
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Competition object
 */
class Competition {
    /**
     * Competition ID
     *
     * @var int
     */
    public int $id;

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
     * Number of events
     *
     * @var int
     */
    public int $num_events = 0;

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
    public array $settings;
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
    public ?string $competition_code;
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
            $competition->settings              = (array) maybe_unserialize( $competition->settings );
            $competition->settings['type']      = $competition->type;
            $competition->settings['age_group'] = $competition->age_group;
            $competition                        = (object) ( $competition->settings + (array) $competition );
            // check if specific sports class exists.
            if ( ! isset( $competition->sport ) ) {
                $competition->sport = '';
            }
            $instance = 'Racketmanager\Competition_' . ucfirst( $competition->sport );
            if ( class_exists( $instance ) ) {
                $competition = new $instance( $competition );
            } else {
                $competition = new Competition( $competition );
            }

            wp_cache_set( $competition->id, $competition, 'competitions' );
        }

        return $competition;
    }

    /**
     * Constructor
     *
     * @param object $competition Competition object.
     */
    public function __construct( object $competition ) {
        if ( ! isset( $competition->id ) ) {
            $this->add( $competition );
        }
        if ( isset( $competition->settings ) ) {
            $competition->settings      = (array) maybe_unserialize( $competition->settings );
            $competition                = (object) array_merge( (array) $competition, $competition->settings );
        }

        foreach ( get_object_vars( $competition ) as $key => $value ) {
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key, $value );
            } else {
                $this->$key = $value;
            }
        }

        $this->name = stripslashes( $this->name );
        $this->type = stripslashes( $this->type );

        // set seasons.
        if ( empty( $this->seasons ) ) {
            $this->seasons = array();
        } else {
            $this->seasons = (array) maybe_unserialize( $this->seasons );
        }
        if ( ! is_admin() ) {
            $i       = 0;
            $seasons = array();
            foreach ( $this->seasons as $season ) {
                $seasons[ $season['name'] ] = $season;
                if ( isset( $season['status'] ) && 'draft' === $season['status'] ) {
                    unset( $seasons[ $season['name'] ] );
                }
                ++$i;
            }
            $this->seasons = $seasons;
        }
        $this->num_seasons = count( $this->seasons );
        $this->set_num_events( true );
        // set season to latest.
        if ( $this->num_seasons > 0 ) {
            $this->set_season();
            if ( ! empty( $this->current_season['date_open'] ) ) {
                $this->date_open = $this->current_season['date_open'];
            }
            if ( ! empty( $this->current_season['date_start'] ) ) {
                $this->date_start = $this->current_season['date_start'];
            } else {
                $this->date_start = $this->current_season['match_dates'][0] ?? null;
            }
            if ( ! empty( $this->current_season['date_end'] ) ) {
                $this->date_end = $this->current_season['date_end'];
            } else {
                $last_round = isset( $this->current_season['match_dates'] ) ? end( $this->current_season['match_dates'] ) : null;
                if ( $last_round ) {
                    $this->date_end = Util::amend_date( $last_round, 14 );
                }
            }
            if ( ! empty( $this->current_season['venue_name'] ) ) {
                $this->venue = $this->current_season['venue_name'];
            }
            if ( isset( $this->current_season['date_closing'] ) && $this->current_season['date_closing'] < gmdate( 'Y-m-d' ) ) {
                $this->is_active = true;
            } else {
                $this->is_active = false;
            }
        }

        // Championship.
        if ( 'championship' === $this->mode ) {
            $this->is_championship = true;
        } else {
            $this->is_championship = false;
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
        if ( empty( $this->competition_code ) ) {
            $this->competition_code = null;
        }
    }

    /**
     * Add new competition
     *
     * @param object $competition competition object.
     */
    private function add( object $competition ): void {
        global $wpdb;

        if ( 'league' === $competition->type ) {
            $mode       = 'default';
            $entry_type = 'team';
        } elseif ( 'cup' === $competition->type ) {
            $mode       = 'championship';
            $entry_type = 'team';
        } elseif ( 'tournament' === $competition->type ) {
            $mode       = 'championship';
            $entry_type = 'player';
        }
        if ( ! empty( $mode ) && ! empty( $entry_type ) ) {
            $settings = array(
                'mode'       => $mode,
                'entry_type' => $entry_type,
            );

            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "INSERT INTO $wpdb->racketmanager_competitions (`name`, `type`, `settings`, `age_group` ) VALUES (%s, %s, %s, %s)",
                    $competition->name,
                    $competition->type,
                    maybe_serialize( $settings ),
                    $competition->age_group,
                )
            );
            $competition->id = $wpdb->insert_id;
        }
    }

    /**
     * Delete Competition
     */
    public function delete(): void {
        global $wpdb;

        foreach ( $this->get_events() as $event ) {
            $event = get_event( $event->id );
            $event->delete();
        }
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_competitions_seasons WHERE `competition_id` = %d",
                $this->id
            )
        );
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_competitions WHERE `id` = %d",
                $this->id
            )
        );
    }

    /**
     * Set name
     *
     * @param string $name competition name.
     */
    public function set_name( string $name ): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_competitions SET `name` = %s WHERE `id` =%d",
                $name,
                $this->id
            )
        );
        $this->name = $name;
        wp_cache_set( $this->id, $this, 'competitions' );
    }

    /**
     * Update settings
     *
     * @param array $settings settings array.
     */
    public function set_settings( array $settings ): void {
        global $wpdb;
        foreach ( Util::get_standings_display_options() as $key => $value ) {
            $settings['standings'][ $key ] = isset( $settings['standings'][ $key ] ) ? 1 : 0;
        }
        $type = $settings['type'];

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_competitions SET `settings` = %s, `type` = %s WHERE `id` = %d",
                maybe_serialize( $settings ),
                $type,
                $this->id
            )
        );
        $this->settings = $settings;
        wp_cache_set( $this->id, $this, 'competitions' );
    }

    /**
     * Set current season
     *
     * @param string $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_season( string $season = '', bool $force_overwrite = false ): void {
        global $wp;
        if ( ! empty( $season ) && true === $force_overwrite ) {
            $data = $this->seasons[ $season ];
        } elseif ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            if ( ! isset( $this->seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $this->seasons[ $key ];
            }
        } elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            if ( ! isset( $this->seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $this->seasons[ $key ];
            }
        } elseif ( isset( $wp->query_vars['season'] ) ) {
            $key = $wp->query_vars['season'];
            if ( ! isset( $this->seasons[ $key ] ) ) {
                $data = false;
            } else {
                $data = $this->seasons[ $key ];
            }
        } elseif ( ! empty( $season ) ) {
            $data = $this->seasons[ $season ];
        } else {
            $data = null;
        }
        $today = gmdate( 'Y-m-d' );
        if ( ! isset( $data ) ) {
            foreach ( array_reverse( $this->seasons ) as $season ) {
                $date_active = empty( $season['date_closing'] ) ? null : Util::amend_date( $season['date_closing'], 7 );
                if ( ! empty( $date_active ) && $date_active <= $today ) {
                    $data = $season;
                    break;
                }
            }
        }
        if ( empty( $data ) ) {
            $data = end( $this->seasons );
        }
        $count_match_dates = isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0;
        $this->is_complete = false;
        if ( empty( $data['date_end'] ) && $count_match_dates >= 2 ) {
            $data['date_end']               = end( $data['match_dates'] );
            $this->seasons[ $data['name'] ] = $data;
        }
        if ( empty( $data['date_start'] ) && $count_match_dates >= 2 ) {
            $data['date_start']             = $data['match_dates'][0];
            $this->seasons[ $data['name'] ] = $data;
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
     * Get current season name
     *
     * @return string
     */
    public function get_season(): string {
        return stripslashes( $this->current_season['name'] );
    }
    /**
     * Gets number of events
     *
     * @param boolean $total should total be stored.
     */
    public function set_num_events( bool $total = false ): void {
        global $wpdb;

        if ( true === $total ) {
            $this->num_events = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT COUNT(ID) FROM $wpdb->racketmanager_events WHERE `competition_id` = %d",
                    $this->id
                )
            );
        }
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
            'orderby' => array( 'name' => 'ASC' ),
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $orderby  = $args['orderby'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`competition_id` = %d', $this->id );

        $search = Util::search_string( $search_terms, true );
        $order  = Util::order_by_string( $orderby );
        $sql    = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `name`, `id`, `settings`, `competition_id` FROM $wpdb->racketmanager_events $search $order LIMIT %d, %d",
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
            $event_index[ $event->id ] = $i;
            $event                     = get_event( $event->id );
            $events[ $i ]              = $event;
        }

        $this->events      = $events;
        $this->event_index = $event_index;

        return $events;
    }
    /**
     * Reload settings from database
     */
    public function reload_settings(): void {
        global $wpdb;

        wp_cache_delete( $this->id, 'competitions' );
        $result = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT `settings` FROM $wpdb->racketmanager_competitions WHERE `id` = %d",
                $this->id
            )
        );
        foreach ( maybe_unserialize( $result->settings ) as $key => $value ) {
            $this->$key = $value;
        }
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
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`status` AS `team_type`, e.`name` AS `event_name`';
        }
        $sql .= " FROM $wpdb->racketmanager_events e, $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_table t1 WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

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
     * Get clubs for competition
     *
     * @param array $args search arguments.
     *
     * @return array|int
     */
    public function get_clubs( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
            'club_id' => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];
        $club_id  = $args['club_id'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`competition_id` = %d', $this->id );
        if ( ! $season ) {
            $season = $this->current_season['name'];
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
        }

        if ( $status ) {
            $search_terms[] = $wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
        }
        if ( $name ) {
            $search_terms[] = $wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
        }
        if ( $club_id ) {
            $search_terms[] = $wpdb->prepare( 'c.`id` = %d', intval( $club_id ) );
        }

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT t2.`club_id`, count(t2.`id`) as `team_count`';
        }
        $sql .= " FROM $wpdb->racketmanager_events e,$wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_table t1, $wpdb->racketmanager_clubs c WHERE e.`id` = l.`event_id` AND t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`club_id` = c.`id`" . $search;

        if ( $count ) {
            return $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
        } else {
            $sql .= ' GROUP BY t2.`club_id`';
        }
        $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql . ' ORDER BY c.`name` ASC LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );

        $competition_clubs = wp_cache_get( md5( $sql ), 'competition_clubs' );
        if ( ! $competition_clubs ) {
            $competition_clubs = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $competition_clubs, 'competition_clubs' );
        }

        foreach ( $competition_clubs as $i => $competition_club ) {
            $team_count                     = $competition_club->team_count;
            $competition_club               = get_club( $competition_club->club_id );
            $competition_club->team_count   = $team_count;
            $competition_club->player_count = $this->get_players(
                array(
                    'season' => $season,
                    'count'  => true,
                    'club'   => $competition_club->id,
                )
            );
            $competition_clubs[ $i ]        = $competition_club;
        }

        $this->clubs = $competition_clubs;

        return $competition_clubs;
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
            $sql_fields = 'SELECT COUNT(*)';
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
        if ( $final ) {
            $search_terms[] = $wpdb->prepare( "`final` = %d", $final );
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . ':00:00';
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
            $confirmation_pending = intval( $confirmation_pending ) . ':00:00';
            $sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
        }
        if ( $result_pending ) {
            $result_pending = intval( $result_pending ) . ':00:00';
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
     * Get winners function
     *
     * @param boolean $group_by group by flag.
     *
     * @return false|array
     */
    public function get_winners( bool $group_by = false ): false|array {
        global $wpdb;

        if ( $this->is_league ) {
            $winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT l.`title` ,wt.`title` AS `winner` ,e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id` FROM $wpdb->racketmanager_table t, $wpdb->racketmanager l, $wpdb->racketmanager_teams wt, $wpdb->racketmanager_events e WHERE t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d AND t.rank = 1 AND t.team_id = wt.id order by e.`name`, l.`title`",
                    $this->id,
                    $this->current_season['name']
                )
            );
        } else {
            $winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, wt.`status` AS `team_type` FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_teams wt, $wpdb->racketmanager_teams lt, $wpdb->racketmanager_events e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by e.`name`, l.`title`",
                    $this->id,
                    $this->current_season['name']
                )
            );
        }

        if ( ! $winners ) {
            return false;
        }

        $return = array();
        foreach ( $winners as $winner ) {
            if ( ! $this->is_league ) {
                $match = get_match( $winner->id );
            }
            if ( $this->is_player_entry ) {
                if ( $winner->winner_id === $winner->home_team ) {
                    $winner_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
                } else {
                    $winner_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
                }
                if ( $winner->loser_id === $winner->home_team ) {
                    $loser_club = isset( $match->teams['home']->club ) ? $match->teams['home']->club->shortcode : null;
                } else {
                    $loser_club = isset( $match->teams['away']->club ) ? $match->teams['away']->club->shortcode : null;
                }
                $winner->winner_club = $winner_club;
                $winner->loser_club  = $loser_club;
            }
            $winner->league           = $winner->title;
            $winner->competition_name = $this->name;
            $winner->competition_type = $this->type;
            $winner->season           = $this->current_season['name'];
            $winner->is_team_entry    = $this->is_team_entry;
            if ( $group_by ) {
                $key = strtoupper( $winner->type );
                if ( false === array_key_exists( $key, $return ) ) {
                    $return[ $key ] = array();
                }
                // now just add the row data.
                $return[ $key ][] = $winner;
            } else {
                $return[] = $winner;
            }
        }
        return $return;
    }
    /**
     * Update seasons
     *
     * @param array $seasons season data.
     */
    public function update_seasons( array $seasons ): bool {
        global $wpdb;
        if ( $this->seasons !== $seasons ) {
            $this->seasons = $seasons;
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_competitions SET `seasons` = %s WHERE `id` = %d",
                    maybe_serialize( $seasons ),
                    $this->id
                )
            );
            wp_cache_set( $this->id, $this, 'competitions' );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Save plan
     *
     * @param int $season season.
     * @param array $courts number of courts available.
     * @param array $start_times start times of matches.
     * @param array $matches matches.
     * @param array $match_times match times.
     *
     * @return boolean updates performed
     */
    public function save_plan( int $season, array $courts, array $start_times, array $matches, array $match_times ): bool {
        global $racketmanager;
        $seasons     = $this->seasons;
        $season_dtls = $this->seasons[$season] ?? null;
        if ( $season_dtls ) {
            $order_of_play = array();
            $num_courts    = count( $courts );
            for ( $i = 0; $i < $num_courts; $i++ ) {
                $order_of_play[ $i ]['court']      = $courts[ $i ];
                $order_of_play[ $i ]['start_time'] = $start_times[ $i ];
                $order_of_play[ $i ]['matches']    = $matches[ $i ];
                $num_matches                       = count( $matches[ $i ] );
                for ( $m = 0; $m < $num_matches; $m++ ) {
                    $match_id = trim( $matches[ $i ][ $m ] );
                    if ( ! empty( $match_id ) ) {
                        $time  = strtotime( $start_times[ $i ] ) + $match_times[ $i ][ $m ];
                        $match = get_match( $match_id );
                        if ( $match ) {
                            $month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
                            $day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
                            $date     = $match->year . '-' . $month . '-' . $day . ' ' . gmdate( 'H:i', $time );
                            $location = $courts[ $i ];
                            if ( $date !== $match->date || $location !== $match->location ) {
                                $match->set_match_date_in_db( $date );
                                $match->set_location( $location );
                            }
                        }
                    }
                }
            }
            $curr_order_of_play = $season_dtls['orderofplay'] ?? null;
            if ( $order_of_play !== $curr_order_of_play ) {
                $season_dtls['orderofplay'] = $order_of_play;
                $seasons[ $season ]         = $season_dtls;
                $updates = $this->update_seasons( $seasons );
            } else {
                $updates = false;
            }
        } else {
            $updates = false;
        }
        return $updates;
    }
    /**
     * Update plan config
     *
     * @param int $season season.
     * @param string|null $start_time start time.
     * @param int|null $num_courts number of courts.
     * @param string|null $time_increment time increment for matches.
     *
     * @return boolean updates performed
     */
    public function update_plan( int $season, ?string $start_time, ?int $num_courts, ?string $time_increment ): bool {
        $update      = false;
        $seasons     = $this->seasons;
        $season_dtls = $this->seasons[$season] ?? null;
        if ( $season_dtls ) {
            $curr_start_time     = $season_dtls['starttime'] ?? null;
            $curr_num_courts     = $season_dtls['num_courts'] ?? null;
            $curr_time_increment = $season_dtls['time_increment'] ?? null;
            if ( $start_time !== $curr_start_time || $num_courts !== $curr_num_courts || $time_increment !== $curr_time_increment ) {
                $season_dtls['starttime']      = $start_time;
                $season_dtls['num_courts']     = $num_courts;
                $season_dtls['time_increment'] = $time_increment;
                $seasons[ $season ]            = $season_dtls;
                $this->update_seasons( $seasons );
                $update = true;
            }
        }
        return $update;
    }
    /**
     * Reset plan config
     *
     * @param int $season season.
     * @param array $matches matches.
     *
     * @return boolean updates performed
     */
    public function reset_plan( int $season, array $matches ): bool {
        global $racketmanager;
        $seasons     = $this->seasons;
        $season_dtls = $this->seasons[$season] ?? null;
        $updates     = false;
        if ( $season_dtls ) {
            if ( $matches ) {
                foreach ( $matches as $match_id ) {
                    $match = get_match( intval( $match_id ) );
                    if ( $match ) {
                        $month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
                        $day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
                        $date     = $match->year . '-' . $month . '-' . $day . ' 00:00';
                        $location = '';
                        if ( $date !== $match->date || $location !== $match->location ) {
                            $match->set_match_date_in_db( $date );
                            $match->set_location( $location );
                            $updates = true;
                        }
                    }
                }
            }
            if ( $updates ) {
                $season_dtls['orderofplay'] = array();
                $seasons[ $season ]         = $season_dtls;
                $this->update_seasons( $seasons );
            }
        }
        return $updates;
    }
    /**
     * Delete season function
     *
     * @param int $season season name.
     *
     * @return boolean
     */
    public function delete_season( int $season ): bool {
        if ( isset( $this->seasons[ $season ] ) ) {
            $seasons = $this->seasons;
            foreach ( $this->get_events() as $event ) {
                $event->delete_season( $season );
            }
            unset( $seasons[ $season ] );
            $this->update_seasons( $seasons );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Add season
     *
     * @param object $season season data.
     */
    public function add_season( object $season ): bool {
        global $racketmanager;
        $updates                  = false;
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
     * Set configuration function
     *
     * @param object $config config object.
     *
     * @return boolean update indicator.
     */
    public function set_config( object $config ): bool {
        $updates = false;
        $settings = new stdClass();
        if ( empty( $this->sport ) || $this->sport !== $config->sport ) {
            $updates = true;
        }
        $settings->sport = $config->sport;
        if ( $this->type !== $config->type ) {
            $settings->type = $config->type;
            switch ( $config->type ) {
                case 'league':
                    $config->mode = 'default';
                    $updates      = true;
                    break;
                case 'cup':
                    $config->mode       = 'championship';
                    $config->entry_type = 'team';
                    $updates            = true;
                    break;
                case 'tournament':
                    $config->mode       = 'championship';
                    $config->entry_type = 'player';
                    $updates            = true;
                    break;
                default:
                    break;
            }
        }
        if ( empty( $this->entry_type ) || $this->entry_type !== $config->entry_type ) {
            $updates = true;
        }
        $settings->entry_type = $config->entry_type;
        if ( empty( $this->mode ) || $this->mode !== $config->mode ) {
            $updates = true;
        }
        $settings->mode = $config->mode;
        if ( empty( $this->competition_code ) || $this->competition_code !== $config->competition_code ) {
            $updates = true;
        }
        $settings->competition_code = $config->competition_code;
        if ( empty( $this->grade ) || $this->grade !== $config->grade ) {
            $updates = true;
        }
        $settings->grade = $config->grade;
        if ( empty( $this->age_group ) || $this->age_group !== $config->age_group ) {
            $updates = true;
        }
        $this->age_group = $config->age_group;
        if ( 'league' === $config->type ) {
            if ( empty( $this->max_teams ) || $this->max_teams !== $config->max_teams ) {
                $updates = true;
            }
            $settings->max_teams = $config->max_teams;
            if ( empty( $this->teams_per_club ) || $this->teams_per_club !== $config->teams_per_club ) {
                $updates = true;
            }
            $settings->teams_per_club = $config->teams_per_club;
            if ( empty( $this->teams_prom_relg ) || $this->teams_prom_relg !== $config->teams_prom_relg ) {
                $updates = true;
            }
            $settings->teams_prom_relg = $config->teams_prom_relg;
            if ( empty( $this->lowest_promotion ) || $this->lowest_promotion !== $config->lowest_promotion ) {
                $updates = true;
            }
            $settings->lowest_promotion = $config->lowest_promotion;
        } elseif ( 'tournament' === $config->type ) {
            if ( empty( $this->num_entries ) || $this->num_entries !== $config->num_entries ) {
                $updates = true;
            }
            $settings->num_entries = $config->num_entries;
        }
        if ( empty( $this->team_ranking ) || $this->team_ranking !== $config->team_ranking ) {
            $updates = true;
        }
        $settings->team_ranking = $config->team_ranking;
        if ( empty( $this->point_rule ) || $this->point_rule !== $config->point_rule ) {
            $updates = true;
        }
        $settings->point_rule = $config->point_rule;
        if ( empty( $this->scoring ) || $this->scoring !== $config->scoring ) {
            $updates = true;
        }
        $settings->scoring = $config->scoring;
        if ( empty( $this->num_sets ) || $this->num_sets !== $config->num_sets ) {
            $updates = true;
        }
        $settings->num_sets = $config->num_sets;
        if ( $this->is_team_entry ) {
            if ( empty( $this->num_rubbers ) || $this->num_rubbers !== $config->num_rubbers ) {
                $updates = true;
            }
            $settings->num_rubbers = $config->num_rubbers;
            if ( ! isset( $this->reverse_rubbers ) || $this->reverse_rubbers !== $config->reverse_rubbers ) {
                $updates = true;
            }
            $settings->reverse_rubbers = $config->reverse_rubbers;
        }
        if ( ! isset( $this->fixed_match_dates ) || $this->fixed_match_dates !== $config->fixed_match_dates ) {
            $updates = true;
        }
        $settings->fixed_match_dates = $config->fixed_match_dates;
        if ( ! isset( $this->home_away ) || $this->home_away !== $config->home_away ) {
            $updates = true;
        }
        $settings->home_away = $config->home_away;
        if ( ! isset( $this->round_length ) || $this->round_length !== $config->round_length ) {
            $updates = true;
        }
        $settings->round_length = $config->round_length;
        if ( 'league' === $config->type || 'cup' === $config->type ) {
            if ( ! isset( $this->home_away_diff ) || $this->home_away_diff !== $config->home_away_diff ) {
                $updates = true;
            }
            $settings->home_away_diff = $config->home_away_diff;
        }
        if ( 'league' === $config->type ) {
            if ( empty( $this->filler_weeks ) || $this->filler_weeks !== $config->filler_weeks ) {
                $updates = true;
            }
            $settings->filler_weeks = $config->filler_weeks;
        }
        if ( 'tournament' !== $config->type ) {
            $match_days = Util::get_match_days();
            foreach ( $match_days as $match_day => $value ) {
                $config->match_days_allowed[ $match_day ] = isset( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
                if ( ! isset( $this->match_days_allowed[ $match_day ] ) || $this->match_days_allowed[ $match_day ] !== $config->match_days_allowed[ $match_day ] ) {
                    $updates = true;
                }
            }
            $settings->match_days_allowed = $config->match_days_allowed;
            if ( ! isset( $this->match_day_restriction ) || $this->match_day_restriction !== $config->match_day_restriction ) {
                $updates = true;
            }
            $settings->match_day_restriction = $config->match_day_restriction;
            if ( ! isset( $this->match_day_weekends ) || $this->match_day_weekends !== $config->match_day_weekends ) {
                $updates = true;
            }
            $settings->match_day_weekends     = $config->match_day_weekends;
            $default_match_start_time         = explode( ':', $config->default_match_start_time );
            $default_match_start_time_hour    = $default_match_start_time[0];
            $default_match_start_time_minutes = $default_match_start_time[1];
            if ( empty( $this->default_match_start_time['hour'] ) || $this->default_match_start_time['hour'] !== $default_match_start_time_hour ) {
                $updates = true;
            }
            $settings->default_match_start_time['hour'] = $default_match_start_time_hour;
            if ( empty( $this->default_match_start_time['minutes'] ) || $this->default_match_start_time['minutes'] !== $default_match_start_time_minutes ) {
                $updates = true;
            }
            $settings->default_match_start_time['minutes'] = $default_match_start_time_minutes;
            if ( ! isset( $this->start_time['weekday']['min'] ) || $this->start_time['weekday']['min'] !== $config->start_time['weekday']['min'] ) {
                $updates = true;
            }
            $settings->start_time['weekday']['min'] = $config->start_time['weekday']['min'];
            if ( ! isset( $this->start_time['weekday']['max'] ) || $this->start_time['weekday']['max'] !== $config->start_time['weekday']['max'] ) {
                $updates = true;
            }
            $settings->start_time['weekday']['max'] = $config->start_time['weekday']['max'];
            if ( ! isset( $this->start_time['weekend']['min'] ) || $this->start_time['weekend']['min'] !== $config->start_time['weekend']['min'] ) {
                $updates = true;
            }
            $settings->start_time['weekend']['min'] = $config->start_time['weekend']['min'];
            if ( ! isset( $this->start_time['weekend']['max'] ) || $this->start_time['weekend']['max'] !== $config->start_time['weekend']['max'] ) {
                $updates = true;
            }
            $settings->start_time['weekend']['max'] = $config->start_time['weekend']['max'];
        }
        if ( empty( $this->point_format ) || $this->point_format !== $config->point_format ) {
            $updates = true;
        }
        $settings->point_format = $config->point_format;
        if ( empty( $this->point_2_format ) || $this->point_2_format !== $config->point_2_format ) {
            $updates = true;
        }
        $settings->point_2_format = $config->point_2_format;
        if ( empty( $this->num_matches_per_page ) || $this->num_matches_per_page !== $config->num_matches_per_page ) {
            $updates = true;
        }
        $settings->num_matches_per_page = $config->num_matches_per_page;
        $standing_display_options       = Util::get_standings_display_options();
        foreach ( $standing_display_options as $display_option => $value ) {
            $config->standings[ $display_option ] = isset( $config->standings[ $display_option ] ) ? 1 : 0;
            if ( $this->standings[ $display_option ] !== $config->standings[ $display_option ] ) {
                $updates = true;
            }
        }
        $settings->standings = $config->standings;
        $rules_options       = $this->get_rules_options();
        foreach ( $rules_options as $rules_option => $value ) {
            $config->rules[ $rules_option ] = isset( $config->rules[ $rules_option ] ) ? 1 : 0;
            if ( ! isset( $this->rules[ $rules_option ] ) || $this->rules[ $rules_option ] !== $config->rules[ $rules_option ] ) {
                $updates = true;
            }
        }
        $settings->rules = $config->rules;
        if ( 'league' === $config->type ) {
            if ( empty( $this->num_courts_available ) || $this->num_courts_available !== $config->num_courts_available ) {
                $updates = true;
            }
            $settings->num_courts_available = $config->num_courts_available;
        }
        if ( $this->name !== $config->name || $updates ) {
            $this->name     = $config->name;
            $this->settings = (array) $settings;
            $updates        = true;
            $this->update_settings();
        }
        return $updates;
    }
    /**
     * Update settings function
     */
    private function update_settings(): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_competitions SET `name` = %s, `type` = %s, `settings` = %s, `age_group` = %s WHERE `id` = %d",
                $this->name,
                $this->type,
                maybe_serialize( $this->settings ),
                $this->age_group,
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'competitions' );
    }
    /**
     * Notify team entry open
     *
     * @param int $season season name.
     *
     * @return object
     */
    public function notify_team_entry_open( int $season ): object {
        global $racketmanager;
        $msg             = null;
        $return          = new stdClass();
        $is_championship = null;
        if ( isset( $this->seasons[ $season ] ) ) {
            $season_dtls = (object) $this->seasons[ $season ];
            if ( $this->is_league ) {
                $events = $this->get_events();
                foreach ( $events as $event ) {
                    if ( empty( $event->get_leagues() ) ) {
                        $return->error = true;
                        $msg[]         = __( 'No leagues found for event', 'racketmanager' ) . ' ' . $event->name;
                    } elseif ( count( $event->seasons ) > 1 ) {
                        $constitution = $event->get_constitution(
                            array(
                                'season' => $season,
                                'count'  => true,
                            )
                        );
                        if ( ! $constitution ) {
                            $return->error = true;
                            $msg[]         = __( 'Constitution not set', 'racketmanager' ) . ' ' . $event->name;
                        }
                    }
                }
                $is_championship         = false;
                $season_dtls->venue_name = null;
            } elseif ( $this->is_cup ) {
                $is_championship         = true;
                $season_dtls->venue_name = null;
                if ( ! empty( $season_dtls->venue ) ) {
                    $venue_club = get_club( $season_dtls->venue );
                    if ( $venue_club ) {
                        $season_dtls->venue_name = $venue_club->shortcode;
                    }
                }
            } else {
                $return->error = true;
                $return->msg   = __( 'Competition type not valid', 'racketmanager' );
            }
            if ( empty( $return->error ) ) {
                $url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '/' . $season . '/';
                $competition_name = $this->name . ' ' . $season;
                $clubs            = $racketmanager->get_clubs(
                    array(
                        'type' => 'affiliated',
                    )
                );
                $headers          = array();
                $from_email       = $racketmanager->get_confirmation_email( $this->type );
                if ( $from_email ) {
                    $headers[]         = RACKETMANAGER_FROM_EMAIL . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
                    $headers[]         = RACKETMANAGER_CC_EMAIL . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
                    $organisation_name = $racketmanager->site_name;
                    $messages_sent     = 0;
                    foreach ( $clubs as $club ) {
                        $email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
                        $email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
                        $action_url    = $url . seo_url( $club->shortcode ) . '/';
                        $email_message = $racketmanager->shortcodes->load_template(
                            'competition-entry-open',
                            array(
                                'email_subject'   => $email_subject,
                                'from_email'      => $from_email,
                                'action_url'      => $action_url,
                                'organisation'    => $organisation_name,
                                'is_championship' => $is_championship,
                                'competition'     => $competition_name,
                                'addressee'       => $club->match_secretary_name,
                                'season_dtls'     => $season_dtls,
                            ),
                            'email'
                        );
                        wp_mail( $email_to, $email_subject, $email_message, $headers );
                        ++$messages_sent;
                    }
                    if ( $messages_sent ) {
                        /* translation: %d number of messages sent */
                        $return->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
                    } else {
                        $return->error = true;
                        $msg[]         = __( 'No notification', 'racketmanager' );
                    }
                } else {
                    $return->error = true;
                    $msg[]         = __( 'No secretary email', 'racketmanager' );
                }
            }
        } else {
            $return->error = true;
            $msg[]         = __( 'Competition season not found', 'racketmanager' );
        }
        if ( ! empty( $return->error ) ) {
            $return->msg = __( 'Notification error', 'racketmanager' );
            foreach ( $msg as $error ) {
                $return->msg .= '<br>' . $error;
            }
        }
        return $return;
    }
    /**
     * Notify team entry reminder
     *
     * @param int $season season name.
     *
     * @return object
     */
    public function notify_team_entry_reminder( int $season ): object {
        global $racketmanager;
        $msg           = null;
        $return        = new stdClass();
        $messages_sent = 0;
        if ( isset( $this->seasons[ $season ] ) ) {
            $clubs = $racketmanager->get_clubs(
                array(
                    'type' => 'affiliated',
                )
            );
            foreach ( $clubs as $c => $club ) {
                $entry_found = $this->get_clubs(
                    array(
                        'club_id' => $club->id,
                        'count'   => true,
                        'season'  => $season,
                        'status'  => 1,
                    )
                );
                if ( $entry_found ) {
                    unset( $clubs[ $c ] );
                }
            }
            if ( $clubs ) {
                $season_dtls             = (object) $this->seasons[ $season ];
                $season_dtls->venue_name = null;
                if ( $this->is_league ) {
                    $is_championship = false;
                } else {
                    $is_championship = true;
                    if ( ! empty( $season_dtls->venue ) ) {
                        $venue_club = get_club( $season_dtls->venue );
                        if ( $venue_club ) {
                            $season_dtls->venue_name = $venue_club->shortcode;
                        }
                    }
                }
                $date_closing     = date_create( $season_dtls->date_closing );
                $now              = date_create();
                $remaining_time   = date_diff( $date_closing, $now, true );
                $days_remaining   = $remaining_time->days;
                $url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '/' . $season . '/';
                $competition_name = $this->name . ' ' . $season;
                $headers          = array();
                $from_email       = $racketmanager->get_confirmation_email( $this->type );
                if ( $from_email ) {
                    $headers[]         = RACKETMANAGER_FROM_EMAIL . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
                    $headers[]         = RACKETMANAGER_CC_EMAIL . ucfirst( $this->type ) . 'Secretary <' . $from_email . '>';
                    $organisation_name = $racketmanager->site_name;
                    foreach ( $clubs as $club ) {
                        $email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entries Closing Soon', 'racketmanager' ) . ' - ' . $club->name;
                        $email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
                        $action_url    = $url . seo_url( $club->shortcode ) . '/';
                        $email_message = $racketmanager->shortcodes->load_template(
                            'competition-entry-open',
                            array(
                                'email_subject'   => $email_subject,
                                'from_email'      => $from_email,
                                'action_url'      => $action_url,
                                'organisation'    => $organisation_name,
                                'is_championship' => $is_championship,
                                'competition'     => $competition_name,
                                'addressee'       => $club->match_secretary_name,
                                'season_dtls'     => $season_dtls,
                                'days_remaining'  => $days_remaining,
                            ),
                            'email'
                        );
                        wp_mail( $email_to, $email_subject, $email_message, $headers );
                        ++$messages_sent;
                    }
                    if ( $messages_sent ) {
                        /* translation: %d number of messages sent */
                        $return->msg = sprintf( __( '%d match secretaries notified', 'racketmanager' ), $messages_sent );
                    } else {
                        $return->error = true;
                        $msg[]         = __( 'No notification', 'racketmanager' );
                    }
                } else {
                    $return->error = true;
                    $msg[]         = __( 'No secretary email', 'racketmanager' );
                }
            } else {
                $return->error = true;
                $msg[]         = __( 'No clubs with outstanding entries', 'racketmanager' );
            }
        } else {
            $return->error = true;
            $msg[]         = __( 'Competition season not found', 'racketmanager' );
        }
        if ( ! empty( $return->error ) ) {
            $return->msg = __( 'Notification error', 'racketmanager' );
            foreach ( $msg as $error ) {
                $return->msg .= '<br>' . $error;
            }
        }
        return $return;
    }

    /**
     * Contact Competition Teams
     *
     * @param string $season season.
     * @param string $email_message message.
     *
     * @return boolean
     */
    private function contact_teams( string $season, string $email_message ): bool {
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
                    if ( ! empty( $team_dtls->club->match_secretary_email ) ) {
                        $headers[] = RACKETMANAGER_BCC_EMAIL . ucwords( $team_dtls->club->match_secretary_name ) . ' <' . $team_dtls->club->match_secretary_email . '>';
                    }
                }
            }
        }
        wp_mail( $email_to, $email_subject, $email_message, $headers );
        return true;
    }
    /**
     * Get rules options
     *
     * @return array of rules options.
     */
    public function get_rules_options(): array {
        global $racketmanager;
        $rules_options    = $racketmanager->get_options( 'checks' );
        $result_options   = $racketmanager->get_options( $this->type );
        if ( isset( $result_options['resultTimeout'] ) ) {
            $rules_options['resultTimeout'] = $result_options['resultTimeout'];
        }
        if ( isset( $result_options['confirmationTimeout'] ) ) {
            $rules_options['confirmationTimeout'] = $result_options['confirmationTimeout'];
        }
        return $rules_options;
    }
    /**
     * Calculate team ratings
     *
     * @param int|null $season season name.
     *
     * @return void
     */
    public function calculate_team_ratings( ?int $season ): void {
        global $racketmanager;
        if ( $season && isset( $this->seasons[ $season ] ) ) {
            $teams = $this->get_teams( array( 'season' => $season ) );
            foreach ( $teams as $team ) {
                $team_points = 0;
                // set league ratings.
                $prev_season      = $season - 1;
                $league_standings = $racketmanager->get_league_standings(
                    array(
                        'season'    => $prev_season,
                        'team'      => $team->team_id,
                        'age_group' => $this->age_group,
                    )
                );
                if ( $league_standings ) {
                    foreach ( $league_standings as $league_standing ) {
                        $points       = 0;
                        $league       = get_league( $league_standing->id );
                        $league_title = explode( ' ', $league->title );
                        $league_no    = end( $league_title );
                        if ( ! $league->event->competition->is_league ) {
                            $position = 0;
                        } elseif ( is_numeric( $league_no ) ) {
                            $teams_per_league = $league->event->competition->max_teams ?? 10;
                            $position         = ( $league_no * $teams_per_league ) + $league_standing->rank;
                        } else {
                            $position = $league_standing->rank;
                        }
                        if ( isset( $league->event->age_limit ) ) {
                            if ( 'open' === $league->event->age_limit ) {
                                $event_points = 1;
                            } elseif ( $league->event->age_limit >= 30 ) {
                                $event_points = 0.25;
                            } elseif ( 16 === $league->event->age_limit ) {
                                $event_points = 0.4;
                            } elseif ( 14 === $league->event->age_limit ) {
                                $event_points = 0.25;
                            } elseif ( 12 === $league->event->age_limit ) {
                                $event_points = 0.15;
                            } else {
                                $event_points = 1;
                            }
                        } else {
                            $event_points = 1;
                        }
                        $position_points = array( 300, 240, 192, 180, 160, 140, 128, 120, 116, 112, 108, 104, 100, 96, 88, 80, 76, 72, 68, 64, 60, 65, 52, 48, 44, 40, 36, 32, 28, 24, 20 );
                        $base_points     = $position_points[$position - 1] ?? 0;
                        if ( ! empty( $base_points ) ) {
                            $points = ceil( $base_points * $event_points );
                        }
                        $base_points_won = 42;
                        $points_div      = ( $league_no - 1 ) * ( $league->event->num_rubbers * 2 );
                        $points_won      = ( $base_points_won - round( $points_div * $event_points ) ) * $league_standing->won_matches;
                        $points         += $points_won;
                        $team_points    += $points;
                    }
                }
                // set cup rating.
                $matches = $this->get_matches(
                    array(
                        'team'     => $team->team_id,
                        'final'    => 'all',
                        'time'     => 365,
                        'complete' => true,
                    )
                );
                foreach ( $matches as $match ) {
                    $points       = Util::calculate_championship_rating( $match, $team->team_id );
                    $team_points += $points;
                }
                $league_team = get_league_team( $team->table_id );
                $league_team?->set_rating($team_points);
            }
        }
    }
}
