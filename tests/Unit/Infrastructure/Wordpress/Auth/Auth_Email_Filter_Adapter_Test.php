<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Auth;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Infrastructure\Wordpress\Auth\Auth_Email_Filter_Adapter;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use WP_User;

#[AllowMockObjectsWithoutExpectations]
final class Auth_Email_Filter_Adapter_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Auth_Service $auth_service;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->auth_service                = $this->createMock( Auth_Service::class );
		$GLOBALS['wp_stubs_added_actions'] = array();
		$GLOBALS['wp_stubs_added_filters'] = array();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function create_adapter(): Auth_Email_Filter_Adapter {
		return new Auth_Email_Filter_Adapter( $this->auth_service );
	}

	public function test_register_hooks_registers_expected_filters(): void {
		$adapter = $this->create_adapter();
		$adapter->register_hooks();

		$expected_filters = array(
			'retrieve_password_message'              => array( array( 'racketmanager_retrieve_password_email', 10, 4 ) ),
			'password_change_email'                  => array( array( 'racketmanager_password_change_email', 10, 3 ) ),
			'wp_privacy_personal_data_email_content' => array( array( 'racketmanager_privacy_personal_data_email', 10, 3 ) ),
			'user_request_action_email_content'      => array( array( 'racketmanager_user_request_action_email', 10, 2 ) ),
			'wp_new_user_notification_email_admin'   => array( array( 'my_wp_new_user_notification_email_admin', 10, 3 ) ),
			'wp_new_user_notification_email'         => array( array( 'my_wp_new_user_notification_email', 10, 3 ) ),
			'password_hint'                          => array( array( 'racketmanager_change_password_hint', 10, 1 ) ),
		);

		$this->assertSame( 7, $this->count_registrations( $GLOBALS['wp_stubs_added_filters'] ) );
		$this->assertSame( array_keys( $expected_filters ), array_keys( $GLOBALS['wp_stubs_added_filters'] ) );

		foreach ( $expected_filters as $hook => $entries ) {
			$this->assertCount( count( $entries ), $GLOBALS['wp_stubs_added_filters'][ $hook ] );
			foreach ( $entries as $index => $entry ) {
				$this->assertSame( array( $adapter, $entry[0] ), $GLOBALS['wp_stubs_added_filters'][ $hook ][ $index ]['callback'] );
				$this->assertSame( $entry[1], $GLOBALS['wp_stubs_added_filters'][ $hook ][ $index ]['priority'] );
				$this->assertSame( $entry[2], $GLOBALS['wp_stubs_added_filters'][ $hook ][ $index ]['accepted_args'] );
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

	public function test_racketmanager_change_password_hint_delegates_to_auth_service(): void {
		$this->auth_service->expects( $this->once() )
			->method( 'get_password_hint' )
			->willReturn( 'Custom hint' );

		$adapter = $this->create_adapter();
		$this->assertSame( 'Custom hint', $adapter->racketmanager_change_password_hint( 'Original hint' ) );
	}

	public function test_racketmanager_wp_email_content_type_delegates_to_auth_service(): void {
		$this->auth_service->expects( $this->once() )
			->method( 'get_email_content_type' )
			->willReturn( 'text/html' );

		$adapter = $this->create_adapter();
		$this->assertSame( 'text/html', $adapter->racketmanager_wp_email_content_type() );
	}

	public function test_email_delegation_methods(): void {
		$user = new WP_User( 10, 'john' );

		$this->auth_service->expects( $this->once() )
			->method( 'format_new_user_notification_email_admin' )
			->with( array( 'key' => 'val' ), $user, 'My Blog' )
			->willReturn( array( 'admin' => 'email' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'format_new_user_notification_email' )
			->with( array( 'key' => 'val' ), $user )
			->willReturn( array( 'user' => 'email' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'format_retrieve_password_email' )
			->with( 'key123', 'john', $user )
			->willReturn( 'Retrieved message' );

		$this->auth_service->expects( $this->once() )
			->method( 'format_password_change_email' )
			->with( array( 'pw' => 'change' ), array( 'old' => 'data' ) )
			->willReturn( array( 'formatted' => 'pw' ) );

		$this->auth_service->expects( $this->once() )
			->method( 'format_privacy_personal_data_email' )
			->willReturn( 'Privacy content' );

		$this->auth_service->expects( $this->once() )
			->method( 'format_user_request_action_email' )
			->willReturn( 'User action content' );

		$adapter = $this->create_adapter();

		$this->assertSame(
			array( 'admin' => 'email' ),
			$adapter->my_wp_new_user_notification_email_admin( array( 'key' => 'val' ), $user, 'My Blog' )
		);
		$this->assertSame(
			array( 'user' => 'email' ),
			$adapter->my_wp_new_user_notification_email( array( 'key' => 'val' ), $user, 'My Blog' )
		);
		$this->assertSame(
			'Retrieved message',
			$adapter->racketmanager_retrieve_password_email( 'Original message', 'key123', 'john', $user )
		);
		$this->assertSame(
			array( 'formatted' => 'pw' ),
			$adapter->racketmanager_password_change_email( array( 'pw' => 'change' ), array( 'old' => 'data' ), array( 'new' => 'data' ) )
		);
		$this->assertSame(
			'Privacy content',
			$adapter->racketmanager_privacy_personal_data_email( 'Orig', 'req1', 'data' )
		);
		$this->assertSame(
			'User action content',
			$adapter->racketmanager_user_request_action_email( 'Orig', array( 'data' ) )
		);
	}

	public function test_email_callbacks_attach_html_content_type_filter(): void {
		$user    = new WP_User( 10, 'john' );
		$adapter = $this->create_adapter();

		$this->auth_service->method( 'format_retrieve_password_email' )->willReturn( 'msg' );
		$this->auth_service->method( 'format_password_change_email' )->willReturn( array() );
		$this->auth_service->method( 'format_privacy_personal_data_email' )->willReturn( 'privacy' );
		$this->auth_service->method( 'format_user_request_action_email' )->willReturn( 'action' );

		$adapter->racketmanager_retrieve_password_email( '', 'k', 'l', $user );
		$this->assertNotEmpty( $GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'] ?? array() );
		$this->assertSame(
			array( $adapter, 'racketmanager_wp_email_content_type' ),
			$GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'][0]['callback']
		);

		$GLOBALS['wp_stubs_added_filters'] = array();
		$adapter->racketmanager_password_change_email( array(), array(), array() );
		$this->assertNotEmpty( $GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'] ?? array() );
		$this->assertSame(
			array( $adapter, 'racketmanager_wp_email_content_type' ),
			$GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'][0]['callback']
		);

		$GLOBALS['wp_stubs_added_filters'] = array();
		$adapter->racketmanager_privacy_personal_data_email( '', '', '' );
		$this->assertNotEmpty( $GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'] ?? array() );
		$this->assertSame(
			array( $adapter, 'racketmanager_wp_email_content_type' ),
			$GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'][0]['callback']
		);

		$GLOBALS['wp_stubs_added_filters'] = array();
		$adapter->racketmanager_user_request_action_email( '', array() );
		$this->assertNotEmpty( $GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'] ?? array() );
		$this->assertSame(
			array( $adapter, 'racketmanager_wp_email_content_type' ),
			$GLOBALS['wp_stubs_added_filters']['wp_mail_content_type'][0]['callback']
		);
	}

	public function test_register_hooks_is_idempotent(): void {
		$adapter = $this->create_adapter();
		$adapter->register_hooks();
		$adapter->register_hooks();

		$this->assertSame( 7, $this->count_registrations( $GLOBALS['wp_stubs_added_filters'] ) );
	}
}
