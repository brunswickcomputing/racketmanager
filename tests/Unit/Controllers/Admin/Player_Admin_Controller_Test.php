<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Player_Admin_Controller;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Application\Player\Commands\Update_Player_Handler;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use WP_Error;

#[AllowMockObjectsWithoutExpectations]
final class Player_Admin_Controller_Test extends TestCase {

    private Player_Service $player_service;
    private Club_Service $club_service;
    private Action_Guard_Interface $action_guard;
    private Player_Action_Dispatcher $dispatcher;
    private Player_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->player_service = $this->createMock( Player_Service::class );
        $this->club_service   = $this->createMock( Club_Service::class );
        $this->action_guard   = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Player_Action_Dispatcher( $this->player_service, $this->action_guard );
        $this->controller     = new Player_Admin_Controller(
            $this->player_service,
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

        $this->controller->player_page( array(), array() );
    }

    public function test_player_not_found_throws_exception(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 999 )
            ->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

        $this->expectException( Player_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Player 999 not found' );

        $this->controller->player_page( array( 'player_id' => '999' ), array() );
    }

    public function test_get_player_page_returns_view_model(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $player = Player_Test_Factory::create_sparse( array(
            'id'           => 10,
            'display_name' => 'John Doe',
            'btm'          => '123456',
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'email'        => 'john@example.com',
            'gender'       => 'M',
            'contactno'    => '0123456789',
        ) );

        $club = Club_Test_Factory::create( id: 2, name: 'Test Club' );

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 10 )
            ->willReturn( $player );

        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 2 )
            ->willReturn( $club );

        $result = $this->controller->player_page(
            array( 'player_id' => '10', 'club_id' => '2' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Page_View_Model::class, $vm );
        self::assertSame( 10, $vm->player_id );
        self::assertSame( 2, $vm->club_id );
        self::assertSame( 'John', $vm->player->firstname );
        self::assertSame( 'Doe', $vm->player->surname );
        self::assertTrue( $vm->form_valid );
        self::assertEmpty( $vm->errors->all() );
        self::assertStringContainsString( 'club_id=2', $vm->page_referrer );
    }

    public function test_get_player_page_rehydrates_form_data_and_errors_from_flash(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $player = Player_Test_Factory::create_sparse( array(
            'id'           => 10,
            'display_name' => 'John Doe',
            'firstname'    => 'John',
            'surname'      => 'Doe',
        ) );

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 10 )
            ->willReturn( $player );

        $flash_data = array(
            'errors'    => array( 'firstname' => 'Invalid name' ),
            'form_data' => array(
                'firstname' => 'Johnny',
                'surname'   => 'Doe Jr',
                'locked'    => '1',
            ),
        );

        $result = $this->controller->player_page(
            array( 'player_id' => '10' ),
            array(),
            $flash_data
        );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( Player_Page_View_Model::class, $vm );
        self::assertSame( 'Johnny', $vm->player->firstname );
        self::assertSame( 'Doe Jr', $vm->player->surname );
        self::assertSame( 'Locked', $vm->player->locked );
        self::assertFalse( $vm->form_valid );
        self::assertTrue( $vm->errors->has( 'firstname' ) );
    }

    public function test_post_update_player_success_redirects_with_message(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );

        $updated_player = $this->createMock( Player::class );
        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->willReturn( $updated_player );

        $post = array(
            'updatePlayer' => '1',
            'playerId'     => '10',
            'club_Id'      => '5',
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'gender'       => 'M',
        );

        $result = $this->controller->player_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=player', $result['redirect'] );
        self::assertStringContainsString( 'player_id=10', $result['redirect'] );
        self::assertStringContainsString( 'club_id=5', $result['redirect'] );
        self::assertSame( 'Player updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_update_player_validation_failure_returns_flash_data(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );

        $error_handler = $this->createMock( Update_Player_Handler::class );
        $wp_error      = new WP_Error( 'firstname', 'First name required' );
        $error_handler->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( $wp_error );

        $dispatcher = new Player_Action_Dispatcher(
            $this->player_service,
            $this->action_guard,
            update_player_handler: $error_handler
        );

        $controller = new Player_Admin_Controller(
            $this->player_service,
            $this->club_service,
            $dispatcher,
            $this->action_guard
        );

        $post = array(
            'updatePlayer' => '1',
            'playerId'     => '10',
            'firstname'    => '',
            'surname'      => 'Doe',
        );

        $result = $controller->player_page( array(), $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=player', $result['redirect'] );
        self::assertStringContainsString( 'player_id=10', $result['redirect'] );
        self::assertTrue( $result['message_type'] );
        self::assertIsArray( $result['flash_data'] );
        self::assertArrayHasKey( 'errors', $result['flash_data'] );
        self::assertArrayHasKey( 'form_data', $result['flash_data'] );
        self::assertSame( 'First name required', $result['flash_data']['errors']['firstname'] );
    }
}
