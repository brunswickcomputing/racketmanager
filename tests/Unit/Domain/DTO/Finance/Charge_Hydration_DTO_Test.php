<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Finance;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Charge_Hydration_DTO;

final class Charge_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Charge_Hydration_DTO::from_object( (object) [
            'id'              => '10',
            'competition_id'  => '2',
            'season'          => 2026,
            'status'          => 'final',
            'date'            => '2026-01-01',
            'fee_competition' => '40.00',
            'fee_event'       => '20.50',
            'total'           => '60.50',
        ] );

        $this->assertSame( 10, $dto->id );
        $this->assertSame( 2, $dto->competition_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 'final', $dto->status );
        $this->assertSame( '2026-01-01', $dto->date );
        $this->assertSame( 40.0, $dto->fee_competition );
        $this->assertSame( 20.5, $dto->fee_event );
        $this->assertSame( 60.5, $dto->total );
    }

    public function test_empty_and_null_numeric_columns_become_null_and_status_defaults_to_draft(): void {
        $dto = Charge_Hydration_DTO::from_object( (object) [
            'id'              => '',
            'competition_id'  => null,
            'fee_competition' => '',
            'fee_event'       => null,
            'total'           => '0',
        ] );

        $this->assertNull( $dto->id );
        $this->assertNull( $dto->competition_id );
        $this->assertNull( $dto->season );
        $this->assertSame( 'draft', $dto->status );
        $this->assertNull( $dto->date );
        $this->assertNull( $dto->fee_competition );
        $this->assertNull( $dto->fee_event );
        $this->assertSame( 0.0, $dto->total );
    }

    public function test_present_empty_status_is_kept_as_empty_string(): void {
        $this->assertSame( '', Charge_Hydration_DTO::from_object( (object) [ 'status' => '' ] )->status );
    }

    public function test_unknown_keys_are_dropped(): void {
        $dto = Charge_Hydration_DTO::from_object( (object) [ 'id' => 1, 'competition' => (object) [ 'id' => 3 ], 'foo' => 'bar' ] );

        $this->assertSame( [ 'id', 'competition_id', 'season', 'status', 'date', 'fee_competition', 'fee_event', 'total' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '3', 'season' => '2025', 'fee_event' => '15' ];

        $this->assertEquals( Charge_Hydration_DTO::from_object( (object) $data ), Charge_Hydration_DTO::from_array( $data ) );
    }
}
