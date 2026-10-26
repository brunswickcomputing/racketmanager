<?php
declare( strict_types=1 );
/**
 * League API: League class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage League
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Shared\Serialized_Column;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use stdClass;

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
    public string $title = '';
    /**
     * League name
     *
     * @var string
     */
    public string $name = '';

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
     * Number of teams to advance
     *
     * @var int|null
     */
    public ?int $num_advance = null;

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
    public string|array $settings = array();
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
    public int $event_id = 0;

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
     * Get the competition type.
     *
     * @return string
     */
    public function get_competition_type(): string {
        return $this->competition_type ?: 'league';
    }

    /**
     * Type
     *
     * @var string
     */
    public string $type = '';

    /**
     * Event
     *
     * @var Event|null
     */
    public ?Event $event = null;

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
     * Constructor
     *
     * @param object     $league League object.
     * @param Event|null $event Optional event object.
     */
    public function __construct(
        object $league,
        ?Event $event = null
    ) {
        if ( isset( $league->settings ) ) {
            $league->settings = (array) self::decode_serialized_array( $league->settings );
            $league           = (object) array_merge( (array) $league, $league->settings );
        } else {
            $league->settings = array();
        }

        foreach ( get_object_vars( $league ) as $key => $value ) {
            if ( 'event' === $key ) {
                continue;
            }
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key ?? array(), (array) $value );
            } else {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
        }

        if ( null !== $event ) {
            $this->event    = $event;
            $this->event_id = (int) ( $event->id ?? 0 );
        } elseif ( isset( $league->event_id ) && is_numeric( $league->event_id ) ) {
            $this->event_id = (int) $league->event_id;
        }

        $this->title = stripslashes( $this->title ?? '' );
        if ( empty( $this->name ) ) {
            $this->name = $this->title;
        } else {
            $this->name = stripslashes( $this->name );
        }

        // set seasons.
        if ( empty( $this->seasons ) ) {
            $this->seasons = array();
        }
        $this->seasons     = (array) self::decode_serialized_array( $this->seasons );
        $event_seasons     = $this->event ? $this->event->get_seasons() : array();
        $this->num_seasons = count( $event_seasons );
        // set season to latest.
        $this->set_season();
        $this->groups          = trim( $this->groups ?? '' );
        $this->mode            = $this->event && isset( $this->event->competition->settings['mode'] ) ? $this->event->competition->settings['mode'] : ( $this->mode ?? 'default' );
        $this->num_sets        = (int) ( $this->event->num_sets ?? 3 );
        $this->num_sets_to_win = (int) floor( $this->num_sets / 2 ) + 1;
        $this->num_rubbers     = (int) ( $this->event->num_rubbers ?? 0 );
        $this->type            = $this->event->type ?? '';
        $comp_type             = $this->event && isset( $this->event->competition->type ) ? $this->event->competition->type : '';
        $this->competition_type = $comp_type instanceof \BackedEnum ? $comp_type->value : (string) $comp_type;
        $this->point_rule      = $this->event && isset( $this->event->competition->settings['point_rule'] ) ? $this->event->competition->settings['point_rule'] : 'three';
        $this->sport           = $this->event && isset( $this->event->competition->settings['sport'] ) ? $this->event->competition->settings['sport'] : 'tennis';
        $this->scoring         = $this->event->scoring ?? null;
        $this->set_match_query_args();
        $this->num_matches_total = 0;
        $this->num_teams_total   = 0;
        $this->num_teams         = 0;
        $this->standings      = $this->event->standings ?? [];
        $this->point_format   = $this->event && isset( $this->event->competition->settings['point_format'] ) ? $this->event->competition->settings['point_format'] : '%d-%d';
        $this->point_2_format = $this->event && isset( $this->event->competition->settings['point_2_format'] ) ? $this->event->competition->settings['point_2_format'] : '%d-%d';
        // set default standings display options for additional team fields.
        if ( isset( $this->fields_team ) && is_countable( $this->fields_team ) && count( $this->fields_team ) > 0 ) {
            foreach ( $this->fields_team as $key => $data ) {
                if ( ! isset( $this->standings[ $key ] ) ) {
                    $this->standings[ $key ] = 1;
                }
            }
        }
        $this->current_team     = 0;
        $this->is_selected_team = false;
        $this->current_match    = 0;
        $this->is_selected_match = false;
        // Championship.
        if ( 'championship' === ($this->mode ?? '') ) {
            $this->is_championship = true;
        }
    }


	public function __serialize(): array {
		return get_object_vars( $this );
	}

	public function __unserialize( array $data ): void {
		foreach ( $data as $key => $value ) {
			$this->$key = $value;
		}
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
        $this->title = $title;
        $this->name  = $title;
        if ( ! empty( $sequence ) ) {
            $this->sequence = $sequence;
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
        if ( isset( $this->match_query_args_types[ $key ] ) ) {
            if ( 'numeric' === $this->match_query_args_types[ $key ] ) {
                $v = intval( $value );
            }
            if ( 'boolean' === $this->match_query_args_types[ $key ] ) {
                $v = intval( $value ) === 1;
            }
        }
        if ( isset( $this->match_query_args[ $key ] ) && is_array( $this->match_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $v ) ) {
                $v = array( $v );
            }
            $this->match_query_args[ $key ] = array_merge( $this->match_query_args[ $key ], $v );
        } else {
            $this->match_query_args[ $key ] = $v;
        }
    }
    /**
     * Get the current match query arguments.
     *
     * @return array
     */
    public function get_match_query_args(): array {
        return $this->match_query_args;
    }

    /**
     * Restore previously captured match query arguments (used to roll back on failure).
     *
     * @param array $query_args Arguments previously returned by get_match_query_args().
     */
    public function restore_match_query_args( array $query_args ): void {
        $this->match_query_args = $query_args;
    }

    /**
     * Get the current team query arguments.
     *
     * @return array
     */
    public function get_team_query_args(): array {
        return $this->team_query_args;
    }

    /**
     * Restore previously captured team query arguments (used to roll back on failure).
     *
     * @param array $query_args Arguments previously returned by get_team_query_args().
     */
    public function restore_team_query_args( array $query_args ): void {
        $this->team_query_args = $query_args;
    }

    /**
     * Get the query arguments used for pagination links.
     *
     * @return array
     */
    public function get_query_args(): array {
        return $this->query_args;
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
        if ( isset( $this->team_query_args_types[ $key ] ) ) {
            if ( 'numeric' === $this->team_query_args_types[ $key ] ) {
                $value = intval( $value );
            }
            if ( 'boolean' === $this->team_query_args_types[ $key ] ) {
                $value = intval( $value ) === 1;
            }
        }

        if ( isset( $this->team_query_args[ $key ] ) && is_array( $this->team_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }
            $this->team_query_args[ $key ] = array_merge( $this->team_query_args[ $key ], $value );
        } else {
            $this->team_query_args[ $key ] = $value;
        }
    }

    /**
     * Set current season (in-memory state update)
     *
     * @param false|string $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_season( false|string $season = false, bool $force_overwrite = false ): void {
        // Build a safe seasons array from Event (JSON-backed) and an index by name
        $seasons_list = ( isset( $this->event ) && is_object( $this->event ) && method_exists( $this->event, 'get_seasons' ) )
            ? $this->event->get_seasons()
            : ( is_array( $this->seasons ) ? $this->seasons : array() );
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

        if ( ! empty( $season ) && ( true === $force_overwrite || isset( $by_name[ $season ] ) ) ) {
            $data = $by_name[ $season ] ?? null;
        } else {
            $tmp  = $seasons_list;
            if ( is_array( $tmp ) ) {
                ksort( $tmp ); // Sort by year key (e.g. "2024", "2025", "2026")
                $data = end( $tmp );
            } else {
                $data = null;
            }
        }

        if ( empty( $data ) ) {
            $tmp  = $seasons_list;
            if ( is_array( $tmp ) ) {
                ksort( $tmp );
                $data = end( $tmp );
            } else {
                $data = null;
            }
        }

        // Fix for inconsistent season data where 'season' is used instead of 'name'
        if ( $data && ! isset( $data['name'] ) && isset( $data['season'] ) ) {
            $data['name'] = (string) $data['season'];
        }

        if ( ! $data ) {
            $data                   = array();
            $data['name']           = 'default';
            $data['num_match_days'] = 0;
        }

        $this->current_season = $data;
        $this->num_match_days = (int) ( $data['num_match_days'] ?? 0 );

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
     * @param int $match_day match day.
     */
    public function set_match_day( int $match_day = 1 ): void {
        $this->match_day                     = $match_day;
        $this->match_query_args['match_day'] = $match_day;
    }

    /**
     * Set number of pages for matches
     *
     * @param string $which type of pagination.
     */
    public function set_num_pages( string $which = 'matches' ): void {
        if ( 'matches' === $which ) {
            $this->num_pages_matches = ( 0 === $this->num_matches_per_page ) ? 1 : (int) ceil( $this->num_matches / $this->num_matches_per_page );
        }
        if ( 'teams' === $which ) {
            $this->num_pages_teams = ( 0 === $this->num_teams_per_page ) ? 1 : (int) ceil( $this->num_teams / $this->num_teams_per_page );
        }
    }

    /**
     * Retrieve current page
     *
     * @param string $which type of pagination.
     * @param int|null $current_page
     * @return int
     */
    public function get_current_page( string $which = 'matches', ?int $current_page = null ): int {
        $this->set_num_pages( $which );

        $current_page = $current_page ?? ( 'teams' === $which ? $this->current_page_teams : $this->current_page_matches );

        if ( 'matches' === $which && $current_page > $this->num_pages_matches ) {
            $current_page = $this->num_pages_matches;
        }
        if ( 'teams' === $which && $current_page > $this->num_pages_teams ) {
            $current_page = $this->num_pages_teams;
        }
        // Prevent negative offsets.
        if ( $current_page <= 0 ) {
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
     * Add team to league
     *
     * @deprecated Use League_Query_Service::add_team(). Persistence belongs to the application service.
     *
     * @param int|string $team_id team identifier.
     * @param string $season season.
     * @param string|null $rank rank.
     * @param string|null $status status.
     * @param int|string $profile profile.
     * @return int|boolean $table_id
     */
    public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int {
        throw new \LogicException( 'League::add_team() no longer persists; use League_Query_Service::add_team().' );
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
        $rule = self::decode_serialized_array( $rule );

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
     * Set number of teams for specific league
     *
     * @param boolean $total total teams or teams per page.
     */
    public function set_num_teams( bool $total = false ): void {
        if ( ! $total ) {
            $this->num_teams = $this->num_teams_total;
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
        throw new \LogicException( 'League::get_num_teams() no longer reads from repository; use League_Query_Service::get_num_teams().' );
    }

    /**
     * Set tab in league/archive shortcodes
     *
     * @param boolean $is_archive is this an archive.
     * @param int|null $tab
     */
    public function set_tab( bool $is_archive = false, ?int $tab = null ): void {
        if ( null !== $tab ) {
            $this->archive_tab = $tab;
        }
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
    public function season_exists( string $season ): bool {
        if ( ! empty( $this->seasons ) && ( isset( $this->seasons[ $season ] ) || in_array( $season, $this->seasons, true ) ) ) {
            return true;
        }
        if ( isset( $this->event ) && $this->event instanceof Event ) {
            return (bool) $this->event->get_season_by_name( $season );
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
     * Get team rank function
     *
     * @param int $team_id team id.
     * @param string $season season.
     *
     * @return int|null team ranking.
     */
    public function get_rank( int $team_id, string $season ): ?int {
        throw new \LogicException( 'League::get_rank() no longer reads from repository; use League_Query_Service::get_rank().' );
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
        throw new \LogicException( 'League::get_status() no longer reads from repository; use League_Query_Service::get_status().' );
    }
	/**
	 * =======================
	 * Administration section
	 * =======================
	 */

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
     * Set rounds function
     *
     * @param string $season season name.
     * @param array $rounds round details.
     * @return void
     */
    public function set_rounds( string $season, array $rounds ): void {
        if ( empty( $this->seasons[ $season ] ) ) {
            $this->seasons[ $season ] = array();
        }
        $this->seasons[ $season ]['rounds'] = $rounds;
    }

    /**
     * Decode only legacy PHP-serialized arrays; leave JSON and non-array values unchanged.
     *
     * Delegates to Serialized_Column; a malformed serialized array yields `false` (as `unserialize()` did)
     * so callers casting with `(array)` and `is_array()` checks keep their historical behaviour.
     */
    private static function decode_serialized_array( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
            return $value;
        }
        $decoded = Serialized_Column::decode( $serialized );
        return is_string( $decoded ) ? false : $decoded;
    }

}
