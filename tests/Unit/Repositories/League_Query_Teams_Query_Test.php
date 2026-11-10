<?php
/**
 * League teams query collaborator unit tests.
 *
 * @package Racketmanager\Tests\Unit\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\League_Query_Teams_Query;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

/**
 * Unit tests for League_Query_Teams_Query.
 */
class League_Query_Teams_Query_Test extends TestCase {
	use Manages_Wordpress_Globals;

	/**
	 * Recording database mock.
	 *
	 * @var Recording_Wpdb
	 */
	private Recording_Wpdb $db;

	/**
	 * Query instance under test.
	 *
	 * @var League_Query_Teams_Query
	 */
	private League_Query_Teams_Query $query;

	/**
	 * Set up test environment before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();

		$this->db    = new Recording_Wpdb();
		$this->query = new League_Query_Teams_Query( $this->db );
	}

	/**
	 * Tear down test environment after each test.
	 */
	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Test query without database object returns empty results or zero.
	 */
	public function test_execute_without_database_returns_empty_or_zero(): void {
		$null_query = new League_Query_Teams_Query( null );

		$this->assertSame( array(), $null_query->execute( 1, array() ) );
		$this->assertSame( 0, $null_query->execute( 1, array(), true ) );
	}

	/**
	 * Test default select query structure with league ID and default table joins.
	 */
	public function test_execute_select_default_projection_and_clause(): void {
		$this->db->get_results_return = array( (object) array( 'id' => 10 ) );

		$results = $this->query->execute( 7, array( 'skip_season_filter' => true ) );

		$this->assertCount( 1, $results );
		$sql = $this->db->last_sql();
		$this->assertStringStartsWith( 'SELECT B.`id` AS `id`', $sql );
		$this->assertStringContainsString( 'FROM wp_racketmanager_teams B INNER JOIN wp_racketmanager_league_teams A ON B.id = A.team_id WHERE `league_id` = %d', $sql );
		$this->assertSame( array( 7 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test count query structure with league ID.
	 */
	public function test_execute_count_default_projection_and_clause(): void {
		$this->db->get_var_return = '3';

		$count = $this->query->execute( 7, array( 'skip_season_filter' => true ), true );

		$this->assertSame( 3, $count );
		$sql = $this->db->last_sql();
		$this->assertStringStartsWith( 'SELECT COUNT(*)', $sql );
		$this->assertStringContainsString( 'FROM wp_racketmanager_teams B INNER JOIN wp_racketmanager_league_teams A ON B.id = A.team_id WHERE `league_id` = %d', $sql );
		$this->assertSame( array( 7 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test season filter variations.
	 */
	public function test_execute_season_filter_variations(): void {
		$this->db->get_results_return = array();

		// 'skip_season_filter' skips season clause completely.
		$this->query->execute( 7, array( 'skip_season_filter' => true, 'season' => '2026' ) );
		$this->assertStringNotContainsString( 'AND A.`season`', $this->db->last_sql() );

		// Specific season bound.
		$this->query->execute( 7, array( 'season' => '2026' ) );
		$this->assertStringContainsString( 'AND A.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 7, '2026' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// 'any' season checks for non-empty season string.
		$this->query->execute( 7, array( 'season' => 'any' ) );
		$this->assertStringContainsString( "AND A.`season` != ''", $this->db->last_sql() );

		// Empty season uses current_season fallback.
		$this->query->execute( 7, array( 'season' => '', 'current_season' => '2025' ) );
		$this->assertStringContainsString( 'AND A.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 7, '2025' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test status and profile filter variations.
	 */
	public function test_execute_status_filter_variations(): void {
		$this->db->get_results_return = array();

		// 'active' status filters profile != 3.
		$this->query->execute( 7, array( 'skip_season_filter' => true, 'status' => 'active' ) );
		$this->assertStringContainsString( 'AND A.`profile` != 3', $this->db->last_sql() );

		// 1 status binds profile = %d.
		$this->query->execute( 7, array( 'skip_season_filter' => true, 'status' => 1 ) );
		$this->assertStringContainsString( 'AND A.`profile` = %d', $this->db->last_sql() );
		$this->assertSame( array( 7, 1 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Other status values do not produce a profile clause.
		$this->query->execute( 7, array( 'skip_season_filter' => true, 'status' => 'pending' ) );
		$this->assertStringNotContainsString( 'AND A.`profile`', $this->db->last_sql() );
	}

	/**
	 * Test team filter combinations (rank, home, club, team_name, team_id, active).
	 */
	public function test_execute_team_filter_combinations(): void {
		$this->db->get_results_return = array();

		$this->query->execute(
			7,
			array(
				'skip_season_filter' => true,
				'rank'               => '1',
				'home'               => true,
				'club'               => 12,
				'team_name'          => 'Leigh First',
				'team_id'            => 44,
				'active'             => true,
			)
		);

		$sql  = $this->db->last_sql();
		$args = $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'];

		$this->assertStringContainsString( 'AND A.`rank` = %s', $sql );
		$this->assertStringContainsString( 'AND B.`home` = 1', $sql );
		$this->assertStringContainsString( 'AND B.`club_id` = %d', $sql );
		$this->assertStringContainsString( 'AND B.`title` = %s', $sql );
		$this->assertStringContainsString( 'AND B.`id` = %d', $sql );
		$this->assertStringContainsString( "AND A.`status` != 'W'", $sql );

		$this->assertSame( array( 7, '1', 12, 'Leigh First', 44 ), $args );
	}

	/**
	 * Test order by handling.
	 */
	public function test_execute_orderby_variations(): void {
		$this->db->get_results_return = array();

		$this->query->execute(
			7,
			array(
				'skip_season_filter' => true,
				'orderby'            => array(
					'rank'  => 'DESC',
					'title' => 'ASC',
				),
			)
		);

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'ORDER BY `rank` DESC,`title` ASC', $sql );
	}

	/**
	 * Test that select queries use raw prepared SQL as cache key in leaguetable group.
	 */
	public function test_execute_select_uses_raw_sql_as_cache_key_in_leaguetable_group(): void {
		$expected_row                 = (object) array( 'id' => 10, 'title' => 'Westcliff' );
		$this->db->get_results_return = array( $expected_row );

		// Initial query: cache miss executes database call and caches under raw prepared SQL.
		$results1 = $this->query->execute( 7, array( 'skip_season_filter' => true ) );
		$this->assertSame( array( $expected_row ), $results1 );

		$prepared_sql = $this->db->last_sql();
		$this->assertArrayHasKey( 'leaguetable', $GLOBALS['wp_stubs_cache'] );
		$this->assertArrayHasKey( $prepared_sql, $GLOBALS['wp_stubs_cache']['leaguetable'] );
		$this->assertArrayNotHasKey( md5( $prepared_sql ), $GLOBALS['wp_stubs_cache']['leaguetable'] );

		// Second query: cache hit returns cached results without executing another DB call.
		$new_row                      = (object) array( 'id' => 999 );
		$this->db->get_results_return = array( $new_row );
		$results2                     = $this->query->execute( 7, array( 'skip_season_filter' => true ) );
		$this->assertSame( array( $expected_row ), $results2 );
		$this->assertCount( 1, $this->db->executed );

		// Cache bypass query: cache => false re-queries database.
		$results3 = $this->query->execute( 7, array( 'skip_season_filter' => true, 'cache' => false ) );
		$this->assertSame( array( $new_row ), $results3 );
		$this->assertCount( 2, $this->db->executed );
	}

	/**
	 * Test that count queries use md5(prepared SQL) as cache key in leaguetable group.
	 */
	public function test_execute_count_uses_md5_as_cache_key_in_leaguetable_group(): void {
		$this->db->get_var_return = '8';

		// Initial count query: cache miss executes DB call and caches under md5(prepared SQL).
		$count1 = $this->query->execute( 7, array( 'skip_season_filter' => true ), true );
		$this->assertSame( 8, $count1 );

		$prepared_sql = $this->db->last_sql();
		$cache_key    = md5( $prepared_sql );
		$this->assertArrayHasKey( 'leaguetable', $GLOBALS['wp_stubs_cache'] );
		$this->assertArrayHasKey( $cache_key, $GLOBALS['wp_stubs_cache']['leaguetable'] );
		$this->assertArrayNotHasKey( $prepared_sql, $GLOBALS['wp_stubs_cache']['leaguetable'] );

		// Second count query: cache hit returns cached count without DB call.
		$this->db->get_var_return = '999';
		$count2                   = $this->query->execute( 7, array( 'skip_season_filter' => true ), true );
		$this->assertSame( 8, $count2 );
		$this->assertCount( 1, $this->db->executed );

		// Cache bypass: cache => false re-queries database.
		$count3 = $this->query->execute( 7, array( 'skip_season_filter' => true, 'cache' => false ), true );
		$this->assertSame( 999, $count3 );
		$this->assertCount( 2, $this->db->executed );
	}

	/**
	 * Test empty result and zero count semantics.
	 */
	public function test_execute_empty_results_and_zero_count(): void {
		// When DB returns null for select, execute returns empty array.
		$this->db->get_results_return = null;
		$results                      = $this->query->execute( 7, array( 'skip_season_filter' => true ) );
		$this->assertSame( array(), $results );

		// When DB returns null for count, execute returns 0.
		$this->db->get_var_return = null;
		$count                    = $this->query->execute( 7, array( 'skip_season_filter' => true ), true );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test League_Repository::query_league_teams delegates to query collaborator.
	 */
	public function test_league_repository_query_league_teams_delegates_to_collaborator(): void {
		$expected_row                 = (object) array( 'id' => 10, 'title' => 'Westcliff' );
		$this->db->get_results_return = array( $expected_row );

		$repository = new League_Repository( $this->db );
		$results    = $repository->query_league_teams( 7, array( 'skip_season_filter' => true ) );

		$this->assertSame( array( $expected_row ), $results );
		$this->assertStringContainsString( 'FROM wp_racketmanager_teams B INNER JOIN wp_racketmanager_league_teams A', $this->db->last_sql() );
	}

	/**
	 * Test complex query exact SQL clause structure.
	 */
	public function test_execute_complex_query_exact_sql_byte_equality(): void {
		$this->db->get_results_return = array();

		$this->query->execute(
			4,
			array(
				'season'    => '2026',
				'rank'      => 'A',
				'home'      => 1,
				'status'    => 'active',
				'club'      => 8,
				'team_name' => 'Leigh 2',
				'team_id'   => 15,
				'active'    => true,
				'orderby'   => array( 'title' => 'ASC' ),
			)
		);

		$expected_sql = implode(
			' ',
			array(
				'SELECT B.`id` AS `id`, B.`title`, B.`club_id`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points_2_plus`, A.`points_2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating`',
				'FROM wp_racketmanager_teams B INNER JOIN wp_racketmanager_league_teams A ON B.id = A.team_id',
				'WHERE `league_id` = %d',
				'AND A.`season` = %s',
				'AND A.`rank` = %s',
				'AND B.`home` = 1',
				'AND A.`profile` != 3',
				'AND B.`club_id` = %d',
				'AND B.`title` = %s',
				'AND B.`id` = %d',
				"AND A.`status` != 'W'",
				'ORDER BY `title` ASC',
			)
		);

		$this->assertSame( $expected_sql, $this->db->last_sql() );
		$this->assertSame( array( 4, '2026', 'A', 8, 'Leigh 2', 15 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}
}
