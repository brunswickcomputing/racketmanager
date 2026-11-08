<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Auth;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Infrastructure\Wordpress\Auth\Auth_Email_Filter_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Login_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Password_Reset_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Custom_Login_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\User_Registration_Request;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionProperty;
use RuntimeException;
use WP_Error;
use WP_User;

#[AllowMockObjectsWithoutExpectations]
final class Login_Action_Adapter_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Auth_Service $auth_service;
	private Option_Service $option_service;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->auth_service                     = $this->createMock( Auth_Service::class );
		$this->option_service                   = $this->createMock( Option_Service::class );
		$GLOBALS['wp_stubs_is_admin']           = false;
		$GLOBALS['wp_stubs_is_user_logged_in']  = false;
		$GLOBALS['wp_stubs_current_user']       = null;
		$GLOBALS['wp_stubs_last_safe_redirect'] = null;
		$GLOBALS['wp_stubs_doing_ajax']         = false;
		$GLOBALS['wp_stubs_added_actions']      = array();
		$GLOBALS['wp_stubs_added_filters']      = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['wp_stubs_is_admin'],
			$GLOBALS['wp_stubs_current_user'],
			$GLOBALS['wp_stubs_last_safe_redirect'],
			$GLOBALS['wp_stubs_doing_ajax']
		);
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Run a callable, swallowing the simulated exit exception.
	 */
	private function run_until_exit( callable $callback ): void {
		try {
			$callback();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'exit', $e->getMessage() );
		}
	}

	/**
	 * Create an adapter whose terminate() throws a RuntimeException instead of exiting.
	 */
	private function create_adapter(
		?Password_Reset_Action_Adapter $password_reset_adapter = null,
		?Auth_Email_Filter_Adapter $email_filter_adapter = null
	): Login_Action_Adapter {
		$password_reset_adapter ??= new Password_Reset_Action_Adapter( $this->auth_service );
		$email_filter_adapter   ??= new Auth_Email_Filter_Adapter( $this->auth_service );

		return new class(
			$this->auth_service,
			$this->option_service,
			$password_reset_adapter,
			$email_filter_adapter
		) extends Login_Action_Adapter {
			public int $terminate_count = 0;

			protected function terminate(): void {
				++$this->terminate_count;
				throw new RuntimeException( 'exit' );
			}
		};
	}

	public function test_construct_registers_expected_actions_and_filters(): void {
		$password_adapter = new Password_Reset_Action_Adapter( $this->auth_service );
		$email_adapter    = new Auth_Email_Filter_Adapter( $this->auth_service );
		$adapter          = $this->create_adapter( $password_adapter, $email_adapter );

		$expected_actions = array(
			'wp_enqueue_scripts'      => array( array( $adapter, 'do_enqueue_login_scripts', 10, 1 ) ),
			'login_form_login'        => array( array( $adapter, 'redirect_to_custom_login', 10, 1 ) ),
			'wp_logout'               => array( array( $adapter, 'redirect_after_logout', 10, 1 ) ),
			'login_form_register'     => array(
				array( $adapter, 'redirect_to_custom_register', 10, 1 ),
				array( $adapter, 'do_register_user', 10, 1 ),
			),
			'admin_init'              => array( array( $adapter, 'disable_dashboard', 10, 1 ) ),
			'login_form_lostpassword' => array(
				array( $password_adapter, 'redirect_to_custom_lost_password', 10, 1 ),
				array( $password_adapter, 'do_password_lost', 10, 1 ),
			),
			'login_form_rp'           => array(
				array( $password_adapter, 'redirect_to_custom_password_reset', 10, 1 ),
				array( $password_adapter, 'do_password_reset', 10, 1 ),
			),
			'login_form_resetpass'    => array(
				array( $password_adapter, 'redirect_to_custom_password_reset', 10, 1 ),
				array( $password_adapter, 'do_password_reset', 10, 1 ),
			),
		);
		$expected_filters = array(
			'login_redirect'                         => array( array( $adapter, 'redirect_after_login', 10, 3 ) ),
			'retrieve_password_message'              => array( array( $email_adapter, 'racketmanager_retrieve_password_email', 10, 4 ) ),
			'password_change_email'                  => array( array( $email_adapter, 'racketmanager_password_change_email', 10, 3 ) ),
			'wp_privacy_personal_data_email_content' => array( array( $email_adapter, 'racketmanager_privacy_personal_data_email', 10, 3 ) ),
			'user_request_action_email_content'      => array( array( $email_adapter, 'racketmanager_user_request_action_email', 10, 2 ) ),
			'wp_new_user_notification_email_admin'   => array( array( $email_adapter, 'my_wp_new_user_notification_email_admin', 10, 3 ) ),
			'wp_new_user_notification_email'         => array( array( $email_adapter, 'my_wp_new_user_notification_email', 10, 3 ) ),
			'password_hint'                          => array( array( $email_adapter, 'racketmanager_change_password_hint', 10, 1 ) ),
		);

		$this->assertSame( 20, $this->count_registrations( $GLOBALS['wp_stubs_added_actions'] ) + $this->count_registrations( $GLOBALS['wp_stubs_added_filters'] ) );
		$this->assert_registrations( $expected_actions, $GLOBALS['wp_stubs_added_actions'] );
		$this->assert_registrations( $expected_filters, $GLOBALS['wp_stubs_added_filters'] );
	}

	public function test_construct_accepts_injected_subadapters(): void {
		$password_adapter = new Password_Reset_Action_Adapter( $this->auth_service );
		$email_adapter    = new Auth_Email_Filter_Adapter( $this->auth_service );
		$adapter          = new Login_Action_Adapter( $this->auth_service, $this->option_service, $password_adapter, $email_adapter );

		$pw_prop = new ReflectionProperty( Login_Action_Adapter::class, 'password_reset_adapter' );
		$pw_prop->setAccessible( true );
		$this->assertSame( $password_adapter, $pw_prop->getValue( $adapter ) );

		$email_prop = new ReflectionProperty( Login_Action_Adapter::class, 'email_filter_adapter' );
		$email_prop->setAccessible( true );
		$this->assertSame( $email_adapter, $email_prop->getValue( $adapter ) );
	}

	private function count_registrations( array $registrations ): int {
		$count = 0;
		foreach ( $registrations as $entries ) {
			$count += count( $entries );
		}
		return $count;
	}

	private function assert_registrations( array $expected, array $actual ): void {
		$this->assertSame( array_keys( $expected ), array_keys( $actual ) );
		foreach ( $expected as $hook => $entries ) {
			$this->assertCount( count( $entries ), $actual[ $hook ], "Unexpected number of callbacks for $hook" );
			foreach ( $entries as $index => $entry ) {
				$this->assertSame( array( $entry[0], $entry[1] ), $actual[ $hook ][ $index ]['callback'], "Callback mismatch for {$hook}[$index]" );
				$this->assertSame( $entry[2], $actual[ $hook ][ $index ]['priority'], "Priority mismatch for {$hook}[$index]" );
				$this->assertSame( $entry[3], $actual[ $hook ][ $index ]['accepted_args'], "Arg count mismatch for {$hook}[$index]" );
			}
		}
	}

	public function test_login_form_callbacks_accept_empty_string_argument_passed_by_wordpress(): void {
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$adapter                   = $this->create_adapter();
		$hooks                     = array( 'login_form_login', 'login_form_register', 'login_form_lostpassword', 'login_form_rp', 'login_form_resetpass' );
		$dispatched                = 0;

		foreach ( $hooks as $hook ) {
			foreach ( $GLOBALS['wp_stubs_added_actions'][ $hook ] as $entry ) {
				// WordPress passes '' as first argument to actions fired without arguments.
				$this->run_until_exit( static fn() => call_user_func_array( $entry['callback'], array( '' ) ) );
				++$dispatched;
			}
		}

		$this->assertSame( 9, $dispatched );
	}

	public function test_hook_facing_login_callbacks_build_request_from_globals(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_REQUEST['redirect_to']   = 'https://example.com/dest';
		$adapter                   = $this->create_adapter();
		$this->run_until_exit( static fn() => call_user_func_array( array( $adapter, 'redirect_to_custom_login' ), array( '' ) ) );

		$this->assertSame(
			'https://example.test/member-login?redirect_to=https%3A%2F%2Fexample.com%2Fdest',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_enqueue_login_scripts(): void {
		$adapter = $this->create_adapter();
		$adapter->do_enqueue_login_scripts();

		$this->assertArrayHasKey( 'recaptcha', $GLOBALS['wp_stubs_enqueued_scripts'] ?? array() );
		$this->assertSame(
			'https://www.google.com/recaptcha/api.js',
			$GLOBALS['wp_stubs_enqueued_scripts']['recaptcha']['src'] ?? ''
		);
	}

	public function test_disable_dashboard_does_nothing_when_not_logged_in(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = false;
		$GLOBALS['wp_stubs_current_user']      = null;

		$adapter = $this->create_adapter();
		$adapter->disable_dashboard();

		$this->assertSame( 0, $adapter->terminate_count );
		$this->assertArrayNotHasKey( 'location', $GLOBALS['wp_stubs_last_safe_redirect'] ?? array() );
	}

	public function test_disable_dashboard_does_nothing_when_not_subscriber(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$GLOBALS['wp_stubs_current_user']      = new WP_User( 1, 'admin', array( 'administrator' ) );
		$GLOBALS['wp_stubs_is_admin']          = true;

		$adapter = $this->create_adapter();
		$adapter->disable_dashboard();

		$this->assertSame( 0, $adapter->terminate_count );
		$this->assertArrayNotHasKey( 'location', $GLOBALS['wp_stubs_last_safe_redirect'] ?? array() );
	}

	public function test_disable_dashboard_does_nothing_when_doing_ajax(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$user                                  = new WP_User( 2, 'sub' );
		$user->caps                            = array( 'subscriber' );
		$GLOBALS['wp_stubs_current_user']      = $user;
		$GLOBALS['wp_stubs_is_admin']          = true;
		$GLOBALS['wp_stubs_doing_ajax']        = true;

		$adapter = $this->create_adapter();
		$adapter->disable_dashboard();

		$this->assertSame( 0, $adapter->terminate_count );
		$this->assertArrayNotHasKey( 'location', $GLOBALS['wp_stubs_last_safe_redirect'] ?? array() );
	}

	public function test_disable_dashboard_does_nothing_when_not_admin(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$user                                  = new WP_User( 2, 'sub' );
		$user->caps                            = array( 'subscriber' );
		$GLOBALS['wp_stubs_current_user']      = $user;
		$GLOBALS['wp_stubs_is_admin']          = false;

		$adapter = $this->create_adapter();
		$adapter->disable_dashboard();

		$this->assertSame( 0, $adapter->terminate_count );
		$this->assertArrayNotHasKey( 'location', $GLOBALS['wp_stubs_last_safe_redirect'] ?? array() );
	}

	public function test_disable_dashboard_redirects_subscriber_when_admin(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$user                                  = new WP_User( 2, 'sub' );
		$user->caps                            = array( 'subscriber' );
		$GLOBALS['wp_stubs_current_user']      = $user;
		$GLOBALS['wp_stubs_is_admin']          = true;
		$GLOBALS['wp_stubs_doing_ajax']        = false;

		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->disable_dashboard() );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_to_custom_login_redirects_logged_in_user(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$GLOBALS['wp_stubs_current_user']      = new WP_User( 1, 'admin', array( 'administrator' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'get_logged_in_redirect_url' )
			->with( 'https://example.com/dest' )
			->willReturn( 'https://example.test/app' );

		$request = new Custom_Login_Request( 'GET', 'https://example.com/dest' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_login( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/app', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_to_custom_login_redirects_guest(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = false;
		$GLOBALS['wp_stubs_current_user']      = null;

		$request = new Custom_Login_Request( 'GET', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_login( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/member-login', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_to_custom_login_preserves_redirect_to_param(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = false;
		$GLOBALS['wp_stubs_current_user']      = null;

		$request = new Custom_Login_Request( 'GET', 'https://example.com/dest' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_login( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?redirect_to=https%3A%2F%2Fexample.com%2Fdest',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_redirect_logged_in_user_delegates_to_auth_service(): void {
		$this->auth_service->expects( $this->once() )
			->method( 'get_logged_in_redirect_url' )
			->with( 'https://example.com/dest' )
			->willReturn( 'https://example.com/dest' );

		$adapter = $this->create_adapter();
		$adapter->redirect_logged_in_user( 'https://example.com/dest' );

		$this->assertSame( 'https://example.com/dest', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_after_logout(): void {
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->redirect_after_logout() );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_after_login_delegates_to_auth_service(): void {
		$user = new WP_User( 1, 'admin', array( 'administrator' ) );
		$this->auth_service->expects( $this->once() )
			->method( 'evaluate_redirect_after_login' )
			->with( 'https://example.com/requested', $user )
			->willReturn( 'https://example.com/final' );

		$adapter = $this->create_adapter();
		$this->assertSame(
			'https://example.com/final',
			$adapter->redirect_after_login( 'https://example.com/requested', 'https://example.com/requested', $user )
		);
	}

	public function test_redirect_to_custom_register_redirects_logged_in_user(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$GLOBALS['wp_stubs_current_user']      = new WP_User( 1, 'admin', array( 'administrator' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'get_logged_in_redirect_url' )
			->with( null )
			->willReturn( 'https://example.test/app' );

		$request = new Custom_Login_Request( 'GET', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_register( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/app', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_to_custom_register_redirects_guest(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = false;
		$GLOBALS['wp_stubs_current_user']      = null;

		$request = new Custom_Login_Request( 'GET', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_register( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/member-login?action=register', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_do_register_user_builds_request_from_globals(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$adapter                   = $this->create_adapter();
		$this->run_until_exit( static fn() => call_user_func_array( array( $adapter, 'do_register_user' ), array( '' ) ) );

		$this->assertSame(
			'https://example.test/member-login?action=register',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_handle_register_user_non_post_redirects_to_register_page(): void {
		$request = new User_Registration_Request( 'GET' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?action=register',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_register_user_closed_registration(): void {
		$GLOBALS['wp_stubs_options']['users_can_register'] = 0;

		$request = new User_Registration_Request( 'POST', 'nonce', 'token', 'test@example.com', 'John', 'Doe' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?action=register&register-errors=closed',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_register_user_nonce_failure(): void {
		$GLOBALS['wp_stubs_options']['users_can_register'] = 1;
		$GLOBALS['wp_stubs_wp_verify_nonce_return']  = false;

		$request = new User_Registration_Request( 'POST', 'bad_nonce', 'token', 'test@example.com', 'John', 'Doe' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?action=register&register-errors=security',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_register_user_recaptcha_failure(): void {
		$GLOBALS['wp_stubs_options']['users_can_register'] = 1;
		$GLOBALS['wp_stubs_wp_verify_nonce_return']  = true;
		$GLOBALS['wp_stubs_wp_remote_post_return']   = array( 'body' => '{"success":false}' );
		$this->option_service->expects( $this->once() )
			->method( 'get_options' )
			->with( 'keys' )
			->willReturn( array( 'recaptchaSecretKey' => 'secret_123' ) );

		$request = new User_Registration_Request( 'POST', 'good_nonce', 'bad_token', 'test@example.com', 'John', 'Doe' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?action=register&register-errors=captcha',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_register_user_missing_fields(): void {
		$GLOBALS['wp_stubs_options']['users_can_register'] = 1;
		$GLOBALS['wp_stubs_wp_verify_nonce_return']  = true;
		$GLOBALS['wp_stubs_wp_remote_post_return']   = array( 'body' => '{"success":true}' );
		$this->option_service->expects( $this->once() )
			->method( 'get_options' )
			->with( 'keys' )
			->willReturn( array( 'recaptchaSecretKey' => 'secret_123' ) );

		$request = new User_Registration_Request( 'POST', 'good_nonce', 'tok_123', '', '', '' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?action=register&register-errors=email%2Cfirst_name%2Clast_name',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_register_user_successful_registration(): void {
		$GLOBALS['wp_stubs_options']['users_can_register'] = 1;
		$GLOBALS['wp_stubs_wp_verify_nonce_return']  = true;
		$GLOBALS['wp_stubs_wp_remote_post_return']   = array( 'body' => '{"success":true}' );
		$this->option_service->expects( $this->once() )
			->method( 'get_options' )
			->with( 'keys' )
			->willReturn( array( 'recaptchaSecretKey' => 'secret_123' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'register_user' )
			->with( 'test@example.com', 'John', 'Doe' )
			->willReturn( 99 );

		$request = new User_Registration_Request( 'POST', 'good_nonce', 'tok_123', 'test@example.com', 'John', 'Doe' );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_register_user( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?registered=test%40example.com',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
		$this->assertContains( array( 99, 'show_admin_bar_front', false ), $GLOBALS['wp_stubs_update_user_meta'] );
	}
}
