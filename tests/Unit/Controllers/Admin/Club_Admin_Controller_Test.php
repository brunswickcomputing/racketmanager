<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Club_Admin_Controller;
use Racketmanager\Admin\View_Models\Club_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Admin_Controller_Test extends TestCase {

    private Club_Service $club_service;
    private Player_Service $player_service;
    private Action_Guard_Interface $guard;
    private Club_Action_Dispatcher $dispatcher;
    private Club_Admin_Controller $controller;

    protected function setUp(): void {
        $this->club_service   = $this->createMock( Club_Service::class );
        $this->player_service = $this->createMock( Player_Service::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Club_Action_Dispatcher(
            $this->club_service,
            $this->player_service,
            $this->guard
        );

        $this->controller = new Club_Admin_Controller(
            $this->club_service,
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

        $this->controller->club_page( array(), array() );
    }

    public function test_get_add_club_returns_view_model(): void {
        $result = $this->controller->club_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Club_Page_View_Model::class, $result['view_model'] );
        self::assertNull( $result['view_model']->club );
        self::assertFalse( $result['view_model']->edit );
        self::assertSame( 'Add Club', $result['view_model']->form_title );
        self::assertSame( 'Add', $result['view_model']->form_action );
    }

    public function test_get_edit_club_not_found_throws(): void {
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 99 )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $this->expectException( Club_Not_Found_Exception::class );

        $this->controller->club_page( array( 'club_id' => '99' ), array() );
    }

    public function test_get_edit_club_returns_view_model(): void {
        $club = Club_Test_Factory::create( id: 1, name: 'Test Club' );
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $result = $this->controller->club_page( array( 'club_id' => '1' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Club_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $club, $result['view_model']->club );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'Edit Club', $result['view_model']->form_title );
        self::assertSame( 'Update', $result['view_model']->form_action );
        self::assertSame( 1, $result['view_model']->club_id );
    }

    public function test_post_add_club_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-club', 'edit_teams' );

        $club = Club_Test_Factory::create( id: 1, name: 'New Club' );
        $this->club_service->expects( self::once() )
            ->method( 'add_club' )
            ->willReturn( $club );

        $result = $this->controller->club_page(
            array(),
            array(
                'addClub'   => '1',
                'club'      => 'New Club',
                'type'      => 'tennis',
                'shortcode' => 'NC',
                'address'   => '123 High Street',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( 'Club added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_edit_club_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-club', 'edit_teams' );

        $club = Club_Test_Factory::create( id: 1, name: 'Test Club' );
        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $this->club_service->expects( self::once() )
            ->method( 'update_club' )
            ->with( 1, self::isInstanceOf( \Racketmanager\Domain\Club\Club::class ) )
            ->willReturn( true );

        $result = $this->controller->club_page(
            array( 'club_id' => '1' ),
            array(
                'editClub'                   => '1',
                'club_id'                    => '1',
                'club'                       => 'Updated Club',
                'type'                       => 'tennis',
                'shortcode'                  => 'UC',
                'address'                    => '123 High Street',
                'match_secretary'            => '7',
                'match_secretary_name'       => 'Sec Name',
                'match_secretary_contact_no' => '0123456789',
                'match_secretary_email'      => 'sec@example.com',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( 'Club updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
