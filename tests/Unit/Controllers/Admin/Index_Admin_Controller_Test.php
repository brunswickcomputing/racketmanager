<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Index_Admin_Controller;
use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Summary_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Application\Admin\Ports\Index_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Index_Admin_Controller_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Competition_Service $competition_service;
	private Index_Action_Dispatcher_Interface $dispatcher;
	private Action_Guard_Interface $guard;
	private Index_Admin_Controller $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->competition_service = $this->createMock( Competition_Service::class );
		$this->dispatcher          = $this->createMock( Index_Action_Dispatcher_Interface::class );
		$this->guard               = $this->createMock( Action_Guard_Interface::class );

		$this->controller = new Index_Admin_Controller(
			$this->competition_service,
			$this->dispatcher,
			$this->guard
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_capability_check_fails_on_get(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'view_leagues' )
			->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

		$this->expectException( Invalid_Status_Exception::class );

		$this->controller->index_page( array(), array() );
	}

	public function test_get_returns_view_model_with_competitions_and_filters(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'view_leagues' );

		$competition = new Competition_Summary_DTO(
			array(
				'id'           => 10,
				'name'         => 'Winter League',
				'age_group'    => 'senior',
				'type'         => 'league',
				'season_count' => 1,
				'event_count'  => 2,
			)
		);

		$this->competition_service->expects( self::once() )
			->method( 'find_competitions_with_summary' )
			->with( 'senior', null )
			->willReturn( array( $competition ) );

		$result = $this->controller->index_page(
			array(
				'page'      => 'racketmanager',
				'age_group' => 'senior',
			),
			array()
		);

		self::assertArrayHasKey( 'view_model', $result );
		self::assertInstanceOf( Index_Page_View_Model::class, $result['view_model'] );
		self::assertCount( 1, $result['view_model']->competitions );
		self::assertSame( 'senior', $result['view_model']->age_group_select );
		self::assertNull( $result['view_model']->competition_name );
		self::assertNull( $result['view_model']->type );
		self::assertNull( $result['view_model']->age_group );
		self::assertSame( array(), $result['view_model']->errors->all() );
	}

	public function test_get_preserves_flash_data(): void {
		$this->guard->expects( self::once() )
			->method( 'assert_capability' )
			->with( 'view_leagues' );

		$this->competition_service->method( 'find_competitions_with_summary' )
			->willReturn( array() );

		$flash_data = array(
			'errors'           => array( 'competition_name' => 'Name is required' ),
			'competition_name' => 'Draft Competition',
			'type'             => 'cup',
			'age_group'        => 'veterans',
		);

		$result = $this->controller->index_page(
			array( 'page' => 'racketmanager' ),
			array(),
			$flash_data
		);

		self::assertArrayHasKey( 'view_model', $result );
		self::assertSame( 'Draft Competition', $result['view_model']->competition_name );
		self::assertSame( 'cup', $result['view_model']->type );
		self::assertSame( 'veterans', $result['view_model']->age_group );
		self::assertTrue( $result['view_model']->errors->has( 'competition_name' ) );
		self::assertSame( 'Name is required', $result['view_model']->errors->message( 'competition_name' ) );
	}

	public function test_post_add_competition_dispatches_and_redirects(): void {
		$query = array(
			'page'      => 'racketmanager',
			'age_group' => 'junior',
		);
		$post  = array(
			'addCompetition'   => '1',
			'competition_name' => 'Junior League',
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post )
			->willReturn(
				new Action_Result_DTO(
					message: 'Competition added',
					message_type: Admin_Message_Type::SUCCESS,
				)
			);

		$result = $this->controller->index_page( $query, $post );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'page=racketmanager', $result['redirect'] );
		self::assertStringContainsString( 'age_group=junior', $result['redirect'] );
		self::assertSame( 'Competition added', $result['message'] );
		self::assertFalse( $result['message_type'] );
	}

	public function test_post_delete_competition_dispatches_and_redirects(): void {
		$query = array( 'page' => 'racketmanager' );
		$post  = array(
			'doCompDel'   => '1',
			'competition' => array( '5' ),
		);

		$this->dispatcher->expects( self::once() )
			->method( 'handle' )
			->with( $query, $post )
			->willReturn(
				new Action_Result_DTO(
					message: 'Competition deleted',
					message_type: Admin_Message_Type::SUCCESS,
				)
			);

		$result = $this->controller->index_page( $query, $post );

		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'page=racketmanager', $result['redirect'] );
		self::assertSame( 'Competition deleted', $result['message'] );
		self::assertFalse( $result['message_type'] );
	}
}
