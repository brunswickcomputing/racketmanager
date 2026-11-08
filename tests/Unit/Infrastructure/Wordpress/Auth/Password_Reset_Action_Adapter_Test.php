<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Auth;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Infrastructure\Wordpress\Auth\Password_Reset_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Custom_Login_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Route_Request;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use RuntimeException;
use WP_Error;
use WP_User;

#[AllowMockObjectsWithoutExpectations]
final class Password_Reset_Action_Adapter_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Auth_Service $auth_service;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->auth_service                 = $this->createMock( Auth_Service::class );
		$GLOBALS['wp_stubs_added_actions']  = array();
		$GLOBALS['wp_stubs_added_filters']  = array();
	}

	protected function tearDown(): void {
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
	private function create_adapter(): Password_Reset_Action_Adapter {
		return new class( $this->auth_service ) extends Password_Reset_Action_Adapter {
			public int $terminate_count = 0;

			protected function terminate(): void {
				++$this->terminate_count;
				throw new RuntimeException( 'exit' );
			}
		};
	}

	public function test_register_hooks_registers_expected_actions(): void {
		$adapter = $this->create_adapter();
		$adapter->register_hooks();

		$expected_actions = array(
			'login_form_lostpassword' => array(
				array( 'redirect_to_custom_lost_password', 10, 1 ),
				array( 'do_password_lost', 10, 1 ),
			),
			'login_form_rp'           => array(
				array( 'redirect_to_custom_password_reset', 10, 1 ),
				array( 'do_password_reset', 10, 1 ),
			),
			'login_form_resetpass'    => array(
				array( 'redirect_to_custom_password_reset', 10, 1 ),
				array( 'do_password_reset', 10, 1 ),
			),
		);

		$this->assertSame( 6, $this->count_registrations( $GLOBALS['wp_stubs_added_actions'] ) );
		$this->assertSame( array_keys( $expected_actions ), array_keys( $GLOBALS['wp_stubs_added_actions'] ) );

		foreach ( $expected_actions as $hook => $entries ) {
			$this->assertCount( count( $entries ), $GLOBALS['wp_stubs_added_actions'][ $hook ] );
			foreach ( $entries as $index => $entry ) {
				$this->assertSame( array( $adapter, $entry[0] ), $GLOBALS['wp_stubs_added_actions'][ $hook ][ $index ]['callback'] );
				$this->assertSame( $entry[1], $GLOBALS['wp_stubs_added_actions'][ $hook ][ $index ]['priority'] );
				$this->assertSame( $entry[2], $GLOBALS['wp_stubs_added_actions'][ $hook ][ $index ]['accepted_args'] );
			}
		}
	}

	private function count_registrations( array $registrations ): int {
		$count = 0;
		foreach ( $registrations as $entries ) {
			$count += count( $entries );
		}
		return $count;
	}

	public function test_register_hooks_is_idempotent(): void {
		$adapter = $this->create_adapter();
		$adapter->register_hooks();
		$adapter->register_hooks();

		$this->assertSame( 6, $this->count_registrations( $GLOBALS['wp_stubs_added_actions'] ) );
	}

	public function test_login_form_callbacks_accept_empty_string_argument_passed_by_wordpress(): void {
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$adapter                   = $this->create_adapter();
		$adapter->register_hooks();
		$hooks                     = array( 'login_form_lostpassword', 'login_form_rp', 'login_form_resetpass' );
		$dispatched                = 0;

		foreach ( $hooks as $hook ) {
			foreach ( $GLOBALS['wp_stubs_added_actions'][ $hook ] as $entry ) {
				// WordPress passes '' as first argument to actions fired without arguments.
				$this->run_until_exit( static fn() => call_user_func_array( $entry['callback'], array( '' ) ) );
				++$dispatched;
			}
		}

		$this->assertSame( 6, $dispatched );
	}

	public function test_hook_facing_password_reset_builds_request_from_globals(): void {
		$_SERVER['REQUEST_METHOD']                           = 'POST';
		$_POST['racketmanager_nonce']                        = 'nonce';
		$_POST['rp_key']                                     = 'key1';
		$_POST['rp_login']                                   = 'login1';
		$_POST['pass1']                                      = 'p';
		$_POST['pass2']                                      = 'p';
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'invalid_key', 'Bad key' );

		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => call_user_func_array( array( $adapter, 'do_password_reset' ), array( '' ) ) );

		$this->assertSame(
			'https://example.test/member-login?login=invalidkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_redirect_to_custom_lostpassword_when_logged_in(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		$GLOBALS['wp_stubs_current_user']      = new WP_User( 1, 'admin', array( 'administrator' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'get_logged_in_redirect_url' )
			->with( null )
			->willReturn( 'https://example.test/app' );

		$request = new Custom_Login_Request( 'GET', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_lost_password( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/app', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_redirect_to_custom_lostpassword_guest(): void {
		$GLOBALS['wp_stubs_is_user_logged_in'] = false;
		$GLOBALS['wp_stubs_current_user']      = null;

		$request = new Custom_Login_Request( 'GET', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_lost_password( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame( 'https://example.test/member-password-lost', $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? '' );
	}

	public function test_do_password_lost_with_error(): void {
		$GLOBALS['wp_stubs_retrieve_password_callback'] = static function () {
			return new WP_Error( 'invalid_user', 'No user found' );
		};

		$request = new Custom_Login_Request( 'POST', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_lost( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-lost?errors=invalid_user',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_lost_success(): void {
		$GLOBALS['wp_stubs_retrieve_password_callback'] = static function () {
			return true;
		};

		$request = new Custom_Login_Request( 'POST', null );
		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_lost( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-lost?checkemail=confirm',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_redirect_to_custom_password_reset_invalid_key(): void {
		$GLOBALS['wp_stubs_is_user_logged_in']                = false;
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'invalid_key', 'Invalid key' );
		$request                                              = new Password_Reset_Route_Request( 'GET', 'badkey', 'john' );

		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-lost?errors=invalidkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_redirect_to_custom_password_reset_expired_key(): void {
		$GLOBALS['wp_stubs_is_user_logged_in']                = false;
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'expired_key', 'Expired' );
		$request                                              = new Password_Reset_Route_Request( 'GET', 'expkey', 'john' );

		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-lost?errors=expiredkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_redirect_to_custom_password_reset_valid_key(): void {
		$GLOBALS['wp_stubs_is_user_logged_in']                = false;
		$user                                                 = new WP_User( 10, 'john' );
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = $user;
		$request                                              = new Password_Reset_Route_Request( 'GET', 'goodkey', 'john' );

		$adapter = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_custom_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-reset?login=john&key=goodkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_non_post_does_nothing(): void {
		$request = new Password_Reset_Request( 'GET', 'nonce', 'key', 'login', 'p1', 'p1' );
		$adapter = $this->create_adapter();
		$adapter->handle_password_reset( $request );

		$this->assertSame( 0, $adapter->terminate_count );
		$this->assertNull( $GLOBALS['wp_stubs_last_safe_redirect'] );
	}

	public function test_do_password_reset_timeout(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
		$request                                    = new Password_Reset_Request( 'POST', 'my_nonce', 'mykey', 'john', 'p1', 'p1' );
		$adapter                                    = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-reset?key=mykey&login=john&error=form_has_timedout',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_invalid_key(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'invalid_key', 'Invalid key' );
		$request                                              = new Password_Reset_Request( 'POST', 'good_nonce', 'badkey', 'john', 'p1', 'p1' );
		$adapter                                              = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?login=invalidkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_expired_key(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'expired_key', 'Key has expired' );
		$request                                              = new Password_Reset_Request( 'POST', 'good_nonce', 'expkey', 'john', 'p1', 'p1' );
		$adapter                                              = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?login=expiredkey',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_mismatch(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$user                                                 = new WP_User( 10, 'john' );
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = $user;
		$request                                              = new Password_Reset_Request( 'POST', 'good_nonce', 'goodkey', 'john', 'p1', 'p2' );
		$adapter                                              = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-reset?key=goodkey&login=john&error=password_reset_mismatch',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_empty(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$user                                                 = new WP_User( 10, 'john' );
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = $user;
		$request                                              = new Password_Reset_Request( 'POST', 'good_nonce', 'goodkey', 'john', '', '' );
		$adapter                                              = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-password-reset?key=goodkey&login=john&error=password_reset_empty',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
	}

	public function test_do_password_reset_success(): void {
		$GLOBALS['wp_stubs_wp_verify_nonce_return']           = true;
		$user                                                 = new WP_User( 10, 'john' );
		$GLOBALS['wp_stubs_check_password_reset_key_return'] = $user;
		$request                                              = new Password_Reset_Request( 'POST', 'good_nonce', 'goodkey', 'john', 'validPass123!', 'validPass123!' );
		$adapter                                              = $this->create_adapter();
		$this->run_until_exit( static fn() => $adapter->handle_password_reset( $request ) );

		$this->assertSame( 1, $adapter->terminate_count );
		$this->assertSame(
			'https://example.test/member-login?passwordUpdate=true',
			$GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? ''
		);
		$this->assertSame( $user, $GLOBALS['wp_stubs_last_reset_password']['user'] ?? null );
		$this->assertSame(
			'validPass123!',
			$GLOBALS['wp_stubs_last_reset_password']['password'] ?? ''
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
}
