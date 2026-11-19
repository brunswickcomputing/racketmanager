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

use InvalidArgumentException;
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
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * Competition name
     *
     * @var string
     */
    public private(set) string $name;

    /**
     * Seasons data
     *
     * @var Season_Collection
     */
    public private(set) Season_Collection $seasons;

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
     * Team ranking mode
     *
     * @var string
     */
    public private(set) string $team_ranking = 'auto';

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
     * Finals
     *
     * @var array
     */
    public private(set) array $finals = array();

    /**
     * Standings table layout settings
     *
     * @var array
     */
    public private(set) array $standings = array(
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
    public private(set) int $num_matches_per_page = 10;

    /**
     * Event offsets indexed by ID
     *
     * @var array
     */
    public private(set) array $event_index = array();

    /**
     * Championship flag
     *
     * @var boolean
     */
    public private(set) bool $is_championship = false;

    /**
     * Type
     *
     * @var Competition_Type|null
     */
    public private(set) Competition_Type $type = Competition_Type::LEAGUE;

    /**
     * Current season
     *
     * @var array|false
     */
    public private(set) array|false $current_season = array();

    /**
     * Number of match days
     *
     * @var int
     */
    public private(set) int $num_match_days = 0;

    /**
     * Events
     *
     * @var array
     */
    public private(set) array $events = array();

    /**
     * Settings
     *
     * @var Competition_Settings
     */
    public private(set) Competition_Settings $settings;

    /**
     * Entry type
     *
     * @var string
     */
    public private(set) string $entry_type = '';

    /**
     * Cup flag
     *
     * @var boolean
     */
    public private(set) bool $is_cup = false;

    /**
     * Tournament flag
     *
     * @var boolean
     */
    public private(set) bool $is_tournament = false;

    /**
     * League flag
     *
     * @var boolean
     */
    public private(set) bool $is_league = false;

    /**
     * Team entry flag
     *
     * @var boolean
     */
    public private(set) bool $is_team_entry = false;

    /**
     * Teams
     *
     * @var array
     */
    public private(set) array $teams = array();

    /**
     * Current phase string
     *
     * @var string|null
     */
    public private(set) ?string $current_phase = null;

    /**
     * Player entry flag
     *
     * @var boolean
     */
    public private(set) bool $is_player_entry = false;

    /**
     * Players array
     *
     * @var array
     */
    public private(set) array $players = array();

    /**
     * Clubs array
     *
     * @var array
     */
    public private(set) array $clubs = array();

    /**
     * Date Open
     *
     * @var string|null
     */
    public private(set) mixed $date_open = null;

    /**
     * Date Start
     *
     * @var string|null
     */
    public private(set) mixed $date_start = null;

    /**
     * Date End
     *
     * @var string|null
     */
    public private(set) mixed $date_end = null;

    /**
     * Venue
     *
     * @var string|null
     */
    public private(set) mixed $venue = null;

    /**
     * Is complete
     *
     * @var boolean
     */
    public private(set) bool $is_complete = false;

    /**
     * Is started
     *
     * @var boolean
     */
    public private(set) bool $is_started = false;

    /**
     * Is closed
     *
     * @var boolean
     */
    public private(set) bool $is_closed = false;

    /**
     * Is pending
     *
     * @var boolean
     */
    public private(set) bool $is_pending = false;

    /**
     * Is open
     *
     * @var boolean
     */
    public private(set) bool $is_open = false;

    /**
     * Competition code
     *
     * @var string|null
     */
    public private(set) ?string $competition_code = null;

    /**
     * Is competition active
     *
     * @var boolean
     */
    public private(set) bool $is_active = false;

    /**
     * Grade
     *
     * @var string|null
     */
    public private(set) ?string $grade = null;

    /**
     * Max teams per league
     *
     * @var int|null
     */
    public private(set) ?int $max_teams = null;

    /**
     * Max teams per club in a league
     *
     * @var int|null
     */
    public private(set) ?int $teams_per_club = null;

    /**
     * Number of teams promoted and relegated
     *
     * @var int|null
     */
    public private(set) ?int $teams_prom_relg = null;

    /**
     * Lowest team to be promoted
     *
     * @var int|null
     */
    public private(set) ?int $lowest_promotion = null;

    /**
     * Default round length
     *
     * @var int|null
     */
    public private(set) ?int $round_length = null;

    /**
     * Are there match day restrictions
     *
     * @var boolean
     */
    public private(set) bool $match_day_restriction = false;

    /**
     * Are weekend matches allowed
     *
     * @var boolean
     */
    public private(set) bool $match_day_weekends = false;

    /**
     * Are match dates fixed
     *
     * @var boolean
     */
    public private(set) bool $fixed_match_dates = false;

    /**
     * Are fixtures home and away
     *
     * @var boolean
     */
    public private(set) bool $home_away = false;

    /**
     * Number of courts available by club
     *
     * @var array
     */
    public private(set) array $num_courts_available = array();

    /**
     * Scoring default format
     *
     * @var string|null
     */
    public private(set) ?string $scoring = null;

    /**
     * Number of sets default
     *
     * @var int
     */
    public private(set) int $num_sets = 0;

    /**
     * Number of rubbers default
     *
     * @var int|null
     */
    public private(set) ?int $num_rubbers = null;

    /**
     * Age group
     *
     * @var string|null
     */
    public private(set) ?string $age_group = null;

    /**
     * Reverse rubbers
     *
     * @var boolean|null
     */
    public private(set) ?bool $reverse_rubbers = null;

    /**
     * Home away difference
     *
     * @var int|null
     */
    public private(set) ?int $home_away_diff = null;

    /**
     * Filler weeks
     *
     * @var int|null
     */
    public private(set) ?int $filler_weeks = null;

    /**
     * Match days allowed array
     *
     * @var array
     */
    public private(set) array $match_days_allowed = array();

    /**
     * Start times - weekday/weekend/min/max
     *
     * @var array|null
     */
    public private(set) ?array $start_time = null;

    /**
     * Rules
     *
     * @var array
     */
    public private(set) array $rules = array();

    /**
     * Entries
     *
     * @var int
     */
    public private(set) int $entries = 0;

    /**
     * Number of players
     *
     * @var int
     */
    public private(set) int $num_players = 0;

    /**
     * Winners
     *
     * @var array
     */
    public private(set) array $winners = array();

    /**
     * Season
     *
     * @var int
     */
    public private(set) int $season = 0;

    /**
     * Number of entries
     *
     * @var int
     */
    public private(set) int $num_entries = 0;

    /**
     * Primary league
     *
     * @var int|null
     */
    public private(set) ?int $primary_league = null;

    /**
     * Offset
     *
     * @var int
     */
    public private(set) int $offset = 0;

    /**
     * Competition type
     *
     * @var string
     */
    public private(set) string $competition_type = '';

    /**
     * Player
     *
     * @var mixed
     */
    public private(set) mixed $player = null;

    /**
     * Entry link
     *
     * @var string|null
     */
    public private(set) ?string $entry_link = null;

    /**
     * Config
     *
     * @var object|null
     */
    public private(set) ?object $config = null;
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
    private string $select_count    = 'SELECT COUNT(*)';
    private string $time_zero       = ':00:00';
    /**
     * @var Competition_Hydration_DTO|null
     */
    private ?Competition_Hydration_DTO $hydration_dto = null;
    private bool $has_explicit_finals                 = false;

    /**
     * Create competition instance.
     *
     * @param string $name      Competition name.
     * @param string $type      Competition type.
     * @param string $age_group Age group.
     * @return self
     */
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
            $settings['mode']       = $mode;
            $settings['entry_type'] = $entry_type;
        }
        $settings['sport'] = 'Tennis'; // Default.

        $seasons = array();

        return new self( $name, $type, $age_group, $seasons, $settings );
    }

    /**
     * Create competition from database row.
     *
     * @param object $row Database row.
     * @return self
     */
    public static function from_database( object $row ): self {
        return Competition_Factory::from_object( $row );
    }

    /**
     * Create competition from object.
     *
     * @param object $row Object data.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Competition_Factory::from_object( $row );
    }

    /**
     * Create from DTO.
     *
     * @param Competition_Hydration_DTO $dto Hydration DTO.
     * @return self
     */
    public static function from_dto( Competition_Hydration_DTO $dto ): self {
        return Competition_Factory::create_from_dto( $dto );
    }

    /**
     * Constructor.
     *
     * @param string                  $name      Competition name.
     * @param Competition_Type|string $type      Competition type.
     * @param string                  $age_group Age group.
     * @param array|null              $seasons   Seasons array.
     * @param array|null              $settings  Settings array.
     * @param int|null                $id        Competition ID.
     */
    public function __construct(
        string $name,
        Competition_Type|string $type,
        string $age_group,
        ?array $seasons = null,
        ?array $settings = null,
        ?int $id = null
    ) {
        $this->id        = $id;
        $this->name      = $name;
        $this->type      = is_string( $type ) ? ( Competition_Type::tryFrom( $type ) ?: Competition_Type::LEAGUE ) : $type;
        $this->age_group = $age_group;

        $this->seasons  = new Season_Collection( $seasons ?? [] );
        $this->settings = new Competition_Settings( $settings ?? [] );

        // Championship.
        if ( 'championship' === $this->settings->mode() ) {
            $this->is_championship = true;
        } else {
            $this->is_championship = false;
        }
        $this->num_seasons = $this->seasons->count();

        // Set current season and date-related derived fields using effective seasons.
        if ( $this->num_seasons > 0 ) {
            $this->set_current_season();
        }
        // Handle competition flags and cup finals using new Enum and Policy.
        $this->apply_type_flags();
    }

    /**
     * Set competition name.
     *
     * @param string $name Competition name.
     * @return void
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }

    /**
     * Set age group.
     *
     * @param string|null $age_group Age group.
     * @return void
     */
    public function set_age_group( ?string $age_group ): void {
        $this->age_group = $age_group;
    }

    /**
     * Set number of courts available for a specific club.
     *
     * @param int $club_id              Club ID.
     * @param int $num_courts_available Number of courts.
     * @return void
     */
    public function set_num_courts_available( int $club_id, int $num_courts_available ): void {
        $courts                     = $this->settings->num_courts_available();
        $courts[ $club_id ]         = $num_courts_available;
        $this->num_courts_available = $courts;
        $this->settings             = $this->settings->with( 'num_courts_available', $courts );
    }

    /**
     * Replace all courts available configuration.
     *
     * @param array $courts Associative array mapping club IDs to court counts.
     * @return void
     */
    public function replace_num_courts_available( array $courts ): void {
        $this->num_courts_available = $courts;
        $this->settings             = $this->settings->with( 'num_courts_available', $courts );
    }

    /**
     * Set seasons collection.
     *
     * @param array $seasons Seasons array.
     * @return void
     */
    public function set_seasons( array $seasons ): void {
        $this->seasons     = new Season_Collection( $seasons );
        $this->num_seasons = $this->seasons->count();
    }

    /**
     * Get seasons as JSON string for UI/Shortcodes/API output.
     *
     * Note: Internal logic should continue using get_seasons() which returns array.
     */
    public function get_seasons_json(): string {
        return Json_Encoder::encode( $this->seasons->all() );
    }

    /**
     * Get competition ID.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get competition name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get competition type value.
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type->value;
    }

    /**
     * Competition type object
     *
     * @return Competition_Type
     */
    public function get_competition_type(): Competition_Type {
        return $this->type;
    }

    /**
     * Get age group.
     *
     * @return string|null
     */
    public function get_age_group(): ?string {
        return $this->age_group;
    }

    /**
     * Set competition ID.
     *
     * @param int $id Competition ID.
     * @return void
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings(): array {
        return $this->settings->all();
    }

    /**
     * Get seasons array.
     *
     * @return array
     */
    public function get_seasons(): array {
        return $this->seasons->all();
    }

    /**
     * Get a season by name
     *
     * @param string|int $name Season name or year.
     * @return array|null
     */
    public function get_season_by_name( string|int $name ): ?array {
        return $this->seasons->get( $name );
    }

    /**
     * Has a specific season
     *
     * @param string|int $name Season name or year.
     * @return bool
     */
    public function has_season( string|int $name ): bool {
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
     * Set current season.
     *
     * @param string|int $season Season name or identifier.
     * @return void
     */
    public function set_current_season( string|int $season = '' ): void {
        $data = $this->resolve_season_data( $season );
        $this->apply_current_season( $data );
    }

    /**
     * Transition the competition season to a specified phase.
     *
     * Explicitly transitions aggregate lifecycle state, overriding calendar date calculations.
     *
     * @param string|int $season Season name or identifier.
     * @param string     $phase  Target phase ('open', 'close', 'start', 'complete').
     * @return void
     * @throws InvalidArgumentException If competition has no seasons or requested season is unknown.
     */
    private function transition_season( string|int $season, string $phase ): void {
        if ( 0 === $this->seasons->count() ) {
            throw new InvalidArgumentException( 'Cannot transition season: competition has no seasons configured.' );
        }

        if ( '' !== (string) $season && ! $this->seasons->has( $season ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException( sprintf( 'Cannot transition unknown season "%s".', $season ) );
        }

        $data = $this->resolve_season_data( $season );
        if ( empty( $data ) ) {
            throw new InvalidArgumentException( 'Cannot transition season: unable to resolve target season.' );
        }

        $this->apply_season_data( $data, $phase );
    }

    /**
     * Transition the competition season to 'open' phase.
     *
     * @param string|int $season Season name or identifier.
     * @return void
     * @throws InvalidArgumentException If the competition has no seasons or the season does not exist.
     */
    public function open_season( string|int $season = '' ): void {
        $this->transition_season( $season, 'open' );
    }

    /**
     * Transition the competition season to 'close' phase.
     *
     * @param string|int $season Season name or identifier.
     * @return void
     * @throws InvalidArgumentException If the competition has no seasons or the season does not exist.
     */
    public function close_season( string|int $season = '' ): void {
        $this->transition_season( $season, 'close' );
    }

    /**
     * Transition the competition season to 'start' phase.
     *
     * @param string|int $season Season name or identifier.
     * @return void
     * @throws InvalidArgumentException If the competition has no seasons or the season does not exist.
     */
    public function start_season( string|int $season = '' ): void {
        $this->transition_season( $season, 'start' );
    }

    /**
     * Transition the competition season to 'complete' phase.
     *
     * @param string|int $season Season name or identifier.
     * @return void
     * @throws InvalidArgumentException If the competition has no seasons or the season does not exist.
     */
    public function complete_season( string|int $season = '' ): void {
        $this->transition_season( $season, 'complete' );
    }

    /**
     * Set finals configuration.
     *
     * @param array $finals Finals configuration array.
     * @return void
     */
    public function set_finals( array $finals ): void {
        $this->finals              = $finals;
        $this->has_explicit_finals = true;
    }

    /**
     * Set events.
     *
     * @param array $events Events collection.
     * @return void
     */
    public function set_events( array $events ): void {
        $this->events = $events;
    }

    /**
     * Set teams.
     *
     * @param array $teams Teams collection.
     * @return void
     */
    public function set_teams( array $teams ): void {
        $this->teams = $teams;
    }

    /**
     * Set players.
     *
     * @param array $players Players collection.
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Set player.
     *
     * @param mixed $player Player representation.
     * @return void
     */
    public function set_player( mixed $player ): void {
        $this->player = $player;
    }

    /**
     * Set clubs.
     *
     * @param array $clubs Clubs collection.
     * @return void
     */
    public function set_clubs( array $clubs ): void {
        $this->clubs = $clubs;
    }

    /**
     * Set entry link.
     *
     * @param string|null $link Entry link URL.
     * @return void
     */
    public function set_entry_link( ?string $link ): void {
        $this->entry_link = $link;
    }

    /**
     * Set competition type and update derived flags and finals.
     *
     * @param Competition_Type|string $type Competition type.
     * @return void
     */
    public function set_type( Competition_Type|string $type ): void {
        $this->type = is_string( $type ) ? ( Competition_Type::tryFrom( $type ) ?: Competition_Type::LEAGUE ) : $type;
        $this->apply_type_flags();
    }

    /**
     * Synchronize type-derived flags and cup finals.
     *
     * @return void
     */
    private function apply_type_flags(): void {
        $this->is_league       = $this->type->is_league();
        $this->is_cup          = $this->type->is_cup();
        $this->is_tournament   = $this->type->is_tournament();
        $this->is_team_entry   = $this->type->is_team_entry();
        $this->is_player_entry = $this->type->is_player_entry();
        $this->entry_type      = (string) ( $this->settings->all()['entry_type'] ?? ( $this->is_team_entry ? 'team' : 'player' ) );

        if ( ! $this->has_explicit_finals ) {
            $this->finals = $this->is_cup ? Competition_Policy::generate_cup_finals() : array();
        }
    }

    /**
     * Set standings table settings.
     *
     * @param array $standings Standings settings.
     * @return void
     */
    public function set_standings( array $standings ): void {
        $this->standings = $standings;
    }

    /**
     * Set entry type.
     *
     * @param string $entry_type Entry type (e.g. 'team' or 'player').
     * @return void
     */
    public function set_entry_type( string $entry_type ): void {
        $this->entry_type = $entry_type;
    }

    /**
     * Set current phase and synchronize phase flags.
     *
     * @param string|null $phase Phase string ('open', 'close', 'start', 'end', 'complete', 'pending').
     * @return void
     */
    public function set_current_phase( ?string $phase ): void {
        $this->current_phase = $phase;
        $this->is_complete   = 'end' === $phase || 'complete' === $phase;
        $this->is_started    = 'start' === $phase;
        $this->is_closed     = 'close' === $phase;
        $this->is_open       = 'open' === $phase;
        $this->is_pending    = 'pending' === $phase;
    }

    /**
     * Set mode and update championship flag.
     *
     * @param string $mode Mode string.
     * @return void
     */
    public function set_mode( string $mode ): void {
        $this->mode            = $mode;
        $this->is_championship = 'championship' === $mode;
    }

    /**
     * Set sport.
     *
     * @param string $sport Sport type.
     * @return void
     */
    public function set_sport( string $sport ): void {
        $this->sport = $sport;
    }

    /**
     * Set default match start time.
     *
     * @param array $start_time Default match start time array ('hour' => int, 'minutes' => int).
     * @return void
     */
    public function set_default_match_start_time( array $start_time ): void {
        $this->default_match_start_time = $start_time;
    }

    /**
     * Set point rule.
     *
     * @param string $rule Point rule.
     * @return void
     */
    public function set_point_rule( string $rule ): void {
        $this->point_rule = $rule;
    }

    /**
     * Set primary point format.
     *
     * @param string $format Point format.
     * @return void
     */
    public function set_point_format( string $format ): void {
        $this->point_format = $format;
    }

    /**
     * Set secondary point format.
     *
     * @param string $format Secondary point format.
     * @return void
     */
    public function set_point_2_format( string $format ): void {
        $this->point_2_format = $format;
    }

    /**
     * Set team ranking mode.
     *
     * @param string $ranking Team ranking mode.
     * @return void
     */
    public function set_team_ranking( string $ranking ): void {
        $this->team_ranking = $ranking;
    }

    /**
     * Set championship flag.
     *
     * @param bool $flag Championship flag.
     * @return void
     */
    public function set_is_championship( bool $flag ): void {
        $this->is_championship = $flag;
    }

    /**
     * Set cup flag.
     *
     * @param bool $flag Cup flag.
     * @return void
     */
    public function set_is_cup( bool $flag ): void {
        $this->is_cup = $flag;
    }

    /**
     * Set league flag.
     *
     * @param bool $flag League flag.
     * @return void
     */
    public function set_is_league( bool $flag ): void {
        $this->is_league = $flag;
    }

    /**
     * Set tournament flag.
     *
     * @param bool $flag Tournament flag.
     * @return void
     */
    public function set_is_tournament( bool $flag ): void {
        $this->is_tournament = $flag;
    }

    /**
     * Set team entry flag.
     *
     * @param bool $flag Team entry flag.
     * @return void
     */
    public function set_is_team_entry( bool $flag ): void {
        $this->is_team_entry = $flag;
    }

    /**
     * Set player entry flag.
     *
     * @param bool $flag Player entry flag.
     * @return void
     */
    public function set_is_player_entry( bool $flag ): void {
        $this->is_player_entry = $flag;
    }

    /**
     * Set active flag.
     *
     * @param bool $flag Active flag.
     * @return void
     */
    public function set_is_active( bool $flag ): void {
        $this->is_active = $flag;
    }

    /**
     * Set open flag.
     *
     * @param bool $flag Open flag.
     * @return void
     */
    public function set_is_open( bool $flag ): void {
        $this->is_open = $flag;
    }

    /**
     * Set closed flag.
     *
     * @param bool $flag Closed flag.
     * @return void
     */
    public function set_is_closed( bool $flag ): void {
        $this->is_closed = $flag;
    }

    /**
     * Set started flag.
     *
     * @param bool $flag Started flag.
     * @return void
     */
    public function set_is_started( bool $flag ): void {
        $this->is_started = $flag;
    }

    /**
     * Set complete flag.
     *
     * @param bool $flag Complete flag.
     * @return void
     */
    public function set_is_complete( bool $flag ): void {
        $this->is_complete = $flag;
    }

    /**
     * Set pending flag.
     *
     * @param bool $flag Pending flag.
     * @return void
     */
    public function set_is_pending( bool $flag ): void {
        $this->is_pending = $flag;
    }

    /**
     * Set number of matches per page.
     *
     * @param int $num Number of matches per page.
     * @return void
     */
    public function set_num_matches_per_page( int $num ): void {
        $this->num_matches_per_page = $num;
    }

    /**
     * Set number of match days.
     *
     * @param int $num Number of match days.
     * @return void
     */
    public function set_num_match_days( int $num ): void {
        $this->num_match_days = $num;
    }

    /**
     * Set number of seasons.
     *
     * @param int $num Number of seasons.
     * @return void
     */
    public function set_num_seasons( int $num ): void {
        $this->num_seasons = $num;
    }

    /**
     * Set number of entries.
     *
     * @param int $num Number of entries.
     * @return void
     */
    public function set_num_entries( int $num ): void {
        $this->num_entries = $num;
    }

    /**
     * Set number of players.
     *
     * @param int $num Number of players.
     * @return void
     */
    public function set_num_players( int $num ): void {
        $this->num_players = $num;
    }

    /**
     * Set number of rubbers.
     *
     * @param int|null $num Number of rubbers.
     * @return void
     */
    public function set_num_rubbers( ?int $num ): void {
        $this->num_rubbers = $num;
    }

    /**
     * Set number of sets.
     *
     * @param int $num Number of sets.
     * @return void
     */
    public function set_num_sets( int $num ): void {
        $this->num_sets = $num;
    }

    /**
     * Set round length.
     *
     * @param int|null $length Round length.
     * @return void
     */
    public function set_round_length( ?int $length ): void {
        $this->round_length = $length;
    }

    /**
     * Set filler weeks.
     *
     * @param int|null $weeks Filler weeks count.
     * @return void
     */
    public function set_filler_weeks( ?int $weeks ): void {
        $this->filler_weeks = $weeks;
    }

    /**
     * Set teams per club.
     *
     * @param int|null $teams Teams per club.
     * @return void
     */
    public function set_teams_per_club( ?int $teams ): void {
        $this->teams_per_club = $teams;
    }

    /**
     * Set teams promoted / relegated.
     *
     * @param int|null $teams Teams promoted / relegated.
     * @return void
     */
    public function set_teams_prom_relg( ?int $teams ): void {
        $this->teams_prom_relg = $teams;
    }

    /**
     * Set max teams per league.
     *
     * @param int|null $max Max teams per league.
     * @return void
     */
    public function set_max_teams( ?int $max ): void {
        $this->max_teams = $max;
    }

    /**
     * Set lowest team promoted.
     *
     * @param int|null $lowest Lowest promotion.
     * @return void
     */
    public function set_lowest_promotion( ?int $lowest ): void {
        $this->lowest_promotion = $lowest;
    }

    /**
     * Set offset.
     *
     * @param int $offset Offset value.
     * @return void
     */
    public function set_offset( int $offset ): void {
        $this->offset = $offset;
    }

    /**
     * Set home away difference.
     *
     * @param int|null $diff Home away difference.
     * @return void
     */
    public function set_home_away_diff( ?int $diff ): void {
        $this->home_away_diff = $diff;
    }

    /**
     * Set competition code.
     *
     * @param string|null $code Competition code.
     * @return void
     */
    public function set_competition_code( ?string $code ): void {
        $this->competition_code = $code;
    }

    /**
     * Set competition type string.
     *
     * @param string $type Competition type string.
     * @return void
     */
    public function set_competition_type( string $type ): void {
        $this->competition_type = $type;
    }

    /**
     * Set config object.
     *
     * @param object $config Config object.
     * @return void
     */
    public function set_config( object $config ): void {
        $this->config = $config;
    }

    /**
     * Set date end.
     *
     * @param mixed $date Date end.
     * @return void
     */
    public function set_date_end( mixed $date ): void {
        $this->date_end = $date;
    }

    /**
     * Set date open.
     *
     * @param mixed $date Date open.
     * @return void
     */
    public function set_date_open( mixed $date ): void {
        $this->date_open = $date;
    }

    /**
     * Set date start.
     *
     * @param mixed $date Date start.
     * @return void
     */
    public function set_date_start( mixed $date ): void {
        $this->date_start = $date;
    }

    /**
     * Set entries count.
     *
     * @param int $entries Entries count.
     * @return void
     */
    public function set_entries( int $entries ): void {
        $this->entries = $entries;
    }

    /**
     * Set event index.
     *
     * @param array $event_index Event index array.
     * @return void
     */
    public function set_event_index( array $event_index ): void {
        $this->event_index = $event_index;
    }

    /**
     * Set fixed match dates flag.
     *
     * @param bool $flag Fixed match dates flag.
     * @return void
     */
    public function set_fixed_match_dates( bool $flag ): void {
        $this->fixed_match_dates = $flag;
    }

    /**
     * Set grade.
     *
     * @param string|null $grade Competition grade.
     * @return void
     */
    public function set_grade( ?string $grade ): void {
        $this->grade = $grade;
    }

    /**
     * Set home away flag.
     *
     * @param bool $flag Home away flag.
     * @return void
     */
    public function set_home_away( bool $flag ): void {
        $this->home_away = $flag;
    }

    /**
     * Set match day restriction flag.
     *
     * @param bool $flag Match day restriction flag.
     * @return void
     */
    public function set_match_day_restriction( bool $flag ): void {
        $this->match_day_restriction = $flag;
    }

    /**
     * Set match day weekends flag.
     *
     * @param bool $flag Match day weekends flag.
     * @return void
     */
    public function set_match_day_weekends( bool $flag ): void {
        $this->match_day_weekends = $flag;
    }

    /**
     * Set match days allowed.
     *
     * @param array $days Match days allowed array.
     * @return void
     */
    public function set_match_days_allowed( array $days ): void {
        $this->match_days_allowed = $days;
    }

    /**
     * Set primary league ID.
     *
     * @param int|null $league Primary league ID.
     * @return void
     */
    public function set_primary_league( ?int $league ): void {
        $this->primary_league = $league;
    }

    /**
     * Set reverse rubbers flag.
     *
     * @param bool|null $flag Reverse rubbers flag.
     * @return void
     */
    public function set_reverse_rubbers( ?bool $flag ): void {
        $this->reverse_rubbers = $flag;
    }

    /**
     * Set rules.
     *
     * @param array $rules Rules array.
     * @return void
     */
    public function set_rules( array $rules ): void {
        $this->rules = $rules;
    }

    /**
     * Set scoring format string.
     *
     * @param string|null $scoring Scoring format string.
     * @return void
     */
    public function set_scoring( ?string $scoring ): void {
        $this->scoring = $scoring;
    }

    /**
     * Set season year/number.
     *
     * @param int $season Season identifier.
     * @return void
     */
    public function set_season( int $season ): void {
        $this->season = $season;
    }

    /**
     * Set start time configuration.
     *
     * @param array|null $start_time Start time configuration array.
     * @return void
     */
    public function set_start_time( ?array $start_time ): void {
        $this->start_time = $start_time;
    }

    /**
     * Set venue.
     *
     * @param mixed $venue Venue details.
     * @return void
     */
    public function set_venue( mixed $venue ): void {
        $this->venue = $venue;
    }

    /**
     * Set winners.
     *
     * @param array $winners Winners array.
     * @return void
     */
    public function set_winners( array $winners ): void {
        $this->winners = $winners;
    }

    /**
     * Apply resolved season data and update phase flags.
     *
     * @param array $data Resolved season data.
     */
    public function apply_current_season( array $data ): void {
        $this->apply_season_data( $data, self::calculate_phase( $data ) );
    }

    /**
     * Assign resolved season data and apply its lifecycle phase.
     *
     * @param array  $data  Season data.
     * @param string $phase Season lifecycle phase.
     * @return void
     */
    private function apply_season_data( array $data, string $phase ): void {
        $this->set_current_phase( $phase );
        $this->num_match_days = (int) ( $data['num_match_days'] ?? 0 );
        $this->current_season = $data;
    }

    /**
     * Pure domain helper to resolve season data.
     *
     * @param string|int $season Season name or identifier.
     * @return array
     */
    public function resolve_season_data( string|int $season = '' ): array {
        $seasons = $this->seasons;
        $data    = null;

        // 1. Resolve which season to use.
        if ( ! empty( $season ) ) {
            $data = $seasons->get( $season );
        }

        // 2. Fallback to active or latest season if not resolved.
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

        // 3. Hydrate basic dates if missing.
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
     * @param array $season_data Season data.
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
     * @param array $season_data Season data.
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
                        ucwords( $event ) === $candidate->name ||
                        0 === strcasecmp( $candidate->name, $event ) ||
                        0 === strcasecmp( $candidate->name, $clean_name ) ||
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
            // Entry validation: null/unset means all season events; explicit [] means closed/none.
            if ( ! is_array( $season_config ) || ! array_key_exists( 'entry_events', $season_config ) || is_null( $entry_events ) ) {
                return true;
            }
            $entry_event_ids = is_array( $entry_events ) ? array_map( 'intval', $entry_events ) : array();
            return in_array( $event_id, $entry_event_ids, true );
        }

        // Frontend viewing: null/unset or empty [] means all season events allowed.
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

        // Normalize input to array.
        if ( is_string( $seasons ) ) {
            $decoded = json_decode( $seasons, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $seasons = $decoded;
            } else {
                // Invalid JSON string; do not persist.
                return false;
            }
        }

        // Compare to current decoded seasons.
        $current = $this->get_seasons();
        if ( $current !== $seasons ) {
            $this->set_seasons( $seasons );
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
