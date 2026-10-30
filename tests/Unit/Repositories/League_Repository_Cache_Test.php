<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class League_Repository_Cache_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private League_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();

        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->repository = new League_Repository( $this->wpdb );
    }

    protected function tearDown(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = false;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_match_day_queries_db_on_cache_miss_and_sets_season_keyed_cache(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( (object) array( 'match_day' => 3 ) );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->get_match_day( 1, '2026', 'current' );
        $this->assertSame( 3, $result );

        // Second call should hit the cache and not invoke get_row again
        $cached_result = $this->repository->get_match_day( 1, '2026', 'current' );
        $this->assertSame( 3, $cached_result );
    }

    public function test_get_match_day_maps_latest_to_current_and_isolates_by_season(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_row' )
            ->willReturnOnConsecutiveCalls(
                (object) array( 'match_day' => 10 ),
                (object) array( 'match_day' => 3 )
            );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        // 2016 season returns 10
        $result_2016 = $this->repository->get_match_day( 1, '2016', 'latest' );
        $this->assertSame( 10, $result_2016 );

        // 2026 season should NOT be poisoned by 2016 and should query DB to return 3
        $result_2026 = $this->repository->get_match_day( 1, '2026', 'current' );
        $this->assertSame( 3, $result_2026 );

        // Verify cached lookup for latest/current on 2016 still returns 10
        $cached_2016 = $this->repository->get_match_day( 1, '2016', 'current' );
        $this->assertSame( 10, $cached_2016 );
    }

    public function test_get_match_day_returns_null_when_no_match_found(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( null );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->get_match_day( 99, '2026', 'next' );
        $this->assertNull( $result );
    }

    public function test_update_title_and_sequence_clears_id_and_title_cache(): void {
        $existing_league = League_Test_Factory::from_object( (object) array( 'id' => 12, 'title' => 'Old Division 1' ) );
        wp_cache_set( 12, $existing_league, 'leagues' );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( 1 );

        $result = $this->repository->update_title_and_sequence( 12, 'New Division 1', '10' );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 12, 'leagues' ), $deleted );
        $this->assertContains( array( 'New Division 1', 'leagues' ), $deleted );
        $this->assertContains( array( 'Old Division 1', 'leagues' ), $deleted );
    }

    public function test_update_seasons_clears_league_cache(): void {
        $existing_league = League_Test_Factory::from_object( (object) array( 'id' => 15, 'title' => 'Division 2' ) );
        wp_cache_set( 15, $existing_league, 'leagues' );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( 1 );

        $result = $this->repository->update_seasons( 15, array( '2026' => array( 'rounds' => 5 ) ) );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 15, 'leagues' ), $deleted );
        $this->assertContains( array( 'Division 2', 'leagues' ), $deleted );
    }

    public function test_delete_clears_league_and_match_day_caches(): void {
        $existing_league = League_Test_Factory::from_object( (object) array(
            'id'      => 20,
            'title'   => 'Premier Division',
            'seasons' => array( '2025' => array(), '2026' => array() ),
        ) );
        wp_cache_set( 20, $existing_league, 'leagues' );

        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );

        $result = $this->repository->delete( 20 );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 20, 'leagues' ), $deleted );
        $this->assertContains( array( 'Premier Division', 'leagues' ), $deleted );
        $this->assertContains( array( 'last_20_2025', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_20_2025', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_20_2025', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'last_20_2026', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_20_2026', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_20_2026', 'leagues_match_days' ), $deleted );
    }

    public function test_get_by_event_id_uses_leagues_cache_group(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array( (object) array( 'id' => 1, 'title' => 'Div 1' ) ) );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->get_by_event_id( 5, 2026 );
        $this->assertCount( 1, $result );

        // Second call should hit the cache in 'leagues' group
        $cached = $this->repository->get_by_event_id( 5, 2026 );
        $this->assertCount( 1, $cached );
    }

    public function test_delete_clears_match_days_and_event_cache_when_league_is_not_cached(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( (object) array(
                'id'       => 25,
                'title'    => 'Uncached Div',
                'event_id' => 7,
                'seasons'  => serialize( array( '2024' => array(), '2025' => array() ) ),
            ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->delete( 25 );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 25, 'leagues' ), $deleted );
        $this->assertContains( array( 'Uncached Div', 'leagues' ), $deleted );
        $this->assertContains( array( 'last_25_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_25_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_25_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'last_25_2025', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_25_2025', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_25_2025', 'leagues_match_days' ), $deleted );

        // Version for event 7 should be incremented in cache
        $version = wp_cache_get( 'ver_event_leagues_7', 'leagues' );
        $this->assertSame( 1, $version );
    }

    public function test_get_by_event_id_invalidated_by_write_read_after_write(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_results' )
            ->willReturnOnConsecutiveCalls(
                array( (object) array( 'id' => 1, 'title' => 'Div 1' ) ),
                array( (object) array( 'id' => 1, 'title' => 'Div 1 Renamed' ) )
            );
        $this->wpdb->method( 'get_row' )
            ->willReturn( (object) array( 'title' => 'Div 1', 'event_id' => 8 ) );
        $this->wpdb->method( 'update' )->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        // 1st read: queries DB
        $res1 = $this->repository->get_by_event_id( 8, 2026 );
        $this->assertSame( 'Div 1', $res1[0]->title );

        // 2nd read: cached, no DB query
        $res2 = $this->repository->get_by_event_id( 8, 2026 );
        $this->assertSame( 'Div 1', $res2[0]->title );

        // Write: updates title and invalidates event 8 version
        $this->repository->update_title_and_sequence( 1, 'Div 1 Renamed' );

        // 3rd read: queries DB again and returns updated list
        $res3 = $this->repository->get_by_event_id( 8, 2026 );
        $this->assertSame( 'Div 1 Renamed', $res3[0]->title );
    }

    public function test_update_seasons_clears_match_days_for_both_old_and_new_seasons(): void {
        $existing_league = League_Test_Factory::from_object( (object) array(
            'id'      => 33,
            'title'   => 'Division 3',
            'seasons' => array( '2024' => array() ),
        ) );
        wp_cache_set( 33, $existing_league, 'leagues' );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( 1 );

        $result = $this->repository->update_seasons( 33, array( '2026' => array( 'rounds' => 4 ) ) );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 33, 'leagues' ), $deleted );
        $this->assertContains( array( 'Division 3', 'leagues' ), $deleted );
        $this->assertContains( array( 'last_33_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_33_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_33_2024', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'last_33_2026', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'next_33_2026', 'leagues_match_days' ), $deleted );
        $this->assertContains( array( 'current_33_2026', 'leagues_match_days' ), $deleted );
    }
}
