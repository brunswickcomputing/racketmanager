<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Finances_Invoices_Admin_Controller;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finances_Invoices_Admin_Controller_Test extends TestCase {

    private Finance_Service $finance_service;
    private Club_Service $club_service;
    private Action_Guard_Interface $guard;
    private Finance_Invoice_Action_Dispatcher $dispatcher;
    private Finances_Invoices_Admin_Controller $controller;

    protected function setUp(): void {
        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->club_service    = $this->createMock( Club_Service::class );
        $this->guard           = $this->createMock( Action_Guard_Interface::class );

        $this->finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $this->dispatcher = new Finance_Invoice_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            new Action_Invoice_Handler( $this->finance_service )
        );

        $this->controller = new Finances_Invoices_Admin_Controller(
            $this->finance_service,
            $this->club_service,
            $this->dispatcher,
            $this->guard,
            new Invoice_Presenter()
        );
    }

    public function test_capability_check_fails(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->invoices_page( array(), array() );
    }

    public function test_get_club_invoices_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->with(
                array(
                    'entry'   => 'team',
                    'orderby' => array(
                        'season'         => 'DESC',
                        'competition_id' => 'ASC',
                    ),
                )
            )
            ->willReturn( array() );

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->with(
                array(
                    'type'   => 'club',
                    'status' => 'open',
                )
            )
            ->willReturn( array() );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $result = $this->controller->invoices_page( array(), array(), 'club-invoices' );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Invoices_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'club-invoices', $result['view_model']->racketmanager_tab );
        self::assertSame( 'Club Invoices', $result['view_model']->tab_title );
    }

    public function test_get_player_invoices_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->with(
                array(
                    'entry'   => 'player',
                    'orderby' => array(
                        'season'         => 'DESC',
                        'competition_id' => 'ASC',
                    ),
                )
            )
            ->willReturn( array() );

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->with(
                array(
                    'type'   => 'player',
                    'status' => 'open',
                )
            )
            ->willReturn( array() );

        $this->club_service->expects( self::never() )->method( 'get_clubs' );

        $result = $this->controller->invoices_page( array(), array(), 'player-invoices' );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Invoices_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'player-invoices', $result['view_model']->racketmanager_tab );
        self::assertSame( 'Player Invoices', $result['view_model']->tab_title );
    }

    public function test_get_club_invoices_passes_billable_status_and_charge_filters(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->with(
                array(
                    'entry'   => 'team',
                    'orderby' => array(
                        'season'         => 'DESC',
                        'competition_id' => 'ASC',
                    ),
                )
            )
            ->willReturn( array() );

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->with(
                array(
                    'type'     => 'club',
                    'billable' => 8,
                    'status'   => 'paid',
                    'charge'   => 15,
                )
            )
            ->willReturn( array() );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $result = $this->controller->invoices_page(
            array(
                'club'   => '8',
                'status' => 'paid',
                'charge' => '15',
            ),
            array(),
            'club-invoices'
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( 8, $result['view_model']->club_id );
        self::assertSame( 15, $result['view_model']->charge_id );
        self::assertSame( 'paid', $result['view_model']->status );
    }

    public function test_post_action_invoices_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_invoices-bulk', 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'action_invoice' )
            ->with( 101, 'delete' )
            ->willReturn( 'Invoices updated' );

        $result = $this->controller->invoices_page(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'delete',
                'invoice'          => array( 101 ),
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=club-invoices', $result['redirect'] );
        self::assertSame( 'Invoices updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
