<?php
/**
 * Event API: Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Event
 */

namespace Racketmanager\Domain;

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
    public int $primary_league;
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
    /**
     * Draw size
     *
     * @var int
     */
    public int $draw_size;
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
        global $wpdb;
        $search = match ($search_term) {
            'name' => $wpdb->prepare(
                '`name` = %s',
                $event_id
            ),
            default => $wpdb->prepare(
                '`id` = %d',
                $event_id
            ),
        };
        if ( ! $event_id ) {
            return false;
        }

        $event = wp_cache_get( $event_id, 'events' );
        if ( ! $event ) {
            $event = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competition_id` FROM $wpdb->racketmanager_events WHERE " . $search . ' LIMIT 1'
            ); // db call ok.
            if ( ! $event ) {
                return false;
            }
            $event->settings = (array) maybe_unserialize( $event->settings );
            $event           = (object) ( $event->settings + (array) $event );
            $event           = new Event( $event );
            wp_cache_set( $event->id, $event, 'events' );
        }

        return $event;
    }

    /**
     * Constructor
     *
     * @param object $event Event object.
     */
    public function __construct(object $event ) {
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
        $this->type        = empty( $this->type ) ? null : stripslashes( $this->type );
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
        if ( 'championship' === $this->competition->settings['mode'] ) {
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
        global $wpdb;
        $settings = array();
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_events (`name`, `competition_id`, `num_rubbers`, `num_sets`, `type`, `settings`) VALUES (%s, %d, %d, %d, %s, %s)",
                $event->name,
                $event->competition_id,
                $event->num_rubbers,
                $event->num_sets,
                $event->type,
                maybe_serialize( $settings ),
            )
        );
        $event->id = $wpdb->insert_id;
    }

    /**
     * Delete Event
     */
    public function delete(): void {
        global $wpdb;

        foreach ( $this->get_leagues() as $league ) {
            $league = get_league( $league->id );
            $league->delete();
        }
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_events WHERE `id` = %d",
                $this->id
            )
        );
    }

    /**
     * Set name
     *
     * @param string $name event name.
     */
    public function set_name(string $name ): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_events SET `name` = %s WHERE `id` =%d",
                $name,
                $this->id
            )
        );
        $this->name = $name;
        wp_cache_set( $this->id, $this, 'events' );
    }

    /**
     * Update settings
     *
     * @param array $settings settings array.
     */
    public function set_settings(array $settings ): void {
        global $wpdb, $racketmanager, $match;
        $num_rubbers = $this->num_rubbers ?? null;
        $num_sets    = $this->num_sets ?? null;
        $type        = $this->type;
        if ( isset( $settings['reverse_rubbers'] ) && '1' === $settings['reverse_rubbers'] ) {
            $match_args             = array();
            $match_args['season']   = $this->current_season['name'];
            $match_args['event_id'] = $this->id;
            if ( ! isset( $this->settings['reverse_rubbers'] ) || $this->settings['reverse_rubbers'] !== $settings['reverse_rubbers'] ) {
                $matches = $racketmanager->get_matches( $match_args );
                foreach ( $matches as $match ) {
                    $match         = get_match( $match->id );
                    $rubber_count  = $match->get_rubbers( null, true );
                    $total_rubbers = $rubber_count * 2;
                    if ( intval( $rubber_count ) === intval( $match->league->num_rubbers ) ) {
                        for ( $ix = $rubber_count + 1; $ix <= $total_rubbers; $ix++ ) {
                            $rubber                = new stdClass();
                            $rubber->type          = $this->type;
                            $rubber->rubber_number = $ix;
                            $rubber->date          = $match->date;
                            $rubber->match_id      = $match->id;
                            new Rubber( $rubber );
                        }
                    }
                }
            }
        }

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_events SET `settings` = %s, `num_rubbers` = %d, `num_sets` = %d, `type` = %s WHERE `id` = %d",
                maybe_serialize( $settings ),
                $num_rubbers,
                $num_sets,
                $type,
                $this->id
            )
        );
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
        global $wpdb;

        if ( true === $total ) {
            $this->num_leagues = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT COUNT(ID) FROM $wpdb->racketmanager WHERE `event_id` = %d",
                    $this->id
                )
            );
        }
    }

    /**
     * Get leagues from database
     *
     * @param array $args search arguments.
     * @return array|int
     */
    public function get_leagues( array $args = array() ): array|int {
        global $wpdb;

        $defaults    = array(
            'offset'      => 0,
            'limit'       => 99999999,
            'orderby'     => array( 'title' => 'ASC' ),
            'consolation' => false,
            'count'       => false,
            'season'      => false,
        );
        $args        = array_merge( $defaults, $args );
        $offset      = $args['offset'];
        $limit       = $args['limit'];
        $orderby     = $args['orderby'];
        $consolation = $args['consolation'];
        $count       = $args['count'];
        $season      = $args['season'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );
        if ( $consolation ) {
            $search_terms[] = "'consolation' = 'consolation'";
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( "`id` IN (SELECT DISTINCT `league_id` FROM $wpdb->racketmanager_league_teams t, $wpdb->racketmanager l WHERE t.`league_id` = l.`id` AND `season` = %d AND `event_id` = %d)", intval( $season ), $this->id);
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql = "SELECT COUNT(*) FROM $wpdb->racketmanager $search ";
            return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $sql //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
        }
        $order = Util::order_by_string( $orderby );
        $sql   = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `title`, `id`, `settings`, `event_id` FROM $wpdb->racketmanager $search $order LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $leagues = wp_cache_get( md5( $sql ), 'leagues' );
        if ( ! $leagues ) {
            $leagues = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $leagues, 'leagues' );
        }

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
        global $wpdb;

        $defaults  = array(
            'season'    => false,
            'club'      => false,
            'league_id' => false,
            'system'    => false,
            'player'    => false,
        );
        $args      = array_merge( $defaults, $args);
        $season    = $args['season'];
        $club      = $args['club'];
        $league_id = $args['league_id'];
        $system    = $args['system'];
        $player    = $args['player'];

        $sql1 = "SELECT p.ID AS `player_id`, p.`display_name` AS `fullname`, ro.`id` AS `roster_id`,  ro.`club_id` FROM $wpdb->racketmanager_club_players AS ro, $wpdb->users AS p WHERE ro.`player_id` = p.`ID`";
        $sql2 = "FROM $wpdb->racketmanager_teams AS t, $wpdb->racketmanager_rubbers AS r, $wpdb->racketmanager_rubber_players AS rp, $wpdb->racketmanager_matches AS m, $wpdb->racketmanager_club_players AS ro WHERE r.`winner_id` != 0 AND r.`id` = rp.`rubber_id` AND ((rp.`player_team` = 'home' AND rp.`club_player_id` = ro.`id` AND  m.`home_team` = t.`id`) OR (rp.`player_team` = 'away' AND rp.`club_player_id` = ro.`id` AND m.`away_team` = t.`id`)) AND ro.`club_id` = t.`club_id` AND r.`match_id` = m.`id` ";
        $search_terms2   = array();
        $search_terms2[] = $wpdb->prepare( "m.`league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` = '%d')", $this->id );

        if ( $season ) {
            $search_terms2[] = $wpdb->prepare( "m.`season` = %s", wp_strip_all_tags( $season ) );
        }
        if ( $league_id ) {
            $search_terms2[] = $wpdb->prepare( "m.`league_id` = %d", intval( $league_id ) );
        }
        if ( $club ) {
            $search_terms2[] = $wpdb->prepare( "ro.`club_id` = %d", intval( $club ) );
        }
        if ( $player ) {
            $search_terms2[] = $wpdb->prepare( "ro.`id` = %d", $player );
        }
        if ( ! $system ) {
            $search_terms2[] = "ro.`system_record` IS NULL";
        }

        $order       = '`club_id`, `fullname` ';
        $search_2    = Util::search_string( $search_terms2 );
        $sql2        = $sql2 . $search_2;
        $sql         = $sql1 . ' AND ro.`id` in (SELECT ro.id ' . $sql2 . ')';
        $sql        .= " ORDER BY $order";
        $playerstats = wp_cache_get( md5( $sql ), 'playerstats' );
        if ( ! $playerstats ) {
            $playerstats = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $playerstats, 'playerstats' );
        }

        foreach ( $playerstats as $i => $playerstat ) {
            $sql3  = 'SELECT t.`id` AS team_id,  t.`title` AS team_title, m.`season`, m.`match_day`, m.`home_team`, m.`away_team`, m.`winner_id` AS match_winner, m.`home_points`, m.`away_points`, m.`loser_id` AS match_loser, r.`rubber_number`, r.`winner_id` AS rubber_winner, r.`loser_id` AS rubber_loser, r.`custom`, m.`final` as `final_round`';
            $sql4  = $wpdb->prepare( "ro.`ID` = %d", $playerstat->roster_id );
            $sql3 .= $sql2 . ' AND ' . $sql4;
            $sql3 .= ' ORDER BY m.`season`, m.`match_day`';

            $sql = $sql3;
            $stats = wp_cache_get( md5( $sql ), 'playerstats' );
            if ( ! $stats ) {
                $stats = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $stats, 'playerstats' );
            }

            foreach ( $stats as $s => $stat ) {
                $stat->custom = stripslashes_deep( maybe_unserialize( $stat->custom ) );
                $stats[ $s ]  = $stat;
            }

            $playerstat->matchdays = $stats;
            $playerstats[ $i ]     = (object) (array) $playerstat;
        }

        return $playerstats;
    }

    /**
     * Get teams from database
     *
     * @param array $args query arguments.
     * @return array database results
     */
    public function get_teams_info(array $args = array() ): array {
        global $wpdb;

        if ( empty( $this->get_season() ) ) {
            return array();
        }
        $defaults  = array(
            'league_id' => false,
            'rank'      => false,
            'home'      => false,
            'club'      => false,
            'orderby'   => array(
                'rank'  => 'ASC',
                'title' => 'ASC',
            ),
        );
        $args      = array_merge( $defaults, $args );
        $league_id = $args['league_id'];
        $rank      = $args['rank'];
        $orderby   = $args['orderby'];
        $home      = $args['home'];
        $club      = $args['club'];

        $search_terms = array();
        if ( $league_id ) {
            if ( 'any' === $league_id ) {
                $search_terms[] = "A.`league_id` != ''";
            } else {
                $search_terms[] = $wpdb->prepare( 'A.`league_id` = %d', intval( $league_id ) );
            }
        }
        if ( $club ) {
            $search_terms[] = $wpdb->prepare( '`club_id` = %d', intval( $club ) );
        }
        if ( $rank ) {
            $search_terms[] = $wpdb->prepare( 'A.`rank` = %s', $rank );
        }

        if ( $home ) {
            $search_terms[] = 'B.`home` = 1';
        }

        $search = Util::search_string( $search_terms );
        $order  = Util::order_by_string( $orderby );
        $sql = "SELECT DISTINCT B.`id`, B.`title`, A.`captain`, B.`club_id`, B.`stadium`, B.`home`, B.`roster`, B.`profile`, A.`group`, A.`match_day`, A.`match_time` FROM $wpdb->racketmanager_teams B, $wpdb->racketmanager_league_teams A WHERE B.id = A.team_id AND A.league_id in (select `id` from $wpdb->racketmanager WHERE `event_id` = " . $this->id . ') AND A.season = ' . $this->get_season() . " $search $order";

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

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
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT TBL.`captain`, TBL.`match_day`, TBL.`match_time` FROM $wpdb->racketmanager_league_teams TBL WHERE TBL.`team_id` = %d AND TBL.`season` = %s AND TBL.`league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` = %d) LIMIT 1",
            $team_id,
            $this->get_season(),
            $this->id
        );

        $team = wp_cache_get( md5( $sql ), 'team' );
        if ( ! $team ) {
            $team = $wpdb->get_row( $sql );
            wp_cache_set( md5( $sql ), $team, 'team' );
        }

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
        global $wpdb;

        wp_cache_delete( $this->id, 'events' );
        $result = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT `settings` FROM $wpdb->racketmanager_events WHERE `id` = %d",
                $this->id
            )
        );
        foreach ( maybe_unserialize( $result->settings ) as $key => $value ) {
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
        global $wpdb;

        $defaults  = array(
            'offset'    => 0,
            'limit'     => 99999999,
            'season'    => false,
            'oldseason' => false,
            'club'      => false,
            'count'     => false,
        );
        $args      = array_merge( $defaults, $args );
        $offset    = $args['offset'];
        $limit     = $args['limit'];
        $season    = $args['season'];
        $oldseason = $args['oldseason'];
        $club      = $args['club'];
        $count     = $args['count'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );

        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
        }

        if ( ! $oldseason ) {
            $oldseason = $season;
        }

        if ( $club ) {
            $search_terms[] = $wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(*)';
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, ot.`league_id` AS old_league_id, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank`,`ot`.`rank` AS old_rank, l.`id`, ot.`points_plus`, ot.`add_points`, t1.`status`, t1.`profile`, t1.`rating`';
        }
        $sql .= " FROM $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_league_teams t1 LEFT OUTER JOIN $wpdb->racketmanager_league_teams ot ON `ot`.`season` = %s and `ot`.`team_id` = `t1`.`team_id` and ot.league_id in (select id from $wpdb->racketmanager ol where ol.`event_id` = %d) WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ";
        $sql  = $wpdb->prepare(
            $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $oldseason,
            $this->id,
        );

        if ( $count ) {
            return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $sql //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
        }
        $sql = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql . ' ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );

        $constitutions = wp_cache_get( md5( $sql ), 'constitution' );
        if ( ! $constitutions ) {
            $constitutions = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
        }

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
        global $wpdb;

        $defaults = array(
            'offset' => 0,
            'limit'  => 99999999,
            'season' => false,
            'club'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $club     = $args['club'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id);

        if ( $season ) {
            $search_terms[] = $wpdb->prepare( '`season` = %s', $season );
        }

        if ( $club ) {
            $search_terms[] = $wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }

        $search = Util::search_string( $search_terms );
        $sql    = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `l`.`title` AS `old_league_title`, l.`id` AS `old_league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title`,`t1`.`rank` AS old_rank, l.`id`, t1.`points_plus`, t1.`add_points`, t1.`status`, t1.`profile`, t1.captain, t1.match_day, t1.match_time FROM $wpdb->racketmanager l, $wpdb->racketmanager_league_teams t1, $wpdb->racketmanager_teams t2 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d",
            intval( $offset ),
            intval( $limit )
        );
        $constitutions = wp_cache_get( md5( $sql ), 'constitution' );
        if ( ! $constitutions ) {
            $constitutions = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $constitutions, 'constitution' );
        }

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
        global $wpdb;

        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );
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

        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(DISTINCT(c.`id`))';
        } else {
            $sql = 'SELECT t2.`club_id`, count(t2.`id`) as `team_count`';
        }
        $sql .= " FROM $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_league_teams t1, $wpdb->racketmanager_clubs c WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` AND t2.`club_id` = c.`id`" . $search;

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

        $event_clubs = wp_cache_get( md5( $sql ), 'event_clubs' );
        if ( ! $event_clubs ) {
            $event_clubs = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $event_clubs, 'event_clubs' );
        }

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
            'league'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
            'player'  => false,
            'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $offset   = $args['offset'];
        $limit    = $args['limit'];
        $season   = $args['season'];
        $orderby  = $args['orderby'];
        $club     = $args['club'];
        $league   = $args['league'];
        $status   = $args['status'];
        $count    = $args['count'];
        $name     = $args['name'];
        $player   = $args['player'];
        $partner  = $args['partner'];

        $search_terms   = array();
        $search_terms[] = $wpdb->prepare( '`event_id` = %d', $this->id );

        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 't1.`season` = %s', $season );
        }
        if ( $club ) {
            $search_terms[] = $wpdb->prepare( 't2.`club_id` = %d', intval( $club ) );
        }
        if ( $league ) {
            $search_terms[] = $wpdb->prepare( 'l.`id` = %d', intval( $league ) );
        }
        if ( $status ) {
            $search_terms[] = $wpdb->prepare( 't1.`profile` = %d', intval( $status ) );
        }
        if ( $name ) {
            $search_terms[] = $wpdb->prepare( 't2.`title` like %s', '%' . $name . '%' );
        }
        if ( $player ) {
            $search_terms[] = $wpdb->prepare( "t2.`id` IN (SELECT `team_id` FROM $wpdb->racketmanager_team_players WHERE `player_id` = %d )", $player );
        }
        if ( $partner ) {
            $search_terms[] = $wpdb->prepare( "t2.`id` IN (SELECT `team_id` FROM $wpdb->racketmanager_team_players WHERE `player_id` = %d )", $partner );
        }
        $search = Util::search_string( $search_terms );
        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`team_id`))';
        } else {
            $sql = 'SELECT `l`.`title` AS `league_title`, l.`id` AS `league_id`, t2.`id` AS `team_id`, t1.`id` AS `table_id`, `t2`.`title` as `name`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`, t2.`roster`, t2.`club_id`, t2.`team_type` AS `team_type`, t1.`rating`';
        }
        $sql .= " FROM $wpdb->racketmanager l, $wpdb->racketmanager_teams t2, $wpdb->racketmanager_league_teams t1 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` " . $search;

        if ( $count ) {
            $event_teams = wp_cache_get( md5( $sql ), 'event_teams' );
            if ( ! $event_teams ) {
                $event_teams = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $event_teams, 'event_teams' );

            }
            return $event_teams;
        }
        $sql .= Util::order_by_string( $orderby );
        $sql  = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql . ' LIMIT %d, %d',
            intval( $offset ),
            intval( $limit )
        );
        $event_teams = wp_cache_get( md5( $sql ), 'event_teams' );
        if ( ! $event_teams ) {
            $event_teams = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $event_teams, 'event_teams' );
        }
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

        if ( $count ) {
            $sql = 'SELECT COUNT(distinct(`player_id`))';
        } else {
            $sql = 'SELECT DISTINCT `player_id`';
        }
        if ( $this->competition->is_player_entry ) {
            $sql .= " FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_league_teams t, $wpdb->racketmanager l  WHERE tp.`team_id` = t.`team_id` AND t.`league_id` = l.`id` AND l.`event_id` = %d";
        } else {
            $sql .= " FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m  WHERE rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` = %d)";
        }
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
            if ( $this->competition->is_player_entry ) {
                $search_terms[] = 'tp.`team_id` = %d';
                $search_args[]  = $team;
            } else {
                $search_terms[] = '(( `home_team` = %d AND `player_team` = %s) OR (`away_team` = %d AND `player_team` = %s))';
                $search_args[]  = $team;
                $search_args[]  = 'home';
                $search_args[]  = $team;
                $search_args[]  = 'away';
            }
        }
        if ( $club ) {
            $search_terms[] = "(( `home_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s) OR (`away_team` in (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) AND `player_team` = %s))";
            $search_args[]  = $club;
            $search_args[]  = 'home';
            $search_args[]  = $club;
            $search_args[]  = 'away';
        }
        $search = Util::search_string( $search_terms );
        $order  = Util::order_by_string( $orderby );
        $sql   .= $search;
        if ( $count ) {
            $sql = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql,
                $search_args,
            );
            $num_players = wp_cache_get( md5( $sql ), 'event_rubber_players' );
            if ( ! $num_players ) {
                $num_players = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                ); // db call ok.
                wp_cache_set( md5( $sql ), $num_players, 'event_rubber_players' );
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
        $players = wp_cache_get( md5( $sql ), 'event_rubber_players' );
        if ( ! $players ) {
            $players = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $players, 'event_rubber_players' );
        }
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
        global $wpdb;

        $search_terms = array();
        if ( $team ) {
            $search_terms[] = $wpdb->prepare( '`team_id` = %d', $team);
        }
        $search = Util::search_string( $search_terms );
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "UPDATE $wpdb->racketmanager_league_teams SET `profile` = 3, `status` = 'W' WHERE `league_id` IN (select `id` FROM $wpdb->racketmanager WHERE `event_id` = %d) AND `season` = %s AND `team_id` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) $search ",
                $this->id,
                $season,
                $club
            )
        );
    }

    /**
     * Mark teams entered into event
     *
     * @param int $team Team Id.
     * @param string $season season.
     */
    public function mark_teams_entered(int $team, string $season ): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "UPDATE $wpdb->racketmanager_league_teams SET `profile` = 1 WHERE `league_id` IN (select `id` FROM $wpdb->racketmanager WHERE `event_id` = %d) AND `season` = %s AND `team_id` = %d",
                $this->id,
                $season,
                $team
            )
        );
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
     * @return array $matches
     */
    public function get_matches( array $match_args ): array {
        global $wpdb;

        $match_args           = array_merge( $this->match_query_args, $match_args);
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
        $team_id              = $match_args['team_id'];
        $team_name            = $match_args['team_name'];
        $home_team            = $match_args['home_team'];
        $home_club            = $match_args['home_club'];
        $away_team            = $match_args['away_team'];
        $match_day            = $match_args['match_day'];
        $count                = $match_args['count'];
        $confirmation_pending = $match_args['confirmationPending'];
        $result_pending       = $match_args['resultPending'];
        $status               = $match_args['status'];
        $pending              = $match_args['pending'];
        $search_terms         = array();
        $sql_from             = " FROM $wpdb->racketmanager_matches AS m, $wpdb->racketmanager AS l, $wpdb->racketmanager_rubbers AS r ";
        $sql                  = " WHERE m.`league_id` = l.`id` AND m.`id` = r.`match_id`";
        $search_terms[]       = $wpdb->prepare( " l.`event_id` = %d " , $this->id );
        if ( $count ) {
            $sql_fields = 'SELECT COUNT( distinct m.id ) AS count)';
        } else {
            $sql_fields = "SELECT DISTINCT m.`final` AS final_round, m.`group`, `home_team`, `away_team`, DATE_FORMAT(m.`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(m.`date`, '%e') AS day, DATE_FORMAT(m.`date`, '%c') AS month, DATE_FORMAT(m.`date`, '%Y') AS year, DATE_FORMAT(m.`date`, '%H') AS `hour`, DATE_FORMAT(m.`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, m.`home_points`, m.`away_points`, m.`winner_id`, m.`loser_id`, m.`post_id`, `season`, m.`id` AS `id`, m.`custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, m.`status`";
        }

        if ( $match_date ) {
            $search_terms[] = $wpdb->prepare( "DATEDIFF( %s, `date`) = 0", htmlspecialchars( wp_strip_all_tags( $match_date ) ) );
        }
        if ( $league_id ) {
            $search_terms[] = $wpdb->prepare( "`league_id`  = %d", $league_id );
        }
        if ( $league_name ) {
            $search_terms[] = $wpdb->prepare( "`league_id` in ( select `id` from $wpdb->racketmanager WHERE `title` = %s ) ", $league_name );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( "`season`  = %s", $season );
        }
        if ( $final ) {
            $search_terms[] = $wpdb->prepare( "`final`  = %s", $final );
        }
        if ( $time_offset ) {
            $time_offset = intval( $time_offset ) . ':00:00';
        } else {
            $time_offset = '00:00:00';
        }
        if ( $status ) {
            $search_terms[] = $wpdb->prepare( "`confirmed` = %s", $status );
        }
        if ( $confirmed ) {
            $search_terms[] = "`confirmed` in ('P','A','C') ";
            if ( $time_offset ) {
                $search_terms[] = $wpdb->prepare( "ADDTIME(`updated`, %s) <= NOW()", $time_offset );
            }
        }
        if ( $player ) {
            $sql_from .= ", $wpdb->racketmanager_rubber_players AS rp";
            $search_terms[] = "r.`id` = rp.`rubber_id`";
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
            $search_terms[] = "(m.`home_points` != '' OR m.`away_points` != '')";
        } elseif ( 'outstanding' === $time ) {
            $search_terms[] = $wpdb->prepare( "ADDTIME(m.`date`, %s) <= NOW() AND m.`winner_id` = 0 AND `confirmed` IS NULL", $time_offset );
        } elseif ( 'next' === $time ) {
            $search_terms[] = "TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0";
        }
        // get only updated matches in specified period for history.
        if ( $history ) {
            $search_terms[] = $wpdb->prepare( "`updated` >= NOW() - INTERVAL %s DAY", $history );
        }

        if ( $club ) {
            $search_terms[] = $wpdb->prepare( "(`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d) OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d))", $club, $club );
        }
        if ( $home_club ) {
            $search_terms[] = $wpdb->prepare( "`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `club_id` = %d)", $home_club );
        }
        if ( ! empty( $home_team ) ) {
            $search_terms[] = $wpdb->prepare( "`home_team` = %s", $home_team );
        }
        if ( ! empty( $away_team ) ) {
            $search_terms[] = $wpdb->prepare( "`away_team` = %s", $away_team );
        }
        if ( ! empty( $team_id ) ) {
            $search_terms[] = $wpdb->prepare( "(`home_team` = %d OR `away_team` = %d)", $team_id, $team_id );
        }
        if ( ! empty( $team_name ) ) {
            $team_name_search = '%' . $wpdb->esc_like( $team_name ) . '%';
            $search_terms[] = $wpdb->prepare( "(`home_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE %s) OR `away_team` IN (SELECT `id` FROM $wpdb->racketmanager_teams WHERE `title` LIKE %s))", $team_name_search, $team_name_search );
        }
        if ( $match_day && intval( $match_day ) > 0 ) {
            $search_terms[] = $wpdb->prepare( "`match_day` = %s", $match_day );
        }
        if ( $pending ) {
            $search_terms[] = "m.`winner_id` = 0";
        }
        $search = Util::search_string( $search_terms );
        $sql    = $sql_fields . $sql_from . $sql . $search ;
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
        global $racketmanager;
        $email_address                 = $racketmanager->get_confirmation_email( $this->competition->type );
        $organisation                  = $racketmanager->site_name;
        $message_args                  = array();
        $message_args['organisation']  = $organisation;
        $message_args['event']         = $this->name;
        $message_args['emailfrom']     = $email_address;
        $message_args['template_type'] = 'email';
        $email_message                 = constitution_notification( $this->id, $message_args );
        $headers                       = array();
        $headers[]                     = 'From: ' . ucfirst( $this->competition->type ) . ' Secretary <' . $email_address . '>';
        $headers[]                     = 'cc: ' . ucfirst( $this->competition->type ) . ' Secretary <' . $email_address . '>';
        $clubs                         = $racketmanager->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $subject                       = $organisation . ' - ' . $this->name . ' ' . $season['name'] . ' - Constitution';
        foreach ( $clubs as $club ) {
            if ( ! empty( $club->match_secretary->email ) ) {
                $headers[] = 'bcc: ' . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
            }
        }
        wp_mail( $email_address, $subject, $email_message, $headers );
    }
    /**
     * Update seasons
     *
     * @param array $seasons season data.
     */
    public function update_seasons( array $seasons ): bool {
        global $wpdb;
        $current = $this->get_seasons();
        if ( $current !== $seasons ) {
            // Keep property as JSON string
            $this->seasons = wp_json_encode( $seasons );
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_events SET `seasons` = %s WHERE `id` = %d",
                    $this->get_seasons_json(),
                    $this->id
                )
            );
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
        global $wpdb;
        if ( isset( $this->get_seasons()[ $season ] ) ) {
            $seasons = $this->get_seasons();
            $leagues = $this->get_leagues();
            foreach ( $leagues as $league ) {
                $league = get_league( $league->id );
                // remove matches and rubbers.
                $league->delete_season_matches( $season );
                // remove tables.
                $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "DELETE FROM $wpdb->racketmanager_league_teams WHERE `league_id` = %d AND `season` = %s",
                        $league->id,
                        $season
                    )
                );
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
        if ( empty( $league_title ) ) {
            $league_count = $this->has_leagues();
            ++$league_count;
            $league           = new stdClass();
            $league->title    = $this->name . ' ' . $league_count;
            $league->event_id = $this->id;
            $league->sequence = $league_count;
        } else {
            $league           = new stdClass();
            $league->title    = $league_title;
            $league->event_id = $this->id;
        }
        $league = new League( $league );
        return $league->id;
    }
    /**
     * Does the event have leagues?
     *
     * @return int count number of leagues
     */
    public function has_leagues(): int {
        global $wpdb;

        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager WHERE `event_id` = %d",
                $this->id
            )
        );
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
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_events SET `settings` = %s, `num_rubbers` = %d, `num_sets` = %d, `type` = %s WHERE `id` = %d",
                maybe_serialize( $this->settings ),
                $this->num_rubbers,
                $this->num_sets,
                $this->type,
                $this->id
            )
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
}
