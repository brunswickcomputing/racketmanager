<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Concerns\Wpdb_Transactions;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
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

	public function test_save_with_players_commits_after_saving_team_and_players(): void {
		$this->db->insert_id = 777;

		$team = $this->make_new_team( 'Player 10 / Player 20' );

		$result = $this->repository->save_with_players( $team, array( 10, 20 ) );

		$this->assertSame( 777, $result );
		$this->assertSame( 777, $team->get_id() );
		$this->assertSame(
			array( 'START TRANSACTION', 'COMMIT' ),
			$this->transaction_statements()
		);

		$inserts = array_values( array_filter( $this->db->executed, fn( $op ) => 'insert' === $op['method'] ) );
		$this->assertCount( 3, $inserts );
		$this->assertSame( 'wp_racketmanager_teams', $inserts[0]['table'] );
		$this->assertSame( 'wp_racketmanager_team_players', $inserts[1]['table'] );
		$this->assertSame( array( 'team_id' => 777, 'player_id' => 10 ), $inserts[1]['data'] );
		$this->assertSame( array( 'team_id' => 777, 'player_id' => 20 ), $inserts[2]['data'] );

		$this->assertSame(
			array( 'query', 'insert', 'insert', 'insert', 'query' ),
			array_column( $this->db->executed, 'method' )
		);
	}

	public function test_save_with_players_uses_entity_id_when_saving_an_existing_team(): void {
		$this->db->get_var_return = 'Old Title';
		$this->db->insert_id      = 1; // must never be used as the team id on the update path.

		$team = $this->make_new_team( 'Player 10 / Player 20' );
		$team->set_id( 42 );

		$result = $this->repository->save_with_players( $team, array( 10, 20 ) );

		$this->assertSame( 42, $result );
		$this->assertSame( 42, $team->get_id() );
		$this->assertSame( array( 'START TRANSACTION', 'COMMIT' ), $this->transaction_statements() );

		$updates = array_values( array_filter( $this->db->executed, fn( $op ) => 'update' === $op['method'] ) );
		$this->assertCount( 1, $updates );
		$this->assertSame( 'wp_racketmanager_teams', $updates[0]['table'] );
		$this->assertSame( array( 'id' => 42 ), $updates[0]['where'] );

		$inserts = array_values( array_filter( $this->db->executed, fn( $op ) => 'insert' === $op['method'] ) );
		$this->assertCount( 2, $inserts );
		$this->assertSame( array( 'team_id' => 42, 'player_id' => 10 ), $inserts[0]['data'] );
		$this->assertSame( array( 'team_id' => 42, 'player_id' => 20 ), $inserts[1]['data'] );
	}

	public function test_save_with_players_rolls_back_when_existing_team_update_fails(): void {
		$this->db->get_var_return = 'Old Title';
		$this->db->update_return  = false;

		$team = $this->make_new_team( 'Player 10' );
		$team->set_id( 42 );

		$this->assertFalse( $this->repository->save_with_players( $team, array( 10 ) ) );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		$this->assertNotContains( 'insert', array_column( $this->db->executed, 'method' ) );
	}

	public function test_save_with_players_rolls_back_when_insert_yields_no_team_id(): void {
		$this->db->insert_id = 0;

		$team = $this->make_new_team( 'Player 10' );

		$this->assertFalse( $this->repository->save_with_players( $team, array( 10 ) ) );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		// Only the team insert ran; no player row was written against a bogus id.
		$this->assertSame( array( 'query', 'insert', 'query' ), array_column( $this->db->executed, 'method' ) );
	}

	public function test_save_with_players_rolls_back_when_team_insert_fails(): void {
		$this->db->insert_return = false;

		$team = $this->make_new_team( 'Player 10' );

		$result = $this->repository->save_with_players( $team, array( 10 ) );

		$this->assertFalse( $result );
		$this->assertNull( $team->get_id() );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		$this->assertSame( array( 'query', 'insert', 'query' ), array_column( $this->db->executed, 'method' ) );
	}

	public function test_save_with_players_rolls_back_when_player_insert_fails(): void {
		$db = new class() extends Recording_Wpdb {
			public int $call = 0;
			public function insert( $table, $data, $format = null ) {
				++$this->call;
				parent::insert( $table, $data, $format );
				return 1 !== $this->call ? false : true;
			}
		};
		$db->insert_id = 777;
		$repository    = new Team_Repository( $db );

		$team = $this->make_new_team( 'Player 10 / Player 20' );

		$result = $repository->save_with_players( $team, array( 10, 20 ) );

		$this->assertFalse( $result );
		$this->assertSame(
			array( 'START TRANSACTION', 'ROLLBACK' ),
			array_column( array_filter( $db->executed, fn( $op ) => 'query' === $op['method'] ), 'sql' )
		);
		$this->assertSame( array( 'query', 'insert', 'insert', 'insert', 'query' ), array_column( $db->executed, 'method' ) );
	}

	public function test_save_with_players_rolls_back_and_rethrows_unexpected_exceptions(): void {
		$db = new class() extends Recording_Wpdb {
			public function insert( $table, $data, $format = null ) {
				throw new \RuntimeException( 'DB Error' );
			}
		};
		$repository = new Team_Repository( $db );

		try {
			$repository->save_with_players( $this->make_new_team( 'Player 10' ), array( 10 ) );
			$this->fail( 'Expected RuntimeException to propagate.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'DB Error', $e->getMessage() );
		}

		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), array_column( $db->executed, 'sql' ) );
	}

	public function test_transaction_statements_are_skipped_without_a_connection_and_issued_with_one(): void {
		$transactions = new class() {
			use Wpdb_Transactions;

			public ?object $wpdb = null;

			public function run_all(): void {
				$this->begin_transaction();
				$this->commit();
				$this->rollback();
			}
		};

		// No connection: the guards must turn every statement into a no-op (no Error on null).
		$transactions->run_all();

		$transactions->wpdb = $this->db;
		$transactions->run_all();

		$this->assertSame( array( 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ), $this->transaction_statements() );
	}

	public function test_delete_with_fixtures_without_connection_still_reports_failure(): void {
		$GLOBALS['wpdb'] = null;
		$repository      = new Team_Repository( null );
		$fixtures        = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'delete_by_team' )->with( 5 )->willReturn( false );

		$this->assertFalse( $repository->delete_with_fixtures( 5, $fixtures ) );
	}

	public function test_delete_with_fixtures_commits_when_fixtures_and_team_are_deleted(): void {
		$this->db->get_var_return = 'Team To Delete';
		$fixtures                 = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'delete_by_team' )->with( 100 )->willReturn( true );

		$this->assertTrue( $this->repository->delete_with_fixtures( 100, $fixtures ) );
		$this->assertSame( array( 'START TRANSACTION', 'COMMIT' ), $this->transaction_statements() );

		$methods = array_column( $this->db->executed, 'method' );
		$this->assertSame( 'query', $methods[0] );
		$this->assertSame( 'query', $methods[ array_key_last( $methods ) ] );
		$this->assertContains( 'delete', $methods );
		$this->assertFalse( wp_cache_get( 'team_id_' . md5( 'Team To Delete' ), 'teams' ) );
	}

	public function test_delete_with_fixtures_rolls_back_when_fixture_delete_fails_without_deleting_team(): void {
		$fixtures = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'delete_by_team' )->with( 100 )->willReturn( false );

		$this->assertFalse( $this->repository->delete_with_fixtures( 100, $fixtures ) );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		$this->assertNotContains( 'delete', array_column( $this->db->executed, 'method' ) );
	}

	public function test_delete_with_fixtures_rolls_back_when_team_delete_fails(): void {
		$this->db->get_var_return = 'Team To Delete';
		$this->db->delete_return  = false;
		$fixtures                 = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'delete_by_team' )->with( 100 )->willReturn( true );

		$this->assertFalse( $this->repository->delete_with_fixtures( 100, $fixtures ) );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		$this->assertContains( 'delete', array_column( $this->db->executed, 'method' ) );
	}

	public function test_delete_with_fixtures_rolls_back_and_swallows_exceptions(): void {
		$fixtures = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'delete_by_team' )->willThrowException( new \RuntimeException( 'DB Error' ) );

		$this->assertFalse( $this->repository->delete_with_fixtures( 100, $fixtures ) );
		$this->assertSame( array( 'START TRANSACTION', 'ROLLBACK' ), $this->transaction_statements() );
		$this->assertNotContains( 'delete', array_column( $this->db->executed, 'method' ) );
	}

	private function make_new_team( string $name ): Team {
		$team = new Team();
		$team->set_name( $name );
		$team->set_type( 'MD' );
		$team->set_team_type( 'P' );
		return $team;
	}

	/**
	 * @return array<int, string>
	 */
	private function transaction_statements(): array {
		return array_values(
			array_column(
				array_filter( $this->db->executed, fn( $op ) => 'query' === $op['method'] ),
				'sql'
			)
		);
	}
}
