<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;

class League_Test extends TestCase {
    public function test_constructs_league_with_competition_settings_object(): void {
        $competition = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( [ 'mode' => 'league', 'point_rule' => 'three', 'sport' => 'tennis' ] );
        $competition->standings = [];
        $competition->is_player_entry = false;
        $event = Event::from_object( (object) [ 'id' => 10, 'name' => 'League', 'seasons' => [] ], $competition );
        $league = League::from_object( (object) [
            'id' => 1,
            'title' => 'Division 1',
            'event_id' => 10,
            'settings' => [],
            'seasons' => [ '2024-25' ],
        ], $event );

        $this->assertSame( 1, $league->get_id() );
        $this->assertSame( 'Division 1', $league->get_name() );
        $this->assertSame( 'league', $league->mode );
        $this->assertSame( 'three', $league->point_rule );
        $this->assertSame( 'tennis', $league->sport );
        $this->assertSame( $event, $league->event );
    }
}