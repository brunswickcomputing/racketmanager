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
use Racketmanager\Domain\Invoice;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Finances_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Finance_Service $finance_service;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Admin_Finances $screen;
    private array $original_superglobals;
    private array $original_globals;

    protected function setUp(): void {
        parent::setUp();
        $this->original_superglobals = array(
            'get'                 => $_GET,
            'post'                => $_POST,
            'request_method_set'  => array_key_exists( 'REQUEST_METHOD', $_SERVER ),
            'request_method'      => $_SERVER['REQUEST_METHOD'] ?? null,
        );
        $this->original_globals     = array(
            'racketmanager_set' => array_key_exists( 'racketmanager', $GLOBALS ),
            'racketmanager'     => $GLOBALS['racketmanager'] ?? null,
            'capability_set'    => array_key_exists( 'wp_stubs_current_user_can', $GLOBALS ),
            'capability'        => $GLOBALS['wp_stubs_current_user_can'] ?? null,
        );
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET  = array();
        $_POST = array();

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer            = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->finance_service     = $this->createMock( Finance_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'finance_service', fn() => $this->finance_service );
        $this->container->set( 'competition_service', fn() => $this->competition_service );
        $this->container->set( 'season_service', fn() => $this->season_service );
        $this->container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $this->container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $this->container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $this->container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $this->container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );
        $this->container->set( 'admin_action_adapter', fn() => new Admin_Action_Adapter( $message_service, $this->redirector ) );
        $this->container->set( 'admin_screen_presenter', fn() => new Admin_Screen_Presenter( $this->renderer, $message_service ) );
        $this->container->set( 'admin_message_service', fn() => $message_service );

        $this->container->set( 'league_contact_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher( $this->createStub( League_Service::class ), $this->competition_service, $this->guard ) );
        $this->container->set( 'league_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher( $this->competition_service, $this->guard ) );
        $this->container->set( 'event_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher( $this->competition_service, $this->guard ) );
        $this->container->set( 'season_delete_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher( $this->competition_service, $this->guard ) );
        $this->container->set( 'league_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_match_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ), $this->guard ) );
        $this->container->set( 'league_constitution_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher( $this->guard ) );
        $this->container->set( 'league_event_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_schedule_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher( new \Racketmanager\Services\Admin\League\League_Schedule_Service( $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ) ), $this->guard ) );
        $this->container->set( 'club_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_player_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher( $this->createStub( Registration_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Team_Service::class ), $this->guard ) );
        $this->container->set( 'club_role_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->guard ) );
        $this->container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->season_service, $this->guard ) );

        $this->container->set( 'finance_charge_action_dispatcher', fn() => new Finance_Charge_Action_Dispatcher( $this->finance_service, $this->guard ) );
        $this->container->set( 'finance_invoice_action_dispatcher', fn() => new Finance_Invoice_Action_Dispatcher( $this->finance_service, $this->guard ) );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Finances( $racketmanager );
    }

    protected function tearDown(): void {
        $_GET  = $this->original_superglobals['get'];
        $_POST = $this->original_superglobals['post'];
        if ( $this->original_superglobals['request_method_set'] ) {
            $_SERVER['REQUEST_METHOD'] = $this->original_superglobals['request_method'];
        } else {
            unset( $_SERVER['REQUEST_METHOD'] );
        }

        if ( $this->original_globals['racketmanager_set'] ) {
            $GLOBALS['racketmanager'] = $this->original_globals['racketmanager'];
        } else {
            unset( $GLOBALS['racketmanager'] );
        }
        if ( $this->original_globals['capability_set'] ) {
            $GLOBALS['wp_stubs_current_user_can'] = $this->original_globals['capability'];
        } else {
            unset( $GLOBALS['wp_stubs_current_user_can'] );
        }

        parent::tearDown();
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
        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->willReturn( array() );
        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );
        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/finances/show-charges',
                self::callback(
                    function( $data ) use ( &$captured_vm ) {
                        $captured_vm = $data;
                        return $captured_vm instanceof Finances_Charges_Page_View_Model;
                    }
                )
            );

        $this->screen->display_charges_page();

        self::assertInstanceOf( Finances_Charges_Page_View_Model::class, $captured_vm );
    }

    public function test_display_club_invoices_page_renders_get_view_model(): void {
        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->willReturn( array() );
        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->willReturn( array() );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/finances/show-invoices',
                self::callback(
                    function( $data ) use ( &$captured_vm ) {
                        $captured_vm = $data;
                        return $captured_vm instanceof Finances_Invoices_Page_View_Model;
                    }
                )
            );

        $this->screen->display_club_invoices_page();

        self::assertInstanceOf( Finances_Invoices_Page_View_Model::class, $captured_vm );
        self::assertSame( 'club-invoices', $captured_vm->racketmanager_tab );
    }

    public function test_display_charge_page_renders_add_mode_view_model(): void {
        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );
        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/finances/charge',
                self::callback(
                    function( $data ) use ( &$captured_vm ) {
                        $captured_vm = $data;
                        return $captured_vm instanceof Finances_Charge_Page_View_Model;
                    }
                )
            );

        $this->screen->display_charge_page();

        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $captured_vm );
        self::assertFalse( $captured_vm->edit );
    }

    public function test_display_charge_page_rehydrates_flash_form_data_with_float_fees_without_type_error(): void {
        $_GET['charges'] = '10';

        $raw_charge = new stdClass();
        $raw_charge->id = 10;
        $raw_charge->competition_id = 5;
        $raw_charge->season = '2026';
        $raw_charge->status = 'draft';
        $raw_charge->date = '2026-05-01';
        $raw_charge->fee_competition = 50;
        $raw_charge->fee_event = 20;
        $raw_charge->competition_name = 'Summer League';
        $raw_charge->competition_type = 'league';

        $charge_dto = new Charge_Details_DTO( $raw_charge );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charge_with_details' )
            ->with( 10 )
            ->willReturn( $charge_dto );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );
        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn(
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

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/finances/charge',
                self::callback(
                    function( $data ) use ( &$captured_vm ) {
                        $captured_vm = $data;
                        return $captured_vm instanceof Finances_Charge_Page_View_Model;
                    }
                )
            );

        $this->screen->display_charge_page();

        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $captured_vm );
        self::assertTrue( $captured_vm->edit );
        self::assertSame( 45.75, $captured_vm->charge->fee_competition );
        self::assertSame( 18.50, $captured_vm->charge->fee_event );
        self::assertSame( 'final', $captured_vm->charge->status );
        self::assertTrue( $captured_vm->errors->has( 'feeClub' ) );
    }

    public function test_display_invoice_page_renders_get_view_model_and_rehydrates_po(): void {
        $_GET['invoice'] = '101';

        $invoice = new Invoice();
        $invoice->id = 101;
        $invoice->charge_id = 5;
        $invoice->billable_id = 12;
        $invoice->billable_type = 'club';
        $invoice->invoice_number = 1001;
        $invoice->date = '2026-06-01';
        $invoice->date_due = '2026-07-01';
        $invoice->status = 'draft';
        $invoice->amount = '100';
        $invoice->details = new stdClass();

        $raw_charge = new stdClass();
        $raw_charge->id = 5;
        $raw_charge->competition_id = 2;
        $raw_charge->season = '2026';
        $raw_charge->status = 'final';
        $raw_charge->date = '2026-05-01';
        $raw_charge->fee_competition = 50;
        $raw_charge->fee_event = 20;
        $raw_charge->competition_name = '2026 League';
        $raw_charge->competition_type = 'league';
        $charge_dto = new Charge_Details_DTO( $raw_charge );

        $invoice_dto = new Invoice_Full_Details_DTO( $invoice, 'Test Club', 'Test Address', null, $charge_dto );

        $this->finance_service->expects( self::once() )
            ->method( 'get_full_invoice_details' )
            ->with( 101 )
            ->willReturn( $invoice_dto );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn(
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

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/finances/invoice',
                self::callback(
                    function( $data ) use ( &$captured_vm ) {
                        $captured_vm = $data;
                        return $captured_vm instanceof Finances_Invoice_Page_View_Model;
                    }
                )
            );

        $this->screen->display_invoice_page();

        self::assertInstanceOf( Finances_Invoice_Page_View_Model::class, $captured_vm );
        self::assertSame( 'PO-999', $captured_vm->invoice->invoice->get_purchase_order() );
        self::assertSame( 'sent', $captured_vm->invoice->invoice->get_status() );
        self::assertTrue( $captured_vm->errors->has( 'status' ) );
    }
}
