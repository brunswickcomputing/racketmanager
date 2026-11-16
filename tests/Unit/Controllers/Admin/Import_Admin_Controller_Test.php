<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Import_Admin_Controller;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Application\Admin\Ports\Import_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class Import_Admin_Controller_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Club_Service $club_service;
	private Competition_Service $competition_service;
	private Import_Action_Dispatcher_Interface $dispatcher;
	private Action_Guard_Interface $guard;
	private Import_Admin_Controller $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->club_service        = $this->createMock( Club_Service::class );
		$this->competition_service = $this->createMock( Competition_Service::class );
		$this->dispatcher          = $this->createMock( Import_Action_Dispatcher_Interface::class );
		$this->guard               = $this->createMock( Action_Guard_Interface::class );

		$this->controller = new Import_Admin_Controller(
			$this->club_service,
			$this->competition_service,
			$this->dispatcher,
			$this->guard
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_get_import_page_renders_view_model(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'import_leagues' );

		$club        = new stdClass();
		$club->id    = 10;
		$competition = new stdClass();
		$competition->id = 20;

		$this->club_service->expects( self::once() )
			->method( 'get_clubs' )
			->with( array( 'type' => 'affiliated' ) )
			->willReturn( array( $club ) );

		$this->competition_service->expects( self::once() )
			->method( 'get_all' )
			->willReturn( array( $competition ) );

		$query  = array( 'page' => 'racketmanager-import' );
		$result = $this->controller->import_page( $query, array() );

		self::assertArrayHasKey( 'view_model', $result );
		self::assertInstanceOf( Import_Page_View_Model::class, $result['view_model'] );
		self::assertSame( array( $club ), $result['view_model']->clubs );
		self::assertSame( array( $competition ), $result['view_model']->competitions );
	}

	public function test_get_import_page_asserts_capability_and_throws(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'import_leagues' )
			->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

		$this->expectException( Invalid_Status_Exception::class );
		$this->expectExceptionMessage( 'Permission denied' );

		$this->controller->import_page( array(), array() );
	}

	public function test_post_mutation_dispatches_and_returns_prg_redirect(): void {
		$query = array( 'page' => 'racketmanager-import' );
		$post  = array(
			'import' => '1',
			'mode'   => 'fixtures',
		);
		$files = array(
			'racketmanager_import' => array( 'name' => 'fixtures.csv' ),
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post, $files )
			->willReturn(
				new Action_Result_DTO(
					message: '5 Fixtures imported',
					message_type: Admin_Message_Type::SUCCESS
				)
			);

		$result = $this->controller->import_page( $query, $post, $files );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'page=racketmanager-import', $result['redirect'] );
		self::assertSame( '5 Fixtures imported', $result['message'] );
		self::assertFalse( $result['message_type'] );
	}

	public function test_post_mutation_failure_returns_error_redirect(): void {
		$query = array( 'page' => 'racketmanager-import' );
		$post  = array(
			'import' => '1',
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post, null )
			->willReturn(
				new Action_Result_DTO(
					message: 'Security token invalid',
					message_type: Admin_Message_Type::ERROR
				)
			);

		$result = $this->controller->import_page( $query, $post );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertSame( 'Security token invalid', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}
}
