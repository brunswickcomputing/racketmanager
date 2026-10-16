<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\League_Repository;
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

		$this->assertStringContainsString( 'DELETE FROM wp_racketmanager_league_teams', $this->db->last_query );
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

		$this->assertStringContainsString( "UPDATE wp_racketmanager_league_teams SET `status` = 'W'", $this->db->last_query );
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
}
