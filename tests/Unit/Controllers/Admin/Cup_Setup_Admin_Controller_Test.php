<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Setup_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_setup_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->setup_page( array(), array() );
    }

    public function test_missing_competition_throws(): void {
        $controller = $this->resolve_controller( 'cup_setup_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->setup_page( array( 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( Competition_Test_Factory::create( id: 1, type: Competition_Type::CUP ) );
        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array( 'competition_service' => $service ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_unknown_season_throws(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2025' )
        );
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array( 'competition_service' => $service ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );
    }

    public function test_get_returns_setup_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'match_dates' => array( '2026-05-01' ) ) )
        );
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array( 'competition_service' => $service ) );
        $result     = $controller->setup_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertNull( $result['view_model']->event );
        self::assertSame( array( '2026-05-01' ), $result['view_model']->match_dates );
    }

    public function test_get_derives_match_dates_without_fixed_match_dates(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array(
                'date_end'     => '2026-06-28',
                'round_length' => 7,
            ) ),
            finals: array(
                array( 'round' => 1 ),
                array( 'round' => 2 ),
                array( 'round' => 3 ),
            )
        );
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array( 'competition_service' => $service ) );
        $result     = $controller->setup_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( array( 0 => '2026-06-28', 1 => '2026-07-05', 2 => '2026-06-28' ), $result['view_model']->match_dates );
    }

    public function test_get_derives_match_dates_with_fixed_match_dates(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array(
                'date_start'   => '2026-05-01',
                'date_end'     => '2026-06-28',
                'round_length' => 7,
            ) ),
            settings: array( 'fixed_match_dates' => true ),
            finals: array(
                array( 'round' => 3 ),
                array( 'round' => 2 ),
                array( 'round' => 1 ),
            )
        );
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array( 'competition_service' => $service ) );
        $result     = $controller->setup_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( array( 2 => '2026-06-28', 1 => '2026-06-15', 0 => '2026-05-01' ), $result['view_model']->match_dates );
    }

    public function test_post_competition_setup_dispatches_and_redirects_preserving_query_and_tab(): void {
        $dispatcher = $this->createMock( Cup_Setup_Action_Dispatcher::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with(
                array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '1', 'season' => '2026' ),
                array( 'action' => 'setup_competition', 'rounds' => array() )
            )
            ->willReturn( new Action_Result_DTO(
                message: 'Cup match dates updated',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'rounds',
                query_params: array( 'updated' => '1' ),
                flash_data: array( 'saved_rounds' => array( '2026-06-01' ) )
            ) );

        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array(
            'cup_setup_action_dispatcher' => $dispatcher,
        ) );

        $result = $controller->setup_page(
            array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '1', 'season' => '2026' ),
            array( 'action' => 'setup_competition', 'rounds' => array() )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertSame( 'Cup match dates updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
        self::assertSame( array( 'saved_rounds' => array( '2026-06-01' ) ), $result['flash_data'] );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'view=setup', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=1', $result['redirect'] );
        self::assertStringContainsString( 'season=2026', $result['redirect'] );
        self::assertStringContainsString( 'tab=rounds', $result['redirect'] );
        self::assertStringContainsString( 'updated=1', $result['redirect'] );
    }

    public function test_post_rank_dispatches_ratings_calculation_and_redirects(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( self::once() )
            ->method( 'calculate_team_ratings' )
            ->with( 1, 2026 );

        $controller = $this->resolve_controller( 'cup_setup_admin_controller', array(
            'competition_service' => $comp_service,
        ) );

        $result = $controller->setup_page(
            array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '1', 'season' => '2026' ),
            array( 'rank' => '1', 'competition_id' => '1', 'season' => '2026', 'racketmanager_nonce' => 'nonce' )
        );

        self::assertSame( 'Cup ratings set', $result['message'] );
        self::assertFalse( $result['message_type'] );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'view=setup', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=1', $result['redirect'] );
        self::assertStringContainsString( 'season=2026', $result['redirect'] );
    }
}
