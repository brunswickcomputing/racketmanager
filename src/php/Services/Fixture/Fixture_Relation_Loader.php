<?php
/**
 * Fixture_Relation_Loader class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

/**
 * Loads the relations (league and home/away team read data) of a Fixture aggregate.
 *
 * The Fixture entity is a plain aggregate. Relation loading lives here so that the
 * entity carries no repository or service collaborators.
 */
class Fixture_Relation_Loader {
    public function __construct(
        private readonly ?League_Repository_Interface $league_repository = null,
        private readonly ?Fixture_Team_Resolver_Interface $team_resolver = null
    ) {
    }

    /**
     * Attach league and team relations to the fixture.
     */
    public function load( Fixture $fixture ): Fixture {
        $this->load_league( $fixture );
        $this->load_teams( $fixture );

        return $fixture;
    }

    /**
     * Attach the league to the fixture when it is not already set.
     */
    public function load_league( Fixture $fixture ): Fixture {
        if ( null !== $fixture->get_league() || ! $fixture->get_league_id() || ! $this->league_repository ) {
            return $fixture;
        }
        try {
            $league = $this->league_repository->find_by_id( $fixture->get_league_id() );
            if ( is_object( $league ) ) {
                $fixture->set_league( $league );
            }
        } catch ( \Throwable ) {
        }

        return $fixture;
    }

    /**
     * Attach home and away team read data to the fixture.
     */
    public function load_teams( Fixture $fixture ): Fixture {
        if ( ! $this->team_resolver || ! empty( $fixture->get_loaded_teams() ) ) {
            return $fixture;
        }
        $home_ref = $fixture->get_home_team();
        $away_ref = $fixture->get_away_team();

        $fixture->set_teams(
            array(
                'home' => $this->build_team( $home_ref, $this->resolve_details( $home_ref ) ),
                'away' => $this->build_team( $away_ref, $this->resolve_details( $away_ref ) ),
            )
        );

        return $fixture;
    }

    /**
     * Resolve team details for a team id or placeholder reference.
     */
    private function resolve_details( int|string|null $team_ref ): ?Team_Details_DTO {
        if ( empty( $team_ref ) ) {
            return null;
        }
        try {
            return is_numeric( $team_ref )
                ? $this->team_resolver->get_team_details( (int) $team_ref )
                : $this->team_resolver->derive_team_details( (string) $team_ref );
        } catch ( \Throwable ) {
            return null;
        }
    }

    /**
     * Build the team read object used by match lists and templates.
     */
    private function build_team( int|string|null $team_ref, ?Team_Details_DTO $details ): object {
        $team = $details?->team;
        $club = $details?->club;
        if ( '-1' === (string) $team_ref ) {
            $name = __( 'Bye', 'racketmanager' );
        } else {
            $name = $team ? (string) $team->get_name() : '';
            $name = '' !== $name ? $name : __( 'Unknown', 'racketmanager' );
        }

        $obj                  = new \stdClass();
        $obj->id              = $team_ref;
        $obj->title           = $name;
        $obj->name            = $name;
        $obj->team_type       = $team && method_exists( $team, 'get_team_type' ) ? $team->get_team_type() : ( is_numeric( $team_ref ) ? 'T' : 'P' );
        $obj->club            = new \stdClass();
        $obj->club->id        = $club ? $club->get_id() : null;
        $obj->club->shortcode = $club && method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '';
        $obj->players         = $details->players ?? array();
        $obj->player          = $obj->players;
        $obj->captain         = $details->captain ?? '';
        $obj->contactemail    = $details->captain_email ?? '';
        $obj->contactno       = $details->captain_tel ?? '';
        $obj->match_day       = $details->match_day ?? '';
        $obj->match_time      = $details->match_time ?? '';
        $obj->is_withdrawn    = $details->is_withdrawn ?? false;

        return $obj;
    }
}
