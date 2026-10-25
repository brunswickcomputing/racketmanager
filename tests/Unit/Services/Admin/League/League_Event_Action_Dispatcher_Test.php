<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Event_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_add_league_guard_failure(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-league', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addLeague' => '1',
            )
        );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_league_service_exception(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Premier Division' )
            ->willThrowException( new League_Not_Found_Exception( 'Event not found' ) );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addLeague'    => '1',
                'event_id'     => 5,
                'league_title' => 'Premier Division',
            )
        );

        self::assertSame( 'Event not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_league_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Premier Division' );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addLeague'    => '1',
                'event_id'     => 5,
                'league_title' => 'Premier Division',
            )
        );

        self::assertSame( 'League added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_leagues_guard_failure(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'leagues-bulk', 'del_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionLeague' => '1',
                'action'         => 'delete',
            )
        );

        self::assertSame( 'Permission denied', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_leagues_empty_returns_empty_dto(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Event_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionLeague' => '1',
                'action'         => 'delete',
                'league'         => array(),
            )
        );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_delete_leagues_delegates_to_league_service(): void {
        $league = $this->createMock( \Racketmanager\Domain\Competition\League::class );
        $league->title = 'Division 1';
        $GLOBALS['wp_stubs_leagues'][51] = $league;
        $service = $this->createMock( League_Service::class );
        $service->expects( self::once() )->method( 'delete_league' )->with( $league );

        $result = ( new League_Event_Action_Dispatcher( $service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array(), array( 'doActionLeague' => '1', 'action' => 'delete', 'league' => array( 51 ) )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Division 1 deleted', $result->message );
        unset( $GLOBALS['wp_stubs_leagues'][51] );
    }

    public function test_delete_leagues_maps_service_failure_to_error(): void {
        $league = $this->createMock( \Racketmanager\Domain\Competition\League::class );
        $GLOBALS['wp_stubs_leagues'][52] = $league;
        $service = $this->createMock( League_Service::class );
        $service->expects( self::once() )->method( 'delete_league' )->with( $league )
            ->willThrowException( new \RuntimeException( 'Fixture delete failed' ) );

        $result = ( new League_Event_Action_Dispatcher( $service, $this->createStub( Action_Guard_Interface::class ) ) )->handle(
            array(), array( 'doActionLeague' => '1', 'action' => 'delete', 'league' => array( 52 ) )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Fixture delete failed', $result->message );
        unset( $GLOBALS['wp_stubs_leagues'][52] );
    }
}
