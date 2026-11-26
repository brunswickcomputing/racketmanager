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

use BackedEnum;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Domain\Shared\Serialized_Column;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Domain\Tournament\Championship;

/**
 * Class to implement the League object
 */
class League {

    /**
     * League ID
     *
     * @var ?int
     */
    public private(set) ?int $id = null;

    /**
     * League name
     *
     * @var string
     */
    public private(set) string $name = '';

    /**
     * League title (legacy alias mirroring $name)
     *
     * @var string
     */
    public private(set) string $title {
    final get => $this->name;
    final set {
            $this->name = $value;
        }
    }

    /**
     * Seasons data
     *
     * @var array|string
     */
    public private(set) string|array $seasons = array();
    /**
     * Sequence
     *
     * @var string|null
     */
    public private(set) ?string $sequence = null;
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
    public private(set) string $sport = 'default';

    /**
     * Point rule
     *
     * @var string
     */
    public private(set) string $point_rule = 'three';

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
     * Standings table layout settings
     *
     * @var array
     */
    public private(set) array $standings = array();

    /**
     * Team groups
     *
     * @var string
     */
    public private(set) string $groups = '';

    /**
     * Number of teams to advance
     *
     * @var int|null
     */
    public private(set) ?int $num_advance = null;

    /**
     * Teams
     *
     * @var array
     */
    public private(set) array $teams = array();
    /**
     * Number of teams
     *
     * @var int
     */
    public private(set) int $num_teams = 0;
    /**
     * Total number of teams
     *
     * @var int
     */
    public private(set) int $num_teams_total = 0;
    /**
     * Matches
     *
     * @var array
     */
    public private(set) array $matches = array();

    /**
     * Total number of matches
     *
     * @var int
     */
    public private(set) int $num_matches_total = 0;

    /**
     * Number of matches
     *
     * @var int
     */
    public private(set) int $num_matches = 0;

    /**
     * Current season
     *
     * @var array
     */
    public private(set) array $current_season = array();

    /**
     * Number of match days
     *
     * @var int
     */
    public private(set) int $num_match_days = 0;

    /**
     * Current match day
     *
     * @var int
     */
    public private(set) int $match_day = 0;

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
    public private(set) string|array $settings = array();
    /**
     * Team offsets indexed by ID
     *
     * @var array
     */
    public private(set) array $team_index = array();

    /**
     * Championship flag
     *
     * @var boolean
     */
    public private(set) bool $is_championship = false;

    /**
     * Championship object
     *
     * @var Championship|null
     */
    public private(set) ?Championship $championship = null;

    /**
     * Event id
     *
     * @var int
     */
    public private(set) int $event_id = 0;

    /**
     * Number of sets
     *
     * @var int
     */
    public private(set) int $num_sets = 0;

    /**
     * Number of rubbers
     *
     * @var int|null
     */
    public private(set) ?int $num_rubbers = null;

    /**
     * Number of sets required to win a match
     *
     * @var int
     */
    public private(set) int $num_sets_to_win = 1;

    /**
     * Competition type
     *
     * @var string
     */
    public private(set) string $competition_type = '';

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
    public private(set) string $type = '';

    /**
     * Event
     *
     * @var Event|null
     */
    public private(set) ?Event $event = null;

    /**
     * Scoring
     *
     * @var string|null
     */
    public private(set) ?string $scoring = '';

    /**
     * Is final flag
     *
     * @var boolean
     */
    public private(set) bool $is_final = false;
    /**
     * Entry type
     *
     * @var string
     */
    public private(set) string $entry_type = '';
    /**
     * Season
     *
     * @var string
     */
    public private(set) string $season = '';
    /**
     * Players
     *
     * @var array
     */
    public private(set) array $players = array();
    /**
     * Team
     *
     * @var object|null
     */
    public private(set) ?object $team = null;
    /**
     * Player
     *
     * @var object|null
     */
    public private(set) ?object $player = null;
    /**
     * Finals
     *
     * @var array|null
     */
    public private(set) ?array $finals = null;


