<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use stdClass;

/**
 * Builds legacy stdClass graph structures and team representations for Fixture_Presentation_Adapter.
 */
class Fixture_Presentation_Adapter_Graph_Builder {
    /**
     * Resolve and format match date string according to option gateway or WordPress format.
     *
     * @param string|null                   $date           Raw fixture date string.
     * @param Option_Gateway_Interface|null $option_gateway Option gateway instance.
     *
     * @return string
     */
    public function resolve_match_date( ?string $date, ?Option_Gateway_Interface $option_gateway ): string {
        if ( ! $date ) {
            return '';
        }

        $date_format = 'Y-m-d';
        if ( $option_gateway ) {
            $date_format = (string) $option_gateway->get_option( 'date_format', 'Y-m-d' );
        } elseif ( function_exists( 'get_option' ) ) {
            $date_format = (string) get_option( 'date_format', 'Y-m-d' );
        }

        if ( function_exists( 'mysql2date' ) ) {
            return (string) mysql2date( $date_format, $date );
        }

        return $date;
    }

    /**
     * Resolve number of rubbers for the fixture.
     *
     * @param object|null $league  League object or entity.
     * @param Fixture     $fixture Domain fixture entity.
     *
     * @return int|null
     */
    public function resolve_num_rubbers( ?object $league, Fixture $fixture ): ?int {
        if ( $league && method_exists( $league, 'get_num_rubbers' ) ) {
            return $league->get_num_rubbers();
        }

        return count( $fixture->get_rubbers() );
    }

    /**
     * Resolve number of sets for the fixture.
     *
     * @param object|null $league League object or entity.
     *
     * @return int|null
     */
    public function resolve_num_sets( ?object $league ): ?int {
        if ( $league && method_exists( $league, 'get_num_sets' ) ) {
            return $league->get_num_sets();
        }

        return null;
    }

    /**
     * Resolve team name from details DTO, previous title, or fallback identifier.
     *
     * @param Team_Details_DTO|null $team_dtls   Team details DTO.
     * @param string|null           $prev_title  Previous match title if applicable.
     * @param string|int|null       $fallback_id Fallback team identifier or name.
     *
     * @return string
     */
    public function resolve_team_name( ?Team_Details_DTO $team_dtls, ?string $prev_title, string|int|null $fallback_id ): string {
        if ( $team_dtls ) {
            return $team_dtls->team->get_name();
        }

        if ( null !== $prev_title ) {
            return $prev_title;
        }

        return (string) ( $fallback_id ?? '' );
    }

    /**
     * Resolve event identifier for the league graph.
     *
     * @param object|null $league League object or entity.
     * @param object|null $event  Event object or entity.
     *
     * @return int
     */
    public function resolve_league_event_id( ?object $league, ?object $event ): int {
        if ( null !== $league ) {
            return (int) ( $this->read_value( $league, 'get_event_id', 'event_id' ) ?? 0 );
        }

        if ( null !== $event ) {
            return (int) ( $this->read_value( $event, 'get_id', 'id' ) ?? 0 );
        }

        return 0;
    }

    /**
     * Read value from object getter or public properties in priority order.
     *
     * @param object $source   Source object.
     * @param string $getter   Method name to check and call.
     * @param string ...$props Property names to check in order.
     *
     * @return mixed
     */
    public function read_value( object $source, string $getter, string ...$props ): mixed {
        if ( method_exists( $source, $getter ) ) {
            return $source->$getter();
        }

        foreach ( $props as $prop ) {
            if ( isset( $source->$prop ) ) {
                return $source->$prop;
            }
        }

        return null;
    }

    /**
     * Normalise entity type representation from BackedEnum or scalar.
     *
     * @param mixed $raw Raw type value or enum instance.
     *
     * @return string|int
     */
    public function normalize_type( mixed $raw ): string|int {
        if ( $raw instanceof BackedEnum ) {
            return $raw->value;
        }

        return (string) ( $raw ?? '' );
    }

    /**
     * Resolve boolean competition type flag.
     *
     * @param object     $competition Competition object or entity.
     * @param string|int $comp_type   Resolved competition type.
     * @param string     $target_type Target competition type name.
     *
     * @return bool
     */
    public function resolve_competition_flag( object $competition, string|int $comp_type, string $target_type ): bool {
        if ( $comp_type === $target_type ) {
            return true;
        }

        $prop = 'is_' . $target_type;

        return ! empty( $competition->$prop ) || ( method_exists( $competition, $prop ) && $competition->$prop() );
    }

    /**
     * Build legacy competition object representation.
     *
     * @param object|null $competition Competition object or entity.
     *
     * @return stdClass
     */
    public function build_competition_object( ?object $competition ): stdClass {
        $comp_obj                  = new stdClass();
        $comp_obj->id              = 0;
        $comp_obj->name            = '';
        $comp_obj->type            = '';
        $comp_obj->is_championship = false;
        $comp_obj->is_cup          = false;
        $comp_obj->is_tournament   = false;
        $comp_obj->is_league       = false;

        if ( null === $competition ) {
            return $comp_obj;
        }

        $comp_obj->id   = (int) ( $this->read_value( $competition, 'get_id', 'id' ) ?? 0 );
        $comp_obj->name = (string) ( $this->read_value( $competition, 'get_name', 'name' ) ?? '' );
        $comp_obj->type = $this->normalize_type( $this->read_value( $competition, 'get_type', 'type' ) );

        $comp_obj->is_championship = $this->resolve_competition_flag( $competition, $comp_obj->type, 'championship' );
        $comp_obj->is_cup          = $this->resolve_competition_flag( $competition, $comp_obj->type, 'cup' );
        $comp_obj->is_tournament   = $this->resolve_competition_flag( $competition, $comp_obj->type, 'tournament' );
        $comp_obj->is_league       = $this->resolve_competition_flag( $competition, $comp_obj->type, 'league' );

        return $comp_obj;
    }

