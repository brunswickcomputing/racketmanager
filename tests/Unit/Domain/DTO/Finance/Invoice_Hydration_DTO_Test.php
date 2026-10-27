<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Finance;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Invoice_Hydration_DTO;

final class Invoice_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_and_decodes_json_details(): void {
        $dto = Invoice_Hydration_DTO::from_object( (object) [
            'id'                => '5',
            'invoice_number'    => '1001',
            'charge_id'         => '2',
            'billable_id'       => '42',
            'billable_type'     => 'club',
            'status'            => 'final',
            'date'              => '2026-01-01',
            'date_due'          => '2026-02-01',
            'amount'            => '125.50',
            'payment_reference' => 'REF1',
            'purchase_order'    => 'PO1',
            'details'           => '{"item":"Entry fee"}',
        ] );

        $this->assertSame( 5, $dto->id );
        $this->assertSame( 1001, $dto->invoice_number );
        $this->assertSame( 2, $dto->charge_id );
        $this->assertSame( 42, $dto->billable_id );
        $this->assertSame( 'club', $dto->billable_type );
        $this->assertSame( 'final', $dto->status );
        $this->assertSame( '2026-01-01', $dto->date );
        $this->assertSame( '2026-02-01', $dto->date_due );
        $this->assertSame( '125.50', $dto->amount );
        $this->assertSame( 'REF1', $dto->payment_reference );
        $this->assertSame( 'PO1', $dto->purchase_order );
        $this->assertIsObject( $dto->details );
        $this->assertSame( 'Entry fee', $dto->details->item );
    }

    public function test_identifier_columns_become_null_for_empty_and_null_and_amount_is_stringified(): void {
        $dto = Invoice_Hydration_DTO::from_object( (object) [
            'id'          => '',
            'charge_id'   => null,
            'billable_id' => '',
            'amount'      => 99.5,
            'details'     => (object) [ 'a' => 1 ],
        ] );

        $this->assertNull( $dto->id );
        $this->assertNull( $dto->charge_id );
        $this->assertNull( $dto->billable_id );
        $this->assertNull( $dto->invoice_number );
        $this->assertSame( '99.5', $dto->amount );
        $this->assertNull( $dto->status );
        $this->assertEquals( (object) [ 'a' => 1 ], $dto->details );
    }

    public function test_present_null_amount_becomes_empty_string_and_absent_amount_stays_null(): void {
        $this->assertSame( '', Invoice_Hydration_DTO::from_object( (object) [ 'amount' => null ] )->amount );
        $this->assertNull( Invoice_Hydration_DTO::from_object( new \stdClass() )->amount );
    }

    public function test_unknown_keys_are_dropped(): void {
        $dto = Invoice_Hydration_DTO::from_object( (object) [ 'id' => 1, 'club_id' => 7, 'player_id' => 8, 'club' => (object) [] ] );

        $this->assertArrayNotHasKey( 'club_id', get_object_vars( $dto ) );
        $this->assertArrayNotHasKey( 'club', get_object_vars( $dto ) );
        $this->assertCount( 12, get_object_vars( $dto ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '3', 'charge_id' => '4', 'amount' => '10.00', 'details' => '{"x":1}' ];

        $this->assertEquals( Invoice_Hydration_DTO::from_object( (object) $data ), Invoice_Hydration_DTO::from_array( $data ) );
    }
}
