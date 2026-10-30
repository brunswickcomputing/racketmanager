<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

/**
 * League_Repository hands its (lazy) League_Query_Service resolver to the Championship_Factory it builds
 * while hydrating a `championship` league, so the consolation "outstanding matches" check keeps working
 * without creating a container cycle (league_query_service → league_repository → league_query_service).
 */
final class League_Repository_Championship_Factory_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_deleted'] = array();
		$GLOBALS['wp_stubs_cache']         = array();
		$this->db                          = new Recording_Wpdb();
		$GLOBALS['wpdb']                   = $this->db;
	}

	protected function tearDown(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = false;
		$GLOBALS['wp_stubs_cache_deleted'] = array();
		$GLOBALS['wp_stubs_cache']         = array();
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Seed a cached primary league (id 1) that already carries a 16-team championship (first round 'last-16').
	 */
	private function seed_primary_league(): League {
		$source = (object) array(
			'id'              => 1,
			'num_teams_total' => 16,
			'current_season'  => array( 'name' => '2026', 'num_match_days' => 4 ),
			'event'           => (object) array(
				'primary_league' => 1,
				'competition'    => (object) array( 'is_active' => true, 'is_complete' => false ),
			),
		);
		$primary               = $this->createStub( League::class );
		$primary->id           = 1;
		$primary->championship = ( new Championship_Factory() )->create( $source, new Championship_Settings() );
		wp_cache_set( 1, $primary, 'leagues' );
		return $primary;
	}

	private function consolation_row(): object {
		return (object) array(
			'id'       => 2,
			'title'    => 'Consolation Draw',
			'event_id' => 30,
			'seasons'  => wp_json_encode( array( '2026' => array( 'name' => '2026', 'num_match_days' => 3 ) ) ),
		);
	}

	private function event_repository(): Event_Repository_Interface {
		// League mode is derived from the competition settings; 'championship' makes the repository build a Championship.
		$competition = new Competition( 'Club Cup', 'cup', 'open', null, array( 'mode' => 'championship' ), 5 );
		$event       = Event::from_object(
			(object) array(
				'id'             => 30,
				'name'           => 'Mens Singles',
				'type'           => 'singles',
				'settings'       => array( 'primary_league' => 1 ),
				'seasons'        => wp_json_encode( array( '2026' => array( 'name' => '2026', 'num_match_days' => 3 ) ) ),
			),
			$competition
		);
		$repo  = $this->createStub( Event_Repository_Interface::class );
		$repo->method( 'find_by_id' )->willReturn( $event );
		return $repo;
	}

	public function test_find_by_id_forwards_lazy_league_query_resolver_to_championship_factory(): void {
		$primary = $this->seed_primary_league();

		$query_service = $this->createMock( League_Query_Service::class );
		$query_service->expects( $this->once() )
			->method( 'get_matches' )
			->with( $primary, $this->callback( fn( array $args ): bool => true === ( $args['pending'] ?? null ) && 'last-16' === ( $args['final'] ?? null ) ) )
			->willReturn( 2 );

		$resolutions = 0;
		$repository  = new League_Repository(
			$this->db,
			$this->event_repository(),
			function () use ( &$resolutions, $query_service ): League_Query_Service {
				++$resolutions;
				return $query_service;
			}
		);
		$this->assertSame( 0, $resolutions, 'resolver must stay lazy until a championship league is hydrated' );

		$this->db->get_row_return = $this->consolation_row();
		$this->db->get_var_return = '2';

		$league = $repository->find_by_id( 2 );

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 1, $resolutions );
		$this->assertTrue( $league->championship->is_consolation );
		// Outstanding primary matches => draw sized to the primary's max rounds with zero teams.
		$this->assertSame( 0, $league->championship->num_teams );
		$this->assertSame( 3, $league->championship->num_rounds );
	}

	public function test_find_by_id_without_resolver_sizes_consolation_from_team_count(): void {
		$this->seed_primary_league();

		$repository               = new League_Repository( $this->db, $this->event_repository() );
		$this->db->get_row_return = $this->consolation_row();
		$this->db->get_var_return = '2';

		$league = $repository->find_by_id( 2 );

		$this->assertInstanceOf( League::class, $league );
		$this->assertTrue( $league->championship->is_consolation );
		$this->assertSame( 2, $league->championship->num_teams );
		$this->assertSame( 1, $league->championship->num_rounds );
	}
}
