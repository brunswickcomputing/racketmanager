<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Teams_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model;
use Racketmanager\Domain\League;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../wp-stubs.php';


use Racketmanager\Domain\Event;

final class Tournament_Teams_Admin_Controller_Test extends TestCase {

    public function test_teams_page_returns_view_model_on_get(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service     = $this->createMock( League_Service::class );
        $team_service       = $this->createMock( Team_Service::class );
        $guard              = $this->createMock( Action_Guard_Interface::class );

        $league_id = 1;

        $league               = $this->createMock( League::class );
        $league->type         = 'M';
        $league->championship = null;

        $event              = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_player_entry' => false ];
        $league->event      = $event;

        $league_service->method( 'get_league' )->with( $league_id )->willReturn( $league );
        $team_service->method( 'get_club_teams' )->willReturn( [] );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $query  = [ 'league_id' => (string) $league_id ];
        $result = $controller->teams_page( $query, [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Teams_List_Page_View_Model::class, $result['view_model'] );
    }

    public function test_teams_page_handles_post_request(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service     = $this->createMock( League_Service::class );
        $team_service       = $this->createMock( Team_Service::class );
        $guard              = $this->createMock( Action_Guard_Interface::class );

        $league_id = 1;
        $league    = $this->createMock( League::class );
        $league_service->method( 'get_league' )->with( $league_id )->willReturn( $league );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $query = [ 'league_id' => (string) $league_id ];
        $post  = [
            'team'   => [ '10', '11' ],
            'season' => '2024'
        ];

        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $league_service->expects( self::once() )
                       ->method( 'add_teams_to_league' )
                       ->with( [ 10, 11 ], $league_id, 2024 )
                       ->willReturn( 2 );

        $result = $controller->teams_page( $query, $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( '2 teams added', $result['message'] );

        // Clean up
        unset( $_SERVER['REQUEST_METHOD'] );
    }

    public function test_teams_page_throws_exception_if_league_missing(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service     = $this->createMock( League_Service::class );
        $team_service       = $this->createMock( Team_Service::class );
        $guard              = $this->createMock( Action_Guard_Interface::class );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'League not found' );

        $controller->teams_page( [], [] );
    }
}
