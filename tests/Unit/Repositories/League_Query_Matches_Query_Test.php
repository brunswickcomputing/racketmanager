<?php
/**
 * League match query collaborator unit tests.
 *
 * @package Racketmanager\Tests\Unit\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\League_Query_Matches_Query;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

/**
 * Unit tests for League_Query_Matches_Query.
 */
class League_Query_Matches_Query_Test extends TestCase {
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
	 * @var League_Query_Matches_Query
	 */
	private League_Query_Matches_Query $query;

	/**
	 * Set up test environment before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();

		$this->db    = new Recording_Wpdb();
		$this->query = new League_Query_Matches_Query( $this->db );
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
		$null_query = new League_Query_Matches_Query( null );

		$this->assertSame( array(), $null_query->execute( 1, array() ) );
		$this->assertSame( 0, $null_query->execute( 1, array(), true ) );
	}

	/**
	 * Test default select query structure with league ID and default final filter.
	 */
	public function test_execute_default_select_structure(): void {
		$this->db->get_results_return = array( (object) array( 'id' => 101 ) );

		$results = $this->query->execute( 5, array( 'skip_season_filter' => true, 'withdrawn' => true ) );

		$this->assertCount( 1, $results );
		$sql = $this->db->last_sql();
		$this->assertStringStartsWith( 'SELECT distinct m.*', $sql );
		$this->assertStringContainsString( 'FROM wp_racketmanager_matches m WHERE m.`league_id` = %d', $sql );
		$this->assertStringContainsString( "AND ( m.`final` = '' OR m.`final` IS NULL )", $sql );
		$this->assertSame( array( 5 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test season filter combinations.
	 */
	public function test_execute_season_filter_variations(): void {
		$this->db->get_results_return = array();

		// 'all' season skips filter.
		$this->query->execute( 1, array( 'season' => 'all' ) );
		$this->assertStringNotContainsString( 'm.`season` = %s', $this->db->last_sql() );

		// 'any' season skips filter.
		$this->query->execute( 1, array( 'season' => 'any' ) );
		$this->assertStringNotContainsString( 'm.`season` = %s', $this->db->last_sql() );

		// Specific season bound.
		$this->query->execute( 1, array( 'season' => '2026-summer' ) );
		$this->assertStringContainsString( 'm.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 1, '2026-summer' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Empty season uses current_season fallback.
		$this->query->execute( 1, array( 'season' => '', 'current_season' => '2025-winter' ) );
		$this->assertStringContainsString( 'm.`season` = %s', $this->db->last_sql() );
		$this->assertSame( array( 1, '2025-winter' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test final and championship filter branches.
	 */
	public function test_execute_final_and_championship_filters(): void {
		$this->db->get_results_return = array();

		// Named final round.
		$this->query->execute( 2, array( 'final' => 'Semi-Final', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`final` = %s', $this->db->last_sql() );
		$this->assertSame( array( 2, 'Semi-Final' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// 'all' final skips final condition.
		$this->query->execute( 2, array( 'final' => 'all', 'skip_season_filter' => true ) );
		$this->assertStringNotContainsString( 'm.`final` = %s', $this->db->last_sql() );
		$this->assertStringNotContainsString( 'm.`final` IS NOT NULL', $this->db->last_sql() );

		// Championship league without final name filter.
		$this->query->execute( 2, array( 'is_championship' => true, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`final` IS NOT NULL', $this->db->last_sql() );
	}

	/**
	 * Test team filter combinations: team_id, team_pair, home_team, and away_team.
	 */
	public function test_execute_team_filters(): void {
		$this->db->get_results_return = array();

		// team_id matches either home or away.
		$this->query->execute( 3, array( 'team_id' => 42, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND (m.`home_team` = %d OR m.`away_team` = %d)', $this->db->last_sql() );
		$this->assertSame( array( 3, 42, 42 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// team_pair matches bilateral pairings.
		$this->query->execute( 3, array( 'team_pair' => array( 10, 20 ), 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND ( (`home_team` = %d AND `away_team` = %d) OR (`home_team` = %d AND `away_team` = %d) )', $this->db->last_sql() );
		$this->assertSame( array( 3, 10, 20, 20, 10 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Separate home_team and away_team.
		$this->query->execute( 3, array( 'home_team' => 15, 'away_team' => 25, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`home_team` = %d', $this->db->last_sql() );
		$this->assertStringContainsString( 'AND m.`away_team` = %d', $this->db->last_sql() );
		$this->assertSame( array( 3, 15, 25 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test match day filter for standard and standings table modes.
	 */
	public function test_execute_match_day_filter(): void {
		$this->db->get_results_return = array();

		// Exact match day.
		$this->query->execute( 4, array( 'match_day' => 7, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`match_day` = %d', $this->db->last_sql() );
		$this->assertSame( array( 4, 7 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Standings table cumulative match day.
		$this->query->execute( 4, array( 'match_day' => 7, 'standingstable' => true, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`match_day` <= %d', $this->db->last_sql() );
		$this->assertSame( array( 4, 7 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Match day 0 or negative ignored.
		$this->query->execute( 4, array( 'match_day' => 0, 'skip_season_filter' => true ) );
		$this->assertStringNotContainsString( 'm.`match_day`', $this->db->last_sql() );
	}

	/**
	 * Test points filters: null, not_null, not_empty, and 'latest' time override.
	 */
	public function test_execute_points_filters(): void {
		$this->db->get_results_return = array();

		$this->query->execute( 5, array( 'home_points' => 'null', 'away_points' => 'not_empty', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`home_points` IS NULL', $this->db->last_sql() );
		$this->assertStringContainsString( "AND m.`away_points` != ''", $this->db->last_sql() );

		$this->query->execute( 5, array( 'home_points' => 'not_null', 'away_points' => 'null', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`home_points` IS NOT NULL', $this->db->last_sql() );
		$this->assertStringContainsString( 'AND m.`away_points` IS NULL', $this->db->last_sql() );

		// 'latest' time overrides points to non-empty check.
		$this->query->execute( 5, array( 'time' => 'latest', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( "AND (m.`home_points` != '' OR m.`away_points` != '')", $this->db->last_sql() );
	}

	/**
	 * Test time filters: next, prev, prev1, today, and specific day.
	 */
	public function test_execute_time_and_outcome_filters(): void {
		$this->db->get_results_return = array();

		// Next fixtures.
		$this->query->execute( 6, array( 'time' => 'next', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) >= 0', $this->db->last_sql() );

		// Previous fixtures.
		$this->query->execute( 6, array( 'time' => 'prev', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0', $this->db->last_sql() );

		// Previous completed match with winner.
		$this->query->execute( 6, array( 'time' => 'prev1', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND (TIMESTAMPDIFF(MINUTE, NOW(), m.`date`) < 0 AND m.`winner_id` != 0)', $this->db->last_sql() );

		// Today's fixtures.
		$this->query->execute( 6, array( 'time' => 'today', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND DATEDIFF(NOW(), m.`date`) = 0', $this->db->last_sql() );

		// Specific date.
		$this->query->execute( 6, array( 'time' => 'day', 'match_date' => '2026-06-20', 'skip_season_filter' => true ) );
		$this->assertStringContainsString( "AND DATEDIFF('2026-06-20', m.`date`) = 0", $this->db->last_sql() );

		// Winner and loser ID filters.
		$this->query->execute( 6, array( 'winner_id' => 12, 'loser_id' => 34, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.`winner_id` = %d', $this->db->last_sql() );
		$this->assertStringContainsString( 'AND m.`loser_id` = %d', $this->db->last_sql() );
		$this->assertSame( array( 6, 12, 34 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}

	/**
	 * Test extra filters: confirmed, leg, player joins, withdrawn joins, club subquery, pending, status.
	 */
	public function test_execute_extra_filters(): void {
		$this->db->get_results_return = array();

		// Player join adds rubbers and rubber_players tables.
		$this->query->execute( 7, array( 'player' => 'P123', 'withdrawn' => true, 'skip_season_filter' => true ) );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'wp_racketmanager_rubbers r, wp_racketmanager_rubber_players rp', $sql );
		$this->assertStringContainsString( 'AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = %s', $sql );
		$this->assertSame( array( 7, 'P123' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Active league teams (withdrawn empty by default).
		$this->query->execute( 7, array( 'skip_season_filter' => true ) );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'wp_racketmanager_league_teams t1, wp_racketmanager_league_teams t2', $sql );
		$this->assertStringContainsString( "AND t1.`status` != 'W'", $sql );
		$this->assertStringContainsString( "AND t2.`status` != 'W'", $sql );

		// Confirmed and leg filters.
		$this->query->execute( 7, array( 'confirmed' => true, 'leg' => '1', 'withdrawn' => true, 'skip_season_filter' => true ) );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( "AND m.`confirmed` = 'Y'", $sql );
		$this->assertStringContainsString( 'AND m.`leg` = %s', $sql );

		// Club subquery.
		$this->query->execute( 7, array( 'club' => 99, 'withdrawn' => true, 'skip_season_filter' => true ) );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'WHERE `club_id` = %d', $sql );
		$this->assertSame( array( 7, 99, 99 ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Pending match filter.
		$this->query->execute( 7, array( 'pending' => true, 'withdrawn' => true, 'skip_season_filter' => true ) );
		$this->assertStringContainsString( 'AND m.winner_id = 0', $this->db->last_sql() );
	}

	/**
	 * Test sorting, limit, and offset generation.
	 */
	public function test_execute_ordering_and_pagination(): void {
		$this->db->get_results_return = array();

		// Custom order by and pagination.
		$this->query->execute(
			8,
			array(
				'orderby'            => array( 'date' => 'ASC', 'id' => 'DESC' ),
				'limit'              => 20,
				'offset'             => 40,
				'skip_season_filter' => true,
				'withdrawn'          => true,
			)
		);
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'ORDER BY `date` ASC,`id` DESC', $sql );
		$this->assertStringEndsWith( 'LIMIT 40,20', $sql );

		// 'next' time sets ORDER BY date ASC automatically.
		$this->query->execute( 8, array( 'time' => 'next', 'skip_season_filter' => true, 'withdrawn' => true ) );
		$this->assertStringContainsString( 'ORDER BY `date` ASC', $this->db->last_sql() );

		// 'prev' time sets ORDER BY date DESC automatically.
		$this->query->execute( 8, array( 'time' => 'prev', 'skip_season_filter' => true, 'withdrawn' => true ) );
		$this->assertStringContainsString( 'ORDER BY `date` DESC', $this->db->last_sql() );
	}

	/**
	 * Test count query execution and caching under 'num_matches' group.
	 */
	public function test_execute_count_caches_with_md5_key_in_num_matches_group(): void {
		$this->db->get_var_return = '14';

		$count = $this->query->execute( 9, array( 'season' => '2026' ), true );

		$this->assertSame( 14, $count );
		$sql       = $this->db->last_sql();
		$cache_key = md5( $sql );

		// Verify key is valid 32-char hexadecimal md5 hash.
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $cache_key );

		// Verify stored in wp_stubs_cache under num_matches group and NOT in matches group.
		$this->assertArrayHasKey( 'num_matches', $GLOBALS['wp_stubs_cache'] );
		$this->assertSame( 14, $GLOBALS['wp_stubs_cache']['num_matches'][ $cache_key ] );
		$this->assertArrayNotHasKey( 'matches', $GLOBALS['wp_stubs_cache'] );

		// Reset database return to verify cache hit.
		$this->db->get_var_return = '999';
		$cached_count             = $this->query->execute( 9, array( 'season' => '2026' ), true );
		$this->assertSame( 14, $cached_count );
	}

	/**
	 * Test select query execution and caching under 'matches' group.
	 */
	public function test_execute_select_caches_with_md5_key_in_matches_group(): void {
		$row                          = (object) array( 'id' => 501, 'league_id' => 10 );
		$this->db->get_results_return = array( $row );

		$results = $this->query->execute( 10, array( 'season' => '2026', 'withdrawn' => true ) );

		$this->assertSame( array( $row ), $results );
		$sql       = $this->db->last_sql();
		$cache_key = md5( $sql );

		// Verify key is valid 32-char hexadecimal md5 hash.
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $cache_key );

		// Verify stored under matches group with exact md5 key and NOT in num_matches group.
		$this->assertArrayHasKey( 'matches', $GLOBALS['wp_stubs_cache'] );
		$this->assertSame( array( $row ), $GLOBALS['wp_stubs_cache']['matches'][ $cache_key ] );
		$this->assertArrayNotHasKey( 'num_matches', $GLOBALS['wp_stubs_cache'] );

		// Subsequent call should hit cache without invoking database.
		$this->db->get_results_return = array();
		$cached_results               = $this->query->execute( 10, array( 'season' => '2026', 'withdrawn' => true ) );
		$this->assertSame( array( $row ), $cached_results );
	}

	/**
	 * Test that cache => false bypasses cached value and executes select query.
	 */
	public function test_execute_with_cache_false_bypasses_cache(): void {
		$initial_row                  = (object) array( 'id' => 601 );
		$this->db->get_results_return = array( $initial_row );

		$this->query->execute( 11, array( 'season' => '2026', 'withdrawn' => true ) );

		// Second call with cache => false returns fresh db row instead of cached.
		$fresh_row                    = (object) array( 'id' => 602 );
		$this->db->get_results_return = array( $fresh_row );

		$fresh_results = $this->query->execute( 11, array( 'season' => '2026', 'withdrawn' => true, 'cache' => false ) );
		$this->assertSame( array( $fresh_row ), $fresh_results );
	}

	/**
	 * Test that count query with cache => false bypasses cached count and queries database.
	 */
	public function test_execute_count_with_cache_false_bypasses_cache(): void {
		$this->db->get_var_return = '10';

		$initial_count = $this->query->execute( 12, array( 'season' => '2026' ), true );
		$this->assertSame( 10, $initial_count );

		// Second call with cache => false returns fresh db count instead of cached 10.
		$this->db->get_var_return = '25';
		$fresh_count              = $this->query->execute( 12, array( 'season' => '2026', 'cache' => false ), true );
		$this->assertSame( 25, $fresh_count );
	}

	/**
	 * Test that falsy non-boolean cache values (0, '0', '', []) do not bypass the cache in select queries.
	 */
	public function test_execute_select_falsy_non_boolean_cache_values_use_cache(): void {
		$cached_row = (object) array( 'id' => 701, 'name' => 'Cached Match' );
		$fresh_row  = (object) array( 'id' => 702, 'name' => 'Fresh Match' );

		$falsy_cache_values = array( 0, '0', '', array() );

		foreach ( $falsy_cache_values as $falsy_value ) {
			// Seed cache for the select query.
			$this->db->get_results_return = array( $cached_row );
			$this->query->execute( 20, array( 'season' => '2026', 'withdrawn' => true ) );

			// Database now returns fresh row if queried.
			$this->db->get_results_return = array( $fresh_row );

			// Passing falsy non-boolean cache argument should hit cache and NOT return fresh_row.
			$results = $this->query->execute(
				20,
				array(
					'season'    => '2026',
					'withdrawn' => true,
					'cache'     => $falsy_value,
				)
			);
			$this->assertSame( array( $cached_row ), $results );
		}

		// Contrast with boolean false, which MUST bypass the cache and return fresh row.
		$bypass_results = $this->query->execute(
			20,
			array(
				'season'    => '2026',
				'withdrawn' => true,
				'cache'     => false,
			)
		);
		$this->assertSame( array( $fresh_row ), $bypass_results );
	}

	/**
	 * Test that falsy non-boolean cache values (0, '0', '', []) do not bypass the cache in count queries.
	 */
	public function test_execute_count_falsy_non_boolean_cache_values_use_cache(): void {
		$falsy_cache_values = array( 0, '0', '', array() );

		foreach ( $falsy_cache_values as $falsy_value ) {
			// Seed count cache.
			$this->db->get_var_return = '50';
			$initial_count            = $this->query->execute( 21, array( 'season' => '2026' ), true );
			$this->assertSame( 50, $initial_count );

			// Database now returns fresh count if queried.
			$this->db->get_var_return = '99';

			// Falsy non-boolean cache values must hit cache and return 50.
			$cached_count = $this->query->execute(
				21,
				array(
					'season' => '2026',
					'cache'  => $falsy_value,
				),
				true
			);
			$this->assertSame( 50, $cached_count );
		}

		// Boolean false must bypass cache and return fresh 99.
		$bypass_count = $this->query->execute(
			21,
			array(
				'season' => '2026',
				'cache'  => false,
			),
			true
		);
		$this->assertSame( 99, $bypass_count );
	}

	/**
	 * Test that final parameter strips HTML tags and escapes entities, and leg is bound as string.
	 */
	public function test_execute_final_escaping_and_leg_binding(): void {
		$this->db->get_results_return = array();

		$this->query->execute(
			15,
			array(
				'final'              => '<em>Final</em> & Match',
				'leg'                => '3',
				'skip_season_filter' => true,
				'withdrawn'          => true,
			)
		);

		$sql  = $this->db->last_sql();
		$args = $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'];

		$this->assertStringContainsString( 'AND m.`final` = %s', $sql );
		$this->assertStringContainsString( 'AND m.`leg` = %s', $sql );
		$this->assertSame( 'Final &amp; Match', $args[1] );
		$this->assertSame( '3', $args[2] );
	}

	/**
	 * Test full SQL byte equality and parameter binding order for combined filter arguments.
	 */
	public function test_execute_combined_filters_full_sql_and_argument_order(): void {
		$this->db->get_results_return = array();

		$query_args = array(
			'season'         => '2026-summer',
			'final'          => '<b>Semi</b> & Final',
			'team_pair'      => array( 11, 22 ),
			'match_day'      => 3,
			'home_points'    => 'not_empty',
			'away_points'    => 'not_null',
			'time'           => 'today',
			'player'         => 'P987',
			'withdrawn'      => false,
			'club'           => 42,
			'status_value'   => 2,
			'status_compare' => 'not',
			'orderby'        => array(
				'date' => 'ASC',
				'id'   => 'DESC',
			),
			'limit'          => 15,
			'offset'         => 30,
			'confirmed'      => true,
			'leg'            => '2',
			'winner_id'      => 5,
			'loser_id'       => 8,
		);

		$this->query->execute( 100, $query_args );

		$expected_sql = implode(
			' ',
			array(
				'SELECT distinct m.*',
				'FROM wp_racketmanager_matches m, wp_racketmanager_rubbers r, wp_racketmanager_rubber_players rp, wp_racketmanager_league_teams t1, wp_racketmanager_league_teams t2',
				'WHERE m.`league_id` = %d',
				'AND m.`season` = %s',
				'AND m.`final` = %s',
				'AND ( (`home_team` = %d AND `away_team` = %d) OR (`home_team` = %d AND `away_team` = %d) )',
				'AND m.`match_day` = %d',
				"AND m.`home_points` != ''",
				'AND m.`away_points` IS NOT NULL',
				'AND m.`winner_id` = %d',
				'AND m.`loser_id` = %d',
				'AND DATEDIFF(NOW(), m.`date`) = 0',
				"AND m.`confirmed` = 'Y'",
				'AND m.`leg` = %s',
				'AND m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND `player_id` = %s',
				"AND m.`home_team` = t1.`team_id` AND t1.`league_id` = m.`league_id` AND t1.`season` = m.`season` AND t1.`status` != 'W'",
				"AND m.`away_team` = t2.`team_id` AND t2.`league_id` = m.`league_id` AND t2.`season` = m.`season` AND t2.`status` != 'W'",
				'AND (m.`home_team` IN (SELECT `id` FROM wp_racketmanager_teams WHERE `club_id` = %d) OR m.`away_team` IN (SELECT `id` FROM wp_racketmanager_teams WHERE `club_id` = %d))',
				'AND m.`status` != %d',
				'ORDER BY `date` ASC,`id` DESC',
				'LIMIT 30,15',
			)
		);

		$expected_args = array(
			100,
			'2026-summer',
			'Semi &amp; Final',
			11,
			22,
			22,
			11,
			3,
			5,
			8,
			'2',
			'P987',
			42,
			42,
			2,
		);

		$this->assertSame( $expected_sql, $this->db->last_sql() );
		$this->assertSame( $expected_args, $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );
	}
}
