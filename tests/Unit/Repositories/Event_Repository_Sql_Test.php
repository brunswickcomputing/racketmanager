<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

class Event_Repository_Sql_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;
	private Event_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->db = new Recording_Wpdb();
		$GLOBALS['wpdb'] = new Recording_Wpdb();
		$GLOBALS['wpdb']->prefix = 'SHOULD_NOT_USE_';
		$GLOBALS['wpdb']->racketmanager = 'SHOULD_NOT_USE_racketmanager_leagues';
		$this->repository = new Event_Repository( $this->db );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_constructor_uses_injected_db_not_global(): void {
		$this->repository->count_leagues( 7 );

		$this->assertNotEmpty( $this->db->prepared );
		$this->assertEmpty( $GLOBALS['wpdb']->prepared );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'wp_racketmanager_leagues', $sql );
		$this->assertStringNotContainsString( 'FROM wp_racketmanager ', $sql );
		$this->assertStringNotContainsString( 'SHOULD_NOT_USE_', $sql );
	}

	public function test_count_leagues_uses_leagues_table(): void {
		$this->db->get_var_return = '3';
		$count = $this->repository->count_leagues( 12 );

		$this->assertSame( 3, $count );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'SELECT COUNT(ID) FROM wp_racketmanager_leagues', $sql );
		$this->assertStringContainsString( '`event_id` = %d', $sql );
	}

	public function test_find_by_name_always_searches_by_name_even_when_numeric(): void {
		$this->db->get_row_return = null;
		$this->repository->find_by_name( '2026' );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'WHERE `name` = %s', $sql );
		$this->assertStringNotContainsString( '`id` = %d', $sql );
	}

	public function test_query_leagues_count_and_list_use_leagues_table(): void {
		$this->db->get_var_return = 2;
		$count = $this->repository->query_leagues( 5, array( 'count' => true ) );
		$this->assertSame( 2, $count );
		$this->assertStringContainsString( 'FROM wp_racketmanager_leagues', $this->db->last_query );

		$this->db->get_results_return = array();
		$this->repository->query_leagues( 5, array( 'season' => 2026 ) );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'wp_racketmanager_leagues', $sql );
		$this->assertStringContainsString( '`season` = %d', $sql );
	}

	public function test_query_players_count_and_season_filter(): void {
		$this->db->get_var_return = 4;
		$count = $this->repository->query_players( 8, false, array( 'count' => true ) );
		$this->assertSame( 4, $count );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'wp_racketmanager_leagues', $sql );
		$this->assertStringNotContainsString( '`season` = %s', $sql );

		$this->repository->query_players( 8, false, array( 'count' => true, 'season' => '2026' ) );
		$this->assertStringContainsString( '`season` = %s', $this->db->last_sql() );
		$this->assertSame( '2026', $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'][1] ?? null );
	}

	public function test_query_matches_uses_leagues_table(): void {
		$this->db->get_results_return = array();
		$this->repository->query_matches( 3, array( 'season' => '2026' ) );
		$sql = $this->db->last_query;
		$this->assertStringContainsString( 'wp_racketmanager_leagues', $sql );
		$this->assertStringNotContainsString( 'FROM wp_racketmanager ', $sql );
	}
}
