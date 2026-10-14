<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

trait Creates_Test_Container {
    protected function create_test_container(
        array $teams = [
            '1' => [ 'name' => 'Red Club 1st Team', 'type' => 'T' ],
            '2' => [ 'name' => 'Blue Club 2nd Team', 'type' => 'T' ],
        ],
    ): Simple_Container {
        $container    = new Simple_Container();
        $team_service = $this->createStub( Team_Service::class );

        $team_dtos = [];
        foreach ( $teams as $id => $info ) {
            $entity = $this->createStub( Team::class );
            $entity->method( 'get_name' )->willReturn( $info['name'] );
            $entity->method( 'get_team_type' )->willReturn( $info['type'] );
            $team_dtos[ (int) $id ] = new Team_Details_DTO( team: $entity );
        }

        $team_service->method( 'get_team_details' )->willReturnCallback(
            static fn( $id ) => $team_dtos[ (int) $id ] ?? null
        );

        $container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $container->set( 'competition_entry_service', fn() => $this->createStub( Competition_Entry_Service::class ) );
        $container->set( 'fixture_service', fn() => $this->createStub( Fixture_Service::class ) );
        $container->set( 'fixture_detail_service', fn() => $this->createStub( Fixture_Detail_Service::class ) );
        $container->set( 'competition_repository', fn() => $this->createStub( Competition_Repository_Interface::class ) );
        $container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );

        return $container;
    }

    protected function configure_league_repository(
        Simple_Container $container,
        int $league_id,
        string $title,
        Competition_Type $type = Competition_Type::LEAGUE,
    ): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $league_repo  = $this->createStub( League_Repository::class );
        $league_mock  = $this->createStub( League::class );
        $event_mock   = $this->createStub( Event::class );

        $comp_mock               = (object) [ 'type' => $type ];
        $event_mock->competition = $comp_mock;
        $league_mock->method( 'get_name' )->willReturn( $title );
        $league_mock->title = $title;
        $league_mock->id    = $league_id;
        $league_mock->event = $event_mock;

        $league_repo->method( 'find_by_id' )->willReturn( $league_mock );
        $comp_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $container->set( 'competition_service', fn() => $comp_service );
    }

    protected function create_container_with_services( array $services = [] ): Simple_Container {
        $container = new Simple_Container();
        foreach ( $services as $key => $instance ) {
            $container->set( (string) $key, static fn() => $instance );
        }
        return $container;
    }

    protected function create_app_with_services( array $services = [] ): RacketManager {
        $container     = $this->create_container_with_services( $services );
        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        return $racketmanager;
    }
}
