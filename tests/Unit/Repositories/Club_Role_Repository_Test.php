<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Player\User;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Club_Role_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private $user_repository;
    private Club_Role_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();

        $this->wpdb            = $this->createMock( wpdb::class );
        $this->wpdb->prefix    = 'wp_';
        $GLOBALS['wpdb']       = $this->wpdb;

        $this->user_repository = $this->createMock( User_Repository_Interface::class );
        $this->repository      = new Club_Role_Repository( $this->wpdb, $this->user_repository );
    }

    protected function tearDown(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = false;
        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $GLOBALS['wp_stubs_cache']         = array();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_hydrates_user_when_user_id_exists(): void {
        $row = (object) array(
            'id'      => 10,
            'user_id' => 42,
            'club_id' => 5,
            'role_id' => 1,
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $row );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $user = new User( (object) array( 'ID' => 42, 'display_name' => 'Alice Smith' ) );
        $this->user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $user );

        $role = $this->repository->find( 10 );

        $this->assertInstanceOf( Club_Role::class, $role );
        $this->assertSame( 10, $role->get_id() );
        $this->assertSame( 42, $role->get_user_id() );
        $this->assertSame( $user, $role->user );
    }

    public function test_find_leaves_user_null_when_user_id_is_empty(): void {
        $row = (object) array(
            'id'      => 11,
            'user_id' => 0,
            'club_id' => 5,
            'role_id' => 1,
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $row );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $this->user_repository->expects( $this->never() )
            ->method( 'find' );

        $role = $this->repository->find( 11 );

        $this->assertInstanceOf( Club_Role::class, $role );
        $this->assertNull( $role->user );
    }

    public function test_find_leaves_user_null_when_user_repository_returns_null(): void {
        $row = (object) array(
            'id'      => 12,
            'user_id' => 99,
            'club_id' => 5,
            'role_id' => 1,
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $row );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $this->user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 99 )
            ->willReturn( null );

        $role = $this->repository->find( 12 );

        $this->assertInstanceOf( Club_Role::class, $role );
        $this->assertNull( $role->user );
    }

    public function test_search_and_build_club_roles_hydrates_users(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array(
                (object) array( 'id' => 101 ),
                (object) array( 'id' => 102 ),
            ) );

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_row' )
            ->willReturnOnConsecutiveCalls(
                (object) array( 'id' => 101, 'user_id' => 50, 'club_id' => 2, 'role_id' => 1 ),
                (object) array( 'id' => 102, 'user_id' => 51, 'club_id' => 2, 'role_id' => 2 )
            );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $user1 = new User( (object) array( 'ID' => 50, 'display_name' => 'User One' ) );
        $user2 = new User( (object) array( 'ID' => 51, 'display_name' => 'User Two' ) );

        $this->user_repository->expects( $this->exactly( 2 ) )
            ->method( 'find' )
            ->willReturnMap( array(
                array( 50, $user1 ),
                array( 51, $user2 ),
            ) );

        $roles = $this->repository->search( array( 'club' => 2 ) );

        $this->assertCount( 2, $roles );
        $this->assertSame( $user1, $roles[0]->user );
        $this->assertSame( $user2, $roles[1]->user );
    }

    /**
     * Real $wpdb rows return every column as a string; build_club_roles() must cast before calling find( int ).
     */
    public function test_build_club_roles_accepts_string_ids_from_database_rows(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( array( (object) array( 'id' => '16' ) ) );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( (object) array( 'id' => '16', 'user_id' => '0', 'club_id' => '135', 'role_id' => '1' ) );
        $this->wpdb->method( 'prepare' )->willReturnArgument( 0 );

        $roles = $this->repository->search( array( 'club' => 135 ) );

        $this->assertCount( 1, $roles );
        $this->assertSame( 16, $roles[0]->get_id() );
    }

    public function test_save_insert_sets_id_and_flushes_cache(): void {
        $role = new Club_Role( (object) array(
            'club_id' => 7,
            'role_id' => 1,
            'user_id' => 15,
        ) );

        $this->wpdb->insert_id = 77;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->willReturn( 1 );

        $result = $this->repository->save( $role );

        $this->assertSame( 77, $result );
        $this->assertSame( 77, $role->get_id() );
    }

    public function test_delete_removes_record(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );

        $result = $this->repository->delete( 10 );
        $this->assertTrue( $result );
    }
}
