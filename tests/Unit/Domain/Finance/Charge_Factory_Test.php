<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Charge_Hydration_DTO;
use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Finance\Charge_Factory;

final class Charge_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_every_field(): void {
        $charge = Charge_Factory::create_from_dto( new Charge_Hydration_DTO( id: 10, competition_id: 2, season: '2026', status: 'final', date: '2026-01-01', fee_competition: 40.0, fee_event: 20.0, total: 60.0 ) );

        $this->assertInstanceOf( Charge::class, $charge );
        $this->assertSame( 10, $charge->get_id() );
        $this->assertSame( 2, $charge->get_competition_id() );
        $this->assertSame( '2026', $charge->get_season() );
        $this->assertSame( Charge_Status::FINAL, $charge->get_status_enum() );
        $this->assertSame( '2026-01-01', $charge->get_date() );
        $this->assertSame( 40.0, $charge->get_fee_competition() );
        $this->assertSame( 20.0, $charge->get_fee_event() );
        $this->assertSame( 60.0, $charge->total );
    }

    public function test_from_object_casts_string_db_row_like_the_legacy_constructor(): void {
        $charge = Charge_Factory::from_object( (object) [ 'id' => '10', 'competition_id' => '', 'status' => '', 'fee_competition' => '40.00', 'fee_event' => null ] );

        $this->assertSame( 10, $charge->id );
        $this->assertNull( $charge->competition_id );
        $this->assertSame( '', $charge->get_status() );
        $this->assertNull( $charge->get_status_enum() );
        $this->assertSame( 40.0, $charge->fee_competition );
        $this->assertNull( $charge->fee_event );
        $this->assertNull( $charge->competition );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '3', 'season' => '2025', 'fee_event' => '15' ];

        $this->assertEquals( Charge_Factory::from_object( (object) $data ), Charge_Factory::from_array( $data ) );
        $this->assertEquals( Charge_Factory::from_object( (object) $data ), Charge::from_object( (object) $data ) );
        $this->assertEquals( Charge::from_object( (object) $data ), Charge::from_array( $data ) );
    }

    public function test_empty_constructor_yields_draft_charge_and_raw_object_is_rejected(): void {
        $empty = new Charge();
        $this->assertNull( $empty->get_id() );
        $this->assertSame( 'draft', $empty->get_status() );

        $this->expectException( \TypeError::class );
        new Charge( (object) [ 'id' => 1 ] );
    }
}
