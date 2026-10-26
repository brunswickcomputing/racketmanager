<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Player_Statistics_Repository;
use wpdb;

final class Player_Statistics_Repository_Test extends TestCase {
	/** @var array<int, string> */
	private array $queries = array();
	private ?bool $cache_enabled_backup = null;
	private mixed $cache_backup = null;

	protected function setUp(): void {
		parent::setUp();
		$this->queries              = array();
		$this->cache_enabled_backup = $GLOBALS['wp_stubs_cache_enabled'] ?? null;
		$this->cache_backup         = $GLOBALS['wp_stubs_cache'] ?? null;
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();
	}

	protected function tearDown(): void {
		if ( null === $this->cache_enabled_backup ) {
			unset( $GLOBALS['wp_stubs_cache_enabled'] );
		} else {
			$GLOBALS['wp_stubs_cache_enabled'] = $this->cache_enabled_backup;
		}
		if ( null === $this->cache_backup ) {
			unset( $GLOBALS['wp_stubs_cache'] );
		} else {
			$GLOBALS['wp_stubs_cache'] = $this->cache_backup;
		}
		parent::tearDown();
	}

	private function make_wpdb( mixed $rows ): wpdb {
		$wpdb                       = new wpdb();
		$wpdb->get_results_callback = function ( string $query ) use ( $rows ) {
			$this->queries[] = $query;
			return $rows;
		};

		return $wpdb;
	}

	public function test_find_competitions_played_builds_filtered_grouped_sql(): void {
		$rows       = array( (object) array( 'id' => '3', 'name' => 'Summer League', 'season' => '2025' ) );
		$repository = new Player_Statistics_Repository( $this->make_wpdb( $rows ) );

		$result = $repository->find_competitions_played( 7, 'MS', '2025' );

		$this->assertSame( $rows, $result );
		$this->assertCount( 1, $this->queries );
		$sql = $this->queries[0];
		$this->assertStringStartsWith( 'SELECT c.id, c.name, m.season FROM ', $sql );
		foreach ( array( 'wp_racketmanager_rubber_players rp', 'wp_racketmanager_rubbers r', 'wp_racketmanager_matches m', 'wp_racketmanager_leagues l', 'wp_racketmanager_events e', 'wp_racketmanager_competitions c' ) as $table ) {
			$this->assertStringContainsString( $table, $sql );
		}
		$this->assertStringContainsString( 'rp.`player_id` = 7 ', $sql );
		$this->assertStringContainsString( " AND c.`type` = 'MS' AND m.`season` = '2025'", $sql );
		$this->assertStringEndsWith( ' GROUP BY c.id, c.name, m.season ORDER BY m.season DESC, c.name ASC', $sql );
		$this->assertSame( $rows, $GLOBALS['wp_stubs_cache']['player_competitions'][ md5( $sql ) ] );
	}

	public function test_find_competitions_played_omits_filters_when_null(): void {
		$repository = new Player_Statistics_Repository( $this->make_wpdb( array() ) );

		$this->assertSame( array(), $repository->find_competitions_played( 7 ) );
		$this->assertStringNotContainsString( 'c.`type`', $this->queries[0] );
		$this->assertStringNotContainsString( 'm.`season` =', $this->queries[0] );
		$this->assertStringContainsString( 'e.competition_id = c.id GROUP BY', $this->queries[0] );
	}

	public function test_find_competitions_played_returns_cached_rows_without_querying(): void {
		$rows       = array( (object) array( 'id' => '3', 'name' => 'Summer League', 'season' => '2025' ) );
		$repository = new Player_Statistics_Repository( $this->make_wpdb( $rows ) );

		$first  = $repository->find_competitions_played( 7, 'MS', '2025' );
		$second = $repository->find_competitions_played( 7, 'MS', '2025' );

		$this->assertCount( 1, $this->queries );
		$this->assertEquals( $first, $second );
	}

	public function test_find_team_stats_builds_sql_with_season_filter_and_caches(): void {
		$rows       = array( (object) array( 'stat_type' => 'teams', 'season' => '2024' ) );
		$repository = new Player_Statistics_Repository( $this->make_wpdb( $rows ) );

		$result = $repository->find_team_stats( 7, '2024' );

		$this->assertSame( $rows, $result );
		$sql = $this->queries[0];
		$this->assertStringContainsString( "SELECT 'teams' as `stat_type`", $sql );
		$this->assertStringContainsString( 'rp.`player_id` = 7 ', $sql );
		$this->assertStringContainsString( "l.`event_id` = e.`id`  AND m.`season` = '2024' ORDER BY m.`season`, e.`type`", $sql );
		$this->assertSame( $rows, $GLOBALS['wp_stubs_cache']['player_stats_teams'][ md5( $sql ) ] );
		$this->assertArrayNotHasKey( 'player_stats_players', $GLOBALS['wp_stubs_cache'] );
	}

	public function test_find_team_stats_omits_season_filter_when_null(): void {
		$repository = new Player_Statistics_Repository( $this->make_wpdb( array() ) );

		$repository->find_team_stats( 7 );

		$this->assertStringNotContainsString( 'm.`season` =', $this->queries[0] );
		$this->assertStringEndsWith( 'l.`event_id` = e.`id`  ORDER BY m.`season`, e.`type`', $this->queries[0] );
	}

	public function test_find_player_stats_builds_sql_with_season_filter_and_caches(): void {
		$rows       = array( (object) array( 'stat_type' => 'players', 'season' => '2025' ) );
		$repository = new Player_Statistics_Repository( $this->make_wpdb( $rows ) );

		$result = $repository->find_player_stats( 7, '2025' );

		$this->assertSame( $rows, $result );
		$sql = $this->queries[0];
		$this->assertStringContainsString( "SELECT 'players' as `stat_type`", $sql );
		$this->assertStringContainsString( 'wp_racketmanager_team_players tp, wp_racketmanager_teams t,', $sql );
		$this->assertStringContainsString( 'tp.`player_id` = 7 ', $sql );
		$this->assertStringContainsString( "(m.`home_team` = t.`id` OR m.`away_team` = t.`id`)", $sql );
		$this->assertStringEndsWith( " AND m.`season` = '2025' ORDER BY m.`season`, e.`type`", $sql );
		$this->assertSame( $rows, $GLOBALS['wp_stubs_cache']['player_stats_players'][ md5( $sql ) ] );
	}

	public function test_find_player_stats_second_call_hits_cache(): void {
		$repository = new Player_Statistics_Repository( $this->make_wpdb( array( (object) array( 'stat_type' => 'players' ) ) ) );

		$repository->find_player_stats( 7 );
		$repository->find_player_stats( 7 );

		$this->assertCount( 1, $this->queries );
	}

	public function test_non_array_results_return_empty_array(): void {
		$repository = new Player_Statistics_Repository( $this->make_wpdb( null ) );

		$this->assertSame( array(), $repository->find_competitions_played( 7 ) );
		$this->assertSame( array(), $repository->find_team_stats( 7 ) );
		$this->assertSame( array(), $repository->find_player_stats( 7 ) );
	}

	public function test_falls_back_to_global_wpdb(): void {
		$original        = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = $this->make_wpdb( array( (object) array( 'stat_type' => 'teams' ) ) );
		try {
			$repository = new Player_Statistics_Repository();
			$this->assertCount( 1, $repository->find_team_stats( 9 ) );
			$this->assertStringContainsString( 'rp.`player_id` = 9 ', $this->queries[0] );
		} finally {
			if ( null === $original ) {
				unset( $GLOBALS['wpdb'] );
			} else {
				$GLOBALS['wpdb'] = $original;
			}
		}
	}
}
