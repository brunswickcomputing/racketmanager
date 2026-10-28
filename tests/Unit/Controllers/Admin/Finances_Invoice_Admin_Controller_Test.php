<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Finances_Invoice_Admin_Controller;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Domain\DTO\Finance\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finances_Invoice_Admin_Controller_Test extends TestCase {

    private Finance_Service $finance_service;
    private Action_Guard_Interface $guard;
    private Finance_Invoice_Action_Dispatcher $dispatcher;
    private Finances_Invoice_Admin_Controller $controller;

    protected function setUp(): void {
        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->guard           = $this->createMock( Action_Guard_Interface::class );

        $this->finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $this->dispatcher = new Finance_Invoice_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            new Action_Invoice_Handler( $this->finance_service )
        );

        $this->controller = new Finances_Invoice_Admin_Controller(
            $this->finance_service,
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

        $this->controller->invoice_page( array(), array() );
    }

    public function test_missing_invoice_params_throws_exception(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->expectException( Invoice_Not_Found_Exception::class );

        $this->controller->invoice_page( array(), array() );
    }

    public function test_get_invoice_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $charge_data                          = new stdClass();
        $charge_data->id                      = 10;
        $charge_data->competition_id          = 1;
        $charge_data->season                  = '2026';
        $charge_data->status                  = 'published';
        $charge_data->date                    = '2026-05-01';
        $charge_data->fee_competition         = '50.00';
        $charge_data->fee_event               = '20.00';
        $charge_data->competition_name        = 'League';
        $charge_data->competition_type        = 'league';
        $charge_dto                           = new Charge_Details_DTO( $charge_data );

        $invoice_entity                    = new Invoice();
        $invoice_entity->id                = 42;
        $invoice_entity->charge_id         = 10;
        $invoice_entity->billable_id       = 5;
        $invoice_entity->billable_type     = 'club';
        $invoice_entity->invoice_number    = 1001;
        $invoice_entity->status            = 'open';
        $invoice_entity->amount            = '70.00';
        $invoice_entity->date              = '2026-05-01';
        $invoice_entity->date_due          = '2026-06-01';
        $invoice_entity->payment_reference = 'REF123';
        $invoice_entity->purchase_order    = 'PO123';
        $invoice_entity->details           = new stdClass();

        $invoice_dto = new Invoice_Full_Details_DTO(
            $invoice_entity,
            'Test Club',
            '123 Tennis Lane',
            null,
            $charge_dto
        );

        $this->finance_service->expects( self::once() )
            ->method( 'get_full_invoice_details' )
            ->with( 42 )
            ->willReturn( $invoice_dto );

        $result = $this->controller->invoice_page( array( 'invoice' => 42 ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Invoice_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 42, $result['view_model']->invoice_id );
        self::assertSame( 'open', $result['view_model']->invoice_status );
    }

    public function test_get_invoice_by_charge_and_club_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $invoice_entity = $this->create_invoice_entity( 42 );
        $invoice_dto    = $this->create_invoice_dto( $invoice_entity );

        $this->finance_service->expects( self::once() )
            ->method( 'get_invoice_by_charge_and_club' )
            ->with( 10, 5 )
            ->willReturn( $invoice_entity );

        $this->finance_service->expects( self::once() )
            ->method( 'get_full_invoice_details' )
            ->with( 42 )
            ->willReturn( $invoice_dto );

        $result = $this->controller->invoice_page(
            array(
                'charge' => 10,
                'club'   => 5,
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Finances_Invoice_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 42, $result['view_model']->invoice_id );
    }

    public function test_post_save_invoice_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-invoice', 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->with( 42, 'paid', '' )
            ->willReturn( new Invoice() );

        $result = $this->controller->invoice_page(
            array( 'view' => 'invoice' ),
            array(
                'saveInvoice' => '1',
                'invoice_id'  => '42',
                'status'      => 'paid',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=invoice', $result['redirect'] );
        self::assertStringContainsString( 'invoice=42', $result['redirect'] );
        self::assertSame( 'Invoice updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_save_invoice_validation_failure_returns_flash_data(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-invoice', 'edit_leagues' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->willReturn( new WP_Error( 'status', 'Error on invoice resend' ) );

        $result = $this->controller->invoice_page(
            array( 'view' => 'invoice', 'invoice' => 42 ),
            array(
                'saveInvoice'   => '1',
                'invoice_id'    => '42',
                'status'        => 'resend',
                'purchaseOrder' => 'PO-1234',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=invoice', $result['redirect'] );
        self::assertStringContainsString( 'invoice=42', $result['redirect'] );
        self::assertSame( 'Error updating invoice', $result['message'] );
        self::assertTrue( $result['message_type'] );
        self::assertArrayHasKey( 'flash_data', $result );
        self::assertSame( 'Error on invoice resend', $result['flash_data']['errors']['status'] );
        self::assertSame( 'PO-1234', $result['flash_data']['form_data']['purchaseOrder'] );
        self::assertSame( 'resend', $result['flash_data']['form_data']['status'] );
    }

    private function create_invoice_entity( int $id = 42 ): Invoice {
        $invoice_entity                    = new Invoice();
        $invoice_entity->id                = $id;
        $invoice_entity->charge_id         = 10;
        $invoice_entity->billable_id       = 5;
        $invoice_entity->billable_type     = 'club';
        $invoice_entity->invoice_number    = 1001;
        $invoice_entity->status            = 'open';
        $invoice_entity->amount            = '70.00';
        $invoice_entity->date              = '2026-05-01';
        $invoice_entity->date_due          = '2026-06-01';
        $invoice_entity->payment_reference = 'REF123';
        $invoice_entity->purchase_order    = 'PO123';
        $invoice_entity->details           = new stdClass();

        return $invoice_entity;
    }

    private function create_invoice_dto( Invoice $invoice_entity ): Invoice_Full_Details_DTO {
        $charge_data                          = new stdClass();
        $charge_data->id                      = 10;
        $charge_data->competition_id          = 1;
        $charge_data->season                  = '2026';
        $charge_data->status                  = 'published';
        $charge_data->date                    = '2026-05-01';
        $charge_data->fee_competition         = '50.00';
        $charge_data->fee_event               = '20.00';
        $charge_data->competition_name        = 'League';
        $charge_data->competition_type        = 'league';

        return new Invoice_Full_Details_DTO(
            $invoice_entity,
            'Test Club',
            '123 Tennis Lane',
            null,
            new Charge_Details_DTO( $charge_data )
        );
    }
}
