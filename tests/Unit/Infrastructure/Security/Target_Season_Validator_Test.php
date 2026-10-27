<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Infrastructure\Security\Target_Season_Validator;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;

final class Target_Season_Validator_Test extends TestCase {

    private function event_with_seasons( array $seasons ): Event {
        $event          = $this->createStub( Event::class );
        $event->id      = 30;
        $event->seasons = $seasons;
        $event->method( 'get_seasons' )->willReturn( $seasons );

        return $event;
    }

    public function test_league_with_event_id_only_loads_event_through_repository(): void {
        $league = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1', 'event_id' => 30 ] );
        $this->assertNull( $league->event );

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->with( 30 )
            ->willReturn( $this->event_with_seasons( [ '2026' => [ 'name' => '2026' ] ] ) );

        $this->assertSame( '2026', Target_Season_Validator::validate( $league, 'league', '2026', $repository ) );
        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2025', $repository ) );
    }

    public function test_league_whose_event_is_not_found_yields_null(): void {
        $league = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1', 'event_id' => 30 ] );

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_id' )->with( 30 )->willReturn( null );

        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2026', $repository ) );
    }

    public function test_league_without_repository_does_not_look_up_the_event(): void {
        $league = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1', 'event_id' => 30 ] );

        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2026' ) );
        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2026', null ) );
    }

    public function test_league_with_loaded_event_does_not_touch_the_repository(): void {
        $league        = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1', 'event_id' => 30 ] );
        $league->event = $this->event_with_seasons( [ '2026' => [ 'name' => '2026' ] ] );

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertSame( '2026', Target_Season_Validator::validate( $league, 'league', '2026', $repository ) );
    }

    public function test_league_without_event_id_does_not_touch_the_repository(): void {
        $league = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1' ] );

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2026', $repository ) );
    }

    public function test_rejects_malformed_or_missing_season_before_any_lookup(): void {
        $league = League::from_object( (object) [ 'id' => 50, 'title' => 'Division 1', 'event_id' => 30 ] );

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertNull( Target_Season_Validator::validate( $league, 'league', null, $repository ) );
        $this->assertNull( Target_Season_Validator::validate( $league, 'league', '2026<script>', $repository ) );
    }

    public function test_tournament_target_ignores_the_repository(): void {
        $tournament         = new \stdClass();
        $tournament->season = '2026';

        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );

        $this->assertSame( '2026', Target_Season_Validator::validate( $tournament, 'tournament', '2026', $repository ) );
        $this->assertNull( Target_Season_Validator::validate( $tournament, 'tournament', '2025', $repository ) );
    }
}
