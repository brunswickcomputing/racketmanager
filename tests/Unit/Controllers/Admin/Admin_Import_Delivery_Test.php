<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Application\Admin\Ports\Admin_Redirector_Interface;
use Racketmanager\Application\Admin\Ports\Import_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Import_Delivery_Test extends TestCase {
	use Creates_Admin_Delivery_Container;
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$_GET                                 = array();
		$_POST                                = array();
		$_FILES                               = array();
		$_SERVER['REQUEST_METHOD']            = 'GET';
		$GLOBALS['wp_stubs_current_user_can'] = true;
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_post_dispatches_and_redirects_with_flash_without_rendering(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET                      = array( 'page' => 'racketmanager-import' );
		$_POST                     = array(
			'import'    => 'Upload file and import',
			'delimiter' => 'TAB',
			'mode'      => 'fixtures',
		);

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->method( 'pop' )->willReturn( array() );
		$flash_store->expects( self::once() )
			->method( 'set' )
			->with( '10 fixtures imported successfully', false );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::once() )
			->method( 'redirect' )
			->with(
				self::callback(
					static function ( string $url ): bool {
						return str_contains( $url, 'page=racketmanager-import' );
					}
				)
			);

		$renderer = $this->createMock( View_Renderer_Interface::class );
		$renderer->expects( self::never() )->method( 'render' );

		$dispatcher = $this->createMock( Import_Action_Dispatcher_Interface::class );
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $_GET, $_POST, $_FILES )
			->willReturn(
				new Action_Result_DTO(
					message: '10 fixtures imported successfully',
					message_type: Admin_Message_Type::SUCCESS,
				)
			);
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

		$container = $this->create_admin_delivery_container(
			services: array(
				'import_action_dispatcher' => $dispatcher,
			),
			redirector: $redirector,
			renderer: $renderer,
			flash_store: $flash_store,
		);

		$screen = $container->get( Admin_Import::class );
		$screen->display_import_page();
	}

	public function test_get_applies_flash_and_renders_typed_view_model_with_real_template(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET                      = array( 'page' => 'racketmanager-import' );

		$club = Club_Test_Factory::create(
			id: 12,
			name: 'Westcliff Lawn Tennis Club',
		);

		$competition = Competition_Test_Factory::create(
			id: 34,
			name: 'Summer League 2026',
		);

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( self::once() )
			->method( 'get_clubs' )
			->with( array( 'type' => 'affiliated' ) )
			->willReturn( array( $club ) );

		$competition_service = $this->createMock( Competition_Service::class );
		$competition_service->expects( self::once() )
			->method( 'get_all' )
			->willReturn( array( $competition ) );

		$guard = $this->createMock( Action_Guard_Interface::class );
		$guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'import_leagues' );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::once() )
			->method( 'pop' )
			->willReturn(
				array(
					'message'      => 'Previous action completed',
					'message_type' => false,
					'flash_data'   => array(),
				)
			);

		$renderer  = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
		$container = $this->create_admin_delivery_container(
			services: array(
				'club_service'        => $club_service,
				'competition_service' => $competition_service,
				'action_guard'        => $guard,
			),
			redirector: $redirector,
			renderer: $renderer,
			flash_store: $flash_store,
		);

		$screen = $container->get( Admin_Import::class );

		ob_start();
		$screen->display_import_page();
		$html = (string) ob_get_clean();

		self::assertStringContainsString( 'RacketManager Import', $html );
		self::assertStringContainsString( 'Westcliff Lawn Tennis Club', $html );
		self::assertStringContainsString( 'Summer League 2026', $html );
		self::assertStringContainsString( 'Previous action completed', $html );
	}

	public function test_get_capability_failure_renders_permission_denied_alert(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET                      = array( 'page' => 'racketmanager-import' );

		$guard = $this->createMock( Action_Guard_Interface::class );
		$guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'import_leagues' )
			->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'You do not have permission to import leagues' ) );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( self::never() )->method( 'get_clubs' );

		$competition_service = $this->createMock( Competition_Service::class );
		$competition_service->expects( self::never() )->method( 'get_all' );

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$container = $this->create_admin_delivery_container(
			services: array(
				'club_service'        => $club_service,
				'competition_service' => $competition_service,
				'action_guard'        => $guard,
			),
			redirector: $redirector,
		);

		$screen = $container->get( Admin_Import::class );

		ob_start();
		$screen->display_import_page();
		$html = (string) ob_get_clean();

		self::assertStringContainsString( 'You do not have permission to import leagues', $html );
		self::assertStringContainsString( 'alert-danger', $html );
	}
}
