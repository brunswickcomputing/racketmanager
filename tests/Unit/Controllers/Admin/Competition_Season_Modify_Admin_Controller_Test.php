<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Competition_Season_Modify_Admin_Controller;
use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Season_Modify_Admin_Controller_Test extends TestCase {

    private Competition_Service $competition_service;
    private Action_Guard_Interface $guard;
    private Competition_Season_Action_Dispatcher $dispatcher;
    private Admin_Competition_Page_Presenter $pages;
    private Competition_Season_Modify_Admin_Controller $controller;

    protected function setUp(): void {
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $finance_service           = $this->createMock( Finance_Service::class );
        $season_service            = $this->createMock( Season_Service::class );
        $club_service        = $this->createMock( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( array() );
        $comp_season_service = $this->createMock( Competition_Season_Service::class );
        $event_repository    = $this->createMock( Event_Repository_Interface::class );

        $this->dispatcher = new Competition_Season_Action_Dispatcher(
            $this->competition_service,
            $finance_service,
            $this->guard,
            $comp_season_service,
            $event_repository
        );

        $this->pages = new Admin_Competition_Page_Presenter(
            $this->competition_service,
            $club_service,
            $season_service,
            $finance_service
        );

        $this->controller = new Competition_Season_Modify_Admin_Controller(
            $this->competition_service,
            $this->dispatcher,
            $this->guard,
            $this->pages
        );
    }

    public function test_capability_check_fails(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_seasons' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Permission denied' );

        $this->controller->season_modify_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $this->expectException( Competition_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->season_modify_page( array(), array() );
    }

    public function test_competition_not_found_throws(): void {
        $this->competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 99 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->expectException( Competition_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->season_modify_page( array( 'competition_id' => '99' ), array() );
    }

    public function test_edit_mode_with_no_seasons_throws(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_seasons' )->willReturn( array() );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $this->expectException( Season_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'No seasons found' );

        $this->controller->season_modify_page(
            array( 'competition_id' => '1', 'season' => '2025' ),
            array()
        );
    }

    public function test_edit_mode_with_unknown_season_throws(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_seasons' )->willReturn( array( '2024' => array( 'name' => '2024' ) ) );
        $competition->method( 'get_season_by_name' )->willReturn( null );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $this->expectException( Season_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Season not found' );

        $this->controller->season_modify_page(
            array( 'competition_id' => '1', 'season' => '2025' ),
            array()
        );
    }

    public function test_get_new_season_page_returns_view_model_and_info_message(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_seasons' )->willReturn( array() );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $result = $this->controller->season_modify_page(
            array( 'competition_id' => '1' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Season_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'New season', $result['message'] );
        self::assertSame( 'info', $result['message_type'] );
    }

    public function test_get_edit_season_page_returns_view_model_without_info_message(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_seasons' )->willReturn( array( '2025' => array( 'name' => '2025' ) ) );
        $competition->method( 'get_season_by_name' )->willReturn( array( 'name' => '2025' ) );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $result = $this->controller->season_modify_page(
            array( 'competition_id' => '1', 'season' => '2025' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Season_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertArrayNotHasKey( 'message', $result );
    }

    public function test_post_season_modify_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-season', 'edit_seasons' );

        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_season_by_name' )->willReturn( array() );
        $this->competition_service->method( 'get_competition' )->willReturn( $competition );

        $result = $this->controller->season_modify_page(
            array( 'competition_id' => '1' ),
            array(
                'editSeason'     => '1',
                'competition_id' => '1',
                'season'         => '2025',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertNotNull( $result['message'] );
    }
}