    /**
     * Build legacy event object representation.
     *
     * @param object|null $event       Event object or entity.
     * @param object|null $competition Competition object or entity.
     *
     * @return stdClass
     */
    public function build_event_object( ?object $event, ?object $competition ): stdClass {
        $event_obj                 = new stdClass();
        $event_obj->id             = 0;
        $event_obj->name           = '';
        $event_obj->type           = '';
        $event_obj->competition_id = 0;
        $event_obj->is_box         = false;

        if ( null === $event ) {
            $event_obj->competition = $this->build_competition_object( $competition );
            return $event_obj;
        }

        $event_obj->id             = (int) ( $this->read_value( $event, 'get_id', 'id' ) ?? 0 );
        $event_obj->name           = (string) ( $this->read_value( $event, 'get_name', 'name' ) ?? '' );
        $event_obj->type           = $this->normalize_type( $this->read_value( $event, 'get_type', 'type' ) );
        $event_obj->competition_id = (int) ( $this->read_value( $event, 'get_competition_id', 'competition_id' ) ?? 0 );

        $event_obj->is_box      = ! empty( $event->is_box ) || 'box' === $event_obj->type;
        $event_obj->competition = $this->build_competition_object( $competition );

        return $event_obj;
    }

    /**
     * Build legacy league graph representation.
     *
     * @param Fixture     $fixture     Domain fixture entity.
     * @param object|null $league      League object or entity.
     * @param object|null $event       Event object or entity.
     * @param object|null $competition Competition object or entity.
     *
     * @return stdClass
     */
    public function build_league_graph( Fixture $fixture, ?object $league, ?object $event, ?object $competition ): stdClass {
        $league_obj                 = new stdClass();
        $league_obj->id             = (int) $fixture->get_league_id();
        $league_obj->title          = '';
        $league_obj->name           = '';
        $league_obj->type           = '';
        $league_obj->mode           = '';
        $league_obj->sport          = '';
        $league_obj->num_rubbers    = $this->resolve_num_rubbers( $league, $fixture );
        $league_obj->num_sets       = $this->resolve_num_sets( $league );
        $league_obj->event_id       = $this->resolve_league_event_id( $league, $event );
        $league_obj->current_season = array( 'name' => (string) $fixture->get_season() );

        if ( null === $league ) {
            $league_obj->event = $this->build_event_object( $event, $competition );
            return $league_obj;
        }

        $league_obj->id    = (int) ( $this->read_value( $league, 'get_id', 'id' ) ?? 0 );
        $league_obj->title = (string) ( $this->read_value( $league, 'get_name', 'title' ) ?? '' );
        $league_obj->name  = $league_obj->title;

        $league_type       = $this->normalize_type( $this->read_value( $league, 'get_type', 'mode', 'type' ) );
        $league_obj->type  = $league_type;
        $league_obj->mode  = $league_type;
        $league_obj->sport = (string) ( $this->read_value( $league, 'get_sport', 'sport' ) ?? '' );

        $league_obj->event = $this->build_event_object( $event, $competition );

        return $league_obj;
    }

    /**
     * Build legacy team object representation.
     *
     * @param string|int|null       $team_id   Team ID or placeholder.
     * @param string                $team_name Team name.
     * @param Team_Details_DTO|null $team_dtls Team details DTO.
     *
     * @return stdClass
     */
    public function build_team_object( string|int|null $team_id, string $team_name, ?Team_Details_DTO $team_dtls ): stdClass {
        $team_obj        = new stdClass();
        $team_obj->id    = $team_id;
        $team_obj->title = $team_name;
        $team_obj->name  = $team_name;

        $team_type = is_numeric( $team_id ) ? 'T' : 'P';
        if ( $team_dtls && method_exists( $team_dtls->team, 'get_team_type' ) ) {
            $team_type = $team_dtls->team->get_team_type();
        }
        $team_obj->team_type = $team_type;

        $team_obj->club            = new stdClass();
        $team_obj->club->id        = null;
        $team_obj->club->shortcode = '';
        if ( $team_dtls && $team_dtls->club ) {
            $team_obj->club->id = $team_dtls->club->get_id();
            if ( method_exists( $team_dtls->club, 'get_shortcode' ) ) {
                $team_obj->club->shortcode = $team_dtls->club->get_shortcode();
            }
        }

        $team_obj->players      = $team_dtls->players ?? array();
        $team_obj->player       = $team_obj->players;
        $team_obj->captain      = $team_dtls->captain ?? '';
        $team_obj->contactemail = $team_dtls->contactemail ?? $team_dtls->captain_email ?? '';
        $team_obj->contactno    = $team_dtls->contactno ?? $team_dtls->captain_tel ?? '';
        $team_obj->match_day    = $team_dtls->match_day ?? '';
        $team_obj->match_time   = $team_dtls->match_time ?? '';
        $team_obj->is_withdrawn = $team_dtls->is_withdrawn ?? false;

        return $team_obj;
    }
}
