<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Invoice_Hydration_DTO;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Finance\Invoice_Factory;
use ReflectionProperty;
use TypeError;

final class Invoice_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_every_field(): void {
        $invoice = Invoice_Factory::create_from_dto( new Invoice_Hydration_DTO(
            id: 5,
            invoice_number: 1001,
            charge_id: 2,
            billable_id: 42,
            billable_type: 'club',
            status: 'final',
            date: '2026-01-01',
            date_due: '2026-02-01',
            amount: '125.50',
            payment_reference: 'REF',
            purchase_order: 'PO',
            details: (object) [ 'item' => 'Entry fee' ],
        ) );

        $this->assertInstanceOf( Invoice::class, $invoice );
        $this->assertSame( 5, $invoice->get_id() );
        $this->assertSame( 1001, $invoice->get_invoice_number() );
        $this->assertSame( 2, $invoice->get_charge_id() );
        $this->assertSame( 42, $invoice->get_billable_id() );
        $this->assertSame( 'club', $invoice->get_billable_type() );
        $this->assertSame( Invoice_Status::FINAL, $invoice->get_status_enum() );
        $this->assertSame( '2026-01-01', $invoice->get_date() );
        $this->assertSame( '2026-02-01', $invoice->get_date_due() );
        $this->assertSame( '125.50', $invoice->get_amount() );
        $this->assertSame( 12550, $invoice->get_money()->get_cents() );
        $this->assertSame( 'REF', $invoice->get_payment_reference() );
        $this->assertSame( 'PO', $invoice->get_purchase_order() );
        $this->assertSame( '{"item":"Entry fee"}', $invoice->get_details() );
    }

    public function test_from_object_casts_string_db_row_and_decodes_details_like_the_legacy_constructor(): void {
        $invoice = Invoice_Factory::from_object( (object) [
            'id'             => '5',
            'invoice_number' => '1001',
            'charge_id'      => '2',
            'billable_id'    => '',
            'amount'         => '125.50',
            'status'         => 'final',
            'details'        => '{"item":"Entry fee"}',
            'club_id'        => '9',
        ] );

        $this->assertSame( 5, $invoice->id );
        $this->assertSame( 1001, $invoice->invoice_number );
        $this->assertSame( 2, $invoice->charge_id );
        $this->assertNull( $invoice->billable_id );
        $this->assertSame( '125.50', $invoice->amount );
        $this->assertSame( 'final', $invoice->status );
        $this->assertSame( 'Entry fee', $invoice->details->item );
        $this->assertFalse( property_exists( $invoice, 'club_id' ) );
    }

    public function test_missing_status_keeps_draft_default_and_missing_non_nullable_columns_stay_uninitialised(): void {
        $invoice = Invoice_Factory::from_object( (object) [ 'charge_id' => 3 ] );

        $this->assertSame( 'draft', $invoice->get_status() );
        $this->assertTrue( $invoice->is_draft() );
        $this->assertNull( $invoice->get_money() );
        $this->assertFalse( new ReflectionProperty( Invoice::class, 'amount' )->isInitialized( $invoice ) );
        $this->assertFalse( new ReflectionProperty( Invoice::class, 'date' )->isInitialized( $invoice ) );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '3', 'charge_id' => '4', 'amount' => '10.00', 'details' => '{"x":1}' ];

        $this->assertEquals( Invoice_Factory::from_object( (object) $data ), Invoice_Factory::from_array( $data ) );
        $this->assertEquals( Invoice_Factory::from_object( (object) $data ), Invoice::from_object( (object) $data ) );
        $this->assertEquals( Invoice::from_object( (object) $data ), Invoice::from_array( $data ) );
    }

    public function test_empty_constructor_yields_draft_invoice_and_raw_object_is_rejected(): void {
        $empty = new Invoice();
        $this->assertNull( $empty->get_id() );
        $this->assertSame( 'draft', $empty->get_status() );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Invoice::class,
            new Invoice( (object) [ 'id' => 1 ] )
        );
    }
}
