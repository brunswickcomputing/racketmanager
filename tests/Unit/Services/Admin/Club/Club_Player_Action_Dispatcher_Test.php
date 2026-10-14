<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Club;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Player_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );
        $guard                = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_add_player_guard_failure(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );
        $guard                = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard );
        $result     = $dispatcher->handle( array(), array( 'addPlayer' => '1' ) );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_player_success(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );
        $guard                = $this->createStub( Action_Guard_Interface::class );

        $registration_service->expects( self::once() )
            ->method( 'register_player_to_club' )
            ->willReturn( 'Player registered' );

        $dispatcher = new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array( 'club_id' => 12 ),
            array(
                'addPlayer' => '1',
                'club_Id'   => 12,
            )
        );

        self::assertSame( 'Player registered', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_club_players_success(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );
        $guard                = $this->createStub( Action_Guard_Interface::class );

        $registration_service->expects( self::exactly( 2 ) )
            ->method( 'remove_registration' );

        $dispatcher = new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doClubPlayerDel' => '1',
                'action'          => 'delete',
                'clubPlayer'      => array( 101, 102 ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_schedule_player_ratings_success(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );
        $guard                = $this->createStub( Action_Guard_Interface::class );

        $player_service->expects( self::once() )
            ->method( 'schedule_player_ratings' )
            ->with( 15 );

        $dispatcher = new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doPlayerRatings' => '1',
                'club_id'         => 15,
            )
        );

        self::assertSame( 'Player ratings scheduled', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
