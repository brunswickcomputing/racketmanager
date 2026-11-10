<?php
/**
 * League_Cache_Invalidator_Test class.
 *
 * @package Racketmanager\Tests
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\League_Cache_Invalidator;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

/**
 * Test cache snapshotting and invalidations handled by League_Cache_Invalidator.
 */
#[AllowMockObjectsWithoutExpectations]
class League_Cache_Invalidator_Test extends TestCase {
	use Manages_Wordpress_Globals;

	/**
	 * Mock database.
	 *
	 * @var wpdb&MockObject
	 */
	private wpdb&MockObject $wpdb;

	/**
	 * Setup before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();
		$GLOBALS['wp_stubs_cache_deleted'] = array();

		$this->wpdb = $this->createMock( wpdb::class );
	}

	/**
	 * Teardown after each test.
	 */
	protected function tearDown(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = false;
		$GLOBALS['wp_stubs_cache']         = array();
		$GLOBALS['wp_stubs_cache_deleted'] = array();
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Test capture_snapshot extracts title, event_id, and seasons from cached League entity.
	 */
	public function test_capture_snapshot_from_cached_league(): void {
		$league = League_Test_Factory::from_object(
			(object) array(
				'id'       => 10,
				'title'    => 'Premier Division',
				'event_id' => 3,
				'seasons'  => array( '2025' => array(), '2026' => array() ),
			)
		);
		wp_cache_set( 10, $league, 'leagues' );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 10 );

		$this->assertSame( 'Premier Division', $snapshot['title'] );
		$this->assertSame( 3, $snapshot['event_id'] );
		$this->assertSame( array( '2025', '2026' ), $snapshot['seasons'] );
	}

	/**
	 * Test capture_snapshot reads from database when not cached and unserializes seasons.
	 */
	public function test_capture_snapshot_from_db_row_with_serialized_seasons(): void {
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn(
				(object) array(
					'id'       => 11,
					'title'    => 'Division 1',
					'event_id' => 4,
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Testing DB serialized string decoding.
					'seasons'  => serialize( array( '2024' => array(), '2025' => array() ) ),
				)
			);
		$this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 11 );

