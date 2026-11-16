<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Options;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Options_Delivery_Test extends TestCase {
	use Creates_Admin_Delivery_Container;
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$_GET                                 = array();
		$_POST                                = array();
		$_SERVER['REQUEST_METHOD']            = 'GET';
		$GLOBALS['wp_stubs_current_user_can'] = true;
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_post_dispatches_and_redirects_with_flash_without_rendering(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET                      = array( 'page' => 'racketmanager-settings' );
		$_POST                     = array(
			'updateRacketManager' => '1',
			'active-tab'          => 'billing',
		);

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->method( 'pop' )->willReturn( array() );
		$flash_store->expects( self::once() )
			->method( 'set' )
			->with( 'Settings saved', false );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::once() )
			->method( 'redirect' )
			->with(
				self::callback(
					static function ( string $url ): bool {
						return str_contains( $url, 'page=racketmanager-settings' ) && str_contains( $url, 'tab=billing' );
					}
				)
			);

		$renderer = $this->createMock( View_Renderer_Interface::class );
		$renderer->expects( self::never() )->method( 'render' );

		$dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $_GET, $_POST )
			->willReturn(
				new Action_Result_DTO(
					message: 'Settings saved',
					message_type: Admin_Message_Type::SUCCESS,
					tab_override: 'billing'
				)
			);
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

		$container = $this->create_admin_delivery_container(
			services: array(
				'options_action_dispatcher' => $dispatcher,
			),
			redirector: $redirector,
			renderer: $renderer,
			flash_store: $flash_store,
		);

		$screen = $container->get( Admin_Options::class );
		$screen->display_options_page();
	}

	public function test_get_applies_flash_and_renders_typed_view_model_with_real_template(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET                      = array(
			'page' => 'racketmanager-settings',
			'tab'  => 'players',
		);

		$stored_options = array(
			'billing' => array(
				'api_secret_key_live'   => 'sk_live_persisted_secret',
				'api_endpoint_key_live' => 'whsec_persisted_secret',
				'bankName'              => 'Persisted Bank',
			),
			'keys'    => array(
				'recaptchaSecretKey' => 'recaptcha_persisted_secret',
			),
		);

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::once() )
			->method( 'pop' )
			->willReturn( array() );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
		$dispatcher->expects( self::never() )->method( 'handle' );

		$renderer = $this->createMock( View_Renderer_Interface::class );
		$renderer->expects( self::once() )
			->method( 'render' )
			->with(
				'admin/show-settings',
				self::callback(
					static function ( $vm ) use ( $stored_options ): bool {
						return $vm instanceof Options_Page_View_Model
							&& 'players' === $vm->tab
							&& $vm->options === $stored_options;
					}
				)
			);

		$option_service = $this->createMock( Option_Service::class );
		$option_service->method( 'get_options' )->willReturn( $stored_options );

		$container = $this->create_admin_delivery_container(
			services: array(
				'options_action_dispatcher' => $dispatcher,
				'option_service'            => $option_service,
			),
			redirector: $redirector,
			renderer: $renderer,
			flash_store: $flash_store,
		);

		$screen = $container->get( Admin_Options::class );
		$screen->display_options_page();
	}

	public function test_view_options_denied_shows_alert_and_skips_dispatch_and_rendering(): void {
		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::never() )->method( 'set' );
		$flash_store->expects( self::once() )->method( 'pop' )->willReturn( array() );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$renderer = $this->createMock( View_Renderer_Interface::class );
		$renderer->expects( self::never() )->method( 'render' );

		$dispatcher = $this->createMock( Options_Action_Dispatcher_Interface::class );
		$dispatcher->expects( self::never() )->method( 'handle' );

		$guard = $this->createMock( Action_Guard_Interface::class );
		$guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'manage_racketmanager' )
			->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'Permission denied' ) );

		$container = $this->create_admin_delivery_container(
			services: array(
				'options_action_dispatcher' => $dispatcher,
			),
			guard: $guard,
			redirector: $redirector,
			renderer: $renderer,
			flash_store: $flash_store,
		);

		$screen = $container->get( Admin_Options::class );

		ob_start();
		try {
			$screen->display_options_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		self::assertStringContainsString( 'alert', $output );
		self::assertStringContainsString( 'Permission denied', $output );
	}

	public function test_container_resolves_screen_bridge(): void {
		$container = $this->create_admin_delivery_container();
		$screen    = $container->get( Admin_Options::class );

		self::assertInstanceOf( Admin_Options::class, $screen );
	}
}
