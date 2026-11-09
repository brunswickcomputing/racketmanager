<?php
/**
 * Public API Unit Tests
 *
 * @package RacketManager
 * @subpackage Tests/Unit
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Public_Api;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;

final class Public_Api_Test extends TestCase {

    private ?object $original_racketmanager = null;

    protected function setUp(): void {
        parent::setUp();
        $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
        Public_Api::reset();
        Plugin_Bootstrap::reset();
    }

    protected function tearDown(): void {
        Public_Api::reset();
        Plugin_Bootstrap::reset();
        $GLOBALS['racketmanager'] = $this->original_racketmanager;
        unset(
            $GLOBALS['wp_stubs_is_user_logged_in'],
            $GLOBALS['wp_stubs_get_current_user_id'],
            $GLOBALS['wp_stubs_current_user']
        );
        parent::tearDown();
    }

    public function test_init_and_container_resolution(): void {
        $container = new Simple_Container();
        Public_Api::init( $container );

        $this->assertSame( $container, Public_Api::get_container() );
        $this->assertSame( $container, Public_Api::container() );

        Public_Api::reset();
        Plugin_Bootstrap::reset();
        $this->assertNull( Public_Api::get_container() );

        Plugin_Bootstrap::set_container( $container );
        $this->assertSame( $container, Public_Api::get_container() );
    }

    public function test_get_user_returns_null_when_no_container_or_repository(): void {
        Public_Api::reset();
        $GLOBALS['racketmanager'] = null;
        $this->assertNull( Public_Api::get_user( 10 ) );
        $this->assertNull( Public_Api::get_user( 0 ) );
        $this->assertNull( Public_Api::get_user( -1 ) );

        $container = new Simple_Container();
        Public_Api::init( $container );
        $this->assertNull( Public_Api::get_user( 10 ) );
    }

    public function test_get_user_delegates_to_user_repository(): void {
        $user       = new User();
        $user->id   = 42;
        $user->display_name = 'Jane Doe';

        $user_repo = $this->createMock( User_Repository_Interface::class );
        $user_repo->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $user );

        $container = new Simple_Container();
        $container->set( 'user_repository', $user_repo );
        Public_Api::init( $container );

        $result = Public_Api::get_user( 42 );
        $this->assertSame( $user, $result );
    }

    public function test_get_user_returns_null_when_user_not_found(): void {
        $user_repo = $this->createMock( User_Repository_Interface::class );
        $user_repo->expects( $this->once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $container = new Simple_Container();
        $container->set( 'user_repository', $user_repo );
        Public_Api::init( $container );

        $this->assertNull( Public_Api::get_user( 999 ) );
    }

    public function test_seo_url_and_un_seo_url(): void {
        $this->assertSame( 'john-smith', Public_Api::seo_url( 'John Smith' ) );
        $this->assertSame( 'john smith', Public_Api::un_seo_url( 'john-smith' ) );

        $this->assertSame( 'test_with_dashes', Public_Api::seo_url( 'Test-With-Dashes' ) );
        $this->assertSame( 'test-with-dashes', Public_Api::un_seo_url( 'test_with_dashes' ) );

        $this->assertSame( '', Public_Api::seo_url( '' ) );
        $this->assertSame( '', Public_Api::un_seo_url( '' ) );
    }

    public function test_get_unread_message_count_when_repository_missing_or_invalid_id(): void {
        Public_Api::reset();
        $GLOBALS['racketmanager'] = null;
        $this->assertSame( 0, Public_Api::get_unread_message_count( 1 ) );
        $this->assertSame( 0, Public_Api::get_unread_message_count( 0 ) );
        $this->assertSame( 0, Public_Api::get_unread_message_count( -5 ) );

        $container = new Simple_Container();
        Public_Api::init( $container );
        $this->assertSame( 0, Public_Api::get_unread_message_count( 10 ) );
    }

    public function test_get_unread_message_count_delegates_to_message_repository(): void {
        $msg_repo = $this->createMock( Message_Repository_Interface::class );
        $msg_repo->expects( $this->once() )
            ->method( 'find_by_user' )
            ->with( 15, [ 'count' => true, 'status' => 'unread' ] )
            ->willReturn( 3 );

        $container = new Simple_Container();
        $container->set( 'message_repository', $msg_repo );
        Public_Api::init( $container );

        $this->assertSame( 3, Public_Api::get_unread_message_count( 15 ) );
    }

    public function test_get_messages_delegation_and_fallbacks(): void {
        Public_Api::reset();
        $GLOBALS['racketmanager'] = null;
        $this->assertSame( [], Public_Api::get_messages( 15 ) );
        $this->assertSame( 0, Public_Api::get_messages( 15, [ 'count' => true ] ) );
        $this->assertSame( [], Public_Api::get_messages( 0 ) );

        $dummy_messages = [ (object) [ 'id' => 1 ], (object) [ 'id' => 2 ] ];
        $msg_repo       = $this->createMock( Message_Repository_Interface::class );
        $msg_repo->expects( $this->once() )
            ->method( 'find_by_user' )
            ->with( 15, [ 'status' => 'read' ] )
            ->willReturn( $dummy_messages );

        $container = new Simple_Container();
        $container->set( 'message_repository', $msg_repo );
        Public_Api::init( $container );

        $this->assertSame( $dummy_messages, Public_Api::get_messages( 15, [ 'status' => 'read' ] ) );
    }
}
