<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Championship_Admin_Controller;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class Championship_Admin_Controller_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Fixture_Repository_Interface $fixture_repo;
	private Championship_Manager $championship_manager;
	private League_Service $league_service;
	private Action_Guard_Interface $guard;
	private Championship_Admin_Controller $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$this->fixture_repo->method( 'find_by_id' )->willReturn( ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor() );

		$this->championship_manager = new Championship_Manager(
			$this->createStub( Result_Service::class ),
			$this->fixture_repo,
			$this->createStub( League_Service::class ),
			$this->createStub( Event_Repository_Interface::class ),
			$this->createStub( League_Query_Service::class )
		);
		$this->league_service       = $this->createMock( League_Service::class );
		$this->guard                = $this->createMock( Action_Guard_Interface::class );

		$this->controller = new Championship_Admin_Controller(
			$this->championship_manager,
			$this->league_service,
			$this->guard
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_league_not_found_returns_error_message(): void {
		$this->league_service->expects( self::once() )
			->method( 'get_league' )
			->with( 999 )
			->willReturn( null );

		$league_stub     = new stdClass();
		$league_stub->id = 999;

		$result = $this->controller->handle_championship_page( $league_stub, array(), array() );

		self::assertSame( 'finalResults', $result['tab'] );
		self::assertSame( 'League not found', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}

	public function test_start_finals_guard_failure_returns_error_message(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->guard->expects( self::once() )
			->method( 'assert_allowed' )
			->with( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed', 'update_results' )
			->willThrowException( new Invalid_Status_Exception( 'Invalid proceed nonce' ) );

		$post   = array( 'action' => 'startFinals' );
		$result = $this->controller->handle_championship_page( $league, array(), $post );

		self::assertSame( 'preliminary', $result['tab'] );
		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'tab=preliminary', $result['redirect'] );
		self::assertSame( 'Invalid proceed nonce', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}

	public function test_start_finals_sets_preliminary_tab_and_not_started_message(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->guard->expects( self::once() )
			->method( 'assert_allowed' )
			->with( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed', 'update_results' );

		$post   = array( 'action' => 'startFinals' );
		$result = $this->controller->handle_championship_page( $league, array(), $post );

		self::assertSame( 'preliminary', $result['tab'] );
		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'tab=preliminary', $result['redirect'] );
		self::assertSame( 'First round not started', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}

	public function test_update_final_results_guard_failure_returns_error_message(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->guard->expects( self::once() )
			->method( 'assert_allowed' )
			->with( 'racketmanager_nonce', 'racketmanager_update-finals', 'update_results' )
			->willThrowException( new Invalid_Status_Exception( 'Invalid nonce' ) );

		$post   = array( 'action' => 'updateFinalResults' );
		$result = $this->controller->handle_championship_page( $league, array(), $post );

		self::assertSame( 'finalResults', $result['tab'] );
		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'tab=finalResults', $result['redirect'] );
		self::assertSame( 'Invalid nonce', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}

	public function test_update_final_results_updates_fixtures_via_manager(): void {
		$championship = $this->createStub( Championship::class );
		$championship->method( 'num_rounds' )->willReturn( 1 );

		$league               = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->championship = $championship;

		$this->guard->expects( self::once() )
			->method( 'assert_allowed' )
			->with( 'racketmanager_nonce', 'racketmanager_update-finals', 'update_results' );

		$matches     = array( 101, 102 );
		$home_points = array( 101 => 3, 102 => 4 );
		$away_points = array( 101 => 1, 102 => 2 );
		$custom      = array( 'notes' => 'ok' );

		$post   = array(
			'action'      => 'updateFinalResults',
			'matches'     => $matches,
			'home_points' => $home_points,
			'away_points' => $away_points,
			'custom'      => $custom,
			'round'       => 2,
		);
		$result = $this->controller->handle_championship_page( $league, array(), $post );

		self::assertSame( 'finalResults', $result['tab'] );
		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'tab=finalResults', $result['redirect'] );
		self::assertSame( 'Updated Results of 2 fixtures', $result['message'] );
		self::assertFalse( $result['message_type'] );
	}

	public function test_update_final_results_when_manager_null_returns_error_message(): void {
		$controller = new Championship_Admin_Controller(
			null,
			$this->league_service,
			$this->guard
		);

		$league               = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->championship = $this->createStub( Championship::class );

		$this->guard->expects( self::once() )
			->method( 'assert_allowed' )
			->with( 'racketmanager_nonce', 'racketmanager_update-finals', 'update_results' );

		$post   = array( 'action' => 'updateFinalResults' );
		$result = $controller->handle_championship_page( $league, array(), $post );

		self::assertSame( 'finalResults', $result['tab'] );
		self::assertArrayHasKey( 'redirect', $result );
		self::assertStringContainsString( 'tab=finalResults', $result['redirect'] );
		self::assertSame( 'Championship manager not found', $result['message'] );
		self::assertTrue( $result['message_type'] );
	}
}
