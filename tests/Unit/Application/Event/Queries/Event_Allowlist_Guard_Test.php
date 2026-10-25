<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Event\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Event_Allowlist_Guard;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use ReflectionClass;

final class Event_Allowlist_Guard_Test extends TestCase {
    private function make_event( int $id, int $competition_id = 10, array $seasons = [ '2026' => [ 'name' => '2026' ] ] ): Event {
        $event                 = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = 'Event ' . $id;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( $seasons );

        return $event;
    }

    private function make_competition( array $season = [ 'name' => '2026' ] ): Competition {
        return Competition_Test_Factory::create( id: 10, name: 'Comp', seasons: [ '2026' => $season ] );
    }

    private function service_returning( Competition $competition, ?Event $resolved ): Competition_Service {
        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'resolve_competition_event' )->willReturn( $resolved );

        return $service;
    }

    public function test_event_instance_in_scope_is_allowed_without_service_lookup(): void {
        $competition = $this->make_competition();
        $event       = $this->make_event( 11 );
        $event->competition = $competition;

        self::assertTrue( Event_Allowlist_Guard::is_event_allowed( $event, '2026' ) );
    }

    public function test_foreign_or_out_of_season_event_instance_is_rejected(): void {
        $competition        = $this->make_competition();
        $foreign            = $this->make_event( 1, 999 );
        $foreign->competition = $competition;
        $old                = $this->make_event( 12, 10, [ '2024' => [ 'name' => '2024' ] ] );
        $old->competition   = $competition;

        self::assertFalse( Event_Allowlist_Guard::is_event_allowed( $foreign, '2026' ) );
        self::assertFalse( Event_Allowlist_Guard::is_event_allowed( $old, '2026' ) );
    }

    public function test_raw_row_is_rejected_without_service_even_when_competition_is_attached(): void {
        $competition = $this->make_competition();
        $row         = (object) [ 'id' => 1, 'competition_id' => 10, 'competition' => $competition ];

        self::assertFalse( Event_Allowlist_Guard::is_event_allowed( $row, '2026' ) );
    }

    public function test_raw_row_is_rejected_when_service_cannot_resolve_it(): void {
        $competition = $this->make_competition();
        $row         = (object) [ 'id' => 1, 'competition_id' => 10 ];

        self::assertFalse( Event_Allowlist_Guard::is_event_allowed( $row, '2026', $this->service_returning( $competition, null ) ) );
    }

    public function test_raw_row_is_allowed_only_when_service_resolves_it_to_an_in_scope_event(): void {
        $competition = $this->make_competition( [ 'name' => '2026', 'entry_events' => [ 11 ] ] );
        $row         = (object) [ 'id' => 11, 'competition_id' => 10 ];

        self::assertTrue( Event_Allowlist_Guard::is_event_allowed( $row, '2026', $this->service_returning( $competition, $this->make_event( 11 ) ) ) );
        self::assertFalse( Event_Allowlist_Guard::is_event_allowed( $row, '2026', $this->service_returning( $competition, $this->make_event( 12 ) ) ) );
    }
}
