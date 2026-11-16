<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Results_Admin_Controller;
use Racketmanager\Admin\Controllers\Results_Checker_Tab_Handler;
use Racketmanager\Admin\Controllers\Results_Fixtures_Tab_Handler;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Admin\View_Models\Results_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Application\Admin\Ports\Results_Checker_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Results_Admin_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Results_Checker_Repository_Interface $checker_repository;
    private Results_Checker_Presenter $checker_presenter;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Results_Checker_Action_Dispatcher_Interface $checker_dispatcher;

    private Fixture_Repository_Interface $fixture_repository;
    private League_Repository_Interface $league_repository;
    private Admin_Results_Match_Presenter $match_presenter;
    private Option_Service $option_service;

    private Action_Guard_Interface $action_guard;
    private Results_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->checker_repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->checker_presenter  = $this->createMock( Results_Checker_Presenter::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );
        $this->checker_dispatcher  = $this->createMock( Results_Checker_Action_Dispatcher_Interface::class );

        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->league_repository  = $this->createMock( League_Repository_Interface::class );
        $this->match_presenter    = new Admin_Results_Match_Presenter(
            new Admin_Date_Presenter(),
            $this->league_repository
        );
        $this->option_service     = $this->createMock( Option_Service::class );

        $this->action_guard = $this->createMock( Action_Guard_Interface::class );

        $checker_tab_handler = new Results_Checker_Tab_Handler(
            $this->checker_repository,
            $this->checker_presenter,
            $this->competition_service,
            $this->season_service,
            $this->checker_dispatcher
        );

        $fixtures_tab_handler = new Results_Fixtures_Tab_Handler(
            $this->fixture_repository,
            $this->match_presenter,
            $this->option_service
        );

        $this->controller = new Results_Admin_Controller(
            $checker_tab_handler,
            $fixtures_tab_handler,
            $this->action_guard
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_capability_check_fails_on_request(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->results_page( array(), array() );
    }

    public function test_post_results_checker_mutation_dispatches_and_redirects(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $post = array(
            'doResultsChecker' => '1',
            'check'            => array( 10 ),
        );

        $dto = new Action_Result_DTO(
            message: 'Result check(s) approved',
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'resultschecker'
        );

        $this->checker_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $post )
            ->willReturn( $dto );

        $result = $this->controller->results_page(
            array( 'page' => 'racketmanager-results', 'season' => '2025' ),
            $post
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-results', $result['redirect'] );
        self::assertStringContainsString( 'tab=resultschecker', $result['redirect'] );
        self::assertSame( 'Result check(s) approved', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_get_results_page_returns_view_model(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'view_leagues' );

        $this->checker_repository->method( 'find_all' )->willReturn( array() );
        $this->checker_presenter->method( 'present_collection' )->willReturn( array() );
        $this->competition_service->method( 'get_league_competitions' )->willReturn( array() );
        $this->season_service->method( 'get_all_seasons' )->willReturn( array() );
        $this->fixture_repository->method( 'find_all' )->willReturn( array() );
        $this->option_service->method( 'get_options' )->willReturn( array() );

        $result = $this->controller->results_page(
            array(
                'tab'                  => 'pending-results',
                'season'               => '2025',
                'competition'          => 5,
                'event'                => 12,
                'filterResultsChecker' => 'outstanding',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Results_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'pending-results', $result['view_model']->tab );
        self::assertSame( '2025', $result['view_model']->season_select );
        self::assertSame( 5, $result['view_model']->competition_select );
        self::assertSame( 12, $result['view_model']->event_select );
        self::assertSame( 'outstanding', $result['view_model']->results_check_filter );
    }
}
