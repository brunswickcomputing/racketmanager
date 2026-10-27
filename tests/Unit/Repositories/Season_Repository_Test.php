<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

final class Season_Repository_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb $db;
	private Season_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->db        = new Recording_Wpdb();
		$GLOBALS['wpdb'] = $this->db;

		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = [];

		$this->repository = new Season_Repository();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_find_by_id_queries_database_and_populates_cache(): void {
		$row = (object) [
			'id'   => 10,
			'name' => '2024/2025',
		];
		$this->db->get_row_return = $row;

		$season = $this->repository->find_by_id( 10, 'id' );

		$this->assertInstanceOf( Season::class, $season );
		$this->assertSame( 10, $season->get_id() );
		$this->assertSame( '2024/2025', $season->get_name() );

		// Verifies both id_ and name_ cache entries are populated
		$cached_by_id   = wp_cache_get( 'id_10', 'seasons' );
		$cached_by_name = wp_cache_get( 'name_2024/2025', 'seasons' );
		$this->assertInstanceOf( Season::class, $cached_by_id );
		$this->assertInstanceOf( Season::class, $cached_by_name );
	}

	public function test_find_by_name_queries_database_and_populates_cache(): void {
		$row = (object) [
			'id'   => 20,
			'name' => 'Summer 2025',
		];
		$this->db->get_row_return = $row;

		$season = $this->repository->find_by_id( 'Summer 2025', 'name' );

		$this->assertInstanceOf( Season::class, $season );
		$this->assertSame( 20, $season->get_id() );
		$this->assertSame( 'Summer 2025', $season->get_name() );

		$cached_by_id   = wp_cache_get( 'id_20', 'seasons' );
		$cached_by_name = wp_cache_get( 'name_Summer 2025', 'seasons' );
		$this->assertInstanceOf( Season::class, $cached_by_id );
		$this->assertInstanceOf( Season::class, $cached_by_name );
	}

	public function test_numeric_name_and_numeric_id_do_not_collide_in_cache(): void {
		// Season A: ID 2024, name "Winter 2023"
		$season_a = Season::from_object( (object) [
			'id'   => 2024,
			'name' => 'Winter 2023',
		] );
		wp_cache_set( 'id_2024', $season_a, 'seasons' );
		wp_cache_set( 'name_Winter 2023', $season_a, 'seasons' );

		// Season B: ID 55, name "2024"
		$season_b = Season::from_object( (object) [
			'id'   => 55,
			'name' => '2024',
		] );
		wp_cache_set( 'id_55', $season_b, 'seasons' );
		wp_cache_set( 'name_2024', $season_b, 'seasons' );

		// Lookup by ID 2024 must return Season A
		$found_by_id = $this->repository->find_by_id( 2024, 'id' );
		$this->assertSame( 2024, $found_by_id->get_id() );
		$this->assertSame( 'Winter 2023', $found_by_id->get_name() );

		// Lookup by Name "2024" must return Season B
		$found_by_name = $this->repository->find_by_id( '2024', 'name' );
		$this->assertSame( 55, $found_by_name->get_id() );
		$this->assertSame( '2024', $found_by_name->get_name() );
	}

	public function test_find_all_maps_results_to_seasons(): void {
		$rows = [
			(object) [ 'id' => 1, 'name' => '2024' ],
			(object) [ 'id' => 2, 'name' => '2025' ],
		];
		$this->db->get_results_return = $rows;

		$results = $this->repository->find_all();

		$this->assertCount( 2, $results );
		$this->assertInstanceOf( Season::class, $results[0] );
		$this->assertSame( '2024', $results[0]->get_name() );
		$this->assertInstanceOf( Season::class, $results[1] );
		$this->assertSame( '2025', $results[1]->get_name() );
	}

	public function test_save_insert_populates_id_and_cache(): void {
		$season = Season::from_object( (object) [ 'id' => null, 'name' => '2026' ] );
		$this->db->insert_id = 99;

		$result = $this->repository->save( $season );

		$this->assertSame( 99, $result );
		$this->assertSame( 99, $season->get_id() );
		$this->assertEquals( $season, wp_cache_get( 'id_99', 'seasons' ) );
		$this->assertEquals( $season, wp_cache_get( 'name_2026', 'seasons' ) );
	}

	public function test_save_update_updates_cache(): void {
		$season = Season::from_object( (object) [ 'id' => 99, 'name' => '2026 Revised' ] );

		$result = $this->repository->save( $season );

		$this->assertTrue( (bool) $result );
		$this->assertEquals( $season, wp_cache_get( 'id_99', 'seasons' ) );
		$this->assertEquals( $season, wp_cache_get( 'name_2026 Revised', 'seasons' ) );
	}

	public function test_delete_invalidates_cache(): void {
		$season = Season::from_object( (object) [ 'id' => 15, 'name' => 'Delete Me' ] );
		wp_cache_set( 'id_15', $season, 'seasons' );
		wp_cache_set( 'name_Delete Me', $season, 'seasons' );

		$result = $this->repository->delete( 15 );

		$this->assertTrue( $result );
		$this->assertFalse( wp_cache_get( 'id_15', 'seasons' ) );
		$this->assertFalse( wp_cache_get( 'name_Delete Me', 'seasons' ) );
	}
}
