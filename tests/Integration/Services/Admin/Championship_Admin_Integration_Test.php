<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Championship_Admin_Integration_Test extends TestCase {

    private function create_real_instance_without_constructor( string $class_name ) {
        $reflection = new ReflectionClass( $class_name );
        return $reflection->newInstanceWithoutConstructor();
    }

    public function test_championship_admin_service_handles_team_has_matches_exception(): void {
        $league_id = 123;
        $season    = 2024;
        $team_id   = 456;

        $league = $this->create_real_instance_without_constructor( League::class );
        $league->id = $league_id;

        $GLOBALS['wp_stubs_leagues'][$league_id] = $league;

        $league_team_id = 789;
        $league_team = $this->createMock( League_Team::class );
        $league_team->method( 'get_team_id' )->willReturn( $team_id );

        $league_service         = $this->createMock( League_Service::class );
        $fixture_service        = $this->createMock( Fixture_Service::class );
        $tournament_service     = $this->createMock( Tournament_Service::class );
        $league_team_repository = $this->createMock( League_Team_Repository::class );
        $team_repository        = $this->createMock( Team_Repository::class );

        $league_team_repository->method( 'find_by_id' )
                               ->with( $league_team_id )
                               ->willReturn( $league_team );

        $admin_service = new Championship_Admin_Service(
            $league_service,
            $fixture_service,
            $tournament_service,
            $league_team_repository,
            $team_repository
        );

        $league_service->expects( self::once() )
                       ->method( 'remove_teams_from_league' )
                       ->with( [$league_team_id], $league_id, $season )
                       ->willReturn( [
                           'messages'  => [$league_team_id . ': Matches exist'],
                           'any_error' => true,
                       ] );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: $league_id,
            season: (string)$season,
            post: [
                'action' => 'delete',
                'team'   => [$league_team_id],
                'season' => $season
            ]
        );

        $result = $admin_service->handle_league_teams_action( $dto );

        self::assertEquals( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Matches exist', $result->message );
    }

    public function test_add_teams_to_league_creates_team_if_not_exists_by_name(): void {
        global $racketmanager;
        // Do not overwrite global $racketmanager if it is already set by stubs with necessary methods
        if ( ! isset( $racketmanager ) || ! method_exists( $racketmanager, 'get_options' ) ) {
            $racketmanager = new stdClass();
        }
        $container = $this->createMock( \Racketmanager\Services\Container\Simple_Container::class );
        $racketmanager->container = $container;

        $player_service = $this->createMock( \Racketmanager\Services\Player_Service::class );
        $container->method( 'get' )->with( 'player_service' )->willReturn( $player_service );

        $league_id = 123;
        $season = 2024;
        $team_name = '2_round_match_456';

        $league_service = $this->createMock( League_Service::class );
        $fixture_service = $this->createMock( Fixture_Service::class );
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_team_repository = $this->createMock( League_Team_Repository::class );
        $team_repository = $this->createMock( Team_Repository::class );

        // Mock Team_Repository::find_by_id to return null (team doesn't exist by name)
        $team_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( $team_name )
            ->willReturn( null );

        // Mock Team_Repository::save to return a new team ID
        $new_team_id = 999;
        $team_repository->expects( self::once() )
            ->method( 'save' )
            ->with( self::callback( function ( $team ) use ( $team_name ) {
                return $team instanceof Team && $team->get_name() === $team_name && $team->get_type() === 'S';
            } ) )
            ->willReturn( $new_team_id );

        // Mock League_Service::add_team_to_league to be called with the new team ID
        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( $new_team_id, $league_id, $season );

        $admin_service = new Championship_Admin_Service(
            $league_service,
            $fixture_service,
            $tournament_service,
            $league_team_repository,
            $team_repository
        );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: $league_id,
            season: (string)$season,
            post: [
                'team' => [$team_name],
                'action' => 'add'
            ]
        );

        $admin_service->add_teams_to_league( $dto );
    }

    public function test_add_teams_to_league_uses_existing_team_if_exists_by_name(): void {
        $league_id = 123;
        $season = 2024;
        $team_name = 'Existing Team';
        $team_id = 555;

        $team = $this->createMock( Team::class );
        $team->method( 'get_id' )->willReturn( $team_id );

        $league_service = $this->createMock( League_Service::class );
        $fixture_service = $this->createMock( Fixture_Service::class );
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_team_repository = $this->createMock( League_Team_Repository::class );
        $team_repository = $this->createMock( Team_Repository::class );

        $team_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( $team_name )
            ->willReturn( $team );

        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( $team_id, $league_id, $season );

        $admin_service = new Championship_Admin_Service(
            $league_service,
            $fixture_service,
            $tournament_service,
            $league_team_repository,
            $team_repository
        );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: $league_id,
            season: (string)$season,
            post: [
                'team' => [$team_name],
                'action' => 'add'
            ]
        );

        $admin_service->add_teams_to_league( $dto );
    }
}
