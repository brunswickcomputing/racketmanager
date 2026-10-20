<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Message;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Message_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Message_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;
        $this->repository = new Message_Repository( $this->wpdb );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_returns_null_for_non_numeric_and_invalid_ids(): void {
        $this->assertNull( $this->repository->find( 'abc' ) );
        $this->assertNull( $this->repository->find( 0 ) );
        $this->assertNull( $this->repository->find( -5 ) );
    }

    public function test_find_returns_message_from_cache(): void {
        $message = $this->createStub( Message::class );
        $message->id = 42;
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['message']['42'] = $message;

        $result = $this->repository->find( 42 );
        $this->assertInstanceOf( Message::class, $result );
        $this->assertSame( 42, $result->id );

        $result_string = $this->repository->find( '42' );
        $this->assertInstanceOf( Message::class, $result_string );
        $this->assertSame( 42, $result_string->id );
    }

    public function test_find_queries_db_when_not_cached(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( "SELECT `id`, `userid`, `date`, `status`, `subject`, `sender`, `message_object` FROM `wp_racketmanager_messages` WHERE `id` = 50 LIMIT 1" );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( (object) [
                'id'             => 50,
                'userid'         => 10,
                'date'           => '2026-01-01 10:00:00',
                'status'         => '1',
                'subject'        => 'Test Subject',
                'sender'         => 'admin@test.com',
                'message_object' => 'Test body',
            ] );

        $result = $this->repository->find( 50 );
        $this->assertInstanceOf( Message::class, $result );
        $this->assertSame( 50, $result->id );
        $this->assertSame( 'Test Subject', $result->subject );
    }

    public function test_find_by_id_handles_null(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
    }

    public function test_save_inserts_new_message(): void {
        $message = new Message();
        $message->userid = 10;
        $message->date = '2026-01-01';
        $message->status = '1';
        $message->subject = 'New';
        $message->sender = 'sender@test.local';
        $message->message_object = 'body';

        $this->wpdb->insert_id = 99;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_messages',
                $this->callback( function ( $data ) {
                    return 10 === $data['userid'] && 'New' === $data['subject'];
                } ),
                $this->callback( 'is_array' )
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $message );
        $this->assertSame( 99, $result );
        $this->assertSame( 99, $message->id );
    }

    public function test_save_updates_existing_message(): void {
        $message = new Message();
        $message->id = 99;
        $message->userid = 10;
        $message->date = '2026-01-01';
        $message->status = '0';
        $message->subject = 'Updated';
        $message->sender = 'sender@test.local';
        $message->message_object = 'body';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_messages',
                $this->callback( function ( $data ) {
                    return '0' === $data['status'] && 'Updated' === $data['subject'];
                } ),
                [ 'id' => 99 ],
                $this->callback( 'is_array' ),
                [ '%d' ]
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $message );
        $this->assertSame( 99, $result );
    }

    public function test_delete_removes_message(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with(
                'wp_racketmanager_messages',
                [ 'id' => 123 ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->delete( 123 ) );
    }

    public function test_delete_by_user_and_type(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with(
                'wp_racketmanager_messages',
                [ 'status' => '0', 'userid' => 10 ],
                [ '%s', '%d' ]
            )
            ->willReturn( 3 );

        $this->assertSame( 3, $this->repository->delete_by_user_and_type( 10, 'read' ) );
    }

    public function test_delete_by_user_and_type_returns_zero_when_no_rows_were_deleted(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 0 );

        $this->assertSame( 0, $this->repository->delete_by_user_and_type( 10, 'read' ) );
    }

    public function test_delete_by_user_and_type_preserves_database_failure(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( false );

        $this->assertFalse( $this->repository->delete_by_user_and_type( 10, 'read' ) );
    }

    public function test_find_by_user_maps_unread_and_read_status_and_counts(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'prepare' )
            ->willReturnCallback(
                static function ( string $query, ...$params ) {
                    return $query . '|' . implode( ',', $params );
                }
            );
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_var' )
            ->willReturnOnConsecutiveCalls( '4', '2' );

        $unread = $this->repository->find_by_user( 10, [ 'count' => true, 'status' => 'unread' ] );
        $read   = $this->repository->find_by_user( 10, [ 'count' => true, 'status' => 'read' ] );

        $this->assertSame( 4, $unread );
        $this->assertSame( 2, $read );
    }

    public function test_find_by_user_returns_ordered_list_and_uses_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'prepare' )
            ->willReturn( 'SELECT `id` FROM `wp_racketmanager_messages` WHERE `userid` = 10 ORDER BY `date` DESC' );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( [ (object) [ 'id' => 7 ], (object) [ 'id' => 8 ] ] );

        $message_seven = new Message();
        $message_seven->id = 7;
        $message_eight = new Message();
        $message_eight->id = 8;
        $GLOBALS['wp_stubs_cache']['message']['7'] = $message_seven;
        $GLOBALS['wp_stubs_cache']['message']['8'] = $message_eight;

        $first  = $this->repository->find_by_user( 10, [] );
        $second = $this->repository->find_by_user( 10, [] );

        $this->assertCount( 2, $first );
        $this->assertSame( 7, $first[0]->id );
        $this->assertSame( 8, $first[1]->id );
        $this->assertCount( 2, $second );
    }
}
