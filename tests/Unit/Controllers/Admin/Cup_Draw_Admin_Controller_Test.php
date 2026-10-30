<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Draw_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_draw_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->draw_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'cup_draw_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->draw_page( array( 'league' => '10' ), array() );
    }

    public function test_missing_league_id_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Cup Comp', type: Competition_Type::CUP );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_by_id' )->with( 1 )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_draw_admin_controller', array(
            'competition_service' => $competition_service,
        ) );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->draw_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_get_builds_cup_draw_view_model(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Senior Cup', type: Competition_Type::CUP );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Draw' ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );

        $controller = $this->resolve_controller( 'cup_draw_admin_controller', array(
            'competition_service' => $competition_service,
            'league_repository'   => $league_repo,
        ) );

        $result = $controller->draw_page( array(
            'competition_id' => '1',
            'league'         => '10',
            'season'         => '2026',
        ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Cup_Draw_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'finalResults', $result['view_model']->tab );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( $league, $result['view_model']->league );
        self::assertIsArray( $result['view_model']->finals );
    }

    public function test_post_handled_returns_redirect_with_message_and_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willReturn( new \Racketmanager\Domain\DTO\Admin\Action_Result_DTO(
                message: 'Fixture updated',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'fixtures'
            ) );

        $controller = $this->resolve_controller( 'cup_draw_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->draw_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'draw',
            'competition_id' => '1',
            'league'         => '10',
            'season'         => '2026',
        ), array(
            'updateLeague' => 'fixture',
            'season'       => '2026',
            'league_id'    => '10',
        ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'tab=fixtures', $result['redirect'] );
        self::assertSame( 'Fixture updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_unhandled_falls_through_to_get_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $competition = Competition_Test_Factory::create( id: 1, name: 'Senior Cup', type: Competition_Type::CUP );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Draw' ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->method( 'get_by_id' )->willReturn( $competition );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );

        $controller = $this->resolve_controller( 'cup_draw_admin_controller', array(
            'draw_action_handler' => $draw_handler,
            'competition_service' => $competition_service,
            'league_repository'   => $league_repo,
        ) );

        $result = $controller->draw_page( array(
            'competition_id' => '1',
            'league'         => '10',
            'season'         => '2026',
        ), array( 'unknown' => 'post' ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Cup_Draw_Page_View_Model::class, $result['view_model'] );
    }

    public function test_post_exception_mapped_to_error_message(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid draw action' ) );

        $controller = $this->resolve_controller( 'cup_draw_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->draw_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'draw',
            'competition_id' => '1',
            'league'         => '10',
        ), array( 'updateLeague' => 'fixture', 'league_id' => '10' ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame( 'Invalid draw action', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
