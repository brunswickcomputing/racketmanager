<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Finances_Charge_Admin_Controller;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler;
use Racketmanager\Domain\DTO\Finance\Charge_Details_DTO;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finances_Charge_Admin_Controller_Test extends TestCase {

    private Finance_Service $finance_service;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Action_Guard_Interface $guard;
    private Finance_Charge_Action_Dispatcher $dispatcher;
    private Finances_Charge_Admin_Controller $controller;

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

        $this->controller = new Finances_Charge_Admin_Controller(
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
            ->with( 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->charge_page( array(), array() );
    }

    public function test_get_add_charge_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $result = $this->controller->charge_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $result['view_model'] );
        self::assertFalse( $result['view_model']->edit );
        self::assertSame( 'Add Charge', $result['view_model']->form_title );
    }

    public function test_get_edit_charge_with_flash_restoration_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );

        $raw_charge                          = new stdClass();
        $raw_charge->id                      = 12;
        $raw_charge->competition_id          = 4;
        $raw_charge->season                  = '2025';
        $raw_charge->status                  = 'draft';
        $raw_charge->date                    = '2025-01-01';
        $raw_charge->fee_competition         = 40.0;
        $raw_charge->fee_event               = 15.0;
        $raw_charge->competition_name        = 'Winter Cup';
        $raw_charge->competition_type        = 'cup';
        $raw_charge->competition_is_team_entry = true;
        $charge_dto                          = new Charge_Details_DTO( $raw_charge );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charge_with_details' )
            ->with( 12 )
            ->willReturn( $charge_dto );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_for_clubs' )
            ->with( 12 )
            ->willReturn( array() );

        $this->competition_service->expects( self::once() )
            ->method( 'get_all' )
            ->willReturn( array() );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $flash_data = array(
            'form_data' => array(
                'status'  => 'published',
                'feeClub' => '55.00',
            ),
        );

        $result = $this->controller->charge_page( array( 'charges' => 12 ), array(), $flash_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Charge_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'Edit Charge', $result['view_model']->form_title );
        self::assertSame( 'published', $result['view_model']->charge->status );
        self::assertSame( 55.0, $result['view_model']->charge->fee_competition );
    }

    public function test_post_save_charge_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'add_charge' )
            ->willReturn( 12 );

        $result = $this->controller->charge_page(
            array( 'view' => 'charge' ),
            array(
                'saveCharge'     => '1',
                'addCharge'      => '1',
                'competition_id' => '1',
                'season'         => '2026',
                'status'         => 'draft',
                'date'           => '2026-05-01',
                'feeClub'        => '50',
                'feeTeam'        => '20',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charge', $result['redirect'] );
        self::assertStringContainsString( 'charges=12', $result['redirect'] );
        self::assertSame( 'Charge added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_edit_charge_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->willReturn( 12 );

        $result = $this->controller->charge_page(
            array( 'view' => 'charge', 'charges' => 12 ),
            array(
                'saveCharge'     => '1',
                'editCharge'     => '1',
                'charges_id'     => '12',
                'competition_id' => '1',
                'season'         => '2026',
                'status'         => 'published',
                'date'           => '2026-05-01',
                'feeClub'        => '55',
                'feeTeam'        => '25',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charge', $result['redirect'] );
        self::assertStringContainsString( 'charges=12', $result['redirect'] );
        self::assertSame( 'Charge updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_generate_invoices_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'send_invoices_for_charge' )
            ->with( 12, true )
            ->willReturn( true );

        $result = $this->controller->charge_page(
            array( 'view' => 'charge' ),
            array(
                'generateInvoices' => '1',
                'charge_id'        => '12',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charge', $result['redirect'] );
        self::assertStringContainsString( 'charges=12', $result['redirect'] );
        self::assertSame( 'Invoices generated and sent', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_get_charge_not_found_propagates(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charge_with_details' )
            ->with( 99 )
            ->willThrowException( new Charge_Not_Found_Exception( 'Charge not found' ) );

        $this->expectException( Charge_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Charge not found' );

        $this->controller->charge_page( array( 'charges' => 99 ), array() );
    }

    public function test_post_add_charge_validation_failure_returns_flash_data(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'add_charge' )
            ->willReturn( new WP_Error( 'competition', 'Competition is required' ) );

        $result = $this->controller->charge_page(
            array( 'view' => 'charge' ),
            array(
                'saveCharge'     => '1',
                'addCharge'      => '1',
                'competition_id' => '',
                'season'         => '2026',
                'status'         => 'draft',
                'feeClub'        => '25.00',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charge', $result['redirect'] );
        self::assertSame( 'Error adding charge', $result['message'] );
        self::assertTrue( $result['message_type'] );
        self::assertArrayHasKey( 'flash_data', $result );
        self::assertSame( 'Competition is required', $result['flash_data']['errors']['competition'] );
        self::assertSame( '2026', $result['flash_data']['form_data']['season'] );
        self::assertSame( 'draft', $result['flash_data']['form_data']['status'] );
    }

    public function test_post_edit_charge_validation_failure_returns_flash_data(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->willReturn( new WP_Error( 'date', 'Invalid date format' ) );

        $result = $this->controller->charge_page(
            array( 'view' => 'charge', 'charges' => 12 ),
            array(
                'saveCharge' => '1',
                'editCharge' => '1',
                'charges_id' => '12',
                'date'       => 'invalid',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=charge', $result['redirect'] );
        self::assertStringContainsString( 'charges=12', $result['redirect'] );
        self::assertSame( 'Error updating charge', $result['message'] );
        self::assertTrue( $result['message_type'] );
        self::assertArrayHasKey( 'flash_data', $result );
        self::assertSame( 'Invalid date format', $result['flash_data']['errors']['date'] );
        self::assertSame( '12', $result['flash_data']['form_data']['charges_id'] );
        self::assertSame( 'invalid', $result['flash_data']['form_data']['date'] );
    }
}
