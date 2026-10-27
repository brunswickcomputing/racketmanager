<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Fixture\Rubber_Factory;
use TypeError;

final class Rubber_Factory_Test extends TestCase {

    public function test_create_from_dto_derives_date_parts_score_title_and_flags(): void {
        $rubber = Rubber_Factory::create_from_dto( new Rubber_Hydration_DTO(
            id: 7,
            match_id: 12,
            status: 1,
            custom: [ 'sets' => [ 1 => [ 'player1' => '6', 'player2' => '3' ] ], 'walkover' => 'home' ],
            sets: [ 1 => [ 'player1' => '6', 'player2' => '3' ] ],
            date: '2026-03-01 19:30',
            home_points: 6.0,
            away_points: 3.5,
            type: 'MD',
            rubber_number: 2,
            reverse_rubbers: true,
            walkover: 'home',
        ) );

        $this->assertInstanceOf( Rubber::class, $rubber );
        $this->assertSame( 7, $rubber->get_id() );
        $this->assertSame( 12, $rubber->get_match_id() );
        $this->assertSame( '2026-03-01', $rubber->rubber_date );
        $this->assertSame( 2026, $rubber->year );
        $this->assertSame( 3, $rubber->month );
        $this->assertSame( 1, $rubber->day );
        $this->assertSame( '19', $rubber->hour );
        $this->assertSame( '30', $rubber->minutes );
        $this->assertSame( '19:30', $rubber->start_time );
        $this->assertSame( '6 - 3.5', $rubber->score );
        $this->assertSame( 'MD2', $rubber->title );
        $this->assertTrue( $rubber->is_walkover );
        $this->assertFalse( $rubber->is_shared );
        $this->assertSame( 'home', $rubber->walkover );
        $this->assertTrue( $rubber->reverse_rubbers );
        $this->assertFalse( $rubber->reverse_rubber );
        $this->assertSame( [ 1 => [ 'player1' => '6', 'player2' => '3' ] ], $rubber->sets );
    }

    public function test_from_object_matches_the_legacy_row_hydration(): void {
        $rubber = Rubber_Factory::from_object( (object) [
            'id'            => '7',
            'match_id'      => '12',
            'date'          => '2026-03-01 00:00',
            'home_points'   => '',
            'away_points'   => '',
            'status'        => '',
            'type'          => 'XD',
            'rubber_number' => '1',
            'custom'        => serialize( [ 'sets' => [], 'share' => true, 'stats' => [ 'x' => 1 ] ] ),
        ] );

        $this->assertSame( 7, $rubber->id );
        $this->assertSame( 0.0, $rubber->home_points );
        $this->assertSame( 0, $rubber->status );
        $this->assertSame( '0 - 0', $rubber->score );
        $this->assertSame( '', $rubber->start_time );
        $this->assertSame( '00', $rubber->hour );
        $this->assertTrue( $rubber->is_shared );
        $this->assertSame( '1', $rubber->share );
        $this->assertSame( [ 'x' => 1 ], $rubber->stats );
        $this->assertSame( 'XD1', $rubber->title );
    }

    public function test_zero_date_and_missing_date_follow_legacy_rules(): void {
        $zero = Rubber_Factory::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'date' => '0000-00-00 00:00:00' ] );
        $none = Rubber_Factory::from_object( (object) [ 'id' => 1, 'match_id' => 2 ] );

        $this->assertSame( 'N/A', $zero->rubber_date );
        $this->assertSame( '', $none->rubber_date );
        $this->assertSame( 0, $none->year );
        $this->assertSame( '-:-', $none->score );
        $this->assertSame( array(), $none->custom );
        $this->assertSame( array(), $none->sets );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '3', 'match_id' => '4', 'type' => 'MS', 'rubber_number' => '1', 'date' => '2026-01-01 10:00', 'players' => [ 'home' => [ 1 ] ] ];

        $this->assertEquals( Rubber_Factory::from_object( (object) $data ), Rubber_Factory::from_array( $data ) );
        $this->assertEquals( Rubber_Factory::from_object( (object) $data ), Rubber::from_object( (object) $data ) );
        $this->assertEquals( Rubber::from_object( (object) $data ), Rubber::from_array( $data ) );
        $this->assertSame( [ 'home' => [ 1 ] ], Rubber::from_array( $data )->players );
    }

    public function test_empty_constructor_yields_empty_rubber_and_raw_object_is_rejected(): void {
        $empty = new Rubber();
        $this->assertSame( 0, $empty->get_id() );
        $this->assertSame( '-:-', $empty->score );
        $this->assertSame( array(), $empty->players );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Rubber::class,
            new Rubber( (object) [ 'id' => 1 ] )
        );
    }
}
