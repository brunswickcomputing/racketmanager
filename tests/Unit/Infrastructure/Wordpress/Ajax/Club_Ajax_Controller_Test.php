<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Club_Ajax_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET  = array();
        $_POST = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function assert_controller_delegates(
        string $adapter_method,
        callable $callback,
        Response $response,
        string $expected_output,
        string $controller_method
    ): void {
        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( $adapter_method )
            ->with( $this->callback( $callback ) )
            ->willReturn( $response );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( $expected_output );
        $controller->$controller_method();
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = array( 'shared' => 'from_get', 'only_get' => 'get_val' );
        $_POST = array( 'shared' => 'from_post', 'only_post' => 'post_val' );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $request    = $controller->get_request();

        $this->assertSame( 'from_post', $request['shared'] );
        $this->assertSame( 'get_val', $request['only_get'] );
        $this->assertSame( 'post_val', $request['only_post'] );
    }

    public function test_update_club_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'club_id' => 12 );

        $this->assert_controller_delegates(
            'update_club',
            static fn( array $req ): bool => ( $req['club_id'] ?? 0 ) === 12,
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'update_club'
        );
    }

    public function test_club_player_request_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'club' => 9 );

        $this->assert_controller_delegates(
            'club_player_request',
            static fn( array $req ): bool => ( $req['club'] ?? 0 ) === 9,
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'club_player_request'
        );
    }

    public function test_club_player_remove_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'clubPlayer' => array( 3, 4 ) );

        $this->assert_controller_delegates(
            'club_player_remove',
            static fn( array $req ): bool => ( $req['clubPlayer'] ?? array() ) === array( 3, 4 ),
            new Response( 'removed', 200 ),
            'removed',
            'club_player_remove'
        );
    }

    public function test_show_club_role_modal_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'test_nonce', 'clubRoleId' => 9, 'modal' => 'roleModal' );

        $this->assert_controller_delegates(
            'show_club_role_modal',
            static fn( array $req ): bool => ( $req['clubRoleId'] ?? 0 ) === 9 && ( $req['modal'] ?? '' ) === 'roleModal',
            new Response( '<div>modal</div>', 200 ),
            '<div>modal</div>',
            'show_club_role_modal'
        );
    }

    public function test_set_club_role_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'clubRoleId' => 9, 'userId' => 5 );

        $this->assert_controller_delegates(
            'set_club_role',
            static fn( array $req ): bool => ( $req['clubRoleId'] ?? 0 ) === 9 && ( $req['userId'] ?? 0 ) === 5,
            new Response( array( 'msg' => 'ok' ), 200 ),
            '{"msg":"ok"}',
            'set_club_role'
        );
    }
}
