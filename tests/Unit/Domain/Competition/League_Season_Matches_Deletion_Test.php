<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

/**
 * Destructive / bulk fixture operations on a real League must reach every fixture: all pages, the right
 * seasons and rounds, regardless of the League's mutable match query state, and never from the query cache.
 */
class League_Season_Matches_Deletion_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wpdb'] = new \wpdb();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function make_event(): Event {
		return Event::from_object(
			(object) array(
				'id'             => 10,
				'name'           => 'Men Doubles',
				'type'           => 'MD',
				'num_sets'       => 3,
				'num_rubbers'    => 2,
				'settings'       => '',
				'seasons'        => wp_json_encode(
					array(
						'2025' => array( 'name' => '2025' ),
						'2026' => array( 'name' => '2026' ),
					)
				),
				'competition_id' => 10,
			)
		);
	}

	private function make_league( int $id, bool $with_event = false ): League {
		$league_raw = (object) array(
			'id'       => $id,
			'title'    => 'Division ' . $id,
			'event_id' => 10,
			'settings' => array(),
			'seasons'  => array(
				'2025' => array( 'name' => '2025' ),
				'2026' => array( 'name' => '2026' ),
			),
		);

		$league                       = $with_event ? League::from_object( $league_raw, $this->make_event() ) : League::from_object( $league_raw );
		$league->num_matches_per_page = 30;

		return $league;
	}

	private function make_league_service(
		League_Repository_Interface $league_repo,
		?Fixture_Repository_Interface $fixture_repo = null,
		?Fixture_Result_Manager $fixture_result_manager = null,
		?Standings_Service $standings_service = null
	): League_Service {
		return new League_Service(
			$this->createStub( RacketManager::class ),
			$league_repo,
			$this->createStub( Event_Repository_Interface::class ),
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Team_Repository_Interface::class ),
			$fixture_repo,
			$fixture_result_manager,
			$standings_service
		);
	}

	/**
	 * Simulate a League whose match query state was left dirty by an earlier read in the same request.
	 */
	private function dirty_match_query_state( League $league ): void {
		$league->set_match_query_arg( 'match_day', 3 );
		$league->set_match_query_arg( 'withdrawn', false );
		$league->set_match_query_arg( 'time', 'next' );
		$league->set_match_query_arg( 'home_team', '99' );
	}

	/**
	 * @param array $args
	 */
	private function assert_clean_unpaginated_uncached_query( array $args ): void {
		$this->assertSame( 0, $args['limit'] );
		$this->assertSame( 0, $args['offset'] );
		$this->assertFalse( $args['cache'] );
		$this->assertSame( -1, $args['match_day'] );
		$this->assertTrue( $args['withdrawn'] );
		$this->assertArrayNotHasKey( 'time', $args );
		$this->assertArrayNotHasKey( 'home_team', $args );
		$this->assertArrayNotHasKey( 'reset_query_args', $args );
	}

	/**
	 * @return list<object>
	 */
	private function rows( int $count, int $first_id ): array {
		$rows = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$rows[] = (object) array( 'id' => $first_id + $i );
		}
		return $rows;
	}

	public function test_delete_removes_fixtures_from_every_season_and_round_before_removing_league_rows(): void {
		$_GET['match_paged'] = '2';

		$league = $this->make_league( 15, true );
		$this->assertSame( '2026', $league->current_season['name'] ?? null, 'Precondition: current season would have been the implicit filter.' );
		$this->dirty_match_query_state( $league );

		// 40 fixtures from 2025 (incl. finals) and 32 from 2026: more than two pages across two seasons.
		$rows = array();
		for ( $i = 1; $i <= 40; $i++ ) {
			$rows[] = (object) array( 'id' => 5000 + $i, 'season' => '2025', 'final' => $i > 36 ? 'final' : '' );
		}
		for ( $i = 1; $i <= 32; $i++ ) {
			$rows[] = (object) array( 'id' => 6000 + $i, 'season' => '2026', 'final' => '' );
		}

		$calls         = array();
		$captured_args = null;
		$league_repo   = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )
			->method( 'query_matches' )
			->with( 15, $this->anything(), false )
			->willReturnCallback(
				static function ( int $league_id, array $args ) use ( &$captured_args, $rows ): array {
					$captured_args = $args;
					return $rows;
				}
			);
		$league_repo->expects( $this->once() )
			->method( 'delete_league_teams' )
			->with( 15 )
			->willReturnCallback(
				static function () use ( &$calls ): bool {
					$calls[] = 'delete_league_teams';
					return true;
				}
			);
		$league_repo->expects( $this->once() )
			->method( 'delete' )
			->with( 15 )
			->willReturnCallback(
				static function () use ( &$calls ): bool {
					$calls[] = 'delete_league';
					return true;
				}
			);

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->never() )->method( 'find_by_id' );
		$fixture_repo->expects( $this->exactly( 72 ) )
			->method( 'delete' )
			->willReturnCallback(
				static function ( int $fixture_id ) use ( &$calls ): bool {
					$calls[] = $fixture_id;
					return true;
				}
			);

		$this->make_league_service( $league_repo, $fixture_repo )->delete_league( $league );

		$this->assertIsArray( $captured_args );
		$this->assertSame( 'any', $captured_args['season'] );
		$this->assertSame( 'all', $captured_args['final'] );
		$this->assert_clean_unpaginated_uncached_query( $captured_args );

		$expected_ids = array_map( static fn( object $row ): int => $row->id, $rows );
		$this->assertSame( array_merge( $expected_ids, array( 'delete_league_teams', 'delete_league' ) ), $calls );
	}

	public function test_delete_throws_before_removing_league_rows_when_fixtures_exist_without_fixture_repository(): void {
		$league = $this->make_league( 16 );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )->method( 'query_matches' )->willReturn( $this->rows( 3, 100 ) );
		$league_repo->expects( $this->never() )->method( 'delete_league_teams' );
		$league_repo->expects( $this->never() )->method( 'delete' );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot delete League: Fixture_Repository_Interface collaborator is required.' );

		$this->make_league_service( $league_repo )->delete_league( $league );
	}

	public function test_delete_season_matches_deletes_every_fixture_of_the_season_beyond_the_first_page(): void {
		$_GET['match_paged'] = '2';

		$league = $this->make_league( 15 );
		$this->dirty_match_query_state( $league );

		$rows = $this->rows( 45, 1001 );
		// duplicates and unusable rows must not produce extra / bogus deletes.
		$rows[] = (object) array( 'id' => 1001 );
		$rows[] = (object) array( 'id' => 0 );
		$rows[] = new stdClass();

		$captured_args = null;
		$league_repo   = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )
			->method( 'query_matches' )
			->with( 15, $this->anything(), false )
			->willReturnCallback(
				static function ( int $league_id, array $args ) use ( &$captured_args, $rows ): array {
					$captured_args = $args;
					return $rows;
				}
			);

		$deleted_ids  = array();
		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 45 ) )
			->method( 'delete' )
			->willReturnCallback(
				static function ( int $fixture_id ) use ( &$deleted_ids ): bool {
					$deleted_ids[] = $fixture_id;
					return true;
				}
			);

		$this->make_league_service( $league_repo, $fixture_repo )->delete_season_fixtures( $league, '2026' );

		$this->assertSame( '2026', $captured_args['season'] );
		$this->assertSame( 'all', $captured_args['final'] );
		$this->assert_clean_unpaginated_uncached_query( $captured_args );
		$this->assertSame( range( 1001, 1045 ), $deleted_ids );
	}

	public function test_delete_season_matches_with_wired_collaborators_deletes_all_matches(): void {
		$_GET['match_paged'] = '3';

		$league = $this->make_league( 22 );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				22,
				$this->callback(
					static fn( array $args ): bool => 0 === $args['limit']
						&& 0 === $args['offset']
						&& false === $args['cache']
						&& '2025' === $args['season']
						&& 'all' === $args['final']
				),
				false
			)
			->willReturn( $this->rows( 35, 2001 ) );

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 35 ) )
			->method( 'delete' )
			->willReturn( true );

		$this->make_league_service( $league_repo, $fixture_repo )->delete_season_fixtures( $league, 2025 );
	}

	public function test_delete_team_deletes_every_fixture_of_the_team_beyond_the_first_page(): void {
		$_GET['match_paged'] = '2';

		$league = $this->make_league( 18 );
		$this->dirty_match_query_state( $league );

		$captured_args = null;
		$rows          = $this->rows( 40, 3001 );
		$league_repo   = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )
			->method( 'query_matches' )
			->with( 18, $this->anything(), false )
			->willReturnCallback(
				static function ( int $league_id, array $args ) use ( &$captured_args, $rows ): array {
					$captured_args = $args;
					return $rows;
				}
			);
		$league_repo->expects( $this->once() )
			->method( 'delete_team' )
			->with( 18, 50, '2026' )
			->willReturn( true );

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 40 ) )
			->method( 'delete' )
			->willReturn( true );

		$this->make_league_service( $league_repo, $fixture_repo )->delete_team( $league, 50, '2026' );

		$this->assertSame( 50, $captured_args['team_id'] );
		$this->assertSame( '2026', $captured_args['season'] );
		$this->assertSame( 'all', $captured_args['final'] );
		$this->assert_clean_unpaginated_uncached_query( $captured_args );
	}

	public function test_withdraw_team_cancels_every_fixture_of_the_team_beyond_the_first_page(): void {
		$_GET['match_paged'] = '2';

		$league = $this->make_league( 19 );
		$this->assertFalse( $league->is_championship );
		$this->dirty_match_query_state( $league );

		$captured_args = null;
		$rows          = $this->rows( 40, 4001 );
		$league_repo   = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )
			->method( 'query_matches' )
			->with( 19, $this->anything(), false )
			->willReturnCallback(
				static function ( int $league_id, array $args ) use ( &$captured_args, $rows ): array {
					$captured_args = $args;
					return $rows;
				}
			);
		$league_repo->expects( $this->once() )
			->method( 'withdraw_team' )
			->with( 19, 50, '2026' )
			->willReturn( true );

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 40 ) )
			->method( 'find_by_id' )
			->willReturnCallback(
				static fn( int $id ): Fixture => Fixture_Factory::from_array(
					array(
						'id'        => $id,
						'home_team' => '50',
						'away_team' => '60',
						'status'    => 0,
					)
				)
			);
		$fixture_repo->expects( $this->never() )->method( 'delete' );

		$confirmed_ids          = array();
		$response               = $this->createStub( Fixture_Update_Response::class );
		$fixture_result_manager = $this->createMock( Fixture_Result_Manager::class );
		$fixture_result_manager->expects( $this->exactly( 40 ) )
			->method( 'confirm_result' )
			->willReturnCallback(
				static function ( Fixture $fixture ) use ( &$confirmed_ids, $response ): Fixture_Update_Response {
					$confirmed_ids[] = $fixture->get_id();
					return $response;
				}
			);

		$standings_service = $this->createMock( Standings_Service::class );
		$standings_service->expects( $this->once() )
			->method( 'update_standings' )
			->with( $league, '2026' );

		$this->make_league_service( $league_repo, $fixture_repo, $fixture_result_manager, $standings_service )->withdraw_team( $league, 50, '2026' );

		$this->assertSame( 50, $captured_args['team_id'] );
		$this->assertSame( '2026', $captured_args['season'] );
		// regular-round semantics are preserved for non-championship leagues.
		$this->assertSame( '', $captured_args['final'] );
		$this->assertFalse( $captured_args['is_championship'] );
		$this->assertSame( 0, $captured_args['limit'] );
		$this->assertSame( 0, $captured_args['offset'] );
		$this->assertFalse( $captured_args['cache'] );
		$this->assertSame( -1, $captured_args['match_day'] );
		$this->assertTrue( $captured_args['withdrawn'] );
		$this->assertSame( range( 4001, 4040 ), $confirmed_ids );
	}

	public function test_delete_season_matches_ignores_stale_cached_query_result_with_real_league_repository(): void {
		$league = $this->make_league( 30 );

		$captured_sql = array();
		$db           = $this->createMock( wpdb::class );
		$db->prefix   = 'wp_';
		$db->method( 'prepare' )
			->willReturnCallback(
				static function ( string $query ) use ( &$captured_sql ): string {
					$captured_sql[] = $query;
					return 'PREPARED_MATCHES_SQL';
				}
			);
		$fresh_rows = $this->rows( 33, 7001 );
		$db->expects( $this->once() )
			->method( 'get_results' )
			->with( 'PREPARED_MATCHES_SQL' )
			->willReturn( $fresh_rows );

		// a stale result for the very same prepared statement is already in the object cache.
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();
		wp_cache_set( md5( 'PREPARED_MATCHES_SQL' ), $this->rows( 2, 9001 ), 'matches' );

		$league_repo = new League_Repository( $db );

		$deleted_ids  = array();
		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 33 ) )
			->method( 'delete' )
			->willReturnCallback(
				static function ( int $fixture_id ) use ( &$deleted_ids ): bool {
					$deleted_ids[] = $fixture_id;
					return true;
				}
			);

		$this->make_league_service( $league_repo, $fixture_repo )->delete_season_fixtures( $league, '2026' );

		$this->assertSame( range( 7001, 7033 ), $deleted_ids );
		$this->assertCount( 1, $captured_sql );
		$this->assertStringContainsString( 'm.`season` = %s', $captured_sql[0] );
		$this->assertStringNotContainsString( 'm.`final`', $captured_sql[0] );
		$this->assertStringNotContainsString( 'LIMIT', $captured_sql[0] );
		$this->assertStringNotContainsString( 'match_day', $captured_sql[0] );
		$this->assertStringNotContainsString( "t1.`status` != 'W'", $captured_sql[0] );
	}

	public function test_delete_sql_spans_all_seasons_and_rounds_with_real_league_repository(): void {
		$league = $this->make_league( 31, true );
		$this->dirty_match_query_state( $league );

		$captured_sql = array();
		$db           = $this->createMock( wpdb::class );
		$db->prefix   = 'wp_';
		$db->method( 'prepare' )
			->willReturnCallback(
				static function ( string $query ) use ( &$captured_sql ): string {
					$captured_sql[] = $query;
					return $query;
				}
			);
		$db->expects( $this->once() )->method( 'get_results' )->willReturn( $this->rows( 31, 8001 ) );
		$deleted_tables = array();
		$db->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->willReturnCallback(
				static function ( string $table, array $where ) use ( &$deleted_tables ): int {
					$deleted_tables[] = array( $table, $where );
					return 1;
				}
			);

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->exactly( 31 ) )->method( 'delete' )->willReturn( true );

		$this->make_league_service( new League_Repository( $db ), $fixture_repo )->delete_league( $league );

		$matches_sql = array_values( array_filter( $captured_sql, static fn( string $sql ): bool => str_contains( $sql, 'racketmanager_matches m' ) ) );
		$this->assertCount( 1, $matches_sql );
		$this->assertStringNotContainsString( 'm.`season`', $matches_sql[0] );
		$this->assertStringNotContainsString( 'm.`final`', $matches_sql[0] );
		$this->assertStringNotContainsString( 'LIMIT', $matches_sql[0] );
		$this->assertStringNotContainsString( 'match_day', $matches_sql[0] );
		$this->assertStringNotContainsString( 'home_team` = %d', $matches_sql[0] );
		$this->assertSame(
			array(
				array( 'wp_racketmanager_league_teams', array( 'league_id' => 31 ) ),
				array( 'wp_racketmanager_leagues', array( 'id' => 31 ) ),
			),
			$deleted_tables
		);
	}
}
