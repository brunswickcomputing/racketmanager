<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Options_Admin_Controller;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Options_Admin_Controller_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Option_Service $option_service;
	private Options_Action_Dispatcher_Interface $dispatcher;
	private Action_Guard_Interface $guard;
	private Options_Admin_Controller $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->option_service = $this->createMock( Option_Service::class );
		$this->dispatcher     = $this->createMock( Options_Action_Dispatcher_Interface::class );
		$this->guard          = $this->createMock( Action_Guard_Interface::class );

		$this->controller = new Options_Admin_Controller(
			$this->option_service,
			$this->dispatcher,
			$this->guard
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_get_options_page_renders_view_model(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'manage_racketmanager' );

		$stored = array(
			'color_scheme' => 'dark',
		);

		$this->option_service->expects( self::once() )
			->method( 'get_options' )
			->willReturn( $stored );

		$query  = array( 'page' => 'racketmanager-settings', 'tab' => 'billing' );
		$result = $this->controller->options_page( $query, array() );

		self::assertArrayHasKey( 'view_model', $result );
		self::assertInstanceOf( Options_Page_View_Model::class, $result['view_model'] );
		self::assertSame( 'billing', $result['view_model']->tab );
		self::assertSame( $stored, $result['view_model']->options );
	}

	public function test_get_options_page_restores_flash_options_and_preserves_stored_secrets(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'manage_racketmanager' );

		$stored = array(
			'billing' => array(
				'api_secret_key_live'   => 'sk_live_secret',
				'api_endpoint_key_live' => 'whsec_secret',
				'bankName'              => 'Old Bank',
			),
			'keys'    => array(
				'recaptchaSecretKey' => 'recaptcha_secret',
			),
		);

		$this->option_service->expects( self::once() )
			->method( 'get_options' )
			->willReturn( $stored );

		$flash_data = array(
			'tab'     => 'billing',
			'options' => array(
				'billing' => array(
					'bankName' => 'New Bank Name',
				),
			),
			'errors'  => array(
				'bankName' => 'Bank name invalid',
			),
		);

		$result = $this->controller->options_page( array(), array(), $flash_data );

		self::assertArrayHasKey( 'view_model', $result );
		$vm = $result['view_model'];
		self::assertSame( 'billing', $vm->tab );
		self::assertSame( 'New Bank Name', $vm->options['billing']['bankName'] );
		self::assertSame( 'sk_live_secret', $vm->options['billing']['api_secret_key_live'] );
		self::assertSame( 'whsec_secret', $vm->options['billing']['api_endpoint_key_live'] );
		self::assertSame( 'recaptcha_secret', $vm->options['keys']['recaptchaSecretKey'] );
		self::assertTrue( $vm->errors->has( 'bankName' ) );
	}

	public function test_get_options_page_asserts_capability_and_throws(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'manage_racketmanager' )
			->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

		$this->expectException( Invalid_Status_Exception::class );
		$this->expectExceptionMessage( 'Permission denied' );

		$this->controller->options_page( array(), array() );
	}

	public function test_post_mutation_dispatches_and_returns_prg_redirect(): void {
		$query = array( 'page' => 'racketmanager-settings' );
		$post  = array(
			'updateRacketManager' => '1',
			'active-tab'          => 'billing',
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post )
			->willReturn(
				new Action_Result_DTO(
					message: 'Settings saved',
					message_type: Admin_Message_Type::SUCCESS,
					tab_override: 'billing'
				)
			);

		$result = $this->controller->options_page( $query, $post );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'page=racketmanager-settings', $result['redirect'] );
		self::assertStringContainsString( 'tab=billing', $result['redirect'] );
		self::assertSame( 'Settings saved', $result['message'] );
		self::assertFalse( $result['message_type'] );
	}

	public function test_post_mutation_failure_returns_error_redirect(): void {
		$query = array( 'page' => 'racketmanager-settings' );
		$post  = array(
			'updateRacketManager' => '1',
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post )
			->willReturn(
				new Action_Result_DTO(
					message: 'Security token invalid',
					message_type: Admin_Message_Type::ERROR,
					flash_data: array( 'errors' => array( 'general' => 'Failed' ) )
				)
			);

		$result = $this->controller->options_page( $query, $post );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertSame( 'Security token invalid', $result['message'] );
		self::assertTrue( $result['message_type'] );
		self::assertSame( array( 'errors' => array( 'general' => 'Failed' ) ), $result['flash_data'] );
	}
}
