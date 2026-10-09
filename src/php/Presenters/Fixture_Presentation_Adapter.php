<?php
declare( strict_types=1 );

namespace Racketmanager\Presenters;

use AllowDynamicProperties;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use stdClass;

/**
 * Presentation adapter providing legacy template compatibility for modern Fixture domain entities.
 */
#[AllowDynamicProperties]
class Fixture_Presentation_Adapter {
    public int $id;
    public ?string $date;
    public ?string $date_original;
    public ?string $start_time;
    public string $match_date;
    public ?string $season;
    public ?int $league_id;
    public ?string $final_round;
    public ?int $leg;
    public ?int $match_day;
    public string|int|null $home_team;
    public string|int|null $away_team;
    public string $location;
    public ?int $winner_id;
    public ?int $loser_id;
    public bool $is_pending;
    public ?string $home_points;
    public ?string $away_points;
    public ?int $status;
    public string $link;
    public string $title;
    public string $match_title;
    public string $home_title;
    public string $away_title;
    public ?int $num_rubbers;
    public ?int $num_sets;
    public array $rubbers;
    public stdClass $league;
    public array $teams;
    public ?object $is_update_allowed;
    public ?string $host;
    public ?int $linked_match;
    public ?stdClass $prev_home_match = null;
    public ?stdClass $prev_away_match = null;

