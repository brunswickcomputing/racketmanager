<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Club_Players_Admin_Controller;
use Racketmanager\Admin\View_Models\Club_Players_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Players_Admin_Controller_Test extends TestCase {

    private Club_Service $club_service;
    private Registration_Service $registration_service;
    private Player_Service $player_service;
    private Action_Guard_Interface $guard;
    private Club_Player_Action_Dispatcher $dispatcher;
    private Club_Players_Admin_Controller $controller;

    protected function setUp(): void {
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Club_Player_Action_Dispatcher(
            $this->registration_service,
            $this->player_service,
            $this->guard
        );

        $this->controller = new Club_Players_Admin_Controller(
            $this->club_service,
            $this->registration_service,
            $this->dispatcher,
            $this->guard
        );
    }

    public function test_capability_check_fails(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->club_players_page( array(), array() );
    }

    public function test_missing_club_id_throws(): void {
        $this->expectException( Club_Not_Found_Exception::class );

        $this->controller->club_players_page( array(), array() );
    }

    public function test_club_not_found_throws(): void {
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 99 )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $this->expectException( Club_Not_Found_Exception::class );

        $this->controller->club_players_page( array( 'club_id' => '99' ), array() );
    }

    public function test_get_club_players_returns_view_model(): void {
        $club = Club_Test_Factory::create( id: 1, name: 'Test Club' );
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $players = array( array( 'id' => 10, 'name' => 'John Doe' ) );
        $this->registration_service->expects( self::once() )
            ->method( 'get_registered_players_list' )
            ->with( 'yes', null, 1, 'M' )
            ->willReturn( $players );

        $result = $this->controller->club_players_page(
            array(
                'club_id' => '1',
                'active'  => 'yes',
                'gender'  => 'M',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Club_Players_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $club, $result['view_model']->club );
        self::assertSame( $players, $result['view_model']->players );
        self::assertSame( 'yes', $result['view_model']->active );
        self::assertSame( 'M', $result['view_model']->gender );
        self::assertSame( 1, $result['view_model']->club_id );
    }

    public function test_post_player_mutation_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );

        $this->registration_service->expects( self::once() )
            ->method( 'register_player_to_club' )
            ->with( 1, self::anything() )
            ->willReturn( 'Player registered to club' );

        $result = $this->controller->club_players_page(
            array( 'club_id' => '1' ),
            array(
                'addPlayer' => '1',
                'club_Id'   => '1',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( 'Player registered to club', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
