<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Action_Result_DTO;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    public function test_get_new_tournament_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertFalse( $result['view_model']->edit );
        self::assertSame( 'Add Tournament', $result['view_model']->form_title );
    }

    public function test_get_existing_tournament_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $tournament_service->expects( self::once() )
            ->method( 'get_tournament_and_fees' )
            ->with( $tournament_id )
            ->willReturn( [
                'tournament' => new stdClass(),
                'fees' => new stdClass(),
            ] );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [ 'tournament' => (string) $tournament_id ], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'Edit Tournament', $result['view_model']->form_title );
    }

    public function test_post_success_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $post_data = [ 'some' => 'data' ];
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            tournament_id: 456,
            message: 'Tournament added',
            message_type: Admin_Message_Type::SUCCESS
        );

        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( null, $post_data )
            ->willReturn( $action_result );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'tournament=456', $result['redirect'] );
        self::assertStringContainsString( 'view=modify', $result['redirect'] );
        self::assertSame( 'Tournament added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_error_re_renders_form_with_errors(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $post_data = [ 'some' => 'data' ];
        $wp_error = new WP_Error( 'invalid_name', 'Name is required' );
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            message: 'Validation failed',
            message_type: Admin_Message_Type::ERROR,
            raw_error: $wp_error
        );

        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( null, $post_data )
            ->willReturn( $action_result );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [], $post_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->validator->error );
        self::assertContains( 'Name is required', $result['view_model']->validator->err_msgs );
        self::assertSame( 'Validation failed', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
