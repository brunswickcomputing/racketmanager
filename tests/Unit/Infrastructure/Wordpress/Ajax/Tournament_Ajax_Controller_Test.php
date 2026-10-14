<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Tournament_Ajax_Controller_Test extends TestCase {
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

        $controller = new Tournament_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $request    = $controller->get_request();

        $this->assertSame( 'from_post', $request['shared'] );
        $this->assertSame( 'get_val', $request['only_get'] );
        $this->assertSame( 'post_val', $request['only_post'] );
    }

    public function test_tournament_payment_create_passes_request_to_adapter(): void {
        $_POST = array( 'tournament_id' => 8, 'playerId' => 3 );

        $adapter = $this->createMock( Tournament_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'tournament_payment_create' )
            ->with( $this->callback( fn( array $req ) => ( $req['tournament_id'] ?? 0 ) === 8 ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Tournament_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->tournament_payment_create();
    }

    public function test_tournament_entry_request_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'nonce', 'tournament_id' => 8, 'playerId' => 3 );
        $request = $_POST;

        $adapter = $this->createMock( Tournament_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'tournament_entry_request' )
            ->with( $request )
            ->willReturn( new Response( array( 'msg' => 'entry' ), 200 ) );

        $controller = new Tournament_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"entry"}' );
        $controller->tournament_entry_request();
    }

    public function test_confirm_tournament_withdrawal_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'nonce', 'tournament_id' => 8, 'playerId' => 3 );
        $request = $_POST;

        $adapter = $this->createMock( Tournament_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'confirm_tournament_withdrawal' )
            ->with( $request )
            ->willReturn( new Response( array( 'msg' => 'withdrawal' ), 200 ) );

        $controller = new Tournament_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"withdrawal"}' );
        $controller->confirm_tournament_withdrawal();
    }
}
