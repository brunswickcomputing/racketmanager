<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Message;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Message_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Message_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->repository = new Message_Repository();
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

    public function test_find_returns_message_for_valid_id(): void {
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

    public function test_find_by_id_handles_null(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
    }

    public function test_save_and_delete_return_false(): void {
        $this->assertFalse( $this->repository->save( (object) [] ) );
        $this->assertFalse( $this->repository->delete( 1 ) );
    }
}