    public function __construct(
        public readonly Fixture $fixture,
        ?League $league = null,
        ?Event $event = null,
        ?Competition $competition = null,
        ?Team_Details_DTO $home_team_dtls = null,
        ?Team_Details_DTO $away_team_dtls = null,
        string $link = '',
        ?object $is_update_allowed = null,
        ?string $prev_home_title = null,
        ?string $prev_away_title = null
    ) {
        $this->id            = (int) $fixture->get_id();
        $this->date          = $fixture->get_date();
        $this->date_original = $fixture->get_date_original();
        $this->start_time    = $fixture->get_start_time();
        $this->match_date    = $this->date ? ( function_exists( 'mysql2date' ) ? (string) mysql2date( get_option( 'date_format', 'Y-m-d' ), $this->date ) : $this->date ) : '';
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
        $this->num_rubbers       = $league && method_exists( $league, 'get_num_rubbers' ) ? $league->get_num_rubbers() : count( $fixture->get_rubbers() );
        $this->num_sets          = $league && method_exists( $league, 'get_num_sets' ) ? $league->get_num_sets() : null;
        $this->rubbers           = $fixture->get_rubbers();

        $league_title = $league ? ( method_exists( $league, 'get_name' ) ? (string) $league->get_name() : (string) ( $league->title ?? '' ) ) : '';
        $league_type  = $league ? ( method_exists( $league, 'get_type' ) ? (string) $league->get_type() : (string) ( $league->mode ?? $league->type ?? '' ) ) : '';
        $league_sport = $league ? ( method_exists( $league, 'get_sport' ) ? (string) $league->get_sport() : (string) ( $league->sport ?? '' ) ) : '';

        // Build league graph for legacy template consumption.
        $this->league                 = new stdClass();
        $this->league->id             = $league ? ( method_exists( $league, 'get_id' ) ? (int) $league->get_id() : (int) ( $league->id ?? 0 ) ) : (int) $fixture->get_league_id();
        $this->league->title          = $league_title;
        $this->league->name           = $this->league->title;
        $this->league->type           = $league_type;
        $this->league->mode           = $league_type;
        $this->league->sport          = $league_sport;
        $this->league->num_rubbers    = $this->num_rubbers;
        $this->league->num_sets       = $this->num_sets;
        $this->league->event_id       = $league ? ( method_exists( $league, 'get_event_id' ) ? (int) $league->get_event_id() : (int) ( $league->event_id ?? 0 ) ) : ( $event ? ( method_exists( $event, 'get_id' ) ? (int) $event->get_id() : (int) ( $event->id ?? 0 ) ) : 0 );
        $this->league->current_season = array( 'name' => (string) $fixture->get_season() );

        $event_obj                 = new stdClass();
        $event_obj->id             = $event ? ( method_exists( $event, 'get_id' ) ? (int) $event->get_id() : (int) ( $event->id ?? 0 ) ) : 0;
        $event_obj->name           = $event ? ( method_exists( $event, 'get_name' ) ? (string) $event->get_name() : (string) ( $event->name ?? '' ) ) : '';
        $event_type                = $event ? ( method_exists( $event, 'get_type' ) ? (string) $event->get_type() : (string) ( $event->type ?? '' ) ) : '';
        $event_obj->type           = $event_type;
        $event_obj->competition_id = $event ? ( method_exists( $event, 'get_competition_id' ) ? (int) $event->get_competition_id() : (int) ( $event->competition_id ?? 0 ) ) : 0;
        $event_obj->is_box         = $event ? ( ! empty( $event->is_box ) || 'box' === $event_type ) : false;

        $comp_obj                  = new stdClass();
        $comp_obj->id              = $competition ? ( method_exists( $competition, 'get_id' ) ? (int) $competition->get_id() : (int) ( $competition->id ?? 0 ) ) : 0;
        $comp_obj->name            = $competition ? ( method_exists( $competition, 'get_name' ) ? (string) $competition->get_name() : (string) ( $competition->name ?? '' ) ) : '';
        $comp_type                 = $competition ? ( method_exists( $competition, 'get_type' ) ? (string) $competition->get_type() : (string) ( $competition->type ?? '' ) ) : '';
        $comp_obj->type            = $comp_type;
        $comp_obj->is_championship = ( 'championship' === $comp_type || ( $competition && method_exists( $competition, 'is_championship' ) && $competition->is_championship() ) );
        $comp_obj->is_cup          = ( 'cup' === $comp_type || ( $competition && method_exists( $competition, 'is_cup' ) && $competition->is_cup() ) );
        $comp_obj->is_tournament   = ( 'tournament' === $comp_type || ( $competition && method_exists( $competition, 'is_tournament' ) && $competition->is_tournament() ) );
        $comp_obj->is_league       = ( 'league' === $comp_type || ( $competition && method_exists( $competition, 'is_league' ) && $competition->is_league() ) );

        $event_obj->competition = $comp_obj;
        $this->league->event    = $event_obj;

        // Build home & away team representations.
        $home_name = $home_team_dtls && $home_team_dtls->team ? $home_team_dtls->team->get_name() : ( $prev_home_title ?? (string) $this->home_team );
        $away_name = $away_team_dtls && $away_team_dtls->team ? $away_team_dtls->team->get_name() : ( $prev_away_title ?? (string) $this->away_team );

        $this->home_title  = $home_name;
        $this->away_title  = $away_name;
        $this->title       = ( $home_name || $away_name ) ? sprintf( '%s - %s', $home_name, $away_name ) : '';
        $this->match_title = $this->title;

        $home_team_obj               = new stdClass();
        $home_team_obj->id           = $this->home_team;
        $home_team_obj->title        = $home_name;
        $home_team_obj->name         = $home_name;
        $home_team_obj->team_type    = $home_team_dtls && $home_team_dtls->team && method_exists( $home_team_dtls->team, 'get_team_type' ) ? $home_team_dtls->team->get_team_type() : ( is_numeric( $this->home_team ) ? 'T' : 'P' );
        $home_team_obj->club         = new stdClass();
        $home_team_obj->club->id     = $home_team_dtls && $home_team_dtls->club ? $home_team_dtls->club->get_id() : null;
        $home_team_obj->club->shortcode = $home_team_dtls && $home_team_dtls->club && method_exists( $home_team_dtls->club, 'get_shortcode' ) ? $home_team_dtls->club->get_shortcode() : '';
        $home_team_obj->players      = $home_team_dtls->players ?? array();
        $home_team_obj->player       = $home_team_obj->players;
        $home_team_obj->captain      = $home_team_dtls->captain ?? '';
        $home_team_obj->contactemail = $home_team_dtls->captain_email ?? '';
        $home_team_obj->contactno    = $home_team_dtls->captain_tel ?? '';
        $home_team_obj->match_day    = $home_team_dtls->match_day ?? '';
        $home_team_obj->match_time   = $home_team_dtls->match_time ?? '';
        $home_team_obj->is_withdrawn = $home_team_dtls->is_withdrawn ?? false;

        $away_team_obj               = new stdClass();
        $away_team_obj->id           = $this->away_team;
        $away_team_obj->title        = $away_name;
        $away_team_obj->name         = $away_name;
        $away_team_obj->team_type    = $away_team_dtls && $away_team_dtls->team && method_exists( $away_team_dtls->team, 'get_team_type' ) ? $away_team_dtls->team->get_team_type() : ( is_numeric( $this->away_team ) ? 'T' : 'P' );
        $away_team_obj->club         = new stdClass();
        $away_team_obj->club->id     = $away_team_dtls && $away_team_dtls->club ? $away_team_dtls->club->get_id() : null;
        $away_team_obj->club->shortcode = $away_team_dtls && $away_team_dtls->club && method_exists( $away_team_dtls->club, 'get_shortcode' ) ? $away_team_dtls->club->get_shortcode() : '';
        $away_team_obj->players      = $away_team_dtls->players ?? array();
        $away_team_obj->player       = $away_team_obj->players;
        $away_team_obj->captain      = $away_team_dtls->captain ?? '';
        $away_team_obj->contactemail = $away_team_dtls->captain_email ?? '';
        $away_team_obj->contactno    = $away_team_dtls->captain_tel ?? '';
        $away_team_obj->match_day    = $away_team_dtls->match_day ?? '';
        $away_team_obj->match_time   = $away_team_dtls->match_time ?? '';
        $away_team_obj->is_withdrawn = $away_team_dtls->is_withdrawn ?? false;

        $this->teams = array(
            'home' => $home_team_obj,
            'away' => $away_team_obj,
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

    public static function from_fixture(
        Fixture $fixture,
        Competition_Service $competition_service,
        Team_Service $team_service,
        ?Fixture_Link_Service $link_service = null,
        ?Fixture_Permission_Service $permission_service = null
    ): self {
        $league_id = (int) $fixture->get_league_id();
        $league    = $league_id ? $competition_service->get_league_repository()->find_by_id( $league_id ) : null;
        $event     = ( $league && $league->get_event_id() ) ? $competition_service->get_event_by_id( $league->get_event_id() ) : null;
        $competition = ( $event && $event->get_competition_id() ) ? $competition_service->get_by_id( $event->get_competition_id() ) : null;

        $home_val = $fixture->get_home_team();
        $away_val = $fixture->get_away_team();

        $home_dtls = is_numeric( $home_val ) ? $team_service->get_team_details( (int) $home_val ) : ( ! empty( $home_val ) ? $team_service->derive_team_details( (string) $home_val ) : null );
        $away_dtls = is_numeric( $away_val ) ? $team_service->get_team_details( (int) $away_val ) : ( ! empty( $away_val ) ? $team_service->derive_team_details( (string) $away_val ) : null );

        $link = ( $link_service && $league ) ? $link_service->get_fixture_link( $fixture, $league, $home_dtls, $away_dtls ) : '';
        $is_update_allowed = $permission_service ? $permission_service->is_update_allowed( $fixture ) : (object) array( 'user_can_update' => false );

        return new self( $fixture, $league, $event, $competition, $home_dtls, $away_dtls, $link, $is_update_allowed );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_title(): string {
        return $this->title;
    }

    public function get_home_team(): string|int|null {
        return $this->home_team;
    }

    public function get_away_team(): string|int|null {
        return $this->away_team;
    }

    public function get_date(): ?string {
        return $this->date;
    }

    public function get_start_time(): ?string {
        return $this->start_time;
    }

    public function get_final(): ?string {
        return $this->final_round;
    }

    public function get_match_day(): ?int {
        return $this->match_day;
    }

    public function get_leg(): ?int {
        return $this->leg;
    }

    public function get_status(): ?int {
        return $this->status;
    }

    public function get_winner_id(): ?int {
        return $this->winner_id;
    }

    public function get_loser_id(): ?int {
        return $this->loser_id;
    }

    public function get_home_points(): ?string {
        return $this->home_points;
    }

    public function get_away_points(): ?string {
        return $this->away_points;
    }

    public function get_league_id(): ?int {
        return $this->league_id;
    }

    public function get_season(): ?string {
        return $this->season;
    }

    public function get_location(): string {
        return $this->location;
    }

    public function get_rubbers(): array {
        return $this->rubbers;
    }

    public function is_pending(): bool {
        return $this->is_pending;
    }

    public function is_update_allowed(): object {
        return $this->is_update_allowed ?? (object) array( 'user_can_update' => false );
    }

    public function get_fixture(): Fixture {
        return $this->fixture;
    }

    public function __call( string $name, array $arguments ) {
        return $this->fixture->$name( ...$arguments );
    }
}
