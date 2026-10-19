<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\User;
use Racketmanager\Repositories\User_Repository;

class User_Repository_Test extends TestCase {
    private User_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->repository = new User_Repository();
        $GLOBALS['wp_stubs_users'] = [];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_users'] );
        parent::tearDown();
    }

    public function test_find_with_integer_id_returns_user(): void {
        $user = $this->repository->find( 101 );
        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 101, $user->ID );
    }

    public function test_find_with_numeric_string_id_returns_user(): void {
        $user = $this->repository->find( '102' );
        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 102, $user->ID );
    }

    public function test_find_with_zero_or_empty_or_negative_returns_null(): void {
        $this->assertNull( $this->repository->find( 0 ) );
        $this->assertNull( $this->repository->find( -5 ) );
        $this->assertNull( $this->repository->find( '' ) );
    }

    public function test_find_with_login_username_found(): void {
        $GLOBALS['wp_stubs_users']['login']['alice'] = (object) [ 'ID' => 103 ];

        $user = $this->repository->find( 'alice' );
        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 103, $user->ID );
    }

    public function test_find_with_login_username_not_found(): void {
        $user = $this->repository->find( 'unknown_user' );
        $this->assertNull( $user );
    }

    public function test_find_user_not_found_in_db_returns_null(): void {
        $GLOBALS['wp_stubs_users_not_found'][999] = true;
        $this->assertNull( $this->repository->find( 999 ) );
    }

    public function test_find_by_id_handles_null_int_and_string(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );

        $user_int = $this->repository->find_by_id( 104 );
        $this->assertInstanceOf( User::class, $user_int );
        $this->assertSame( 104, $user_int->ID );

        $user_str = $this->repository->find_by_id( '105' );
        $this->assertInstanceOf( User::class, $user_str );
        $this->assertSame( 105, $user_str->ID );
    }

    public function test_save_and_delete_return_false(): void {
        $user = new User();
        $this->assertFalse( $this->repository->save( $user ) );
        $this->assertFalse( $this->repository->delete( 123 ) );
    }
}
