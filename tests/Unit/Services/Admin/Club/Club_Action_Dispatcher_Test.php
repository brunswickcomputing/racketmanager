<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Club;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_add_club_guard_failure(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-club', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle( array(), array( 'addClub' => '1' ) );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_club_validation_failure(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addClub'   => '1',
                'club'      => '',
                'shortcode' => '',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertNotEmpty( $result->message );
    }

    public function test_add_club_success(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $new_club     = $this->createStub( Club::class );
        $new_club->id = 42;

        $club_service->expects( self::once() )
            ->method( 'add_club' )
            ->willReturn( $new_club );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addClub'   => '1',
                'club'      => 'Test Club',
                'shortcode' => 'TC',
                'type'      => 'tennis',
                'address'   => '123 Court Road',
            )
        );

        self::assertSame( 'Club added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 42, $result->entity_id );
        self::assertSame( 'club', $result->query_params['view'] );
        self::assertSame( 42, $result->query_params['club_id'] );
    }

    public function test_edit_club_guard_failure(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-club', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle( array(), array( 'editClub' => '1' ) );

        self::assertSame( 'Permission denied', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_clubs_empty_warning(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doClubDel' => '1',
                'action'    => 'delete',
                'club'      => array(),
            )
        );

        self::assertSame( 'No clubs selected', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_delete_clubs_success(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $club_service->expects( self::exactly( 2 ) )
            ->method( 'remove_club' );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doClubDel' => '1',
                'action'    => 'delete',
                'club'      => array( 10, 11 ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_schedule_player_ratings_success(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( true );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSchedulePlayerRatings' => '1',
            )
        );

        self::assertSame( 'Player ratings calculation scheduled', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_schedule_player_ratings_already_scheduled(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( false );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSchedulePlayerRatings' => '1',
            )
        );

        self::assertSame( 'Player ratings calculation already scheduled', $result->message );
        self::assertSame( Admin_Message_Type::INFO, $result->message_type );
    }

    public function test_schedule_player_ratings_wp_error(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( new \WP_Error( 'error', 'Failed' ) );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSchedulePlayerRatings' => '1',
            )
        );

        self::assertSame( 'Error scheduling player ratings calculation', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_schedule_player_ratings_exception(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createMock( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willThrowException( new \Exception( 'DB connection failure' ) );

        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doSchedulePlayerRatings' => '1',
            )
        );

        self::assertSame( 'DB connection failure', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
