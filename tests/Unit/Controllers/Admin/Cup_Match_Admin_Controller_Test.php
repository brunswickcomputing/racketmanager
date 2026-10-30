<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Match_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_match_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->match_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'cup_match_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->match_page( array( 'league' => '10', 'edit' => '50' ), array() );
    }

    public function test_missing_league_id_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Cup Comp', type: Competition_Type::CUP );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 1 )->willReturn( $competition );

        $controller = $this->resolve_controller( 'cup_match_admin_controller', array(
            'competition_service' => $competition_service,
        ) );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->match_page( array( 'competition_id' => '1', 'edit' => '50' ), array() );
    }

    public function test_missing_edit_param_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Cup Comp', type: Competition_Type::CUP );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup Draw' ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 1 )->willReturn( $competition );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

        $controller = $this->resolve_controller( 'cup_match_admin_controller', array(
            'competition_service' => $competition_service,
            'league_repository'   => $league_repo,
        ) );

        $this->expectException( Fixture_Not_Found_Exception::class );
        $controller->match_page( array( 'competition_id' => '1', 'league' => '10' ), array() );
    }

    public function test_get_builds_single_cup_match_view_model(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Senior Cup', type: Competition_Type::CUP );
        $league      = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Cup Draw' ) );

        $champ_ref = new \ReflectionClass( Championship::class );
        $champ_obj = $champ_ref->newInstanceWithoutConstructor();

        $league->is_championship = true;
        $league->championship    = $champ_obj;

        $match = new Fixture();
        $match->id = 50;
        $match->league_id = 10;
        $match->match_day = 2;
        $match->final_round = 'final';
        $match->home_team = '101';
        $match->away_team = '102';

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 1 )->willReturn( $competition );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )->method( 'find_by_id' )->with( 50 )->willReturn( $match );

        $home_team = new Team();
        $home_team->id = 101;
        $home_team->title = 'Home Club A';

        $away_team = new Team();
        $away_team->id = 102;
        $away_team->title = 'Away Club B';

        $team_repo = $this->createMock( Team_Repository_Interface::class );
        $team_repo->expects( self::exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnMap( array(
                array( 101, $home_team ),
                array( 102, $away_team ),
            ) );

        $controller = $this->resolve_controller( 'cup_match_admin_controller', array(
            'competition_service' => $competition_service,
            'league_repository'   => $league_repo,
            'fixture_repository'  => $fixture_repo,
            'team_repository'     => $team_repo,
        ) );

        $result = $controller->match_page( array(
            'competition_id' => '1',
            'league'         => '10',
            'season'         => '2026',
            'edit'           => '50',
            'final'          => 'final',
        ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->is_finals );
        self::assertTrue( $result['view_model']->single_cup_game );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'edit', $result['view_model']->mode );
        self::assertSame( 'Home Club A', $result['view_model']->home_title );
        self::assertSame( 'Away Club B', $result['view_model']->away_title );
        self::assertSame( 'final', $result['view_model']->final_key );
        self::assertSame( 2, $result['view_model']->match_day );
    }

    public function test_post_handled_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willReturn( new \Racketmanager\Domain\DTO\Admin\Action_Result_DTO(
                message: 'Match saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'fixtures'
            ) );

        $controller = $this->resolve_controller( 'cup_match_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->match_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'match',
            'competition_id' => '1',
            'league'         => '10',
            'season'         => '2026',
            'edit'           => '50',
        ), array(
            'updateLeague' => 'fixture',
            'league_id'    => '10',
            'season'       => '2026',
        ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertSame( 'Match saved', $result['message'] );
    }

    public function test_post_exception_returns_error_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $draw_handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $draw_handler->expects( self::once() )
            ->method( 'manage_fixtures_in_league' )
            ->willThrowException( new Invalid_Status_Exception( 'Failed updating match' ) );

        $controller = $this->resolve_controller( 'cup_match_admin_controller', array(
            'draw_action_handler' => $draw_handler,
        ) );

        $result = $controller->match_page( array(
            'page'           => 'racketmanager-cups',
            'view'           => 'match',
            'competition_id' => '1',
            'league'         => '10',
            'edit'           => '50',
        ), array(
            'updateLeague' => 'fixture',
            'league_id'    => '10',
        ) );

        unset( $_SERVER['REQUEST_METHOD'] );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame( 'Failed updating match', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
