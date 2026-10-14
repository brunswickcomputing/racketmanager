<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;

class Account_Ajax_Controller_Test extends TestCase {
    private array $original_get;
    private array $original_post;

    protected function setUp(): void {
        parent::setUp();
        $this->original_get  = $_GET;
        $this->original_post = $_POST;
        $_GET                = array();
        $_POST               = array();
    }

    protected function tearDown(): void {
        $_GET  = $this->original_get;
        $_POST = $this->original_post;
        parent::tearDown();
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = array( 'shared' => 'from_get', 'only_get' => 'get_val' );
        $_POST = array( 'shared' => 'from_post', 'only_post' => 'post_val' );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $request    = $controller->get_request();

        $this->assertSame( 'from_post', $request['shared'] );
        $this->assertSame( 'get_val', $request['only_get'] );
        $this->assertSame( 'post_val', $request['only_post'] );
    }

    public function test_login_passes_request_to_adapter(): void {
        $_GET  = array( 'get_key' => '1' );
        $_POST = array( 'security' => 'test_nonce', 'log' => 'john' );

        $adapter = $this->createMock( Account_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'login' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['security'] ?? '' ) === 'test_nonce'
                    && ( $req['log'] ?? '' ) === 'john'
                    && ( $req['get_key'] ?? '' ) === '1';
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->login();
    }

    public function test_add_favourite_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'test_nonce', 'type' => 'club', 'id' => 10 );

        $adapter = $this->createMock( Account_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'add_favourite' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['type'] ?? '' ) === 'club' && ( $req['id'] ?? 0 ) === 10;
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->add_favourite();
    }

    public function test_update_account_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'username' => 'test@example.com' );

        $adapter = $this->createMock( Account_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'update_account' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['username'] ?? '' ) === 'test@example.com';
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->update_account();
    }

    public function test_reset_password_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'user_login' => 'test@example.com' );

        $adapter = $this->createMock( Account_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'reset_password' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['user_login'] ?? '' ) === 'test@example.com';
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->reset_password();
    }
}
