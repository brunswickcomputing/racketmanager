<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Deletion_Service_Test extends TestCase {

    private function service( Competition_Repository_Interface $competitions, Event_Repository_Interface $events, League_Service $leagues ): Competition_Service {
        return new Competition_Service(
            ( new \ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor(),
            $competitions,
            $this->createStub( Club_Repository_Interface::class ),
            $events,
            $this->createStub( League_Repository_Interface::class ),
            $this->createStub( League_Team_Repository_Interface::class ),
            $this->createStub( Season_Repository_Interface::class ),
            $this->createStub( Team_Repository_Interface::class ),
            null,
            static fn() => $leagues
        );
    }

    private function event(): Event {
        $event = new Event( (object) array( 'id' => 55, 'competition_id' => 10, 'name' => 'Doubles', 'type' => 'doubles' ) );
        $event->set_seasons( array( 2025 => array( 'name' => '2025' ), 2026 => array( 'name' => '2026' ) ) );
        return $event;
    }

    public function test_delete_event_delegates_every_league_before_deleting_event(): void {
        $event   = $this->event();
        $calls   = array();
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::exactly( 2 ) )->method( 'delete_league' )->willReturnCallback(
            static function ( int $id ) use ( &$calls ): void { $calls[] = $id; }
        );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->expects( self::once() )->method( 'query_leagues' )->with( 55 )
            ->willReturn( array( (object) array( 'id' => 3 ), (object) array( 'id' => 4 ) ) );
        $events->expects( self::once() )->method( 'delete' )->with( 55 )->willReturnCallback(
            static function () use ( &$calls ): bool {
                self::assertSame( array( 3, 4 ), $calls );
                return true;
            }
        );

        $this->service( $this->createStub( Competition_Repository_Interface::class ), $events, $leagues )->delete_event( $event );
    }

    public function test_delete_event_failure_does_not_delete_event(): void {
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::once() )->method( 'delete_league' )->with( 3 )
            ->willThrowException( new \RuntimeException( 'Fixture delete failed' ) );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 3 ) ) );
        $events->expects( self::never() )->method( 'delete' );

        $this->expectExceptionMessage( 'Fixture delete failed' );
        $this->service( $this->createStub( Competition_Repository_Interface::class ), $events, $leagues )->delete_event( $this->event() );
    }

    public function test_delete_event_without_leagues_does_not_require_league_service(): void {
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->method( 'query_leagues' )->willReturn( array() );
        $events->expects( self::once() )->method( 'delete' )->with( 55 )->willReturn( true );
        $service = new Competition_Service(
            ( new \ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor(),
            $this->createStub( Competition_Repository_Interface::class ),
            $this->createStub( Club_Repository_Interface::class ),
            $events,
            $this->createStub( League_Repository_Interface::class ),
            $this->createStub( League_Team_Repository_Interface::class ),
            $this->createStub( Season_Repository_Interface::class ),
            $this->createStub( Team_Repository_Interface::class )
        );

        $service->delete_event( $this->event() );
    }

    public function test_delete_event_season_delegates_fixture_deletion_and_persists_remaining_seasons(): void {
        $event = $this->event();
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::once() )->method( 'delete_season_fixtures' )->with( 3, 2026 );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 3 ) ) );
        $events->expects( self::once() )->method( 'delete_league_teams_for_season' )->with( 3, '2026' )->willReturn( true );
        $events->expects( self::once() )->method( 'update_seasons' )
            ->with( 55, array( 2025 => array( 'name' => '2025' ) ) )->willReturn( true );

        $this->service( $this->createStub( Competition_Repository_Interface::class ), $events, $leagues )
            ->delete_event_season( $event, 2026 );
        self::assertArrayNotHasKey( 2026, $event->get_seasons() );
    }

    public function test_delete_event_season_failure_preserves_season_and_teams(): void {
        $event = $this->event();
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::once() )->method( 'delete_season_fixtures' )
            ->willThrowException( new \InvalidArgumentException( 'Invalid season' ) );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 3 ) ) );
        $events->expects( self::never() )->method( 'delete_league_teams_for_season' );
        $events->expects( self::never() )->method( 'update_seasons' );

        try {
            $this->service( $this->createStub( Competition_Repository_Interface::class ), $events, $leagues )
                ->delete_event_season( $event, 2026 );
            self::fail( 'Expected invalid season exception' );
        } catch ( \InvalidArgumentException $e ) {
            self::assertSame( 'Invalid season', $e->getMessage() );
            self::assertArrayHasKey( 2026, $event->get_seasons() );
        }
    }

    public function test_delete_event_season_repository_failure_does_not_change_event_seasons(): void {
        $event = $this->event();
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::once() )->method( 'delete_season_fixtures' )->with( 3, 2026 );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 3 ) ) );
        $events->expects( self::once() )->method( 'delete_league_teams_for_season' )->willReturn( true );
        $events->expects( self::once() )->method( 'update_seasons' )->willReturn( false );

        try {
            $this->service( $this->createStub( Competition_Repository_Interface::class ), $events, $leagues )
                ->delete_event_season( $event, 2026 );
            self::fail( 'Expected persistence failure' );
        } catch ( \RuntimeException $e ) {
            self::assertSame( 'Could not update seasons for event 55', $e->getMessage() );
            self::assertArrayHasKey( 2026, $event->get_seasons() );
        }
    }

    public function test_remove_delegates_event_deletion_and_does_not_delete_competition_on_error(): void {
        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_id' )->willReturn( 10 );
        $competitions = $this->createMock( Competition_Repository_Interface::class );
        $competitions->expects( self::once() )->method( 'find_by_id' )->with( 10 )->willReturn( $competition );
        $competitions->expects( self::never() )->method( 'delete' );
        $events = $this->createMock( Event_Repository_Interface::class );
        $events->expects( self::once() )->method( 'find_by_competition_id' )->with( 10 )->willReturn( array( $this->event() ) );
        $events->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 3 ) ) );
        $events->expects( self::never() )->method( 'delete' );
        $leagues = $this->createMock( League_Service::class );
        $leagues->expects( self::once() )->method( 'delete_league' )->with( 3 )
            ->willThrowException( new \RuntimeException( 'Fixture delete failed' ) );

        $this->expectExceptionMessage( 'Fixture delete failed' );
        $this->service( $competitions, $events, $leagues )->remove( 10 );
    }
}