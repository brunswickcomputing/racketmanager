<?php
/**
 * Event API: Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Event
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\constitution_notification;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_team;

/**
 * Class to implement the Event object
 */
class Event {
    /**
     * Event ID
     *
     * @var int
     */
    public int $id;

    /**
     * Event name
     *
     * @var string
     */
    public string $name;

    /**
     * Seasons data
     *
     * @var string|array|null
     */
    public string|array|null $seasons = array();

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
     * Standings table layout settings
     *
     * @var array
     */
    public array $standings = array();

    /**
     * Number of teams ascending
     *
     * @var int
     */
    public int $num_ascend = 0;

    /**
     * Number of teams descending
     *
     * @var int
     */
    public int $num_descend = 0;

    /**
     * Number of teams for relegation
     *
     * @var int
     */
    public int $num_relegation = 0;

    /**
     * Number of teams per page in list
     *
     * @var int
     */
    public int $num_matches_per_page = 10;

    /**
     * League offsets indexed by ID
     *
     * @var array
     */
    public array $league_index = array();

    /**
     * League loop
     *
     * @var boolean
     */
    public bool $in_the_league_loop = false;

    /**
     * Current league
     *
     * @var int
     */
    public int $league_team = -1;

    /**
     * Custom team input field keys and translated labels
     *
     * @var array
     */
    public array $fields_team = array();

    /**
     * Championship flag
     *
     * @var boolean
     */
    public bool $is_championship = false;
    /**
     * Box flag
     *
     * @var boolean
     */
    public bool $is_box = false;
    /**
     * Num_rubbers
     *
     * @var int|null
     */
    public ?int $num_rubbers = null;

    /**
     * Num_sets
     *
     * @var int|null
     */
    public ?int $num_sets = null;

    /**
     * Type
     *
     * @var string
     */
    public string $type = '';

    /**
     * Current season
     *
     * @var string|array
     */
    public string|array $current_season = '';

    /**
     * Number of match days
     *
     * @var string|int
     */
    public string|int $num_match_days = '';

    /**
     * Number of leagues
     *
     * @var string|int
     */
    public string|int $num_leagues = '';

    /**
     * Leagues
     *
     * @var array|string
     */
    public array|string $leagues = array();

    /**
     * Settings keys
     *
     * @var string|array|null
     */
    public string|array|null $settings_keys = '';

    /**
     * Constitutions
     *
     * @var string|array
     */
    public string|array $constitutions = '';

    /**
     * Event Teams
     *
     * @var string|array
     */
    public string|array $event_teams = '';
    /**
     * Settings
     *
     * @var string|array
     */
    public string|array $settings = array();
    /**
     * Groups
     *
     * @var string|null
     */
    public ?string $groups = null;
    /**
     * Teams per group
     *
     * @var int|null
     */
    public ?int $teams_per_group = null;
    /**
     * Number to advance
     *
     * @var int|null
     */
    public ?int $num_advance = null;
    /**
     * Match place 3
     *
     * @var boolean
     */
    public bool $match_place3;
    /**
     * Entry open
     *
     * @var boolean
     */
    public bool $entry_open;
    /**
     * Entry type
     *
     * @var string
     */
    public string $entry_type;
    /**
     * Clubs
     *
     * @var array
     */
    public array $clubs;
    /**
     * Players
     *
     * @var array
     */
    public array $players;
    /**
     * Competition
     *
     * @var null|object
     */
    public null|object $competition;
    /**
     * Competition id
     *
     * @var int
     */
    public int $competition_id;
    /**
     * Age limit
     *
     * @var string|null
     */
    public ?string $age_limit = null;
    /**
     * Age offset
     *
     * @var string|null
     */
    public ?string $age_offset = null;
    /**
     * Reverse rubbers
     *
     * @var string|boolean|null
     */
    public string|bool|null $reverse_rubbers = false;
    /**
     * Scoring
     *
     * @var string
     */
    public string $scoring;
    /**
     * Offset
     *
     * @var int
     */
    public int $offset = 0;
    /**
     * Match days allowed
     *
     * @var array
     */
    public array $match_days_allowed;
    /**
     * Entries
     *
     * @var array
     */
    public array $entries;
    /**
     * Num entries
     *
     * @var int
     */
    public int $num_entries;
    /**
     * Teams
     *
     * @var array
     */
    public array $teams;
    /**
     * Player
     *
     * @var object
     */
    public object $player;
    /**
     * Primary league
     *
     * @var int
     */
    public int $primary_league = 0;
    /**
     * Team count
     *
     * @var int
     */
    public int $team_count;
    /**
     * Player count
     *
     * @var int
     */
    public int $player_count;
    /**
     * Number of seeds
     *
     * @var int
     */
    public int $num_seeds;
    /**
     * Team
     *
     * @var object
     */
    public object $team;
    /**
     * Team ranking
     *
     * @var string
     */
    public string $team_ranking;
    /**
     * Competition type
     *
     * @var string
     */
    public string $competition_type;
    /**
     * Number of courts available
     *
     * @var array
     */
    public array $num_courts_available;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /** @var array<string, mixed> Storage for non-table data */
    private array $meta = [];

