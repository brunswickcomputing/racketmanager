<?php
declare( strict_types=1 );
/**
 * Event API: Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Event
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Domain\Value_Objects\Json_Encoder;
use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Class to implement the Event object
 */
class Event {

    /**
     * Event ID
     *
     * @var int
     */
    public int $id = 0;

    /**
     * Event name
     *
     * @var string
     */
    public string $name = '';

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
     * @var string|array|bool
     */
    public string|array|bool $current_season = '';

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
    public null|object $competition = null;
    /**
     * Competition id
     *
     * @var int
     */
    public int $competition_id = 0;
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
     * @var string|null
     */
    public ?string $scoring = null;
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
     * Constructor
     *
     * Hydrates from the typed DTO only: table columns are applied first, then the decoded `settings`
     * keys (settings take precedence over columns, as the legacy row merge did). `new Event()` yields
     * an empty entity.
     *
     * @param Event_Hydration_DTO|null $dto Event hydration DTO.
     * @param Competition|null         $competition
     */
    public function __construct(
        ?Event_Hydration_DTO $dto = null,
        ?Competition $competition = null
    ) {
        if ( null === $dto ) {
            return;
        }
        $columns = array(
            'id'             => $dto->id,
            'name'           => $dto->name,
            'type'           => $dto->type,
            'num_sets'       => $dto->num_sets,
            'num_rubbers'    => $dto->num_rubbers,
            'competition_id' => $dto->competition_id,
            'settings'       => $dto->settings,
            'seasons'        => $dto->seasons,
            'settings_keys'  => array_keys( $dto->settings ),
        );

        foreach ( array_merge( $columns, $dto->settings ) as $key => $value ) {
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key ?? array(), (array) $value );
            } else {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
        }

        $this->name        = stripslashes( $this->name ?? '' );
        $this->type        = empty( $this->type ) ? '' : stripslashes( $this->type );
        if ( null !== $competition ) {
            $this->competition = $competition;
            if ( isset( $competition->id ) && (int) $competition->id > 0 && 0 === (int) ( $this->competition_id ?? 0 ) ) {
                $this->competition_id = (int) $competition->id;
            }
        }
        if ( ! isset( $this->reverse_rubbers ) ) {
            $this->reverse_rubbers = '0';
        }