		$this->assertSame( 'Division 1', $snapshot['title'] );
		$this->assertSame( 4, $snapshot['event_id'] );
		$this->assertSame( array( '2024', '2025' ), $snapshot['seasons'] );
	}

	/**
	 * Test capture_snapshot handles DB row without seasons cleanly.
	 */
	public function test_capture_snapshot_from_db_row_without_seasons(): void {
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn(
				(object) array(
					'title'    => 'Division Title Only',
					'event_id' => 5,
				)
			);
		$this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 12, '`title`, `event_id`' );

		$this->assertSame( 'Division Title Only', $snapshot['title'] );
		$this->assertSame( 5, $snapshot['event_id'] );
		$this->assertSame( array(), $snapshot['seasons'] );
	}

	/**
	 * Test capture_snapshot does not fall back to name when title is empty on cached League.
	 */
	public function test_capture_snapshot_from_cached_league_ignores_name_when_title_empty(): void {
		$league        = new League();
		$league->id    = 22;
		$league->title = '';
		$league->name  = 'Championship Name';
		wp_cache_set( 22, $league, 'leagues' );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 22 );

		$this->assertNull( $snapshot['title'] );

		$invalidator->invalidate_after_delete( 22, $snapshot );
		$this->assertNotContains( array( 'Championship Name', 'leagues' ), $this->deleted_keys() );
		$invalidator->invalidate_after_update_title( 22, 'New Title', $snapshot );
		$this->assertNotContains( array( 'Championship Name', 'leagues' ), $this->deleted_keys() );
	}

	/**
	 * Test capture_snapshot does not fall back to name when DB row title is empty.
	 */
	public function test_capture_snapshot_from_db_row_ignores_name_when_title_empty(): void {
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn(
				(object) array(
					'id'       => 23,
					'title'    => '',
					'name'     => 'DB Name Fallback',
					'event_id' => 2,
				)
			);
		$this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 23 );

		$this->assertNull( $snapshot['title'] );
		$this->assertSame( 2, $snapshot['event_id'] );

		$invalidator->invalidate_after_delete( 23, $snapshot );
		$this->assertNotContains( array( 'DB Name Fallback', 'leagues' ), $this->deleted_keys() );
	}

	/**
	 * Collect deleted cache entries as [ key, group ] pairs.
	 *
	 * @return array<int, array{0: mixed, 1: string}>
	 */
	private function deleted_keys(): array {
		return $GLOBALS['wp_stubs_cache_deleted'];
	}

	/**
	 * Test capture_snapshot preserves exact query columns in prepared SQL.
	 */
	public function test_capture_snapshot_preserves_custom_columns_in_db_query(): void {
		$expected_sql = implode( ' ', array( 'SELECT', '`title`, `event_id`', 'FROM', 'wp_racketmanager_leagues', 'WHERE', '`id` = %d', 'LIMIT 1' ) );
		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with( $expected_sql, 24 )
			->willReturn( 'prepared-league-query' );
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( (object) array( 'title' => 'Title Only', 'event_id' => 6 ) );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 24, '`title`, `event_id`' );

		$this->assertSame( 'Title Only', $snapshot['title'] );
		$this->assertSame( 6, $snapshot['event_id'] );
	}

	/**
	 * Test capture_snapshot returns nulls and empty array when not in cache and not in DB.
	 */
	public function test_capture_snapshot_returns_empty_when_not_found(): void {
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( null );
		$this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = $invalidator->capture_snapshot( 99 );

		$this->assertNull( $snapshot['title'] );
		$this->assertNull( $snapshot['event_id'] );
		$this->assertSame( array(), $snapshot['seasons'] );
	}

	/**
	 * Test capture_snapshot returns empty array when wpdb is null.
	 */
	public function test_capture_snapshot_returns_empty_when_wpdb_is_null(): void {
		$invalidator = new League_Cache_Invalidator( null, '' );
		$snapshot    = $invalidator->capture_snapshot( 100 );

		$this->assertNull( $snapshot['title'] );
		$this->assertNull( $snapshot['event_id'] );
		$this->assertSame( array(), $snapshot['seasons'] );
	}

	/**
	 * Test invalidate_after_delete purges id, title, all match day keys, and bumps event version.
	 */
	public function test_invalidate_after_delete_clears_all_keys(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = array(
			'title'    => 'Deleted League',
			'event_id' => 7,
			'seasons'  => array( '2024', '2025' ),
		);

		$invalidator->invalidate_after_delete( 21, $snapshot );

		$deleted = $GLOBALS['wp_stubs_cache_deleted'];
		$this->assertContains( array( 21, 'leagues' ), $deleted );
		$this->assertContains( array( 'Deleted League', 'leagues' ), $deleted );
		$this->assertContains( array( 'last_21_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'next_21_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'current_21_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'last_21_2025', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'next_21_2025', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'current_21_2025', 'leagues_match_days' ), $deleted );

		$version = wp_cache_get( 'ver_event_leagues_7', 'leagues' );
		$this->assertSame( 1, $version );
	}

	/**
	 * Test invalidate_after_update_title purges id, new title, old title, and bumps event version.
	 */
	public function test_invalidate_after_update_title_clears_both_titles_and_event(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = array(
			'title'    => 'Old Title',
			'event_id' => 8,
			'seasons'  => array(),
		);

		$invalidator->invalidate_after_update_title( 31, 'New Title', $snapshot );

		$deleted = $GLOBALS['wp_stubs_cache_deleted'];
		$this->assertContains( array( 31, 'leagues' ), $deleted );
		$this->assertContains( array( 'New Title', 'leagues' ), $deleted );
		$this->assertContains( array( 'Old Title', 'leagues' ), $deleted );

		$version = wp_cache_get( 'ver_event_leagues_8', 'leagues' );
		$this->assertSame( 1, $version );
	}

	/**
	 * Test invalidate_after_update_title does not duplicate delete if title has not changed.
	 */
	public function test_invalidate_after_update_title_skips_old_title_if_identical(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = array(
			'title'    => 'Same Title',
			'event_id' => null,
			'seasons'  => array(),
		);

		$invalidator->invalidate_after_update_title( 32, 'Same Title', $snapshot );

		$deleted = $GLOBALS['wp_stubs_cache_deleted'];
		$this->assertContains( array( 32, 'leagues' ), $deleted );
		$this->assertContains( array( 'Same Title', 'leagues' ), $deleted );
		// Ensure only 2 deletions took place (id and title)
		$this->assertCount( 2, $deleted );
	}

	/**
	 * Test invalidate_after_update_seasons merges old and new seasons and clears all match day keys.
	 */
	public function test_invalidate_after_update_seasons_merges_and_clears_all_seasons(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = array(
			'title'    => 'League Seasons',
			'event_id' => 12,
			'seasons'  => array( '2023', '2024' ),
		);

		$invalidator->invalidate_after_update_seasons( 40, array( '2024' => array(), '2025' => array() ), $snapshot );

		$deleted = $GLOBALS['wp_stubs_cache_deleted'];
		$this->assertContains( array( 40, 'leagues' ), $deleted );
		$this->assertContains( array( 'League Seasons', 'leagues' ), $deleted );
		$this->assertContains( array( 'last_40_2023', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'next_40_2023', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'current_40_2023', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'last_40_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'next_40_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'current_40_2024', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'last_40_2025', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'next_40_2025', 'leagues_match_days' ), $deleted );
		$this->assertContains( array( 'current_40_2025', 'leagues_match_days' ), $deleted );

		$version = wp_cache_get( 'ver_event_leagues_12', 'leagues' );
		$this->assertSame( 1, $version );
	}

	/**
	 * Test get_event_leagues_version and invalidate_event_leagues_cache with null and values.
	 */
	public function test_event_leagues_version_get_and_invalidate(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );

		// Null event_id
		$this->assertSame( 0, $invalidator->get_event_leagues_version( null ) );
		$invalidator->invalidate_event_leagues_cache( null );
		$this->assertSame( 0, $invalidator->get_event_leagues_version( null ) );

		// Initial version for event 99
		$this->assertSame( 0, $invalidator->get_event_leagues_version( 99 ) );

		// Invalidate
		$invalidator->invalidate_event_leagues_cache( 99 );
		$this->assertSame( 1, $invalidator->get_event_leagues_version( 99 ) );

		// Invalidate again
		$invalidator->invalidate_event_leagues_cache( 99 );
		$this->assertSame( 2, $invalidator->get_event_leagues_version( 99 ) );
	}

	/**
	 * Test invalidation ignores event_id 0 and does not write cache version.
	 */
	public function test_invalidate_handles_event_id_zero_without_bumping_cache(): void {
		$invalidator = new League_Cache_Invalidator( $this->wpdb, 'wp_racketmanager_leagues' );
		$snapshot    = array(
			'title'    => 'Zero Event League',
			'event_id' => 0,
			'seasons'  => array(),
		);

		$invalidator->invalidate_after_delete( 35, $snapshot );
		$invalidator->invalidate_after_update_title( 35, 'New Title', $snapshot );
		$invalidator->invalidate_after_update_seasons( 35, array(), $snapshot );

		$version = wp_cache_get( 'ver_event_leagues_0', 'leagues' );
		$this->assertFalse( $version );
		$this->assertSame( 0, $invalidator->get_event_leagues_version( 0 ) );
		$invalidator->invalidate_event_leagues_cache( 0 );
		$this->assertSame( 0, $invalidator->get_event_leagues_version( 0 ) );
	}
}
