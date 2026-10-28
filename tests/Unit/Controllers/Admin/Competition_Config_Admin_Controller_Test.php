<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Competition_Config_Admin_Controller;
use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Config_Admin_Controller_Test extends TestCase {

    private Competition_Service $competition_service;
    private Tournament_Service $tournament_service;
    private Action_Guard_Interface $guard;
    private Competition_Action_Dispatcher $dispatcher;
    private Admin_Competition_Page_Presenter $pages;
    private Competition_Config_Admin_Controller $controller;

    protected function setUp(): void {
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->tournament_service  = $this->createMock( Tournament_Service::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $event_repository          = $this->createMock( Event_Repository_Interface::class );
        $this->dispatcher          = new Competition_Action_Dispatcher(
            $this->competition_service,
            $this->guard,
            $event_repository
        );

        $club_service    = $this->createMock( Club_Service::class );
        $season_service  = $this->createMock( Season_Service::class );
        $finance_service = $this->createMock( Finance_Service::class );
        $this->pages     = new Admin_Competition_Page_Presenter(
            $this->competition_service,
            $club_service,
            $season_service,
            $finance_service
        );

        $this->controller = new Competition_Config_Admin_Controller(
            $this->competition_service,
            $this->tournament_service,
            $this->dispatcher,
            $this->guard,
            $this->pages
        );
    }

    public function test_capability_check_fails(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Permission denied' );

        $this->controller->config_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $this->expectException( Competition_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->config_page( array(), array() );
    }

    public function test_competition_not_found_throws(): void {
        $this->competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 42 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->expectException( Competition_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Competition not found' );

        $this->controller->config_page( array( 'competition_id' => '42' ), array() );
    }

    public function test_get_config_page_returns_view_model(): void {
        $competition            = $this->createStub( Competition::class );
        $competition->id        = 10;
        $competition->name      = 'League 1';
        $competition->type      = Competition_Type::LEAGUE;
        $competition->is_league = true;
        $competition->method( 'get_id' )->willReturn( 10 );
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_settings' )->willReturn( array( 'sport' => 'tennis' ) );

        $this->competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );
        $this->competition_service->method( 'get_events_for_competition' )->willReturn( array() );
        $this->competition_service->method( 'get_rules_options' )->willReturn( array() );

        $result = $this->controller->config_page( array( 'competition_id' => '10' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Competition_Config_Page_View_Model::class, $result['view_model'] );
    }

    public function test_post_config_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-competition-config', 'edit_leagues' );

        $this->competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willReturn( 1 );

        $result = $this->controller->config_page(
            array( 'competition_id' => '10' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '10',
                'competition_title'       => 'Updated Title',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=10', $result['redirect'] );
        self::assertSame( 'Competition updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
