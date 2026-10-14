<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Tournament;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class League_Fixture_Creation_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $fixture_repository;
    private $rubber_repository;
    private Fixture_Service $fixture_service;
    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository  = $this->createMock( Rubber_Repository_Interface::class );

        $repository_provider = new Repository_Provider(
            league_repository: $this->createStub( League_Repository_Interface::class ),
            team_repository: $this->createStub( Team_Repository_Interface::class ),
            rubber_repository: $this->rubber_repository,
            fixture_repository: $this->fixture_repository,
            club_repository: $this->createStub( Club_Repository_Interface::class )
        );

        $service_provider = new Fixture_Service_Provider();
        $this->fixture_service = new Fixture_Service( $repository_provider, $service_provider );

        $this->container = new Simple_Container();
        $this->container->set( 'fixture_service', fn() => $this->fixture_service );
        $this->container->set( 'fixture_repository', fn() => $this->fixture_repository );

        $racketmanager            = new stdClass();
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_league_add_match_invokes_fixture_service(): void {
        $league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $league->id = 50;

        $match_data = (object) [
            'league_id' => 50,
            'home_team' => '10',
            'away_team' => '20',
            'season'    => '2026',
        ];

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Fixture::class ) );

        $league->add_match( $match_data );
    }

    public function test_league_create_match_schedule_invokes_fixture_service(): void {
        $league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $league->id = 50;

        $rounds = [
            [
                'startDate' => '2026-05-01',
                'fixtures'  => [
                    [ 'home' => 1, 'away' => 2 ],
                ],
            ],
        ];

        $team1 = (object) [ 'id' => 10, 'match_day' => 'Monday', 'match_time' => '19:00', 'club' => (object) [ 'shortcode' => 'CLUB1' ] ];
        $team2 = (object) [ 'id' => 20, 'match_day' => 'Tuesday', 'match_time' => '19:00', 'club' => (object) [ 'shortcode' => 'CLUB2' ] ];
        $teams = [ $team1, $team2 ];
        $match_dates = [ '2026-05-01' ];

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Fixture::class ) );

        $league->create_match_schedule( $rounds, $teams, $match_dates, '2026', false );
    }

    public function test_championship_legs_created_when_home_away_enabled_and_not_final(): void {
        $competition = $this->createMock( \Racketmanager\Domain\Competition\Competition::class );
        $competition->method( 'get_season_by_name' )
            ->willReturn( [ 'home_away_diff' => 2 ] );

        $event = $this->createMock( Event::class );
        $event->current_season = [ 'home_away' => 1 ];
        $event->competition = $competition;

        $league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $league->id = 50;
        $league->is_championship = true;
        $league->event = $event;

        $fixture = Fixture_Factory::from_object( (object) [
            'id'        => 100,
            'league_id' => 50,
            'home_team' => '1',
            'away_team' => '2',
            'season'    => '2026',
            'date'      => '2026-06-01 10:00:00',
            'final'     => 'semi',
        ] );

        // Should save leg 1, then leg 2 fixture, then update leg 1 linked match
        $this->fixture_repository->expects( $this->atLeast( 2 ) )
            ->method( 'save' );

        $this->fixture_service->create_fixture( $fixture, $league );

        $this->assertEquals( 1, $fixture->get_leg() );
    }

    public function test_championship_legs_not_created_when_final_round(): void {
        $competition = $this->createMock( \Racketmanager\Domain\Competition\Competition::class );
        $event = $this->createMock( Event::class );
        $event->current_season = [ 'home_away' => 1 ];
        $event->competition = $competition;

        $league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $league->id = 50;
        $league->is_championship = true;
        $league->event = $event;

        $fixture = Fixture_Factory::from_object( (object) [
            'id'          => 100,
            'league_id'   => 50,
            'home_team'   => '1',
            'away_team'   => '2',
            'season'      => '2026',
            'date'        => '2026-06-01 10:00:00',
            'final_round' => 'final',
        ] );

        // Should only save once, no second leg
        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Fixture::class ) );

        $this->fixture_service->create_fixture( $fixture, $league );

        $this->assertNull( $fixture->get_leg() );
    }
}
