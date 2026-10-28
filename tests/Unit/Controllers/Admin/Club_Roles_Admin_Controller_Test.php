<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Club_Roles_Admin_Controller;
use Racketmanager\Admin\View_Models\Club_Roles_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Roles_Admin_Controller_Test extends TestCase {

    private Club_Service $club_service;
    private Registration_Service $registration_service;
    private Action_Guard_Interface $guard;
    private Club_Role_Action_Dispatcher $dispatcher;
    private Club_Roles_Admin_Controller $controller;

    protected function setUp(): void {
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Club_Role_Action_Dispatcher(
            $this->club_service,
            $this->guard
        );

        $this->controller = new Club_Roles_Admin_Controller(
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

        $this->controller->club_roles_page( array(), array() );
    }

    public function test_missing_club_id_throws(): void {
        $this->expectException( Club_Not_Found_Exception::class );

        $this->controller->club_roles_page( array(), array() );
    }

    public function test_club_not_found_throws(): void {
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 99 )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $this->expectException( Club_Not_Found_Exception::class );

        $this->controller->club_roles_page( array( 'club_id' => '99' ), array() );
    }

    public function test_get_club_roles_returns_view_model(): void {
        $club = Club_Test_Factory::create( id: 1, name: 'Test Club' );
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $roles   = array( (object) array( 'role_id' => 1, 'role_name' => 'Secretary' ) );
        $players = array( array( 'id' => 10, 'name' => 'John Doe' ) );

        $this->club_service->expects( self::once() )
            ->method( 'get_roles_for_club' )
            ->with( 1 )
            ->willReturn( $roles );
        $this->registration_service->expects( self::once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 1 )
            ->willReturn( $players );

        $result = $this->controller->club_roles_page(
            array( 'club_id' => '1' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Club_Roles_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $club, $result['view_model']->club );
        self::assertSame( $roles, $result['view_model']->roles );
        self::assertSame( $players, $result['view_model']->club_players );
        self::assertSame( 1, $result['view_model']->club_id );
    }

    public function test_post_role_mutation_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-club-role', 'edit_teams' );

        $this->club_service->expects( self::once() )
            ->method( 'set_club_role' )
            ->with( 1, 2, 3 )
            ->willReturn( true );

        $result = $this->controller->club_roles_page(
            array( 'club_id' => '1' ),
            array(
                'addRole' => '1',
                'club_id' => '1',
                'role_id' => '2',
                'user_id' => '3',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( 'Role added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
