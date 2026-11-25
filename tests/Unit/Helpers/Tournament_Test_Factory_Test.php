<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Tests\Helpers\Tournament_Test_Factory;

final class Tournament_Test_Factory_Test extends TestCase {
    public function test_defaults_create_tournament_with_semantic_defaults(): void {
        $tournament = Tournament_Test_Factory::create();

        $this->assertInstanceOf( Tournament::class, $tournament );
        $this->assertSame( 1, $tournament->id );
        $this->assertSame( 'Test Tournament', $tournament->name );
        $this->assertSame( '2026', $tournament->season );
        $this->assertSame( '2026-06-01', $tournament->date_start );
        $this->assertSame( '2026-06-30', $tournament->date_end );
        $this->assertIsArray( $tournament->finals );
        $this->assertCount( 6, $tournament->finals );
    }

    public function test_create_with_custom_attributes(): void {
        $tournament = Tournament_Test_Factory::create(
            id: 42,
            name: 'Summer Open',
            season: '2027',
            date_start: '2027-07-01',
            date_end: '2027-07-15'
        );

        $this->assertSame( 42, $tournament->id );
        $this->assertSame( 'Summer Open', $tournament->name );
        $this->assertSame( '2027', $tournament->season );
        $this->assertSame( '2027-07-01', $tournament->date_start );
        $this->assertSame( '2027-07-15', $tournament->date_end );
    }

    public function test_date_overrides_are_used_to_derive_tournament_phase(): void {
        $tournament = Tournament_Test_Factory::create(
            date_start: '2099-01-01',
            date_end: '2099-02-01',
            overrides: [ 'date_open' => '2000-01-01' ]
        );

        $this->assertSame( 'open', $tournament->current_phase );
        $this->assertTrue( $tournament->is_open );
    }

    public function test_create_with_null_id(): void {
        $tournament = Tournament_Test_Factory::create( id: null, name: 'Unsaved Tournament' );

        $this->assertNull( $tournament->id );
        $this->assertSame( 'Unsaved Tournament', $tournament->name );
    }

    public function test_create_with_overrides(): void {
        $competition = (object) [ 'id' => 99, 'name' => 'Comp' ];
        $event       = (object) [ 'id' => 10 ];
        $player      = (object) [ 'id' => 20 ];
        $match       = (object) [ 'id' => 30 ];
        $entry       = (object) [ 'id' => 40 ];
        $fees        = (object) [ 'entry' => 25.0 ];
        $info        = (object) [ 'notes' => 'Special rules' ];

        $tournament = Tournament_Test_Factory::create(
            overrides: [
                'id'               => 100,
                'name'             => 'Overridden Tournament',
                'season'           => '2028',
                'num_courts'       => 8,
                'start_time'       => '09:00',
                'date_end'         => '2028-08-31',
                'date_closing'     => '2028-07-20',
                'date_withdrawal'  => '2028-07-25',
                'date_open'        => '2028-06-01',
                'date_start'       => '2028-08-01',
                'venue'            => 5,
                'venue_name'       => 'Main Grounds',
                'is_active'        => true,
                'order_of_play'    => [ 'r1', 'r2' ],
                'time_increment'   => '15',
                'competitions'     => [ $competition ],
                'events'           => [ $event ],
                'players'          => [ $player ],
                'current_phase'    => 'open',
                'is_complete'      => true,
                'is_started'       => true,
                'is_closed'        => false,
                'is_withdrawal'    => false,
                'is_open'          => true,
                'competition_code' => 'TC2028',
                'finals'           => [ 'final' => [] ],
                'grade'            => 'Grade 3',
                'fees'             => $fees,
                'num_entries'      => 64,
                'type'             => 'singles',
                'matches'          => [ $match ],
                'payments'         => 1250.50,
                'entries'          => [ $entry ],
                'match_dates'      => [ '2028-08-01', '2028-08-02' ],
                'information'      => $info,
            ]
        );

        $this->assertSame( 100, $tournament->id );
        $this->assertSame( 'Overridden Tournament', $tournament->name );
        $this->assertSame( '2028', $tournament->season );
        $this->assertSame( 8, $tournament->num_courts );
        $this->assertSame( '09:00', $tournament->start_time );
        $this->assertSame( '2028-08-31', $tournament->date_end );
        $this->assertSame( '2028-07-20', $tournament->date_closing );
        $this->assertSame( '2028-07-25', $tournament->date_withdrawal );
        $this->assertSame( '2028-06-01', $tournament->date_open );
        $this->assertSame( '2028-08-01', $tournament->date_start );
        $this->assertSame( 5, $tournament->venue );
        $this->assertSame( 'Main Grounds', $tournament->venue_name );
        $this->assertTrue( $tournament->is_active );
        $this->assertSame( [ 'r1', 'r2' ], $tournament->order_of_play );
        $this->assertSame( '15', $tournament->time_increment );
        $this->assertSame( [ $competition ], $tournament->competitions );
        $this->assertSame( [ $event ], $tournament->events );
        $this->assertSame( [ $player ], $tournament->players );
        $this->assertSame( 'open', $tournament->current_phase );
        $this->assertTrue( $tournament->is_complete );
        $this->assertTrue( $tournament->is_started );
        $this->assertFalse( $tournament->is_closed );
        $this->assertFalse( $tournament->is_withdrawal );
        $this->assertTrue( $tournament->is_open );
        $this->assertSame( 'TC2028', $tournament->competition_code );
        $this->assertSame( [ 'final' => [] ], $tournament->finals );
        $this->assertSame( 'Grade 3', $tournament->grade );
        $this->assertSame( $fees, $tournament->fees );
        $this->assertSame( 64, $tournament->num_entries );
        $this->assertSame( 'singles', $tournament->type );
        $this->assertSame( [ $match ], $tournament->matches );
        $this->assertSame( 1250.50, $tournament->payments );
        $this->assertSame( [ $entry ], $tournament->entries );
        $this->assertSame( [ '2028-08-01', '2028-08-02' ], $tournament->match_dates );
        $this->assertSame( $info, $tournament->information );
    }

    public function test_create_with_competition_id_override_clears_cached_competition(): void {
        $competition = new Competition( 'Initial Comp', Competition_Type::TOURNAMENT, 'open', null, [], 10 );
        $tournament  = Tournament_Test_Factory::create(
            overrides: [
                'competition'    => $competition,
                'competition_id' => 99,
            ]
        );

        $this->assertSame( 99, $tournament->competition_id );
        $this->assertNull( $tournament->competition );
    }

    public function test_rejects_unknown_property_overrides(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( 'Unknown Tournament test property: unknown_property' );

        Tournament_Test_Factory::create( overrides: [ 'unknown_property' => true ] );
    }

    public function test_rejects_private_property_overrides(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessageIsOrContains( 'Unknown Tournament test property: meta' );

        Tournament_Test_Factory::create( overrides: [ 'meta' => [ 'foo' => 'bar' ] ] );
    }
}
