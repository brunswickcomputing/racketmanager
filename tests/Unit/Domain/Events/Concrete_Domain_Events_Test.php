<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Events;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Events\Club_Player_Registered_Event;
use Racketmanager\Domain\Competition\Events\Competition_Season_Cascaded_Event;
use Racketmanager\Domain\Fixture\Events\Fixture_Result_Finalized_Event;

final class Concrete_Domain_Events_Test extends TestCase {
    public function test_competition_season_cascaded_event_properties(): void {
        $now   = new DateTimeImmutable( '2026-06-01 10:00:00' );
        $event = new Competition_Season_Cascaded_Event(
            competition_id: 10,
            season_name: 'Summer 2026',
            occurred_on: $now
        );

        $this->assertSame( 10, $event->competition_id );
        $this->assertSame( 'Summer 2026', $event->season_name );
        $this->assertSame( $now, $event->get_occurred_on() );
        $this->assertSame( Competition_Season_Cascaded_Event::class, $event->get_event_name() );
    }

    public function test_club_player_registered_event_properties(): void {
        $event = new Club_Player_Registered_Event(
            club_id: 5,
            player_id: 25,
            registered_by_user_id: 2,
            status: 'approved'
        );

        $this->assertSame( 5, $event->club_id );
        $this->assertSame( 25, $event->player_id );
        $this->assertSame( 2, $event->registered_by_user_id );
        $this->assertSame( 'approved', $event->status );
        $this->assertInstanceOf( DateTimeImmutable::class, $event->get_occurred_on() );
    }

    public function test_fixture_result_finalized_event_properties(): void {
        $event = new Fixture_Result_Finalized_Event(
            fixture_id: 77,
            league_id: 14,
            season: '2026',
            competition_type: 'LTA'
        );

        $this->assertSame( 77, $event->fixture_id );
        $this->assertSame( 14, $event->league_id );
        $this->assertSame( '2026', $event->season );
        $this->assertSame( 'LTA', $event->competition_type );
    }
}
