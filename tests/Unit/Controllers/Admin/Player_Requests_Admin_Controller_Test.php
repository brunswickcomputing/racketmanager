<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Player_Requests_Admin_Controller;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;

#[AllowMockObjectsWithoutExpectations]
final class Player_Requests_Admin_Controller_Test extends TestCase {

    private Registration_Service $registration_service;
    private Club_Service $club_service;
    private Action_Guard_Interface $action_guard;
    private Player_Request_Action_Dispatcher $dispatcher;
    private Player_Requests_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->action_guard         = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher           = new Player_Request_Action_Dispatcher( $this->registration_service, $this->action_guard );
        $this->controller           = new Player_Requests_Admin_Controller(
            $this->registration_service,
            $this->club_service,
            $this->dispatcher,
            $this->action_guard
        );
    }

    public function test_capability_denial_throws_exception(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Forbidden' );

        $this->controller->player_requests_page( array(), array() );
    }

    public function test_get_player_requests_page_returns_view_model_with_filters(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $clubs    = array( (object) array( 'id' => 5, 'name' => 'Club 5' ) );
        $requests = array( (object) array( 'id' => 202, 'player_id' => 10, 'club_id' => 5 ) );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( $clubs );

        $this->registration_service->expects( self::once() )
            ->method( 'get_registered_players_list' )
            ->with( null, 'approved', 5 )
            ->willReturn( $requests );

        $result = $this->controller->player_requests_page(
            array( 'club' => '5', 'status' => 'approved' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Requests_Page_View_Model::class, $vm );
        self::assertSame( 5, $vm->club_id );
        self::assertSame( 'approved', $vm->status );
        self::assertSame( 'requests', $vm->racketmanager_tab );
        self::assertSame( 'Requests', $vm->tab_title );
        self::assertSame( $clubs, $vm->clubs );
        self::assertSame( $requests, $vm->player_requests );
    }

    public function test_get_player_requests_page_defaults(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $this->registration_service->expects( self::once() )
            ->method( 'get_registered_players_list' )
            ->with( null, 'outstanding', null )
            ->willReturn( array() );

        $result = $this->controller->player_requests_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Requests_Page_View_Model::class, $vm );
        self::assertNull( $vm->club_id );
        self::assertSame( 'outstanding', $vm->status );
    }

    public function test_post_approve_player_request_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_club-player-request-bulk', 'edit_leagues' );

        $this->registration_service->expects( self::once() )
            ->method( 'approve_registration' )
            ->with( 202, self::anything() );

        $post = array(
            'doPlayerRequest' => '1',
            'action'          => 'approve',
            'playerRequest'   => array( 202 ),
        );

        $result = $this->controller->player_requests_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=requests', $result['redirect'] );
        self::assertStringContainsString( '202 - Player has been approved', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_delete_player_request_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_club-player-request-bulk', 'edit_leagues' );

        $this->registration_service->expects( self::once() )
            ->method( 'remove_registration' )
            ->with( 202, self::anything() );

        $post = array(
            'doPlayerRequest' => '1',
            'action'          => 'delete',
            'playerRequest'   => array( 202 ),
        );

        $result = $this->controller->player_requests_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=requests', $result['redirect'] );
        self::assertStringContainsString( '202 - Player has been removed from club', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_guard_failure_returns_error_redirect(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_club-player-request-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token invalid' ) );

        $this->registration_service->expects( self::never() )
            ->method( 'approve_registration' );

        $post = array(
            'doPlayerRequest' => '1',
            'action'          => 'approve',
            'playerRequest'   => array( 202 ),
        );

        $result = $this->controller->player_requests_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=requests', $result['redirect'] );
        self::assertSame( 'Token invalid', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
