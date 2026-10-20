<?php
/**
 * Invoice Presenter Test
 *
 * @package RacketManager\Tests\Unit\Presentation\Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\View_Models\Invoice_Item_View_Model;
use stdClass;

class Invoice_Presenter_Test extends TestCase {
    private Invoice_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Invoice_Presenter();
    }

    public function test_present_invoice_with_full_details_and_breakdown(): void {
        $details                   = new stdClass();
        $details->fee_competition  = '25.50';
        $details->fee_events       = 12.50;
        $details->fee              = 50.50;
        $details->paid             = 20.25;
        $details->total            = 30.25;

        $event1                    = new stdClass();
        $event1->name              = 'Men\'s Singles';
        $event1->count             = 2;
        $event1->fee               = 25.00;

        $event2                    = new stdClass();
        $event2->type              = 'MD';
        $event2->count             = 1;
        $event2->fee               = 12.50;

        $details->events           = [ $event1, $event2 ];

        $invoice                   = (object) [
            'id'               => 101,
            'invoice_number'   => '2026-INV-001',
            'status'           => 'paid',
            'date'             => '2026-05-10',
            'date_due'         => '2026-06-10',
            'purchase_order'   => 'PO-9876',
            'billable_name'    => 'Westcliff LTC',
            'billable_address' => '10 Tennis Court Way, Westcliff, Essex',
            'charge_name'      => 'summer league tournament',
            'details'          => $details,
        ];

        $billing = [
            'billingAddress'   => '1 HQ Lane, London, UK',
            'billingEmail'     => 'finance@example.com',
            'billingTelephone' => '01234 567890',
            'billingCurrency'  => 'GBP',
            'bankName'         => 'Barclays Bank',
            'sortCode'         => '20-00-00',
            'accountNumber'    => '12345678',
        ];

        $vm = $this->presenter->present_invoice(
            invoice: $invoice,
            billing: $billing,
            organisation_name: 'Leigh & Westcliff League',
            site_url: 'https://example.com/site',
            date_format: 'd/m/Y',
            is_user_logged_in: true,
            login_url: 'https://example.com/wp-login.php'
        );

        $this->assertSame( 101, $vm->id );
        $this->assertSame( '2026-INV-001', $vm->invoice_number );
        $this->assertSame( 'Invoice 2026-INV-001 (paid)', $vm->title );
        $this->assertSame( 'paid', $vm->status );
        $this->assertSame( 'PAID', $vm->status_display );
        $this->assertTrue( $vm->is_paid );
        $this->assertSame( '10/05/2026', $vm->date_formatted );
        $this->assertSame( 'Payment Due: 10/06/2026', $vm->due_text );
        $this->assertSame( 'Purchase Order: PO-9876', $vm->purchase_order_display );
        $this->assertSame( 'Westcliff LTC', $vm->billable_name );
        $this->assertSame( '10 Tennis Court Way<br /> Westcliff<br /> Essex', $vm->billable_address_formatted );
        $this->assertSame( 'Summer league tournament', $vm->charge_name );
        $this->assertSame( '1 HQ Lane<br /> London<br /> UK', $vm->billing_address );
        $this->assertSame( 'finance@example.com', $vm->billing_email );
        $this->assertSame( '01234 567890', $vm->billing_telephone );
        $this->assertSame( 'example.com/site', $vm->billing_url );
        $this->assertTrue( $vm->has_breakdown );
        $this->assertSame( '£25.50', $vm->fee_competition_formatted );
        $this->assertSame( '£12.50', $vm->fee_events_unit_formatted );
        $this->assertCount( 2, $vm->items );
        $this->assertSame( 'Men\'s Singles', $vm->items[0]->description );
        $this->assertSame( 2, $vm->items[0]->quantity );
        $this->assertSame( '£12.50', $vm->items[0]->unit_price_formatted );
        $this->assertSame( '£25.00', $vm->items[0]->net_price_formatted );
        $this->assertSame( '£50.50', $vm->fee_total_formatted );
        $this->assertTrue( $vm->has_paid );
        $this->assertSame( '£20.25', $vm->paid_formatted );
        $this->assertSame( '£30.25', $vm->amount_due_formatted );
        $this->assertSame( 'Barclays Bank', $vm->bank_name );
        $this->assertSame( '20-00-00', $vm->sort_code );
        $this->assertSame( '12345678', $vm->account_number );
        $this->assertSame( '2026-INV-001', $vm->payment_reference );
        $this->assertTrue( $vm->is_user_logged_in );
    }

    public function test_present_invoice_due_on_receipt_when_dates_match(): void {
        $invoice = (object) [
            'invoice_number' => 'INV-10',
            'status'         => 'draft',
            'date'           => '2026-05-10',
            'date_due'       => '2026-05-10',
        ];

        $vm = $this->presenter->present_invoice( invoice: $invoice, date_format: 'd-m-Y' );

        $this->assertSame( 'Payment due on receipt', $vm->due_text );
        $this->assertNull( $vm->status_display );
        $this->assertFalse( $vm->is_paid );
    }

    public function test_present_invoice_handles_null_invoice_and_fallbacks(): void {
        $vm = $this->presenter->present_invoice( null, [ 'invoiceNumber' => 'FALLBACK-01' ] );

        $this->assertSame( 'FALLBACK-01', $vm->invoice_number );
        $this->assertSame( 'Invoice FALLBACK-01', $vm->title );
        $this->assertSame( '', $vm->date_formatted );
        $this->assertSame( '', $vm->due_text );
        $this->assertFalse( $vm->has_breakdown );
        $this->assertSame( '£0.00', $vm->fee_total_formatted );
        $this->assertFalse( $vm->has_paid );
        $this->assertNull( $vm->paid_formatted );
    }

    public function test_present_club_invoice_detail(): void {
        $club = (object) [ 'id' => 10, 'shortcode' => 'Leigh' ];
        $invoice = (object) [
            'id'             => 42,
            'invoice_number' => '4242',
            'status'         => 'sent',
        ];

        $vm = $this->presenter->present_club_invoice_detail(
            invoice: $invoice,
            club: $club,
            return_url: '/clubs/leigh/invoices/',
            details: '<p>Detail HTML</p>'
        );

        $this->assertSame( 42, $vm->id );
        $this->assertSame( 'Invoice 4242 (sent)', $vm->title );
        $this->assertSame( '/clubs/leigh/invoices/', $vm->return_url );
        $this->assertSame( '<p>Detail HTML</p>', $vm->details );
    }

    public function test_present_club_invoices_list_calculates_total_and_formats_items(): void {
        $invoices = [
            (object) [
                'id'             => 1,
                'invoice_number' => 1001,
                'charge_name'    => 'league entry',
                'amount'         => '15.50',
                'status'         => 'sent',
                'date_due'       => '2026-04-01',
            ],
            (object) [
                'id'             => 2,
                'invoice_number' => 1002,
                'charge_name'    => 'ball fees',
                'amount'         => 34.50,
                'status'         => 'paid',
                'date_due'       => '2026-05-01',
            ],
        ];

        $list = $this->presenter->present_club_invoices_list( $invoices, 'GBP', 'd-m-Y' );

        $this->assertCount( 2, $list );
        $this->assertSame( 50.00, $list->total_amount );
        $this->assertSame( '£50.00', $list->total_amount_formatted );

        $items = $list->items;
        $this->assertSame( 1, $items[0]->id );
        $this->assertSame( 'League entry', $items[0]->charge_name );
        $this->assertSame( 15.50, $items[0]->amount );
        $this->assertSame( '£15.50', $items[0]->amount_formatted );
        $this->assertSame( '01-04-2026', $items[0]->date_due_formatted );

        $this->assertSame( 2, $items[1]->id );
        $this->assertSame( 'Ball fees', $items[1]->charge_name );
        $this->assertSame( 34.50, $items[1]->amount );
        $this->assertSame( '£34.50', $items[1]->amount_formatted );
        $this->assertSame( '01-05-2026', $items[1]->date_due_formatted );
    }

    public function test_format_currency_supports_various_currencies_and_inputs(): void {
        $this->assertSame( '£10.50', $this->presenter->format_currency( 10.50, 'GBP' ) );
        $this->assertSame( '£0.00', $this->presenter->format_currency( null, 'GBP' ) );
        $this->assertSame( '£0.00', $this->presenter->format_currency( '', 'GBP' ) );
        $this->assertStringContainsString( '10.50', $this->presenter->format_currency( '10.50', 'EUR' ) );
        $this->assertStringContainsString( '10.50', $this->presenter->format_currency( 10.50, 'USD' ) );
    }

    public function test_present_admin_charges_list(): void {
        $charges = [
            (object) [
                'id'     => 10,
                'name'   => 'Summer 2026 League',
                'status' => 'final',
                'total'  => '120.50',
            ],
            (object) [
                'id'     => 11,
                'name'   => 'Winter 2026 Cup',
                'status' => 'draft',
                'total'  => null,
            ],
            (object) [
                'id'     => 12,
                'name'   => 'Spring 2026 Plate',
                'status' => 'draft',
                'total'  => '0.00',
            ],
            (object) [
                'id'     => 13,
                'name'   => 'Autumn 2026 Shield',
                'status' => 'final',
                'total'  => 0,
            ],
        ];

        $vm = $this->presenter->present_admin_charges_list(
            charges: $charges,
            competitions: [],
            seasons: [],
            competition_id: 2,
            season: '2026',
            tab: 'charges',
            currency_code: 'EUR'
        );

        $this->assertCount( 4, $vm->charges );
        $this->assertSame( 'Charges', $vm->tab_title );
        $this->assertSame( 120.50, $vm->total_amount );
        $this->assertSame( '€120.50', $vm->total_amount_formatted );
        $this->assertSame( 2, $vm->competition_id );
        $this->assertSame( '2026', $vm->season );

        $item1 = $vm->charges[0];
        $this->assertSame( 10, $item1->id );
        $this->assertSame( 'Summer 2026 League', $item1->name );
        $this->assertSame( 'final', $item1->status );
        $this->assertSame( '€120.50', $item1->total_formatted );
        $this->assertSame( '/wp-admin/admin.php?page=racketmanager-finances&view=charge&charges=10', $item1->edit_url );

        $item2 = $vm->charges[1];
        $this->assertSame( 11, $item2->id );
        $this->assertSame( '-', $item2->total_formatted );

        $item3 = $vm->charges[2];
        $this->assertSame( 12, $item3->id );
        $this->assertSame( '-', $item3->total_formatted );

        $item4 = $vm->charges[3];
        $this->assertSame( 13, $item4->id );
        $this->assertSame( '-', $item4->total_formatted );
    }

    public function test_present_admin_charges_list_handles_sub_one_unit_and_zero_totals(): void {
        $charges = [
            (object) [
                'id'     => 1,
                'name'   => 'Charge A',
                'status' => 'final',
                'total'  => '150.75',
            ],
            (object) [
                'id'     => 2,
                'name'   => 'Charge B',
                'status' => 'final',
                'total'  => '0.50',
            ],
            (object) [
                'id'     => 3,
                'name'   => 'Charge C',
                'status' => 'draft',
                'total'  => '0',
            ],
            (object) [
                'id'     => 4,
                'name'   => 'Charge D',
                'status' => 'draft',
                'total'  => null,
            ],
        ];

        $vm = $this->presenter->present_admin_charges_list(
            charges: $charges,
            competitions: [],
            seasons: [],
            tab: 'charges',
            currency_code: 'GBP'
        );

        $this->assertCount( 4, $vm->charges );
        $this->assertSame( '£150.75', $vm->charges[0]->total_formatted );
        $this->assertSame( '£0.50', $vm->charges[1]->total_formatted );
        $this->assertSame( '-', $vm->charges[2]->total_formatted );
        $this->assertSame( '-', $vm->charges[3]->total_formatted );
        $this->assertSame( 151.25, $vm->total_amount );
        $this->assertSame( '£151.25', $vm->total_amount_formatted );
    }

    public function test_present_admin_invoices_list_for_club_and_player_tabs(): void {
        $invoices = [
            (object) [
                'id'             => 201,
                'invoice_number' => 1001,
                'charge_name'    => 'summer league',
                'billable_name'  => 'Westcliff Hardcourt',
                'amount'         => '75.25',
                'status'         => 'paid',
                'date_due'       => '2026-06-15',
            ],
            (object) [
                'id'             => 202,
                'invoice_number' => 1002,
                'charge_name'    => 'cup fee',
                'billable_name'  => 'Leigh Road CC',
                'amount'         => '24.75',
                'status'         => 'sent',
                'date_due'       => '2026-07-01',
            ],
        ];

        $vm_club = $this->presenter->present_admin_invoices_list(
            invoices: $invoices,
            charges: [],
            clubs: [],
            tab: 'club-invoices',
            club_id: 5,
            charge_id: 10,
            status: 'open',
            currency_code: 'EUR',
            date_format: 'd/m/Y'
        );

        $this->assertCount( 2, $vm_club->invoices );
        $this->assertSame( 'Club Invoices', $vm_club->tab_title );
        $this->assertSame( 100.0, $vm_club->total_amount );
        $this->assertStringContainsString( '100.00', $vm_club->total_amount_formatted );
        $this->assertSame( 'Club', $vm_club->billable_type );

        $item1 = $vm_club->invoices[0];
        $this->assertSame( 201, $item1->id );
        $this->assertSame( 'Summer league', $item1->charge_name );
        $this->assertSame( 'Westcliff Hardcourt', $item1->billable_name );
        $this->assertStringContainsString( '75.25', $item1->amount_formatted );
        $this->assertSame( '2026-06-15', $item1->date_due );
        $this->assertSame( '15/06/2026', $item1->date_due_formatted );
        $this->assertSame( '/wp-admin/admin.php?page=racketmanager-finances&view=invoice&invoice=201', $item1->edit_url );

        $vm_player = $this->presenter->present_admin_invoices_list(
            invoices: [],
            charges: [],
            clubs: [],
            tab: 'player-invoices'
        );
        $this->assertSame( 'Player', $vm_player->billable_type );
        $this->assertSame( 'Player Invoices', $vm_player->tab_title );
    }

    public function test_present_admin_charge_detail_and_club_charges(): void {
        $club_charges = [
            (object) [
                'name'      => 'Westcliff Lawn',
                'num_teams' => 4,
                'fee'       => '160.00',
                'events'    => [
                    (object) [
                        'type'  => 'MS',
                        'count' => 2,
                        'fee'   => 80.00,
                    ],
                ],
            ],
        ];

        $vm = $this->presenter->present_admin_charge_detail(
            charge: (object) [ 'id' => 99 ],
            competitions: [],
            seasons: [],
            club_charges: $club_charges,
            edit: true,
            form_title: 'Edit Charge',
            form_action: 'Update',
            currency_code: 'GBP'
        );

        $this->assertTrue( $vm->edit );
        $this->assertSame( 'Edit Charge', $vm->form_title );
        $this->assertCount( 1, $vm->club_charges );

        $club = $vm->club_charges[0];
        $this->assertSame( 'Westcliff Lawn', $club->name );
        $this->assertSame( 4, $club->num_teams );
        $this->assertSame( '£160.00', $club->fee_formatted );
        $this->assertCount( 1, $club->events );

        $ev = $club->events[0];
        $this->assertSame( 'MS', $ev->type );
        $this->assertSame( '£80.00', $ev->fee_formatted );
    }

    public function test_present_admin_invoice_page(): void {
        $invoice = (object) [
            'id'             => 301,
            'charge_name'    => 'annual affiliation',
            'billable_name'  => 'Thorpe Bay LTC',
            'invoice'        => new class {
                public string $billable_type = 'club';
                public function get_id(): int { return 301; }
                public function get_invoice_number(): string { return 'INV-2026-99'; }
                public function get_status(): string { return 'final'; }
                public function get_purchase_order(): string { return 'PO-THORPE-1'; }
            },
        ];

        $vm = $this->presenter->present_admin_invoice_page(
            invoice: $invoice,
            invoice_view: '<div>Rendered HTML Table</div>'
        );

        $this->assertSame( 'club-invoices', $vm->view );
        $this->assertSame( 'Club invoices', $vm->breadcrumb );
        $this->assertSame( 'Invoice INV-2026-99 (final)', $vm->title );
        $this->assertSame( 'Annual affiliation - Thorpe Bay LTC', $vm->subtitle );
        $this->assertSame( 'final', $vm->invoice_status );
        $this->assertSame( 'PO-THORPE-1', $vm->purchase_order );
        $this->assertSame( 301, $vm->invoice_id );
        $this->assertSame( '<div>Rendered HTML Table</div>', $vm->invoice_view );
    }

    public function test_present_invoice_with_unknown_statuses(): void {
        $statuses = ['pending', 'failed', ''];
        foreach ($statuses as $status) {
            // Using REAL Invoice
            $invoice = new \Racketmanager\Domain\Finance\Invoice();
            $invoice->status = $status;
            $invoice->amount = '10.00';
            $invoice->invoice_number = 123;
            
            // Should not throw and is_paid should be false
            $vm = $this->presenter->present_invoice($invoice);
            $this->assertFalse($vm->is_paid);
            $this->assertNull($vm->status_display);
            
            // Using Invoice_Full_Details_DTO
            $invoice_dto = new \Racketmanager\Domain\Finance\Invoice();
            $invoice_dto->id = 1;
            $invoice_dto->invoice_number = 123;
            $invoice_dto->status = $status;
            $invoice_dto->amount = '10.00';
            $invoice_dto->charge_id = 1;
            $invoice_dto->billable_id = 1;
            $invoice_dto->billable_type = 'player';
            $invoice_dto->date = '2026-01-01';
            $invoice_dto->date_due = '2026-01-01';
            $invoice_dto->payment_reference = 'ref';
            $invoice_dto->purchase_order = 'po';
            $invoice_dto->details = new stdClass();
            $invoice_dto->club = new stdClass();
            $invoice_dto->charge = new stdClass();
            $invoice_dto->racketmanager = new stdClass();
            
            $charge_data = new stdClass();
            $charge_data->id = 1;
            $charge_data->competition_id = 1;
            $charge_data->season = '2026';
            $charge_data->charge_name = 'test';
            $charge_data->competition_name = 'Comp';
            $charge_data->status = 'final';
            $charge_data->competition_type = 'LEAGUE';
            $charge_data->competition_is_team_entry = true;
            $charge_data->date = '2026-01-01';
            $charge_data->fee_competition = '10.00';
            $charge_data->fee_event = '0.00';

            $charge_details = new \Racketmanager\Domain\DTO\Finance\Charge_Details_DTO($charge_data);
            
            $full_dto = new \Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO(
                $invoice_dto,
                '',
                '',
                null,
                $charge_details
            );
            
            $vm_dto = $this->presenter->present_invoice($full_dto);
            $this->assertFalse($vm_dto->is_paid);
            $this->assertNull($vm_dto->status_display);
        }
    }
}
