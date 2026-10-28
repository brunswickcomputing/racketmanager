<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Matches_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_matches_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->matches_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'cup_matches_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->matches_page( array( 'league' => '10' ), array() );
    }

    public function test_missing_league_id_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Cup Comp', type: Competition_Type::CUP );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 1 )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_matches_admin_controller', array(
            'competition_service' => $competition_service,
        ) );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->matches_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_get_builds_fixtures_view_model_with_final_round(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Senior Cup', type: Competition_Type::CUP );
        $league      = League::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup Draw' ) );

        $champ_ref = new \ReflectionClass( Championship::class );
        $champ_obj = $champ_ref->newInstanceWithoutConstructor();

        $league->is_championship = true;
        $league->championship    = $champ_obj;
        $league->current_season  = array( 'home_away' => 'true' );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );

        $mock_match = (object) array( 'id' => 101, 'final_round' => 'semi' );
        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->expects( self::once() )
            ->method( 'get_matches' )
            ->with( $league, array(
                'final'   => 'semi',
                'orderby' => array( 'id' => 'ASC' ),
                'leg'     => 1,
            ) )
            ->willReturn( array( $mock_match ) );

        $controller = $this->resolve_controller( 'cup_matches_admin_controller', array(
            'competition_service'  => $competition_service,
            'league_repository'    => $league_repo,
            'league_query_service' => $league_query_service,
        ) );

        $result = $controller->matches_page( array(
            'competition_id' => '1',
            'league_id'      => '10',
            'season'         => '2026',
            'final'          => 'semi',
        ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->is_finals );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'edit', $result['view_model']->mode );
        self::assertSame( 'semi', $result['view_model']->final_key );
        self::assertSame( array( $mock_match ), $result['view_model']->fixtures );
    }

    public function test_post_handled_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willReturn( new \Racketmanager\Domain\DTO\Admin\Action_Result_DTO(
                message: 'Matches saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'fixtures'
            ) );

        $controller = $this->resolve_controller( 'cup_matches_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->matches_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'matches',
            'competition_id' => '1',
            'league_id'      => '10',
            'season'         => '2026',
        ), array(
            'updateLeague' => 'fixture',
            'league_id'    => '10',
            'season'       => '2026',
        ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'tab=fixtures', $result['redirect'] );
        self::assertSame( 'Matches saved', $result['message'] );
    }

    public function test_post_exception_returns_error_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willThrowException( new Invalid_Status_Exception( 'Failed saving matches' ) );

        $controller = $this->resolve_controller( 'cup_matches_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->matches_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'matches',
            'competition_id' => '1',
            'league_id'      => '10',
        ), array(
            'updateLeague' => 'fixture',
            'league_id'    => '10',
        ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame( 'Failed saving matches', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
