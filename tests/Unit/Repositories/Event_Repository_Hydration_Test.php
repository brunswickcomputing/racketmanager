<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

/**
 * Events loaded from the database must be hydrated with their competition.
 */
final class Event_Repository_Hydration_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->db = new Recording_Wpdb();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function make_row( int $id, int $competition_id ): object {
		return (object) array(
			'id'             => $id,
			'name'           => 'Hydrated Event ' . $id,
			'type'           => 'MD',
			'num_sets'       => 3,
			'num_rubbers'    => 1,
			'settings'       => '',
			'seasons'        => '[]',
			'competition_id' => $competition_id,
		);
	}

	private function make_competition(): Competition {
		$competition           = $this->createStub( Competition::class );
		$competition->id       = 9;
		$competition->settings = new Competition_Settings( array( 'mode' => 'championship' ) );
		$competition->type     = Competition_Type::TOURNAMENT;
		return $competition;
	}

	public function test_find_by_id_attaches_competition_from_injected_repository(): void {
		$competition            = $this->make_competition();
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 9 )
			->willReturn( $competition );

		$this->db->get_row_return = $this->make_row( 9101, 9 );
		$this->db->get_var_return = '2';

		$repository = new Event_Repository( $this->db, $competition_repository );
		$event      = $repository->find_by_id( 9101 );

		$this->assertNotNull( $event );
		$this->assertSame( $competition, $event->competition );
		$this->assertTrue( $event->is_championship );
		$this->assertSame( 2, $event->num_leagues );
	}

	public function test_find_by_name_attaches_competition_from_injected_repository(): void {
		$competition            = $this->make_competition();
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 9 )
			->willReturn( $competition );

		$this->db->get_row_return = $this->make_row( 9102, 9 );
		$this->db->get_var_return = '1';

		$repository = new Event_Repository( $this->db, $competition_repository );
		$event      = $repository->find_by_name( 'Hydrated Event 9102' );

		$this->assertNotNull( $event );
		$this->assertSame( $competition, $event->competition );
		$this->assertSame( 1, $event->num_leagues );
	}

	public function test_event_is_loaded_without_competition_when_competition_not_found(): void {
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )->method( 'find_by_id' )->with( 77 )->willReturn( null );

		$this->db->get_row_return = $this->make_row( 9103, 77 );
		$this->db->get_var_return = '0';

		$repository = new Event_Repository( $this->db, $competition_repository );
		$event      = $repository->find_by_id( 9103 );

		$this->assertNotNull( $event );
		$this->assertNull( $event->competition );
	}

	public function test_find_all_attaches_competition_from_injected_repository(): void {
		$competition            = $this->make_competition();
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 9 )
			->willReturn( $competition );

		$this->db->get_results_return = array( $this->make_row( 9104, 9 ) );

		$repository = new Event_Repository( $this->db, $competition_repository );
		$events     = $repository->find_all();

		$this->assertCount( 1, $events );
		$this->assertSame( $competition, $events[0]->competition );
	}

	public function test_find_by_competition_id_attaches_competition_from_injected_repository(): void {
		$competition            = $this->make_competition();
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 9 )
			->willReturn( $competition );

		$this->db->get_results_return = array( $this->make_row( 9105, 9 ) );

		$repository = new Event_Repository( $this->db, $competition_repository );
		$events     = $repository->find_by_competition_id( 9 );

		$this->assertCount( 1, $events );
		$this->assertSame( $competition, $events[0]->competition );
	}

	public function test_tournament_repository_find_event_for_tournament_attaches_competition(): void {
		$competition         = $this->make_competition();
		$competition_service = $this->createMock( Competition_Service::class );
		$competition_service->expects( $this->once() )
			->method( 'get_by_id' )
			->with( 9 )
			->willReturn( $competition );

		$wpdb = $this->createMock( \wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnArgument( 0 );
		$wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $this->make_row( 9106, 9 ) );

		$repository = new Tournament_Repository(
			db: $wpdb,
			competition_service: static fn() => $competition_service
		);
		$event = $repository->find_event_for_tournament( 12, 9106 );

		$this->assertNotNull( $event );
		$this->assertSame( $competition, $event->competition );
	}
}