    /**
     * Config
     *
     * @var object
     */
    public object $config;
    /**
     * Retrieve event instance
     *
     * @param int|string $event_id event id.
     * @param string $search_term search.
     */
    public static function get_instance(int|string $event_id, string $search_term = 'id' ) {
        if ( ! $event_id ) {
            return false;
        }

        $event = wp_cache_get( $event_id, 'events' );
        if ( ! $event ) {
            global $racketmanager;
            $event_repository = isset( $racketmanager->container ) && $racketmanager->container->has( 'event_repository' )
                ? $racketmanager->container->get( 'event_repository' )
                : new Event_Repository();

            if ( 'name' === $search_term ) {
                $event = $event_repository->find_by_name( (string) $event_id );
            } else {
                $event = $event_repository->find_by_id( $event_id );
            }
            if ( ! $event ) {
                return false;
            }
        }

        return $event;
    }

    /**
     * Constructor
     *
     * @param object $event Event object.
     */
    public function __construct( object $event ) {
        if ( ! isset( $event->id ) ) {
            $this->add( $event );
        }
        if ( isset( $event->settings ) ) {
            $event->settings      = (array) maybe_unserialize( $event->settings );
            $event->settings_keys = array_keys($event->settings);
            $event                = (object) array_merge( (array) $event, $event->settings );
        } else {
            $event->settings = array();
        }

        foreach ( get_object_vars( $event ) as $key => $value ) {
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key, $value );
            } else {
                if ( empty( $value ) ) {
                    $type = gettype( $this->$key );
                    switch( $type ) {
                        case 'integer':
                            $value = 0;
                            break;
                        case 'NULL':
                        case 'string':
                            $value = null;
                            break;
                        default:
                            break;
                    }
                }
                $this->$key = $value;
            }
        }

        $this->name        = stripslashes( $this->name );
        $this->type        = empty( $this->type ) ? '' : stripslashes( $this->type );
        global $racketmanager;
        $competition_service = $racketmanager->container->get( 'competition_service' );
        $this->competition = $competition_service->get_by_id( $this->competition_id );
        if ( ! isset( $this->reverse_rubbers ) ) {
            $this->reverse_rubbers = '0';
        }

        // Seasons handling (Option B for Event): keep property as JSON string; do not reindex or mutate stored structure
        if ( empty( $this->seasons ) ) {
            $this->seasons = '[]';
        } elseif ( is_array( $this->seasons ) ) {
            // Normalize arrays to JSON string
            $this->seasons = wp_json_encode( $this->seasons );
        } elseif ( is_string( $this->seasons ) ) {
            // If the string looks serialized, try to unserialize and convert to JSON
            $maybe = @maybe_unserialize( $this->seasons );
            if ( is_array( $maybe ) ) {
                $this->seasons = wp_json_encode( $maybe );
            }
        }
        $this->num_seasons = count( $this->get_seasons() );
        $this->set_num_leagues( true );
        $this->standings = $this->competition->standings;
        // set season to latest.
        if ( $this->num_seasons > 0 ) {
            $season = empty( $this->competition->current_season['name'] ) ? null : $this->competition->current_season['name'];
            $this->set_season( $season );
        }

        // Championship.
        if ( 'championship' === $this->competition->settings->get('mode') ) {
            $this->is_championship = true;
        }
        if ( 'league' === $this->competition->type && $this->competition->is_player_entry ) {
            $this->is_box = true;
        }
    }

    /**
     * Get event id
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get event settings
     *
     * @return array
     */
    public function get_settings(): array {
        return $this->settings;
    }

    /**
     * Get event seasons
     *
     * @return array
     */
    public function get_seasons(): array {
        // Lazily decode JSON string; return empty array on failure
        if ( is_string( $this->seasons ) ) {
            $decoded = json_decode( $this->seasons, true );
            return ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) ? $decoded : array();
        }
        return is_array( $this->seasons ) ? $this->seasons : array();
    }

    /**
     * Get seasons as JSON string
     */
    public function get_seasons_json(): string {
        if ( is_string( $this->seasons ) ) {
            return $this->seasons;
        }
        return wp_json_encode( $this->get_seasons() );
    }

    /**
     * Get a season by name (searches by season['name'])
     */
    public function get_season_by_name( string $name ): ?array {
        $seasons = $this->get_seasons();
        return $seasons[ $name ] ?? null;
    }

    /**
     * Get event competition id
     *
     * @return int
     */
    public function get_competition_id(): int {
        return $this->competition_id;
    }

    /**
     * Get event type
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get event num sets
     *
     * @return int
     */
    public function get_num_sets(): int {
        return $this->num_sets;
    }

    /**
     * Get event num rubbers
     *
     * @return int
     */
    public function get_num_rubbers(): ?int {
        return $this->num_rubbers;
    }

    /**
     * Set id
     *
     * @param int $id
     *
     * @return void
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function set_seasons( array $seasons ): void {
        $this->seasons = $seasons;
    }
    /**
     * Add new event
     *
     * @param object $event event object.
     */
    private function add(object $event ): void {
        $event->id = $this->get_event_repository()->insert_event( $event );
    }

    /**
     * Delete Event
     */
    public function delete(): void {
        foreach ( $this->get_leagues() as $league ) {
            $league = get_league( $league->id );
            $league->delete();
        }
        $this->get_event_repository()->delete( $this->id );
    }

    /**
     * Set a transient metadata property.
     */
    public function set_meta( string $key, mixed $value ): void {
        $this->meta[ $key ] = $value;
    }

    /**
     * Retrieve a transient metadata property.
     */
    public function get_meta( string $key, mixed $default = null ): mixed {
        return $this->meta[ $key ] ?? $default;
    }

    /**
     * Set name
     *
     * @param string $name event name.
     */
    public function set_name(string $name ): void {
        $this->get_event_repository()->update_name( $this->id, $name );
        $this->name = $name;
        wp_cache_set( $this->id, $this, 'events' );
    }

    /**
     * Update settings
     *
     * @param array $settings settings array.
     */
    public function set_settings(array $settings ): void {
        global $racketmanager;
        $num_rubbers = $this->num_rubbers ?? null;
        $num_sets    = $this->num_sets ?? null;
        $type        = $this->type;
        if ( isset( $settings['reverse_rubbers'] ) && '1' === $settings['reverse_rubbers'] ) {
            $match_args             = array();
            $match_args['season']   = $this->current_season['name'];
            $match_args['event_id'] = $this->id;
            if ( ! isset( $this->settings['reverse_rubbers'] ) || $this->settings['reverse_rubbers'] !== $settings['reverse_rubbers'] ) {
                $matches            = $racketmanager->get_matches( $match_args );
                $fixture_repository = isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_repository' )
                    ? $racketmanager->container->get( 'fixture_repository' )
                    : new Fixture_Repository();
                $rubber_repository  = isset( $racketmanager->container ) && $racketmanager->container->has( 'rubber_repository' )
                    ? $racketmanager->container->get( 'rubber_repository' )
                    : new Rubber_Repository();
                $league_repository  = isset( $racketmanager->container ) && $racketmanager->container->has( 'league_repository' )
                    ? $racketmanager->container->get( 'league_repository' )
                    : new League_Repository();

                foreach ( $matches as $match_row ) {
                    $match_id = is_object( $match_row ) ? (int) $match_row->id : (int) $match_row;
                    $fixture  = $fixture_repository->find_by_id( $match_id );
                    if ( ! $fixture ) {
                        continue;
                    }
                    $rubber_count = $rubber_repository->count_by_fixture_id( $match_id );
                    $league_id    = $fixture->get_league_id();
                    $league       = $league_id ? $league_repository->find_by_id( $league_id ) : null;
                    $num_rubbers_threshold = $league ? (int) ( $league->num_rubbers ?? 0 ) : 0;
                    $total_rubbers = $rubber_count * 2;
                    if ( $num_rubbers_threshold > 0 && intval( $rubber_count ) === $num_rubbers_threshold ) {
                        for ( $ix = $rubber_count + 1; $ix <= $total_rubbers; $ix++ ) {
                            $rubber_data                = new stdClass();
                            $rubber_data->type          = $this->type;
                            $rubber_data->rubber_number = $ix;
                            $rubber_data->date          = $fixture->get_date();
                            $rubber_data->match_id      = $fixture->get_id();
                            $rubber_entity              = new Rubber( $rubber_data );
                            $rubber_repository->save( $rubber_entity );
                        }
                    }
                }
            }
        }

        $this->get_event_repository()->update_settings_and_rules( $this->id, $settings, $num_rubbers, $num_sets, $type );
    }

    /**
     * Set current season
     *
     * @param string|null $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_season( ?string $season = null, bool $force_overwrite = false ): void {
        global $wp;
        $seasons_list = $this->get_seasons();
        // Build a local index by name for lookup without mutating storage
        $by_name = array();
        foreach ( $seasons_list as $s ) {
            if ( isset( $s['name'] ) ) {
                $by_name[ $s['name'] ] = $s;
            }
        }
        if ( ! empty( $season ) && true === $force_overwrite ) {
            $data = $by_name[ $season ] ?? null;
        } elseif ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            if ( ! isset( $by_name[ $key ] ) ) {
                $data = false;
            } else {
                $data = $by_name[ $key ];
            }
        } elseif ( ! empty( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            if ( ! isset( $by_name[ $key ] ) ) {
                $data = false;
            } else {
                $data = $by_name[ $key ];
            }
        } elseif ( isset( $wp->query_vars['season'] ) ) {
            $key = $wp->query_vars['season'];
            if ( ! isset( $by_name[ $key ] ) ) {
                $data = false;
            } else {
                $data = $by_name[ $key ];
            }
        } elseif ( ! empty( $season ) ) {
            $data = $by_name[ $season ] ?? null;
        } else {
            $data = null;
        }
        if ( ! isset( $data ) ) {
            $tmp = $seasons_list;
            $data                 = ! empty( $tmp ) ? end( $tmp ) : null;
            $this->num_match_days = $data['num_match_days'] ?? 0;
        }

        $this->current_season = $data;
    }

    /**
     * Get current season name
     *
     * @return ?string
     */
    public function get_season(): ?string {
        if ( empty( $this->current_season['name'] ) ) {
            return null;
        } else {
            return stripslashes( $this->current_season['name'] );
        }
    }

    /**
     * Get current season
     *
     * @param false|string $season season.
     * @param boolean $index lookup.
     * @return false|array|string
     */
    public function get_season_event(false|string $season = false, bool $index = false ): false|array|string {
        $seasons_list = $this->get_seasons();
        $by_name = array();
        foreach ( $seasons_list as $s ) {
            if ( isset( $s['name'] ) ) {
                $by_name[ $s['name'] ] = $s;
            }
        }
        if (! empty( $_GET['season'] )) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            if ( ! isset( $by_name[ $key ] ) ) {
                $data = false;
            } else {
                $data = $by_name[ $key ];
            }
        } elseif ( isset( $_GET[ 'season_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            if ( ! isset( $by_name[ $key ] ) ) {
                $data = false;
            } else {
                $data = $by_name[ $key ];
            }
        } elseif ( $season ) {
            $data = $by_name[ $season ] ?? null;
        } elseif ( ! empty( $seasons_list ) ) {
            $tmp  = $seasons_list;
            $data = end( $tmp );
        } else {
            $data = false;
        }
        if ( empty( $data ) ) {
            $tmp  = $seasons_list;
            $data = ! empty( $tmp ) ? end( $tmp ) : false;
        }
        if ( $index ) {
            return $data[ $index ];
        } else {
            return $data;
        }
    }

    /**
     * Gets number of leagues
     *
     * @param boolean $total should total be stored.
     */
    public function set_num_leagues(bool $total = false ): void {
        if ( true === $total ) {
            $this->num_leagues = $this->get_event_repository()->count_leagues( $this->id );
        }
    }

    /**
     * Get leagues from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_leagues( array $args = array() ): array|int {
        $defaults    = array(
            'offset'      => 0,
            'limit'       => 99999999,
            'orderby'     => array( 'title' => 'ASC' ),
            'consolation' => false,
            'count'       => false,
            'season'      => false,
        );
        $args        = array_merge( $defaults, $args );
        $consolation = $args['consolation'];
        $count       = $args['count'];

        $result = $this->get_event_repository()->query_leagues( $this->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $leagues = is_array( $result ) ? $result : [];
        $league_index = array();
        foreach ( $leagues as $i => $league ) {
            $league_index[ $league->id ] = $i;
            $league                      = get_league( $league->id );
            if ( $consolation && ! $league->championship->is_consolation ) {
                unset( $leagues[ $i ] );
            }
            if ( isset( $leagues[ $i ] ) ) {
                $leagues[ $i ] = $league;
            }
        }
        if ( ! $consolation ) {
            $this->leagues      = $leagues;
            $this->league_index = $league_index;
        }

        return $leagues;
    }

    /**
     * Get player stats
     *
     * @param array $args query arguments.
     * @return array
     */
    public function get_player_stats( array $args ): array {
        return $this->get_event_repository()->get_player_stats( $this->id, $args );
    }

    /**
     * Get teams from database
     *
     * @param array $args query arguments.
     * @return array database results
     */
    public function get_teams_info(array $args = array() ): array {
        if ( empty( $this->get_season() ) ) {
            return array();
        }
        $teams = $this->get_event_repository()->get_teams_info( $this->id, $this->get_season(), $args );

        $class = '';
        foreach ( $teams as $i => $team ) {
            $class        = ( 'alternate' === $class ) ? '' : 'alternate';
            $captain      = get_userdata( $team->captain );
            $team->roster = maybe_unserialize( $team->roster );
            $team->title  = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
            if ( ! empty( $captain ) ) {
                $team->captain      = $captain->display_name;
                $team->captain_id   = $captain->ID;
                $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
                $team->contactemail = $captain->user_email;
            } else {
                $team->captain      = 'Unknown';
                $team->captain_id   = '';
                $team->contactno    = '';
                $team->contactemail = '';
            }
            $team->club_id = stripslashes( $team->club_id );
            $team->club    = get_club( $team->club_id );
            $team->stadium = stripslashes( $team->stadium );
            $team->class   = $class;
            $teams[ $i ]   = $team;
        }

        return $teams;
    }

    /**
     * Get specific team details from database
     *
     * @param int $team_id team id.
     * @return object|null database results
     */
    public function get_team_info( int $team_id ): ?object {
        $team = $this->get_event_repository()->get_team_info( $this->id, $team_id, $this->get_season() );

        if ( $team ) {
            if ( $team->match_day ) {
                $team->match_day_num = Util_Lookup::get_match_day_number( $team->match_day );
            } else {
                $team->match_day_num = null;
            }
            $captain = get_userdata( $team->captain );
            if ( $captain ) {
                $team->captain      = $captain->display_name;
                $team->captain_id   = $captain->ID;
                $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
                $team->contactemail = $captain->user_email;
            } else {
                $team->captain      = 'Unknown';
                $team->captain_id   = '';
                $team->contactno    = '';
                $team->contactemail = '';
            }
        }

        return $team;
    }

    /**
     * Reload settings from database
     */
    public function reload_settings(): void {
        wp_cache_delete( $this->id, 'events' );
        $settings = $this->get_event_repository()->get_settings( $this->id );
        foreach ( $settings as $key => $value ) {
            $this->$key = $value;
        }
    }
    /**
     * Get constitution from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_constitution(array $args = array() ): array|int {
        $defaults  = array(
            'offset'    => 0,
            'limit'     => 99999999,
            'season'    => false,
            'oldseason' => false,
            'club'      => false,
            'count'     => false,
        );
        $args      = array_merge( $defaults, $args );
        $count     = $args['count'];

        $result = $this->get_event_repository()->query_constitution( $this->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $constitutions = is_array( $result ) ? $result : [];
        foreach ( $constitutions as $i => $constitution ) {
            if ( isset( $constitution->old_league_id ) ) {
                $constitution->old_league_title = get_league( $constitution->old_league_id )->title;
            } else {
                $constitution->old_league_title = '';
            }
            $constitutions[ $i ] = $constitution;
        }

        $this->constitutions = $constitutions;

        return $constitutions;
    }

    /**
     * Get constitution from database
     *
     * @param array|string $args search arguments.
     * @return array
     */
    public function build_constitution(array|string $args = array() ): array {
        $defaults = array(
            'offset' => 0,
            'limit'  => 99999999,
            'season' => false,
            'club'   => false,
        );
        $args     = array_merge( $defaults, (array) $args );

        $constitutions = $this->get_event_repository()->build_constitution( $this->id, $args );

        foreach ( $constitutions as $i => $constitution ) {
            $constitution->rank = $constitution->old_rank;
            if ( 'W' !== $constitution->status ) {
                $constitution->status = '';
            }
            $constitution->profile   = '0';
            $constitution->league_id = $constitution->old_league_id;

            $constitutions[ $i ] = $constitution;
        }

        $this->constitutions = $constitutions;

        return $constitutions;
    }

    /**
     * Get clubs for event
     *
     * @param array|string $args search arguments.
     * @return array|int
     */
    public function get_clubs(array|string $args = array() ): array|int {
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
        );
        $args     = array_merge( $defaults, (array) $args );
        $count    = $args['count'];
        $season   = $args['season'] ?: ( $this->current_season['name'] ?? false );
        $args['season'] = $season;

        $result = $this->get_event_repository()->query_clubs( $this->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $event_clubs = is_array( $result ) ? $result : [];
        foreach ( $event_clubs as $i => $event_club ) {
            $team_count               = $event_club->team_count;
            $event_club               = get_club( $event_club->club_id );
            $event_club->team_count   = $team_count;
            $event_club->player_count = $this->get_players(
                array(
                    'season' => $season,
                    'count'  => true,
                    'club'   => $event_club->id,
                )
            );
            $event_clubs[ $i ]        = $event_club;
        }

        $this->clubs = $event_clubs;

        return $event_clubs;
    }

    /**
     * Get teams from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(
                'league_title' => 'ASC',
                'name'         => 'ASC',
            ),
            'club'    => false,
            'league'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
            'player'  => false,
            'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $season   = $args['season'];
        $count    = $args['count'];

        $result = $this->get_event_repository()->query_teams( $this->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $event_teams = is_array( $result ) ? $result : [];
        foreach ( $event_teams as $i => $event_team ) {
            $event_team->roster = maybe_unserialize( $event_team->roster );
            $event_team->club   = get_club( $event_team->club_id );
            if ( str_contains( $event_team->name, '_' ) ) {
                $team_name = Util::generate_team_name( $event_team->name );
                if ( ! empty( $team_name ) ) {
                    $event_team->title = $team_name;
                }
            } else {
                $event_team->title = $event_team->name;
            }
            if ( 'P' === $event_team->team_type && ! empty( $event_team->roster ) ) {
                $team                = get_team( $event_team->team_id );
                $event_team->players = $team->players;
                $p                   = 1;
                foreach ( $team->players as $team_player ) {
                    $event_team->player[ $p ]    = $team_player->get_fullname();
                    $event_team->player_id[ $p ] = $team_player->get_id();
                    ++$p;
                }
            } elseif ( $event_team->club ) {
                $event_team->player_count = $this->get_players(
                    array(
                        'season' => $season,
                        'count'  => true,
                        'team'   => $event_team->team_id,
                    )
                );
            } else {
                $event_team->player_count = 0;
            }
            $event_team->info  = $this->get_team_info( $event_team->team_id );
            $event_teams[ $i ] = $event_team;
        }

        $this->event_teams = $event_teams;

        return $event_teams;
    }

    /**
     * Get players for event
     *
     * @param array|string $args search arguments.
     * @return array|int
     */
    public function get_players(array|string $args = array() ): array|int {
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
        $args     = array_merge( $defaults, (array) $args );
        $count    = $args['count'];
        $stats    = $args['stats'];
        if ( empty( $args['season'] ) ) {
            $args['season'] = is_array( $this->current_season ) ? ( $this->current_season['name'] ?? '' ) : '';
        }

        $result = $this->get_event_repository()->query_players( $this->id, (bool) $this->competition->is_player_entry, $args );
        if ( $count ) {
            return (int) $result;
        }

        $players = is_array( $result ) ? $result : [];
        $event_players = array();
        foreach ( $players as $player ) {
            $player = get_player( $player->player_id );
            if ( $player->system_record ) {
                continue;
            }
            if ( $stats ) {
                $player->matches      = $player->get_matches( $this, $this->current_season['name'], 'event' );
                $player->stats        = $player->get_stats();
                $player->win_pct      = $player->stats['total']->win_pct;
                $player->matches_won  = $player->stats['total']->matches_won;
                $player->matches_lost = $player->stats['total']->matches_lost;
                $player->played       = $player->stats['total']->played;
            }
            $event_players[] = $player;
        }
        if ( $stats ) {
            $won    = array_column( $event_players, 'matches_won' );
            $played = array_column( $event_players, 'played' );
            array_multisort( $won, SORT_DESC, $played, SORT_ASC, $event_players );
        } else {
            asort( $event_players );
        }
        $this->players = $event_players;
        return $this->players;
    }

    /**
     * Mark teams withdrawn from event
     *
     * @param string $season season.
     * @param int $club Club id.
     * @param false|int $team team id (optional).
     */
    public function mark_teams_withdrawn( string $season, int $club, false|int $team = false ): void {
        $this->get_event_repository()->mark_teams_withdrawn( $this->id, $season, $club, $team );
    }

    /**
     * Mark teams entered into event
     *
     * @param int $team Team Id.
     * @param string $season season.
     */
    public function mark_teams_entered(int $team, string $season ): void {
        $this->get_event_repository()->mark_teams_entered( $this->id, $team, $season );
    }

    /**
     * Add team entered into event
     *
     * @param int $team Team Id.
     * @param string $season season.
     */
    public function add_team_to_event(int $team, string $season ): void {
        $leagues   = $this->get_leagues( array( 'orderby' => array( 'title' => 'DESC' ) ) );
        $league_id = $leagues[0]->id;
        $rank      = 99;
        $status    = 'NT';
        $profile   = 1;
        $league    = get_league( $league_id );
        $league->add_team( $team, $season, $rank, $status, $profile );
    }

    /**
     * Get matches for event
     *
     * @param array $match_args query arguments.
     * @return array|int $matches
     */
    public function get_matches( array $match_args ): array|int {
        $match_args = array_merge( $this->match_query_args, $match_args );
        $count      = $match_args['count'] ?? false;
        $player     = $match_args['player'] ?? false;

        $result = $this->get_event_repository()->query_matches( $this->id, $match_args );
        if ( $count ) {
            return (int) $result;
        }

        $matches = is_array( $result ) ? $result : [];
        $class   = '';

        foreach ( $matches as $i => $match ) {
            $class        = ( 'alternate' === $class ) ? '' : 'alternate';
            $match        = get_match( $match );
            $match->class = $class;
            if ( $player ) {
                $match->rubbers = $match->get_rubbers( $player );
            }
            $matches[ $i ] = $match;
        }

        return $matches;
    }
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
        'team_id'             => false,
        'team_name'           => false,
        'home_team'           => false,
        'away_team'           => false,
        'match_day'           => false,
        'home_club'           => false,
        'count'               => false,
        'confirmationPending' => false,
        'resultPending'       => false,
        'status'              => false,
        'pending'             => false,
    );
    /**
     * Generate box league schedule
     */
    public function generate_box_league_matches(): void {
        foreach ( $this->get_leagues() as $league ) {
            $league = get_league( $league );
            $league->schedule_matches();
        }
    }
    /**
     * Send constitution
     *
     * @param array $season season data.
     */
    public function send_constitution( array $season ): void {
        $this->get_notification_service()->send_constitution( $this, $season );
    }
    /**
     * Update seasons
     *
     * @param array $seasons season data.
     */
    public function update_seasons( array $seasons ): bool {
        $current = $this->get_seasons();
        if ( $current !== $seasons ) {
            // Keep property as JSON string
            $this->seasons = wp_json_encode( $seasons );
            $this->get_event_repository()->update_seasons( $this->id, $seasons );
            wp_cache_set( $this->id, $this, 'events' );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Add season
     *
     * @param array $season season data.
     */
    public function add_season( array $season ): void {
        global $racketmanager;
        $seasons                 = $this->get_seasons();
        $season_name             = $season['name'];
        $seasons[ $season_name ] = $season;
        $this->update_seasons( $seasons );
        $racketmanager->set_message( __( 'Season added', 'racketmanager' ) );
        if ( $this->competition->is_league ) {
            $curr_season = end( $seasons );
            $prev_season = prev( $seasons );
            if ( $prev_season ) {
                $teams = $this->build_constitution( array( 'season' => $prev_season['name'] ) );
            } else {
                $teams = array();
            }
            $status      = '';
            $profile     = '0';
            $rank        = 1;
            $league_id   = null;
            $league      = null;
            foreach ( $teams as $team ) {
                if ( $team->old_league_id !== $league_id ) {
                    $league_id = $team->old_league_id;
                    $league    = get_league( $league_id );
                }
                if ( $league ) {
                    $league_team_id = $league->add_team( $team->team_id, $curr_season['name'], $rank, $status, $profile );
                    if ( $league_team_id ) {
                        $league_team_entry = get_league_team( $league_team_id );
                        if ( $league_team_entry ) {
                            $league_team_entry->add_details( $team->captain, $team->match_day, $team->match_time );
                        }
                        $team->league_team_id = $league_team_id;
                    }
                    ++$rank;
                }
            }
        }
    }
    /**
     * Update season
     *
     * @param array $season season data.
     */
    public function update_season(array $season ): void {
        global $racketmanager;
        $seasons                 = $this->get_seasons();
        $season_name             = $season['name'];
        $seasons[ $season_name ] = $season;
        ksort( $seasons );
        $this->update_seasons( $seasons );
        $racketmanager->set_message( __( 'Season updated', 'racketmanager' ) );
    }
    /**
     * Delete season
     *
     * @param int $season season data.
     */
    public function delete_season(int $season ): void {
        if ( isset( $this->get_seasons()[ $season ] ) ) {
            $seasons = $this->get_seasons();
            $leagues = $this->get_leagues();
            foreach ( $leagues as $league ) {
                $league = get_league( $league->id );
                // remove matches and rubbers.
                $league->delete_season_matches( $season );
                // remove tables.
                $this->get_event_repository()->delete_league_teams_for_season( $league->id, (string) $season );
            }
            unset( $seasons[ $season ] );
            $this->update_seasons( $seasons );
        }
    }

    /**
     * Add league
     *
     * @param string|null $league_title league title.
     *
     * @return false|int
     */
    public function add_league( ? string $league_title = null ): false|int {
        global $racketmanager;
        try {
            $league_service = $racketmanager->container->get( 'league_service' );
            $league         = $league_service->add_league_to_event( (int) $this->id, $league_title );
            return $league->id ?? false;
        } catch ( Event_Not_Found_Exception ) {
            return false;
        }
    }
    /**
     * Does the event have leagues?
     *
     * @return int count number of leagues
     */
    public function has_leagues(): int {
        return $this->get_event_repository()->count_leagues( $this->id );
    }
    /**
     * Promote and relegate teams
     *
     * @param array $teams array of teams.
     * @param int $season season name.
     * @return boolean
     */
    public function promote_and_relegate(array $teams, int $season ): bool {
        $competition_season = $this->competition->get_season_by_name( $season );
        $leagues            = $this->get_leagues( array( 'season' => $season ) );
        $teams_prom_relg    = intval( $competition_season['teams_prom_relg'] );
        $teams_per_club     = intval( $competition_season['teams_per_club'] );
        $max_teams          = intval( $competition_season['max_teams'] );
        $lowest_promotion   = intval( $competition_season['lowest_promotion'] );
        $highest_relegation = $max_teams - $teams_prom_relg + 1;
        if ( $leagues ) {
            $curr_league_id = null;
            $curr_league    = null;
            $prev_league    = null;
            $next_league    = null;
            $num_entries    = count( $teams );
            $num_promoted   = 0;
            $num_relegated  = 0;
            $team_count     = 0;
            $new_league_id  = 0;
            foreach ( $teams as $team_dtls ) {
                $status    = null;
                $team_id   = $team_dtls->team_id;
                $league_id = $team_dtls->league_id;
                if ( $league_id !== $curr_league_id ) {
                    if ( empty( $rank ) ) {
                        $prev_league = null;
                        $curr_league = current( $leagues );
                    } else {
                        $prev_league = prev( $leagues );
                        $curr_league = next( $leagues );
                        $team_count  = $prev_league->get_num_teams( 'active' );
                    }
                    $next_league    = next( $leagues );
                    $curr_league_id = $league_id;
                    $num_promoted   = 0;
                    $num_relegated  = 0;
                    $count_club     = array();
                }
                $team     = get_team( $team_id );
                $club_id  = $team->club_id;
                $old_rank = intval( $team_dtls->old_rank );
                $rank     = intval( $team_dtls->rank );
                $table_id = intval( $team_dtls->table_id );
                if ( 'W' !== $team_dtls->status ) {
                    $club_teams_curr = $curr_league->get_league_teams(
                        array(
                            'club'   => $club_id,
                            'season' => $season,
                            'count'  => true,
                            'cache'  => false,
                        )
                    );
                    if ( empty( $count_club[ $club_id ] ) ) {
                        $count_club[ $club_id ] = 0;
                    }
                    $count_club[ $club_id ] += 1;
                    if ( $club_teams_curr > $teams_per_club && $count_club[ $club_id ] === $teams_per_club ) {
                        $new_league_id = $next_league->id;
                        $status        = 'RT';
                        ++$num_relegated;
                    }
                    if ( $num_relegated < $teams_prom_relg && $old_rank >= $highest_relegation ) {
                        if ( $rank === $num_entries ) {
                            $new_league_id = $curr_league->id;
                            $status        = 'BT';
                        } elseif ( intval( $curr_league->num_teams_total ) === $max_teams ) {
                            $new_league_id = $next_league->id;
                            $status        = 'RB';
                            ++$num_relegated;
                        } else {
                            $pos           = $max_teams - $old_rank + 1;
                            $new_league_id = $next_league->id;
                            $status        = 'R' . $pos;
                            ++$num_relegated;
                        }
                    } elseif ( intval( $curr_league->num_teams_total ) === $old_rank ) {
                        $new_league_id = $curr_league->id;
                        $status        = 'BT';
                    } elseif ( 1 === $rank ) {
                        $status        = 'C';
                        $new_league_id = $curr_league->id;
                    } elseif ( $prev_league && $old_rank <= $lowest_promotion ) {
                        if ( intval( $team_count ) !== $max_teams ) {
                            $club_teams_prev = $prev_league->get_league_teams(
                                array(
                                    'club'   => $club_id,
                                    'season' => $season,
                                    'count'  => true,
                                    'cache'  => false,
                                )
                            );
                            if ( $club_teams_prev < $teams_per_club ) {
                                if ( ! empty( $prev_league->id ) ) {
                                    $status        = 'P' . $old_rank;
                                    $new_league_id = $prev_league->id;
                                }
                                ++$team_count;
                                ++$num_promoted;
                            } elseif ( 1 === $old_rank ) {
                                $status        = 'W' . $old_rank;
                                $new_league_id = $curr_league->id;
                            }
                        }
                    }
                }
                if ( $status ) {
                    $league_team = get_league_team( $table_id );
                    $league_team?->update_constitution($new_league_id, $status);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Set Configuration
     *
     * @param object $config configuration details.
     * @return bool
     */
    public function set_config(object $config ): bool {
        $updates   = false;
        $settings = new stdClass();
        if ( empty( $this->age_limit ) || $this->age_limit !== $config->age_limit ) {
            $updates = true;
        }
        $settings->age_limit = $config->age_limit;
        if ( empty( $this->age_offset ) || $this->age_offset !== $config->age_offset ) {
            $updates = true;
        }
        $settings->age_offset = $config->age_offset;
        if ( empty( $this->scoring ) || $this->scoring !== $config->scoring ) {
            $updates = true;
        }
        $settings->scoring = $config->scoring;
        if ( ! $this->competition->is_tournament ) {
            if ( empty( $this->offset ) || $this->offset !== $config->offset ) {
                $updates = true;
            }
            $settings->offset = $config->offset;
        }
        if ( $this->competition->is_championship ) {
            if ( empty( $this->primary_league ) || $this->primary_league !== $config->primary_league ) {
                $updates = true;
            }
            $settings->primary_league = $config->primary_league;
        }
        if ( $this->competition->is_league ) {
            $match_days = Util_Lookup::get_match_days();
            foreach ( $match_days as $match_day => $value ) {
                $config->match_days_allowed[ $match_day ] = isset( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
                if ( ! isset( $this->match_days_allowed[ $match_day ] ) || $this->match_days_allowed[ $match_day ] !== $config->match_days_allowed[ $match_day ] ) {
                    $updates = true;
                }
            }
            $settings->match_days_allowed = $config->match_days_allowed;
        }
        if ( $this->competition->is_team_entry ) {
            if ( empty( $this->num_rubbers ) || $this->num_rubbers !== $config->num_rubbers ) {
                $updates = true;
            }
            $this->num_rubbers = $config->num_rubbers;
            if ( empty( $this->reverse_rubbers ) || $this->reverse_rubbers !== $config->reverse_rubbers ) {
                $updates = true;
            }
            $settings->reverse_rubbers = $config->reverse_rubbers;
        }
        if ( empty( $this->num_sets ) || $this->num_sets !== $config->num_sets ) {
            $updates = true;
        }
        $this->num_sets = $config->num_sets;
        if ( empty( $this->type ) || $this->type !== $config->type ) {
            $updates = true;
        }
        $this->type = $config->type;
        if ( $updates ) {
            $this->settings = (array) $settings;
            $this->update_settings();
        }
        if ( empty( $this->name ) || $this->name !== $config->name ) {
            $this->set_name( $config->name );
            $updates = true;
        }
        return $updates;
    }
    /**
     * Update settings
     */
    private function update_settings(): void {
        $this->get_event_repository()->update_settings_and_rules(
            $this->id,
            $this->settings,
            $this->num_rubbers,
            $this->num_sets,
            $this->type
        );
        wp_cache_set( $this->id, $this, 'events' );
    }
    /*
     * Get club information for event
     *
     * @param object $club club.
     * @return object
     */
    public function get_club( $club ): object {
        $club->teams   = $this->get_teams(
            array(
                'club'   => $club->id,
                'season' => $this->current_season['name'],
                'status' => 1,
            )
        );
        $club->matches = array();
        $matches             = $this->get_matches(
            array(
                'season'  => $this->current_season['name'],
                'club'    => $club->id,
                'time'    => 'next',
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );
        foreach ( $matches as $match ) {
            $key = substr( $match->date, 0, 10 );
            if ( false === array_key_exists( $key, $club->matches ) ) {
                $club->matches[ $key ] = array();
            }
            $club->matches[ $key ][] = $match;
        }
        $club->results = array();
        $matches             = $this->get_matches(
            array(
                'season'  => $this->current_season['name'],
                'club'    => $club->id,
                'time'    => 'latest',
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );
        foreach ( $matches as $match ) {
            $key = substr( $match->date, 0, 10 );
            if ( false === array_key_exists( $key, $club->results ) ) {
                $club->results[ $key ] = array();
            }
            $club->results[ $key ][] = $match;
        }
        $club->players = $this->get_players(
            array(
                'club'   => $club->id,
                'season' => $this->current_season['name'],
                'stats'  => true,
            )
        );
        return $club;
    }

    /**
     * Resolve Competition_Notification_Service instance from container or fallback.
     */
    private function get_notification_service(): Competition_Notification_Service {
        global $racketmanager;
        return isset( $racketmanager->container ) && $racketmanager->container->has( 'competition_notification_service' )
            ? $racketmanager->container->get( 'competition_notification_service' )
            : new Competition_Notification_Service();
    }

    /**
     * Resolve Event_Repository instance from container or fallback.
     */
    private function get_event_repository(): Event_Repository_Interface {
        global $racketmanager;
        return isset( $racketmanager->container ) && $racketmanager->container->has( 'event_repository' )
            ? $racketmanager->container->get( 'event_repository' )
            : new Event_Repository();
    }
}
