<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Championship;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Application\Admin\Ports\Admin_Redirector_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Championship_Test extends TestCase {
	use Creates_Admin_Delivery_Container;
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_current_user_can'] = true;
		$_GET                                 = array();
		$_POST                                = array();
		$_SERVER['REQUEST_METHOD']            = 'GET';
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_handle_championship_admin_page_returns_tab_on_get_without_redirecting(): void {
		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::never() )->method( 'set' );

		$container = $this->create_admin_delivery_container(
			redirector: $redirector,
			flash_store: $flash_store,
		);

		$admin  = $container->get( Admin_Championship::class );
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$tab = $admin->handle_championship_admin_page( $league );

		self::assertSame( 'finalResults', $tab );
	}

	public function test_handle_display_calls_handle_championship_admin_page(): void {
		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::never() )->method( 'redirect' );

		$container = $this->create_admin_delivery_container(
			redirector: $redirector,
		);

		$admin = $container->get( Admin_Championship::class );

		ob_start();
		$admin->handle_display();
		$output = ob_get_clean();

		self::assertStringContainsString( 'League not found', $output );
	}

	public function test_handle_championship_admin_page_redirects_and_flashes_on_start_finals(): void {
		$post                      = array(
			'action'                      => 'startFinals',
			'racketmanager_proceed_nonce' => 'valid_nonce',
		);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = $post;
		$_REQUEST                  = $post;

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::once() )
			->method( 'redirect' )
			->with( self::stringContains( 'tab=preliminary' ) );

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::once() )
			->method( 'set' )
			->with( 'First round not started', true );

		$container = $this->create_admin_delivery_container(
			redirector: $redirector,
			flash_store: $flash_store,
		);

		$admin  = $container->get( Admin_Championship::class );
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$tab = $admin->handle_championship_admin_page( $league );

		self::assertSame( 'preliminary', $tab );
	}

	public function test_handle_championship_admin_page_redirects_and_flashes_error_when_manager_not_found(): void {
		$post                      = array(
			'action'              => 'updateFinalResults',
			'racketmanager_nonce' => 'valid_nonce',
			'custom'              => array(),
			'matches'             => array(),
			'home_points'         => array(),
			'away_points'         => array(),
		);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = $post;
		$_REQUEST                  = $post;

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::once() )
			->method( 'redirect' )
			->with( self::stringContains( 'tab=finalResults' ) );

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::once() )
			->method( 'set' )
			->with( 'Championship manager not found', true );

		$container = $this->create_admin_delivery_container(
			redirector: $redirector,
			flash_store: $flash_store,
		);

		$admin  = $container->get( Admin_Championship::class );
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$tab = $admin->handle_championship_admin_page( $league );

		self::assertSame( 'finalResults', $tab );
	}

	public function test_handle_championship_admin_page_updates_results_and_redirects_when_manager_present(): void {
		$post                      = array(
			'action'              => 'updateFinalResults',
			'racketmanager_nonce' => 'valid_nonce',
			'custom'              => array(),
			'matches'             => array( 101, 102 ),
			'home_points'         => array( 101 => 5, 102 => 4 ),
			'away_points'         => array( 101 => 1, 102 => 2 ),
			'round'               => 2,
		);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = $post;
		$_REQUEST                  = $post;

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$fixture_repo->method( 'find_by_id' )->willReturn( ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor() );

		$manager = new Championship_Manager(
			$this->createStub( Result_Service::class ),
			$fixture_repo,
			$this->createStub( League_Service::class ),
			$this->createStub( Event_Repository_Interface::class ),
			$this->createStub( League_Query_Service::class )
		);

		$redirector = $this->createMock( Admin_Redirector_Interface::class );
		$redirector->expects( self::once() )
			->method( 'redirect' )
			->with( self::stringContains( 'tab=finalResults' ) );

		$flash_store = $this->createMock( Admin_Flash_Message_Store::class );
		$flash_store->expects( self::once() )
			->method( 'set' )
			->with( 'Updated Results of 2 fixtures', false );

		$container = $this->create_admin_delivery_container(
			services: array(
				Championship_Manager::class => $manager,
			),
			redirector: $redirector,
			flash_store: $flash_store,
		);

		$championship = $this->createStub( Championship::class );
		$championship->method( 'num_rounds' )->willReturn( 1 );

		$league               = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->championship = $championship;

		$admin = $container->get( Admin_Championship::class );
		$tab   = $admin->handle_championship_admin_page( $league );

		self::assertSame( 'finalResults', $tab );
	}
}
