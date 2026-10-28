<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Finances_Charges_Admin_Controller;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finances_Charges_Admin_Controller_Test extends TestCase {

    private Finance_Service $finance_service;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Action_Guard_Interface $guard;
    private Finance_Charge_Action_Dispatcher $dispatcher;
    private Finances_Charges_Admin_Controller $controller;

    protected function setUp(): void {
        $this->finance_service     = $this->createMock( Finance_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );

        $this->finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $this->dispatcher = new Finance_Charge_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            new Generate_Charge_Invoices_Handler( $this->finance_service )
        );

        $this->controller = new Finances_Charges_Admin_Controller(
            $this->finance_service,
            $this->competition_service,
            $this->season_service,
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

        $this->controller->charges_page( array(), array() );
    }

    public function test_get_charges_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->with( array() )
            ->willReturn( array() );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $result = $this->controller->charges_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Charges_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'charges', $result['view_model']->racketmanager_tab );
    }

    public function test_get_charges_passes_competition_and_season_filters(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->with(
                array(
                    'competition' => 7,
                    'season'      => 2026,
                )
            )
            ->willReturn( array() );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $result = $this->controller->charges_page(
            array(
                'competition' => '7',
                'season'      => '2026',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( 7, $result['view_model']->competition_id );
        self::assertSame( '2026', $result['view_model']->season );
    }

    public function test_post_delete_charges_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'del_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'remove_charge' )
            ->with( 10 )
            ->willReturn( 1 );

        $result = $this->controller->charges_page(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array( 10 ),
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charges', $result['redirect'] );
        self::assertSame( 'Charge 10 deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
