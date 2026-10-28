<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Cup_Plan_Page_View_Model;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Plan_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_plan_admin_controller' );
        $this->deny_capability( 'edit_teams' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->plan_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'cup_plan_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->plan_page( array( 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $controller = $this->resolve_controller( 'cup_plan_admin_controller' );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->plan_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_get_builds_cup_plan_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Senior Cup',
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'venue' => 12 ) )
        );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 1, 2026 )
            ->willReturn( $competition );
        $competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 1, 2026 )
            ->willReturn( array() );
        $competition_service->expects( self::once() )
            ->method( 'get_matches_for_competition' )
            ->with( $competition, array( 'season' => 2026, 'final' => 'final' ) )
            ->willReturn( array() );

        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $club_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 12 )
            ->willReturn( Club::from_object( (object) array( 'id' => 12, 'shortcode' => 'WTC' ) ) );

        $controller = $this->resolve_controller( 'cup_plan_admin_controller', array(
            'competition_service' => $competition_service,
            'club_repository'     => $club_repo,
        ) );

        $result = $controller->plan_page( array(
            'competition_id' => '1',
            'season'         => '2026',
        ), array(), array(
            'form_data' => array(
                'startTime'           => '10:00',
                'timeIncrement'       => '30',
                'numCourtsAvailable'  => '4',
            ),
        ) );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Cup_Plan_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 'matches', $result['view_model']->tab );
    }

    public function test_post_handled_returns_redirect_with_flash_data(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $dispatcher = $this->createMock( Cup_Plan_Action_Dispatcher::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Action_Result_DTO(
                message: 'Plan saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'plan',
                flash_data: array( 'key' => 'val' )
            ) );

        $controller = $this->resolve_controller( 'cup_plan_admin_controller', array(
            'cup_plan_action_dispatcher' => $dispatcher,
        ) );

        $result = $controller->plan_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'plan',
            'competition_id' => '1',
            'season'         => '2026',
        ), array( 'action' => 'savePlan' ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'tab=plan', $result['redirect'] );
        self::assertSame( 'Plan saved', $result['message'] );
        self::assertSame( array( 'key' => 'val' ), $result['flash_data'] );
    }

    public function test_post_exception_returns_error_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $dispatcher = $this->createMock( Cup_Plan_Action_Dispatcher::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willThrowException( new Invalid_Status_Exception( 'Failed saving plan' ) );

        $controller = $this->resolve_controller( 'cup_plan_admin_controller', array(
            'cup_plan_action_dispatcher' => $dispatcher,
        ) );

        $result = $controller->plan_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'plan',
            'competition_id' => '1',
            'season'         => '2026',
        ), array( 'action' => 'savePlan' ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame( 'Failed saving plan', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
