<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Event_Repository_Cache_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private $competition_repository;
    private Event_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();

        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->competition_repository = $this->createStub( Competition_Repository_Interface::class );
        $this->repository             = new Event_Repository( $this->wpdb, $this->competition_repository );
    }

    protected function tearDown(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = false;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_update_name_clears_id_and_name_cache(): void {
        $event = new Event( (object) array( 'id' => 30, 'name' => 'Old Event Name' ) );
        wp_cache_set( 30, $event, 'events' );

        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->update_name( 30, 'New Event Name' );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 30, 'events' ), $deleted );
        $this->assertContains( array( 'New Event Name', 'events' ), $deleted );
        $this->assertContains( array( 'Old Event Name', 'events' ), $deleted );
    }

    public function test_update_settings_and_rules_clears_events_cache(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->update_settings_and_rules( 35, array( 'format' => 'sets' ), 4, 3, 'mens' );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 35, 'events' ), $deleted );
    }

    public function test_update_seasons_clears_events_cache(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $result = $this->repository->update_seasons( 40, array( '2026' => array( 'rounds' => 5 ) ) );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 40, 'events' ), $deleted );
    }

    public function test_delete_clears_id_and_name_cache(): void {
        $event = new Event( (object) array( 'id' => 45, 'name' => 'Event To Delete' ) );
        wp_cache_set( 45, $event, 'events' );

        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );

        $result = $this->repository->delete( 45 );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 45, 'events' ), $deleted );
        $this->assertContains( array( 'Event To Delete', 'events' ), $deleted );
    }

    public function test_save_clears_cache_on_update(): void {
        $event = new Event( (object) array(
            'id'             => 50,
            'name'           => 'Updated Event',
            'type'           => 'singles',
            'num_sets'       => 3,
            'num_rubbers'    => 2,
            'competition_id' => 1,
            'settings'       => array(),
            'seasons'        => array(),
        ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( 1 );

        $result = $this->repository->save( $event );
        $this->assertTrue( $result );

        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 50, 'events' ), $deleted );
        $this->assertContains( array( 'Updated Event', 'events' ), $deleted );
    }

    public function test_read_after_write_queries_db_again(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_row' )
            ->willReturnOnConsecutiveCalls(
                (object) array( 'id' => 30, 'name' => 'Original Event', 'type' => 'singles', 'competition_id' => 1 ),
                (object) array( 'id' => 30, 'name' => 'Renamed Event', 'type' => 'singles', 'competition_id' => 1 )
            );
        $this->wpdb->method( 'query' )->willReturn( 1 );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        // 1st read: queries DB and caches
        $res1 = $this->repository->find_by_id( 30 );
        $this->assertSame( 'Original Event', $res1->name );

        // 2nd read: cached, no DB query
        $res2 = $this->repository->find_by_id( 30 );
        $this->assertSame( 'Original Event', $res2->name );

        // Write: updates name and invalidates cache
        $this->repository->update_name( 30, 'Renamed Event' );

        // 3rd read: queries DB again and gets updated name
        $res3 = $this->repository->find_by_id( 30 );
        $this->assertSame( 'Renamed Event', $res3->name );
    }
}
