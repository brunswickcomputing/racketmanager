<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

class League_Repository_Sql_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;
	private League_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->db = new Recording_Wpdb();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unit test setup of global wpdb mock.
		$GLOBALS['wpdb'] = new Recording_Wpdb();
		$GLOBALS['wpdb']->prefix = 'SHOULD_NOT_USE_';
		$this->repository = new League_Repository( $this->db );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_constructor_uses_injected_db_not_global(): void {
		$this->db->get_results_return = array();
		$this->repository->query_league_teams( 5, array( 'season' => '2026' ) );

		$this->assertNotEmpty( $this->db->prepared );
		$this->assertEmpty( $GLOBALS['wpdb']->prepared );
	}

	public function test_delete_team_targets_league_teams_table(): void {
		$this->repository->delete_team( 5, 50, '2026' );

		$this->assertStringContainsString( implode( ' ', array( 'DELETE', 'FROM wp_racketmanager_league_teams' ) ), $this->db->last_query );
		$delete = $this->db->executed[ array_key_last( $this->db->executed ) ];
		$this->assertSame( 'wp_racketmanager_league_teams', $delete['table'] );
		$this->assertSame(
			array(
				'team_id'   => 50,
				'league_id' => 5,
				'season'    => '2026',
			),
			$delete['where']
		);
	}

	public function test_withdraw_team_sets_status_w(): void {
		$this->repository->withdraw_team( 5, 50, '2026' );

		$this->assertStringContainsString( implode( ' ', array( 'UPDATE', 'wp_racketmanager_league_teams', 'SET', "`status` = 'W'" ) ), $this->db->last_query );
	}

	public function test_query_league_teams_filters_known_season(): void {
		$this->db->get_results_return = array();
		$this->repository->query_league_teams( 9, array( 'season' => '2026' ) );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'AND A.`season` = %s', $sql );
		$this->assertSame( array( 9, '2026' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	public function test_query_league_teams_skips_season_when_guard_set(): void {
		$this->db->get_results_return = array();
		$this->repository->query_league_teams(
			9,
			array(
				'season'             => 'unknown',
				'skip_season_filter' => true,
			)
		);

		$sql = $this->db->last_sql();
		$this->assertStringNotContainsString( 'AND A.`season`', $sql );
	}

	public function test_query_league_teams_any_and_empty_season(): void {
		$this->db->get_results_return = array();
		$this->repository->query_league_teams( 9, array( 'season' => 'any' ) );
		$this->assertStringContainsString( "AND A.`season` != ''", $this->db->last_sql() );

		$this->repository->query_league_teams(
			9,
			array(
				'season'         => '',
				'current_season' => '2025',
			)
		);
		$this->assertStringContainsString( 'AND A.`season` = %s', $this->db->last_sql() );
		$this->assertSame( '2025', $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'][1] ?? null );
	}

	public function test_query_league_teams_count_path(): void {
		$this->db->get_var_return = '4';
		$count = $this->repository->query_league_teams( 9, array( 'season' => '2026' ), true );
		$this->assertSame( 4, $count );
		$this->assertStringContainsString( 'SELECT COUNT(*)', $this->db->last_sql() );
	}

	public function test_query_players_resolves_util_import(): void {
		$this->db->get_results_return = array();
		$result = $this->repository->query_players( 3, array( 'season' => '2026' ) );
		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'wp_racketmanager_rubber_players', $this->db->last_sql() );
	}

	public function test_add_team_to_league_omits_empty_rank(): void {
		$this->db->get_var_return = null;
		$this->repository->add_team_to_league( 1, 2, '2026', '', 'NT' );
		$insert = $this->db->executed[ array_key_last( $this->db->executed ) ];
		$this->assertArrayNotHasKey( 'rank', $insert['data'] );
		$this->assertArrayNotHasKey( 'status', $insert['data'] );
	}

	public function test_find_names_queries_id_and_title_as_name_ordered_by_id(): void {
		$expected_rows = array(
			(object) array( 'id' => 1, 'name' => 'Premier Division' ),
			(object) array( 'id' => 2, 'name' => 'Division 1' ),
		);
		$this->db->get_results_return = $expected_rows;

		$results = $this->repository->find_names();

		$this->assertSame( $expected_rows, $results );
		$this->assertSame(
			implode( ' ', array( 'SELECT `id`, `title` AS `name`', 'FROM wp_racketmanager_leagues', 'ORDER BY `id`' ) ),
			$this->db->last_query
		);
	}

	public function test_find_names_returns_empty_array_when_results_not_array(): void {
		$this->db->get_results_return = null;

		$results = $this->repository->find_names();

		$this->assertSame( array(), $results );
	}

	public function test_find_names_returns_empty_array_without_db(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unit test null-db fallback check.
		unset( $GLOBALS['wpdb'] );
		$repository = new League_Repository( null );

		$this->assertSame( array(), $repository->find_names() );
	}

	public function test_query_matches_season_filter_branches(): void {
		$this->db->get_results_return = array();

		$this->repository->query_matches( 10, array( 'season' => 'all' ) );
		$this->assertStringNotContainsString( 'AND m.`season` = %s', $this->db->last_sql() );

		$this->repository->query_matches( 10, array( 'season' => 'any' ) );
		$this->assertStringNotContainsString( 'AND m.`season` = %s', $this->db->last_sql() );

		$this->repository->query_matches( 10, array( 'season' => '2026', 'skip_season_filter' => true ) );
		$this->assertStringNotContainsString( 'AND m.`season` = %s', $this->db->last_sql() );

		$this->repository->query_matches(
			10,
			array(
				'season'         => '',
				'current_season' => '2025',
			)
		);
		$this->assertStringContainsString( 'AND m.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 10, '2025' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		$this->repository->query_matches( 10, array( 'season' => '2026' ) );
		$this->assertStringContainsString( 'AND m.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 10, '2026' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	public function test_query_matches_status_compare_and_args_order(): void {
		$this->db->get_results_return = array();

		$this->repository->query_matches(
			10,
			array(
				'season'         => '2026',
				'status_value'   => 2,
				'status_compare' => 'not',
			)
		);
		$this->assertStringContainsString( 'AND m.`status` != %d', $this->db->last_sql() );
		$this->assertSame( array( 10, '2026', 2 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		$this->repository->query_matches(
			10,
			array(
				'season'       => '2026',
				'status_value' => 1,
			)
		);
		$this->assertStringContainsString( 'AND m.`status` = %d', $this->db->last_sql() );
		$this->assertSame( array( 10, '2026', 1 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	public function test_query_matches_away_points_filter(): void {
		$this->db->get_results_return = array();

		$this->repository->query_matches( 10, array( 'away_points' => 'null' ) );
		$this->assertStringContainsString( 'AND m.`away_points` IS NULL', $this->db->last_sql() );

		$this->repository->query_matches( 10, array( 'away_points' => 'not_null' ) );
		$this->assertStringContainsString( 'AND m.`away_points` IS NOT NULL', $this->db->last_sql() );

		$this->repository->query_matches( 10, array( 'away_points' => false ) );
		$this->assertStringNotContainsString( 'm.`away_points`', $this->db->last_sql() );
	}

	/**
	 * Test find_all season filtering, NULL event, and missing league handling.
	 */
	public function test_find_all_season_filtering_and_missing_league_handling(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();

		$event_with_season = League_Test_Factory::default_event(
			1,
			null,
			array( '2026' => array( 'name' => '2026' ) )
		);
		$event_other_season = League_Test_Factory::default_event(
			2,
			null,
			array( '2025' => array( 'name' => '2025' ) )
		);

		$league1 = League_Test_Factory::create( 1, 'League 2026', 1, $event_with_season );
		$league2 = League_Test_Factory::create( 2, 'League 2025', 2, $event_other_season );
		$league3 = League_Test_Factory::create( 3, 'League Null Event' );
		$league3->event = null;

		// Cache hydrated entities under leagues group.
		wp_cache_set( 1, $league1, 'leagues' );
		wp_cache_set( 2, $league2, 'leagues' );
		wp_cache_set( 3, $league3, 'leagues' );

		// Database returns IDs 1..4 for find_all query. ID 4 is missing from cache and DB returns null for find_by_id.
		$this->db->get_results_return = array(
			(object) array( 'id' => 1 ),
			(object) array( 'id' => 2 ),
			(object) array( 'id' => 3 ),
			(object) array( 'id' => 4 ),
		);
		$this->db->get_row_return     = null;

		// 1. With season filter '2026': only league1 matches.
		$results_filtered = $this->repository->find_all( array( 'season' => '2026' ) );
		$this->assertCount( 1, $results_filtered );
		$this->assertEquals( $league1, $results_filtered[0] );

		// Clear cache query key so next find_all executes.
		$GLOBALS['wp_stubs_cache']['leagues'] = array();
		wp_cache_set( 1, $league1, 'leagues' );
		wp_cache_set( 2, $league2, 'leagues' );
		wp_cache_set( 3, $league3, 'leagues' );
		$this->db->get_results_return         = array(
			(object) array( 'id' => 1 ),
			(object) array( 'id' => 2 ),
			(object) array( 'id' => 3 ),
			(object) array( 'id' => 4 ),
		);

		// 2. Without season filter: leagues 1..3 returned, missing league 4 skipped.
		$results_all = $this->repository->find_all();
		$this->assertCount( 3, $results_all );
		$this->assertEquals( array( $league1, $league2, $league3 ), $results_all );
	}
}
