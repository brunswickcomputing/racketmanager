<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Club;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Team_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Team_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_add_team_guard_failure(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-team', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle( array(), array( 'addTeam' => '1' ) );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_team_success(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $club_service->expects( self::once() )
            ->method( 'create_team' )
            ->with( 5, 'Men' );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addTeam'   => '1',
                'club'      => 5,
                'team_type' => 'Men',
            )
        );

        self::assertSame( 'Team added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_edit_team_success(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $team_service->expects( self::once() )
            ->method( 'amend_team_for_club' )
            ->with( 20, 5, 'Mixed' )
            ->willReturn( true );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'editTeam'  => '1',
                'team_id'   => 20,
                'clubId'    => 5,
                'team_type' => 'Mixed',
            )
        );

        self::assertSame( 'Team updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_teams_guard_failure(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'del_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doTeamDel' => '1',
                'action'    => 'delete',
            )
        );

        self::assertSame( 'Permission denied', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_teams_empty_selection(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doTeamDel' => '1',
                'action'    => 'delete',
                'team'      => array(),
            )
        );

        self::assertSame( 'No teams selected', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_delete_teams_success_and_failure(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $team1 = new \Racketmanager\Domain\Team();
        $team1->set_name( 'Team One' );
        $team2 = new \Racketmanager\Domain\Team();
        $team2->set_name( 'Team Two' );

        $team_service->expects( self::exactly( 2 ) )
            ->method( 'get_team_by_id' )
            ->willReturnMap( [
                [ 10, $team1 ],
                [ 20, $team2 ],
            ] );

        $team_service->expects( self::exactly( 2 ) )
            ->method( 'delete_team' )
            ->willReturnMap( [
                [ 10, true ],
                [ 20, false ],
            ] );

        $dispatcher = new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doTeamDel' => '1',
                'action'    => 'delete',
                'team'      => array( 10, 20 ),
            )
        );

        self::assertSame( 'Team One deleted<br>Team Two failed to delete', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
