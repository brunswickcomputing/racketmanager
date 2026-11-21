<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Player\User_Factory;
use Racketmanager\Repositories\User_Repository;

class User_Repository_Test extends TestCase {
    private User_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->repository = new User_Repository();
        $GLOBALS['wp_stubs_users'] = [];
        $GLOBALS['wp_stubs_userdata'] = [];
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_users'],
            $GLOBALS['wp_stubs_userdata'],
            $GLOBALS['wp_stubs_users_not_found'],
            $GLOBALS['wp_stubs_user_meta'],
            $GLOBALS['wp_stubs_cache'],
            $GLOBALS['wp_stubs_cache_enabled'],
            $GLOBALS['wp_stubs_cache_deleted'],
            $GLOBALS['wp_stubs_wp_update_user'],
            $GLOBALS['wp_stubs_wp_update_user_error'],
            $GLOBALS['wp_stubs_wp_set_password'],
            $GLOBALS['wp_stubs_wp_set_auth_cookie'],
            $GLOBALS['wp_stubs_update_user_meta']
        );
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

    public function test_find_hydrates_string_database_id_and_reads_fresh_meta(): void {
        $GLOBALS['wp_stubs_userdata'][77] = (object) [
            'data' => (object) [
                'ID'           => '77',
                'user_email'   => 'db@example.test',
                'display_name' => 'Database User',
            ],
        ];
        $GLOBALS['wp_stubs_user_meta'][77]['first_name'] = [ 'First' ];

        $user = $this->repository->find( 77 );
        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 77, $user->id );
        $this->assertSame( 'db@example.test', $user->email );
        $this->assertSame( 'First', $user->firstname );

        $GLOBALS['wp_stubs_user_meta'][77]['first_name'] = [ 'Updated' ];
        $fresh_user = $this->repository->find( 77 );
        $this->assertNotSame( $user, $fresh_user );
        $this->assertSame( 'Updated', $fresh_user->firstname );
    }

    public function test_find_hydrates_all_metadata_fields(): void {
        $GLOBALS['wp_stubs_userdata'][8] = (object) [
            'ID'           => 8,
            'display_name' => 'Admin Boss',
        ];
        $GLOBALS['wp_stubs_userdata'][78] = (object) [
            'data' => (object) [
                'ID'           => '78',
                'user_email'   => 'full@example.test',
                'display_name' => 'Full User',
            ],
        ];
        $GLOBALS['wp_stubs_user_meta'][78] = [
            'first_name'           => [ 'John' ],
            'last_name'            => [ 'Doe' ],
            'gender'               => [ 'M' ],
            'racketmanager_type'   => [ 'player' ],
            'btm'                  => [ '123456' ],
            'year_of_birth'        => [ '1990' ],
            'contactno'            => [ '07123456789' ],
            'remove_date'          => [ '2026-01-15' ],
            'remove_user'          => [ '4' ],
            'locked'               => [ '1' ],
            'locked_date'          => [ '2026-02-01' ],
            'locked_user'          => [ '8' ],
            'racketmanager_opt_in' => [ 1, 2 ],
        ];

        $user = $this->repository->find( 78 );
        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 78, $user->id );
        $this->assertSame( 78, $user->ID );
        $this->assertSame( 'full@example.test', $user->email );
        $this->assertSame( 'Full User', $user->display_name );
        $this->assertSame( 'John', $user->firstname );
        $this->assertSame( 'Doe', $user->surname );
        $this->assertSame( 'M', $user->gender );
        $this->assertSame( 'player', $user->type );
        $this->assertSame( '123456', $user->btm );
        $this->assertSame( '1990', $user->year_of_birth );
        $this->assertSame( (int) gmdate( 'Y' ) - 1990, $user->age );
        $this->assertSame( '07123456789', $user->contactno );
        $this->assertSame( '2026-01-15', $user->removed_date );
        $this->assertSame( '4', $user->removed_user );
        $this->assertSame( '1', $user->locked );
        $this->assertSame( '2026-02-01', $user->locked_date );
        $this->assertSame( '8', $user->locked_user );
        $this->assertSame( 'Admin Boss', $user->locked_user_name );
        $this->assertSame( 'player', $user->system_record );
        $this->assertSame( [ '1', '2' ], $user->opt_ins );
    }

    public function test_find_with_empty_metadata_preserves_empty_strings_and_defaults(): void {
        $GLOBALS['wp_stubs_userdata'][79] = (object) [
            'data' => (object) [
                'ID'           => '79',
                'user_email'   => 'empty@example.test',
                'display_name' => 'Empty User',
            ],
        ];
        $GLOBALS['wp_stubs_user_meta'][79] = [];

        $user = $this->repository->find( 79 );
        $this->assertInstanceOf( User::class, $user );

        $empty_string_fields = [
            'firstname',
            'surname',
            'gender',
            'type',
            'btm',
            'year_of_birth',
            'contactno',
            'removed_date',
            'removed_user',
            'locked',
            'locked_date',
            'locked_user',
            'locked_user_name',
            'system_record',
        ];

        foreach ( $empty_string_fields as $field ) {
            $this->assertTrue( isset( $user->$field ), "Field {$field} should be set" );
            $this->assertSame( '', $user->$field, "Field {$field} should be empty string" );
        }

        $this->assertTrue( isset( $user->age ) );
        $this->assertSame( 0, $user->age );

        $this->assertTrue( isset( $user->opt_ins ) );
        $this->assertSame( [], $user->opt_ins );
    }

    public function test_save_writes_changed_fields_password_and_opt_ins(): void {
        $GLOBALS['wp_stubs_user_meta'][55]['first_name'] = [ 'Old' ];
        $GLOBALS['wp_stubs_user_meta'][55]['racketmanager_opt_in'] = [ '1' ];
        $GLOBALS['wp_stubs_wp_set_password'] = [];
        $GLOBALS['wp_stubs_wp_set_auth_cookie'] = [];
        $GLOBALS['wp_stubs_update_user_meta'] = [];

        $user = User_Factory::from_array(
            [
                'id'            => 55,
                'email'         => 'new@example.test',
                'firstname'     => 'Jane',
                'surname'       => 'Doe',
                'contactno'     => '07111',
                'gender'        => 'F',
                'btm'           => 99,
                'year_of_birth' => 1988,
                'password'      => 'secret',
                'opt_ins'       => [ '1' ],
            ]
        );

        $result = $this->repository->save( $user );
        $this->assertSame( 55, $result );
        $this->assertNotEmpty( $GLOBALS['wp_stubs_wp_update_user'] );
        $this->assertSame( [ 'secret', 55 ], $GLOBALS['wp_stubs_wp_set_password'][0] );
        $this->assertSame( [ 55, 1, true ], $GLOBALS['wp_stubs_wp_set_auth_cookie'][0] );
        $meta_keys = array_column( $GLOBALS['wp_stubs_update_user_meta'], 1 );
        $this->assertContains( 'first_name', $meta_keys );
        $this->assertContains( 'contactno', $meta_keys );
    }

    public function test_save_skips_password_when_empty_and_returns_zero_when_unchanged(): void {
        $GLOBALS['wp_stubs_wp_set_password'] = [];
        $GLOBALS['wp_stubs_wp_set_auth_cookie'] = [];
        $GLOBALS['wp_stubs_wp_update_user'] = [];
        $GLOBALS['wp_stubs_update_user_meta'] = [];

        $user = User_Factory::from_array(
            [
                'id'            => 101,
                'email'         => 'user101@example.test',
                'password'      => '',
                'firstname'     => '',
                'surname'       => '',
                'contactno'     => '',
                'gender'        => '',
                'btm'           => '',
                'year_of_birth' => '',
            ]
        );

        $result = $this->repository->save( $user );
        $this->assertSame( 0, $result );
        $this->assertSame( [], $GLOBALS['wp_stubs_wp_set_password'] );
        $this->assertSame( [], $GLOBALS['wp_stubs_wp_set_auth_cookie'] );
    }

    public function test_save_adds_and_removes_opt_ins(): void {
        $GLOBALS['wp_stubs_user_meta'][12]['racketmanager_opt_in'] = [ '1' ];
        $user = User_Factory::from_array(
            [
                'id'      => 12,
                'email'   => 'user12@example.test',
                'opt_ins' => [],
            ]
        );

        $this->assertSame( 12, $this->repository->save( $user ) );
        $this->assertSame( [], $GLOBALS['wp_stubs_user_meta'][12]['racketmanager_opt_in'] );

        $user->set_opt_ins( [ '1' ] );
        $this->assertSame( 12, $this->repository->save( $user ) );
        $this->assertSame( [ '1' ], $GLOBALS['wp_stubs_user_meta'][12]['racketmanager_opt_in'] );
    }

    public function test_update_contact_returns_false_on_wp_error(): void {
        $GLOBALS['wp_stubs_wp_update_user_error'] = 'bad email';
        $user = User_Factory::from_array(
            [
                'id'         => 9,
                'user_email' => 'old@example.test',
            ]
        );

        $this->assertFalse( $this->repository->update_contact( $user, '07700', 'new@example.test' ) );
        unset( $GLOBALS['wp_stubs_wp_update_user_error'] );
    }
}
