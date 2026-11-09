<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use stdClass;

/**
 * Presentation adapter providing legacy template compatibility for modern Fixture domain entities.
 */
class Fixture_Presentation_Adapter {
    /**
     * Fixture identifier.
     *
     * @var int
     */
    public int $id;

    /**
     * Fixture date.
     *
     * @var string|null
     */
    public ?string $date;

    /**
     * Original fixture date string.
     *
     * @var string|null
     */
    public ?string $date_original;

    /**
     * Match start time.
     *
     * @var string|null
     */
    public ?string $start_time;

    /**
     * Formatted match date.
     *
     * @var string
     */
    public string $match_date;

    /**
     * Match season.
     *
     * @var string|null
     */
    public ?string $season;

    /**
     * League identifier.
     *
     * @var int|null
     */
    public ?int $league_id;

    /**
     * Final round title.
     *
     * @var string|null
     */
    public ?string $final_round;

    /**
     * Match leg number.
     *
     * @var int|null
     */
    public ?int $leg;

    /**
     * Match day number.
     *
     * @var int|null
     */
    public ?int $match_day;

    /**
     * Home team identifier or name.
     *
     * @var string|int|null
     */
    public string|int|null $home_team;

    /**
     * Away team identifier or name.
     *
     * @var string|int|null
     */
    public string|int|null $away_team;

    /**
     * Match location.
     *
     * @var string
     */
    public string $location;

    /**
     * Winner identifier.
     *
     * @var int|null
     */
    public ?int $winner_id;

    /**
     * Loser identifier.
     *
     * @var int|null
     */
    public ?int $loser_id;

    /**
     * Whether fixture is pending.
     *
     * @var bool
     */
    public bool $is_pending;

    /**
     * Home team points.
     *
     * @var string|null
     */
    public ?string $home_points;

    /**
     * Away team points.
     *
     * @var string|null
     */
    public ?string $away_points;

    /**
     * Fixture status code.
     *
     * @var int|null
     */
    public ?int $status;

    /**
     * Fixture link URL.
     *
     * @var string
     */
    public string $link;

    /**
     * Full fixture title.
     *
     * @var string
     */
    public string $title;

    /**
     * Match title.
     *
     * @var string
     */
    public string $match_title;

    /**
     * Home team title.
     *
     * @var string
     */
    public string $home_title;

    /**
     * Away team title.
     *
     * @var string
     */
    public string $away_title;

    /**
     * Number of rubbers.
     *
     * @var int|null
     */
    public ?int $num_rubbers;

    /**
     * Number of sets.
     *
     * @var int|null
     */
    public ?int $num_sets;

    /**
     * Rubbers list.
     *
     * @var array
     */
    public array $rubbers;

    /**
     * Legacy league object representation.
     *
     * @var object
     */
    public object $league;

    /**
     * Home and away team representations.
     *
     * @var array
     */
    public array $teams;

    /**
     * Update permissions status object.
     *
     * @var object|null
     */
    public ?object $is_update_allowed;

    /**
     * Host team indicator.
     *
     * @var string|null
     */
    public ?string $host;

    /**
     * Linked fixture identifier.
     *
     * @var int|null
     */
    public ?int $linked_match;

    /**
     * Previous home match details.
     *
     * @var stdClass|null
     */
    public ?stdClass $prev_home_match = null;

    /**
     * Previous away match details.
     *
     * @var stdClass|null
     */
    public ?stdClass $prev_away_match = null;

    /**
     * Constructor.
     *
     * @param Fixture               $fixture        Domain fixture entity.
     * @param object|null           $league         League object or entity.
     * @param object|null           $event          Event object or entity.
     * @param object|null           $competition    Competition object or entity.
     * @param Team_Details_DTO|null $home_team_dtls Home team details DTO.
     * @param Team_Details_DTO|null $away_team_dtls Away team details DTO.
     * @param mixed                 ...$options     Optional parameters: string $link, ?object $is_update_allowed,
     *                                              ?string $prev_home_title, ?string $prev_away_title,
     *                                              ?Option_Gateway_Interface $option_gateway.
     */
    public function __construct(
        public readonly Fixture $fixture,
        ?object $league = null,
        ?object $event = null,
        ?object $competition = null,
        ?Team_Details_DTO $home_team_dtls = null,
        ?Team_Details_DTO $away_team_dtls = null,
        ...$options
    ) {
        $parsed            = new Fixture_Presentation_Adapter_Options_Parser()->parse( $options );
        $link              = $parsed['link'];
        $is_update_allowed = $parsed['is_update_allowed'];
        $prev_home_title   = $parsed['prev_home_title'];
        $prev_away_title   = $parsed['prev_away_title'];
        $option_gateway    = $parsed['option_gateway'];

        $graph_builder       = new Fixture_Presentation_Adapter_Graph_Builder();
        $this->id            = (int) $fixture->get_id();
        $this->date          = $fixture->get_date();
        $this->date_original = $fixture->get_date_original();
        $this->start_time    = $fixture->get_start_time();
        $this->match_date    = $graph_builder->resolve_match_date( $this->date, $option_gateway );
        $this->season        = $fixture->get_season();
        $this->league_id     = $fixture->get_league_id();
        $this->final_round   = $fixture->get_final();
        $this->leg           = $fixture->get_leg();
        $this->match_day     = $fixture->get_match_day();
        $this->home_team     = $fixture->get_home_team();
        $this->away_team     = $fixture->get_away_team();
        $this->location      = $fixture->get_location() ?? '';
        $this->winner_id     = $fixture->get_winner_id();
        $this->loser_id      = $fixture->get_loser_id();
        $this->is_pending    = $fixture->is_pending();
        $this->home_points   = $fixture->get_home_points();
        $this->away_points   = $fixture->get_away_points();
        $this->status        = $fixture->get_status();
        $this->link          = $link;
        $this->host          = $fixture->get_host();
        $this->linked_match  = $fixture->get_linked_fixture();

        $this->is_update_allowed = $is_update_allowed ?? (object) array( 'user_can_update' => false );
        $this->rubbers           = $fixture->get_rubbers();
        $this->league            = $graph_builder->build_league_graph( $fixture, $league, $event, $competition );
        $this->num_rubbers       = $this->league->num_rubbers;
        $this->num_sets          = $this->league->num_sets;

        // Build home & away team representations.
        $this->home_title  = $graph_builder->resolve_team_name( $home_team_dtls, $prev_home_title, $this->home_team );
        $this->away_title  = $graph_builder->resolve_team_name( $away_team_dtls, $prev_away_title, $this->away_team );
        $this->title       = ( $this->home_title || $this->away_title ) ? sprintf( '%s - %s', $this->home_title, $this->away_title ) : '';
        $this->match_title = $this->title;

        $this->teams = array(
            'home' => $graph_builder->build_team_object( $this->home_team, $this->home_title, $home_team_dtls ),
            'away' => $graph_builder->build_team_object( $this->away_team, $this->away_title, $away_team_dtls ),
        );

        if ( $prev_home_title ) {
            $this->prev_home_match              = new stdClass();
            $this->prev_home_match->match_title = $prev_home_title;
        }
        if ( $prev_away_title ) {
            $this->prev_away_match              = new stdClass();
            $this->prev_away_match->match_title = $prev_away_title;
        }
    }

