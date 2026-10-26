<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Registration_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private Registration_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = array();

        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->repository = new Registration_Repository();
    }

    protected function tearDown(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = false;
        $GLOBALS['wp_stubs_cache']         = array();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_on_cache_miss_loads_row_and_primes_cache(): void {
        $row = (object) array(
            'id'        => '5',
            'player_id' => '11',
            'club_id'   => '3',
            'status'    => 'active',
        );

        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( 'SELECT * FROM wp_racketmanager_club_players WHERE id = %d' )
            ->willReturn( $row );

        $club_player = $this->repository->find( 5 );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 5, $club_player->get_id() );
        $this->assertSame( 11, $club_player->get_player_id() );
        $this->assertSame( 3, $club_player->get_club_id() );
        $this->assertArrayHasKey( md5( '5' ), $GLOBALS['wp_stubs_cache']['club_players'] );
    }

    public function test_find_on_cache_hit_does_not_query_database(): void {
        wp_cache_set( md5( '7' ), (object) array( 'id' => '7', 'player_id' => '2', 'club_id' => '9' ), 'club_players' );

        $this->wpdb->expects( $this->never() )->method( 'get_row' );

        $club_player = $this->repository->find( 7 );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 7, $club_player->get_id() );
    }

    public function test_find_returns_null_when_row_missing(): void {
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );
        $this->wpdb->method( 'get_row' )->willReturn( null );

        $this->assertNull( $this->repository->find( 99 ) );
    }
}
