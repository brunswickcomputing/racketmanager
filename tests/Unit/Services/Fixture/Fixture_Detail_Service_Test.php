<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture {
    function home_url( string $path = '' ): string {
        return 'https://example.com' . $path;
    }
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

namespace Racketmanager\Tests\Unit\Services\Fixture {

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Service_Provider;

class Fixture_Detail_Service_Test extends TestCase {

    private $fixture_repository;
    private $league_repository;
    private $team_repository;
    private $competition_service;
    private $team_service;
    private $tournament_service;
    private $permission_service;
    private $repository_provider;
    private $service_provider;
    private Fixture_Detail_Service $service;

    protected function setUp(): void {
        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->league_repository = $this->createStub( League_Repository_Interface::class );
        $this->team_repository = $this->createStub( Team_Repository_Interface::class );
        
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->team_service = $this->createStub( Team_Service::class );
        $this->tournament_service = $this->createStub( Tournament_Service::class );
        $this->permission_service = $this->createStub( Fixture_Permission_Service::class );

        $this->repository_provider = $this->createStub( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );
        $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->team_repository );

        $this->service = new Fixture_Detail_Service( 
            $this->repository_provider, 
            $this->competition_service, 
            $this->team_service, 
            $this->permission_service 
        );
    }

    public function test_get_fixture_with_details_populates_new_fields(): void {
        $fixture_id = 1;
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 10 );
        $fixture->method( 'get_home_points' )->willReturn( "5" );
        $fixture->method( 'get_away_points' )->willReturn( "3" );
        $fixture->method( 'get_season' )->willReturn( '2026' );
        $fixture->method( 'is_walkover' )->willReturn( false );
        $fixture->method( 'is_retired' )->willReturn( true );

        $event = $this->createStub( Event::class );
        $event->method( 'get_num_rubbers' )->willReturn( 9 );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_competition_id' )->willReturn( 30 );

        $league = $this->createStub( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $league->method( 'get_name' )->willReturn( 'Premier League' );
        $league->method( 'get_competition_type' )->willReturn( 'league' );

        $competition = $this->createStub( Competition::class );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $dto = $this->service->get_fixture_with_details( $fixture_id );

        $this->assertInstanceOf( Fixture_Details_DTO::class, $dto );
        $this->assertEquals( '5 - 3', $dto->score_display );
        $this->assertContains( 'retired', $dto->status_flags );
        $this->assertStringContainsString( 'premier-league', $dto->link );
        $this->assertStringContainsString( '2026', $dto->link );
    }

    public function test_generate_score_display_handles_walkover(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'is_walkover' )->willReturn( true );
        $fixture->method( 'get_home_points' )->willReturn( "10" );
        $fixture->method( 'get_away_points' )->willReturn( "0" );
        $fixture->method( 'get_season' )->willReturn( '2026' );

        $event = $this->createStub( Event::class );
        $event->method( 'get_num_rubbers' )->willReturn( 10 );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_competition_id' )->willReturn( 30 );

        // Setup minimal context for get_fixture_with_details
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        $league = $this->createStub( League::class );
        $league->method( 'get_competition_type' )->willReturn( 'league' );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

        $dto = $this->service->get_fixture_with_details( 1 );
        $this->assertEquals( 'Walkover', $dto->score_display );
    }

    public function test_generate_score_display_handles_set_scores(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_home_points' )->willReturn( "2" );
        $fixture->method( 'get_away_points' )->willReturn( "1" );
        $fixture->method( 'get_season' )->willReturn( '2026' );
        $fixture->method( 'get_custom' )->willReturn( [
            'sets' => [
                new Set_Score( 6, 4 ),
                new Set_Score( 3, 6 ),
                new Set_Score( 6, 2 )
            ]
        ] );

        $event = $this->createStub( Event::class );
        $event->method( 'get_num_rubbers' )->willReturn( 0 ); // Individual match
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_competition_id' )->willReturn( 30 );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        $league = $this->createStub( League::class );
        $league->method( 'get_competition_type' )->willReturn( 'league' );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

        $dto = $this->service->get_fixture_with_details( 1 );
        $this->assertEquals( '6-4 3-6 6-2', $dto->score_display );
    }

    public function test_generate_score_display_handles_implicit_walkover(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'is_walkover' )->willReturn( false );
        $fixture->method( 'get_home_points' )->willReturn( null );
        $fixture->method( 'get_away_points' )->willReturn( null );
        $fixture->method( 'get_winner_id' )->willReturn( 100 );
        $fixture->method( 'get_home_team' )->willReturn( "-1" ); // BYE/Walkover indicator
        $fixture->method( 'get_season' )->willReturn( '2026' );

        $event = $this->createStub( Event::class );
        $event->method( 'get_num_rubbers' )->willReturn( 9 );
        $event->method( 'get_id' )->willReturn( 20 );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        $league = $this->createStub( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

        $dto = $this->service->get_fixture_with_details( 1 );
        $this->assertEquals( 'Walkover', $dto->score_display );
    }

    public function test_generate_score_display_handles_points_based_walkover(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'is_walkover' )->willReturn( false );
        $fixture->method( 'get_home_points' )->willReturn( "10" );
        $fixture->method( 'get_away_points' )->willReturn( "0" );
        $fixture->method( 'get_home_team' )->willReturn( "-1" );
        $fixture->method( 'get_season' )->willReturn( '2026' );

        $event = $this->createStub( Event::class );
        $event->method( 'get_num_rubbers' )->willReturn( 9 );
        $event->method( 'get_id' )->willReturn( 20 );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        $league = $this->createStub( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

        $dto = $this->service->get_fixture_with_details( 1 );
        $this->assertEquals( 'Walkover', $dto->score_display );
    }
}
}
