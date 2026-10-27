<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Tournament_Entry;

class Tournament_Entry_Test extends TestCase {
    public function test_instantiation_without_data(): void {
        $entry = new Tournament_Entry();
        $this->assertInstanceOf( Tournament_Entry::class, $entry );
        $this->assertNull( $entry->get_id() );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_club_id() );
    }

    public function test_instantiation_with_data_and_getters_setters(): void {
        $data = (object) [
            'id'            => 55,
            'tournament_id' => 12,
            'player_id'     => 34,
            'status'        => 1,
            'fee'           => 1500,
            'club_id'       => 7,
        ];

        $entry = Tournament_Entry::from_object( $data );

        $this->assertSame( 55, $entry->get_id() );
        $this->assertSame( 12, $entry->get_tournament_id() );
        $this->assertSame( 34, $entry->get_player_id() );
        $this->assertSame( 1, $entry->get_status() );
        $this->assertSame( 1500, $entry->get_fee() );
        $this->assertSame( 7, $entry->get_club_id() );

        $entry->set_id( 60 );
        $entry->set_tournament_id( 14 );
        $entry->set_player_id( 36 );
        $entry->set_status( 2 );
        $entry->set_fee( 2000 );
        $entry->set_club_id( 9 );

        $this->assertSame( 60, $entry->get_id() );
        $this->assertSame( 14, $entry->get_tournament_id() );
        $this->assertSame( 36, $entry->get_player_id() );
        $this->assertSame( 2, $entry->get_status() );
        $this->assertSame( 2000, $entry->get_fee() );
        $this->assertSame( 9, $entry->get_club_id() );
    }
}
