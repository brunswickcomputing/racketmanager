<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\League;
use Racketmanager\Domain\League_Team;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\League_Service;
use stdClass;

use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;

require_once __DIR__ . '/../../wp-stubs.php';

final class League_Service_Test extends TestCase {

    private League_Service $service;
    private $league_repository;
    private $event_repository;
    private $league_team_repository;
    private $team_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->league_repository      = $this->createMock( League_Repository::class );
        $this->event_repository       = $this->createMock( Event_Repository::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository::class );
        $this->team_repository        = $this->createMock( Team_Repository::class );
        $plugin_instance              = $this->createMock( RacketManager::class );

        $this->service = new League_Service(
            $plugin_instance,
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );
    }

    private function create_real_instance_without_constructor( string $class_name ) {
        $reflection = new ReflectionClass( $class_name );
        return $reflection->newInstanceWithoutConstructor();
    }

    public function test_add_team_to_league_returns_existing_if_already_exists(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $team = $this->createMock( Team::class );
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );

        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $existing_league_team = $this->createMock( League_Team::class );

        $this->league_team_repository->expects( self::once() )
                                     ->method( 'find_by_team_league_and_season' )
                                     ->with( $team_id, $league_id, $season )
                                     ->willReturn( $existing_league_team );

        $this->league_team_repository->expects( self::never() )
                                     ->method( 'save' );

        $result = $this->service->add_team_to_league( $team_id, $league_id, $season );

        self::assertSame( $existing_league_team, $result );
    }

    public function test_remove_team_from_league_throws_exception_if_matches_exist(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $league_team = $this->createMock( League_Team::class );
        $this->league_team_repository->method( 'find_by_team_league_and_season' )
                                     ->willReturn( $league_team );

        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $league->expects( self::once() )
               ->method( 'get_matches' )
               ->with( [
                   'team_id' => $team_id,
                   'season'  => $season,
                   'final'   => 'all',
               ] )
               ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $this->expectException( Team_Has_Matches_Exception::class );

        $this->service->remove_team_from_league( $team_id, $league_id, $season );
    }

    public function test_championship_admin_service_handles_team_has_matches_exception(): void {
        $league_id = 123;
        $season    = 2024;
        $team_id   = 456;

        $league = $this->create_real_instance_without_constructor( League::class );
        $league->id = $league_id;

        // In the test context, we need to mock the global Racketmanager\get_league function
        // but since we can't redefine it if it already exists, we rely on our wp-stubs.php 
        // to have a way to inject it, or we just hope it hasn't been defined yet.
        // Actually, the wp-stubs.php I saw earlier DOES NOT define get_league.
        // Championship_Admin_Service uses `use function Racketmanager\get_league;`
        
        if (!function_exists('Racketmanager\get_league')) {
            eval('namespace Racketmanager; function get_league($id) { return $GLOBALS["league_mock_for_test"] ?? $GLOBALS["test_league"] ?? null; }');
        }
        $GLOBALS['league_mock_for_test'] = $league;
        $GLOBALS['test_league'] = $league; // Just in case another test already defined it using this key

        $league_service     = $this->createMock( League_Service::class );
        $fixture_service    = $this->createMock( Fixture_Service::class );
        $tournament_service = $this->createMock( Tournament_Service::class );

        $admin_service = new Championship_Admin_Service(
            $league_service,
            $fixture_service,
            $tournament_service
        );

        $league_service->expects( self::once() )
                       ->method( 'remove_team_from_league' )
                       ->with( $team_id, $league_id, $season )
                       ->willThrowException( new Team_Has_Matches_Exception( 'Matches exist' ) );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: $league_id,
            season: (string)$season,
            post: [
                'action' => 'delete',
                'team'   => [$team_id],
                'season' => $season
            ]
        );

        $result = $admin_service->handle_league_teams_action( $dto );

        self::assertEquals( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Matches exist', $result->message );
    }
}
