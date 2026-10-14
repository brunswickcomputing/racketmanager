<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Schedule_Service_Test extends TestCase {

    private Fixture_Maintenance_Service $fixture_maintenance_service;
    private League_Schedule_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $this->service                     = new League_Schedule_Service( $this->fixture_maintenance_service );
        $GLOBALS['wp_stubs_events']        = array();
        $GLOBALS['wp_stubs_leagues']       = array();
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_events'], $GLOBALS['wp_stubs_leagues'], $GLOBALS['racketmanager'] );
        parent::tearDown();
    }

    public function test_delete_event_matches_success_deletes_each_fixture(): void {
        $event     = $this->createMock( Event::class );
        $event->id = 10;
        $event->method( 'get_season' )->willReturn( '2026' );

        $league = $this->createMock( League::class );
        $match1     = new stdClass();
        $match1->id = 101;
        $match2     = new stdClass();
        $match2->id = 102;
        $league->method( 'get_matches' )->with( array( 'season' => '2026' ) )->willReturn( array( $match1, $match2 ) );

        $event->method( 'get_leagues' )->willReturn( array( $league ) );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'delete_fixture' )
            ->willReturnCallback( function ( int $id ) {
                self::assertContains( $id, array( 101, 102 ) );
                return true;
            } );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( 'Matches deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_event_matches_completed_matches_returns_error(): void {
        $event     = $this->createMock( Event::class );
        $event->id = 10;
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 3 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::never() )
            ->method( 'delete_fixture' );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( 'Event has completed matches', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_event_matches_partial_delete_returns_warning(): void {
        $event1     = $this->createMock( Event::class );
        $event1->id = 10;
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2     = $this->createMock( Event::class );
        $event2->id = 20;
        $event2->method( 'get_season' )->willReturn( '2026' );

        $league = $this->createMock( League::class );
        $match     = new stdClass();
        $match->id = 201;
        $league->method( 'get_matches' )->willReturn( array( $match ) );
        $event2->method( 'get_leagues' )->willReturn( array( $league ) );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )
            ->willReturnCallback( function ( array $args ) {
                return ( 10 === $args['event_id'] ) ? 2 : 0;
            } );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::once() )
            ->method( 'delete_fixture' )
            ->with( 201 );

        $result = $this->service->delete_event_matches( array( 10, 20 ) );

        self::assertSame( 'Some matches deleted, but some events have completed matches', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_schedule_league_matches_validation_fails_with_existing_matches(): void {
        $event       = $this->createMock( Event::class );
        $event->id   = 10;
        $event->name = 'Summer League';
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 5 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Summer League already has matches scheduled for 2026', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_differing_match_days(): void {
        $event1                 = $this->createMock( Event::class );
        $event1->id             = 10;
        $event1->name           = 'Event 1';
        $event1->current_season = array(
            'num_match_days' => 5,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2                 = $this->createMock( Event::class );
        $event2->id             = 20;
        $event2->name           = 'Event 2';
        $event2->current_season = array(
            'num_match_days' => 7,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event2->method( 'get_season' )->willReturn( '2026' );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10, 20 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different number of match days', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_differing_match_dates(): void {
        $event1                 = $this->createMock( Event::class );
        $event1->id             = 10;
        $event1->name           = 'Event 1';
        $event1->current_season = array(
            'num_match_days' => 5,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => false,
        );
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2                 = $this->createMock( Event::class );
        $event2->id             = 20;
        $event2->name           = 'Event 2';
        $event2->current_season = array(
            'num_match_days' => 5,
            'match_dates'    => array( '2026-06-01', '2026-06-08' ),
            'home_away'      => false,
        );
        $event2->method( 'get_season' )->willReturn( '2026' );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10, 20 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different match dates', $result->message );
    }
}
