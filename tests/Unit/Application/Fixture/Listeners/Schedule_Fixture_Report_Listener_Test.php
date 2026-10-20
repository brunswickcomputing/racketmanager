<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Fixture\Listeners;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\Listeners\Schedule_Fixture_Report_Listener;
use Racketmanager\Domain\Fixture\Events\Fixture_Result_Finalized_Event;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Schedule_Fixture_Report_Listener_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_scheduled_events'] = [];
        unset( $GLOBALS['racketmanager'], $GLOBALS['wp_stubs_cache'], $GLOBALS['wp_stubs_cache_enabled'], $GLOBALS['wp_stubs_cache_mode'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_listener_schedules_report_when_competition_type_and_reporting_service_present(): void {
        $reporting_service = $this->createStub( Result_Reporting_Service::class );
        $listener          = new Schedule_Fixture_Report_Listener( $reporting_service );
        $event             = new Fixture_Result_Finalized_Event(
            fixture_id: 42,
            league_id: 10,
            season: '2026',
            competition_type: 'LTA'
        );

        $listener( $event );

        $this->assertCount( 1, $GLOBALS['wp_scheduled_events'] );
        $this->assertSame( 'racketmanager_report_fixture_result', $GLOBALS['wp_scheduled_events'][0]['hook'] );
        $this->assertSame( [ 42, 'LTA' ], $GLOBALS['wp_scheduled_events'][0]['args'] );
    }

    public function test_listener_does_not_schedule_when_reporting_service_is_null(): void {
        $listener = new Schedule_Fixture_Report_Listener( null );
        $event    = new Fixture_Result_Finalized_Event(
            fixture_id: 42,
            league_id: 10,
            season: '2026',
            competition_type: 'LTA'
        );

        $listener( $event );

        $this->assertCount( 0, $GLOBALS['wp_scheduled_events'] );
    }

    public function test_listener_does_not_schedule_when_competition_type_is_empty_or_null(): void {
        $reporting_service = $this->createStub( Result_Reporting_Service::class );
        $listener          = new Schedule_Fixture_Report_Listener( $reporting_service );

        $null_type_event = new Fixture_Result_Finalized_Event(
            fixture_id: 43,
            league_id: 10,
            season: '2026',
            competition_type: null
        );
        $listener( $null_type_event );

        $empty_type_event = new Fixture_Result_Finalized_Event(
            fixture_id: 44,
            league_id: 10,
            season: '2026',
            competition_type: ''
        );
        $listener( $empty_type_event );

        $this->assertCount( 0, $GLOBALS['wp_scheduled_events'] );
    }

    public function test_dispatcher_integration_with_listener(): void {
        $reporting_service = $this->createStub( Result_Reporting_Service::class );
        $dispatcher        = new Simple_Domain_Event_Dispatcher();
        $listener          = new Schedule_Fixture_Report_Listener( $reporting_service );

        $dispatcher->subscribe( Fixture_Result_Finalized_Event::class, $listener );

        $dispatcher->dispatch(
            new Fixture_Result_Finalized_Event(
                fixture_id: 99,
                league_id: 1,
                season: '2026',
                competition_type: 'tournament'
            )
        );

        $this->assertCount( 1, $GLOBALS['wp_scheduled_events'] );
        $this->assertSame( 'racketmanager_report_fixture_result', $GLOBALS['wp_scheduled_events'][0]['hook'] );
        $this->assertSame( [ 99, 'tournament' ], $GLOBALS['wp_scheduled_events'][0]['args'] );
    }

    public function test_container_boot_subscribes_listener_and_schedules_on_event(): void {
        $wpdb = $this->createStub( wpdb::class );
        $GLOBALS['wpdb'] = $wpdb;

        $app = $this->createStub( RacketManager::class );
        $app->method( 'get_options' )->willReturn( [] );

        $container = Container_Bootstrap::boot( $app );
        $dispatcher = $container->get( 'domain_event_dispatcher' );

        $dispatcher->dispatch(
            new Fixture_Result_Finalized_Event(
                fixture_id: 777,
                league_id: 10,
                season: '2026',
                competition_type: 'league'
            )
        );

        $this->assertCount( 1, $GLOBALS['wp_scheduled_events'] );
        $this->assertSame( 'racketmanager_report_fixture_result', $GLOBALS['wp_scheduled_events'][0]['hook'] );
        $this->assertSame( [ 777, 'league' ], $GLOBALS['wp_scheduled_events'][0]['args'] );
    }

    public function test_dispatcher_propagates_listener_exceptions(): void {
        $dispatcher = new Simple_Domain_Event_Dispatcher();
        $dispatcher->subscribe(
            Fixture_Result_Finalized_Event::class,
            function () {
                throw new \RuntimeException( 'Simulated listener failure' );
            }
        );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Simulated listener failure' );

        $dispatcher->dispatch(
            new Fixture_Result_Finalized_Event(
                fixture_id: 1,
                league_id: 1,
                season: '2026',
                competition_type: 'league'
            )
        );
    }
}
