<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Finance_Ajax_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET  = array();
        $_POST = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = array( 'shared' => 'from_get', 'only_get' => 'get_val' );
        $_POST = array( 'shared' => 'from_post', 'only_post' => 'post_val' );

        $controller = new Finance_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $request    = $controller->get_request();

        $this->assertSame( 'from_post', $request['shared'] );
        $this->assertSame( 'get_val', $request['only_get'] );
        $this->assertSame( 'post_val', $request['only_post'] );
    }

    public function test_show_purchase_order_modal_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'test_nonce', 'invoiceId' => 42, 'modal' => 'poModal' );

        $adapter = $this->createMock( Finance_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'show_purchase_order_modal' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['invoiceId'] ?? 0 ) === 42 && ( $req['modal'] ?? '' ) === 'poModal';
            } ) )
            ->willReturn( new Response( '<div>po</div>', 200 ) );

        $controller = new Finance_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '<div>po</div>' );
        $controller->show_purchase_order_modal();
    }

    public function test_set_purchase_order_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'invoiceId' => 42, 'purchaseOrder' => 'PO-123' );

        $adapter = $this->createMock( Finance_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'set_purchase_order' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['invoiceId'] ?? 0 ) === 42 && ( $req['purchaseOrder'] ?? '' ) === 'PO-123';
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Finance_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->set_purchase_order();
    }
}
