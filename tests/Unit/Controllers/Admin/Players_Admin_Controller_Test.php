<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Players_Admin_Controller;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

#[AllowMockObjectsWithoutExpectations]
final class Players_Admin_Controller_Test extends TestCase {

    private Player_Service $player_service;
    private Action_Guard_Interface $action_guard;
    private Player_Action_Dispatcher $dispatcher;
    private Players_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->player_service = $this->createMock( Player_Service::class );
        $this->action_guard   = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Player_Action_Dispatcher( $this->player_service, $this->action_guard );
        $this->controller     = new Players_Admin_Controller(
            $this->player_service,
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

        $this->controller->players_page( array(), array() );
    }

    public function test_get_players_page_returns_view_model(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $players = array(
            (object) array( 'id' => 1, 'fullname' => 'John Doe' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->willReturn( $players );

        $result = $this->controller->players_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Players_Page_View_Model::class, $vm );
        self::assertSame( 'Players', $vm->tab_title );
        self::assertSame( 'players', $vm->racketmanager_tab );
        self::assertSame( $players, $vm->players );
        self::assertTrue( $vm->form_valid );
    }

    public function test_get_players_page_search_with_name_returns_filtered_players(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $players = array(
            (object) array( 'id' => 2, 'fullname' => 'Alice Smith' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->with( array( 'name' => 'Smith' ) )
            ->willReturn( $players );

        $result = $this->controller->players_page(
            array( 'doPlayerSearch' => '1', 'name' => 'Smith' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Players_Page_View_Model::class, $vm );
        self::assertSame( $players, $vm->players );
        self::assertArrayNotHasKey( 'message', $result );
    }

    public function test_get_players_page_search_empty_name_returns_notice_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $players = array(
            (object) array( 'id' => 1, 'fullname' => 'John Doe' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->with()
            ->willReturn( $players );

        $result = $this->controller->players_page(
            array( 'doPlayerSearch' => '1', 'name' => '' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'No search term specified', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_get_players_page_rehydrates_player_from_flash(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->willReturn( array() );

        $flash_data = array(
            'form_data' => array(
                'firstname' => 'Alice',
                'surname'   => 'Smith',
            ),
            'errors'    => array(
                'email' => 'Email required',
            ),
        );

        $result = $this->controller->players_page( array(), array(), $flash_data );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Players_Page_View_Model::class, $vm );
        self::assertNotNull( $vm->player );
        self::assertSame( 'Alice', $vm->player->firstname );
        self::assertFalse( $vm->form_valid );
        self::assertTrue( $vm->errors->has( 'email' ) );
    }

    public function test_post_add_player_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );

        $new_player = $this->createMock( Player::class );
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willReturn( $new_player );

        $post = array(
            'addPlayer' => '1',
            'firstname' => 'Alice',
            'surname'   => 'Smith',
        );

        $result = $this->controller->players_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=players', $result['redirect'] );
        self::assertSame( 'Player added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_delete_players_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_player-bulk', 'edit_teams' );

        $this->player_service->expects( self::once() )
            ->method( 'delete_player' )
            ->with( 10 )
            ->willReturn( true );

        $post = array(
            'doPlayerDel' => '1',
            'action'      => 'delete',
            'player'      => array( 10 ),
        );

        $result = $this->controller->players_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=players', $result['redirect'] );
        self::assertStringContainsString( '10 deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_guard_failure_returns_error_redirect(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_player-bulk', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Token invalid' ) );

        $this->player_service->expects( self::never() )
            ->method( 'delete_player' );

        $post = array(
            'doPlayerDel' => '1',
            'action'      => 'delete',
            'player'      => array( 10 ),
        );

        $result = $this->controller->players_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=players', $result['redirect'] );
        self::assertSame( 'Token invalid', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
