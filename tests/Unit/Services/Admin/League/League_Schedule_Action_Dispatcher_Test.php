<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Schedule_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $maintenance_service = $this->createMock( League_Schedule_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new League_Schedule_Action_Dispatcher( $maintenance_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_schedule_action_guard_failure(): void {
        $maintenance_service = $this->createMock( League_Schedule_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_schedule-matches', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new League_Schedule_Action_Dispatcher( $maintenance_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
            )
        );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_schedule_empty_events_warning(): void {
        $maintenance_service = $this->createMock( League_Schedule_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $maintenance_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'schedule',
                'event'          => array(),
            )
        );

        self::assertSame( 'No events selected', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_schedule_unrecognized_action_warning(): void {
        $maintenance_service = $this->createMock( League_Schedule_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $maintenance_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'unknown',
            )
        );

        self::assertSame( 'No action specified', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_delete_empty_events_warning(): void {
        $maintenance_service = $this->createMock( League_Schedule_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $maintenance_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'delete',
                'event'          => array(),
            )
        );

        self::assertSame( 'No events selected', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }
}
