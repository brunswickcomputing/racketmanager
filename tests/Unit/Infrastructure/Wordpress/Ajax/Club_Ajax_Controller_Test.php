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

        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'update_club' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['club_id'] ?? 0 ) === 12;
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->update_club();
    }

    public function test_club_player_request_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'club' => 9 );

        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'club_player_request' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['club'] ?? 0 ) === 9;
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->club_player_request();
    }

    public function test_club_player_remove_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'clubPlayer' => array( 3, 4 ) );

        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'club_player_remove' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['clubPlayer'] ?? array() ) === array( 3, 4 );
            } ) )
            ->willReturn( new Response( 'removed', 200 ) );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( 'removed' );
        $controller->club_player_remove();
    }

    public function test_show_club_role_modal_passes_request_to_adapter(): void {
        $_POST = array( 'security' => 'test_nonce', 'clubRoleId' => 9, 'modal' => 'roleModal' );

        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'show_club_role_modal' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['clubRoleId'] ?? 0 ) === 9 && ( $req['modal'] ?? '' ) === 'roleModal';
            } ) )
            ->willReturn( new Response( '<div>modal</div>', 200 ) );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '<div>modal</div>' );
        $controller->show_club_role_modal();
    }

    public function test_set_club_role_passes_request_to_adapter(): void {
        $_POST = array( 'racketmanager_nonce' => 'test_nonce', 'clubRoleId' => 9, 'userId' => 5 );

        $adapter = $this->createMock( Club_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'set_club_role' )
            ->with( $this->callback( function ( array $req ) {
                return ( $req['clubRoleId'] ?? 0 ) === 9 && ( $req['userId'] ?? 0 ) === 5;
            } ) )
            ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );

        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( '{"msg":"ok"}' );
        $controller->set_club_role();
    }
}
