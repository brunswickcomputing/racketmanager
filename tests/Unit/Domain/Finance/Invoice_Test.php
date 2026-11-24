<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Invoice_Hydration_DTO;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Finance\Invoice_Factory;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

final class Invoice_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_default_state_initializes_cleanly_without_uninitialized_property_errors(): void {
        $invoice = new Invoice();

        self::assertNull( $invoice->id );
        self::assertNull( $invoice->invoice_number );
        self::assertNull( $invoice->club );
        self::assertNull( $invoice->billable_id );
        self::assertNull( $invoice->billable_type );
        self::assertNull( $invoice->charge );
        self::assertNull( $invoice->charge_id );
        self::assertSame( 'draft', $invoice->status );
        self::assertNull( $invoice->date );
        self::assertNull( $invoice->date_due );
        self::assertNull( $invoice->amount );
        self::assertNull( $invoice->payment_reference );
        self::assertNull( $invoice->purchase_order );
        self::assertNull( $invoice->details );

        self::assertNull( $invoice->get_id() );
        self::assertNull( $invoice->get_invoice_number() );
        self::assertNull( $invoice->get_club() );
        self::assertNull( $invoice->get_billable_id() );
        self::assertNull( $invoice->get_billable_type() );
        self::assertNull( $invoice->get_charge() );
        self::assertNull( $invoice->get_charge_id() );
        self::assertSame( 'draft', $invoice->get_status() );
        self::assertSame( Invoice_Status::DRAFT, $invoice->get_status_enum() );
        self::assertTrue( $invoice->is_draft() );
        self::assertFalse( $invoice->is_paid() );
        self::assertFalse( $invoice->is_final() );
        self::assertFalse( $invoice->is_sent() );
        self::assertFalse( $invoice->is_cancelled() );
        self::assertFalse( $invoice->is_new() );
        self::assertTrue( $invoice->can_be_sent() );
        self::assertNull( $invoice->get_date() );
        self::assertNull( $invoice->get_date_due() );
        self::assertNull( $invoice->get_amount() );
        self::assertNull( $invoice->get_money() );
        self::assertNull( $invoice->get_payment_reference() );
        self::assertNull( $invoice->get_purchase_order() );
        self::assertNull( $invoice->get_details() );
    }

    public function test_asymmetric_visibility_allows_reading_all_properties(): void {
        $dto = new Invoice_Hydration_DTO(
            id: 88,
            invoice_number: 2026088,
            charge_id: 12,
            billable_id: 34,
            billable_type: 'club',
            status: 'sent',
            date: '2026-06-01',
            date_due: '2026-07-01',
            amount: '120.00',
            payment_reference: 'REF-88',
            purchase_order: 'PO-88',
            details: (object) array( 'fee' => 120 ),
        );
        $invoice = new Invoice( $dto );
        $club = (object) array( 'id' => 34, 'name' => 'Leigh LTC' );
        $charge = (object) array( 'id' => 12, 'season' => '2026' );
        $invoice->set_club( $club );
        $invoice->set_charge( $charge );

        self::assertSame( 88, $invoice->id );
        self::assertSame( 2026088, $invoice->invoice_number );
        self::assertSame( $club, $invoice->club );
        self::assertSame( 34, $invoice->billable_id );
        self::assertSame( 'club', $invoice->billable_type );
        self::assertSame( $charge, $invoice->charge );
        self::assertSame( 12, $invoice->charge_id );
        self::assertSame( 'sent', $invoice->status );
        self::assertSame( '2026-06-01', $invoice->date );
        self::assertSame( '2026-07-01', $invoice->date_due );
        self::assertSame( '120.00', $invoice->amount );
        self::assertSame( 'REF-88', $invoice->payment_reference );
        self::assertSame( 'PO-88', $invoice->purchase_order );
        self::assertEquals( (object) array( 'fee' => 120 ), $invoice->details );
    }

    #[DataProvider( 'invoice_property_mutations' )]
    public function test_asymmetric_visibility_forbids_external_mutation( string $property, mixed $value ): void {
        $invoice = new Invoice();

        $this->expectException( Error::class );
        $this->expectExceptionMessage( sprintf( 'Cannot modify private(set) property %s::$%s', Invoice::class, $property ) );

        $invoice->{$property} = $value;
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invoice_property_mutations(): array {
        return array(
            'id'                => array( 'id', 1 ),
            'invoice_number'    => array( 'invoice_number', 1001 ),
            'club'              => array( 'club', new stdClass() ),
            'billable_id'       => array( 'billable_id', 10 ),
            'billable_type'     => array( 'billable_type', 'club' ),
            'charge'            => array( 'charge', new stdClass() ),
            'charge_id'         => array( 'charge_id', 5 ),
            'status'            => array( 'status', 'final' ),
            'date'              => array( 'date', '2026-01-01' ),
            'date_due'          => array( 'date_due', '2026-02-01' ),
            'amount'            => array( 'amount', '50.00' ),
            'payment_reference' => array( 'payment_reference', 'REF' ),
            'purchase_order'    => array( 'purchase_order', 'PO' ),
            'details'           => array( 'details', new stdClass() ),
        );
    }

    public function test_typed_getters_and_setters(): void {
        $invoice = new Invoice();

        $invoice->set_id( 123 );
        self::assertSame( 123, $invoice->get_id() );

        $invoice->set_invoice_number( 2026123 );
        self::assertSame( 2026123, $invoice->get_invoice_number() );

        $club = (object) array( 'id' => 5 );
        $invoice->set_club( $club );
        self::assertSame( $club, $invoice->get_club() );

        $invoice->set_billable_id( 5 );
        self::assertSame( 5, $invoice->get_billable_id() );

        $invoice->set_billable_type( 'club' );
        self::assertSame( 'club', $invoice->get_billable_type() );

        $charge = (object) array( 'id' => 10 );
        $invoice->set_charge( $charge );
        self::assertSame( $charge, $invoice->get_charge() );

        $invoice->set_charge_id( 10 );
        self::assertSame( 10, $invoice->get_charge_id() );

        $invoice->set_date( '2026-06-01' );
        self::assertSame( '2026-06-01', $invoice->get_date() );

        $invoice->set_date_due( '2026-07-01' );
        self::assertSame( '2026-07-01', $invoice->get_date_due() );

        $invoice->set_amount( '99.50' );
        self::assertSame( '99.50', $invoice->get_amount() );

        $invoice->set_payment_reference( 'TXN-123' );
        self::assertSame( 'TXN-123', $invoice->get_payment_reference() );

        $invoice->set_purchase_order( 'PO-456' );
        self::assertSame( 'PO-456', $invoice->get_purchase_order() );

        $details = (object) array( 'note' => 'Paid in full' );
        $invoice->set_details( $details );
        self::assertSame( '{"note":"Paid in full"}', $invoice->get_details() );

        $invoice->set_invoice_number( '' );
        self::assertNull( $invoice->get_invoice_number() );

        $invoice->set_club( null );
        self::assertNull( $invoice->get_club() );

        $invoice->set_charge( null );
        self::assertNull( $invoice->get_charge() );

        $invoice->set_charge_id( null );
        self::assertNull( $invoice->get_charge_id() );

        $invoice->set_details( null );
        self::assertNull( $invoice->get_details() );
    }

    public function test_status_transitions_and_predicates(): void {
        $invoice = new Invoice();

        $invoice->set_status( Invoice_Status::PAID );
        self::assertSame( 'paid', $invoice->get_status() );
        self::assertSame( Invoice_Status::PAID, $invoice->get_status_enum() );
        self::assertTrue( $invoice->is_paid() );
        self::assertFalse( $invoice->is_draft() );
        self::assertFalse( $invoice->is_final() );
        self::assertFalse( $invoice->is_sent() );
        self::assertFalse( $invoice->is_cancelled() );
        self::assertFalse( $invoice->is_new() );
        self::assertFalse( $invoice->can_be_sent() );

        $invoice->set_status_enum( Invoice_Status::SENT );
        self::assertSame( 'sent', $invoice->get_status() );
        self::assertTrue( $invoice->is_sent() );

        $invoice->set_status( 'cancelled' );
        self::assertSame( 'cancelled', $invoice->get_status() );
        self::assertSame( Invoice_Status::CANCELLED, $invoice->get_status_enum() );
        self::assertTrue( $invoice->is_cancelled() );

        $invoice->set_status( 'new' );
        self::assertSame( 'new', $invoice->get_status() );
        self::assertSame( Invoice_Status::NEW, $invoice->get_status_enum() );
        self::assertTrue( $invoice->is_new() );

        $invoice->set_status( 'final' );
        self::assertTrue( $invoice->is_final() );
        self::assertTrue( $invoice->can_be_sent() );

        $invoice->set_status( 'unknown_state' );
        self::assertSame( 'unknown_state', $invoice->get_status() );
        self::assertNull( $invoice->get_status_enum() );
        self::assertFalse( $invoice->is_paid() );
        self::assertFalse( $invoice->is_draft() );
        self::assertFalse( $invoice->is_final() );
    }

    public function test_amount_and_money_value_object(): void {
        $invoice = new Invoice();

        $invoice->set_amount( '45.00' );
        self::assertSame( '45.00', $invoice->get_amount() );

        $money = $invoice->get_money();
        self::assertNotNull( $money );
        self::assertSame( 4500, $money->get_cents() );
        self::assertSame( '£45.00', $money->format() );

        $eur_money = $invoice->get_money( Currency::EUR );
        self::assertNotNull( $eur_money );
        self::assertSame( '€45.00', $eur_money->format() );

        $invoice->set_money( Money::from_cents( 10050, Currency::GBP ) );
        self::assertSame( '100.50', $invoice->get_amount() );
        self::assertSame( 10050, $invoice->get_money()->get_cents() );

        $invoice->set_amount( Money::from_cents( 2500, Currency::EUR ) );
        self::assertSame( '25.00', $invoice->get_amount() );

        $invoice->set_amount( 75.5 );
        self::assertSame( '75.5', $invoice->get_amount() );

        $invoice->set_amount( '' );
        self::assertNull( $invoice->get_amount() );
        self::assertNull( $invoice->get_money() );

        $invoice->set_amount( null );
        self::assertNull( $invoice->get_amount() );
    }

    public function test_withdrawal_refund_invoice_supports_negative_amount_and_reflects_in_money_value_object(): void {
        $invoice = new Invoice();
        $invoice->set_amount( '-15.50' );

        self::assertSame( '-15.50', $invoice->get_amount() );
        $money = $invoice->get_money();
        self::assertNotNull( $money );
        self::assertSame( -1550, $money->get_cents() );
        self::assertSame( '-£15.50', $money->format() );

        $invoice->set_money( Money::from_cents( -2500, Currency::GBP ) );
        self::assertSame( '-25.00', $invoice->get_amount() );
        self::assertSame( -2500, $invoice->get_money()->get_cents() );
        self::assertSame( '-£25.00', $invoice->get_money()->format() );

        $invoice->set_amount( -30.0 );
        self::assertSame( '-30', $invoice->get_amount() );
        self::assertSame( -3000, $invoice->get_money()->get_cents() );
    }

    public function test_details_json_encoding_and_raw_string(): void {
        $invoice = new Invoice();

        $invoice->set_details( '{"direct":"json"}' );
        self::assertSame( '{"direct":"json"}', $invoice->get_details() );

        $invoice->set_details( (object) array( 'key' => 'value' ) );
        self::assertSame( '{"key":"value"}', $invoice->get_details() );
    }

    public function test_factory_and_dto_round_trip(): void {
        $raw = (object) array(
            'id'             => '77',
            'invoice_number' => '1077',
            'charge_id'      => '5',
            'billable_id'    => '20',
            'billable_type'  => 'club',
            'status'         => 'paid',
            'date'           => '2026-05-01',
            'date_due'       => '2026-06-01',
            'amount'         => '85.00',
            'details'        => '{"items":3}',
        );

        $from_object = Invoice::from_object( $raw );
        self::assertSame( 77, $from_object->get_id() );
        self::assertSame( 1077, $from_object->get_invoice_number() );
        self::assertSame( 5, $from_object->get_charge_id() );
        self::assertSame( 20, $from_object->get_billable_id() );
        self::assertSame( 'club', $from_object->get_billable_type() );
        self::assertSame( 'paid', $from_object->get_status() );
        self::assertSame( '2026-05-01', $from_object->get_date() );
        self::assertSame( '2026-06-01', $from_object->get_date_due() );
        self::assertSame( '85.00', $from_object->get_amount() );
        self::assertSame( '{"items":3}', $from_object->get_details() );

        $raw_array = array(
            'id'             => 77,
            'invoice_number' => 1077,
            'charge_id'      => 5,
            'billable_id'    => 20,
            'billable_type'  => 'club',
            'status'         => 'paid',
            'date'           => '2026-05-01',
            'date_due'       => '2026-06-01',
            'amount'         => '85.00',
            'details'        => '{"items":3}',
        );
        $from_array = Invoice::from_array( $raw_array );
        self::assertSame( 77, $from_array->get_id() );
        self::assertSame( 1077, $from_array->get_invoice_number() );
        self::assertSame( 5, $from_array->get_charge_id() );
        self::assertSame( 20, $from_array->get_billable_id() );
        self::assertSame( 'club', $from_array->get_billable_type() );
        self::assertSame( 'paid', $from_array->get_status() );
        self::assertSame( '2026-05-01', $from_array->get_date() );
        self::assertSame( '2026-06-01', $from_array->get_date_due() );
        self::assertSame( '85.00', $from_array->get_amount() );
        self::assertSame( '{"items":3}', $from_array->get_details() );
        self::assertEquals( $from_object, $from_array );

        $dto = Invoice_Hydration_DTO::from_object( $raw );
        $from_dto = Invoice_Factory::create_from_dto( $dto );
        self::assertSame( 77, $from_dto->get_id() );
        self::assertSame( '2026-05-01', $from_dto->get_date() );
        self::assertSame( '2026-06-01', $from_dto->get_date_due() );
        self::assertSame( '85.00', $from_dto->get_amount() );
        self::assertSame( '{"items":3}', $from_dto->get_details() );
        self::assertEquals( $from_object, $from_dto );
    }

    public function test_repository_save_uses_typed_getters_and_hydrates_id(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 789;

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['wpdb'] = $wpdb;

        $wpdb->expects( self::once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_invoices',
                array(
                    'charge_id'         => 8,
                    'billable_id'       => 15,
                    'billable_type'     => 'club',
                    'invoice_number'    => 2026001,
                    'date'              => '2026-06-01',
                    'date_due'          => '2026-07-01',
                    'status'            => 'draft',
                    'amount'            => '150.00',
                    'payment_reference' => 'REF-001',
                    'purchase_order'    => 'PO-001',
                    'details'           => '{"items":1}',
                ),
                array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
            )
            ->willReturn( 1 );

        $invoice = new Invoice();
        $invoice->set_charge_id( 8 );
        $invoice->set_billable_id( 15 );
        $invoice->set_billable_type( 'club' );
        $invoice->set_invoice_number( 2026001 );
        $invoice->set_date( '2026-06-01' );
        $invoice->set_date_due( '2026-07-01' );
        $invoice->set_amount( '150.00' );
        $invoice->set_payment_reference( 'REF-001' );
        $invoice->set_purchase_order( 'PO-001' );
        $invoice->set_details( '{"items":1}' );

        $repo = new Invoice_Repository();
        $result = $repo->save( $invoice );

        self::assertTrue( $result );
        self::assertSame( 789, $invoice->get_id() );

        // Test update path
        $wpdb->expects( self::once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_invoices',
                array(
                    'charge_id'         => 8,
                    'billable_id'       => 15,
                    'billable_type'     => 'club',
                    'invoice_number'    => 2026001,
                    'date'              => '2026-06-01',
                    'date_due'          => '2026-07-01',
                    'status'            => 'final',
                    'amount'            => '150.00',
                    'payment_reference' => 'REF-001',
                    'purchase_order'    => 'PO-001',
                    'details'           => '{"items":1}',
                ),
                array( 'id' => 789 ),
                array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ),
                array( '%d' )
            )
            ->willReturn( 1 );

        $invoice->set_status( 'final' );
        $update_result = $repo->save( $invoice );
        self::assertSame( 1, $update_result );
    }
}
