<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Setup_Event_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->setup_event_page( array(), array() );
    }

    public function test_missing_league_throws(): void {
        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller' );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );
    }

    public function test_unknown_league_throws(): void {
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( null );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array( 'league_repository' => $league_repo ) );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'league' => '99', 'competition_id' => '1', 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::CUP );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $event       = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $league->event = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1' ), array() );
    }

    public function test_unknown_season_throws(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2025' )
        );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $event       = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $league->event = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1', 'season' => '2026' ), array() );
    }

    public function test_get_returns_setup_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'match_dates' => array( '2026-06-01' ) ) )
        );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $event       = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $league->event = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $result = $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( $league, $result['view_model']->league );
        self::assertSame( $event, $result['view_model']->event );
        self::assertSame( array( '2026-06-01' ), $result['view_model']->match_dates );
    }

    public function test_get_derives_match_dates_with_event_offset(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array(
                'match_dates' => array( '2026-05-01', '2026-05-15', '2026-06-01' ),
            ) )
        );
        $league        = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $event         = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $event->offset = 1;
        $league->event = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $result = $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( array( 0 => '2026-05-08', 1 => '2026-05-22', 2 => '2026-06-01' ), $result['view_model']->match_dates );
    }

    public function test_get_derives_match_dates_from_event_season(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array(
                'match_dates' => array( '2026-05-01' ),
            ) )
        );
        $league        = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $event         = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $event->set_seasons( array(
            '2026' => array(
                'match_dates' => array( '2026-07-01', '2026-07-15' ),
            ),
        ) );
        $league->event = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $result = $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( array( '2026-07-01', '2026-07-15' ), $result['view_model']->match_dates );
    }

    public function test_get_derives_match_dates_from_reversed_league_rounds(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026' )
        );
        $league          = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup' ) );
        $league->seasons = array(
            '2026' => array(
                'rounds' => array(
                    (object) array( 'date' => '2026-05-01' ),
                    (object) array( 'date' => '2026-05-15' ),
                    (object) array( 'date' => '2026-06-01' ),
                ),
            ),
        );
        $event           = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );
        $league->event   = $event;

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'league_repository'   => $league_repo,
            'competition_service' => $comp_service,
        ) );

        $result = $controller->setup_event_page( array( 'league' => '10', 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( array( '2026-06-01', '2026-05-15', '2026-05-01' ), $result['view_model']->match_dates );
    }

    public function test_post_league_setup_dispatches_and_redirects_with_tab_and_query_params(): void {
        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'set_championship_fixtures' )
            ->willReturn( new Action_Result_DTO(
                message: 'Championship fixtures saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'fixtures',
                query_params: array( 'saved' => '1' ),
                flash_data: array( 'status' => 'ok' )
            ) );

        $controller = $this->resolve_controller( 'cup_setup_event_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->setup_event_page(
            array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'league' => '10', 'competition_id' => '1', 'season' => '2026' ),
            array(
                'action'              => 'set_rounds',
                'league_id'           => '10',
                'season'              => '2026',
                'rounds'              => array(
                    array( 'round' => 1, 'match_date' => '2026-05-01' ),
                ),
                'racketmanager_nonce' => 'nonce',
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertSame( 'Championship fixtures saved', $result['message'] );
        self::assertFalse( $result['message_type'] );
        self::assertSame( array( 'status' => 'ok' ), $result['flash_data'] );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'view=setup-event', $result['redirect'] );
        self::assertStringContainsString( 'league=10', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=1', $result['redirect'] );
        self::assertStringContainsString( 'season=2026', $result['redirect'] );
        self::assertStringContainsString( 'tab=fixtures', $result['redirect'] );
        self::assertStringContainsString( 'saved=1', $result['redirect'] );
    }
}
