<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Player_Errors_Admin_Controller;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

#[AllowMockObjectsWithoutExpectations]
final class Player_Errors_Admin_Controller_Test extends TestCase {

    private Player_Service $player_service;
    private Action_Guard_Interface $action_guard;
    private Player_Error_Action_Dispatcher $dispatcher;
    private Player_Errors_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->player_service = $this->createMock( Player_Service::class );
        $this->action_guard   = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Player_Error_Action_Dispatcher( $this->player_service, $this->action_guard );
        $this->controller     = new Player_Errors_Admin_Controller(
            $this->player_service,
            $this->dispatcher,
            $this->action_guard
        );
    }

    public function test_capability_denial_throws_exception(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Forbidden' );

        $this->controller->player_errors_page( array(), array() );
    }

    public function test_get_player_errors_page_returns_view_model(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $errors = array(
            (object) array( 'id' => 1, 'player_id' => 10, 'error_type' => 'no_player' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_player_errors' )
            ->with( 'no_player' )
            ->willReturn( $errors );

        $result = $this->controller->player_errors_page(
            array( 'status' => 'no_player' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Errors_Page_View_Model::class, $vm );
        self::assertSame( 'no_player', $vm->status );
        self::assertSame( 'errors', $vm->racketmanager_tab );
        self::assertSame( 'Errors', $vm->tab_title );
        self::assertSame( $errors, $vm->player_errors );
    }

    public function test_get_player_errors_page_defaults_status_to_null(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->player_service->expects( self::once() )
            ->method( 'get_player_errors' )
            ->with( null )
            ->willReturn( array() );

        $result = $this->controller->player_errors_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Errors_Page_View_Model::class, $vm );
        self::assertNull( $vm->status );
        self::assertSame( array(), $vm->player_errors );
    }

    public function test_post_delete_player_error_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_player-error-bulk', 'edit_leagues' );

        $this->player_service->expects( self::once() )
            ->method( 'remove_player_error' )
            ->with( 101 );

        $post = array(
            'doPlayerErrorBulk' => '1',
            'action'            => 'delete',
            'playerError'       => array( 101 ),
        );

        $result = $this->controller->player_errors_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=errors', $result['redirect'] );
        self::assertStringContainsString( '101 - Player error has been removed', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_guard_failure_returns_error_redirect(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_player-error-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token invalid' ) );

        $this->player_service->expects( self::never() )
            ->method( 'remove_player_error' );

        $post = array(
            'doPlayerErrorBulk' => '1',
            'action'            => 'delete',
            'playerError'       => array( 101 ),
        );

        $result = $this->controller->player_errors_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=errors', $result['redirect'] );
        self::assertSame( 'Token invalid', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
