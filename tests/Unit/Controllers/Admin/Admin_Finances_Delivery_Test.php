<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Finances;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Domain\DTO\Finance\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Finances_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Finance_Service $finance_service;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Admin_Finances $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer            = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->finance_service     = $this->createMock( Finance_Service::class );
        $this->finance_service->method( 'get_currency' )->willReturn( Currency::GBP );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'finance_service'     => $this->finance_service,
                'competition_service' => $this->competition_service,
                'season_service'      => $this->season_service,
                'invoice_presenter'   => new Invoice_Presenter(),
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Finances( $racketmanager );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function expect_flash_pop( array $flash_data = array() ): void {
        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( $flash_data );
    }

    private function expect_competitions_and_seasons( array $competitions = array(), array $seasons = array() ): void {
        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( $competitions );
        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( $seasons );
    }

    private function expect_render_capturing( string $template, string $vm_class ): stdClass {
        $holder     = new stdClass();
        $holder->vm = null;

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                $template,
                self::callback(
                    static function ( $data ) use ( $holder, $vm_class ): bool {
                        $holder->vm = $data;
                        return $data instanceof $vm_class;
                    }
                )
            );

        return $holder;
    }

    private function create_charge_dto( array $overrides = array() ): Charge_Details_DTO {
        $raw_charge                   = new stdClass();
        $raw_charge->id               = $overrides['id'] ?? 10;
        $raw_charge->competition_id   = $overrides['competition_id'] ?? 5;
        $raw_charge->season           = $overrides['season'] ?? '2026';
        $raw_charge->status           = $overrides['status'] ?? 'draft';
        $raw_charge->date             = $overrides['date'] ?? '2026-05-01';
        $raw_charge->fee_competition  = $overrides['fee_competition'] ?? 50;
        $raw_charge->fee_event        = $overrides['fee_event'] ?? 20;
        $raw_charge->competition_name = $overrides['competition_name'] ?? 'Summer League';
        $raw_charge->competition_type = $overrides['competition_type'] ?? 'league';

        return new Charge_Details_DTO( $raw_charge );
    }

    public function test_display_finances_page_without_capability_shows_message(): void {
        $this->expectOutputRegex( '/You do not have sufficient permissions to access this page/' );
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $this->renderer->expects( self::never() )->method( 'render' );

        $this->screen->handle_display( null );
    }

    public function test_display_charges_page_handles_post_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doChargesDel']     = '1';
        $_POST['action']           = 'delete';
        $_POST['charge']           = array( 10 );

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'remove_charge' )
            ->with( 10 )
            ->willReturn( 1 );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'view=charges' ) );

        $this->renderer->expects( self::never() )->method( 'render' );

        $this->screen->display_charges_page();
    }

    public function test_display_charges_page_renders_get_view_model(): void {
        $this->expect_flash_pop();
        $this->expect_competitions_and_seasons();

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->willReturn( array(
                (object) array(
                    'id'     => 10,
                    'name'   => 'Summer 2026 League',
                    'status' => 'final',
                    'total'  => '120.50',
                ),
                (object) array(
                    'id'     => 11,
                    'name'   => 'Winter 2026 Cup',
                    'status' => 'draft',
                    'total'  => '0.00',
                ),
            ) );

        $holder = $this->expect_render_capturing( 'admin/finances/show-charges', Finances_Charges_Page_View_Model::class );

        $this->screen->display_charges_page();

        self::assertInstanceOf( Finances_Charges_Page_View_Model::class, $holder->vm );
        self::assertCount( 2, $holder->vm->charges );
        self::assertSame( '£120.50', $holder->vm->charges[0]->total_formatted );
        self::assertSame( '-', $holder->vm->charges[1]->total_formatted );
        self::assertSame( '£120.50', $holder->vm->total_amount_formatted );
    }

    public function test_display_club_invoices_page_renders_get_view_model(): void {
        $this->expect_flash_pop();

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->willReturn( array(
                (object) array(
                    'id'             => 201,
                    'invoice_number' => 1001,
                    'charge_name'    => 'summer league',
                    'billable_name'  => 'Westcliff Hardcourt',
                    'amount'         => '75.25',
                    'status'         => 'paid',
                    'date_due'       => '2026-06-15',
                ),
            ) );
        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->willReturn( array() );

        $holder = $this->expect_render_capturing( 'admin/finances/show-invoices', Finances_Invoices_Page_View_Model::class );

        $this->screen->display_club_invoices_page();

        self::assertInstanceOf( Finances_Invoices_Page_View_Model::class, $holder->vm );
        self::assertCount( 1, $holder->vm->invoices );
        self::assertSame( '£75.25', $holder->vm->invoices[0]->amount_formatted );
        self::assertSame( '£75.25', $holder->vm->total_amount_formatted );
    }

    public function test_display_charge_page_renders_add_mode_view_model(): void {
        $this->expect_flash_pop();
        $this->expect_competitions_and_seasons();

        $holder = $this->expect_render_capturing( 'admin/finances/charge', Finances_Charge_Page_View_Model::class );

        $this->screen->display_charge_page();

        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $holder->vm );
        self::assertFalse( $holder->vm->edit );
    }

    public function test_display_charge_page_rehydrates_flash_form_data_with_float_fees_without_type_error(): void {
        $_GET['charges'] = '10';

        $charge_dto = $this->create_charge_dto();

        $this->finance_service->expects( self::once() )
            ->method( 'get_charge_with_details' )
            ->with( 10 )
            ->willReturn( $charge_dto );

        $this->expect_competitions_and_seasons();
        $this->expect_flash_pop(
            array(
                'flash_data' => array(
                    'errors'    => array( 'feeClub' => 'Invalid fee' ),
                    'form_data' => array(
                        'charges_id' => '10',
                        'feeClub'    => '45.75',
                        'feeTeam'    => '18.50',
                        'status'     => 'final',
                    ),
                ),
            )
        );

        $holder = $this->expect_render_capturing( 'admin/finances/charge', Finances_Charge_Page_View_Model::class );

        $this->screen->display_charge_page();

        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $holder->vm );
        self::assertTrue( $holder->vm->edit );
        self::assertSame( 45.75, $holder->vm->charge->fee_competition );
        self::assertSame( 18.50, $holder->vm->charge->fee_event );
        self::assertSame( 'final', $holder->vm->charge->status );
        self::assertTrue( $holder->vm->errors->has( 'feeClub' ) );
    }

    public function test_display_invoice_page_renders_get_view_model_and_rehydrates_po(): void {
        $_GET['invoice'] = '101';

        $invoice                 = new Invoice();
        $invoice->id             = 101;
        $invoice->charge_id      = 5;
        $invoice->billable_id    = 12;
        $invoice->billable_type  = 'club';
        $invoice->invoice_number = 1001;
        $invoice->date           = '2026-06-01';
        $invoice->date_due       = '2026-07-01';
        $invoice->status         = 'draft';
        $invoice->amount         = '100';
        $invoice->details        = new stdClass();

        $charge_dto = $this->create_charge_dto(
            array(
                'id'               => 5,
                'competition_id'   => 2,
                'status'           => 'final',
                'competition_name' => 'League',
            )
        );

        $invoice_dto = new Invoice_Full_Details_DTO( $invoice, 'Test Club', 'Test Address', null, $charge_dto );

        $this->finance_service->expects( self::once() )
            ->method( 'get_full_invoice_details' )
            ->with( 101 )
            ->willReturn( $invoice_dto );

        $this->expect_flash_pop(
            array(
                'flash_data' => array(
                    'errors'    => array( 'status' => 'Resend failed' ),
                    'form_data' => array(
                        'status'        => 'sent',
                        'purchaseOrder' => 'PO-999',
                    ),
                ),
            )
        );

        $holder = $this->expect_render_capturing( 'admin/finances/invoice', Finances_Invoice_Page_View_Model::class );

        $this->screen->display_invoice_page();

        self::assertInstanceOf( Finances_Invoice_Page_View_Model::class, $holder->vm );
        self::assertSame( 'PO-999', $holder->vm->invoice->invoice->get_purchase_order() );
        self::assertSame( 'PO-999', $holder->vm->purchase_order );
        self::assertSame( 'sent', $holder->vm->invoice->invoice->get_status() );
        self::assertSame( 'sent', $holder->vm->invoice_status );
        self::assertSame( 101, $holder->vm->invoice_id );
        self::assertSame( 'Invoice 1001 (sent)', $holder->vm->title );
        self::assertSame( '2026 League - Test Club', $holder->vm->subtitle );
        self::assertSame( 'club-invoices', $holder->vm->view );
        self::assertSame( 'Club invoices', $holder->vm->breadcrumb );
        self::assertTrue( $holder->vm->errors->has( 'status' ) );
    }
}
