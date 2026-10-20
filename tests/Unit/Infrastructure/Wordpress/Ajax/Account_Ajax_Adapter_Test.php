<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Message;
use Racketmanager\Domain\User;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use stdClass;
use WP_Error;

class Account_Ajax_Adapter_Test extends TestCase {
    private MockObject|Stub|Security_Service_Interface $security_service;
    private Account_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service = $this->createStub( Security_Service_Interface::class );
        $this->adapter          = $this->make_adapter();
        $_POST                  = array();
        $_REQUEST               = array();
        unset( $GLOBALS['wp_stubs_users'], $GLOBALS['wp_stubs_messages'], $GLOBALS['wp_stubs_user_meta'], $GLOBALS['wp_stubs_current_user_id'], $GLOBALS['wp_stubs_get_current_user_id'], $GLOBALS['wp_stubs_last_signon_args'], $GLOBALS['wp_stubs_signon_error'] );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_users'], $GLOBALS['wp_stubs_messages'], $GLOBALS['wp_stubs_user_meta'], $GLOBALS['wp_stubs_current_user_id'], $GLOBALS['wp_stubs_get_current_user_id'], $GLOBALS['wp_stubs_last_signon_args'], $GLOBALS['wp_stubs_signon_error'] );
        parent::tearDown();
    }

    private function make_adapter( ?Message_Repository_Interface $message_repo = null, ?User_Repository_Interface $user_repo = null ): Account_Ajax_Adapter {
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

        return new Account_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $message_repo,
            $user_repo
        );
    }

    public function test_register_hooks_uses_single_priv_and_nopriv_ownership(): void {
        $controller = new Account_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = array();
        $controller->register_hooks( $registry );

        // add_favourite: priv only
        $this->assertSame( array( $controller, 'add_favourite' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_add_favourite'][0] );
        $this->assertArrayNotHasKey( 'wp_ajax_nopriv_racketmanager_add_favourite', $GLOBALS['wp_action_hooks'] );

        // get_message: priv + nopriv logged_out
        $this->assertSame( array( $controller, 'get_user_message' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_message'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_get_message'][0] );

        // delete_message: priv + nopriv logged_out
        $this->assertSame( array( $controller, 'delete_message' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_delete_message'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_delete_message'][0] );

        // delete_messages: priv only
        $this->assertSame( array( $controller, 'delete_messages' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_delete_messages'][0] );
        $this->assertArrayNotHasKey( 'wp_ajax_nopriv_racketmanager_delete_messages', $GLOBALS['wp_action_hooks'] );

        // deletes_message (typo alias): nopriv only -> logged_out
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_deletes_message'][0] );
        $this->assertArrayNotHasKey( 'wp_ajax_racketmanager_deletes_message', $GLOBALS['wp_action_hooks'] );

        // reset_password: nopriv only
        $this->assertSame( array( $controller, 'reset_password' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_reset_password'][0] );
        $this->assertArrayNotHasKey( 'wp_ajax_racketmanager_reset_password', $GLOBALS['wp_action_hooks'] );

        // login: nopriv only
        $this->assertSame( array( $controller, 'login' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_login'][0] );
        $this->assertArrayNotHasKey( 'wp_ajax_racketmanager_login', $GLOBALS['wp_action_hooks'] );

        // update_account: priv -> update_account, nopriv -> login
        $this->assertSame( array( $controller, 'update_account' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_update_account'][0] );
        $this->assertSame( array( $controller, 'login' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_update_account'][0] );
    }

    public function test_logged_out_returns_401_with_array(): void {
        $response = $this->adapter->logged_out();
        $this->assertSame( 401, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertIsArray( $data );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
    }

    public function test_login_rejects_invalid_nonce(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'ajax-nonce' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $request  = array( 'security' => 'bad', 'log' => 'user', 'pwd' => 'pass' );
        $response = $this->adapter->login( $request );
        $this->assertSame( 403, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->error );
        $this->assertSame( 'Login failed', $content->msg );
    }

    public function test_login_failure_returns_401_with_error_fields(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $request = array( 'security' => 'valid', 'log' => 'baduser', 'pwd' => 'wrongpass' );
        $GLOBALS['wp_stubs_signon_error'] = new WP_Error( 'invalid_username', 'Invalid username' );

        $response = $this->adapter->login( $request );
        $this->assertSame( 401, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->error );
        $this->assertSame( 'Login failed', $content->msg );
        unset( $GLOBALS['wp_stubs_signon_error'] );
    }

    public function test_login_success_returns_redirect_url_and_passes_signon_args(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $request = array( 'security' => 'valid', 'log' => 'gooduser', 'pwd' => 'goodpass', 'redirect_to' => 'https://example.test/dashboard' );

        $response = $this->adapter->login( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'https://example.test/dashboard', $response->get_content() );

        $this->assertNotEmpty( $GLOBALS['wp_stubs_last_signon_args'] );
        $this->assertSame( 'gooduser', $GLOBALS['wp_stubs_last_signon_args']['user_login'] );
        $this->assertSame( 'goodpass', $GLOBALS['wp_stubs_last_signon_args']['user_password'] );
        $this->assertTrue( $GLOBALS['wp_stubs_last_signon_args']['remember'] );
    }

    public function test_add_favourite_rejects_invalid_nonce(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'ajax-nonce' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $request  = array( 'security' => 'bad', 'type' => 'club', 'id' => 5 );
        $response = $this->adapter->add_favourite( $request );
        $this->assertSame( 500, $response->get_status_code() );
    }

    public function test_add_favourite_adds_and_removes_user_meta(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->exactly( 2 ) )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $request = array( 'security' => 'valid', 'type' => 'club', 'id' => 15 );
        $GLOBALS['wp_stubs_current_user_id'] = 7;

        // First call: adds favourite
        $response = $this->adapter->add_favourite( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'add', $content->action );
        $this->assertSame( 'Favourite added', $content->msg );

        // Second call with same id: removes favourite
        $response2 = $this->adapter->add_favourite( $request );
        $this->assertSame( 200, $response2->get_status_code() );
        $content2 = $response2->get_content();
        $this->assertSame( 'del', $content2->action );
        $this->assertSame( 'Favourite removed', $content2->msg );
    }

    public function test_get_user_message_success_and_nonce_failure(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'ajax-nonce' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $bad = $this->adapter->get_user_message( array( 'security' => 'bad', 'message_id' => 10 ) );
        $this->assertSame( 403, $bad->get_status_code() );

        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();
        $GLOBALS['wp_stubs_messages'][10] = (object) array( 'status' => 'unread' );

        $good = $this->adapter->get_user_message( array( 'security' => 'valid', 'message_id' => 10 ) );
        $this->assertSame( 200, $good->get_status_code() );
        $content = $good->get_content();
        $this->assertSame( 'unread', $content->status );
        $this->assertSame( 'message-content:10', $content->output );
    }

    public function test_delete_message_handles_missing_id_not_found_and_success(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->exactly( 3 ) )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'ajax-nonce' )
            ->willReturn( true );
        $message_repo = $this->createMock( Message_Repository_Interface::class );
        $message_repo->method( 'find' )->willReturnCallback(
            static function ( int|string $id ) {
                if ( 12 === (int) $id ) {
                    $message = new Message();
                    $message->id = 12;
                    return $message;
                }
                return null;
            }
        );
        $message_repo->expects( $this->once() )->method( 'delete' )->with( 12 )->willReturn( true );
        $this->adapter = $this->make_adapter( $message_repo );

        // Missing id
        $response = $this->adapter->delete_message( array( 'security' => 'valid', 'message_id' => 0 ) );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'No message id found in request', $response->get_content() );

        // Not found
        $response = $this->adapter->delete_message( array( 'security' => 'valid', 'message_id' => 999 ) );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'Message not found', $response->get_content() );

        // Success
        $response = $this->adapter->delete_message( array( 'security' => 'valid', 'message_id' => 12 ) );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->success );
    }

    public function test_delete_messages_rejects_invalid_nonce(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'racketmanager_delete-messages' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'bad', 'message_type' => 'match' ) );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertTrue( $response->get_content()->error );
    }

    public function test_delete_messages_handles_validations_and_deletion(): void {
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->exactly( 4 ) )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'racketmanager_delete-messages' )
            ->willReturn( true );
        $GLOBALS['wp_stubs_current_user_id'] = 5;

        $user = new User();
        $user->id = 5;
        $user->ID = 5;

        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturnCallback(
            static function () use ( $user ) {
                static $calls = 0;
                ++$calls;
                return 1 === $calls ? null : $user;
            }
        );
        $received_type = null;
        $message_repo  = $this->createStub( Message_Repository_Interface::class );
        $message_repo->method( 'delete_by_user_and_type' )->willReturnCallback(
            static function ( int $user_id, string $type ) use ( &$received_type ): int {
                $received_type = $type;
                return '2' === $type ? 3 : 0;
            }
        );
        $this->adapter = $this->make_adapter( $message_repo, $user_repo );

        // Missing type
        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'valid' ) );
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertSame( 'You must select the type of messages to delete', $response->get_content()->msg );

        // User not found
        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'valid', 'message_type' => 2 ) );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'User not found', $response->get_content()->msg );

        // User delete messages success with type argument verification
        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'valid', 'message_type' => 2 ) );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 3, $content->success );
        $this->assertSame( '2', $received_type );
        $this->assertNotEmpty( $content->output );
        $this->assertNotEmpty( $content->type );

        $message_repo = $this->createStub( Message_Repository_Interface::class );
        $message_repo->method( 'delete_by_user_and_type' )->willReturn( 0 );
        $user_repo    = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $this->adapter = $this->make_adapter( $message_repo, $user_repo );

        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'valid', 'message_type' => 2 ) );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 0, $response->get_content()->success );
    }

    public function test_delete_messages_reports_database_failure_as_error(): void {
        $this->security_service = $this->createStub( Security_Service_Interface::class );
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_current_user_id'] = 5;

        $user = new User();
        $user->id = 5;
        $user->ID = 5;

        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $message_repo = $this->createStub( Message_Repository_Interface::class );
        $message_repo->method( 'delete_by_user_and_type' )->willReturn( false );
        $this->adapter = $this->make_adapter( $message_repo, $user_repo );

        $response = $this->adapter->delete_messages( array( 'racketmanager_nonce' => 'valid', 'message_type' => 'read' ) );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertFalse( $response->get_content()->success );
        $this->assertStringContainsString( 'danger', $response->get_content()->output );
    }

    public function test_reset_password_flows(): void {
        // Nonce failure
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'reset_password' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->reset_password( array( 'racketmanager_nonce' => 'bad', 'user_login' => 'test@example.test' ) );
        $this->assertSame( 500, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Unable to request password reset', $content[0] );
        $this->assertSame( array( 'Form has expired. Please refresh the page and resubmit' ), $content[1] );
        $this->assertSame( array( '' ), $content[2] );

        // Valid nonce, missing email
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'reset_password' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->reset_password( array( 'racketmanager_nonce' => 'valid', 'user_login' => '' ) );
        $this->assertSame( 500, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Unable to request password reset', $content[0] );
        $this->assertSame( array( 'Email address not supplied' ), $content[1] );
        $this->assertSame( array( 'user_login' ), $content[2] );

        // Valid nonce, retrieve error
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'reset_password' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->reset_password( array( 'racketmanager_nonce' => 'valid', 'user_login' => 'error@example.test' ) );
        $this->assertSame( 500, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Unable to request password reset', $content[0] );
        $this->assertSame( array( 'Invalid email' ), $content[1] );
        $this->assertSame( array( 'user_login' ), $content[2] );

        // Valid nonce, success
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'reset_password' )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->reset_password( array( 'racketmanager_nonce' => 'valid', 'user_login' => 'ok@example.test' ) );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'Check your email', $response->get_content()[0] );
    }

    public function test_update_account_flows_and_payload_mapping(): void {
        $GLOBALS['wp_stubs_current_user_id'] = 5;
        $user = new User();
        $user->id = 5;
        $user->ID = 5;

        // Nonce failure
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'bad', 'member_account' )
            ->willReturn( false );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->update_account( array( 'racketmanager_nonce' => 'bad' ) );
        $this->assertSame( 403, $response->get_status_code() );

        // Valid nonce, validation error
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'member_account' )
            ->willReturn( true );
        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $this->adapter = $this->make_adapter( null, $user_repo );

        $response = $this->adapter->update_account( array( 'racketmanager_nonce' => 'valid', 'username' => 'test@example.test' ) );
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertTrue( $response->get_content()->error );

        // Valid nonce, user update success with complete payload mapping assertion
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'member_account' )
            ->willReturn( true );
        $captured_data = null;
        $user_repo     = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $user_repo->method( 'save' )->willReturnCallback(
            static function ( object $entity ) use ( &$captured_data ) {
                $captured_data = clone $entity;
                return 5;
            }
        );
        $this->adapter = $this->make_adapter( null, $user_repo );

        $request = array(
            'racketmanager_nonce' => 'valid',
            'username'            => 'player@example.test',
            'firstname'           => 'John',
            'lastname'            => 'Smith',
            'contactno'           => '07123456789',
            'gender'              => 'M',
            'btm'                 => '12345',
            'year_of_birth'       => '1990',
            'password'            => 'secret123',
            'rePassword'          => 'secret123',
            'opt_in'              => array( '1' => '1' ),
        );

        $response = $this->adapter->update_account( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'User updated', $response->get_content()->msg );
        $this->assertSame( 'success', $response->get_content()->class );

        $this->assertNotNull( $captured_data );
        $this->assertSame( 'player@example.test', $captured_data->email );
        $this->assertSame( 'John', $captured_data->firstname );
        $this->assertSame( 'Smith', $captured_data->surname );
        $this->assertSame( '07123456789', $captured_data->contactno );
        $this->assertSame( 'M', $captured_data->gender );
        $this->assertSame( 12345, $captured_data->btm );
        $this->assertSame( 1990, $captured_data->year_of_birth );
        $this->assertSame( 'secret123', $captured_data->password );
        $this->assertSame( 'secret123', $captured_data->re_password );
        $this->assertSame( array( '1' ), $captured_data->opt_ins );

        // Empty btm and year_of_birth casting to null
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'member_account' )
            ->willReturn( true );
        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $user_repo->method( 'save' )->willReturnCallback(
            static function ( object $entity ) use ( &$captured_data ) {
                $captured_data = clone $entity;
                return 5;
            }
        );
        $this->adapter = $this->make_adapter( null, $user_repo );

        $request['btm']           = '';
        $request['year_of_birth'] = '';

        $response = $this->adapter->update_account( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertNull( $captured_data->btm );
        $this->assertNull( $captured_data->year_of_birth );

        // No-op save maps to warning
        $this->security_service = $this->createMock( Security_Service_Interface::class );
        $this->security_service->expects( $this->once() )
            ->method( 'verify_nonce' )
            ->with( 'valid', 'member_account' )
            ->willReturn( true );
        $user_repo = $this->createStub( User_Repository_Interface::class );
        $user_repo->method( 'find' )->willReturn( $user );
        $user_repo->method( 'save' )->willReturn( 0 );
        $this->adapter = $this->make_adapter( null, $user_repo );

        $response = $this->adapter->update_account( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'No updates', $response->get_content()->msg );
        $this->assertSame( 'warning', $response->get_content()->class );
    }
}