    /**
     * Constructor
     *
     * Hydrates from the typed DTO only: table columns are applied first, then the decoded `settings`
     * keys (settings take precedence over columns, as the legacy row merge did). `new League()` yields
     * an empty entity.
     *
     * @param League_Hydration_DTO|null $dto   League hydration DTO.
     * @param Event|null                $event Optional event object.
     */
    public function __construct(
        ?League_Hydration_DTO $dto = null,
        ?Event $event = null
    ) {
        if ( null === $dto ) {
            return;
        }
        $columns = array(
            'id'       => $dto->id,
            'name'     => $dto->name,
            'event_id' => $dto->event_id,
            'sequence' => $dto->sequence,
            'settings' => $dto->settings,
            'seasons'  => $dto->seasons,
        );

        $settings = $dto->settings;
        unset( $settings['title'], $settings['name'] );

        foreach ( array_merge( $columns, $settings ) as $key => $value ) {
            if ( 'event' === $key ) {
                continue;
            }
            if ( 'standings' === $key ) {
                $this->$key = array_merge( $this->$key ?? array(), (array) $value );
            } elseif ( property_exists( $this, $key ) ) {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
        }

        if ( null !== $event ) {
            $this->event    = $event;
            $this->event_id = $event->id ?? 0;
        } elseif ( null !== $dto->event_id ) {
            $this->event_id = (int) $dto->event_id;
        }

        $this->name = stripslashes( $this->name ?? '' );

        // set seasons.
        if ( empty( $this->seasons ) ) {
            $this->seasons = array();
        }
        $this->seasons     = (array) self::decode_serialized_array( $this->seasons );
        $event_seasons     = $this->event ? $this->event->get_seasons() : array();
        $this->num_seasons = count( $event_seasons );
        // set season to latest.
        $this->set_season();
        $this->groups           = trim( $this->groups ?? '' );
        $this->mode             = $this->event && isset( $this->event->competition->settings['mode'] ) ? $this->event->competition->settings['mode'] : ( $this->mode ?? 'default' );
        $this->num_sets         = $this->event->num_sets ?? 3;
        $this->num_sets_to_win  = (int) floor( $this->num_sets / 2 ) + 1;
        $this->num_rubbers      = $this->event->num_rubbers ?? 0;
        $this->type             = $this->event->type ?? '';
        $comp_type              = $this->event && isset( $this->event->competition->type ) ? $this->event->competition->type : '';
        $this->competition_type = $comp_type instanceof BackedEnum ? $comp_type->value : (string) $comp_type;
        $this->point_rule       = $this->event && isset( $this->event->competition->settings['point_rule'] ) ? $this->event->competition->settings['point_rule'] : 'three';
        $this->sport            = $this->event && isset( $this->event->competition->settings['sport'] ) ? $this->event->competition->settings['sport'] : 'tennis';
        $this->scoring          = $this->event->scoring ?? null;
        $this->set_match_query_args();
        $this->num_matches_total = 0;
        $this->num_teams_total   = 0;
        $this->num_teams         = 0;
        $this->standings         = $this->event->standings ?? [];
        $this->point_format      = $this->event && isset( $this->event->competition->settings['point_format'] ) ? $this->event->competition->settings['point_format'] : '%d-%d';
        $this->point_2_format    = $this->event && isset( $this->event->competition->settings['point_2_format'] ) ? $this->event->competition->settings['point_2_format'] : '%d-%d';
        // Championship.
        if ( 'championship' === ( $this->mode ?? '' ) ) {
            $this->is_championship = true;
        }
    }


    /**
     * Create a League entity from a generic object (e.g. database row).
     *
     * Delegates to {@see League_Factory::from_object()} for validating hydration.
     *
     * @param object     $row   Raw row (e.g. `$wpdb` result or stdClass fixture).
     * @param Event|null $event Optional parent event.
     *
     * @return self|null
     */
    public static function from_object( object $row, ?Event $event = null ): ?self {
        return League_Factory::from_object( $row, $event );
    }

    /**
     * Create a League entity from an associative array.
     *
     * Delegates to {@see League_Factory::from_array()} for validating hydration.
     *
     * @param array      $data  Row data.
     * @param Event|null $event Optional parent event.
     *
     * @return self|null
     */
    public static function from_array( array $data, ?Event $event = null ): ?self {
        return League_Factory::from_array( $data, $event );
    }

    /**
     * Serialize the object properties for storage or transport.
     *
     * @return array Serialized object properties.
     */
    public function __serialize(): array {
        return get_object_vars( $this );
    }

    /**
     * Restore the object state from serialized data.
     *
     * @param array $data Serialized data keyed by property name.
     *
     * @return void
     */
    public function __unserialize( array $data ): void {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
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
        return $this->name;
    }

    /**
     * Get the title (legacy proxy for get_name())
     *
     * @return string
     */
    public function get_title(): string {
        return $this->get_name();
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
     * Get the event id
     *
     * @return int
     */
    public function get_event_id(): int {
        return $this->event_id;
    }

    /**
     * Set identifier.
     *
     * @param int|null $id Identifier value.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * Edit League
     *
     * @param string      $name     League name.
     * @param string|null $sequence Sequence.
     */
    public function update( string $name, ?string $sequence = null ): void {
        $this->name = $name;
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
        $this->set_match_query_arg( 'limit', 30 );
    }

    /**
     * Set match query argument
     *
     * @param string  $key key.
     * @param mixed   $value value.
     * @param boolean $replace - used for arrays to add arguments or replace with values.
     */
    public function set_match_query_arg( string $key, mixed $value, bool $replace = true ): void {
        if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
            $value = 30;
        }
        // sanitise query arg types.
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
     * @param string  $key key.
     * @param mixed   $value value.
     * @param boolean $replace - used for arrays to add arguments or replace with values.
     */
    public function set_team_query_arg( string $key, mixed $value, bool $replace = true ): void {
        if ( 'limit' === $key && ( true === $value || 'true' === $value ) ) {
            $value = 10;
        }
        // sanitise query arg types.
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
     * @param boolean      $force_overwrite force overwrite.
     */
    public function set_season( false|string $season = false, bool $force_overwrite = false ): void {
        // Build a safe seasons array from Event (JSON-backed) and an index by name.
        $seasons_list = ( isset( $this->event ) && is_object( $this->event ) && method_exists( $this->event, 'get_seasons' ) )
            ? $this->event->get_seasons()
            : ( is_array( $this->seasons ) ? $this->seasons : array() );
        $by_name      = array();
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
            $tmp = $seasons_list;
            if ( is_array( $tmp ) ) {
                ksort( $tmp ); // Sort by year key (e.g. "2024", "2025", "2026").
                $data = end( $tmp );
            } else {
                $data = null;
            }
        }

        if ( empty( $data ) ) {
            $tmp = $seasons_list;
            if ( is_array( $tmp ) ) {
                ksort( $tmp );
                $data = end( $tmp );
            } else {
                $data = null;
            }
        }

        // Fix for inconsistent season data where 'season' is used instead of 'name'.
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
     * Retrieve match day
     *
     * @param int $match_day match day.
     */
    public function set_match_day( int $match_day = 1 ): void {
        $this->match_day                     = $match_day;
        $this->match_query_args['match_day'] = $match_day;
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
            $point_rules['three']                 = array(
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
     * Set number of teams for specific league.
     *
     * When an integer is passed, sets the team count directly.
     * When a boolean is passed, syncs from num_teams_total if false.
     *
     * @param int|bool $arg Team count or total teams flag.
     * @return void
     */
    public function set_num_teams( int|bool $arg = false ): void {
        if ( is_int( $arg ) ) {
            $this->num_teams = $arg;
            return;
        }
        if ( ! $arg ) {
            $this->num_teams = $this->num_teams_total;
        }
    }

    /**
     * Set number of teams count directly.
     *
     * @param int $num_teams Number of teams.
     * @return void
     */
    public function set_num_teams_count( int $num_teams ): void {
        $this->num_teams = $num_teams;
    }

    /**
     * Check if season exists
     *
     * @param string|int $season season.
     *
     * @return boolean
     */
    public function season_exists( string|int $season ): bool {
        $season_str = (string) $season;
        if ( ! empty( $this->seasons ) && ( isset( $this->seasons[ $season ] ) || isset( $this->seasons[ $season_str ] ) || in_array( $season, $this->seasons, true ) || in_array( $season_str, $this->seasons, true ) ) ) {
            return true;
        }
        if ( isset( $this->event ) && $this->event instanceof Event ) {
            return (bool) $this->event->get_season_by_name( $season );
        }
        return false;
    }

    /**
     * Set rounds function
     *
     * @param string|int $season season name.
     * @param array      $rounds round details.
     * @return void
     */
    public function set_rounds( string|int $season, array $rounds ): void {
        $season = (string) $season;
        if ( empty( $this->seasons[ $season ] ) ) {
            $this->seasons[ $season ] = array();
        }
        $this->seasons[ $season ]['rounds'] = $rounds;
    }

    /**
     * Decode a serialized array string when the provided value matches serialized array format.
     *
     * @param mixed $value Value to decode.
     *
     * @return mixed Decoded array value, false when decoding returns a string, or the original value when not applicable.
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

    /**
     * Set championship flag.
     *
     * @param bool $is_championship Whether this league is a championship.
     * @return void
     */
    public function set_is_championship( bool $is_championship ): void {
        $this->is_championship = $is_championship;
    }

    /**
     * Set league name.
     *
     * @param string $name League name.
     * @return void
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }

    /**
     * Set league title.
     *
     * @param string $title League title.
     * @return void
     */
    public function set_title( string $title ): void {
        $this->title = $title;
    }

    /**
     * Set sequence.
     *
     * @param string|null $sequence Sequence string.
     * @return void
     */
    public function set_sequence( ?string $sequence ): void {
        $this->sequence = $sequence;
    }

    /**
     * Set seasons data.
     *
     * @param array|string $seasons Seasons data.
     * @return void
     */
    public function set_seasons( array|string $seasons ): void {
        $this->seasons = $seasons;
    }

    /**
     * Set number of seasons.
     *
     * @param int $num_seasons Number of seasons.
     * @return void
     */
    public function set_num_seasons( int $num_seasons ): void {
        $this->num_seasons = $num_seasons;
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
     * Set point rule.
     *
     * @param string $point_rule Point rule name.
     * @return void
     */
    public function set_point_rule( string $point_rule ): void {
        $this->point_rule = $point_rule;
    }

    /**
     * Set primary point format.
     *
     * @param string $point_format Point format string.
     * @return void
     */
    public function set_point_format( string $point_format ): void {
        $this->point_format = $point_format;
    }

    /**
     * Set secondary point format.
     *
     * @param string $point_2_format Secondary point format string.
     * @return void
     */
    public function set_point_2_format( string $point_2_format ): void {
        $this->point_2_format = $point_2_format;
    }

    /**
     * Set league mode.
     *
     * @param string $mode League mode.
     * @return void
     */
    public function set_mode( string $mode ): void {
        $this->mode = $mode;
    }

    /**
     * Set standings table layout settings.
     *
     * @param array $standings Standings settings.
     * @return void
     */
    public function set_standings( array $standings ): void {
        $this->standings = $standings;
    }

    /**
     * Set team groups.
     *
     * @param string $groups Team groups.
     * @return void
     */
    public function set_groups( string $groups ): void {
        $this->groups = $groups;
    }

    /**
     * Set number of teams to advance.
     *
     * @param int|null $num_advance Number of teams to advance.
     * @return void
     */
    public function set_num_advance( ?int $num_advance ): void {
        $this->num_advance = $num_advance;
    }

    /**
     * Set teams.
     *
     * @param array $teams Array of teams.
     * @return void
     */
    public function set_teams( array $teams ): void {
        $this->teams = $teams;
    }

    /**
     * Set total number of teams.
     *
     * @param int $num_teams_total Total number of teams.
     * @return void
     */
    public function set_num_teams_total( int $num_teams_total ): void {
        $this->num_teams_total = $num_teams_total;
    }

    /**
     * Set matches.
     *
     * @param array $matches Matches array.
     * @return void
     */
    public function set_matches( array $matches ): void {
        $this->matches = $matches;
    }

    /**
     * Set total number of matches.
     *
     * @param int $num_matches_total Total number of matches.
     * @return void
     */
    public function set_num_matches_total( int $num_matches_total ): void {
        $this->num_matches_total = $num_matches_total;
    }

    /**
     * Set number of matches.
     *
     * @param int $num_matches Number of matches.
     * @return void
     */
    public function set_num_matches( int $num_matches ): void {
        $this->num_matches = $num_matches;
    }

    /**
     * Set current season array and update derived query arguments and match days.
     *
     * Side effects:
     * - Updates $num_match_days if 'num_match_days' is provided in $current_season.
     * - Synchronises 'season' in $team_query_args and $match_query_args if 'name' is provided in $current_season.
     *
     * @param array $current_season Current season details.
     * @return void
     */
    public function set_current_season( array $current_season ): void {
        $this->current_season = $current_season;
        if ( isset( $current_season['num_match_days'] ) ) {
            $this->num_match_days = (int) $current_season['num_match_days'];
        }
        if ( isset( $current_season['name'] ) ) {
            $this->set_team_query_arg( 'season', (string) $current_season['name'] );
            $this->set_match_query_arg( 'season', (string) $current_season['name'] );
        }
    }

    /**
     * Set number of match days.
     *
     * @param int $num_match_days Number of match days.
     * @return void
     */
    public function set_num_match_days( int $num_match_days ): void {
        $this->num_match_days = $num_match_days;
    }

    /**
     * Set settings.
     *
     * @param array|string $settings Settings array or string.
     * @return void
     */
    public function set_settings( array|string $settings ): void {
        $this->settings = $settings;
    }

    /**
     * Set team index.
     *
     * @param array $team_index Team offsets indexed by ID.
     * @return void
     */
    public function set_team_index( array $team_index ): void {
        $this->team_index = $team_index;
    }

    /**
     * Set championship object.
     *
     * @param Championship|null $championship Championship instance.
     * @return void
     */
    public function set_championship( ?Championship $championship ): void {
        $this->championship = $championship;
    }

    /**
     * Set event ID.
     *
     * @param int $event_id Event ID.
     * @return void
     */
    public function set_event_id( int $event_id ): void {
        $this->event_id = $event_id;
    }

    /**
     * Set event object.
     *
     * @param Event|null $event Event instance.
     * @return void
     */
    public function set_event( ?Event $event ): void {
        $this->event = $event;
    }

    /**
     * Set number of sets.
     *
     * @param int $num_sets Number of sets.
     * @return void
     */
    public function set_num_sets( int $num_sets ): void {
        $this->num_sets = $num_sets;
    }

    /**
     * Set number of rubbers.
     *
     * @param int|null $num_rubbers Number of rubbers.
     * @return void
     */
    public function set_num_rubbers( ?int $num_rubbers ): void {
        $this->num_rubbers = $num_rubbers;
    }

    /**
     * Set number of sets required to win a match.
     *
     * @param int $num_sets_to_win Number of sets to win.
     * @return void
     */
    public function set_num_sets_to_win( int $num_sets_to_win ): void {
        $this->num_sets_to_win = $num_sets_to_win;
    }

    /**
     * Set competition type.
     *
     * @param string $competition_type Competition type string.
     * @return void
     */
    public function set_competition_type( string $competition_type ): void {
        $this->competition_type = $competition_type;
    }

    /**
     * Set type.
     *
     * @param string $type Type string.
     * @return void
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Set scoring format.
     *
     * @param string|null $scoring Scoring format string.
     * @return void
     */
    public function set_scoring( ?string $scoring ): void {
        $this->scoring = $scoring;
    }

    /**
     * Set finals list.
     *
     * @param array|null $finals Finals list.
     * @return void
     */
    public function set_finals_list( ?array $finals ): void {
        $this->finals = $finals;
    }

    /**
     * Set finals flag or finals list.
     *
     * When a boolean is passed, updates the is_final flag.
     * When an array or null is passed, updates the finals list.
     *
     * @param bool|array|null $is_final Final flag indicator or finals array.
     * @return void
     */
    public function set_finals( bool|array|null $is_final = true ): void {
        if ( is_array( $is_final ) || null === $is_final ) {
            $this->finals = $is_final;
        } else {
            $this->is_final = $is_final;
        }
    }

    /**
     * Set is_final flag.
     *
     * @param bool $is_final Final flag indicator.
     * @return void
     */
    public function set_is_final( bool $is_final ): void {
        $this->is_final = $is_final;
    }

    /**
     * Set entry type.
     *
     * @param string $entry_type Entry type string.
     * @return void
     */
    public function set_entry_type( string $entry_type ): void {
        $this->entry_type = $entry_type;
    }

    /**
     * Set season name directly.
     *
     * @param string $season Season name.
     * @return void
     */
    public function set_season_name( string $season ): void {
        $this->season = $season;
    }

    /**
     * Set players.
     *
     * @param array $players Players array.
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Get players.
     *
     * @return array
     */
    public function get_players(): array {
        return $this->players;
    }

    /**
     * Set player.
     *
     * @param object|null $player Player object.
     * @return void
     */
    public function set_player( ?object $player ): void {
        $this->player = $player;
    }

    /**
     * Set team.
     *
     * @param object|null $team Team object.
     * @return void
     */
    public function set_team( ?object $team ): void {
        $this->team = $team;
    }
}
