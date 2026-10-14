<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Validator\Validator_Finance;

class Finance_Ajax_Adapter_Test extends TestCase {
    private Stub|Security_Service_Interface $security_service;
    private Finance_Service|MockObject|Stub $finance_service;
    private Finance_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service = $this->createStub( Security_Service_Interface::class );
        $this->finance_service  = $this->createStub( Finance_Service::class );
        $this->adapter          = $this->make_adapter();
        $_POST                  = array();
        $_REQUEST               = array();
    }

    private function make_adapter(): Finance_Ajax_Adapter {
        $response_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static function ( $data, $status = null ) {
                return new Response( $data, $status ?? 200 );
            }
        );
        $response_factory->method( 'create_error_response' )->willReturnCallback(
            static function ( $data, $status = null ) {
                return new Response( $data, $status ?? 400 );
            }
        );
        $response_factory->method( 'create_raw_response' )->willReturnCallback(
            static function ( $content, $status = null ) {
                return new Response( $content, $status ?? 200 );
            }
        );

        return new Finance_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $this->finance_service
        );
    }

    public function test_register_hooks_uses_single_priv_and_nopriv_ownership(): void {
        $controller = new Finance_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = array();
        $controller->register_hooks( $registry );

        $this->assertSame( array( $controller, 'show_purchase_order_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_purchase_order_modal'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_purchase_order_modal'][0] );
        $this->assertSame( array( $controller, 'set_purchase_order' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_set_purchase_order'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_set_purchase_order'][0] );
        $this->assertCount( 1, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_purchase_order_modal'] );
        $this->assertCount( 1, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_set_purchase_order'] );
    }

    public function test_logged_out_modal_returns_401_with_alert(): void {
        $response = $this->adapter->logged_out_modal();
        $this->assertSame( 401, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertIsArray( $data );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
    }

    public function test_show_purchase_order_modal_success_returns_raw_html(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $request  = array( 'security' => 'valid', 'invoiceId' => 42, 'modal' => 'poModal' );
        $response = $this->adapter->show_purchase_order_modal( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'purchase-order-modal:42', $response->get_content() );
    }

    public function test_show_purchase_order_modal_nonce_failure_returns_alert_html_with_403(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'ajax-nonce' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $request  = array( 'security' => 'bad', 'invoiceId' => 42 );
        $response = $this->adapter->show_purchase_order_modal( $request );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
        $this->assertStringContainsString( 'alert', $response->get_content() );
    }

    public function test_set_purchase_order_rejects_invalid_nonce(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'purchase-order-update' )
            ->willReturn( false );

        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->finance_service->expects( $this->never() )->method( 'set_purchase_order_for_invoice' );
        $this->adapter = $this->make_adapter();

        $request  = array( 'racketmanager_nonce' => 'bad', 'invoiceId' => 42, 'purchaseOrder' => 'PO-100', 'modal' => 'poModal' );
        $response = $this->adapter->set_purchase_order( $request );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertTrue( $response->get_content()->error );
    }

    public function test_set_purchase_order_modal_validation_error(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'purchase-order-update' )
            ->willReturn( true );

        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->finance_service->expects( $this->never() )->method( 'set_purchase_order_for_invoice' );
        $this->adapter = $this->make_adapter();

        $request  = array( 'racketmanager_nonce' => 'valid', 'invoiceId' => 42, 'purchaseOrder' => 'PO-100', 'modal' => '' );
        $response = $this->adapter->set_purchase_order( $request );
        $this->assertTrue( $response->get_content()->error );
    }

    public function test_set_purchase_order_handles_service_exception(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'purchase-order-update' )
            ->willReturn( true );

        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->finance_service->expects( $this->once() )
            ->method( 'set_purchase_order_for_invoice' )
            ->with( 999, 'PO-999' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice not found' ) );
        $this->adapter = $this->make_adapter();

        $request  = array( 'racketmanager_nonce' => 'valid', 'invoiceId' => 999, 'purchaseOrder' => 'PO-999', 'modal' => 'poModal' );
        $response = $this->adapter->set_purchase_order( $request );
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertSame( 'Invoice not found', $response->get_content()->msg );
    }

    public function test_set_purchase_order_success_updates_invoice(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'purchase-order-update' )
            ->willReturn( true );

        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->finance_service->expects( $this->once() )
            ->method( 'set_purchase_order_for_invoice' )
            ->with( 42, 'PO-42' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $request  = array( 'racketmanager_nonce' => 'valid', 'invoiceId' => 42, 'purchaseOrder' => 'PO-42', 'modal' => 'poModal' );
        $response = $this->adapter->set_purchase_order( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Purchase order updated', $content->msg );
        $this->assertSame( 'poModal', $content->modal );
        $this->assertSame( 'invoice:42', $content->invoice );
    }
}
