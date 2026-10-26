<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
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

    public function test_schedule_passes_posted_season_and_events_to_service(): void {
        $service = $this->createMock( League_Schedule_Service::class );
        $service->expects( self::once() )
            ->method( 'schedule_league_matches' )
            ->with( array( '10', '20' ), '2025' )
            ->willReturn( new Action_Result_DTO( message: 'ok', message_type: Admin_Message_Type::SUCCESS ) );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $service, $guard );
        $result     = $dispatcher->handle(
            array( 'season' => '2026' ),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'schedule',
                'event'          => array( 3 => '10', 7 => '20' ),
                'season'         => ' 2025 ',
            )
        );

        self::assertSame( 'ok', $result->message );
    }

    public function test_schedule_falls_back_to_query_season_when_not_posted(): void {
        $service = $this->createMock( League_Schedule_Service::class );
        $service->expects( self::once() )
            ->method( 'schedule_league_matches' )
            ->with( array( '10' ), '2026' )
            ->willReturn( new Action_Result_DTO() );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $service, $guard );
        $dispatcher->handle(
            array( 'season' => '2026' ),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'schedule',
                'event'          => array( '10' ),
            )
        );
    }

    public function test_schedule_passes_null_season_when_absent_everywhere(): void {
        $service = $this->createMock( League_Schedule_Service::class );
        $service->expects( self::once() )
            ->method( 'schedule_league_matches' )
            ->with( array( '10' ), null )
            ->willReturn( new Action_Result_DTO() );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $service, $guard );
        $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'schedule',
                'event'          => array( '10' ),
                'season'         => '',
            )
        );
    }

    public function test_delete_passes_events_and_null_season_to_service(): void {
        $service = $this->createMock( League_Schedule_Service::class );
        $service->expects( self::once() )
            ->method( 'delete_event_matches' )
            ->with( array( '10' ), null )
            ->willReturn( new Action_Result_DTO( message: 'Matches deleted', message_type: Admin_Message_Type::SUCCESS ) );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'delete',
                'event'          => array( '10' ),
            )
        );

        self::assertSame( 'Matches deleted', $result->message );
    }

    public function test_delete_passes_posted_season_to_service(): void {
        $service = $this->createMock( League_Schedule_Service::class );
        $service->expects( self::once() )
            ->method( 'delete_event_matches' )
            ->with( array( '10' ), '2025' )
            ->willReturn( new Action_Result_DTO( message: 'Matches deleted', message_type: Admin_Message_Type::SUCCESS ) );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Schedule_Action_Dispatcher( $service, $guard );
        $dispatcher->handle(
            array( 'season' => '2026' ),
            array(
                'scheduleAction' => '1',
                'actionSchedule' => 'delete',
                'event'          => array( '10' ),
                'season'         => '2025',
            )
        );
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
