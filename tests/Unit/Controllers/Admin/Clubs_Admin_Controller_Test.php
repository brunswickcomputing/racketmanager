<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Clubs_Admin_Controller;
use Racketmanager\Admin\View_Models\Clubs_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Clubs_Admin_Controller_Test extends TestCase {

    private Club_Service $club_service;
    private Player_Service $player_service;
    private Action_Guard_Interface $guard;
    private Club_Action_Dispatcher $dispatcher;
    private Clubs_Admin_Controller $controller;

    protected function setUp(): void {
        $this->club_service   = $this->createMock( Club_Service::class );
        $this->player_service = $this->createMock( Player_Service::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Club_Action_Dispatcher(
            $this->club_service,
            $this->player_service,
            $this->guard
        );

        $this->controller = new Clubs_Admin_Controller(
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

        $this->controller->clubs_page( array(), array() );
    }

    public function test_get_clubs_returns_view_model(): void {
        $club = Club_Test_Factory::create( id: 1, name: 'Test Club' );
        $this->club_service->method( 'get_clubs' )->willReturn( array( $club ) );

        $result = $this->controller->clubs_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Clubs_Page_View_Model::class, $result['view_model'] );
        self::assertCount( 1, $result['view_model']->clubs );
        self::assertSame( $club, $result['view_model']->clubs[0] );
    }

    public function test_post_delete_clubs_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'clubs-bulk', 'del_teams' );

        $this->club_service->expects( self::once() )
            ->method( 'remove_club' )
            ->with( 1 );

        $result = $this->controller->clubs_page(
            array(),
            array(
                'doClubDel'  => '1',
                'action'     => 'delete',
                'club'       => array( '1' ),
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( '1 deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_schedule_ratings_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'clubs-bulk', 'edit_teams' );

        $this->player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( true );

        $result = $this->controller->clubs_page(
            array(),
            array(
                'doSchedulePlayerRatings' => '1',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-clubs', $result['redirect'] );
        self::assertSame( 'Player ratings calculation scheduled', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
