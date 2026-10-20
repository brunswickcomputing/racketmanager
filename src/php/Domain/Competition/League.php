<?php
/**
 * League API: League class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage League
 */

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result_Factory;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function get_query_var;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_league_team;
use function Racketmanager\get_player;
use function Racketmanager\get_team;
use function Racketmanager\show_score;
use function Racketmanager\show_standings;
use function Racketmanager\withdrawn_team_email;

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
    public string|array $settings;
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
    public int $event_id;

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
     * @var Event
     */
    public Event $event;

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
     * Retrieve league instance.
     *
     * Compatibility helper: retrieves a League by ID or name using the passed repository
     * or a fallback repository instance. Domain methods do not consult the container.
     *
     * @param int|string $league_id league id.
     * @param League_Repository_Interface|null $league_repository
     * @return League|false
     */
    public static function get_instance( int|string $league_id, ?League_Repository_Interface $league_repository = null ): League|false {
        if ( ! $league_id ) {
            return false;
        }
        if ( null === $league_repository ) {
            global $racketmanager;
            $league_repository = isset( $racketmanager->container ) && $racketmanager->container->has( 'league_repository' )
                ? $racketmanager->container->get( 'league_repository' )
                : null;
        }
        if ( null === $league_repository ) {
            return false;
        }

        if ( is_numeric( $league_id ) ) {
            $league = $league_repository->find_by_id( (int) $league_id );
        } else {
            $league = $league_repository->find_by_name( (string) $league_id );
        }
        return $league ?: false;
    }

    public function __serialize(): array {
        $data = get_object_vars( $this );
        unset(
            $data['league_repository'],
            $data['fixture_repository'],
            $data['rubber_repository'],
            $data['standings_service'],
            $data['league_schedule_service'],
            $data['competition_notification_service'],
            $data['fixture_service'],
            $data['fixture_result_manager'],
            $data['notification_service']
        );
        return $data;
    }

    public function __unserialize( array $data ): void {
        foreach ( $data as $key => $value ) {
            $this->$key = $value;
        }
        $this->league_repository = null;
        $this->fixture_repository = null;
        $this->rubber_repository = null;
        $this->standings_service = null;
        $this->league_schedule_service = null;
        $this->competition_notification_service = null;
        $this->fixture_service = null;
        $this->fixture_result_manager = null;
        $this->notification_service = null;
    }

    private ?League_Repository_Interface $league_repository = null;
    private ?Fixture_Repository_Interface $fixture_repository = null;
    private ?Rubber_Repository_Interface $rubber_repository = null;
    private ?Standings_Service $standings_service = null;
    private ?League_Schedule_Service $league_schedule_service = null;
    private ?Competition_Notification_Service $competition_notification_service = null;
    private ?Fixture_Service $fixture_service = null;
    private ?Fixture_Result_Manager $fixture_result_manager = null;
    private ?Notification_Service $notification_service = null;

    /**
     * Constructor
     *
     * @param object|null $league League object.
     * @param League_Repository_Interface|null $league_repository
     * @param Fixture_Repository_Interface|null $fixture_repository
     * @param Standings_Service|null $standings_service
     * @param League_Schedule_Service|null $league_schedule_service
     * @param Competition_Notification_Service|null $competition_notification_service
     * @param Fixture_Service|null $fixture_service
     * @param Fixture_Result_Manager|null $fixture_result_manager
     * @param Notification_Service|null $notification_service
     */
    public function __construct(
        object $league,
        ?League_Repository_Interface $league_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?Standings_Service $standings_service = null,
        ?League_Schedule_Service $league_schedule_service = null,
        ?Competition_Notification_Service $competition_notification_service = null,
        ?Fixture_Service $fixture_service = null,
        ?Fixture_Result_Manager $fixture_result_manager = null,
        ?Notification_Service $notification_service = null
    ) {
        $this->league_repository = $league_repository;
        $this->fixture_repository = $fixture_repository;
        $this->standings_service = $standings_service;
        $this->league_schedule_service = $league_schedule_service;
        $this->competition_notification_service = $competition_notification_service;
        $this->fixture_service = $fixture_service;
        $this->fixture_result_manager = $fixture_result_manager;
        $this->notification_service = $notification_service;

        if ( isset( $league->settings ) ) {
            $league->settings = (array) maybe_unserialize( $league->settings );
            $league           = (object) array_merge( (array) $league, $league->settings );
        } else {
            $league->settings = array();
        }

        foreach ( get_object_vars( $league ) as $key => $value ) {
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key, $value );
            } else {
                $this->$key = $value;
            }
        }

        $this->title = stripslashes( $this->title );
        $this->name  = $this->title;
        $event       = get_event( $this->event_id );
        $this->event = $event;
        // set seasons.
        if ( empty( $this->seasons ) ) {
            $this->seasons = array();
        }
        $this->seasons     = (array) maybe_unserialize( $this->seasons );
        $event_seasons     = $this->event->get_seasons();
        $this->num_seasons = count( $event_seasons );
        // set season to latest.
        $this->set_season();
        $this->groups          = trim( $this->groups ?? '' );
        $this->mode            = $event->competition->settings['mode'] ?? 'default';
        $this->num_sets        = (int) ( $event->num_sets ?? 3 );
        $this->num_sets_to_win = floor( $this->num_sets / 2 ) + 1;
        $this->num_rubbers     = (int) ( $event->num_rubbers ?? 0 );
        $this->type            = $event->type ?? '';
        $this->competition_type = $event->competition->type instanceof \BackedEnum ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
        $this->point_rule      = $event->competition->settings['point_rule'] ?? 'three';
        $this->sport           = $event->competition->settings['sport'] ?? 'tennis';
        $this->scoring         = $event->scoring ?? null;
        $this->set_match_query_args();
        $this->set_num_matches(); // for pagination.
        $this->set_num_teams( true ); // get total number of teams.
        $this->standings      = $event->standings ?? [];
        $this->point_format   = $event->competition->settings['point_format'] ?? '%d-%d';
        $this->point_2_format = $event->competition->settings['point_2_format'] ?? '%d-%d';
        // set default standings display options for additional team fields.
        if ( count( $this->fields_team ) > 0 ) {
            foreach ( $this->fields_team as $key => $data ) {
                if ( ! isset( $this->standings[ $key ] ) ) {
                    $this->standings[ $key ] = 1;
                }
            }
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // set selected team marker.
        if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
            $this->current_team     = intval( $_GET[ 'team_' . $this->id ] );
            $this->is_selected_team = true;
        }

        // set selected match marker.
        if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
            $this->current_match     = intval( $_GET[ 'match_' . $this->id ] );
            $this->is_selected_match = true;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        // Championship.
        if ( 'championship' === ($this->mode ?? '') ) {
            $this->is_championship = true;
            $settings              = Championship_Settings::from_array( $this->settings );
            $factory               = new Championship_Factory();
            $this->championship    = $factory->create( $this, $settings );
        }

        // add actions & filter.
        add_filter( 'racketmanager_import_matches_' . $this->sport, array( &$this, 'import_matches' ), 10, 4 );
        add_filter( 'racketmanager_import_teams_' . $this->sport, array( &$this, 'import_teams' ), 10, 3 );
    }

    public function set_league_repository( ?League_Repository_Interface $league_repository ): self {
        $this->league_repository = $league_repository;
        return $this;
    }

    public function set_fixture_repository( ?Fixture_Repository_Interface $fixture_repository ): self {
        $this->fixture_repository = $fixture_repository;
        return $this;
    }

    public function set_rubber_repository( ?Rubber_Repository_Interface $rubber_repository ): self {
        $this->rubber_repository = $rubber_repository;
        return $this;
    }

    public function set_standings_service( ?Standings_Service $standings_service ): self {
        $this->standings_service = $standings_service;
        return $this;
    }

    public function set_league_schedule_service( ?League_Schedule_Service $league_schedule_service ): self {
        $this->league_schedule_service = $league_schedule_service;
        return $this;
    }

    public function set_competition_notification_service( ?Competition_Notification_Service $competition_notification_service ): self {
        $this->competition_notification_service = $competition_notification_service;
        return $this;
    }

    public function set_fixture_service( ?Fixture_Service $fixture_service ): self {
        $this->fixture_service = $fixture_service;
        return $this;
    }

    public function set_fixture_result_manager( ?Fixture_Result_Manager $fixture_result_manager ): self {
        $this->fixture_result_manager = $fixture_result_manager;
        return $this;
    }

    public function set_notification_service( ?Notification_Service $notification_service ): self {
        $this->notification_service = $notification_service;
        return $this;
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
        if ( ! empty( $sequence ) ) {
            $this->sequence = $sequence;
        }
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot update League: League_Repository_Interface collaborator is required.' );
        }
        $this->league_repository->update_title_and_sequence( $this->id, $this->title, $this->sequence );
        wp_cache_set( $this->id, $this, 'leagues' );
    }

    /**
     * Delete League
     */
    public function delete(): void {
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot delete League: League_Repository_Interface collaborator is required.' );
        }
        $matches = $this->get_matches( array() );
        if ( $matches ) {
            $this->delete_matches( $matches );
        }
        // remove tables.
        $this->league_repository->delete_league_teams( $this->id );
        $this->league_repository->delete( $this->id );
        wp_cache_delete( $this->id, 'leagues' );
    }
    /**
     * Delete team from League
     *
     * @param integer $team team id.
     * @param string $season season.
     */
    public function delete_team( int $team, string $season ): void {
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot delete team from League: League_Repository_Interface collaborator is required.' );
        }
        $matches = $this->get_matches(
            array(
                'team_id' => $team,
                'season'  => $season,
                'final'   => 'all',
            )
        );
        if ( $matches && $this->fixture_repository ) {
            foreach ( $matches as $match ) {
                $fixture_id = is_object( $match ) ? (int) $match->id : (int) $match;
                $this->fixture_repository->delete( $fixture_id );
            }
        }
        // remove tables.
        $this->league_repository->delete_team( $this->id, $team, $season );
        wp_cache_delete( $team, '$league_team' );
        wp_cache_delete( $this->id, 'leaguetable' );
    }
    /**
     * Withdraw team from League
     *
     * @param integer $team_id team id.
     * @param string $season season.
     */
    public function withdraw_team( int $team_id, string $season ): void {
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot withdraw team from League: League_Repository_Interface collaborator is required.' );
        }
        $match_args                     = array();
        $match_args['team_id']          = $team_id;
        $match_args['season']           = $season;
        $match_args['reset_query_args'] = true;
        if ( $this->is_championship ) {
            $match_args['pending'] = true;
        }
        // update matches.
        $matches = $this->get_matches( $match_args );
        if ( $matches ) {
            if ( ! $this->fixture_repository ) {
                throw new \RuntimeException( 'Cannot withdraw team from League: Fixture_Repository_Interface collaborator is required.' );
            }
            if ( ! $this->fixture_result_manager ) {
                throw new \RuntimeException( 'Cannot withdraw team from League: Fixture_Result_Manager collaborator is required.' );
            }
            foreach ( $matches as $match_item ) {
                $fixture_id = is_object( $match_item ) ? (int) $match_item->id : (int) $match_item;
                $fixture    = $this->fixture_repository->find_by_id( $fixture_id );
                if ( ! $fixture ) {
                    continue;
                }

                $status_code = $fixture->get_status();
                $status      = empty( $status_code ) ? null : Util_Lookup::get_match_status( $status_code );
                if ( 'Withdrawn' !== $status ) {
                    if ( $this->is_championship ) {
                        if ( intval( $fixture->get_home_team() ) === $team_id ) {
                            $match_status = 'walkover_player2';
                        } else {
                            $match_status = 'walkover_player1';
                        }
                        if ( empty( $fixture->get_leg() ) || 2 === (int) $fixture->get_leg() ) {
                            if ( $this->notification_service ) {
                                $this->notification_service->notify_team_withdrawal( $fixture, $team_id );
                            }
                        }

                        $request = new Fixture_Result_Update_Request( $fixture->get_id(), [], $match_status, $fixture->get_confirmed() );
                        $this->fixture_result_manager->handle_fixture_result_update( $fixture, $request );
                    } else {
                        $result_data = [
                            'home_points' => 0,
                            'away_points' => 0,
                            'status'      => Util_Lookup::get_match_status_code( 'cancelled' ),
                            'custom'      => $fixture->get_custom() ?? [],
                            'sets'        => [],
                        ];
                        $result      = Result_Factory::from_array( $result_data, (string) $fixture->get_home_team(), (string) $fixture->get_away_team() );
                        $this->fixture_result_manager->confirm_result( $fixture, '', null, $result );
                    }
                }
            }
        }
        // update table.
        $this->league_repository->withdraw_team( $this->id, $team_id, $season );
        wp_cache_delete( $team_id, '$league_team' );
        wp_cache_delete( $this->id, 'leaguetable' );
        if ( ! $this->is_championship ) {
            $this->update_standings( $season );
        }
        if ( ! $this->is_championship ) {
            $this->notify_league_team_withdrawn( $team_id, $season );
        }
    }

    /**
     * Function to notify all teams in league of withdrawal
     * @param int $team_id
     * @param string $season
     *
     * @return void
     */
    protected function notify_league_team_withdrawn( int $team_id, string $season ): void {
        if ( $this->competition_notification_service ) {
            $this->competition_notification_service->notify_withdrawn_team( $this, $team_id, $season );
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
        if ( 'numeric' === $this->match_query_args_types[ $key ] ) {
            $v = intval( $value );
        }
        if ( 'boolean' === $this->match_query_args_types[ $key ] ) {
            $v = intval( $value ) === 1;
        }
        if ( is_array( $this->match_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $v ) ) {
                $v = array( $v );
            }
            $this->match_query_args[ $key ] = array_merge( $this->match_query_args[ $key ], $v );
        } else {
            $this->match_query_args[ $key ] = $v;
        }
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
        if ( 'numeric' === $this->team_query_args_types[ $key ] ) {
            $value = intval( $value );
        }
        if ( 'boolean' === $this->team_query_args_types[ $key ] ) {
            $value = intval( $value ) === 1;
        }

        if ( is_array( $this->team_query_args[ $key ] ) && ! $replace ) {
            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }
            $this->team_query_args[ $key ] = array_merge( $this->team_query_args[ $key ], $value );
        } else {
            $this->team_query_args[ $key ] = $value;
        }
    }

    /**
     * Set current season
     *
     * @param false|string $season season.
     * @param boolean $force_overwrite force overwrite.
     */
    public function set_season( false|string $season = false, bool $force_overwrite = false ): void {
        global $wp;
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
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( ! empty( $season ) && true === $force_overwrite ) {
            $data = $by_name[ $season ] ?? null;
        } elseif ( ! empty( $_POST['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['season'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $data = $by_name[ $key ] ?? false;
        } elseif ( ! empty( $_GET['season'] ) ) {
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
            $data = $by_name[ $key ] ?? false;
        } elseif ( isset( $_GET[ 'season_' . $this->id ] ) && ! empty( $_GET[ 'season_' . $this->id ] ) ) {
            $key  = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $this->id ] ) ) );
            $data = $by_name[ $key ] ?? false;
        } elseif ( isset( $wp->query_vars['season'] ) ) {
            $key  = $wp->query_vars['season'];
            $data = $by_name[ $key ] ?? false;
        } elseif ( ! empty( $season ) ) {
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
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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
        $this->num_match_days = $data['num_match_days'] ?? 0;

        $this->set_team_query_arg( 'season', $this->current_season['name'] );
        $this->set_match_query_arg( 'season', $this->current_season['name'] );

        // Refresh team count for the newly set season before rebuilding championship
        $this->set_num_teams( true );

        if ( 'championship' === ( $this->mode ?? '' ) ) {
            $settings           = Championship_Settings::from_array( $this->settings );
            $factory            = new Championship_Factory();
            $this->championship = $factory->create( $this, $settings );
        }
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
     * @param false|string $_match_day match day.
     */
    public function set_match_day( false|string $_match_day = false ): void {
        global $wp;
        if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $match_day = intval( $_GET['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $_GET[ 'match_day_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $match_day = intval( $_GET[ 'match_day_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $match_day = intval( $_POST['match_day'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        } elseif ( isset( $wp->query_vars['match_day'] ) ) {
            $match_day = get_query_var( 'match_day' );
        } elseif ( is_numeric( $_match_day ) && 0 !== $_match_day ) {
            $match_day = intval( $_match_day );
        } elseif ( 'last' === $_match_day ) {
            $match_day = wp_cache_get( 'last_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match_day_val = $this->league_repository ? $this->league_repository->get_match_day( $this->id, $this->current_season['name'], 'last' ) : null;
                if ( $match_day_val ) {
                    $match_day = $match_day_val;
                    wp_cache_set( 'last_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } elseif ( 'next' === $_match_day ) {
            $match_day = wp_cache_get( 'next_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match_day_val = $this->league_repository ? $this->league_repository->get_match_day( $this->id, $this->current_season['name'], 'next' ) : null;
                if ( $match_day_val ) {
                    $match_day = $match_day_val;
                    wp_cache_set( 'next_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } elseif ( 'current' === $_match_day || 'latest' === $_match_day ) {
            $match_day = wp_cache_get( 'current_' . $this->id, 'leagues_match_days' );
            if ( ! $match_day ) {
                $match_day_val = $this->league_repository ? $this->league_repository->get_match_day( $this->id, $this->current_season['name'], 'current' ) : null;
                if ( $match_day_val ) {
                    $match_day = $match_day_val;
                    wp_cache_set( 'current_' . $this->id, $match_day, 'leagues_match_days' );
                } else {
                    $match_day = $_match_day;
                }
            }
        } else {
            $match_day = 1;
        }
        if ( empty( $match_day ) || ! is_numeric( $match_day ) ) {
            $match_day = 1;
        }
        $this->match_day                     = intval( $match_day );
        $this->match_query_args['match_day'] = $match_day;
    }

    /**
     * Get pagination
     *
     * @param string $which type of pagination.
     *
     * @return string|null
     */
    public function get_page_links( string $which = 'matches' ): ?string {
        $this->get_current_page( $which );

        if ( 'matches' === $which ) {
            $base         = is_admin() ? 'match_paged' : 'match_paged_' . $this->id;
            $num_pages    = $this->num_pages_matches;
            $current_page = $this->current_page_matches;
            $num_items    = $this->num_matches;
        } elseif ( 'teams' === $which ) {
            $base         = is_admin() ? 'team_paged' : 'team_paged_' . $this->id;
            $num_pages    = $this->num_pages_teams;
            $current_page = $this->current_page_teams;
            $num_items    = $this->num_matches;
        } else {
            return '';
        }

        $query_args = $this->query_args;

        if ( 'matches' === $which && isset( $_POST['match_day'] ) && is_string( $_POST['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $query_args['match_day'] = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_POST['match_day'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        $page_links = paginate_links(
            array(
                'base'      => add_query_arg( $base, '%#%' ),
                'format'    => '',
                'prev_text' => '&#9668;',
                'next_text' => '&#9658;',
                'total'     => $num_pages,
                'current'   => $current_page,
                'add_args'  => $query_args,
            )
        );

        if ( $page_links && is_admin() ) {
            /* translators: %s: number of matches  */
            $page_links = sprintf( '<span class="displaying-num">' . __( '%s Matches', 'racketmanager' ) . '</span>%s', number_format_i18n( $num_items ), $page_links );
        }

        return $page_links;
    }

    /**
     * Set number of pages for matches
     *
     * @param string $which type of pagination.
     */
    public function set_num_pages( string $which = 'matches' ): void {
        if ( 'matches' === $which ) {
            $this->num_pages_matches = ( 0 === $this->num_matches_per_page ) ? 1 : ceil( $this->num_matches / $this->num_matches_per_page );
        }
        if ( 'teams' === $which ) {
            $this->num_pages_teams = ( 0 === $this->num_teams_per_page ) ? 1 : ceil( $this->num_teams / $this->num_teams_per_page );
        }
    }

    /**
     * Retrieve current page
     *
     * @param string $which type of pagination.
     * @return int
     */
    public function get_current_page( string $which = 'matches' ): int {
        global $wp;

        $this->set_num_pages( $which );

        if ( 'matches' === $which ) {
            $key = 'match_paged';
        } elseif ( 'teams' === $which ) {
            $key = 'team_paged';
        } else {
            $key = null;
        }
        if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = intval( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $wp->query_vars[ $key ] ) ) {
            $current_page = max( 1, intval( $wp->query_vars[ $key ] ) );
        } elseif ( isset( $_GET[ $key . '_' . $this->id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $current_page = intval( $_GET[ $key . '_' . $this->id ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $wp->query_vars[ $key . '_' . $this->id ] ) ) {
            $current_page = max( 1, intval( $wp->query_vars[ $key . '_' . $this->id ] ) );
        } else {
            $current_page = 1;
        }

        if ( 'matches' === $which && $current_page > $this->num_pages_matches ) {
            $current_page = $this->num_pages_matches;
        }
        if ( 'teams' === $which && $current_page > $this->num_pages_teams ) {
            $current_page = $this->num_pages_teams;
        }
        // Prevent negative offsets.
        if ( 0 === intval( $current_page ) ) {
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
     * Get teams from league from database
     *
     * @param array $query_args query_arguments.
     *
     * @return array|int database results
     */
    public function get_league_teams( array $query_args = array() ): array|int {
        $old_query_args = $this->team_query_args;

        // set query args.
        foreach ( $query_args as $key => $value ) {
            $this->set_team_query_arg( $key, $value );
        }
        $reset_query_args = $this->team_query_args['reset_query_args'];
        $get_details      = $this->team_query_args['get_details'];
        $count            = $this->team_query_args['count'];

        $repo_args = $this->team_query_args;
        $season    = $repo_args['season'] ?? '';
        if ( empty( $season ) ) {
            $repo_args['season'] = $this->current_season['name'] ?? '';
        } elseif ( 'any' === $season ) {
            $repo_args['season'] = 'any';
        } elseif ( $this->season_exists( htmlspecialchars( (string) $season ) ) ) {
            $repo_args['season'] = htmlspecialchars( (string) $season );
        } else {
            unset( $repo_args['season'] );
            $repo_args['skip_season_filter'] = true;
        }
        $teams = $this->league_repository ? $this->league_repository->query_league_teams( $this->id, $repo_args, (bool) $count ) : ( $count ? 0 : array() );

        if ( ! $count ) {
            $teams      = is_array( $teams ) ? $teams : array();
            $class      = array();
            $team_index = array();
            foreach ( $teams as $i => $team ) {
                $team    = get_league_team( $team->table_id );
                $class[] = ( 'alternate' === $class ) ? '' : 'alternate';
                $team->custom  = stripslashes_deep( maybe_unserialize( $team->custom ) );
                $team->roster  = maybe_unserialize( $team->roster );
                $team->title   = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
                $team->class   = implode( ' ', $class );
                $team->points_formatted = array(
                    'primary'   => sprintf( $this->point_format, $team->points_plus, $team->points_minus ),
                    'secondary' => sprintf( $this->point_2_format, $team->points_2_plus, $team->points_2_minus ),
                );
                if ( ! empty( $team->players ) ) {
                    $type        = substr( $this->event->type, 1, 1 );
                    $team_rating = 0;
                    foreach ( $team->players as $player ) {
                        $rating = $player->wtn[ $type ];
                        if ( is_numeric( $rating ) ) {
                            $team_rating += $rating;
                        }
                    }
                    $team->rating = $team_rating;
                }
                if ( $get_details ) {
                    $team_dtls           = $this->get_team_dtls( $team->id );
                    $team->match_day     = $team_dtls->match_day;
                    $team->match_time    = $team_dtls->match_time;
                    $team->captain_id    = $team_dtls->captain_id;
                    $team->captain_name  = $team_dtls->captain;
                    $team->contactno     = $team_dtls->contactno;
                    $team->contactemail  = $team_dtls->contactemail;
                    $team->league_status = $team_dtls->league_status;
                }

                $team_index[ $team->id ] = $i;
                $teams[ $i ]             = $team;
            }

            $this->teams      = $teams;
            $this->team_index = $team_index;

            $this->set_num_teams();
        }

        // reset team query args.
        if ( true === $reset_query_args ) {
            foreach ( $old_query_args as $key => $query_arg ) {
                $this->set_team_query_arg( $key, $query_arg );
            }

            $this->set_team_query_arg( 'reset_query_args', false );
        }

        return $teams;
    }

    /**
     * Add team to league
     *
     * @param int|string $team_id team identifier.
     * @param string $season season.
     * @param string|null $rank rank.
     * @param string|null $status status.
     * @param int|string $profile profile.
     *
     * @return int|boolean $table_id
     */
    public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int {
        global $racketmanager;
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot add team to League: League_Repository_Interface collaborator is required.' );
        }
        $valid = true;
        if ( ! is_numeric( $team_id ) ) {
            $team = get_team( $team_id );
            if ( ! $team ) {
                $team        = new stdClass();
                $team->title = $team_id;
                $team->type  = $this->type;
                if ( $this->event->competition->is_tournament ) {
                    $team->team_type = 'S';
                }
                $team = new Team( $team );
            }
            $team_id = $team->id;
        }
        $table_id = $this->league_repository->add_team_to_league(
            $this->id,
            (int) $team_id,
            $season,
            $rank,
            $status,
            (int) $profile
        );
        if ( false === $table_id ) {
            $message_text = __( 'Team already in table or error adding team', 'racketmanager' );
            $valid        = false;
        } else {
            $message_text = __( 'Table entry added', 'racketmanager' );
            $valid        = true;
        }
        if ( is_object( $racketmanager ) && method_exists( $racketmanager, 'set_message' ) ) {
            $racketmanager->set_message( $message_text, ! $valid );
        }

        return $valid ? $table_id : false;
    }

    /**
     * Get single team from League cache
     *
     * @param int $team_id team id.
     *
     * @return object|false
     */
    public function get_league_team( int $team_id ): object|false {
        if ( isset( $this->team_index[ $team_id ] ) ) {
            return $this->teams[ $this->team_index[ $team_id ] ];
        } else {
            return $this->get_team_dtls( $team_id );
        }
    }

    /**
     * Get single team
     *
     * @param int $team_id team id.
     * @param string|null $season season name (optional).
     *
     * @return object|false
     */
    public function get_team_dtls( int $team_id, ?string $season = null ): object|false {
        if ( empty( $season ) ) {
            $season = $this->current_season['name'];
        }
        if ( -1 === $team_id ) {
            $team               = (object) array(
                'id'     => -1,
                'title'  => 'Bye',
                'player' => array(),
            );
            $team->captain      = '';
            $team->contactno    = '';
            $team->contactemail = '';
            $team->club_id      = '';
            $team->stadium      = '';
            $team->roster       = '';
            return $team;
        }

        $team = $this->league_repository ? $this->league_repository->get_team_details( $this->id, $team_id, $season ) : null;

        if ( ! isset( $team ) ) {
            return false;
        }
        if ( str_contains( $team->title, '_' ) ) {
            $team_name = Util::generate_team_name( $team->title );
            if ( ! empty( $team_name ) ) {
                $team->title = $team_name;
            }
        } else {
            $team->title = htmlspecialchars( stripslashes( $team->title ), ENT_QUOTES );
        }
        $captain = get_userdata( $team->captain );
        if ( $captain ) {
            $team->captain_id   = $team->captain;
            $team->captain      = $captain->display_name;
            $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
            $team->contactemail = $captain->user_email;
        } else {
            $team->captain_id   = 0;
            $team->captain      = '';
            $team->contactno    = '';
            $team->contactemail = '';
        }
        if ( ! empty( $team->club_id ) ) {
            $team->club_id = stripslashes( $team->club_id );
            $team->club    = get_club( $team->club_id );
        } else {
            $team->club_id = null;
            $team->club    = null;
        }
        $team->stadium          = stripslashes( $team->stadium );
        $team->roster           = maybe_unserialize( $team->roster );
        $team->points_formatted = array(
            'primary'   => sprintf( $this->point_format, $team->points_plus, $team->points_minus ),
            'secondary' => sprintf( $this->point_2_format, $team->points_2_plus, $team->points_2_minus ),
        );
        if ( 'P' === $team->team_type && null !== $team->roster ) {
            $team->players = array();
            $i             = 1;
            foreach ( $team->roster as $player ) {
                $team_player           = get_player( $player );
                $team->players [ $i ]  = $team_player;
                $team->player[ $i ]    = $team_player->get_fullname() ?? '';
                $team->player_id[ $i ] = $player;
                ++$i;
            }
        }
        $team->is_withdrawn = false;
        if ( 'W' === $team->league_status ) {
            $team->is_withdrawn = true;
        }
        return $team;
    }

    /**
     * Gets matches from database
     *
     * @param array $query_args query arguments.
     *
     * @return array|int|object of matches
     */
    public function get_matches( array $query_args ): array|int|object {
        $old_query_args = $this->match_query_args;
        // set query args.
        foreach ( $query_args as $key => $value ) {
            $this->set_match_query_arg( $key, $value );
        }
        $limit            = $this->match_query_args['limit'];
        $season           = $this->match_query_args['season'];
        $count            = $this->match_query_args['count'];
        $reset_limit      = $this->match_query_args['reset_limit'];
        $reset_query_args = $this->match_query_args['reset_query_args'];
        $player           = $this->match_query_args['player'];
        $status           = $this->match_query_args['status'];

        $matches = array();

        // disable limit for championship mode.
        if ( $this->is_championship ) {
            $limit = false;
        }
        if ( ! empty( $season ) && 'any' !== $season && ! $this->season_exists( $season ) ) {
            return $matches;
        }

        $query_args_to_pass                    = $this->match_query_args;
        $query_args_to_pass['current_season']  = $this->current_season['name'] ?? '';
        $offset                                = intval( $limit > 0 ) ? ( $this->get_current_page() - 1 ) * $limit : 0;
        $query_args_to_pass['offset']          = $offset;
        $query_args_to_pass['is_championship'] = $this->is_championship;

        if ( $status ) {
            $status_code = $status['status_code'] ?? null;
            $compare     = $status['compare'] ?? null;
            if ( $status_code ) {
                $status_value = Util_Lookup::get_match_status_code( $status_code );
                if ( $status_value ) {
                    $query_args_to_pass['status_value']   = $status_value;
                    $query_args_to_pass['status_compare'] = $compare;
                }
            }
        }

        if ( $count ) {
            $this->set_match_query_arg( 'count', false );
            $matches = $this->league_repository ? $this->league_repository->query_matches( $this->id, $query_args_to_pass, true ) : 0;
        } else {
            $matches = $this->league_repository ? $this->league_repository->query_matches( $this->id, $query_args_to_pass, false ) : array();
            if ( $this->fixture_repository ) {
                $class = '';
                foreach ( $matches as $i => $match ) {
                    $match        = $this->fixture_repository->find_by_id( (int) $match->id );
                    $class        = ( 'alternate' === $class ) ? '' : 'alternate';
                    if ( $match ) {
                        $match->class = $class;
                    }
                    if ( $player && $match && $this->rubber_repository ) {
                        $match->set_rubbers( $this->rubber_repository->find_by_fixture_id( (int) $match->get_id(), (int) $player ) );
                    }

                    $matches[ $i ] = $match;
                }
                if ( 1 === $limit && $matches ) {
                    $matches = $matches[0];
                }
            }
        }

        // reset match limit.
        if ( true === $reset_limit ) {
            $this->set_match_query_arg( 'limit', $old_query_args['limit'] );
            $this->set_match_query_arg( 'reset_limit', false );
        }

        // reset match query args.
        if ( true === $reset_query_args ) {
            foreach ( $old_query_args as $key => $query_arg ) {
                $this->set_match_query_arg( $key, $query_arg );
            }
            $this->set_match_query_arg( 'reset_query_args', false );
        }

        if ( true !== $count ) {
            $this->matches = $matches;
        } else {
            $this->num_matches = $matches;
        }

        return $matches;
    }

    /**
     * Get standings table
     *
     * @param array $teams teams.
     * @param int $match_day match day.
     * @param string $mode mode.
     *
     * @return array the ranked teams
     */
    public function get_standings( array $teams, int $match_day, string $mode = 'all' ): array {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot get standings: Standings_Service collaborator is required.' );
        }
        return $this->standings_service->get_standings( $this, $teams, $match_day, $mode );
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
        $rule = maybe_unserialize( $rule );

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
     * Get number of teams for specific league
     *
     * @param boolean $total total teams or teams per page.
     */
    public function set_num_teams( bool $total = false ): void {
        if ( true === $total ) {
            $this->num_teams_total = $this->get_num_teams();
        } else {
            $this->num_teams        = $this->num_teams_total;
            $this->pagination_teams = $this->get_page_links( 'teams' );
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
        $args['count']            = true;
        $args['season']           = $this->current_season['name'];
        $args['reset_query_args'] = true;
        $args['club']             = false;
        if ( $status ) {
            $args['active'] = true;
        }
        if ( $latest ) {
            $args['cache'] = false;
        }
        return $this->get_league_teams( $args );
    }
    /**
     * Gets number of matches
     *
     * @param boolean $total total matches or matches per page.
     */
    public function set_num_matches( bool $total = false ): void {
        $match_args                     = array();
        $match_args['count']            = true;
        $match_args['season']           = null;
        $match_args['reset_query_args'] = true;
        if ( true !== $total ) {
            $match_args['limit'] = 0;
        }
        $num_matches = $this->get_matches( $match_args );
        if ( $total ) {
            $this->num_matches_total = $num_matches;
        } else {
            $this->pagination_matches = $this->get_page_links();
        }
    }

    /**
     * Get specific field for crosstable
     *
     * @param int $team_id team.
     * @param int $opponent_id opponent.
     *
     * @return string
     */
    public function get_crosstable_field( int $team_id, int $opponent_id ): string {
        if ( $team_id === $opponent_id ) {
            $score = '&nbsp;';
        } else {
            $matches   = $this->get_matches(
                array(
                    'home_team'        => $team_id,
                    'away_team'        => $opponent_id,
                    'match_day'        => -1,
                    'limit'            => false,
                    'reset_query_args' => true,
                )
            );
            $home_away = empty( $this->event->current_season['home_away'] ) ? false : $this->event->current_season['home_away'];
            if ( $home_away ) {
                if ( $matches ) {
                    $score = '';
                    foreach ( $matches as $match ) {
                        $score .= show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) ) . '<br>';
                    }
                } else {
                    $score = '&nbsp;';
                }
            } elseif ( $matches ) {
                $match = $matches[0];
                $score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
            } else {
                $matches = $this->get_matches(
                    array(
                        'home_team'        => $opponent_id,
                        'away_team'        => $team_id,
                        'match_day'        => -1,
                        'limit'            => false,
                        'reset_query_args' => true,
                    )
                );
                if ( $matches ) {
                    $match = $matches[0];
                    $score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
                } else {
                    $score = '&nbsp;';
                }
            }
        }

        return $score;
    }
    /**
     * Set tab in league/archive shortcodes
     *
     * @param boolean $is_archive is this an archive.
     */
    public function set_tab( bool $is_archive = false ): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET[ 'match_day_' . $this->id ] ) || isset( $_GET[ 'team_id_' . $this->id ] ) || isset( $_GET[ 'match_paged_' . $this->id ] ) ) {
            $this->archive_tab = 2;
        }
        if ( isset( $_GET[ 'team_' . $this->id ] ) ) {
            $this->archive_tab = 3;
        }
        if ( isset( $_GET[ 'match_' . $this->id ] ) ) {
            $this->archive_tab = 2;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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
    private function season_exists( string $season ): bool {
        return (bool) $this->event->get_season_by_name( $season );
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
     * Default ranking function. Re-defined in sports-specific class
     * 1) Primary points DESC
     * 2) Done Matches ASC
     *
     * @param false|string $season season.
     */
    public function set_teams_rank( false|string $season = false ): void {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot set teams rank: Standings_Service collaborator is required.' );
        }
        $this->standings_service->set_teams_rank( $this, $season );
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
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot get rank: League_Repository_Interface collaborator is required.' );
        }
        return $this->league_repository->get_rank( $this->id, $season, $team_id );
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
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot get status: League_Repository_Interface collaborator is required.' );
        }
        return $this->league_repository->get_status( $this->id, $season, $team_id );
    }
    /**
     * =======================
     * Administration section
     * =======================
     */

    /**
     * Update standings manually
     *
     * @param array $teams teams.
     * @param array $points points.
     * @param array $matches matches.
     * @param array $custom custom.
     */
    public function save_standings_manually( array $teams, array $points, array $matches, array $custom ): void {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot save standings manually: Standings_Service collaborator is required.' );
        }
        $this->standings_service->save_standings_manually( $this, $teams, $points, $matches, $custom );
    }

    /**
     * Update match results
     *
     * @param array $matches matches.
     * @param array $home_points home points.
     * @param array $away_points away points.
     * @param array $custom custom.
     * @param string $season season.
     * @param false|string $final_round final indicator.
     * @param string $confirmed confirmed status.
     *
     * @return int
     */
    public function update_match_results( array $matches, array $home_points, array $away_points, array $custom, string $season, false|string $final_round = false, string $confirmed = 'Y' ): int {
        if ( empty( $matches ) ) {
            return 0;
        }
        $fixture_repository = $this->get_fixture_repository();
        $result_manager = $this->fixture_result_manager;
        if ( ! $fixture_repository || ! $result_manager ) {
            throw new \RuntimeException( 'Cannot update match results: Fixture_Repository_Interface and Fixture_Result_Manager collaborators are required.' );
        }
        $num_matches = 0;
        foreach ( $matches as $match_id ) {
            $c           = $custom[ $match_id ] ?? array();
            $points_home = isset( $home_points[ $match_id ] ) ? floatval( $home_points[ $match_id ] ) : null;
            $points_away = isset( $away_points[ $match_id ] ) ? floatval( $away_points[ $match_id ] ) : null;

            $fixture = $fixture_repository->find_by_id( (int) $match_id );
            if ( $fixture ) {
                $fixture_custom = $fixture->get_custom() ?? array();
                $result_data    = [
                    'home_points' => $points_home,
                    'away_points' => $points_away,
                    'status'      => $fixture->get_status(),
                    'custom'      => $c,
                    'sets'        => $c['sets'] ?? ( $fixture_custom['sets'] ?? array() ),
                ];
                $result = Result_Factory::from_array( $result_data, $fixture->get_home_team(), $fixture->get_away_team() );

                if ( 'Y' === $confirmed ) {
                    $result_manager->confirm_result( $fixture, '', null, $result );
                } else {
                    $result_manager->update_result( $fixture, $result, $confirmed );
                }
                ++$num_matches;
            }
        }

        if ( $num_matches > 0 && ! $final_round ) {
            $this->update_standings( $season );
        }
        return $num_matches;
    }
    /**
     * Update standings function
     *
     * @param string $season season.
     */
    public function update_standings( string $season ): void {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot update standings: Standings_Service collaborator is required.' );
        }
        $this->standings_service->update_standings( $this, $season );
    }
    /**
     * Update points for given team
     *
     * @param object $league_team team.
     */
    private function save_standings( object $league_team ): void {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot save standings: Standings_Service collaborator is required.' );
        }
        $this->standings_service->save_standings( $this, $league_team );
    }

    /**
     * Calculate points for given team depending on point rule
     *
     * @param object $team team.
     * @param false|array $matches match.
     *
     * @return array
     */
    public function calculate_points( object $team, false|array $matches = false ): array {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot calculate points: Standings_Service collaborator is required.' );
        }
        return $this->standings_service->calculate_points( $this, $team, $matches );
    }

    /**
     * Determine if two teams are tied based on criteria
     *
     * @param object $team1 team1.
     * @param object $team2 team2.
     *
     * @return boolean
     */
    public function is_tie( object $team1, object $team2 ): bool {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot determine tie: Standings_Service collaborator is required.' );
        }
        return $this->standings_service->is_tie( $team1, $team2 );
    }

    /**
     * Get custom standings data
     *
     * @param int $team_id team reference.
     * @param array $data data used to get standings.
     * @param array $matches array of matches.
     *
     * @return array
     */
    public function get_standings_data( int $team_id, array $data = array(), array $matches = array() ): array {
        if ( ! $this->standings_service ) {
            throw new \RuntimeException( 'Cannot get standings data: Standings_Service collaborator is required.' );
        }
        return $this->standings_service->get_standings_data( $this, $team_id, $data, $matches );
    }

    /**
     * Display custom standings columns
     *
     * @param object $team team.
     * @param string $rule rule.
     */
    public function display_standings_columns( object $team, string $rule ): void {
        if ( count( $this->fields_team ) > 0 ) {
            foreach ( $this->fields_team as $key => $data ) {
                if ( ! isset( $team->{$key} ) ) {
                    if ( isset( $data['keys'] ) ) {
                        $team->{$key} = array();
                        foreach ( $data['keys'] as $k ) {
                            $team->{$key}[ $k ] = '';
                        }
                    } else {
                        $team->{$key} = '';
                    }
                }

                if ( ( isset( $data['type'] ) && 'input' === $data['type'] ) && is_admin() && 'manual' === $rule ) {
                    echo '<td class="column-' . esc_html( $key ) . ' column-num d-none d-md-table-cell" data-colname="' . esc_html( $data['label'] ) . '">';
                    if ( is_array( $team->{$key} ) ) {
                        foreach ( $team->{$key} as $k => $v ) {
                            echo '<input class="points" type="text" size="2" id="home_' . esc_html( $team->id ) . '_' . esc_html( $k ) . '" name="custom[' . esc_html( $team->id ) . '][' . esc_html( $key ) . '][' . esc_html( $k ) . ']" value="' . esc_html( $team->{$key}[ $k ] ) . '" />';
                        }
                    } else {
                        echo '<input class="points" type="text" size="2" id="home_' . esc_html( $team->id ) . '" name="custom[' . esc_html( $team->id ) . '][' . esc_html( $key ) . ']" value="' . esc_html( $team->{$key} ) . '" />';
                    }
                    echo '</td>';
                } elseif ( show_standings( $key ) ) {
                    if ( is_array( $team->{$key} ) ) {
                        $team->{$key} = vsprintf( $this->point_2_format, $team->{$key} );
                    }
                    echo '<td class="num column-' . esc_html( $key ) . ' d-none d-md-table-cell d-none d-md-table-cell" data-colname="' . esc_html( $data['label'] ) . '">' . esc_html( $team->{$key} ) . '</td>';
                }
            }
        }
    }

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
     * Schedule matches
     */
    public function schedule_matches(): void {
        if ( ! $this->league_schedule_service ) {
            throw new \RuntimeException( 'Cannot schedule matches: League_Schedule_Service collaborator is required.' );
        }
        $this->league_schedule_service->schedule_league( $this );
    }

    /**
     * Schedule matches
     *
     * @param array   $teams teams to create schedule for.
     * @param string  $num_rounds number of rounds.
     * @param boolean $home_away home and away indicator.
     */
    public function create_schedule( array $teams, string $num_rounds, bool $home_away ): void {
        if ( ! $this->league_schedule_service ) {
            throw new \RuntimeException( 'Cannot create schedule: League_Schedule_Service collaborator is required.' );
        }
        $this->league_schedule_service->create_schedule( $this, $teams, $num_rounds, $home_away );
    }

    /**
     * Create match schedule with teams
     *
     * @param array $rounds rounds.
     * @param array $teams array of teams.
     * @param array $match_dates array of match dates.
     * @param string $season season.
     * @param boolean $is_box is this a box league.
     */
    public function create_match_schedule( array $rounds, array $teams, array $match_dates, string $season, bool $is_box ): void {
        if ( ! $this->league_schedule_service ) {
            throw new \RuntimeException( 'Cannot create match schedule: League_Schedule_Service collaborator is required.' );
        }
        $this->league_schedule_service->create_match_schedule( $this, $rounds, $teams, $match_dates, $season, $is_box );
    }
    /**
     * Add match to league function
     *
     * @param object $match match object.
     *
     * @return void
     */
    public function add_match( object $match ): void {
        if ( ! $this->fixture_service ) {
            throw new \RuntimeException( 'Cannot add match: Fixture_Service collaborator is required.' );
        }

        $fixture = $match instanceof Fixture ? $match : Fixture_Factory::from_object( $match );
        $this->fixture_service->create_fixture( $fixture, $this );
    }


    /**
     * Update match within league function
     *
     * @param object $match match object.
     */
    public function update_match( object $match ): void {
        if ( ! $this->fixture_repository ) {
            throw new \RuntimeException( 'Cannot update match: Fixture_Repository_Interface collaborator is required.' );
        }

        if ( method_exists( $match, 'update' ) ) {
            $match->update();
        } elseif ( $match instanceof Fixture ) {
            $this->fixture_repository->save( $match );
        }

        $linked_match_id = $match instanceof Fixture ? $match->get_linked_fixture() : ( $match->linked_match ?? null );
        if ( ! empty( $linked_match_id ) ) {
            $linked_fixture = $this->fixture_repository->find_by_id( (int) $linked_match_id );
            if ( $linked_fixture ) {
                $home_team = $match instanceof Fixture ? $match->get_home_team() : ( $match->home_team ?? null );
                $away_team = $match instanceof Fixture ? $match->get_away_team() : ( $match->away_team ?? null );
                $date      = $match instanceof Fixture ? $match->get_date() : ( $match->date ?? null );
                $host      = $match instanceof Fixture ? $match->get_host() : ( $match->host ?? null );

                if ( null !== $home_team ) {
                    $linked_fixture->set_home_team( (string) $home_team );
                }
                if ( null !== $away_team ) {
                    $linked_fixture->set_away_team( (string) $away_team );
                }
                if ( ! empty( $date ) ) {
                    $linked_fixture->set_date( gmdate( 'Y-m-d H:i:s', strtotime( $date . ' +14 day' ) ) );
                }
                if ( ! empty( $host ) ) {
                    $linked_fixture->set_host( 'home' === $host ? 'away' : 'home' );
                }
                $this->fixture_repository->save( $linked_fixture );
            }
        }
    }
    /**
     * Get players for league
     *
     * @param array $args search arguments.
     *
     * @return array
     */
    public function get_players( array $args = array() ): array {
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(),
            'club'    => false,
            'team'    => false,
            'count'   => false,
            'group'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];
        $group    = $args['group'];

        if ( ! $args['season'] ) {
            $args['season'] = $this->current_season['name'] ?? '';
        }

        if ( $count ) {
            return $this->get_league_repository()->query_players( $this->id, $args, true );
        }

        $players = $this->get_league_repository()->query_players( $this->id, $args, false );
        $league_players = array();
        foreach ( $players as $player ) {
            $player = get_player( $player->player_id );
            if ( $player->system_record ) {
                continue;
            }
            $league_players[] = $player;
        }
        if ( $group ) {
            $this->players = array();
            foreach ( $league_players as $player ) {
                $key = strtoupper( substr( $player->get_fullname(), 0, 1 ) );
                if ( false === array_key_exists( $key, $this->players ) ) {
                    $this->players[ $key ] = array();
                }
                // now just add the row data.
                $this->players[ $key ][] = $player;
            }
        } else {
            $this->players = $league_players;
        }

        return $this->players;
    }
    /**
     * Set rounds function
     *
     * @param string $season season name.
     * @param array $rounds round details.
     *
     * @return void
     */
    public function set_rounds( string $season, array $rounds ): void {
        if ( ! $this->league_repository ) {
            throw new \RuntimeException( 'Cannot set rounds: League_Repository_Interface collaborator is required.' );
        }
        if ( empty( $this->seasons[ $season ] ) ) {
            $this->seasons[ $season ] = array();
        }
        $this->seasons[ $season ]['rounds'] = $rounds;

        if ( $this->league_repository->update_seasons( $this->id, $this->seasons ) ) {
            wp_cache_set( $this->id, $this, 'leagues' );
        }
    }
    /**
     * Contact League Teams
     *
     * @param string $season season.
     * @param string $email_message message.
     *
     * @return boolean
     */
    public function contact_teams( string $season, string $email_message ): bool {
        if ( $this->competition_notification_service ) {
            return $this->competition_notification_service->contact_league_teams( $this, $season, $email_message );
        }
        return false;
    }

    public function get_standings_service(): ?Standings_Service {
        return $this->standings_service;
    }

    public function get_schedule_service(): ?League_Schedule_Service {
        return $this->league_schedule_service;
    }

    public function get_notification_service(): ?Competition_Notification_Service {
        return $this->competition_notification_service;
    }

    public function get_fixture_service(): ?Fixture_Service {
        return $this->fixture_service;
    }

    public function get_fixture_repository(): ?Fixture_Repository_Interface {
        return $this->fixture_repository;
    }

    public function get_league_repository(): ?League_Repository_Interface {
        return $this->league_repository;
    }

    public function get_fixture_result_manager(): ?Fixture_Result_Manager {
        return $this->fixture_result_manager;
    }

    public function get_app_notification_service(): ?Notification_Service {
        return $this->notification_service;
    }
}

