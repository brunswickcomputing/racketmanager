<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Presentation_Adapter_Test extends TestCase {

    public function test_adapter_hydrates_from_realistic_fixture_under_strict_types(): void {
        $data              = new stdClass();
        $data->id          = 101;
        $data->league_id   = 10;
        $data->home_team   = '1';
        $data->away_team   = '2';
        $data->date        = '2026-05-15 18:30:00';
        $data->season      = '2026';
        $data->leg         = 2;
        $data->match_day   = 5;
        $data->home_points = '3.5';
        $data->away_points = '0.5';
        $data->status      = 1;
        $data->host        = 'home';
        $data->location    = 'Court 1';

        $fixture = new Fixture( $data );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_name' )->willReturn( 'Division 1' );
        $league->method( 'get_event_id' )->willReturn( 20 );

        $event = $this->createMock( Event::class );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_name' )->willReturn( 'Men\'s Doubles' );
        $event->method( 'get_type' )->willReturn( 'league' );
        $event->method( 'get_competition_id' )->willReturn( 30 );

        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_id' )->willReturn( 30 );
        $competition->method( 'get_name' )->willReturn( 'Summer League' );
        $competition->type = Competition_Type::LEAGUE;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->with( 10 )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_league_repository' )->willReturn( $league_repo );
        $comp_service->method( 'get_event_by_id' )->with( 20 )->willReturn( $event );
        $comp_service->method( 'get_by_id' )->with( 30 )->willReturn( $competition );

        $team1 = $this->createMock( Team::class );
        $team1->method( 'get_name' )->willReturn( 'Club A 1' );
        $team1_dto = new Team_Details_DTO( $team1, null, null );

        $team2 = $this->createMock( Team::class );
        $team2->method( 'get_name' )->willReturn( 'Club B 1' );
        $team2_dto = new Team_Details_DTO( $team2, null, null );

        $team_service = $this->createMock( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturnMap( [
            [ 1, $team1_dto ],
            [ 2, $team2_dto ],
        ] );

        $adapter = Fixture_Presentation_Adapter::from_fixture( $fixture, $comp_service, $team_service );

        $this->assertSame( 101, $adapter->id );
        $this->assertSame( 2, $adapter->leg );
        $this->assertSame( 5, $adapter->match_day );
        $this->assertSame( '3.5', $adapter->home_points );
        $this->assertSame( '0.5', $adapter->away_points );
        $this->assertSame( 1, $adapter->status );
        $this->assertSame( 'Division 1', $adapter->league->title );
        $this->assertSame( 'Men\'s Doubles', $adapter->league->event->name );
        $this->assertSame( 'Summer League', $adapter->league->event->competition->name );
        $this->assertSame( 'Club A 1', $adapter->home_title );
        $this->assertSame( 'Club B 1', $adapter->away_title );
        $this->assertSame( 'Club A 1 - Club B 1', $adapter->title );
    }
}
