<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Account_Ajax_Controller_Test extends TestCase {
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

    private function assert_controller_delegates(
        string $adapter_method,
        callable $callback,
        Response $response,
        string $expected_output,
        string $controller_method
    ): void {
        $adapter = $this->createMock( Account_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( $adapter_method )
            ->with( $this->callback( $callback ) )
            ->willReturn( $response );

        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( $expected_output );
        $controller->$controller_method();
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

        $this->assert_controller_delegates(
            'login',
            static fn( array $req ): bool => ( $req['security'] ?? '' ) === 'test_nonce'
                && ( $req['log'] ?? '' ) === 'john'
                && ( $req['get_key'] ?? '' ) === '1',
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'login'
        );
    }

    public function test_add_favourite_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'test_nonce', 'type' => 'club', 'id' => 10 );

        $this->assert_controller_delegates(
            'add_favourite',
            static fn( array $req ): bool => ( $req['type'] ?? '' ) === 'club' && ( $req['id'] ?? 0 ) === 10,
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'add_favourite'
        );
    }

    public function test_update_account_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'username' => 'test@example.com' );

        $this->assert_controller_delegates(
            'update_account',
            static fn( array $req ): bool => ( $req['username'] ?? '' ) === 'test@example.com',
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'update_account'
        );
    }

    public function test_reset_password_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'user_login' => 'test@example.com' );

        $this->assert_controller_delegates(
            'reset_password',
            static fn( array $req ): bool => ( $req['user_login'] ?? '' ) === 'test@example.com',
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'reset_password'
        );
    }
}
