<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Action_Adapter_Test extends TestCase {

    private ?string $original_method;

    protected function setUp(): void {
        parent::setUp();
        $this->original_method = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function tearDown(): void {
        if ( null === $this->original_method ) {
            unset( $_SERVER['REQUEST_METHOD'] );
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->original_method;
        }
        parent::tearDown();
    }

    public function test_result_without_redirect_is_not_delivered(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::never() )->method( 'set' );
        $redirector->expects( self::never() )->method( 'redirect' );

        $adapter = new Admin_Action_Adapter( new Admin_Message_Service( $store ), $redirector );

        self::assertFalse( $adapter->deliver_redirect( array( 'message' => 'Hello' ) ) );
    }

    public function test_redirect_stores_flash_message_and_redirects_once(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Saved', 'warning' );
        $redirector->expects( self::once() )->method( 'redirect' )->with( 'https://example.test/admin.php?page=x' );

        $adapter = new Admin_Action_Adapter( new Admin_Message_Service( $store ), $redirector );

        self::assertTrue(
            $adapter->deliver_redirect(
                array(
                    'redirect'     => 'https://example.test/admin.php?page=x',
                    'message'      => 'Saved',
                    'message_type' => 'warning',
                )
            )
        );
    }

    public function test_redirect_without_message_does_not_store_flash(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::never() )->method( 'set' );
        $redirector->expects( self::once() )->method( 'redirect' );

        $adapter = new Admin_Action_Adapter( new Admin_Message_Service( $store ), $redirector );

        self::assertTrue( $adapter->deliver_redirect( array( 'redirect' => 'https://example.test/' ) ) );
    }

    public function test_post_only_redirect_is_ignored_for_get_requests(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $store                     = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector                = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::never() )->method( 'set' );
        $redirector->expects( self::never() )->method( 'redirect' );

        $adapter = new Admin_Action_Adapter( new Admin_Message_Service( $store ), $redirector );

        self::assertFalse( $adapter->deliver_redirect( array( 'redirect' => 'https://example.test/', 'message' => 'x' ), true ) );
    }

    public function test_post_only_redirect_is_delivered_for_post_requests(): void {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $store                     = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector                = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::once() )->method( 'redirect' )->with( 'https://example.test/' );

        $adapter = new Admin_Action_Adapter( new Admin_Message_Service( $store ), $redirector );

        self::assertTrue( $adapter->is_post_request() );
        self::assertTrue( $adapter->deliver_redirect( array( 'redirect' => 'https://example.test/' ), true ) );
    }
}
