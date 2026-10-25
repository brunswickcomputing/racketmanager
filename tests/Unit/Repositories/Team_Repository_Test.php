<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

final class Team_Repository_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;
	private Team_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->db        = new Recording_Wpdb();
		$GLOBALS['wpdb'] = $this->db;

		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();

		$this->repository = new Team_Repository( $this->db );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_find_id_by_title_returns_zero_for_empty_or_whitespace_title_without_query(): void {
		$this->assertSame( 0, $this->repository->find_id_by_title( '' ) );
		$this->assertSame( 0, $this->repository->find_id_by_title( '   ' ) );
		$this->assertEmpty( $this->db->prepared );
	}

	public function test_find_id_by_title_queries_database_and_caches_positive_id(): void {
		$this->db->get_var_return = '42';

		$id = $this->repository->find_id_by_title( '  Westcliff Lawn Mixed 1  ' );

		$this->assertSame( 42, $id );
		$this->assertNotEmpty( $this->db->prepared );
		$this->assertStringContainsString( 'SELECT `id` FROM wp_racketmanager_teams WHERE `title` = %s LIMIT 1', $this->db->last_sql() );
		$this->assertSame( array( 'Westcliff Lawn Mixed 1' ), $this->db->prepared[ array_key_last( $this->db->prepared ) ]['args'] );

		// Second call should hit object cache without querying DB again
		$prepared_count = count( $this->db->prepared );
		$cached_id      = $this->repository->find_id_by_title( 'Westcliff Lawn Mixed 1' );
		$this->assertSame( 42, $cached_id );
		$this->assertCount( $prepared_count, $this->db->prepared );
	}

	public function test_find_id_by_title_does_not_cache_zero_on_miss(): void {
		$this->db->get_var_return = '0';

		$id = $this->repository->find_id_by_title( 'Nonexistent Team' );
		$this->assertSame( 0, $id );
		$this->assertCount( 1, $this->db->prepared );

		// Subsequent call queries DB again (no negative caching)
		$this->db->get_var_return = '99';
		$id_second = $this->repository->find_id_by_title( 'Nonexistent Team' );
		$this->assertSame( 99, $id_second );
		$this->assertCount( 2, $this->db->prepared );
	}

	public function test_save_new_team_invalidates_title_cache(): void {
		$title_key = 'team_id_' . md5( 'New Team Title' );
		wp_cache_set( $title_key, 123, 'teams' );

		$this->db->insert_id = 55;

		$team = new Team(
			(object) array(
				'title'     => 'New Team Title',
				'stadium'   => 'Court 1',
				'club_id'   => 2,
				'type'      => 'MD',
				'team_type' => null,
			)
		);

		$result = $this->repository->save( $team );
		$this->assertSame( 55, $result );
		$this->assertFalse( wp_cache_get( $title_key, 'teams' ) );
	}

	public function test_save_existing_team_invalidates_old_and_new_title_caches(): void {
		$old_title_key = 'team_id_' . md5( 'Old Team Title' );
		$new_title_key = 'team_id_' . md5( 'Updated Team Title' );

		wp_cache_set( $old_title_key, 10, 'teams' );
		wp_cache_set( $new_title_key, 10, 'teams' );

		// Existing team in DB with old title (read with a scalar query).
		$this->db->get_var_return = 'Old Team Title';

		$team = new Team(
			(object) array(
				'id'        => 10,
				'title'     => 'Updated Team Title',
				'stadium'   => 'Court 2',
				'club_id'   => 2,
				'type'      => 'MD',
				'team_type' => null,
			)
		);

		$result = $this->repository->save( $team );
		$this->assertTrue( $result );

		$this->assertFalse( wp_cache_get( $old_title_key, 'teams' ) );
		$this->assertFalse( wp_cache_get( $new_title_key, 'teams' ) );

		$methods = array_column( $this->db->executed, 'method' );
		$this->assertContains( 'get_var', $methods );
		$this->assertNotContains( 'get_row', $methods, 'Old title must be read with a scalar query, not a hydrated team.' );
		$this->assertStringContainsString( 'SELECT `title` FROM wp_racketmanager_teams WHERE `id` = %d LIMIT 1', $this->db->sql_containing( 'SELECT `title`' ) ?? '' );
	}

	public function test_save_existing_team_keeps_title_caches_when_update_fails(): void {
		$old_title_key = 'team_id_' . md5( 'Old Team Title' );
		$new_title_key = 'team_id_' . md5( 'Updated Team Title' );

		wp_cache_set( $old_title_key, 10, 'teams' );
		wp_cache_set( $new_title_key, 10, 'teams' );

		$this->db->get_var_return = 'Old Team Title';
		$this->db->update_return  = false;

		$team = new Team(
			(object) array(
				'id'        => 10,
				'title'     => 'Updated Team Title',
				'stadium'   => 'Court 2',
				'club_id'   => 2,
				'type'      => 'MD',
				'team_type' => null,
			)
		);

		$this->assertFalse( $this->repository->save( $team ) );
		$this->assertSame( 10, wp_cache_get( $old_title_key, 'teams' ) );
		$this->assertSame( 10, wp_cache_get( $new_title_key, 'teams' ) );
		$this->assertFalse( wp_cache_get( 10, 'teams' ), 'Entity cache must not be populated by a failed write.' );
	}

	public function test_save_new_team_keeps_title_cache_when_insert_fails(): void {
		$title_key = 'team_id_' . md5( 'New Team Title' );
		wp_cache_set( $title_key, 123, 'teams' );

		$this->db->insert_return = false;

		$team = new Team(
			(object) array(
				'title'     => 'New Team Title',
				'stadium'   => 'Court 1',
				'club_id'   => 2,
				'type'      => 'MD',
				'team_type' => null,
			)
		);

		$this->assertFalse( $this->repository->save( $team ) );
		$this->assertSame( 123, wp_cache_get( $title_key, 'teams' ) );
	}

	public function test_delete_team_invalidates_title_cache(): void {
		$title_key = 'team_id_' . md5( 'Team To Delete' );
		wp_cache_set( $title_key, 88, 'teams' );

		$this->db->get_var_return = 'Team To Delete';

		$result = $this->repository->delete( 88 );
		$this->assertTrue( $result );
		$this->assertFalse( wp_cache_get( $title_key, 'teams' ) );
		$this->assertNotContains( 'get_row', array_column( $this->db->executed, 'method' ) );
	}

	public function test_delete_team_keeps_title_cache_when_delete_fails(): void {
		$title_key = 'team_id_' . md5( 'Team To Delete' );
		wp_cache_set( $title_key, 88, 'teams' );

		$this->db->get_var_return = 'Team To Delete';
		$this->db->delete_return  = false;

		$this->assertFalse( $this->repository->delete( 88 ) );
		$this->assertSame( 88, wp_cache_get( $title_key, 'teams' ) );
	}
}
