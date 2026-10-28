<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Competition_Seasons_Admin_Controller;
use Racketmanager\Admin\View_Models\Competition_Seasons_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Seasons_Admin_Controller_Test extends TestCase {

    private Competition_Service $competition_service;
    private Season_Delete_Action_Dispatcher $dispatcher;
    private Action_Guard_Interface $guard;
    private Competition_Seasons_Admin_Controller $controller;

    protected function setUp(): void {
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher          = new Season_Delete_Action_Dispatcher(
            $this->competition_service,
            $this->guard
        );

        $this->controller = new Competition_Seasons_Admin_Controller(
            $this->competition_service,
            $this->dispatcher,
            $this->guard
        );
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    private function competition( int $id = 10, string $name = 'Summer League' ): Competition {
        $comp = $this->createMock( Competition::class );
        $comp->id = $id;
        $comp->name = $name;
        $comp->method( 'get_type' )->willReturn( 'league' );
        return $comp;
    }

    public function test_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $competition = $this->competition( 10, 'Summer League' );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $result = $this->controller->seasons_page(
            array( 'competition_id' => '10' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Competition_Seasons_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( 10, $result['view_model']->competition_id );
    }

    public function test_post_dispatches_delete_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $competition = $this->competition( 10 );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'seasons-bulk', 'del_matches' );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'delete_seasons' )
            ->with( 10, array( 2024 ) );

        $result = $this->controller->seasons_page(
            array( 'competition_id' => '10', 'page' => 'racketmanager-leagues' ),
            array( 'competition_id' => '10', 'doActionSeason' => '1', 'action' => 'delete', 'del_season' => array( '2024' ) )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=seasons', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=10', $result['redirect'] );
        self::assertSame( 'Season(s) deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_without_do_action_season_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $competition = $this->competition( 10 );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service
            ->expects( self::never() )
            ->method( 'delete_seasons' );

        $this->guard
            ->expects( self::never() )
            ->method( 'assert_allowed' );

        $result = $this->controller->seasons_page(
            array( 'competition_id' => '10' ),
            array( 'competition_id' => '10' )
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Competition_Seasons_Page_View_Model::class, $result['view_model'] );
    }

    public function test_throws_when_competition_id_missing(): void {
        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->seasons_page( array(), array() );
    }

    public function test_throws_when_competition_not_found(): void {
        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 99 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->seasons_page( array( 'competition_id' => '99' ), array() );
    }
}
