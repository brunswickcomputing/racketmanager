<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Domain\Tournament\Tournament_Entry_Factory;
use ReflectionProperty;
use TypeError;

final class Tournament_Entry_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_every_field(): void {
        $entry = Tournament_Entry_Factory::create_from_dto( new Tournament_Entry_Hydration_DTO( id: 55, tournament_id: 12, player_id: 34, status: 1, fee: 15, club_id: 7 ) );

        $this->assertInstanceOf( Tournament_Entry::class, $entry );
        $this->assertSame( 55, $entry->get_id() );
        $this->assertSame( 12, $entry->get_tournament_id() );
        $this->assertSame( 34, $entry->get_player_id() );
        $this->assertSame( 1, $entry->get_status() );
        $this->assertSame( 15, $entry->get_fee() );
        $this->assertSame( 1500, $entry->get_fee_money()?->get_cents() );
        $this->assertSame( 7, $entry->get_club_id() );
        $this->assertNull( $entry->club );
        $this->assertFalse( $entry->has_domain_events() );
    }

    public function test_from_object_casts_string_db_row_and_keeps_nullable_columns_null(): void {
        $entry = Tournament_Entry_Factory::from_object( (object) [ 'id' => '10', 'tournament_id' => '1', 'player_id' => '2', 'status' => '0', 'fee' => null, 'club_id' => null ] );

        $this->assertSame( 10, $entry->get_id() );
        $this->assertSame( 1, $entry->get_tournament_id() );
        $this->assertSame( 2, $entry->get_player_id() );
        $this->assertSame( 0, $entry->get_status() );
        $this->assertNull( $entry->get_fee() );
        $this->assertNull( $entry->get_club_id() );
    }

    public function test_missing_required_columns_leave_properties_uninitialised_instead_of_warning(): void {
        $entry = Tournament_Entry_Factory::from_object( (object) [ 'player_id' => 2 ] );

        $this->assertNull( $entry->get_id() );
        $this->assertSame( 2, $entry->get_player_id() );
        $this->assertFalse( new ReflectionProperty( Tournament_Entry::class, 'tournament_id' )->isInitialized( $entry ) );
        $this->assertFalse( new ReflectionProperty( Tournament_Entry::class, 'status' )->isInitialized( $entry ) );
    }

    public function test_from_object_with_fee_populates_fee_money(): void {
        $data  = (object) [
            'id'            => '10',
            'tournament_id' => '1',
            'player_id'     => '2',
            'status'        => '0',
            'fee'           => '15.00',
            'club_id'       => null,
        ];
        $entry = Tournament_Entry_Factory::from_object( $data );

        $this->assertSame( 15, $entry->get_fee() );
        $this->assertInstanceOf( \Racketmanager\Domain\Value_Objects\Money::class, $entry->get_fee_money() );
        $this->assertSame( 1500, $entry->get_fee_money()->get_cents() );
        $this->assertSame( '£15.00', (string) $entry->get_fee_money() );
        $this->assertFalse( $entry->has_domain_events() );
    }

    public function test_from_object_with_fractional_decimal_fee_preserves_cents(): void {
        $data  = (object) [
            'id'            => '11',
            'tournament_id' => '1',
            'player_id'     => '2',
            'status'        => '0',
            'fee'           => '15.50',
            'club_id'       => null,
        ];
        $entry = Tournament_Entry_Factory::from_object( $data );

        $this->assertSame( 16, $entry->get_fee() );
        $this->assertInstanceOf( \Racketmanager\Domain\Value_Objects\Money::class, $entry->get_fee_money() );
        $this->assertSame( 1550, $entry->get_fee_money()->get_cents() );
        $this->assertSame( '£15.50', (string) $entry->get_fee_money() );
        $this->assertFalse( $entry->has_domain_events() );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '3', 'tournament_id' => '4', 'player_id' => '5', 'status' => '1', 'fee' => '20' ];

        $this->assertEquals( Tournament_Entry_Factory::from_object( (object) $data ), Tournament_Entry_Factory::from_array( $data ) );
        $this->assertEquals( Tournament_Entry_Factory::from_object( (object) $data ), Tournament_Entry::from_object( (object) $data ) );
        $this->assertEquals( Tournament_Entry::from_object( (object) $data ), Tournament_Entry::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_entry_and_raw_object_is_rejected(): void {
        $empty = new Tournament_Entry();
        $this->assertNull( $empty->get_id() );
        $this->assertNull( $empty->get_fee() );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Tournament_Entry::class,
            new Tournament_Entry( (object) [ 'id' => 1 ] )
        );
    }
}
