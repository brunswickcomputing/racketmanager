<?php
declare( strict_types=1 );
/**
 * Competition API: Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Competition
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Shared\Slug;
use Racketmanager\Domain\Value_Objects\Json_Encoder;
use Racketmanager\Util\Util;
use stdClass;

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
     * @var Season_Collection
     */
    public Season_Collection $seasons;

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
     * @var ?Competition_Type
     */
    public ?Competition_Type $type = null;

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
     * @var Competition_Settings
     */
    public Competition_Settings $settings;
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
    public ?string $age_group = null;
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
     * @var ?string
     */
    public ?string $entry_link = null;
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
    /**
     * @var Competition_Hydration_DTO|null
     */
    private ?Competition_Hydration_DTO $hydration_dto = null;

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
        return Competition_Factory::from_object( $row );
    }

    public static function from_object( object $row ): self {
        return Competition_Factory::from_object( $row );
    }

    /**
     * Create from DTO
     *
     * @param Competition_Hydration_DTO $dto
     *
     * @return self
     */
    public static function from_dto( Competition_Hydration_DTO $dto ): self {
        return Competition_Factory::create_from_dto( $dto );
    }

    /**
     * Constructor
     *
     * @param string $name
     * @param Competition_Type|string $type
     * @param string $age_group
     * @param array|null $seasons
     * @param array|null $settings
     * @param int|null $id
     */
    public function __construct(
        string $name,
        Competition_Type|string $type,
        string $age_group,
        ?array $seasons = null,
        ?array $settings = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = is_string( $type ) ? ( Competition_Type::tryFrom( $type ) ?: Competition_Type::LEAGUE ) : $type;
        $this->age_group = $age_group;

        $this->seasons = new Season_Collection( $seasons ?? [] );
        $this->settings = new Competition_Settings( $settings ?? [] );

        // Championship.
        if ( 'championship' === $this->settings->mode() ) {
            $this->is_championship = true;
        } else {
            $this->is_championship = false;
        }
        $this->num_seasons = $this->seasons->count();

        // Set current season and date-related derived fields using effective seasons
        if ( $this->num_seasons > 0 ) {
            $this->set_current_season();
        }
        // Handle competition flags and cup finals using new Enum and Policy
        $this->is_league       = $this->type->is_league();
        $this->is_cup          = $this->type->is_cup();
        $this->is_tournament   = $this->type->is_tournament();
        $this->is_team_entry   = $this->type->is_team_entry();
        $this->is_player_entry = $this->type->is_player_entry();
        $this->entry_type      = (string) ( $this->settings->all()['entry_type'] ?? ( $this->is_team_entry ? 'team' : 'player' ) );

        if ( $this->is_cup ) {
            $this->finals = Competition_Policy::generate_cup_finals();
        }
    }

    public function set_name( $name ): void {
        $this->name = $name;
    }

    public function set_age_group( $age_group ): void {
        $this->age_group = $age_group;
    }

    public function set_num_courts_available( int $club_id, int $num_courts_available ): void {
        $courts = $this->settings->num_courts_available();
        $courts[ $club_id ] = $num_courts_available;
        $this->settings = $this->settings->with( 'num_courts_available', $courts );
    }

    public function set_seasons( array $seasons ): void {
        $this->seasons = new Season_Collection( $seasons );
    }

    /**
     * Get seasons as JSON string for UI/Shortcodes/API output.
     *
     * Note: Internal logic should continue using get_seasons() which returns array.
     */
    public function get_seasons_json(): string {
        return Json_Encoder::encode( $this->seasons->all() );
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_type(): string {
        return $this->type->value;
    }

    /**
     * Competition type object
     */
    public function get_competition_type(): Competition_Type {
        return $this->type;
    }

    public function get_age_group(): ?string {
        return $this->age_group;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function get_settings(): array {
        return $this->settings->all();
    }

    public function get_seasons(): array {
        return $this->seasons->all();
    }

    /**
     * Get a season by name
     */
    public function get_season_by_name( string $name ): ?array {
        return $this->seasons->get( $name );
    }

    /**
     * Has a specific season
     */
    public function has_season( string $name ): bool {
        return $this->seasons->has( $name );
    }

    /**
     * Update settings
     *
     * @param array|Competition_Settings $settings settings array or object.
     */
    public function set_settings( array|Competition_Settings $settings ): void {
        $this->settings = $settings instanceof Competition_Settings ? $settings : new Competition_Settings( $settings );
    }

    /**
     * Set current season
     *
     * @param string $season season.
     * @param boolean $force_overwrite force overwrite (deprecated).
     */
    public function set_current_season( string $season = '', bool $force_overwrite = false ): void {
        $data = $this->resolve_season_data( $season );
        $this->apply_current_season( $data );
    }

    /**
     * Apply resolved season data and update phase flags.
     *
     * @param array $data Resolved season data.
     */
    public function apply_current_season( array $data ): void {
        $this->current_phase = self::calculate_phase( $data );

        // Reset state flags
        $this->is_complete = 'end' === $this->current_phase || 'complete' === $this->current_phase;
        $this->is_started  = 'start' === $this->current_phase;
        $this->is_closed   = 'close' === $this->current_phase;
        $this->is_open     = 'open' === $this->current_phase;
        $this->is_pending  = 'pending' === $this->current_phase;

        $this->num_match_days = (int) ( $data['num_match_days'] ?? 0 );
        $this->current_season = $data;
    }

    /**
     * Pure domain helper to resolve season data.
     *
     * @param string $season
     * @return array
     */
    public function resolve_season_data( string $season = '' ): array {
        $seasons = $this->seasons;
        $data    = null;

        // 1. Resolve which season to use
        if ( ! empty( $season ) ) {
            $data = $seasons->get( $season );
        }

        // 2. Fallback to active or latest season if not resolved
        if ( ! isset( $data ) || false === $data ) {
            $today = gmdate( 'Y-m-d' );
            foreach ( $seasons->reverse() as $season_item ) {
                $date_active = empty( $season_item['date_closing'] ) ? null : Util::amend_date( $season_item['date_closing'], 7 );
                if ( ! empty( $date_active ) && $date_active <= $today ) {
                    $data = $season_item;
                    break;
                }
            }
        }

        if ( empty( $data ) ) {
            $data = $seasons->latest() ?? [];
        }

        // 3. Hydrate basic dates if missing
        $count_match_dates = isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0;
        if ( empty( $data['date_end'] ) && $count_match_dates >= 2 ) {
            $data['date_end'] = end( $data['match_dates'] );
        }
        if ( empty( $data['date_start'] ) && $count_match_dates >= 2 ) {
            $data['date_start'] = reset( $data['match_dates'] );
        }

        return $data;
    }

    /**
     * Pure domain helper to calculate season phase.
     *
     * @param array $season_data
     * @return string
     */
    public static function calculate_phase( array $season_data ): string {
        $today = gmdate( 'Y-m-d' );

        if ( ! empty( $season_data['date_end'] ) && $today > $season_data['date_end'] ) {
            return 'end';
        }
        if ( ! empty( $season_data['date_start'] ) && $today >= $season_data['date_start'] ) {
            return 'start';
        }
        if ( ! empty( $season_data['date_closing'] ) && $today > $season_data['date_closing'] ) {
            return 'close';
        }
        if ( ! empty( $season_data['date_open'] ) ) {
            if ( $today >= $season_data['date_open'] ) {
                return 'open';
            }
            return 'pending';
        }

        return 'complete';
    }

    /**
     * Instance wrapper for calculate_phase.
     *
     * @param array $season_data
     * @return string
     */
    public function calculate_season_phase( array $season_data ): string {
        return self::calculate_phase( $season_data );
    }

    /**
     * Resolve a season name from input or fall back to competition state.
     *
     * @param int|string|null $season Season name or identifier.
     * @return string|null
     */
    public function resolve_season_name( int|string|null $season = null ): ?string {
        if ( ! empty( $season ) ) {
            return (string) $season;
        }
        if ( isset( $this->season ) && ! empty( $this->season ) ) {
            return (string) $this->season;
        }
        if ( isset( $this->current_season['name'] ) && ! empty( $this->current_season['name'] ) ) {
            return (string) $this->current_season['name'];
        }
        return null;
    }

    /**
     * Get season configuration array for a given season.
     *
     * @param int|string|null $season Season name.
     * @return array|null
     */
    public function get_season_config( int|string|null $season = null ): ?array {
        $season_name = $this->resolve_season_name( $season );
        if ( empty( $season_name ) ) {
            return null;
        }

        if ( isset( $this->seasons ) && $this->seasons instanceof Season_Collection ) {
            return $this->seasons->get( $season_name );
        }

        return null;
    }

    /**
     * Check an event belongs to this competition and (optionally) is configured for a season.
     *
     * @param Event       $event Event.
     * @param string|null $season_name Season name.
     * @return bool
     */
    private function is_event_in_scope( Event $event, ?string $season_name ): bool {
        if ( empty( $event->id ) ) {
            return false;
        }
        if ( ! isset( $event->competition_id ) || (int) $event->competition_id !== (int) $this->id ) {
            return false;
        }
        if ( ! empty( $season_name ) && null === $event->get_season_by_name( $season_name ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get the events of this competition that are configured for a season.
     *
     * Pure in-memory filter over the provided candidate events only.
     *
     * @param int|string|null $season Season name.
     * @param iterable        $events Events list to filter.
     * @return Event[]
     */
    public function get_events_for_season( int|string|null $season = null, iterable $events = [] ): array {
        $season_name = $this->resolve_season_name( $season );
        if ( empty( $season_name ) ) {
            return array();
        }

        $filtered = array();
        foreach ( $events as $candidate ) {
            if ( $candidate instanceof Event && $this->is_event_in_scope( $candidate, $season_name ) ) {
                $filtered[] = $candidate;
            }
        }

        return $filtered;
    }

    /**
     * Resolve an event by ID, name, or object against an in-memory event collection.
     *
     * The event must belong to this competition and be configured for the season.
     *
     * @param int|string|object $event Event ID, event name/slug, or Event object.
     * @param string|null       $season Season name.
     * @param iterable          $events In-memory event collection.
     * @return Event|null
     */
    public function resolve_event( int|string|object $event, ?string $season = null, iterable $events = [] ): ?Event {
        $season_name = $this->resolve_season_name( $season );
        $event_obj   = null;

        if ( $event instanceof Event ) {
            $event_obj = $event;
        } elseif ( ( is_object( $event ) && isset( $event->id ) && is_numeric( $event->id ) && (int) $event->id > 0 ) || ( is_numeric( $event ) && (int) $event > 0 ) ) {
            $target_id = is_object( $event ) ? (int) $event->id : (int) $event;
            foreach ( $events as $candidate ) {
                if ( $candidate instanceof Event && (int) $candidate->id === $target_id ) {
                    $event_obj = $candidate;
                    break;
                }
            }
        } elseif ( is_string( $event ) && '' !== trim( $event ) ) {
            $clean_name = str_replace( '-', ' ', $event );
            $event_slug = Slug::normalise( $event ); // '' when nothing slug-able survives: never a match.
            foreach ( $events as $candidate ) {
                if (
                    $candidate instanceof Event &&
                    (
                        $candidate->name === ucwords( $event ) ||
                        strcasecmp( $candidate->name, $event ) === 0 ||
                        strcasecmp( $candidate->name, $clean_name ) === 0 ||
                        ( '' !== $event_slug && Slug::normalise( (string) $candidate->name ) === $event_slug )
                    )
                ) {
                    $event_obj = $candidate;
                    break;
                }
            }
        }

        if ( ! $event_obj instanceof Event || ! $this->is_event_in_scope( $event_obj, $season_name ) ) {
            return null;
        }

        return $event_obj;
    }

    /**
     * Check if an event is allowed/active for a specific competition season.
     *
     * Pure in-memory check without database queries. Fails closed: a raw ID, slug or row
     * is only accepted when it resolves to an in-scope Event within the supplied candidates.
     *
     * @param int|string|object $event Event ID, event name/slug, or Event object.
     * @param int|string|null   $season Season name.
     * @param bool              $for_entry Whether checking for entry submission (where [] denies all) vs frontend viewing (where [] allows all).
     * @param iterable          $events Optional in-memory event collection.
     * @return bool
     */
    public function is_event_allowed( int|string|object $event, int|string|null $season = null, bool $for_entry = false, iterable $events = [] ): bool {
        $season_name = $this->resolve_season_name( $season );
        if ( empty( $season_name ) ) {
            return false;
        }

        $season_config = $this->get_season_config( $season_name );
        if ( null === $season_config ) {
            return false;
        }

        // Fail closed: raw IDs/slugs/rows are only trusted when resolved against supplied candidate events.
        $event_obj = $this->resolve_event( $event, $season_name, $events );
        if ( ! $event_obj ) {
            return false;
        }
        $event_id = (int) $event_obj->id;

        $entry_events = $season_config['entry_events'] ?? null;

        if ( $for_entry ) {
            // Entry validation: null/unset means all season events; explicit [] means closed/none
            if ( ! is_array( $season_config ) || ! array_key_exists( 'entry_events', $season_config ) || is_null( $entry_events ) ) {
                return true;
            }
            $entry_event_ids = is_array( $entry_events ) ? array_map( 'intval', $entry_events ) : array();
            return in_array( $event_id, $entry_event_ids, true );
        }

        // Frontend viewing: null/unset or empty [] means all season events allowed
        if ( empty( $entry_events ) || ! is_array( $entry_events ) ) {
            return true;
        }

        $entry_event_ids = array_map( 'intval', $entry_events );
        return in_array( $event_id, $entry_event_ids, true );
    }

    /**
     * Get frontend active/allowlisted events for a season.
     * Unset or empty allowlist displays all season events.
     *
     * @param int|string|null $season Season name.
     * @param iterable        $events In-memory event collection.
     * @return array
     */
    public function get_frontend_events( int|string|null $season = null, iterable $events = [] ): array {
        $season = $this->resolve_season_name( $season );
        if ( empty( $season ) ) {
            return array();
        }

        $events_list   = $this->get_events_for_season( $season, $events );
        $season_config = $this->get_season_config( $season );
        $entry_events  = $season_config['entry_events'] ?? null;

        if ( empty( $entry_events ) || ! is_array( $entry_events ) ) {
            return array_values( $events_list );
        }

        $entry_event_ids = array_map( 'intval', $entry_events );
        foreach ( $events_list as $index => $event ) {
            $event_id = (int) ( $event->id ?? ( method_exists( $event, 'get_id' ) ? $event->get_id() : 0 ) );
            if ( ! in_array( $event_id, $entry_event_ids, true ) ) {
                unset( $events_list[ $index ] );
            }
        }

        return array_values( $events_list );
    }

    /**
     * Get events available for entry in this competition.
     *
     * @param int|string|null $season Season name.
     * @param iterable        $events In-memory event collection.
     * @return array
     */
    public function get_entry_events( int|string|null $season = null, iterable $events = [] ): array {
        $season = $this->resolve_season_name( $season );
        if ( empty( $season ) ) {
            return array();
        }

        $events_list   = $this->get_events_for_season( $season, $events );
        $season_config = $this->get_season_config( $season );

        if ( ! is_array( $season_config ) || ! array_key_exists( 'entry_events', $season_config ) || is_null( $season_config['entry_events'] ) ) {
            return array_values( $events_list );
        }

        $entry_event_ids = is_array( $season_config['entry_events'] ) ? array_map( 'intval', $season_config['entry_events'] ) : array();
        foreach ( $events_list as $index => $event ) {
            $event_id = (int) ( $event->id ?? ( method_exists( $event, 'get_id' ) ? $event->get_id() : 0 ) );
            if ( ! in_array( $event_id, $entry_event_ids, true ) ) {
                unset( $events_list[ $index ] );
            }
        }

        return array_values( $events_list );
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
     * Update seasons
     *
     * Accepts either an array (preferred) or a JSON string (Option B compatibility).
     * Only mutates in-memory state; callers persist through Competition_Service.
     *
     * @param array|string $seasons Season data as array or JSON string.
     */
    public function update_seasons( array|string $seasons ): bool {

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
            $this->seasons = new Season_Collection( $seasons );
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
        $seasons                  = $this->get_seasons();
        $seasons[ $season->name ] = (array) $season;

        return (bool) $this->update_seasons( $seasons );
    }
    /**
     * Update season
     *
     * @param array $season season data.
     */
    public function update_season( array $season ): bool {
        $seasons                 = $this->get_seasons();
        $season_name             = $season['name'];
        $seasons[ $season_name ] = $season;
        ksort( $seasons );
        return $this->update_seasons( $seasons );
    }

}
