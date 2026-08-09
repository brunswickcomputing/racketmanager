<?php
/**
 * Competition API: Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Competition
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_player;

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
     * @var Competition_Type
     */
    public Competition_Type $type;

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
        return wp_json_encode( $this->seasons->all() );
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
        $service = new \Racketmanager\Services\Competition\Competition_Season_Service();
        $data    = $service->resolve_current_season( $this, $season, $force_overwrite );
        
        $this->current_phase = $service->calculate_phase( $data );
        
        // Reset state flags
        $this->is_complete = 'end' === $this->current_phase || 'complete' === $this->current_phase;
        $this->is_started  = 'start' === $this->current_phase;
        $this->is_closed   = 'close' === $this->current_phase;
        $this->is_open     = 'open' === $this->current_phase;
        $this->is_pending  = 'pending' === $this->current_phase;

        $this->num_match_days = $data['num_match_days'] ?? 0;
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
        $repository = new \Racketmanager\Repositories\Competition_Repository();
        $events = $repository->find_events( $this->id, $args );

        $event_index = array();
        foreach ( $events as $i => $event ) {
            $event_index[ $event->id ] = $i;
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
        $repository = new \Racketmanager\Repositories\Competition_Repository();
        $teams = $repository->find_teams( $this->id, $args );

        if ( is_array( $teams ) ) {
            $this->teams = $teams;
        }

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
        $repository = new \Racketmanager\Repositories\Competition_Repository();
        
        // Handle player entry type
        if ( $this->is_player_entry ) {
            $season = $args['season'] ?? false;
            $teams = $this->get_teams( [ 'season' => $season ] );
            $players = [];
            foreach ( $teams as $team ) {
                foreach ( $team->player as $player ) {
                    $players[] = $player;
                }
            }
            $competition_players = array_unique( $players );
        } else {
            $competition_players = $repository->find_players( $this->id, $args );
        }

        if ( is_array( $competition_players ) ) {
            // Stats logic if needed (matching original get_players line 996-1006)
            if ( ! empty( $args['stats'] ) ) {
                foreach ( $competition_players as $player ) {
                    $player->matches      = $player->get_matches( $this, $this->current_season['name'], 'competition' );
                    $player->stats        = $player->get_stats();
                    $player->win_pct      = $player->stats['total']->win_pct;
                    $player->matches_won  = $player->stats['total']->matches_won;
                    $player->matches_lost = $player->stats['total']->matches_lost;
                    $player->played       = $player->stats['total']->played;
                }
                $won    = array_column( $competition_players, 'matches_won' );
                $played = array_column( $competition_players, 'played' );
                array_multisort( $won, SORT_DESC, $played, SORT_ASC, $competition_players );
            } else {
                asort( $competition_players );
            }
            $this->players = $competition_players;
        }

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
        $repository = new \Racketmanager\Repositories\Competition_Repository();
        return $repository->find_matches( $this->id, $match_args );
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
            $repository    = new \Racketmanager\Repositories\Competition_Repository();
            $repository->save( $this );
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
        
        $updates = $this->update_seasons( $seasons );
        
        if ( $updates ) {
            $service = new \Racketmanager\Services\Competition\Competition_Season_Service();
            $service->cascade_season_to_events( $this, $season );
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
        $notification_service = new \Racketmanager\Services\Competition\Competition_Notification_Service();
        return $notification_service->contact_teams( $this, $season, $email_message );
    }

    /**
     * Retrieve competition instance
     *
     * @param int|string $competition_id competition id.
     * @param string|null $search_term search.
     */
    public static function get_instance( int|string $competition_id, ?string $search_term = 'id' ) {
        $repository = new \Racketmanager\Repositories\Competition_Repository();

        if ( 'name' === $search_term ) {
            return $repository->find_by_name( (string) $competition_id );
        }

        return $repository->find_by_id( (int) $competition_id );
    }

}
