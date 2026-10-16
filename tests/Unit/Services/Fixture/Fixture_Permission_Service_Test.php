<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Registration_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Permission_Service_Test extends TestCase {

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
        
        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->registration_service = $this->createStub( Registration_Service::class );
        $this->league_repository = $this->createMock( League_Repository_Interface::class );
        $this->team_repository = $this->createStub( Team_Repository_Interface::class );
        $this->club_repository = $this->createStub( Club_Repository_Interface::class );
        
        $this->repository_provider = $this->createStub( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );
        $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->team_repository );
        $this->repository_provider->method( 'get_club_repository' )->willReturn( $this->club_repository );

        $this->service_provider = $this->createStub( Service_Provider::class );
        $this->service_provider->method( 'get_registration_service' )->willReturn( $this->registration_service );

        // Mock global $racketmanager
        global $racketmanager;
        $racketmanager = new class {
            public function get_options() {
                return [
                    'standard' => [
                        'matchCapability' => 'captain',
                        'resultEntry' => 'home'
                    ]
                ];
            }
        };

        $this->service = $this->getMockBuilder( Fixture_Permission_Service::class )
            ->setConstructorArgs( [ $this->repository_provider, $this->registration_service, [] ] )
            ->onlyMethods( [ 'current_user_can', 'get_current_user_id', 'get_options' ] )
            ->getMock();

        $this->service->method( 'get_options' )->willReturn( [
            'standard' => [
                'matchCapability' => 'captain',
                'resultEntry' => 'home'
            ],
            'player_comp' => [
                'matchCapability' => 'player',
                'resultEntry' => 'home'
            ],
            'either_comp' => [
                'matchCapability' => 'captain',
                'resultEntry' => 'either'
            ]
        ] );
    }

    private function setup_test_fixture(
        string $confirmed = 'P',
        ?int $winner_id = null,
        ?int $home_approver = null,
        ?int $away_approver = null,
        string $competition_type = 'standard',
        int $home_club_id = 1000,
        int $away_club_id = 2000,
        int $home_team_id = 200,
        int $away_team_id = 300,
        int $league_id = 100
    ): Fixture {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_confirmed' )->willReturn( $confirmed );
        $fixture->method( 'get_league_id' )->willReturn( $league_id );
        if ( null !== $winner_id ) {
            $fixture->method( 'get_winner_id' )->willReturn( $winner_id );
        }
        if ( null !== $home_approver ) {
            $fixture->method( 'get_home_approver' )->willReturn( $home_approver );
        }
        if ( null !== $away_approver ) {
            $fixture->method( 'get_away_approver' )->willReturn( $away_approver );
        }
        $fixture->method( 'get_home_team' )->willReturn( (string) $home_team_id );
        $fixture->method( 'get_away_team' )->willReturn( (string) $away_team_id );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $league = $this->createStub( League::class );
        $league->method( 'get_competition_type' )->willReturn( $competition_type );
        $this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( $league_id )->willReturn( $league );

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_club_id' )->willReturn( $home_club_id );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_club_id' )->willReturn( $away_club_id );

        $this->team_repository->method( 'find_by_id' )->willReturnMap( [
            [ $home_team_id, $home_team ],
            [ $away_team_id, $away_team ],
        ] );

        return $fixture;
    }

    public function test_is_update_allowed_returns_notFixtureFound_if_fixture_missing(): void {
        $this->fixture_repository->method( 'find_by_id' )->willReturn( null );
        
        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertFalse( $result->user_can_update );
        $this->assertEquals( 'notFixtureFound', $result->message );
    }

    public function test_admin_can_update_if_pending(): void {
        $this->setup_test_fixture();

        $this->service->expects( $this->once() )->method( 'current_user_can' )->with( 'manage_racketmanager' )->willReturn( true );
        $this->service->method( 'get_current_user_id' )->willReturn( 1 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'admin', $result->user_type );
        $this->assertTrue( $result->match_update );
    }

    public function test_home_captain_can_update_if_home_entry(): void {
        $this->setup_test_fixture( home_approver: 10 );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 10 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'captain', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }

    public function test_away_captain_can_update_if_home_entry_only_but_match_pending(): void {
        $this->setup_test_fixture( away_approver: 20 );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 20 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertTrue( $result->match_approval_mode );
        $this->assertEquals( 'captain', $result->user_type );
        $this->assertEquals( 'away', $result->user_team );
    }

    public function test_away_captain_cannot_update_if_home_entry_only_and_match_already_entered(): void {
        $this->setup_test_fixture( winner_id: 1000, away_approver: 20 );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 20 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertFalse( $result->user_can_update );
        $this->assertEquals( 'notHomeCaptain', $result->message );
    }

    public function test_home_match_secretary_can_update(): void {
        $this->setup_test_fixture();

        $home_club = $this->createStub( Club::class );
        $home_club->match_secretary = (object)['id' => 30];
        $away_club = $this->createStub( Club::class );
        
        $this->club_repository->method( 'find_by_id' )->willReturnMap([
            [1000, 'id', $home_club],
            [2000, 'id', $away_club]
        ]);

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 30 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'matchsecretary', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }

    public function test_confirmed_match_cannot_be_updated_by_captain(): void {
        $this->setup_test_fixture( confirmed: 'Y', home_approver: 10 );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 10 );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertFalse( $result->user_can_update );
        $this->assertEquals( 'captain', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
        $this->assertEquals( 'matchAlreadyConfirmed', $result->message );
    }

    public function test_regular_player_cannot_update_if_not_playing(): void {
        $this->setup_test_fixture( competition_type: 'player_comp' );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 50 ); // Random user ID

        // No secretary or captain roles found for user 50
        $this->club_repository->method( 'find_by_id' )->willReturn( $this->createStub( Club::class ) );

        // Not an active player in either club
        $this->registration_service->method( 'is_player_active_in_club' )->willReturn( false );

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertFalse( $result->user_can_update );
        $this->assertEquals( 'notTeamPlayer', $result->message );
    }

    public function test_home_player_can_update_if_match_capability_is_player(): void {
        $this->setup_test_fixture( competition_type: 'player_comp' );

        $this->service->method( 'current_user_can' )->willReturn( false );
        $this->service->method( 'get_current_user_id' )->willReturn( 50 );

        $this->club_repository->method( 'find_by_id' )->willReturn( $this->createStub( Club::class ) );

        // Is active in home club
        $this->registration_service->method( 'is_player_active_in_club' )->willReturnMap([
            [1000, 50, true],
            [2000, 50, false]
        ]);

        $result = $this->service->is_update_allowed( 1 );
        
        $this->assertTrue( $result->user_can_update );
        $this->assertEquals( 'player', $result->user_type );
        $this->assertEquals( 'home', $result->user_team );
    }
}
