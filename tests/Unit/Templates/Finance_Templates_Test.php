<?php
/**
 * Finance Templates Render Test
 *
 * @package RacketManager\Tests\Unit\Templates
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Query;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Finance\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Invoice_Item_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_View_Model;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class Finance_Templates_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Php_View_Renderer $renderer;
    private Invoice_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->renderer  = new Php_View_Renderer( dirname( __DIR__, 3 ) . '/templates/' );
        $this->presenter = new Invoice_Presenter();

        global $racketmanager;
        $racketmanager = new class {
            public function is_user_favourite( $type = null, $id = null ): bool {
                return false;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_invoice_template_renders_when_user_logged_in(): void {
        $invoice_vm = new Invoice_View_Model(
            id: 99,
            invoice_number: '2026-99',
            title: 'Invoice 2026-99 (paid)',
            status: 'paid',
            status_display: 'PAID',
            is_paid: true,
            date: '2026-06-01',
            date_formatted: '01-06-2026',
            date_due: '2026-07-01',
            date_due_formatted: '01-07-2026',
            due_text: 'Payment Due: 01-07-2026',
            purchase_order: 'PO-12345',
            purchase_order_display: 'Purchase Order: PO-12345',
            billable_name: 'Thorpe Bay LTC',
            billable_address: '10 Court Road, Southend',
            billable_address_formatted: '10 Court Road<br /> Southend',
            charge_name: 'Annual Affiliation Fee',
            organisation_name: 'Leigh & Westcliff League',
            billing_address: 'Main Pavilion<br /> Leigh-on-Sea',
            billing_email: 'accounts@leighleague.org',
            billing_url: 'leighleague.org',
            billing_telephone: '07000 112233',
            has_breakdown: true,
            fee_competition_formatted: '£5.25',
            fee_events_unit_formatted: '£12.50',
            items: [
                new Invoice_Item_View_Model(
                    description: 'Men\'s Division 1',
                    quantity: 2,
                    unit_price_formatted: '£12.50',
                    net_price_formatted: '£25.00'
                ),
            ],
            fee_total_formatted: '£30.25',
            has_paid: true,
            paid_formatted: '£10.00',
            amount_due_formatted: '£20.25',
            bank_name: 'NatWest Bank',
            sort_code: '60-00-01',
            account_number: '87654321',
            payment_reference: '2026-99',
            is_user_logged_in: true,
            login_url: '/login'
        );

        $html = $this->renderer->render_to_string( 'invoice', [], [
            'invoice'           => $invoice_vm,
            'organisation_name' => $invoice_vm->organisation_name,
            'billing'           => [],
            'invoice_number'    => $invoice_vm->invoice_number,
        ] );

        $this->assertStringContainsString( '<h2>Invoice&nbsp;2026-99</h2>', $html );
        $this->assertStringContainsString( '<div class="invoice-date">01-06-2026</div>', $html );
        $this->assertStringContainsString( 'Payment Due: 01-07-2026', $html );
        $this->assertStringContainsString( 'Purchase Order: PO-12345', $html );
        $this->assertStringContainsString( 'PAID', $html );
        $this->assertStringContainsString( 'Thorpe Bay LTC', $html );
        $this->assertStringContainsString( '10 Court Road<br /> Southend', $html );
        $this->assertStringContainsString( 'Annual Affiliation Fee', $html );
        $this->assertStringContainsString( 'Competition Entry Fee', $html );
        $this->assertStringContainsString( 'Men\'s Division 1', $html );
        $this->assertStringContainsString( '£25.00', $html );
        $this->assertStringContainsString( '£30.25', $html );
        $this->assertStringContainsString( 'Paid', $html );
        $this->assertStringContainsString( 'Due', $html );
        $this->assertStringContainsString( '£10.00', $html );
        $this->assertStringContainsString( '£20.25', $html );
        $this->assertStringContainsString( 'NatWest Bank', $html );
        $this->assertStringContainsString( '60-00-01', $html );
        $this->assertStringContainsString( '87654321', $html );
    }

    public function test_invoice_template_renders_end_to_end_from_dto_and_presenter(): void {
        $invoice = new Invoice();
        $invoice->id                = 123;
        $invoice->charge_id         = 10;
        $invoice->billable_id       = 20;
        $invoice->billable_type     = 'club';
        $invoice->invoice_number    = 2026123;
        $invoice->status            = 'final';
        $invoice->amount            = '30.25';
        $invoice->date              = '2026-06-01';
        $invoice->date_due          = '2026-07-01';
        $invoice->purchase_order    = 'PO-987';
        $invoice->payment_reference = '2026123';

        $details_json = json_encode( [
            'fee_competition' => 5.25,
            'fee_events'      => 12.50,
            'fee'             => 30.25,
            'paid'            => 10.00,
            'total'           => 20.25,
            'events'          => [
                [
                    'name'  => 'Men\'s Division 1',
                    'count' => 2,
                    'fee'   => 25.00,
                ],
            ],
        ] );
        $invoice->details = json_decode( $details_json );

        $charge = new Charge_Details_DTO( (object) [
            'id'               => 10,
            'season'           => 2026,
            'date'             => '2026-05-01',
            'competition_id'   => 1,
            'competition_name' => 'Annual Affiliation Fee',
            'competition_type' => 'league',
            'status'           => 'final',
        ] );

        $dto = new Invoice_Full_Details_DTO(
            invoice: $invoice,
            billable_name: 'Thorpe Bay LTC',
            billable_address: '10 Court Road, Southend',
            contact: null,
            charge: $charge
        );

        $billing = [
            'billingAddress'   => 'Main Pavilion, Leigh-on-Sea',
            'billingEmail'     => 'accounts@leighleague.org',
            'billingTelephone' => '07000 112233',
            'billingCurrency'  => 'EUR',
            'bankName'         => 'NatWest Bank',
            'sortCode'         => '60-00-01',
            'accountNumber'    => '87654321',
        ];

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )->willReturn( $dto );
        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( $billing );
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static fn( string $option, mixed $default = false ): mixed => match ( $option ) {
                'blogname'    => 'Leigh &amp; Westcliff Tennis',
                'siteurl'     => 'https://leighleague.org',
                'date_format' => 'd/m/Y',
                default       => $default,
            }
        );
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway );
        $result  = $handler->handle( new Get_Invoice_Query( id: 123 ) );

        $invoice_vm = $this->presenter->present_invoice(
            invoice: $result->invoice,
            billing: $result->billing,
            organisation_name: $result->organisation_name,
            site_url: $result->site_url,
            date_format: $result->date_format,
            is_user_logged_in: $result->is_user_logged_in,
            login_url: $result->login_url
        );

        $this->assertSame( 123, $invoice_vm->id );
        $this->assertSame( '2026123', $invoice_vm->invoice_number );
        $this->assertSame( '01/06/2026', $invoice_vm->date_formatted );
        $this->assertSame( 'Payment Due: 01/07/2026', $invoice_vm->due_text );
        $this->assertSame( 'leighleague.org', $invoice_vm->billing_url );
        $this->assertTrue( $invoice_vm->is_user_logged_in );
        $this->assertSame( 'https://example.com/wp-login.php', $invoice_vm->login_url );

        $filename = ! empty( $result->template ) ? 'invoice-' . $result->template : 'invoice';
        $vars = [
            'organisation_name' => $result->organisation_name,
            'invoice'           => $invoice_vm,
            'billing'           => $result->billing,
            'invoice_number'    => $result->invoice_number,
        ];
        $html = $this->renderer->render_to_string( $filename, [], $vars );

        $this->assertStringContainsString( '<h2>Invoice&nbsp;2026123</h2>', $html );
        $this->assertStringContainsString( 'Leigh & Westcliff Tennis', $html );
        $this->assertStringContainsString( 'Main Pavilion<br /> Leigh-on-Sea', $html );
        $this->assertStringContainsString( '01/06/2026', $html );
        $this->assertStringContainsString( 'Payment Due: 01/07/2026', $html );
        $this->assertStringContainsString( 'Purchase Order: PO-987', $html );
        $this->assertStringContainsString( 'Thorpe Bay LTC', $html );
        $this->assertStringContainsString( '10 Court Road<br /> Southend', $html );
        $this->assertStringContainsString( 'Annual Affiliation Fee', $html );
        $this->assertStringContainsString( 'Competition Entry Fee', $html );
        $this->assertStringContainsString( 'Men\'s Division 1', $html );
        $this->assertStringContainsString( '€5.25', $html );
        $this->assertStringContainsString( '€25.00', $html );
        $this->assertStringContainsString( '€30.25', $html );
        $this->assertStringContainsString( 'Paid', $html );
        $this->assertStringContainsString( 'Due', $html );
        $this->assertStringContainsString( '€10.00', $html );
        $this->assertStringContainsString( '€20.25', $html );
        $this->assertStringContainsString( 'NatWest Bank', $html );
        $this->assertStringContainsString( '60-00-01', $html );
        $this->assertStringContainsString( '87654321', $html );
    }

    public function test_club_invoice_handler_renders_list_with_billing_currency_and_site_date_format(): void {
        $club = new Club();
        $club->id = 10;
        $club->name = 'Leigh LTC';
        $club->shortcode = 'Leigh';

        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoices_by_criteria' )->willReturn( [
            (object) [
                'id'             => 21,
                'invoice_number' => 'INV-021',
                'charge_name'    => 'summer league fees',
                'amount'         => '12.50',
                'status'         => 'sent',
                'date_due'       => '2026-05-01',
            ],
        ] );
        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [ 'billingCurrency' => 'EUR' ] );
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static fn( string $option, mixed $default = false ): mixed => 'date_format' === $option ? 'd/m/Y' : $default
        );

        $handler = new Get_Club_Invoices_Handler(
            $club_service,
            $finance_service,
            $settings_service,
            $option_gateway
        );
        $result = $handler->handle( new Get_Club_Invoices_Query( club_name: 'Leigh', is_admin: true ) );
        $invoices_vm = $this->presenter->present_club_invoices_list(
            invoices: $result->invoices_data,
            currency_code: $result->currency_code,
            date_format: $result->date_format
        );
        $view = new \Racketmanager\Presentation\View_Models\Shortcode_View_Model(
            template: 'invoices',
            vars: [
                'club'            => $result->club,
                'user_can_manage' => $result->user_can_manage,
                'invoices'        => $invoices_vm,
                'invoice'         => null,
            ],
            template_type: 'club'
        );
        $renderer = new Shortcode_View_Presenter( new Shortcode_Template_Renderer( $this->renderer ) );
        $html = $renderer->render( $view );

        $this->assertStringContainsString( 'INV-021', $html );
        $this->assertStringContainsString( '€', $html );
        $this->assertStringContainsString( '12.50', $html );
        $this->assertStringContainsString( '01/05/2026', $html );
    }

    public function test_club_invoice_handler_presents_single_invoice_detail(): void {
        $club = new Club();
        $club->id = 10;
        $club->name = 'Leigh LTC';
        $club->shortcode = 'Leigh';
        $invoice = new Invoice();
        $invoice->id = 22;
        $invoice->invoice_number = 2026022;
        $invoice->billable_id = 10;
        $invoice->billable_type = 'club';
        $invoice->status = 'sent';

        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )->willReturn( $invoice );
        $settings_service = new Settings_Service( [ 'billing' => [ 'billingCurrency' => 'EUR' ] ] );
        $handler = new Get_Club_Invoices_Handler(
            $club_service,
            $finance_service,
            $settings_service,
            $this->createStub( Option_Gateway_Interface::class )
        );

        $result = $handler->handle( new Get_Club_Invoices_Query( club_name: 'Leigh', invoice_ref: '22', is_admin: true ) );
        $club_presenter = new Club_Presenter();
        $return_url = $result->club ? $club_presenter->get_link( $result->club, 'invoices' ) : '';
        $details = function_exists( '\Racketmanager\show_invoice' ) ? \Racketmanager\show_invoice( $result->invoice->id ) : '';
        $invoice_vm = $this->presenter->present_club_invoice_detail(
            invoice: $result->invoice,
            club: $result->club,
            return_url: $return_url,
            details: $details
        );
        $view = new \Racketmanager\Presentation\View_Models\Shortcode_View_Model(
            template: $result->template_ref,
            vars: [
                'club'            => $result->club,
                'user_can_manage' => $result->user_can_manage,
                'invoices'        => null,
                'invoice'         => $invoice_vm,
            ],
            template_type: 'club'
        );

        $this->assertSame( 'invoice', $view->template );
        $this->assertSame( 22, $view->vars['invoice']->id );
        $this->assertSame( '/clubs/leigh/invoices/', $view->vars['invoice']->return_url );
        $this->assertTrue( $view->vars['user_can_manage']->club );
    }

    public function test_invoice_template_renders_login_message_when_not_logged_in(): void {
        $invoice_vm = new Invoice_View_Model(
            is_user_logged_in: false,
            login_url: 'https://example.com/wp-login.php'
        );

        $html = $this->renderer->render_to_string( 'invoice', [], [
            'invoice' => $invoice_vm,
        ] );

        $this->assertStringContainsString( 'You need to <a href="https://example.com/wp-login.php">login</a> to view invoices', $html );
        $this->assertStringNotContainsString( 'id="invoice-header"', $html );
    }

    public function test_club_invoice_template_renders_with_details_and_return_url(): void {
        $club            = new stdClass();
        $club->id        = 10;
        $club->name      = 'Leigh LTC';
        $club->shortcode = 'Leigh';

        $invoice_vm = new Invoice_View_Model(
            id: 55,
            invoice_number: '2026-55',
            title: 'Invoice 2026-55 (sent)',
            status: 'sent',
            return_url: '/clubs/leigh/invoices/',
            details: '<div id="testInvoiceBreakdown">Breakdown Content</div>'
        );

        $user_can_manage       = new stdClass();
        $user_can_manage->club = true;

        $html = $this->renderer->render_to_string( 'club/invoice', [], [
            'club'            => $club,
            'invoice'         => $invoice_vm,
            'user_can_manage' => $user_can_manage,
        ] );

        $this->assertStringContainsString( 'Invoice 2026-55 (sent)', $html );
        $this->assertStringContainsString( 'data-invoice-id="55"', $html );
        $this->assertStringContainsString( 'Manage Purchase Order', $html );
        $this->assertStringContainsString( 'Breakdown Content', $html );
        $this->assertStringContainsString( 'href="/clubs/leigh/invoices/"', $html );
    }

    public function test_club_invoices_list_template_renders_table_and_total(): void {
        $club            = new stdClass();
        $club->id        = 10;
        $club->name      = 'Leigh LTC';
        $club->shortcode = 'Leigh';

        $invoices_data = [
            (object) [
                'id'             => 10,
                'invoice_number' => 'INV-001',
                'charge_name'    => 'summer league balls',
                'amount'         => '12.50',
                'status'         => 'sent',
                'date_due'       => '2026-05-01',
            ],
            (object) [
                'id'             => 11,
                'invoice_number' => 'INV-002',
                'charge_name'    => 'cup entry',
                'amount'         => '20.25',
                'status'         => 'paid',
                'date_due'       => '2026-06-01',
            ],
        ];

        $list_vm = $this->presenter->present_club_invoices_list( $invoices_data, 'GBP', 'd-m-Y' );

        $html = $this->renderer->render_to_string( 'club/invoices', [], [
            'club'     => $club,
            'invoices' => $list_vm,
        ] );

        $this->assertStringContainsString( 'Invoices', $html );
        $this->assertStringContainsString( 'href="10/"', $html );
        $this->assertStringContainsString( 'INV-001', $html );
        $this->assertStringContainsString( 'Summer league balls', $html );
        $this->assertStringContainsString( '£12.50', $html );
        $this->assertStringContainsString( 'sent', $html );
        $this->assertStringContainsString( '01-05-2026', $html );

        $this->assertStringContainsString( 'href="11/"', $html );
        $this->assertStringContainsString( 'INV-002', $html );
        $this->assertStringContainsString( 'Cup entry', $html );
        $this->assertStringContainsString( '£20.25', $html );
        $this->assertStringContainsString( 'paid', $html );
        $this->assertStringContainsString( '01-06-2026', $html );

        $this->assertStringContainsString( 'Total', $html );
        $this->assertStringContainsString( '£32.75', $html );
    }

    public function test_admin_charges_template_renders_view_model_with_currency_and_totals(): void {
        $charges_data = [
            (object) [
                'id'     => 101,
                'name'   => 'Summer League 2026',
                'status' => 'final',
                'total'  => '150.75',
            ],
            (object) [
                'id'     => 102,
                'name'   => 'Winter Cup 2026',
                'status' => 'final',
                'total'  => '0.50',
            ],
            (object) [
                'id'     => 103,
                'name'   => 'Spring Plate 2026',
                'status' => 'draft',
                'total'  => null,
            ],
            (object) [
                'id'     => 104,
                'name'   => 'Autumn Trophy 2026',
                'status' => 'draft',
                'total'  => '0',
            ],
        ];

        $competitions = [
            (object) [ 'id' => 1, 'name' => 'Main League' ],
        ];
        $seasons = [
            (object) [ 'name' => '2026' ],
        ];

        $vm = $this->presenter->present_admin_charges_list(
            charges: $charges_data,
            competitions: $competitions,
            seasons: $seasons,
            competition_id: 1,
            season: '2026',
            tab: 'charges',
            currency_code: 'GBP'
        );

        $html = $this->renderer->render_to_string( 'admin/finances/charges', $vm, [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'id="charges-action"', $html );
        $this->assertStringContainsString( 'Summer League 2026', $html );
        $this->assertStringContainsString( 'Winter Cup 2026', $html );
        $this->assertStringContainsString( 'Spring Plate 2026', $html );
        $this->assertStringContainsString( 'Autumn Trophy 2026', $html );
        $this->assertStringContainsString( 'final', $html );
        $this->assertStringContainsString( 'draft', $html );
        $this->assertStringContainsString( '<td class="text-end">£150.75</td>', $html );
        $this->assertStringContainsString( '<td class="text-end">£0.50</td>', $html );
        $this->assertStringContainsString( '<td class="text-end">-</td>', $html );
        $this->assertStringContainsString( '<tfoot class="table-footer">', $html );
        $this->assertStringContainsString( '<td class="text-end">£151.25</td>', $html );
    }

    public function test_admin_invoices_template_renders_club_and_player_invoices(): void {
        $invoices_data = [
            (object) [
                'id'             => 501,
                'invoice_number' => 2026001,
                'charge_name'    => 'summer league balls',
                'billable_name'  => 'Westcliff Lawn LTC',
                'amount'         => '85.50',
                'status'         => 'sent',
                'date_due'       => '2026-06-30',
            ],
        ];

        $charges = [
            (object) [ 'id' => 10, 'name' => 'Summer League' ],
        ];
        $clubs = [
            (object) [ 'id' => 20, 'shortcode' => 'Westcliff' ],
        ];

        $vm = $this->presenter->present_admin_invoices_list(
            invoices: $invoices_data,
            charges: $charges,
            clubs: $clubs,
            tab: 'club-invoices',
            club_id: 20,
            charge_id: 10,
            status: 'open',
            currency_code: 'GBP',
            date_format: 'd-m-Y'
        );

        $html = $this->renderer->render_to_string( 'admin/finances/invoices', $vm, [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'id="invoices-action"', $html );
        $this->assertStringContainsString( '2026001', $html );
        $this->assertStringContainsString( 'href="/wp-admin/admin.php?page=racketmanager-finances&view=invoice&invoice=501"', $html );
        $this->assertStringContainsString( 'Summer league balls', $html );
        $this->assertStringContainsString( 'Westcliff Lawn LTC', $html );
        $this->assertStringContainsString( '£85.50', $html );
        $this->assertStringContainsString( 'sent', $html );
        $this->assertStringContainsString( '30-06-2026', $html );
        $this->assertStringContainsString( '<td class="text-end">£85.50</td>', $html );
    }

    public function test_admin_charge_detail_template_renders_form_and_club_breakdown(): void {
        $charge = (object) [
            'id'                          => 77,
            'competition_id'              => 1,
            'season'                      => '2026',
            'status'                      => 'final',
            'date'                        => '2026-04-01',
            'fee_competition'             => '50.00',
            'fee_event'                   => '25.00',
            'competition_is_team_entry'   => true,
        ];

        $club_charges = [
            (object) [
                'name'      => 'Leigh Tennis Club',
                'num_teams' => 3,
                'fee'       => '75.00',
                'events'    => [
                    (object) [
                        'type'  => 'MS',
                        'count' => 3,
                        'fee'   => 75.00,
                    ],
                ],
            ],
        ];

        $vm = $this->presenter->present_admin_charge_detail(
            charge: $charge,
            competitions: [ (object) [ 'id' => 1, 'name' => 'Main League' ] ],
            seasons: [ (object) [ 'name' => '2026' ] ],
            club_charges: $club_charges,
            edit: true,
            form_title: 'Edit League Charges',
            form_action: 'Save',
            currency_code: 'GBP'
        );

        $html = $this->renderer->render_to_string( 'admin/finances/charge', $vm, [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'Edit League Charges', $html );
        $this->assertStringContainsString( 'name="competition_id"', $html );
        $this->assertStringContainsString( 'name="season"', $html );
        $this->assertStringContainsString( 'name="feeClub"', $html );
        $this->assertStringContainsString( 'value="50.00"', $html );
        $this->assertStringContainsString( 'value="25.00"', $html );
        $this->assertStringContainsString( 'Club charges', $html );
        $this->assertStringContainsString( 'Leigh Tennis Club', $html );
        $this->assertStringContainsString( '<div class="col-3">Mens Singles</div>', $html );
        $this->assertStringContainsString( '<div class="col-2">£75.00</div>', $html );
        $this->assertStringContainsString( '£75.00', $html );
        $this->assertStringContainsString( 'Generate Invoices', $html );
    }

    public function test_admin_invoice_page_template_renders_view_model_and_amend_form(): void {
        $invoice = (object) [
            'id'             => 88,
            'charge_name'    => 'summer balls',
            'billable_name'  => 'Hadleigh TC',
            'invoice'        => new class {
                public string $billable_type = 'club';
                public function get_id(): int { return 88; }
                public function get_invoice_number(): string { return '2026088'; }
                public function get_status(): string { return 'final'; }
                public function get_purchase_order(): string { return 'PO-HADLEIGH-1'; }
            },
        ];

        $vm = $this->presenter->present_admin_invoice_page(
            invoice: $invoice,
            invoice_view: '<div class="test-invoice-rendered-output">Rendered Invoice Line Items</div>'
        );

        $html = $this->renderer->render_to_string( 'admin/finances/invoice', $vm, [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'Invoice 2026088 (final)', $html );
        $this->assertStringContainsString( 'Summer balls - Hadleigh TC', $html );
        $this->assertStringContainsString( 'name="purchaseOrder"', $html );
        $this->assertStringContainsString( 'value="PO-HADLEIGH-1"', $html );
        $this->assertStringContainsString( 'value="88"', $html );
        $this->assertStringContainsString( 'value="final"', $html );
        $this->assertStringContainsString( 'selected="selected">Final</option>', $html );
        $this->assertStringContainsString( 'test-invoice-rendered-output', $html );
        $this->assertStringContainsString( 'href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=club-invoices"', $html );
    }

    public function test_admin_show_charges_and_show_invoices_tab_templates_render(): void {
        $charges_vm = $this->presenter->present_admin_charges_list(
            charges: [],
            competitions: [],
            seasons: [],
            tab: 'charges'
        );

        $html_charges = $this->renderer->render_to_string( 'admin/finances/show-charges', $charges_vm, [ 'vm' => $charges_vm ] );
        $this->assertStringContainsString( 'nav-tabs', $html_charges );
        $this->assertStringContainsString( 'racketmanager-charges', $html_charges );

        $invoices_vm = $this->presenter->present_admin_invoices_list(
            invoices: [],
            charges: [],
            clubs: [],
            tab: 'club-invoices'
        );

        $html_invoices = $this->renderer->render_to_string( 'admin/finances/show-invoices', $invoices_vm, [ 'vm' => $invoices_vm ] );
        $this->assertStringContainsString( 'nav-tabs', $html_invoices );
        $this->assertStringContainsString( 'racketmanager-club-invoices', $html_invoices );
    }
}
