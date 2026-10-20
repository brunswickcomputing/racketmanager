<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Racketmanager\Application\Fixture\Listeners\Schedule_Fixture_Report_Listener;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Domain\Fixture\Events\Fixture_Result_Finalized_Event;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Finalization_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Fixture_Finalization_Service $service;
    private Standings_Service|Stub $standings_service;
    private Knockout_Progression_Service|MockObject $progression_service;
    private Result_Reporting_Service|Stub $reporting_service;
    private Simple_Domain_Event_Dispatcher $dispatcher;
    private Schedule_Fixture_Report_Listener $listener;
    private Fixture|Stub $fixture;
    private League|MockObject $league;
    private Result|Stub $result;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_scheduled_events'] = [];

        $this->progression_service = $this->createMock( Knockout_Progression_Service::class );
        $this->reporting_service   = $this->createStub( Result_Reporting_Service::class );
        $this->dispatcher          = new Simple_Domain_Event_Dispatcher();
        $this->listener            = new Schedule_Fixture_Report_Listener( $this->reporting_service );
        $this->dispatcher->subscribe( Fixture_Result_Finalized_Event::class, $this->listener );

        $this->service = new Fixture_Finalization_Service(
            null, // standings_service
            $this->progression_service,
            $this->reporting_service,
            $this->dispatcher
        );

        $this->fixture = $this->createStub( Fixture::class );
        $this->league  = $this->createMock( League::class );
        $this->result  = $this->createStub( Result::class );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_finalize_updates_standings_when_requested(): void {
        $this->fixture->method( 'get_season' )->willReturn( '2026' );

        $this->league->expects( $this->once() )
            ->method( 'update_standings' )
            ->with( '2026' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, true );

        $this->assertContains( Fixture_Update_Status::TABLE_UPDATED, $outcomes );
    }

    public function test_finalize_skips_standings_when_not_requested(): void {
        $this->league->expects( $this->never() )
            ->method( 'update_standings' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );

        $this->assertNotContains( Fixture_Update_Status::TABLE_UPDATED, $outcomes );
    }

    public function test_finalize_triggers_progression_for_draw_stage(): void {
        $this->fixture->method( 'get_league_id' )->willReturn( 123 );
        $this->league->method( 'get_competition_type' )->willReturn( 'tournament' );

        $this->league->is_championship = true;
        $this->league->method( 'get_id' )->willReturn( 123 );
        $this->league->method( 'get_name' )->willReturn( 'Tournament Draw' );
        $this->league->method( 'get_event_id' )->willReturn( 789 );

        $this->progression_service->expects( $this->once() )
            ->method( 'progress_winner' )
            ->with( $this->isInstanceOf( Stage::class ), $this->fixture, $this->league );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );

        $this->assertContains( Fixture_Update_Status::PROGRESSED, $outcomes );
    }

    public function test_finalize_schedules_external_report(): void {
        $this->fixture->method( 'get_id' )->willReturn( 456 );
        $this->league->method( 'get_competition_type' )->willReturn( 'LTA' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );

        $this->assertIsArray( $outcomes );
        $this->assertCount( 1, $GLOBALS['wp_scheduled_events'] );
        $this->assertSame( 'racketmanager_report_fixture_result', $GLOBALS['wp_scheduled_events'][0]['hook'] );
        $this->assertSame( [ 456, 'LTA' ], $GLOBALS['wp_scheduled_events'][0]['args'] );
    }

    public function test_finalize_dispatches_fixture_result_finalized_event(): void {
        $mock_dispatcher = $this->createMock( Domain_Event_Dispatcher_Interface::class );

        $this->fixture->method( 'get_id' )->willReturn( 55 );
        $this->fixture->method( 'get_league_id' )->willReturn( 12 );
        $this->fixture->method( 'get_season' )->willReturn( '2026' );

        $this->league->method( 'get_id' )->willReturn( 12 );
        $this->league->method( 'get_name' )->willReturn( 'League 1' );
        $this->league->method( 'get_event_id' )->willReturn( 789 );
        $this->league->method( 'get_competition_type' )->willReturn( 'LTA' );
        $this->league->expects( $this->once() )->method( 'update_standings' )->with( '2026' );

        $dispatched_events = [];
        $mock_dispatcher->expects( $this->once() )
            ->method( 'dispatch' )
            ->willReturnCallback( function ( Domain_Event_Interface $event ) use ( &$dispatched_events ) {
                $dispatched_events[] = $event;
            } );

        $service = new Fixture_Finalization_Service(
            standings_service: null,
            progression_service: $this->progression_service,
            event_dispatcher: $mock_dispatcher
        );

        $outcomes = $service->finalize( $this->fixture, $this->league, $this->result, true );

        $this->assertContains( Fixture_Update_Status::TABLE_UPDATED, $outcomes );
        $this->assertCount( 1, $dispatched_events );
        $this->assertInstanceOf( Fixture_Result_Finalized_Event::class, $dispatched_events[0] );
        $this->assertSame( 55, $dispatched_events[0]->fixture_id );
        $this->assertSame( 12, $dispatched_events[0]->league_id );
        $this->assertSame( '2026', $dispatched_events[0]->season );
        $this->assertSame( 'LTA', $dispatched_events[0]->competition_type );
    }

    public function test_finalize_integration_with_real_dispatcher_and_schedule_report_listener(): void {
        $this->fixture->method( 'get_id' )->willReturn( 88 );
        $this->fixture->method( 'get_league_id' )->willReturn( 14 );
        $this->fixture->method( 'get_season' )->willReturn( '2026' );

        $this->league->method( 'get_id' )->willReturn( 14 );
        $this->league->method( 'get_name' )->willReturn( 'League 2' );
        $this->league->method( 'get_event_id' )->willReturn( 789 );
        $this->league->method( 'get_competition_type' )->willReturn( 'LTA' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );

        $this->assertIsArray( $outcomes );
        $this->assertCount( 1, $GLOBALS['wp_scheduled_events'] );
        $this->assertSame( 'racketmanager_report_fixture_result', $GLOBALS['wp_scheduled_events'][0]['hook'] );
        $this->assertSame( [ 88, 'LTA' ], $GLOBALS['wp_scheduled_events'][0]['args'] );
    }

    public function test_finalize_propagates_exception_when_listener_throws(): void {
        $throwing_dispatcher = new Simple_Domain_Event_Dispatcher();
        $throwing_dispatcher->subscribe(
            Fixture_Result_Finalized_Event::class,
            function () {
                throw new \RuntimeException( 'Listener failed' );
            }
        );

        $service = new Fixture_Finalization_Service(
            null,
            $this->progression_service,
            null,
            $throwing_dispatcher
        );

        $this->fixture->method( 'get_id' )->willReturn( 123 );
        $this->league->method( 'get_competition_type' )->willReturn( 'LTA' );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Listener failed' );

        $service->finalize( $this->fixture, $this->league, $this->result, false );
    }
}
