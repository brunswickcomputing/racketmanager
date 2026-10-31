<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
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

    public function test_get_adapter_fallback_wires_repositories_and_option_service_from_container(): void {
        $container      = new Simple_Container();
        $option_service = $this->createStub( Option_Service::class );
        $message_repo   = $this->createStub( Message_Repository_Interface::class );
        $user_repo      = $this->createStub( User_Repository_Interface::class );

        $container->set( 'option_service', $option_service );
        $container->set( 'message_repository', $message_repo );
        $container->set( 'user_repository', $user_repo );

        $app            = $this->createStub( RacketManager::class );
        $app->container = $container;

        $controller = new Account_Ajax_Controller( $app );

        $ref = new \ReflectionMethod( $controller, 'get_adapter' );
        $ref->setAccessible( true );
        $adapter = $ref->invoke( $controller );

        $this->assertInstanceOf( Account_Ajax_Adapter::class, $adapter );

        $opt_prop = new \ReflectionProperty( Account_Ajax_Adapter::class, 'option_service' );
        $opt_prop->setAccessible( true );
        $this->assertSame( $option_service, $opt_prop->getValue( $adapter ) );

        $msg_prop = new \ReflectionProperty( Account_Ajax_Adapter::class, 'message_repository' );
        $msg_prop->setAccessible( true );
        $this->assertSame( $message_repo, $msg_prop->getValue( $adapter ) );

        $usr_prop = new \ReflectionProperty( Account_Ajax_Adapter::class, 'user_repository' );
        $usr_prop->setAccessible( true );
        $this->assertSame( $user_repo, $usr_prop->getValue( $adapter ) );
    }

    public function test_fallback_adapter_enforces_btm_requirement_when_option_service_configured(): void {
        $GLOBALS['wp_stubs_current_user_id'] = 5;

        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'verify_nonce' )->willReturn( true );

        $option_service = $this->createStub( Option_Service::class );
        $option_service->method( 'get_options' )
            ->willReturn( array( 'btm' => '1' ) );

        $user      = new User();
        $user->id  = 5;
        $user->ID  = 5;
        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );

        $container = new Simple_Container();
        $container->set( 'security_service', $security );
        $container->set( 'option_service', $option_service );
        $container->set( 'user_repository', $user_repo );

        $app            = $this->createStub( RacketManager::class );
        $app->container = $container;

        $_POST = array(
            'racketmanager_nonce' => 'valid_nonce',
            'username'            => 'player@example.test',
            'firstname'           => 'John',
            'lastname'            => 'Smith',
            'gender'              => 'M',
            'btm'                 => '',
        );

        $controller = new Account_Ajax_Controller( $app );

        $ref = new \ReflectionMethod( $controller, 'get_adapter' );
        $ref->setAccessible( true );
        $adapter = $ref->invoke( $controller );

        $request = array(
            'racketmanager_nonce' => 'valid_nonce',
            'username'            => 'player@example.test',
            'firstname'           => 'John',
            'lastname'            => 'Smith',
            'gender'              => 'M',
            'btm'                 => '',
        );

        $response = $adapter->update_account( $request );

        $this->assertSame( 401, $response->get_status_code() );
        $content = $response->get_content();
        $data    = is_array( $content ) && isset( $content['data'] ) ? $content['data'] : $content;
        $this->assertTrue( $data->error );
        $this->assertContains( 'btm', $data->err_flds );
    }
}
