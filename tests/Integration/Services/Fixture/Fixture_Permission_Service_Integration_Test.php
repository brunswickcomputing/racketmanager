<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Registration_Service;

class Fixture_Permission_Service_Integration_Test extends TestCase {

    private $fixture_repository;
    private $registration_service;
    private $league_repository;
    private $team_repository;
    private $club_repository;
    private $repository_provider;
    private $service_provider;
    private Fixture_Permission_Service $service;

    protected function setUp(): void {
        parent::setUp();
        
        $GLOBALS['racketmanager'] = new class {
            public function get_options() {
                return [];
            }
        };

        $this->fixture_repository = $this->createMock( Fixture_Repository::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->league_repository = $this->createMock( League_Repository::class );
        $this->team_repository = $this->createMock( Team_Repository::class );
        $this->club_repository = $this->createMock( Club_Repository::class );
        
        $this->repository_provider = new Repository_Provider(
            fixture_repository: $this->fixture_repository,
            league_repository: $this->league_repository,
            team_repository: $this->team_repository,
            club_repository: $this->club_repository,
            rubber_repository: $this->createMock( \Racketmanager\Repositories\Rubber_Repository::class )
        );

        $this->service_provider = new Service_Provider(
            registration_service: $this->registration_service
        );

        $this->service = new class( $this->repository_provider, $this->service_provider ) extends Fixture_Permission_Service {
            public $mock_current_user_can = false;
            public $mock_current_user_id = 0;
            public $mock_options = [];

            protected function current_user_can( string $capability ): bool {
                return $this->mock_current_user_can;
            }

            protected function get_current_user_id(): int {
                return $this->mock_current_user_id;
            }

            protected function get_options(): array {
                return $this->mock_options;
            }
        };
    }

    public function test_admin_can_update_fixture(): void {
        $fixture = new Fixture( (object)[ 'id' => 1, 'confirmed' => 'P', 'league_id' => 100 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $this->service->mock_current_user_can = true;
        
        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'admin', $result->user_type );
    }

    public function test_home_captain_can_update_pending_match(): void {
        $fixture = new Fixture( (object)[ 
            'id' => 1, 
            'confirmed' => 'P', 
            'league_id' => 100, 
            'home_captain' => 10,
            'home_team' => '200',
            'away_team' => '300'
        ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $event->competition = (object)[ 'type' => 'standard' ];
        $league->event = $event;
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 1000 );
        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 2000 );

        $this->team_repository->method( 'find_by_id' )->willReturnMap([
            [ 200, $home_team ],
            [ 300, $away_team ]
        ]);

        $this->service->mock_current_user_id = 10;
        $this->service->mock_options = [
            'standard' => [ 'matchCapability' => 'captain', 'resultEntry' => 'home' ]
        ];

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'captain', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }

    public function test_away_captain_can_update_pending_match_in_home_entry_mode_with_approval(): void {
        $fixture = new Fixture( (object)[ 
            'id' => 1, 
            'confirmed' => 'P', 
            'league_id' => 100, 
            'away_captain' => 20,
            'home_team' => '200',
            'away_team' => '300'
        ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $event->competition = (object)[ 'type' => 'standard' ];
        $league->event = $event;
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 1000 );
        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 2000 );

        $this->team_repository->method( 'find_by_id' )->willReturnMap([
            [ 200, $home_team ],
            [ 300, $away_team ]
        ]);

        $this->service->mock_current_user_id = 20;
        $this->service->mock_options = [
            'standard' => [ 'matchCapability' => 'captain', 'resultEntry' => 'home' ]
        ];

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertTrue( $result->match_approval_mode );
        $this->assertEquals( 'captain', $result->user_type );
        $this->assertEquals( 'away', $result->user_team );
    }

    public function test_player_can_update_if_capability_is_player(): void {
        $fixture = new Fixture( (object)[ 
            'id' => 1, 
            'confirmed' => 'P', 
            'league_id' => 100, 
            'home_team' => '200',
            'away_team' => '300'
        ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $event->competition = (object)[ 'type' => 'standard' ];
        $league->event = $event;
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 1000 );
        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 2000 );

        $this->team_repository->method( 'find_by_id' )->willReturnMap([
            [ 200, $home_team ],
            [ 300, $away_team ]
        ]);

        $this->service->mock_current_user_id = 50;
        $this->service->mock_options = [
            'standard' => [ 'matchCapability' => 'player', 'resultEntry' => 'home' ]
        ];

        // Mock player active in home club
        $this->registration_service->method( 'is_player_active_in_club' )->willReturnMap([
            [ 1000, 50, true ],
            [ 2000, 50, false ]
        ]);

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'player', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }

    public function test_match_secretary_can_update(): void {
        $fixture = new Fixture( (object)[ 
            'id' => 1, 
            'confirmed' => 'P', 
            'league_id' => 100, 
            'home_team' => '200',
            'away_team' => '300'
        ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $event->competition = (object)[ 'type' => 'standard' ];
        $league->event = $event;
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 1000 );
        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 2000 );

        $this->team_repository->method( 'find_by_id' )->willReturnMap([
            [ 200, $home_team ],
            [ 300, $away_team ]
        ]);

        $home_club = $this->createMock( Club::class );
        $home_club->match_secretary = (object)[ 'id' => 30 ];
        $away_club = $this->createMock( Club::class );
        
        $this->club_repository->method( 'find' )->willReturnMap([
            [ 1000, 'id', $home_club ],
            [ 2000, 'id', $away_club ]
        ]);

        $this->service->mock_current_user_id = 30;
        $this->service->mock_options = [
            'standard' => [ 'matchCapability' => 'captain', 'resultEntry' => 'home' ]
        ];

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'matchsecretary', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }

    public function test_confirmed_match_cannot_be_updated_by_captain(): void {
        $fixture = new Fixture( (object)[ 
            'id' => 1, 
            'confirmed' => 'Y', 
            'league_id' => 100, 
            'home_captain' => 10,
            'home_team' => '200',
            'away_team' => '300',
            'winner_id' => 200
        ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
        
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $event->competition = (object)[ 'type' => 'standard' ];
        $league->event = $event;
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 1000 );
        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 2000 );

        $this->team_repository->method( 'find_by_id' )->willReturnMap([
            [ 200, $home_team ],
            [ 300, $away_team ]
        ]);

        $this->service->mock_current_user_id = 10;
        $this->service->mock_options = [
            'standard' => [ 'matchCapability' => 'captain', 'resultEntry' => 'home' ]
        ];

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertFalse( $result->user_can_update );
        $this->assertEquals( 'matchAlreadyConfirmed', $result->message );
    }
}
