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
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unit test setup of global wpdb mock.
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

        $query          = 'SELECT * FROM wp_racketmanager_club_players WHERE id = %d';
        $prepared_query = 'prepared registration query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 5 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
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

        $this->wpdb->expects( $this->never() )->method( 'prepare' );
        $this->wpdb->expects( $this->never() )->method( 'get_row' );

        $club_player = $this->repository->find( 7 );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 7, $club_player->get_id() );
    }

    public function test_find_returns_null_when_row_missing(): void {
        $query          = 'SELECT * FROM wp_racketmanager_club_players WHERE id = %d';
        $prepared_query = 'prepared missing registration query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 99 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
            ->willReturn( null );

        $this->assertNull( $this->repository->find( 99 ) );
    }

    public function test_save_insert_persists_new_entity_sets_id_and_flushes_cache(): void {
        $club_player = Club_Player::from_array(
            array(
                'player_id'      => 14,
                'club_id'        => 3,
                'status'         => 'pending',
                'requested_date' => '2026-01-10 09:00:00',
                'requested_user' => 1,
                'created_date'   => '2026-01-11 10:00:00',
                'created_user'   => 2,
                'removed_date'   => null,
                'removed_user'   => null,
                'system_record'  => false,
            )
        );

        $GLOBALS['wp_stubs_cache']['club_players'] = array( 'key1' => 'value1' );
        $this->wpdb->insert_id                     = 55;

        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_club_players',
                $this->identicalTo(
                    array(
                        'player_id'      => 14,
                        'club_id'        => 3,
                        'requested_date' => '2026-01-10 09:00:00',
                        'requested_user' => 1,
                        'created_date'   => '2026-01-11 10:00:00',
                        'created_user'   => 2,
                        'removed_date'   => null,
                        'removed_user'   => null,
                        'system_record'  => false,
                        'status'         => 'pending',
                    )
                ),
                $this->identicalTo(
                    array(
                        '%d',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                    )
                )
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $club_player );

        $this->assertSame( 55, $result );
        $this->assertSame( 55, $club_player->get_id() );
        $this->assertArrayNotHasKey( 'club_players', $GLOBALS['wp_stubs_cache'] );
    }

    public function test_save_insert_returns_false_when_insert_fails(): void {
        $club_player = Club_Player::from_array(
            array(
                'player_id' => 14,
                'club_id'   => 3,
            )
        );

        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->willReturn( false );

        $this->assertFalse( $this->repository->save( $club_player ) );
        $this->assertNull( $club_player->get_id() );
    }

    public function test_save_update_persists_existing_entity_and_flushes_cache(): void {
        $club_player = Club_Player::from_array(
            array(
                'id'             => 42,
                'player_id'      => 25,
                'club_id'        => 8,
                'status'         => 'approved',
                'requested_date' => '2026-01-01 12:00:00',
                'requested_user' => 4,
                'created_date'   => '2026-01-02 14:00:00',
                'created_user'   => 7,
                'removed_date'   => null,
                'removed_user'   => null,
                'system_record'  => true,
            )
        );

        $GLOBALS['wp_stubs_cache']['club_players'] = array( 'key2' => 'value2' );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_club_players',
                $this->identicalTo(
                    array(
                        'player_id'      => 25,
                        'club_id'        => 8,
                        'requested_date' => '2026-01-01 12:00:00',
                        'requested_user' => 4,
                        'created_date'   => '2026-01-02 14:00:00',
                        'created_user'   => 7,
                        'removed_date'   => null,
                        'removed_user'   => null,
                        'system_record'  => true,
                        'status'         => 'approved',
                    )
                ),
                $this->identicalTo(
                    array(
                        'id' => 42,
                    )
                ),
                $this->identicalTo(
                    array(
                        '%d',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                    )
                ),
                $this->identicalTo(
                    array(
                        '%d',
                    )
                )
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $club_player );

        $this->assertTrue( $result );
        $this->assertArrayNotHasKey( 'club_players', $GLOBALS['wp_stubs_cache'] );
    }

    public function test_save_update_returns_false_when_update_fails(): void {
        $club_player = new Club_Player();
        $club_player->set_id( 42 );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( false );

        $this->assertFalse( $this->repository->save( $club_player ) );
    }

    public function test_find_by_id_returns_hydrated_club_player_when_row_exists(): void {
        $row = (object) array(
            'id'             => '10',
            'player_id'      => '15',
            'system_record'  => '1',
            'club_id'        => '4',
            'removed_date'   => null,
            'removed_user'   => null,
            'created_date'   => '2026-01-01 00:00:00',
            'created_user'   => '1',
            'requested_date' => '2025-12-01',
            'requested_user' => '2',
            'status'         => 'approved',
        );

        $query          = 'SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user`, `status` FROM wp_racketmanager_club_players WHERE id = %d';
        $prepared_query = 'prepared registration by ID query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 10 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
            ->willReturn( $row );

        $club_player = $this->repository->find_by_id( 10 );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 10, $club_player->get_id() );
        $this->assertSame( 15, $club_player->get_player_id() );
        $this->assertSame( 4, $club_player->get_club_id() );
        $this->assertSame( 'approved', $club_player->get_status() );
        $this->assertTrue( $club_player->is_active() );
        $this->assertTrue( $club_player->is_system_record() );
    }

    public function test_find_by_id_returns_null_when_row_missing(): void {
        $query          = 'SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user`, `status` FROM wp_racketmanager_club_players WHERE id = %d';
        $prepared_query = 'prepared missing registration by ID query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 999 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
            ->willReturn( null );

        $this->assertNull( $this->repository->find_by_id( 999 ) );
    }

    public function test_find_by_club_and_player_returns_hydrated_club_player(): void {
        $row = (object) array(
            'id'        => '12',
            'club_id'   => '4',
            'player_id' => '18',
            'status'    => 'active',
        );

        $query          = 'SELECT * FROM wp_racketmanager_club_players WHERE club_id = %d AND player_id = %d AND `removed_date` IS NULL';
        $prepared_query = 'prepared club and player query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 4, 18 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
            ->willReturn( $row );

        $club_player = $this->repository->find_by_club_and_player( 4, 18 );

        $this->assertInstanceOf( Club_Player::class, $club_player );
        $this->assertSame( 12, $club_player->get_id() );
        $this->assertSame( 4, $club_player->get_club_id() );
        $this->assertSame( 18, $club_player->get_player_id() );
    }

    public function test_find_by_club_and_player_returns_null_when_missing(): void {
        $query          = 'SELECT * FROM wp_racketmanager_club_players WHERE club_id = %d AND player_id = %d AND `removed_date` IS NULL';
        $prepared_query = 'prepared missing club and player query';
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $query, 4, 999 )
            ->willReturn( $prepared_query );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( $prepared_query )
            ->willReturn( null );

        $this->assertNull( $this->repository->find_by_club_and_player( 4, 999 ) );
    }

    public function test_delete_deletes_record_and_returns_true(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with(
                'wp_racketmanager_club_players',
                array( 'id' => 50 ),
                array( '%d' )
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->delete( 50 ) );
    }

    public function test_delete_returns_false_when_delete_fails(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( false );

        $this->assertFalse( $this->repository->delete( 50 ) );
    }

    public function test_delete_for_club_deletes_records_and_returns_true(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with(
                'wp_racketmanager_club_players',
                array( 'club_id' => 6 ),
                array( '%d' )
            )
            ->willReturn( 3 );

        $this->assertTrue( $this->repository->delete_for_club( 6 ) );
    }

    public function test_delete_for_club_returns_false_when_delete_fails(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( false );

        $this->assertFalse( $this->repository->delete_for_club( 6 ) );
    }
}
