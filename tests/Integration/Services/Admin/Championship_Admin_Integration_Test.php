<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Championship_Admin_Integration_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_real_instance_without_constructor( string $class_name ) {
        $reflection = new ReflectionClass( $class_name );
        return $reflection->newInstanceWithoutConstructor();
    }

    private function create_admin_service(
        ?League_Service $league_service = null,
        ?Fixture_Service $fixture_service = null,
        ?Tournament_Service $tournament_service = null,
        ?League_Team_Repository_Interface $league_team_repository = null,
        ?Team_Repository_Interface $team_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?Championship_Manager $championship_manager = null,
        ?Event_Repository_Interface $event_repository = null
    ): Championship_Admin_Service {
        $fixture_repo = $fixture_repository ?? $this->createStub( Fixture_Repository_Interface::class );
        $league_service = $league_service ?? $this->createMock( League_Service::class );
        $event_repo = $event_repository ?? $this->createStub( Event_Repository_Interface::class );
        return new Championship_Admin_Service(
            $league_service,
            $fixture_service ?? $this->createMock( Fixture_Service::class ),
            $tournament_service ?? $this->createMock( Tournament_Service::class ),
            $league_team_repository ?? $this->createStub( League_Team_Repository_Interface::class ),
            $team_repository ?? $this->createStub( Team_Repository_Interface::class ),
            $fixture_repo,
            $championship_manager ?? new Championship_Manager(
                $this->createStub( Result_Service::class ),
                $fixture_repo,
                $league_service,
                $event_repo
            ),
            $event_repo
        );
    }

    private function create_draw_dto( int $league_id = 123, int $season = 2024, array $post = [] ): Draw_Action_Request_DTO {
        return new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: $league_id,
            season: (string) $season,
            post: $post
        );
    }

    public function test_championship_admin_service_handles_team_has_matches_exception(): void {
        $league_id      = 123;
        $season         = 2024;
        $league_team_id = 456;

        $league = $this->create_real_instance_without_constructor( League::class );
        $league->id = $league_id;

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'get_league' )
            ->with( $league_id )
            ->willReturn( $league );
        $league_service->expects( self::once() )
                       ->method( 'remove_teams_from_league' )
                       ->with( [$league_team_id], $league_id, $season )
                       ->willReturn( [
                           'messages'  => [$league_team_id . ': Matches exist'],
                           'any_error' => true,
                       ] );

        $admin_service = $this->create_admin_service( league_service: $league_service );

        $dto = $this->create_draw_dto(
            league_id: $league_id,
            season: $season,
            post: [
                'action' => 'delete',
                'team'   => [$league_team_id],
                'season' => $season,
            ]
        );

        $result = $admin_service->handle_league_teams_action( $dto );

        self::assertEquals( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Matches exist', $result->message );
    }

    public function test_add_teams_to_league_creates_team_if_not_exists_by_name(): void {
        $league_id = 123;
        $season = 2024;
        $team_name = '2_round_match_456';

        $league_service = $this->createMock( League_Service::class );
        $team_repository = $this->createMock( Team_Repository_Interface::class );

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

        $admin_service = $this->create_admin_service(
            league_service: $league_service,
            team_repository: $team_repository
        );

        $dto = $this->create_draw_dto(
            league_id: $league_id,
            season: $season,
            post: [
                'team'   => [$team_name],
                'action' => 'add',
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
        $team_repository = $this->createMock( Team_Repository_Interface::class );

        $team_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( $team_name )
            ->willReturn( $team );

        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( $team_id, $league_id, $season );

        $admin_service = $this->create_admin_service(
            league_service: $league_service,
            team_repository: $team_repository
        );

        $dto = $this->create_draw_dto(
            league_id: $league_id,
            season: $season,
            post: [
                'team'   => [$team_name],
                'action' => 'add',
            ]
        );

        $admin_service->add_teams_to_league( $dto );
    }

    public function test_update_final_results_includes_updated_fixture_count_in_message(): void {
        $championship = $this->createStub( Championship::class );
        $league = $this->createMock( League::class );
        $league->championship = $championship;

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'get_league' )
            ->with( 123 )
            ->willReturn( $league );

        $championship->method( 'num_rounds' )->willReturn( 1 );
        $fixture = $this->createStub( Fixture::class );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $fixture );
        $result_service = $this->createMock( Result_Service::class );
        $result_service->expects( self::once() )
            ->method( 'apply_to_fixture' )
            ->with( $fixture, self::isInstanceOf( \Racketmanager\Domain\Result\Result::class ) );
        $championship_manager = new Championship_Manager(
            $result_service,
            $fixture_repository,
            $league_service,
            $this->createStub( Event_Repository_Interface::class )
        );

        $admin_service = $this->create_admin_service(
            league_service: $league_service,
            fixture_repository: $fixture_repository,
            championship_manager: $championship_manager
        );
        $result = $admin_service->update_final_results( $this->create_draw_dto(
            league_id: 123,
            post: [
                'fixtures'    => [ 10 ],
                'home_points' => [ 10 => 6 ],
                'away_points' => [ 10 => 3 ],
                'custom'      => [ 10 => [] ],
                'round'       => 1,
            ]
        ) );

        self::assertSame( 'Updated Results of 1 fixtures', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
