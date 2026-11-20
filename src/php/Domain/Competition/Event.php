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
use Racketmanager\Domain\Shared\Serialized_Column;
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
    public private(set) int $id = 0;

    /**
     * Event name
     *
     * @var string
     */
    public private(set) string $name = '';

    /**
     * Seasons data
     *
     * @var string|array|null
     */
    public private(set) string|array|null $seasons = array();

    /**
     * Number of seasons
     *
     * @var int
     */
    public private(set) int $num_seasons = 0;

    /**
     * Sport type
     *
     * @var string
     */
    public private(set) string $sport = 'tennis';

    /**
     * Point rule
     *
     * @var string
     */
    public private(set) string $point_rule = 'tennis';

    /**
     * Primary points format
     *
     * @var string
     */
    public private(set) string $point_format = '%d-%d';

    /**
     * Secondary points format
     *
     * @var string
     */
    public private(set) string $point_2_format = '%d-%d';

    /**
     * League mode
     *
     * @var string
     */
    public private(set) string $mode = 'default';

    /**
     * Default match starting time
     *
     * @var array
     */
    public private(set) array $default_match_start_time = array(
        'hour'    => 19,
        'minutes' => 30,
    );

    /**
     * Standings table layout settings
     *
     * @var array
     */
    public private(set) array $standings = array();

    /**
     * Number of teams ascending
     *
     * @var int
     */
    public private(set) int $num_ascend = 0;

    /**
     * Number of teams descending
     *
     * @var int
     */
    public private(set) int $num_descend = 0;

    /**
     * Number of teams for relegation
     *
     * @var int
     */
    public private(set) int $num_relegation = 0;

    /**
     * Number of teams per page in list
     *
     * @var int
     */
    public private(set) int $num_matches_per_page = 10;

    /**
     * League offsets indexed by ID
     *
     * @var array
     */
    public private(set) array $league_index = array();

    /**
     * League loop
     *
     * @var boolean
     */
    public private(set) bool $in_the_league_loop = false;

    /**
     * Current league
     *
     * @var int
     */
    public private(set) int $league_team = -1;

    /**
     * Custom team input field keys and translated labels
     *
     * @var array
     */
    public private(set) array $fields_team = array();

    /**
     * Championship flag
     *
     * @var boolean
     */
    public private(set) bool $is_championship = false;
    /**
     * Box flag
     *
     * @var boolean
     */
    public private(set) bool $is_box = false;
    /**
     * Num_rubbers
     *
     * @var int|null
     */
    public private(set) ?int $num_rubbers = null;

    /**
     * Num_sets
     *
     * @var int|null
     */
    public private(set) ?int $num_sets = null;

    /**
     * Type
     *
     * @var string
     */
    public private(set) string $type = '';

    /**
     * Current season
     *
     * @var string|array|bool|null
     */
    public private(set) string|array|bool|null $current_season = null;

    /**
     * Number of match days
     *
     * @var string|int
     */
    public private(set) string|int $num_match_days = '';

    /**
     * Number of leagues
     *
     * @var string|int
     */
    public private(set) string|int $num_leagues = '';

    /**
     * Leagues
     *
     * @var array|string
     */
    public private(set) array|string $leagues = array();

    /**
     * Settings keys
     *
     * @var string|array|null
     */
    public private(set) string|array|null $settings_keys = '';

    /**
     * Constitutions
     *
     * @var string|array
     */
    public private(set) string|array $constitutions = '';

    /**
     * Event Teams
     *
     * @var string|array
     */
    public private(set) string|array $event_teams = '';
    /**
     * Settings
     *
     * @var string|array
     */
    public private(set) string|array $settings = array();
    /**
     * Groups
     *
     * @var string|null
     */
    public private(set) ?string $groups = null;
    /**
     * Teams per group
     *
     * @var int|null
     */
    public private(set) ?int $teams_per_group = null;
    /**
     * Number to advance
     *
     * @var int|null
     */
    public private(set) ?int $num_advance = null;
    /**
     * Match place 3
     *
     * @var boolean
     */
    public private(set) bool $match_place3 = false;
    /**
     * Entry open
     *
     * @var boolean
     */
    public private(set) bool $entry_open = false;
    /**
     * Entry type
     *
     * @var string
     */
    public private(set) string $entry_type = '';
    /**
     * Clubs
     *
     * @var array
     */
    public private(set) array $clubs = array();
    /**
     * Players
     *
     * @var array
     */
    public private(set) array $players = array();
    /**
     * Competition
     *
     * @var null|object
     */
    public private(set) null|object $competition = null;
    /**
     * Competition id
     *
     * @var int
     */
    public private(set) int $competition_id = 0;
    /**
     * Age limit
     *
     * @var string|null
     */
    public private(set) ?string $age_limit = null;
    /**
     * Age offset
     *
     * @var string|null
     */
    public private(set) ?string $age_offset = null;
    /**
     * Reverse rubbers
     *
     * @var string|int|boolean|null
     */
    public private(set) string|int|bool|null $reverse_rubbers = false;
    /**
     * Scoring
     *
     * @var string|null
     */
    public private(set) ?string $scoring = null;
    /**
     * Offset
     *
     * @var int
     */
    public private(set) int $offset = 0;
    /**
     * Match days allowed
     *
     * @var array
     */
    public private(set) array $match_days_allowed = array();
    /**
     * Entries
     *
     * @var array
     */
    public private(set) array $entries = array();
    /**
     * Num entries
     *
     * @var int
     */
    public private(set) int $num_entries = 0;
    /**
     * Teams
     *
     * @var array
     */
    public private(set) array $teams = array();
    /**
     * Player
     *
     * @var object|null
     */
    public private(set) ?object $player = null;
    /**
     * Primary league
     *
     * @var int
     */
    public private(set) int $primary_league = 0;
    /**
     * Cached primary league entity
     *
     * @var League|null
     */
    public private(set) ?League $primary_league_obj = null;
    /**
     * Team count
     *
     * @var int
     */
    public private(set) int $team_count = 0;
    /**
     * Player count
     *
     * @var int
     */
    public private(set) int $player_count = 0;
    /**
     * Number of seeds
     *
     * @var int
     */
    public private(set) int $num_seeds = 0;
    /**
     * Team
     *
     * @var object|null
     */
    public private(set) ?object $team = null;
    /**
     * Team ranking
     *
     * @var string
     */
    public private(set) string $team_ranking = '';
    /**
     * Competition type
     *
     * @var string
     */
    public private(set) string $competition_type = '';
    /**
     * Number of courts available
     *
     * @var array
     */
    public private(set) array $num_courts_available = array();
    /**
     * Status
     *
     * @var string
     */
    public private(set) string $status = '';
    /** @var array<string, mixed> Storage for non-table data */
    private array $meta = [];

    /**
     * Config
     *
     * @var object|null
     */
    public private(set) ?object $config = null;

    /**
     * Parent league
     *
     * @var League|null
     */
    public private(set) ?League $parent_league = null;

    /**
     * Draw data
     *
     * @var string|null
     */
    public private(set) ?string $draw = null;

    /**
     * Event matches
     *
     * @var array
     */
    public private(set) array $matches = array();

    /**
     * Event stages
     *
     * @var array
     */
    public private(set) array $stages = array();

    /**
     * Event rounds
     *
     * @var array
     */
    public private(set) array $rounds = array();

    /**
     * Champion ID
     *
     * @var int|null
     */
    public private(set) ?int $champion = null;

    /**
     * Runner-up ID
     *
     * @var int|null
     */
    public private(set) ?int $runner_up = null;

    /**
     * Constructor.
     *
     * Hydrates from the typed DTO only: table columns are applied first, then the decoded `settings`
     * keys (settings take precedence over columns, as the legacy row merge did). `new Event()` yields
     * an empty entity.
     *
     * @param Event_Hydration_DTO|null $dto         Event hydration DTO.
     * @param Competition|null         $competition Parent competition.
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
            'matches'        => $dto->matches,
            'stages'         => $dto->stages,
            'rounds'         => $dto->rounds,
            'champion'       => $dto->champion,
            'runner_up'      => $dto->runner_up,
            'parent_league'  => $dto->parent_league,
            'draw'           => $dto->draw,
            'status'         => $dto->status,
        );

        if ( null !== $dto->primary_league ) {
            $columns['primary_league'] = $dto->primary_league;
        }

        foreach ( $columns as $key => $value ) {
            Typed_Property_Hydrator::assign( $this, $key, $value );
        }
        $this->apply_settings( $dto->settings );

        $this->name = stripslashes( $this->name ?? '' );
        $this->type = empty( $this->type ) ? '' : stripslashes( $this->type );
        if ( null !== $competition ) {
            $this->competition = $competition;
            if ( isset( $competition->id ) && (int) $competition->id > 0 && 0 === (int) ( $this->competition_id ?? 0 ) ) {
                $this->competition_id = (int) $competition->id;
            }
        }
        if ( ! isset( $this->reverse_rubbers ) ) {
            $this->reverse_rubbers = '0';
        }

        // Seasons handling (Option B for Event): keep property as JSON string; do not reindex or mutate stored structure.
        if ( empty( $this->seasons ) ) {
            $this->seasons = '[]';
        } elseif ( is_array( $this->seasons ) ) {
            // Normalize arrays to JSON string.
            $this->seasons = Json_Encoder::encode( $this->seasons );
        } elseif ( is_string( $this->seasons ) ) {
            // If the string looks serialized, try to unserialize and convert to JSON.
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
                $season = empty( $this->competition->current_season['name'] ) ? null : (string) $this->competition->current_season['name'];
                $this->set_season( $season );
            }

            // Championship.
            if ( isset( $this->competition->settings ) && is_object( $this->competition->settings ) && method_exists( $this->competition->settings, 'get' ) ) {
                if ( 'championship' === $this->competition->settings->get( 'mode' ) ) {
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

    /**
     * Decode only legacy PHP-serialized arrays; leave JSON and malformed values unchanged.
     *
     * @param mixed $value Serialized or raw string.
     * @return mixed
     */
    private static function decode_serialized_array( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
            return $value;
        }
        $unserialized = Serialized_Column::unserialize_safe( $serialized, false );
        return false === $unserialized ? $value : $unserialized;
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
        // Lazily decode JSON string; return empty array on failure.
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
     *
     * @param string|int $name Season name or year.
     * @return array|null
     */
    public function get_season_by_name( string|int $name ): ?array {
        $seasons = $this->get_seasons();
        if ( isset( $seasons[ $name ] ) && is_array( $seasons[ $name ] ) ) {
            return $seasons[ $name ];
        }
        $name_str = (string) $name;
        if ( isset( $seasons[ $name_str ] ) && is_array( $seasons[ $name_str ] ) ) {
            return $seasons[ $name_str ];
        }
        foreach ( $seasons as $season ) {
            if ( is_array( $season ) && isset( $season['name'] ) && (string) $season['name'] === $name_str ) {
                return $season;
            }
        }
        return null;
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
     * Set event ID.
     *
     * @param int|null $id Event ID.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id ?? 0;
    }

    /**
     * Set championship flag.
     *
     * @param bool $is_championship Championship flag.
     *
     * @return void
     */
    public function set_is_championship( bool $is_championship ): void {
        $this->is_championship = $is_championship;
    }

    /**
     * Set box flag.
     *
     * @param bool $is_box Box flag.
     *
     * @return void
     */
    public function set_is_box( bool $is_box ): void {
        $this->is_box = $is_box;
    }

    /**
     * Set entry open flag.
     *
     * @param bool $entry_open Entry open flag.
     *
     * @return void
     */
    public function set_entry_open( bool $entry_open ): void {
        $this->entry_open = $entry_open;
    }

    /**
     * Set event seasons.
     *
     * @param array $seasons Seasons array.
     *
     * @return void
     */
    public function set_seasons( array $seasons ): void {
        $this->seasons = $seasons;
    }

    /**
     * Set parent competition reference.
     *
     * @param object|null $competition Parent competition.
     */
    public function set_competition( ?object $competition ): void {
        $this->competition = $competition;
        if ( null !== $competition && isset( $competition->id ) && (int) $competition->id > 0 ) {
            $this->competition_id = (int) $competition->id;
        }
    }

    /**
     * Set cached primary league entity.
     *
     * @param League|null $primary_league_obj League entity.
     */
    public function set_primary_league_obj( ?League $primary_league_obj ): void {
        $this->primary_league_obj = $primary_league_obj;
    }

    /**
     * Set competition ID.
     *
     * @param int $competition_id Competition ID.
     */
    public function set_competition_id( int $competition_id ): void {
        $this->competition_id = $competition_id;
    }

    /**
     * Set event status.
     *
     * @param string $status Event status.
     */
    public function set_status( string $status ): void {
        $this->status = $status;
    }

    /**
     * Set primary league ID.
     *
     * @param int $primary_league Primary league ID.
     */
    public function set_primary_league( int $primary_league ): void {
        $this->primary_league = $primary_league;
    }

    /**
     * Set scoring format.
     *
     * @param string|null $scoring Scoring format.
     */
    public function set_scoring( ?string $scoring ): void {
        $this->scoring = $scoring;
    }

    /**
     * Set offset.
     *
     * @param int $offset Offset value.
     */
    public function set_offset( int $offset ): void {
        $this->offset = $offset;
    }

    /**
     * Set age limit.
     *
     * @param string|null $age_limit Age limit.
     */
    public function set_age_limit( ?string $age_limit ): void {
        $this->age_limit = $age_limit;
    }

    /**
     * Set age offset.
     *
     * @param string|null $age_offset Age offset.
     */
    public function set_age_offset( ?string $age_offset ): void {
        $this->age_offset = $age_offset;
    }

    /**
     * Set reverse rubbers flag.
     *
     * @param string|int|bool|null $reverse_rubbers Reverse rubbers.
     */
    public function set_reverse_rubbers( string|int|bool|null $reverse_rubbers ): void {
        $this->reverse_rubbers = $reverse_rubbers;
    }

    /**
     * Set event type.
     *
     * @param string $type Event type.
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Set number of sets.
     *
     * @param int|null $num_sets Number of sets.
     */
    public function set_num_sets( ?int $num_sets ): void {
        $this->num_sets = $num_sets;
    }

    /**
     * Set number of rubbers.
     *
     * @param int|null $num_rubbers Number of rubbers.
     */
    public function set_num_rubbers( ?int $num_rubbers ): void {
        $this->num_rubbers = $num_rubbers;
    }

    /**
     * Set current season.
     *
     * @param string|array|bool|null $season Current season data or name.
     */
    public function set_current_season( string|array|bool|null $season ): void {
        $this->current_season = $season;
    }

    /**
     * Set config object.
     *
     * @param object|null $config Config object.
     */
    public function set_config_object( ?object $config ): void {
        $this->config = $config;
    }

    /**
     * Set league count.
     *
     * @param string|int $count League count.
     */
    public function set_league_count( string|int $count ): void {
        $this->num_leagues = $count;
    }

    /**
     * Set leagues.
     *
     * @param array|string $leagues Leagues data.
     */
    public function set_leagues( array|string $leagues ): void {
        $this->leagues = $leagues;
    }

    /**
     * Set league index.
     *
     * @param array $league_index League index map.
     */
    public function set_league_index( array $league_index ): void {
        $this->league_index = $league_index;
    }

    /**
     * Set standings settings.
     *
     * @param array $standings Standings settings.
     */
    public function set_standings( array $standings ): void {
        $this->standings = $standings;
    }

    /**
     * Set clubs.
     *
     * @param array $clubs Clubs list.
     */
    public function set_clubs( array $clubs ): void {
        $this->clubs = $clubs;
    }

    /**
     * Set teams.
     *
     * @param array $teams Teams list.
     */
    public function set_teams( array $teams ): void {
        $this->teams = $teams;
    }

    /**
     * Set active team.
     *
     * @param object|null $team Team object.
     */
    public function set_team( ?object $team ): void {
        $this->team = $team;
    }

    /**
     * Set players.
     *
     * @param array $players Players list.
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Set active player.
     *
     * @param object|null $player Player object.
     */
    public function set_player( ?object $player ): void {
        $this->player = $player;
    }

    /**
     * Set event teams.
     *
     * @param string|array $event_teams Event teams.
     */
    public function set_event_teams( string|array $event_teams ): void {
        $this->event_teams = $event_teams;
    }

    /**
     * Set constitutions.
     *
     * @param string|array $constitutions Constitutions.
     */
    public function set_constitutions( string|array $constitutions ): void {
        $this->constitutions = $constitutions;
    }

    /**
     * Set mode.
     *
     * @param string $mode Event mode.
     */
    public function set_mode( string $mode ): void {
        $this->mode = $mode;
    }

    /**
     * Set sport.
     *
     * @param string $sport Sport name.
     */
    public function set_sport( string $sport ): void {
        $this->sport = $sport;
    }

    /**
     * Set point rule.
     *
     * @param string $point_rule Point rule.
     */
    public function set_point_rule( string $point_rule ): void {
        $this->point_rule = $point_rule;
    }

    /**
     * Set point format.
     *
     * @param string $point_format Point format.
     */
    public function set_point_format( string $point_format ): void {
        $this->point_format = $point_format;
    }

    /**
     * Set point 2 format.
     *
     * @param string $point_2_format Point 2 format.
     */
    public function set_point_2_format( string $point_2_format ): void {
        $this->point_2_format = $point_2_format;
    }

    /**
     * Set number of match days.
     *
     * @param string|int $num_match_days Number of match days.
     */
    public function set_num_match_days( string|int $num_match_days ): void {
        $this->num_match_days = $num_match_days;
    }

    /**
     * Set number of seasons.
     *
     * @param int $num_seasons Number of seasons.
     */
    public function set_num_seasons( int $num_seasons ): void {
        $this->num_seasons = $num_seasons;
    }

    /**
     * Set draw data.
     *
     * @param string|null $draw Draw data.
     */
    public function set_draw( ?string $draw ): void {
        $this->draw = $draw;
    }

    /**
     * Set groups.
     *
     * @param string|null $groups Groups data.
     */
    public function set_groups( ?string $groups ): void {
        $this->groups = $groups;
    }

    /**
     * Set teams per group.
     *
     * @param int|null $teams_per_group Teams per group.
     */
    public function set_teams_per_group( ?int $teams_per_group ): void {
        $this->teams_per_group = $teams_per_group;
    }

    /**
     * Set number to advance.
     *
     * @param int|null $num_advance Number to advance.
     */
    public function set_num_advance( ?int $num_advance ): void {
        $this->num_advance = $num_advance;
    }

    /**
     * Set matches.
     *
     * @param array $matches Matches array.
     */
    public function set_matches( array $matches ): void {
        $this->matches = $matches;
    }

    /**
     * Set stages.
     *
     * @param array $stages Stages array.
     */
    public function set_stages( array $stages ): void {
        $this->stages = $stages;
    }

    /**
     * Set rounds.
     *
     * @param array $rounds Rounds array.
     */
    public function set_rounds( array $rounds ): void {
        $this->rounds = $rounds;
    }

    /**
     * Set champion ID.
     *
     * @param int|null $champion Champion ID.
     */
    public function set_champion( ?int $champion ): void {
        $this->champion = $champion;
    }

    /**
     * Set runner-up ID.
     *
     * @param int|null $runner_up Runner up ID.
     */
    public function set_runner_up( ?int $runner_up ): void {
        $this->runner_up = $runner_up;
    }

    /**
     * Record tournament winner and runner-up.
     *
     * @param int|null $champion_id Champion ID.
     * @param int|null $runner_up_id Runner-up ID.
     */
    public function record_winner( ?int $champion_id, ?int $runner_up_id = null ): void {
        $this->champion  = $champion_id;
        $this->runner_up = $runner_up_id;
    }

    /**
     * Set parent league.
     *
     * @param League|null $parent_league Parent league.
     */
    public function set_parent_league( ?League $parent_league ): void {
        $this->parent_league = $parent_league;
    }

    /**
     * Set a transient metadata property.
     *
     * @param string $key   Metadata key.
     * @param mixed  $value Metadata value.
     */
    public function set_meta( string $key, mixed $value ): void {
        $this->meta[ $key ] = $value;
    }

    /**
     * Retrieve a transient metadata property.
     *
     * @param string $key      Metadata key.
     * @param mixed  $fallback Default fallback value.
     *
     * @return mixed
     */
    public function get_meta( string $key, mixed $fallback = null ): mixed {
        return $this->meta[ $key ] ?? $fallback;
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
     * Recognized configuration setting keys that may be applied to aggregate state.
     *
     * @var list<string>
     */
    private const ALLOWED_SETTINGS_KEYS = array(
        'age_limit',
        'age_offset',
        'competition_type',
        'default_match_start_time',
        'entry_open',
        'entry_type',
        'fields_team',
        'groups',
        'is_box',
        'match_days_allowed',
        'match_place3',
        'mode',
        'num_advance',
        'num_ascend',
        'num_courts_available',
        'num_descend',
        'num_matches_per_page',
        'num_relegation',
        'num_rubbers',
        'num_seeds',
        'num_sets',
        'offset',
        'point_2_format',
        'point_format',
        'point_rule',
        'primary_league',
        'reverse_rubbers',
        'scoring',
        'sport',
        'standings',
        'team_ranking',
        'teams_per_group',
        'type',
    );

    /**
     * Apply persisted setting values onto the matching aggregate properties.
     *
     * Uses the same typed coercion as constructor hydration; the `settings` collection itself is left untouched.
     * Only recognized settings keys are applied, protecting aggregate identity and internal state.
     *
     * @param array $settings Setting values keyed by property name.
     */
    public function apply_settings( array $settings ): void {
        foreach ( $settings as $key => $value ) {
            if ( ! in_array( (string) $key, self::ALLOWED_SETTINGS_KEYS, true ) ) {
                continue;
            }
            if ( 'standings' === $key ) {
                $this->standings = array_merge( $this->standings, (array) $value );
            } else {
                Typed_Property_Hydrator::assign( $this, (string) $key, $value );
            }
        }
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
     * @param string|null $season          Season name.
     * @param boolean     $force_overwrite Force overwrite flag.
     * @param boolean     $strict          Strict mode flag (unknown explicit season yields false instead of falling back).
     */
    public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {
        $seasons_list = $this->get_seasons();
        // Build a local index by name for lookup without mutating storage.
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
            $tmp  = $seasons_list;
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
     * @param false|string $season Season name.
     * @param boolean      $index  Lookup flag.
     *
     * @return false|array|string
     */
    public function get_season_event( false|string $season = false, bool $index = false ): false|array|string {
        $seasons_list = $this->get_seasons();
        $by_name      = array();
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
            // Keep property as JSON string.
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
     * Update configuration
     *
     * @param object|array $config Configuration details.
     * @return bool
     */
    public function update_configuration( object|array $config ): bool {
        if ( is_array( $config ) ) {
            $config = (object) $config;
        }
        $updates   = false;
        $settings  = new stdClass();
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
            if ( ! isset( $config->match_days_allowed ) || ! is_array( $config->match_days_allowed ) ) {
                $config->match_days_allowed = array();
            }
            foreach ( $match_days as $match_day => $value ) {
                $config->match_days_allowed[ $match_day ] = ! empty( $config->match_days_allowed[ $match_day ] ) ? 1 : 0;
                if ( ! isset( $this->match_days_allowed[ $match_day ] ) || $this->match_days_allowed[ $match_day ] !== $config->match_days_allowed[ $match_day ] ) {
                    $updates = true;
                }
            }
            $settings->match_days_allowed = $config->match_days_allowed;
            $this->match_days_allowed     = $config->match_days_allowed;
        }
        if ( $is_team_entry ) {
            if ( isset( $config->num_rubbers ) ) {
                if ( empty( $this->num_rubbers ) || $this->num_rubbers !== $config->num_rubbers ) {
                    $updates = true;
                }
                $this->num_rubbers = $config->num_rubbers;
            }
            if ( isset( $config->reverse_rubbers ) ) {
                if ( empty( $this->reverse_rubbers ) || $this->reverse_rubbers !== $config->reverse_rubbers ) {
                    $updates = true;
                }
                $settings->reverse_rubbers = $config->reverse_rubbers;
                $this->reverse_rubbers     = $config->reverse_rubbers;
            }
        }
        if ( isset( $config->num_sets ) ) {
            if ( empty( $this->num_sets ) || $this->num_sets !== $config->num_sets ) {
                $updates = true;
            }
            $this->num_sets = $config->num_sets;
        }
        if ( isset( $config->type ) ) {
            if ( empty( $this->type ) || $this->type !== $config->type ) {
                $updates = true;
            }
            $this->type = $config->type;
        }
        if ( $updates ) {
            $this->settings = (array) $settings;
        }
        if ( isset( $config->name ) && ( empty( $this->name ) || $this->name !== $config->name ) ) {
            $this->set_name( $config->name );
            $updates = true;
        }
        return $updates;
    }

    /**
     * Set Configuration
     *
     * @param object $config configuration details.
     * @return bool
     */
    public function set_config( object $config ): bool {
        return $this->update_configuration( $config );
    }
}