    /**
     * Create presentation adapter from fixture and services.
     *
     * @param Fixture                          $fixture             Fixture domain entity.
     * @param Competition_Service              $competition_service Competition service.
     * @param Team_Service                     $team_service        Team service.
     * @param Fixture_Link_Service|null        $link_service        Fixture link service.
     * @param Fixture_Permission_Service|null  $permission_service  Fixture permission service.
     * @param Option_Gateway_Interface|null    $option_gateway      Option gateway.
     * @param Rubber_Repository_Interface|null $rubber_repository   Rubber repository.
     *
     * @return self
     */
    public static function from_fixture(
        Fixture $fixture,
        Competition_Service $competition_service,
        Team_Service $team_service,
        ?Fixture_Link_Service $link_service = null,
        ?Fixture_Permission_Service $permission_service = null,
        ?Option_Gateway_Interface $option_gateway = null,
        ?Rubber_Repository_Interface $rubber_repository = null
    ): self {
        return Fixture_Presentation_Adapter_Factory::create_from_fixture(
            $fixture,
            $competition_service,
            $team_service,
            $link_service,
            $permission_service,
            $option_gateway,
            $rubber_repository
        );
    }

    /**
     * Get fixture identifier.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Get home team identifier or name.
     *
     * @return string|int|null
     */
    public function get_home_team(): string|int|null {
        return $this->home_team;
    }

    /**
     * Get away team identifier or name.
     *
     * @return string|int|null
     */
    public function get_away_team(): string|int|null {
        return $this->away_team;
    }

    /**
     * Get date.
     *
     * @return string|null
     */
    public function get_date(): ?string {
        return $this->date;
    }

    /**
     * Get start time.
     *
     * @return string|null
     */
    public function get_start_time(): ?string {
        return $this->start_time;
    }

    /**
     * Get final round.
     *
     * @return string|null
     */
    public function get_final(): ?string {
        return $this->final_round;
    }

    /**
     * Get match day.
     *
     * @return int|null
     */
    public function get_match_day(): ?int {
        return $this->match_day;
    }

    /**
     * Get leg.
     *
     * @return int|null
     */
    public function get_leg(): ?int {
        return $this->leg;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->status;
    }

    /**
     * Get winner id.
     *
     * @return int|null
     */
    public function get_winner_id(): ?int {
        return $this->winner_id;
    }

    /**
     * Get loser id.
     *
     * @return int|null
     */
    public function get_loser_id(): ?int {
        return $this->loser_id;
    }

    /**
     * Get home points.
     *
     * @return string|null
     */
    public function get_home_points(): ?string {
        return $this->home_points;
    }

    /**
     * Get away points.
     *
     * @return string|null
     */
    public function get_away_points(): ?string {
        return $this->away_points;
    }

    /**
     * Get league id.
     *
     * @return int|null
     */
    public function get_league_id(): ?int {
        return $this->league_id;
    }

    /**
     * Get season.
     *
     * @return string|null
     */
    public function get_season(): ?string {
        return $this->season;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function get_location(): string {
        return $this->location;
    }

    /**
     * Get rubbers.
     *
     * @return array
     */
    public function get_rubbers(): array {
        return $this->rubbers;
    }

    /**
     * Check if match is pending.
     *
     * @return bool
     */
    public function is_pending(): bool {
        return $this->is_pending;
    }

    /**
     * Check if update is allowed.
     *
     * @return object
     */
    public function is_update_allowed(): object {
        return $this->is_update_allowed ?? (object) array( 'user_can_update' => false );
    }

    /**
     * Magic call proxy to domain fixture entity.
     *
     * @param string $name      Method name.
     * @param array  $arguments Method arguments.
     *
     * @return mixed
     */
    public function __call( string $name, array $arguments ) {
        return $this->fixture->$name( ...$arguments );
    }
}
