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

/**
 * Factory for creating Fixture_Presentation_Adapter instances from Fixture domain entities.
 */
class Fixture_Presentation_Adapter_Factory {
    /**
     * Create adapter instance from fixture entity and associated services.
     *
     * @param Fixture                          $fixture             Domain fixture entity.
     * @param Competition_Service              $competition_service Competition service.
     * @param Team_Service                     $team_service        Team service.
     * @param Fixture_Link_Service|null        $link_service        Fixture link service.
     * @param Fixture_Permission_Service|null  $permission_service  Fixture permission service.
     * @param Option_Gateway_Interface|null    $option_gateway      Option gateway.
     * @param Rubber_Repository_Interface|null $rubber_repository   Rubber repository.
     *
     * @return Fixture_Presentation_Adapter
     */
    public static function create_from_fixture(
        Fixture $fixture,
        Competition_Service $competition_service,
        Team_Service $team_service,
        ?Fixture_Link_Service $link_service = null,
        ?Fixture_Permission_Service $permission_service = null,
        ?Option_Gateway_Interface $option_gateway = null,
        ?Rubber_Repository_Interface $rubber_repository = null
    ): Fixture_Presentation_Adapter {
        $league = self::resolve_league( $fixture, $competition_service );
        self::load_fixture_rubbers( $fixture, $league, $rubber_repository );

        $event       = self::resolve_event( $league, $competition_service );
        $competition = self::resolve_competition( $event, $competition_service );

        $home_dtls = self::resolve_team_details( $fixture->get_home_team(), $team_service );
        $away_dtls = self::resolve_team_details( $fixture->get_away_team(), $team_service );

        $link              = self::resolve_fixture_link( $fixture, $league, $home_dtls, $away_dtls, $link_service );
        $is_update_allowed = (object) array( 'user_can_update' => false );
        if ( $permission_service ) {
            $is_update_allowed = $permission_service->is_update_allowed( $fixture );
        }

        return new Fixture_Presentation_Adapter(
            $fixture,
            $league,
            $event,
            $competition,
            $home_dtls,
            $away_dtls,
            link: $link,
            is_update_allowed: $is_update_allowed,
            option_gateway: $option_gateway
        );
    }

    /**
     * Resolve league entity or fallback from fixture and competition service.
     *
     * @param Fixture             $fixture             Domain fixture entity.
     * @param Competition_Service $competition_service Competition service.
     *
     * @return object|null
     */
    public static function resolve_league( Fixture $fixture, Competition_Service $competition_service ): ?object {
        $league_id = (int) $fixture->get_league_id();
        if ( $league_id ) {
            return $competition_service->get_league_repository()->find_by_id( $league_id );
        }

        if ( method_exists( $fixture, 'get_league' ) ) {
            return $fixture->get_league();
        }

        return $fixture->league ?? null;
    }

    /**
     * Lazy-load fixture rubbers from repository if not already hydrated and applicable.
     *
     * @param Fixture                          $fixture           Domain fixture entity.
     * @param object|null                      $league            Resolved league entity or object.
     * @param Rubber_Repository_Interface|null $rubber_repository Rubber repository.
     *
     * @return void
     */
    public static function load_fixture_rubbers(
        Fixture $fixture,
        ?object $league,
        ?Rubber_Repository_Interface $rubber_repository
    ): void {
        $should_load = null !== $rubber_repository
            && null === $fixture->rubbers
            && $fixture->get_id() > 0
            && ( ! $league || (int) ( $league->num_rubbers ?? 0 ) > 0 );

        if ( $should_load ) {
            $fixture->set_rubbers( $rubber_repository->find_by_fixture_id( $fixture->get_id() ) );
        }
    }

    /**
     * Resolve event entity or fallback from league and competition service.
     *
     * @param object|null         $league              Resolved league entity or object.
     * @param Competition_Service $competition_service Competition service.
     *
     * @return object|null
     */
    public static function resolve_event( ?object $league, Competition_Service $competition_service ): ?object {
        if ( ! $league ) {
            return null;
        }

        if ( method_exists( $league, 'get_event_id' ) && $league->get_event_id() ) {
            return $competition_service->get_event_by_id( $league->get_event_id() );
        }

        return $league->event ?? null;
    }

    /**
     * Resolve competition entity or fallback from event and competition service.
     *
     * @param object|null         $event               Resolved event entity or object.
     * @param Competition_Service $competition_service Competition service.
     *
     * @return object|null
     */
    public static function resolve_competition( ?object $event, Competition_Service $competition_service ): ?object {
        if ( ! $event ) {
            return null;
        }

        if ( method_exists( $event, 'get_competition_id' ) && $event->get_competition_id() ) {
            return $competition_service->get_by_id( $event->get_competition_id() );
        }

        return $event->competition ?? null;
    }

    /**
     * Resolve team details DTO for numeric ID or string team reference.
     *
     * @param string|int|null $team_val     Team identifier or reference string.
     * @param Team_Service    $team_service Team service.
     *
     * @return Team_Details_DTO|null
     */
    public static function resolve_team_details( string|int|null $team_val, Team_Service $team_service ): ?Team_Details_DTO {
        if ( is_numeric( $team_val ) ) {
            return $team_service->get_team_details( (int) $team_val );
        }

        if ( ! empty( $team_val ) ) {
            return $team_service->derive_team_details( (string) $team_val );
        }

        return null;
    }

    /**
     * Resolve fixture link from link service or fallback to fixture link.
     *
     * @param Fixture                   $fixture      Domain fixture entity.
     * @param object|null               $league       Resolved league entity or object.
     * @param Team_Details_DTO|null     $home_dtls    Resolved home team details DTO.
     * @param Team_Details_DTO|null     $away_dtls    Resolved away team details DTO.
     * @param Fixture_Link_Service|null $link_service Fixture link service.
     *
     * @return string
     */
    public static function resolve_fixture_link(
        Fixture $fixture,
        ?object $league,
        ?Team_Details_DTO $home_dtls,
        ?Team_Details_DTO $away_dtls,
        ?Fixture_Link_Service $link_service
    ): string {
        $fixture_link = $fixture->get_link();
        $link         = $fixture_link ?? '';

        if ( $link_service && $league ) {
            $link = $link_service->get_fixture_link( $fixture, $league, $home_dtls, $away_dtls );
        }

        if ( empty( $link ) && $fixture_link ) {
            $link = $fixture_link;
        }

        return $link;
    }
}
