<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Tournament_Entry_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Tournament_Entry_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;
        $this->repository = new Tournament_Entry_Repository();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_returns_null_for_empty_id(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
        $this->assertNull( $this->repository->find_by_id( 0 ) );
    }

    public function test_find_by_id_returns_cached_entry(): void {
        $entry = new Tournament_Entry( (object) [
            'id'            => 10,
            'tournament_id' => 1,
            'player_id'     => 2,
            'status'        => 0,
            'fee'           => null,
            'club_id'       => null,
        ] );
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['tournament_entries']['10'] = $entry;

        $result = $this->repository->find_by_id( 10 );
        $this->assertEquals( $entry, $result );
    }

    public function test_save_new_entry_inserts_and_sets_id(): void {
        $entry = new Tournament_Entry();
        $entry->set_tournament_id( 5 );
        $entry->set_player_id( 15 );
        $entry->set_status( 1 );

        $this->wpdb->insert_id = 77;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->willReturn( 1 );

        $result = $this->repository->save( $entry );
        $this->assertSame( 77, $result );
        $this->assertSame( 77, $entry->get_id() );
    }

    public function test_delete_removes_entry(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->delete( 77 ) );
    }
}