        // Seasons handling (Option B for Event): keep property as JSON string; do not reindex or mutate stored structure
        if ( empty( $this->seasons ) ) {
            $this->seasons = '[]';
        } elseif ( is_array( $this->seasons ) ) {
            // Normalize arrays to JSON string
            $this->seasons = Json_Encoder::encode( $this->seasons );
        } elseif ( is_string( $this->seasons ) ) {
            // If the string looks serialized, try to unserialize and convert to JSON
            $maybe = self::decode_serialized_array( $this->seasons );
            if ( is_array( $maybe ) ) {
                $this->seasons = Json_Encoder::encode( $maybe );
            }
        }
        $this->num_seasons = count( $this->get_seasons() );
        if ( $this->competition ) {
            $this->standings = $this->competition->standings ?? [];
            // set season to latest.
            if ( $this->num_seasons > 0 ) {
                $season = empty( $this->competition->current_season['name'] ) ? null : $this->competition->current_season['name'];
                $this->set_season( $season );
            }

            // Championship.
            if ( isset( $this->competition->settings ) && is_object( $this->competition->settings ) && method_exists( $this->competition->settings, 'get' ) ) {
                if ( 'championship' === $this->competition->settings->get('mode') ) {
                    $this->is_championship = true;
                }
            } elseif ( isset( $this->competition->settings['mode'] ) && 'championship' === $this->competition->settings['mode'] ) {
                $this->is_championship = true;
            }
            $comp_type = $this->competition->type instanceof Competition_Type ? $this->competition->type->value : (string) $this->competition->type;
            if ( ( 'league' === $comp_type || Competition_Type::LEAGUE === $this->competition->type ) && ! empty( $this->competition->is_player_entry ) ) {
                $this->is_box = true;
            }
        }
    }

    /**
     * Hydrate from a raw row through the validating factory.
     *
     * @param object           $row         Raw row (e.g. `$wpdb` result or stdClass fixture).
     * @param Competition|null $competition Optional parent competition.
     *
     * @return self
     */
    public static function from_object( object $row, ?Competition $competition = null ): self {
        return Event_Factory::from_object( $row, $competition );
    }

    /**
     * Hydrate from an associative array through the validating factory.
     *
     * @param array            $data        Row data.
     * @param Competition|null $competition Optional parent competition.
     *
     * @return self
     */
    public static function from_array( array $data, ?Competition $competition = null ): self {
        return Event_Factory::from_array( $data, $competition );
    }

    /** Decode only legacy PHP-serialized arrays; leave JSON and malformed values unchanged. */
    private static function decode_serialized_array( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
            return $value;
        }
        return @unserialize( $serialized );
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
        return Json_Encoder::encode( $this->get_seasons() );
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
    public function set_name( string $name ): void {
        $this->name = $name;
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
     * @param string|null $season season.
     * @param boolean $force_overwrite force overwrite.
     * @param boolean $strict strict mode (unknown explicit season yields false instead of falling back).
     */
    public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {
        $seasons_list = $this->get_seasons();
        // Build a local index by name for lookup without mutating storage
        $by_name = array();
        foreach ( $seasons_list as $s ) {
            if ( isset( $s['name'] ) ) {
                $by_name[ $s['name'] ] = $s;
            }
        }
        if ( ! empty( $season ) && ( true === $force_overwrite || isset( $by_name[ $season ] ) ) ) {
            $data = $by_name[ $season ] ?? null;
        } elseif ( ! empty( $season ) && true === $strict ) {
            $data = false;
        } else {
            $data = null;
        }
        if ( null === $data ) {
            $tmp = $seasons_list;
            $data = ! empty( $tmp ) ? end( $tmp ) : null;
        }

        if ( is_array( $data ) ) {
            $this->num_match_days = $data['num_match_days'] ?? ( isset( $data['match_days'] ) && is_array( $data['match_days'] ) ? count( $data['match_days'] ) : ( isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0 ) );
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
    public function get_season_event( false|string $season = false, bool $index = false ): false|array|string {
        $seasons_list = $this->get_seasons();
        $by_name = array();
        foreach ( $seasons_list as $s ) {
            if ( isset( $s['name'] ) ) {
                $by_name[ $s['name'] ] = $s;
            }
        }
        if ( $season && isset( $by_name[ $season ] ) ) {
            $data = $by_name[ $season ];
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
     * Update seasons
     *
     * @param array $seasons season data.
     */
    public function update_seasons( array $seasons ): bool {
        $current = $this->get_seasons();
        if ( $current !== $seasons ) {
            // Keep property as JSON string
            $this->seasons = Json_Encoder::encode( $seasons );
            return true;
        } else {
            return false;
        }
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

    /**
     * Set Configuration
     *
     * @param object $config configuration details.
     * @return bool
     */
    public function set_config( object $config ): bool {
        $updates   = false;
        $settings = new stdClass();
        $age_limit = isset( $config->age_limit ) ? $config->age_limit : ( $this->age_limit ?? null );
        if ( ( $this->age_limit ?? null ) !== $age_limit ) {
            $updates = true;
        }
        $settings->age_limit = $age_limit;
        $this->age_limit     = $age_limit;

        $age_offset = isset( $config->age_offset ) ? $config->age_offset : ( $this->age_offset ?? null );
        if ( ( $this->age_offset ?? null ) !== $age_offset ) {
            $updates = true;
        }
        $settings->age_offset = $age_offset;
        $this->age_offset     = $age_offset;

        $scoring = isset( $config->scoring ) ? $config->scoring : ( $this->scoring ?? null );
        if ( ( $this->scoring ?? null ) !== $scoring ) {
            $updates = true;
        }
        $settings->scoring = $scoring;
        $this->scoring     = $scoring;

        $is_tournament   = (bool) ( $this->competition && ( $this->competition->is_tournament ?? false ) );
        $is_championship = (bool) ( $this->competition && ( $this->competition->is_championship ?? false ) );
        $is_league       = (bool) ( $this->competition && ( $this->competition->is_league ?? false ) );
        $is_team_entry   = (bool) ( $this->competition && ( $this->competition->is_team_entry ?? false ) );

        if ( ! $is_tournament ) {
            $offset = isset( $config->offset ) ? $config->offset : ( $this->offset ?? null );
            if ( ( $this->offset ?? null ) !== $offset ) {
                $updates = true;
            }
            $settings->offset = $offset;
            $this->offset     = (int) $offset;
        }

        if ( ! empty( $config->primary_league ) && (int) $config->primary_league > 0 ) {
            $primary_league = (int) $config->primary_league;
        } elseif ( $is_championship || $is_tournament ) {
            $primary_league = ( ! empty( $this->primary_league ) && (int) $this->primary_league > 0 ) ? (int) $this->primary_league : null;
        } else {
            $primary_league = null;
        }

        if ( null !== $primary_league ) {
            if ( (int) ( $this->primary_league ?? 0 ) !== $primary_league ) {
                $updates = true;
            }
            $settings->primary_league = $primary_league;
            $this->primary_league     = $primary_league;
        }
        if ( $is_league ) {
            $match_days = Util_Lookup::get_match_days();
            foreach ( $match_days as $match_day => $value ) {
                $config->match_days_allowed[ $match_day ] = isset( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
                if ( ! isset( $this->match_days_allowed[ $match_day ] ) || $this->match_days_allowed[ $match_day ] !== $config->match_days_allowed[ $match_day ] ) {
                    $updates = true;
                }
            }
            $settings->match_days_allowed = $config->match_days_allowed;
        }
        if ( $is_team_entry ) {
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
        }
        if ( empty( $this->name ) || $this->name !== $config->name ) {
            $this->set_name( $config->name );
            $updates = true;
        }
        return $updates;
    }
}
