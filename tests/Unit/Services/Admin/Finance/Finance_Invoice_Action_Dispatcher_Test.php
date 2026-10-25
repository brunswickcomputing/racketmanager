<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Finance;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Command;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Updated_Exception;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finance_Invoice_Action_Dispatcher_Test extends TestCase {

    private Finance_Service $finance_service;
    private Action_Guard_Interface $guard;
    private Action_Invoice_Handler $action_invoice_handler;
    private Finance_Invoice_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->finance_service        = $this->createMock( Finance_Service::class );
        $this->guard                  = $this->createMock( Action_Guard_Interface::class );
        $this->action_invoice_handler = new Action_Invoice_Handler( $this->finance_service );
        $this->dispatcher             = new Finance_Invoice_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            $this->action_invoice_handler
        );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );
        self::assertNull( $result->message );
    }

    public function test_bulk_invoices_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_invoices-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $this->finance_service->expects( self::never() )->method( 'action_invoice' );

        $result = $this->dispatcher->handle(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'paid',
                'invoice'          => array( 10 ),
            )
        );

        self::assertSame( 'Unauthorized', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'club-invoices' ), $result->query_params );
    }

    public function test_bulk_invoices_empty_selection_warning(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::never() )->method( 'action_invoice' );

        $result = $this->dispatcher->handle(
            array( 'view' => 'player-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'paid',
                'invoice'          => array(),
            )
        );

        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'No invoices selected', $result->message );
        self::assertSame( array( 'view' => 'player-invoices' ), $result->query_params );
    }

    public function test_bulk_invoices_status_change_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'action_invoice' )
            ->with( 25, 'paid' )
            ->willReturn( 'Invoice 25 status updated' );

        $result = $this->dispatcher->handle(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'paid',
                'invoice'          => array( '25' ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Invoice 25 status updated', $result->message );
    }

    public function test_bulk_invoices_invalid_argument_exception_gives_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'action_invoice' )
            ->willThrowException( new Invalid_Argument_Exception( 'Missing action parameter' ) );

        $result = $this->dispatcher->handle(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'invalid_action',
                'invoice'          => array( 25 ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Missing action parameter', $result->message );
    }

    public function test_bulk_invoices_not_found_exception_gives_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'action_invoice' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice not found' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doActionInvoices' => '1',
                'action'           => 'delete',
                'invoice'          => array( 99 ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Invoice not found', $result->message );
    }

    public function test_bulk_invoices_executes_injected_handler_with_command(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $handler = $this->createMock( Action_Invoice_Handler::class );
        $handler->expects( self::once() )
            ->method( 'handle' )
            ->with( self::callback( function ( Action_Invoice_Command $cmd ): bool {
                return 42 === $cmd->invoice_id && 'paid' === $cmd->action;
            } ) )
            ->willReturn( 'Invoice #42 marked as paid' );

        $dispatcher = new Finance_Invoice_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            action_invoice_handler: $handler
        );

        $result = $dispatcher->handle(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'paid',
                'invoice'          => array( 42 ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Invoice #42 marked as paid', $result->message );
    }

    public function test_bulk_invoices_multiple_mixed_outcomes_aggregates_messages_and_errors(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $handler = $this->createMock( Action_Invoice_Handler::class );
        $handler->expects( self::exactly( 2 ) )
            ->method( 'handle' )
            ->willReturnCallback( function ( Action_Invoice_Command $cmd ): string {
                if ( 10 === $cmd->invoice_id ) {
                    return 'Invoice 10 updated';
                }
                throw new Invoice_Not_Found_Exception( 'Invoice 20 missing' );
            } );

        $dispatcher = new Finance_Invoice_Action_Dispatcher(
            $this->finance_service,
            $this->guard,
            action_invoice_handler: $handler
        );

        $result = $dispatcher->handle(
            array( 'view' => 'club-invoices' ),
            array(
                'doActionInvoices' => '1',
                'action'           => 'sent',
                'invoice'          => array( 10, 20 ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Invoice 10 updated<br>Invoice 20 missing', $result->message );
    }

    public function test_save_invoice_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-invoice', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid nonce' ) );

        $this->finance_service->expects( self::never() )->method( 'amend_invoice' );

        $result = $this->dispatcher->handle(
            array( 'invoice' => '10' ),
            array(
                'saveInvoice' => '1',
                'invoice_id'  => '10',
            )
        );

        self::assertSame( 'Invalid nonce', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'invoice', 'invoice' => 10 ), $result->query_params );
    }

    public function test_save_invoice_empty_id_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::never() )->method( 'amend_invoice' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice' => '1',
                'invoice_id'  => '',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'club-invoices' ), $result->query_params );
    }

    public function test_save_invoice_success_with_blank_purchase_order_and_status(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $invoice = new Invoice();
        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->with( 10, 'sent', '' )
            ->willReturn( $invoice );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice'   => '1',
                'invoice_id'    => '10',
                'status'        => 'sent',
                'purchaseOrder' => '',
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'view' => 'invoice', 'invoice' => 10 ), $result->query_params );
    }

    public function test_save_invoice_without_status_returns_field_error_without_mutating_invoice(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::never() )->method( 'amend_invoice' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice'   => '1',
                'invoice_id'    => '10',
                'purchaseOrder' => '',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'invoice', 'invoice' => 10 ), $result->query_params );
        self::assertSame( 'Please select an invoice status', $result->flash_data['errors']['status'] );
        self::assertSame( array( 'invoice_id' => '10', 'purchaseOrder' => '' ), $result->flash_data['form_data'] );
    }

    public function test_save_invoice_wp_error_flashes_errors_and_form_data(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_error = new WP_Error( 'status', 'Error on invoice resend' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice'   => '1',
                'invoice_id'    => '10',
                'status'        => 'resend',
                'purchaseOrder' => 'PO-1234',
                'unwanted'      => 'ignore',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'invoice', 'invoice' => 10 ), $result->query_params );
        self::assertArrayHasKey( 'status', $result->flash_data['errors'] );
        self::assertSame( 'PO-1234', $result->flash_data['form_data']['purchaseOrder'] );
        self::assertArrayNotHasKey( 'unwanted', $result->flash_data['form_data'] );
    }

    public function test_save_invoice_not_found_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice not found' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice' => '1',
                'invoice_id'  => '10',
                'status'      => 'draft',
            )
        );

        self::assertSame( 'Invoice not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_invoice_not_updated_warning(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_invoice' )
            ->willThrowException( new Invoice_Not_Updated_Exception( 'No updates' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveInvoice' => '1',
                'invoice_id'  => '10',
                'status'      => 'draft',
            )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_constructor_requires_action_invoice_handler(): void {
        $ctor = new \ReflectionMethod( Finance_Invoice_Action_Dispatcher::class, '__construct' );
        $params = [];
        foreach ( $ctor->getParameters() as $param ) {
            $params[ $param->getName() ] = $param;
        }

        $this->assertArrayHasKey( 'action_invoice_handler', $params );
        $this->assertFalse( $params['action_invoice_handler']->isOptional(), 'action_invoice_handler must be required.' );
        $this->assertFalse( $params['action_invoice_handler']->allowsNull(), 'action_invoice_handler must not allow null.' );
        $this->assertSame( Action_Invoice_Handler::class, (string) $params['action_invoice_handler']->getType() );
    }
}
